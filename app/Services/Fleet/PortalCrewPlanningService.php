<?php

namespace App\Services\Fleet;

use App\Models\FleetVessel;
use App\Models\PoolCandidate;
use App\Models\VesselAssignment;
use App\Models\VesselManningRequirement;
use App\Models\SeafarerCertificate;
use Illuminate\Support\Collection;

class PortalCrewPlanningService
{
    // ── Availability Weights (Part 1) ──
    private const AVAIL_WEIGHT = [
        'available'      => 20,
        'soon_available' => 12, // adjusted by days below
        'on_contract'    => -10,
        'unknown'        => -5,
    ];

    private const URGENCY_BOOST_THRESHOLD = 15; // days
    private const URGENCY_BOOST_SCORE     = 10;

    // ── Gap urgency priority ordering (Part 2) ──
    private const CRITICAL_PRIORITY = ['available', 'soon_available', 'on_contract', 'unknown'];
    private const PLANNING_PRIORITY = ['soon_available', 'available', 'unknown', 'on_contract'];

    /**
     * Analyse crew gaps for a fleet vessel based on manning requirements vs assignments.
     */
    public function analyseVessel(FleetVessel $vessel): array
    {
        $requirements = $vessel->manningRequirements()->get();
        $activeAssignments = $vessel->assignments()
            ->whereIn('status', ['planned', 'onboard'])
            ->get();

        $gaps = [];
        $totalRequired = 0;
        $totalFilled = 0;

        foreach ($requirements as $req) {
            $filled = $activeAssignments->where('rank_code', $req->rank_code)->count();
            $open = max(0, $req->required_count - $filled);
            $totalRequired += $req->required_count;
            $totalFilled += $filled;

            // Urgency: check nearest contract end for this rank
            $nearestEnd = $activeAssignments
                ->where('rank_code', $req->rank_code)
                ->sortBy('contract_end_at')
                ->first();

            $urgencyBucket = 'none';
            if ($open > 0) {
                $urgencyBucket = 'immediate';
            } elseif ($nearestEnd && $nearestEnd->contract_end_at) {
                $days = (int) now()->startOfDay()->diffInDays($nearestEnd->contract_end_at, false);
                if ($days <= 30) $urgencyBucket = '30d';
                elseif ($days <= 60) $urgencyBucket = '60d';
                elseif ($days <= 90) $urgencyBucket = '90d';
            }

            $gaps[] = [
                'rank_code' => $req->rank_code,
                'required_count' => $req->required_count,
                'filled_count' => $filled,
                'open_count' => $open,
                'urgency_bucket' => $urgencyBucket,
                'nearest_contract_end' => $nearestEnd?->contract_end_at?->toDateString(),
            ];
        }

        $totalOpen = max(0, $totalRequired - $totalFilled);

        return [
            'vessel' => [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'vessel_type' => $vessel->vessel_type,
                'crew_size' => $vessel->crew_size,
            ],
            'gaps' => $gaps,
            'summary' => [
                'total_required' => $totalRequired,
                'total_filled' => $totalFilled,
                'total_open' => $totalOpen,
                'critical_gaps' => collect($gaps)->whereIn('urgency_bucket', ['immediate', '30d'])->count(),
                'high_fit_count' => 0, // populated after recommendations
            ],
        ];
    }

    /**
     * Recommend candidates for a specific rank gap on a fleet vessel.
     * Scoring: base_fit + availability_weight + compliance_weight + vessel_fit_weight
     */
    public function recommendForGap(
        FleetVessel $vessel,
        string $rankCode,
        int $limit = 10,
        string $urgencyBucket = 'immediate',
        ?string $contractWindowStart = null,
    ): array {
        $assignedIds = $vessel->assignments()
            ->whereIn('status', ['planned', 'onboard'])
            ->pluck('candidate_id');

        $candidates = PoolCandidate::query()
            ->where('seafarer', true)
            ->whereIn('status', ['in_pool', 'assessed'])
            ->whereNotNull('email_verified_at')
            ->where('is_demo', false)
            ->whereNotIn('id', $assignedIds)
            ->limit($limit * 5)
            ->get();

        $now = now()->startOfDay();

        $scored = $candidates->map(function (PoolCandidate $c) use ($vessel, $now, $urgencyBucket, $contractWindowStart) {
            // ── Base fit score ──
            $baseFit = $this->computeBaseFitScore($c);
            $fitLevel = $this->scoresToFitLevel($baseFit);

            // ── Availability assessment (Part 1) ──
            $avail = $this->computeAvailability($c, $now);
            $availWeight = $this->computeAvailabilityWeight($avail);

            // ── Compliance weight ──
            $certResult = $this->assessCertCompliance($c);
            $complianceWeight = match ($certResult['status']) {
                'ok' => 10,
                'warn' => 0,
                'critical' => -15,
                default => -5,
            };

            // ── Vessel fit weight ──
            $vesselFitWeight = 0;
            $vesselFitEvidence = 'insufficient_data';

            // ── Contract collision detection (Part 4) ──
            $conflicts = [];
            if ($avail['status'] === 'on_contract' && $avail['contract_end_estimate'] && $contractWindowStart) {
                $contractEnd = \Carbon\Carbon::parse($avail['contract_end_estimate']);
                $windowStart = \Carbon\Carbon::parse($contractWindowStart);
                if ($contractEnd->gt($windowStart)) {
                    $conflicts[] = [
                        'conflict' => 'contract_overlap',
                        'severity' => 'medium',
                        'detail' => 'Current contract ends ' . $contractEnd->toDateString() . ', overlaps vessel start window.',
                    ];
                }
            }

            // ── Total score ──
            $totalScore = $baseFit + $availWeight + $complianceWeight + $vesselFitWeight;

            // ── Operational readiness/risks (Part 5) ──
            $opReadiness = [];
            $opRisks = [];

            if ($avail['status'] === 'available') $opReadiness[] = 'Available now';
            if ($certResult['status'] === 'ok') $opReadiness[] = 'Certificate compliant';
            if (empty($conflicts) && in_array($avail['status'], ['available', 'soon_available'])) {
                $opReadiness[] = 'Contract aligned';
            }

            if (!empty($conflicts)) $opRisks[] = 'Contract overlap';
            if ($certResult['status'] === 'critical') $opRisks[] = 'Certificate expiring';
            if ($certResult['status'] === 'warn') $opRisks[] = 'Certificate attention needed';
            if ($avail['status'] === 'unknown') $opRisks[] = 'Availability uncertain';
            if ($avail['status'] === 'on_contract' && !$avail['contract_end_estimate']) {
                $opRisks[] = 'Contract end date unknown';
            }

            return [
                'candidate_id' => $c->id,
                'name' => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                'nationality' => $c->nationality,
                'fit_level' => $fitLevel,
                'total_score' => max(0, min(100, $totalScore)),
                'score_breakdown' => [
                    'base_fit' => $baseFit,
                    'availability' => $availWeight,
                    'compliance' => $complianceWeight,
                    'vessel_fit' => $vesselFitWeight,
                ],
                'availability' => $avail['label'],
                'availability_status' => $avail['status'],
                'days_to_available' => $avail['days_to_available'],
                'contract_end_estimate' => $avail['contract_end_estimate'],
                'cert_compliance' => $certResult['status'],
                'cert_details' => $certResult['details'],
                'vessel_fit_evidence' => $vesselFitEvidence,
                'conflicts' => $conflicts,
                'operational_readiness' => $opReadiness,
                'operational_risks' => $opRisks,
                'notes' => $this->buildNotes($c, $fitLevel, $avail, $certResult),
                '_avail_status_raw' => $avail['status'], // for sorting
            ];
        });

        // ── Part 2: Gap urgency coupling (priority sorting) ──
        $isCritical = in_array($urgencyBucket, ['immediate', '30d']);
        $priorityOrder = $isCritical ? self::CRITICAL_PRIORITY : self::PLANNING_PRIORITY;

        $sorted = $scored->sort(function ($a, $b) use ($priorityOrder) {
            $aPriority = array_search($a['_avail_status_raw'], $priorityOrder);
            $bPriority = array_search($b['_avail_status_raw'], $priorityOrder);
            if ($aPriority === false) $aPriority = 99;
            if ($bPriority === false) $bPriority = 99;

            // First sort by availability priority
            if ($aPriority !== $bPriority) {
                return $aPriority <=> $bPriority;
            }
            // Then by total score descending
            return $b['total_score'] <=> $a['total_score'];
        });

        // Separate into shortlist and future pool
        $results = $sorted->map(function ($r) {
            unset($r['_avail_status_raw']);
            return $r;
        })->take($limit)->values()->toArray();

        return $results;
    }

    /**
     * Part 3: Future pool — upcoming candidates within 120 days.
     */
    public function futurePool(FleetVessel $vessel, string $rankCode, int $limit = 10): array
    {
        $assignedIds = $vessel->assignments()
            ->whereIn('status', ['planned', 'onboard'])
            ->pluck('candidate_id');

        $now = now()->startOfDay();
        $cutoff = $now->copy()->addDays(120);

        $candidates = PoolCandidate::query()
            ->where('seafarer', true)
            ->whereIn('status', ['in_pool', 'assessed'])
            ->whereNotNull('email_verified_at')
            ->where('is_demo', false)
            ->whereNotIn('id', $assignedIds)
            ->where(function ($q) use ($cutoff) {
                $q->where('availability_status', 'soon_available')
                  ->orWhere(function ($sub) use ($cutoff) {
                      $sub->where('availability_status', 'on_contract')
                          ->whereNotNull('contract_end_estimate')
                          ->where('contract_end_estimate', '<=', $cutoff);
                  });
            })
            ->orderBy('contract_end_estimate')
            ->limit($limit)
            ->get();

        return $candidates->map(function (PoolCandidate $c) use ($now) {
            $avail = $this->computeAvailability($c, $now);
            $certResult = $this->assessCertCompliance($c);

            return [
                'candidate_id' => $c->id,
                'name' => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                'nationality' => $c->nationality,
                'availability_status' => $avail['status'],
                'days_to_available' => $avail['days_to_available'],
                'contract_end_estimate' => $avail['contract_end_estimate'],
                'available_label' => $avail['days_to_available'] !== null
                    ? "Available in {$avail['days_to_available']} days"
                    : 'Availability unknown',
                'cert_compliance' => $certResult['status'],
                'cert_risk_preview' => $certResult['details'],
            ];
        })->values()->toArray();
    }

    // ═══════════════════════════════════════════
    // Private scoring methods
    // ═══════════════════════════════════════════

    private function computeBaseFitScore(PoolCandidate $c): int
    {
        $interview = $c->formInterviews()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        if (!$interview) return 30; // baseline for unassessed

        return (int) min(100, max(0, $interview->final_score ?? 0));
    }

    private function scoresToFitLevel(int $score): string
    {
        if ($score >= 70) return 'high';
        if ($score >= 50) return 'medium';
        if ($score >= 30) return 'insufficient_data';
        return 'risk_note';
    }

    private function computeAvailability(PoolCandidate $c, \DateTimeInterface $now): array
    {
        $status = $c->availability_status ?? 'unknown';
        $contractEnd = $c->contract_end_estimate;
        $daysTo = null;

        if ($status === 'available') {
            $daysTo = 0;
            $label = 'available';
        } elseif ($status === 'soon_available') {
            $daysTo = $contractEnd
                ? max(0, (int) \Carbon\Carbon::parse($now)->diffInDays($contractEnd, false))
                : null;
            $label = 'soon';
        } elseif ($status === 'on_contract') {
            $daysTo = $contractEnd
                ? max(0, (int) \Carbon\Carbon::parse($now)->diffInDays($contractEnd, false))
                : null;
            $label = $contractEnd ? 'soon' : 'unknown';
            // Re-classify: on_contract with known end within 60d → soon_available for scoring
            if ($daysTo !== null && $daysTo <= 60) {
                $status = 'soon_available';
            }
        } else {
            $label = 'unknown';
        }

        return [
            'status' => $status,
            'label' => $label,
            'days_to_available' => $daysTo,
            'contract_end_estimate' => $contractEnd?->toDateString(),
        ];
    }

    private function computeAvailabilityWeight(array $avail): int
    {
        $status = $avail['status'];
        $daysTo = $avail['days_to_available'];

        $base = self::AVAIL_WEIGHT[$status] ?? -5;

        // Graduated weight for soon_available
        if ($status === 'soon_available' && $daysTo !== null) {
            if ($daysTo <= 30) {
                $base = 12;
            } elseif ($daysTo <= 60) {
                $base = 6;
            } else {
                $base = 0;
            }
        }

        // Urgency boost for very near availability
        if ($daysTo !== null && $daysTo < self::URGENCY_BOOST_THRESHOLD) {
            $base += self::URGENCY_BOOST_SCORE;
        }

        return $base;
    }

    private function assessCertCompliance(PoolCandidate $c): array
    {
        $certs = $c->certificates()->get();

        if ($certs->isEmpty()) {
            return ['status' => 'insufficient_data', 'details' => ['No certificates on file']];
        }

        $details = [];
        $hasExpired = false;
        $hasExpiringSoon = false;

        foreach ($certs as $cert) {
            if ($cert->isExpired()) {
                $hasExpired = true;
                $details[] = "{$cert->certificate_type}: expired";
            } elseif ($cert->expires_at && $cert->isExpiringSoon(60)) {
                $hasExpiringSoon = true;
                $days = (int) now()->diffInDays($cert->expires_at, false);
                $details[] = "{$cert->certificate_type}: expires in {$days}d";
            }
        }

        if ($hasExpired) {
            return ['status' => 'critical', 'details' => $details];
        }
        if ($hasExpiringSoon) {
            return ['status' => 'warn', 'details' => $details];
        }

        return ['status' => 'ok', 'details' => ['All certificates valid']];
    }

    private function buildNotes(PoolCandidate $c, string $fitLevel, array $avail, array $certResult): array
    {
        $notes = [];
        if ($fitLevel === 'high') $notes[] = 'Strong assessment profile';
        if ($fitLevel === 'risk_note') $notes[] = 'Review assessment details';
        if ($fitLevel === 'insufficient_data') $notes[] = 'Assessment pending';

        if ($avail['status'] === 'available') $notes[] = 'Ready to deploy';
        elseif ($avail['days_to_available'] !== null && $avail['days_to_available'] <= 30) {
            $notes[] = "Available in {$avail['days_to_available']}d";
        }

        if ($certResult['status'] === 'critical') $notes[] = 'Certificate action needed';

        if ($c->english_level_self && in_array($c->english_level_self, ['advanced', 'fluent', 'native'])) {
            $notes[] = 'Strong English';
        }

        return array_slice($notes, 0, 3);
    }
}
