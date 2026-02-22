<?php

namespace App\Services\Trust;

use App\Models\BehavioralProfile;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\TrustEvent;

class CrewReliabilityCalculator
{
    public function __construct(
        private ContractPatternAnalyzer $contractAnalyzer,
        private RankProgressionAnalyzer $rankAnalyzer,
    ) {}

    /**
     * Compute (or recompute) CRI for a candidate and persist the result.
     * Fail-open: catch exceptions, return null, log warning.
     */
    public function compute(string $poolCandidateId): ?CandidateTrustProfile
    {
        try {
            return $this->doCompute($poolCandidateId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('daily')->warning('[CRI] compute failed', [
                'candidate_id' => $poolCandidateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doCompute(string $poolCandidateId): CandidateTrustProfile
    {
        $candidate = PoolCandidate::with([
            'contracts' => fn($q) => $q->orderBy('start_date'),
        ])->findOrFail($poolCandidateId);

        $contracts = $candidate->contracts;

        // ── Run analyzers ──
        $contractAnalysis = $this->contractAnalyzer->analyze($contracts);
        $rankAnalysis = $this->rankAnalyzer->analyze($contracts);
        $behavioralData = $this->getLatestBehavioral($candidate);

        // ── Score each block (0–25) ──
        $consistency = $this->scoreConsistency($contractAnalysis);
        $rankIntegrity = $this->scoreRankIntegrity($rankAnalysis, $contractAnalysis);
        $timelineCoherence = $this->scoreTimelineCoherence($contractAnalysis);
        $behavioralStability = $this->scoreBehavioralStability($behavioralData);

        $criScore = (int) round(min(max(
            $consistency + $rankIntegrity + $timelineCoherence + $behavioralStability,
            0
        ), 100));

        // ── Confidence level ──
        $totalContracts = $contractAnalysis['total_contracts'];
        if ($totalContracts < 2) {
            $confidenceLevel = CandidateTrustProfile::CONFIDENCE_LOW;
        } elseif ($totalContracts <= 5) {
            $confidenceLevel = CandidateTrustProfile::CONFIDENCE_MEDIUM;
        } else {
            $confidenceLevel = CandidateTrustProfile::CONFIDENCE_HIGH;
        }

        // ── Aggregate flags ──
        $allFlags = array_merge($contractAnalysis['flags'], $rankAnalysis['flags']);

        $rankAnomalyFlag = in_array('FLAG_RANK_ANOMALY', $allFlags)
            || in_array('FLAG_UNREALISTIC_PROMOTION', $allFlags);
        $frequentSwitchFlag = in_array('FLAG_FREQUENT_SWITCH', $allFlags);
        $timelineInconsistencyFlag = in_array('FLAG_OVERLAP', $allFlags)
            || in_array('FLAG_LONG_GAP', $allFlags);

        // ── Persist ──
        $trustProfile = CandidateTrustProfile::updateOrCreate(
            ['pool_candidate_id' => $poolCandidateId],
            [
                'cri_score' => $criScore,
                'confidence_level' => $confidenceLevel,
                'short_contract_ratio' => $contractAnalysis['short_contract_ratio'],
                'overlap_count' => $contractAnalysis['overlap_count'],
                'gap_months_total' => (int) round($contractAnalysis['total_gap_months']),
                'unique_company_count_3y' => $contractAnalysis['recent_unique_companies_3y'],
                'rank_anomaly_flag' => $rankAnomalyFlag,
                'frequent_switch_flag' => $frequentSwitchFlag,
                'timeline_inconsistency_flag' => $timelineInconsistencyFlag,
                'flags_json' => $allFlags,
                'detail_json' => [
                    'scores' => [
                        'contract_consistency' => $consistency,
                        'rank_integrity' => $rankIntegrity,
                        'timeline_coherence' => $timelineCoherence,
                        'behavioral_stability' => $behavioralStability,
                    ],
                    'contract_analysis' => $contractAnalysis,
                    'rank_analysis' => $rankAnalysis,
                ],
                'computed_at' => now(),
            ]
        );

        // ── Audit event ──
        TrustEvent::create([
            'pool_candidate_id' => $poolCandidateId,
            'event_type' => TrustEvent::TYPE_RECOMPUTE,
            'payload_json' => [
                'cri_score' => $criScore,
                'confidence_level' => $confidenceLevel,
                'flags' => $allFlags,
            ],
        ]);

        return $trustProfile;
    }

    // ────────────────────────────────────────────────────────
    //  Block scorers (user spec v1 formula)
    // ────────────────────────────────────────────────────────

    /**
     * Consistency (25):
     * 25 - min(25, short_contract_ratio*25 + overlap_count*10 + (gap_months_total/6)*5)
     */
    private function scoreConsistency(array $a): float
    {
        if ($a['total_contracts'] === 0) {
            return 0;
        }

        $penalty = $a['short_contract_ratio'] * 25
            + $a['overlap_count'] * 10
            + ($a['total_gap_months'] / 6) * 5;

        return round(25 - min(25, $penalty), 2);
    }

    /**
     * RankIntegrity (25): 25 - (rank_anomaly ? 15 : 0)
     */
    private function scoreRankIntegrity(array $rank, array $contract): float
    {
        if ($contract['total_contracts'] === 0) {
            return 25; // no data = no penalty
        }

        $hasAnomaly = !empty($rank['anomalies']);
        return $hasAnomaly ? 10.0 : 25.0;
    }

    /**
     * TimelineCoherence (25): 25 - (timeline_inconsistent ? 20 : 0)
     */
    private function scoreTimelineCoherence(array $a): float
    {
        if ($a['total_contracts'] === 0) {
            return 25;
        }

        $inconsistent = $a['overlap_count'] > 0 || $a['total_gap_months'] > 18;
        return $inconsistent ? 5.0 : 25.0;
    }

    /**
     * BehavioralStability (25):
     * If behavioral exists: 25 - min(25, manipulation_flags_count * 5)
     * Else: 20 (neutral)
     */
    private function scoreBehavioralStability(?array $behavioralData): float
    {
        if ($behavioralData === null) {
            return 20; // neutral – no behavioral data
        }

        $manipulationCount = 0;
        $flags = $behavioralData['flags'] ?? [];
        foreach ($flags as $flag) {
            $type = $flag['type'] ?? '';
            $severity = $flag['severity'] ?? 'low';
            if ($type === 'manipulation' || $severity === 'high') {
                $manipulationCount++;
            }
        }

        return round(25 - min(25, $manipulationCount * 5), 2);
    }

    /**
     * Get the latest behavioral profile for a candidate.
     */
    private function getLatestBehavioral(PoolCandidate $candidate): ?array
    {
        try {
            $interview = $candidate->formInterviews()
                ->where('status', 'completed')
                ->whereNotNull('behavioral_profile_id')
                ->latest('completed_at')
                ->first();

            if (!$interview || !$interview->behavioral_profile_id) {
                return null;
            }

            $profile = BehavioralProfile::find($interview->behavioral_profile_id);
            if (!$profile) {
                return null;
            }

            return [
                'confidence' => $profile->confidence ?? 0,
                'dimensions' => $profile->dimensions_json ?? [],
                'flags' => $profile->flags_json ?? [],
            ];
        } catch (\Throwable) {
            return null; // fail-open
        }
    }
}
