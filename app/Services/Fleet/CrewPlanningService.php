<?php

namespace App\Services\Fleet;

use App\Models\CandidateContract;
use App\Models\PoolCandidate;
use App\Models\Vessel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CrewPlanningService
{
    /**
     * Get upcoming crew gaps for a vessel (contracts ending within X days).
     */
    public function getUpcomingGaps(string $vesselId, int $daysAhead = 90): array
    {
        $vessel = Vessel::find($vesselId);
        if (!$vessel) {
            return ['vessel' => null, 'gaps' => []];
        }

        $cutoff = now()->addDays($daysAhead);

        // Active crew + those ending soon
        $contracts = CandidateContract::query()
            ->where(function ($q) use ($vesselId, $vessel) {
                $q->where('vessel_id', $vesselId)
                    ->orWhere('vessel_imo', $vessel->imo);
            })
            ->where(function ($q) use ($cutoff) {
                // Active (no end_date) OR ending within daysAhead
                $q->whereNull('end_date')
                    ->orWhere('end_date', '<=', $cutoff);
            })
            ->with('poolCandidate:id,first_name,last_name,rank,email')
            ->orderBy('end_date')
            ->get();

        $gaps = [];
        foreach ($contracts as $contract) {
            if (!$contract->end_date) {
                continue; // Active, no gap
            }

            $daysUntilEnd = (int) now()->diffInDays($contract->end_date, false);
            if ($daysUntilEnd < 0) {
                $daysUntilEnd = 0; // Already ended, gap is now
            }

            $gaps[] = [
                'contract_id' => $contract->id,
                'rank_code' => $contract->rank_code,
                'candidate_name' => $contract->poolCandidate?->full_name ?? 'Unknown',
                'candidate_id' => $contract->pool_candidate_id,
                'end_date' => $contract->end_date?->toDateString(),
                'days_until_gap' => $daysUntilEnd,
                'urgency' => $daysUntilEnd <= 14 ? 'critical' : ($daysUntilEnd <= 30 ? 'high' : 'normal'),
            ];
        }

        // Sort by urgency (soonest first)
        usort($gaps, fn($a, $b) => $a['days_until_gap'] <=> $b['days_until_gap']);

        return [
            'vessel' => [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'imo' => $vessel->imo,
                'type' => $vessel->type,
            ],
            'gaps' => $gaps,
            'active_crew_count' => $contracts->whereNull('end_date')->count(),
            'gaps_count' => count($gaps),
        ];
    }

    /**
     * Recommend candidates for a specific role on a vessel.
     */
    public function recommendCandidates(string $vesselId, string $rankCode, int $limit = 10): array
    {
        $vessel = Vessel::find($vesselId);
        if (!$vessel) {
            return [];
        }

        // Find eligible candidates: in_pool/assessed, verified email, matching rank, not already on this vessel
        $activeOnVessel = CandidateContract::query()
            ->where(function ($q) use ($vesselId, $vessel) {
                $q->where('vessel_id', $vesselId)
                    ->orWhere('vessel_imo', $vessel->imo);
            })
            ->whereNull('end_date')
            ->pluck('pool_candidate_id');

        $candidates = PoolCandidate::query()
            ->where('primary_industry', 'maritime')
            ->whereIn('status', [PoolCandidate::STATUS_ASSESSED, PoolCandidate::STATUS_IN_POOL])
            ->where('rank', $rankCode)
            ->whereNotNull('email_verified_at')
            ->whereNotIn('id', $activeOnVessel)
            ->with(['formInterviews' => function ($q) {
                $q->where('status', 'completed')
                    ->latest('completed_at')
                    ->limit(1);
            }])
            ->limit($limit * 3) // Over-fetch for ranking
            ->get();

        $now = now();

        // Score and rank
        $scored = $candidates->map(function ($candidate) use ($vessel) {
            $latestInterview = $candidate->formInterviews->first();
            $score = $latestInterview?->final_score ?? 0;
            $decision = $latestInterview?->decision;

            // Vessel type match bonus
            $vesselTypeBonus = 0;
            $matchingContracts = $candidate->contracts()
                ->where('vessel_type', $vessel->type)
                ->count();
            if ($matchingContracts > 0) {
                $vesselTypeBonus = min($matchingContracts * 5, 15);
            }

            $fitScore = $score + $vesselTypeBonus;
            if ($decision === 'HIRE') $fitScore += 10;
            if ($decision === 'REJECT') $fitScore -= 30;

            // Availability from lifecycle fields
            $availStatus = $candidate->availability_status ?? 'unknown';
            $contractEnd = $candidate->contract_end_estimate;
            $daysToAvailable = null;

            if ($availStatus === 'available') {
                $daysToAvailable = 0;
            } elseif ($availStatus === 'on_contract' && $contractEnd) {
                $daysToAvailable = max(0, (int) $now->diffInDays($contractEnd, false));
                // on_contract with date → treat as soon
                $availStatus = 'soon_available';
            } elseif ($availStatus === 'soon_available') {
                $daysToAvailable = $contractEnd ? max(0, (int) $now->diffInDays($contractEnd, false)) : null;
            }

            return [
                'candidate_id' => $candidate->id,
                'name' => $candidate->full_name,
                'rank' => $candidate->rank,
                'email' => $candidate->email,
                'assessment_score' => $score,
                'decision' => $decision,
                'vessel_type_experience' => $matchingContracts,
                'fit_score' => max(0, min(100, $fitScore)),
                'availability_status' => $availStatus,
                'contract_end_estimate' => $contractEnd?->toDateString(),
                'days_to_available' => $daysToAvailable,
            ];
        })
            ->sortByDesc('fit_score')
            ->take($limit)
            ->values()
            ->toArray();

        return $scored;
    }

    /**
     * Fleet-wide gap analysis: all vessels with upcoming crew gaps.
     */
    public function fleetGapAnalysis(int $daysAhead = 90): array
    {
        $cutoff = now()->addDays($daysAhead);

        $gaps = DB::table('candidate_contracts')
            ->join('vessels', function ($join) {
                $join->on('candidate_contracts.vessel_id', '=', 'vessels.id')
                    ->orOn('candidate_contracts.vessel_imo', '=', 'vessels.imo');
            })
            ->whereNotNull('candidate_contracts.end_date')
            ->where('candidate_contracts.end_date', '<=', $cutoff)
            ->where('candidate_contracts.end_date', '>=', now()->subDays(7)) // Include recently ended
            ->select(
                'vessels.id as vessel_id',
                'vessels.name as vessel_name',
                'vessels.imo',
                'vessels.type as vessel_type',
                DB::raw('COUNT(*) as gap_count'),
                DB::raw('MIN(candidate_contracts.end_date) as earliest_gap')
            )
            ->groupBy('vessels.id', 'vessels.name', 'vessels.imo', 'vessels.type')
            ->orderBy('earliest_gap')
            ->get();

        return $gaps->map(function ($row) {
            $daysUntil = (int) now()->diffInDays($row->earliest_gap, false);
            return [
                'vessel_id' => $row->vessel_id,
                'vessel_name' => $row->vessel_name,
                'imo' => $row->imo,
                'vessel_type' => $row->vessel_type,
                'gap_count' => $row->gap_count,
                'earliest_gap' => $row->earliest_gap,
                'days_until_earliest' => max(0, $daysUntil),
                'urgency' => $daysUntil <= 14 ? 'critical' : ($daysUntil <= 30 ? 'high' : 'normal'),
            ];
        })->toArray();
    }
}
