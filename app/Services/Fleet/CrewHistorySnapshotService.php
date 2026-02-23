<?php

namespace App\Services\Fleet;

use App\Models\CandidateTrustProfile;
use App\Models\CrewHistorySnapshot;
use App\Models\PoolCandidate;
use App\Models\Vessel;
use Illuminate\Support\Facades\DB;

class CrewHistorySnapshotService
{
    private const COMPETENCY_DIMENSIONS = [
        'DISCIPLINE', 'LEADERSHIP', 'STRESS', 'TEAMWORK', 'COMMS', 'TECH_PRACTICAL',
    ];

    /**
     * Take a snapshot of the current crew roster for a vessel.
     */
    public function takeSnapshot(string $vesselId, string $trigger = 'scheduled'): ?CrewHistorySnapshot
    {
        $vessel = Vessel::find($vesselId);
        if (!$vessel) {
            return null;
        }

        $slots = DB::table('vessel_crew_skeleton_slots')
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->whereNotNull('candidate_id')
            ->get();

        if ($slots->isEmpty()) {
            return null;
        }

        $candidateIds = $slots->pluck('candidate_id')->unique();
        $candidates = PoolCandidate::whereIn('id', $candidateIds)->get()->keyBy('id');
        $trustProfiles = CandidateTrustProfile::whereIn('pool_candidate_id', $candidateIds)
            ->get()
            ->keyBy('pool_candidate_id');

        $synergyEngine = app(CrewSynergyEngineV2::class);
        $allDimSets = [];
        $synergyScores = [];

        $roster = [];
        foreach ($slots as $slot) {
            $cand = $candidates[$slot->candidate_id] ?? null;
            $trust = $trustProfiles[$slot->candidate_id] ?? null;
            $dims = $this->extractDimensions($trust);
            $allDimSets[] = $dims;

            // Compute synergy if v2 enabled
            $synergyScore = null;
            if ($synergyEngine->isEnabled()) {
                $result = $synergyEngine->computeCompatibility($slot->candidate_id, $vesselId);
                $synergyScore = $result['compatibility_score'] ?? null;
                if ($synergyScore !== null) {
                    $synergyScores[] = $synergyScore;
                }
            }

            $roster[] = [
                'candidate_id' => $slot->candidate_id,
                'rank_code' => $slot->slot_role,
                'name' => $cand ? ($cand->first_name . ' ' . $cand->last_name) : 'Unknown',
                'start_date' => $slot->assigned_at ?? null,
                'dimensions' => $dims,
                'synergy_score' => $synergyScore,
            ];
        }

        // Compute dimension averages
        $dimensionAverages = $this->computeAverages($allDimSets);
        $avgSynergy = !empty($synergyScores) ? round(array_sum($synergyScores) / count($synergyScores), 1) : null;

        return CrewHistorySnapshot::updateOrCreate(
            ['vessel_id' => $vesselId, 'snapshot_date' => now()->toDateString()],
            [
                'crew_roster' => $roster,
                'dimension_averages' => $dimensionAverages,
                'avg_synergy_score' => $avgSynergy,
                'crew_count' => count($roster),
                'trigger' => $trigger,
            ]
        );
    }

    /**
     * Get snapshot history for a vessel.
     */
    public function getHistory(string $vesselId, int $months = 6): array
    {
        return CrewHistorySnapshot::where('vessel_id', $vesselId)
            ->where('snapshot_date', '>=', now()->subMonths($months))
            ->orderBy('snapshot_date', 'desc')
            ->get()
            ->toArray();
    }

    private function extractDimensions(?CandidateTrustProfile $profile): array
    {
        if (!$profile) {
            return [];
        }

        $detail = $profile->detail_json ?? [];
        $dimensions = $detail['competency_engine']['score_by_dimension']
            ?? $detail['competency']['dimensions']
            ?? [];

        $result = [];
        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $val = $dimensions[$dim] ?? null;
            if (is_array($val)) {
                $val = $val['score'] ?? null;
            }
            $result[$dim] = $val !== null ? (int) $val : null;
        }
        return $result;
    }

    private function computeAverages(array $dimensionSets): array
    {
        $sums = [];
        $counts = [];

        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $sums[$dim] = 0;
            $counts[$dim] = 0;
        }

        foreach ($dimensionSets as $set) {
            foreach (self::COMPETENCY_DIMENSIONS as $dim) {
                if (isset($set[$dim]) && $set[$dim] !== null) {
                    $sums[$dim] += $set[$dim];
                    $counts[$dim]++;
                }
            }
        }

        $avgs = [];
        foreach (self::COMPETENCY_DIMENSIONS as $dim) {
            $avgs[$dim] = $counts[$dim] > 0 ? round($sums[$dim] / $counts[$dim], 1) : null;
        }
        return $avgs;
    }
}
