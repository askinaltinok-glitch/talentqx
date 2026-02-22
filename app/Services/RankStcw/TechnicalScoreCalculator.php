<?php

namespace App\Services\RankStcw;

use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\TrustEvent;
use Illuminate\Support\Facades\Log;

/**
 * TechnicalScore =
 *   (RankDaysWeight × 0.4)
 * + (VesselMatchWeight × 0.3)
 * + (CertificationMatchWeight × 0.3)
 *
 * Each sub-weight is 0.0–1.0, producing a final score 0.0–1.0.
 * Stored in trust_profile.detail_json['rank_stcw'].
 */
class TechnicalScoreCalculator
{
    private StcwComplianceChecker $complianceChecker;
    private PromotionGapCalculator $promotionGapCalc;

    public function __construct(
        StcwComplianceChecker $complianceChecker,
        PromotionGapCalculator $promotionGapCalc,
    ) {
        $this->complianceChecker = $complianceChecker;
        $this->promotionGapCalc = $promotionGapCalc;
    }

    /**
     * Compute the full Rank & STCW technical score for a candidate.
     * Fail-open: catches exceptions, returns null, logs warning.
     */
    public function compute(string $poolCandidateId): ?array
    {
        if (!config('maritime.rank_stcw_v1')) {
            return null;
        }

        try {
            return $this->doCompute($poolCandidateId);
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('[RankSTCW] compute failed', [
                'candidate' => $poolCandidateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doCompute(string $poolCandidateId): ?array
    {
        $candidate = PoolCandidate::find($poolCandidateId);
        if (!$candidate) {
            return null;
        }

        // Step 1: STCW Compliance
        $compliance = $this->complianceChecker->check($poolCandidateId);

        // Step 2: Promotion Gap
        $promotionGap = $this->promotionGapCalc->calculate($poolCandidateId);

        // Step 3: Vessel match weight (from sea_time data in trust profile)
        $vesselMatch = $this->calculateVesselMatchWeight($poolCandidateId);

        // Calculate sub-weights (0.0–1.0)
        $rankDaysWeight = $this->calculateRankDaysWeight($promotionGap);
        $vesselMatchWeight = $vesselMatch['weight'];
        $certificationWeight = $compliance ? $compliance['compliance_ratio'] : 0.0;

        // TechnicalScore = (RankDaysWeight × 0.4) + (VesselMatchWeight × 0.3) + (CertificationMatchWeight × 0.3)
        $technicalScore = round(
            ($rankDaysWeight * 0.4) + ($vesselMatchWeight * 0.3) + ($certificationWeight * 0.3),
            4
        );

        $result = [
            'technical_score' => $technicalScore,
            'rank_days_weight' => round($rankDaysWeight, 4),
            'vessel_match_weight' => round($vesselMatchWeight, 4),
            'certification_weight' => round($certificationWeight, 4),
            'promotion_gap' => $promotionGap,
            'stcw_compliance' => $compliance,
            'vessel_match' => $vesselMatch,
            'computed_at' => now()->toIso8601String(),
        ];

        // Store in trust profile
        $this->storeResult($poolCandidateId, $result);

        // Audit event
        TrustEvent::create([
            'pool_candidate_id' => $poolCandidateId,
            'event_type' => 'rank_stcw_computed',
            'payload_json' => [
                'technical_score' => $technicalScore,
                'rank_days_weight' => $rankDaysWeight,
                'vessel_match_weight' => $vesselMatchWeight,
                'certification_weight' => $certificationWeight,
            ],
        ]);

        return $result;
    }

    /**
     * RankDaysWeight: How well the candidate's time at current rank meets requirements.
     *
     * If promotionGap is null (no data / top rank) → 0.5 (neutral).
     * If promotionGap >= 0 → scale from 0.7 to 1.0 based on how much surplus.
     * If promotionGap < 0  → scale from 0.0 to 0.7 based on progress.
     */
    private function calculateRankDaysWeight(?array $promotionGap): float
    {
        if (!$promotionGap) {
            return 0.5; // no data
        }

        if ($promotionGap['is_top_rank']) {
            // Top rank: score based on total sea time meeting minimum
            $totalGap = $promotionGap['total_gap_days'];
            if ($totalGap === null || $promotionGap['min_total_required'] === 0) {
                return 0.8;
            }
            $ratio = $promotionGap['total_sea_days'] / max($promotionGap['min_total_required'], 1);
            return min(1.0, max(0.3, $ratio));
        }

        $required = $promotionGap['required_rank_days'];
        if ($required === 0) {
            return 0.8; // no requirement for this rank
        }

        $actual = $promotionGap['actual_rank_days'];
        $ratio = $actual / max($required, 1);

        if ($ratio >= 1.0) {
            // Met or exceeded: 0.7 + up to 0.3 bonus (caps at 2x required)
            return min(1.0, 0.7 + 0.3 * min(($ratio - 1.0), 1.0));
        }

        // Below requirement: 0.0 to 0.7 linearly
        return round($ratio * 0.7, 4);
    }

    /**
     * VesselMatchWeight: Based on diversity and depth of vessel type experience.
     *
     * Scoring factors:
     * - Dominant vessel type experience % (higher = more specialized = good for matching)
     * - Number of vessel types experienced (breadth)
     * - Total sea time (more = better baseline)
     */
    private function calculateVesselMatchWeight(string $poolCandidateId): array
    {
        $trustProfile = CandidateTrustProfile::where('pool_candidate_id', $poolCandidateId)->first();
        $seaTime = $trustProfile?->detail_json['sea_time'] ?? null;

        if (!$seaTime || ($seaTime['total_sea_days'] ?? 0) === 0) {
            return ['weight' => 0.0, 'vessel_types' => 0, 'dominant_pct' => 0.0, 'total_days' => 0];
        }

        $vesselTypeDays = $seaTime['vessel_type_days'] ?? [];
        $totalDays = $seaTime['total_sea_days'];
        $vesselTypes = count($vesselTypeDays);

        // Find dominant vessel type percentage
        $dominantPct = 0.0;
        if (!empty($vesselTypeDays)) {
            $maxDays = max($vesselTypeDays);
            $dominantPct = $totalDays > 0 ? ($maxDays / $totalDays) : 0;
        }

        // Score components:
        // 1. Depth: dominant type > 50% of experience → higher score for specialization
        $depthScore = min(1.0, $dominantPct * 1.2); // 0-1.0

        // 2. Breadth: more vessel types = more versatile
        $breadthScore = min(1.0, ($vesselTypes - 1) * 0.25); // 0 types extra → 0, 4+ → 1.0

        // 3. Volume: total days baseline (360 days = 1 year baseline)
        $volumeScore = min(1.0, $totalDays / 720.0); // 2 years → 1.0

        // Weighted: depth 40%, volume 40%, breadth 20%
        $weight = round(
            ($depthScore * 0.4) + ($volumeScore * 0.4) + ($breadthScore * 0.2),
            4
        );

        return [
            'weight' => $weight,
            'vessel_types' => $vesselTypes,
            'dominant_pct' => round($dominantPct, 4),
            'total_days' => $totalDays,
        ];
    }

    private function storeResult(string $poolCandidateId, array $result): void
    {
        $trustProfile = CandidateTrustProfile::firstOrNew(
            ['pool_candidate_id' => $poolCandidateId]
        );

        $detailJson = $trustProfile->detail_json ?? [];
        $detailJson['rank_stcw'] = $result;

        $trustProfile->detail_json = $detailJson;

        if (!$trustProfile->exists) {
            $trustProfile->pool_candidate_id = $poolCandidateId;
            $trustProfile->cri_score = 0;
            $trustProfile->confidence_level = 'low';
            $trustProfile->computed_at = now();
        }

        $trustProfile->save();
    }
}
