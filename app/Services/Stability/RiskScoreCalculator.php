<?php

namespace App\Services\Stability;

/**
 * RiskScore = weighted sum of normalized risk factors (0.0 – 1.0)
 *
 * v1.1: All weights and normalization scales read from StabilityConfig.
 *       No hardcoded constants.
 *       Supports temporal decay, vessel diversity, and promotion context modifiers.
 */
class RiskScoreCalculator
{
    /**
     * Calculate composite risk score from input metrics.
     *
     * @param float $shortRatio  short contract ratio (already 0-1)
     * @param float $totalGapMonths  total gap months
     * @param int $overlapCount  number of overlapping contracts
     * @param bool $rankAnomaly  whether rank anomaly was detected
     * @param int $recentUniqueCompanies3y  unique companies in recent window
     * @param float|null $stabilityIndex  stability index (0-10)
     * @param float $temporalRecencyScore  temporal recency score (0-1, 0=old instability, 1=recent instability)
     * @param float $vesselDiversityScore  vessel diversity score (0-1, higher=more diverse with tenure)
     * @param float $promotionModifier  modifier for promotion context (1.0=no change, <1.0=reduce penalties)
     * @param StabilityConfig|null $cfg  config resolver
     */
    public function calculate(
        float $shortRatio,
        float $totalGapMonths,
        int $overlapCount,
        bool $rankAnomaly,
        int $recentUniqueCompanies3y,
        ?float $stabilityIndex,
        float $temporalRecencyScore = 0.0,
        float $vesselDiversityScore = 0.0,
        float $promotionModifier = 1.0,
        ?StabilityConfig $cfg = null,
    ): array {
        $cfg = $cfg ?? new StabilityConfig();
        $weights = $cfg->getFactorWeights();

        // Normalization caps from config
        $gapCap = (float) $cfg->get('gap_months_norm_cap', 36.0);
        $overlapCap = (float) $cfg->get('overlap_count_norm_cap', 5.0);
        $switchCap = (float) $cfg->get('frequent_switch_norm_cap', 8.0);
        $siPivot = (float) $cfg->get('stability_index_norm_pivot', 5.0);
        $siNeutral = (float) $cfg->get('stability_index_neutral', 0.5);

        $factors = [];

        // 1. Short contract ratio — apply promotion modifier
        $normShort = min(1.0, max(0.0, $shortRatio)) * $promotionModifier;
        $w = $weights['short_ratio'] ?? 0.20;
        $factors['short_ratio'] = [
            'raw' => $shortRatio,
            'normalized' => round($normShort, 4),
            'weight' => $w,
            'contribution' => round($normShort * $w, 4),
        ];

        // 2. Gap months
        $normGap = min(1.0, $totalGapMonths / $gapCap);
        $w = $weights['gap_months'] ?? 0.18;
        $factors['gap_months'] = [
            'raw' => $totalGapMonths,
            'normalized' => round($normGap, 4),
            'weight' => $w,
            'contribution' => round($normGap * $w, 4),
        ];

        // 3. Overlap count
        $normOverlap = min(1.0, $overlapCount / $overlapCap);
        $w = $weights['overlap_count'] ?? 0.18;
        $factors['overlap_count'] = [
            'raw' => $overlapCount,
            'normalized' => round($normOverlap, 4),
            'weight' => $w,
            'contribution' => round($normOverlap * $w, 4),
        ];

        // 4. Rank anomaly (binary)
        $normRank = $rankAnomaly ? 1.0 : 0.0;
        $w = $weights['rank_anomaly'] ?? 0.12;
        $factors['rank_anomaly'] = [
            'raw' => $rankAnomaly,
            'normalized' => $normRank,
            'weight' => $w,
            'contribution' => round($normRank * $w, 4),
        ];

        // 5. Frequent switch — apply promotion modifier
        $normSwitch = min(1.0, $recentUniqueCompanies3y / $switchCap) * $promotionModifier;
        $w = $weights['frequent_switch'] ?? 0.08;
        $factors['frequent_switch'] = [
            'raw' => $recentUniqueCompanies3y,
            'normalized' => round($normSwitch, 4),
            'weight' => $w,
            'contribution' => round($normSwitch * $w, 4),
        ];

        // 6. Stability inverse
        $normStability = $stabilityIndex !== null
            ? max(0.0, 1.0 - min($stabilityIndex / $siPivot, 1.0))
            : $siNeutral;
        $w = $weights['stability_inverse'] ?? 0.09;
        $factors['stability_inverse'] = [
            'raw' => $stabilityIndex,
            'normalized' => round($normStability, 4),
            'weight' => $w,
            'contribution' => round($normStability * $w, 4),
        ];

        // 7. Vessel diversity (positive modifier = reduces risk)
        $normDiversity = min(1.0, max(0.0, 1.0 - $vesselDiversityScore));
        $w = $weights['vessel_diversity'] ?? 0.05;
        $factors['vessel_diversity'] = [
            'raw' => $vesselDiversityScore,
            'normalized' => round($normDiversity, 4),
            'weight' => $w,
            'contribution' => round($normDiversity * $w, 4),
        ];

        // 8. Temporal recency (higher = recent instability = more risk)
        $normTemporal = min(1.0, max(0.0, $temporalRecencyScore));
        $w = $weights['temporal_recency'] ?? 0.10;
        $factors['temporal_recency'] = [
            'raw' => $temporalRecencyScore,
            'normalized' => round($normTemporal, 4),
            'weight' => $w,
            'contribution' => round($normTemporal * $w, 4),
        ];

        // Sum weighted contributions
        $riskScore = 0.0;
        foreach ($factors as $f) {
            $riskScore += $f['contribution'];
        }

        return [
            'risk_score' => round(min(1.0, max(0.0, $riskScore)), 4),
            'factors' => $factors,
        ];
    }
}
