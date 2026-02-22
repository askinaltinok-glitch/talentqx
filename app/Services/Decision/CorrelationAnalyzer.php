<?php

namespace App\Services\Decision;

/**
 * Cross-Engine Correlation Analyzer v1
 *
 * Detects behavioral-intelligence patterns by correlating outputs from
 * all 5 engines: technical, competency/depth, stability/risk, compliance, sea-time.
 *
 * Produces:
 *  - correlation_flags: named pattern flags
 *  - correlation_summary: human-readable summary
 *  - correlation_risk_weight: 0.0–1.0 aggregate risk modifier
 *
 * Rules:
 *  - expert_unstable:       depth >= 75 AND stability_index < threshold → REVIEW
 *  - compliant_low_experience: compliance >= 80 AND sea_time < threshold → note only
 *  - stable_but_weak:       stability >= threshold AND depth < 50 → REVIEW
 *  - high_skill_high_risk:  risk >= 0.6 AND depth >= 70 → REVIEW + risk_note
 *
 * Never auto-rejects. Does NOT override manual decision overrides.
 */
class CorrelationAnalyzer
{
    /**
     * Analyze cross-engine correlations.
     *
     * All inputs are nullable — the analyzer is tolerant of missing engine data.
     *
     * @return array{correlation_flags: array, correlation_summary: string, correlation_risk_weight: float}
     */
    public function analyze(
        ?float $technicalScore,
        ?float $technicalDepthIndex,
        ?float $stabilityIndex,
        ?float $riskScore,
        ?float $complianceScore,
        ?float $competencyScore,
        ?array $seaTimeMetrics,
        ?array $thresholds = null,
    ): array {
        $cfg = $thresholds ?? config('maritime.correlation', []);

        // Thresholds (all config-driven with safe defaults)
        $depthExpertThreshold     = (float) ($cfg['expert_depth_threshold'] ?? 75);
        $stabilityLowThreshold    = (float) ($cfg['stability_low_threshold'] ?? 4.0);
        $stabilityHighThreshold   = (float) ($cfg['stability_high_threshold'] ?? 7.0);
        $complianceHighThreshold  = (float) ($cfg['compliance_high_threshold'] ?? 80);
        $seaTimeLowDaysThreshold  = (int)   ($cfg['sea_time_low_days_threshold'] ?? 365);
        $depthWeakThreshold       = (float) ($cfg['depth_weak_threshold'] ?? 50);
        $riskHighThreshold        = (float) ($cfg['risk_high_threshold'] ?? 0.6);
        $depthSkillThreshold      = (float) ($cfg['depth_skill_threshold'] ?? 70);

        $flags = [];
        $summaryParts = [];

        // ── Pattern 1: Expert but Unstable ──────────────────────────
        if ($technicalDepthIndex !== null && $stabilityIndex !== null) {
            if ($technicalDepthIndex >= $depthExpertThreshold && $stabilityIndex < $stabilityLowThreshold) {
                $flags[] = [
                    'flag' => 'expert_unstable',
                    'severity' => 'warning',
                    'decision_impact' => 'review',
                    'detail' => "High domain expertise (depth {$technicalDepthIndex}) but unstable career (SI " . round($stabilityIndex, 2) . ")",
                ];
                $summaryParts[] = 'Expert-level knowledge contradicted by unstable employment pattern — investigate career gaps';
            }
        }

        // ── Pattern 2: Safe but Inexperienced ───────────────────────
        $seaTotalDays = $seaTimeMetrics['merged_total_days'] ?? $seaTimeMetrics['total_sea_days'] ?? null;
        if ($complianceScore !== null && $seaTotalDays !== null) {
            if ($complianceScore >= $complianceHighThreshold && $seaTotalDays < $seaTimeLowDaysThreshold) {
                $flags[] = [
                    'flag' => 'compliant_low_experience',
                    'severity' => 'info',
                    'decision_impact' => 'note',
                    'detail' => "Fully compliant (score {$complianceScore}) but limited sea time ({$seaTotalDays} days)",
                ];
                $summaryParts[] = 'Documents compliant but limited sea time — consider supervised onboarding';
            }
        }

        // ── Pattern 3: Stable but Technically Weak ──────────────────
        if ($stabilityIndex !== null && $technicalDepthIndex !== null) {
            if ($stabilityIndex >= $stabilityHighThreshold && $technicalDepthIndex < $depthWeakThreshold) {
                $flags[] = [
                    'flag' => 'stable_but_weak',
                    'severity' => 'warning',
                    'decision_impact' => 'review',
                    'detail' => "Stable career (SI " . round($stabilityIndex, 2) . ") but weak technical depth ({$technicalDepthIndex})",
                ];
                $summaryParts[] = 'Long tenure does not correlate with technical mastery — assess practical skills';
            }
        }

        // ── Pattern 4: High Risk but Strong Skill ───────────────────
        if ($riskScore !== null && $technicalDepthIndex !== null) {
            if ($riskScore >= $riskHighThreshold && $technicalDepthIndex >= $depthSkillThreshold) {
                $flags[] = [
                    'flag' => 'high_skill_high_risk',
                    'severity' => 'warning',
                    'decision_impact' => 'review',
                    'detail' => "High career risk ({$riskScore}) despite strong skills (depth {$technicalDepthIndex})",
                ];
                $summaryParts[] = 'Skilled but high-risk profile — frequent moves may indicate management conflicts or market demand';
            }
        }

        // ── Compute aggregate risk weight ───────────────────────────
        $riskWeight = $this->computeRiskWeight($flags);

        // ── Build summary ───────────────────────────────────────────
        $summary = empty($summaryParts)
            ? 'No cross-engine correlation anomalies detected'
            : implode('. ', $summaryParts) . '.';

        return [
            'correlation_flags' => $flags,
            'correlation_summary' => $summary,
            'correlation_risk_weight' => $riskWeight,
        ];
    }

    /**
     * Determine the highest decision impact from correlation flags.
     *
     * Returns 'review', 'note', or null (no impact).
     * Never returns 'reject'.
     */
    public function resolveDecisionImpact(array $correlationFlags): ?string
    {
        $hasReview = false;
        $hasNote = false;

        foreach ($correlationFlags as $flag) {
            $impact = $flag['decision_impact'] ?? null;
            if ($impact === 'review') {
                $hasReview = true;
            } elseif ($impact === 'note') {
                $hasNote = true;
            }
        }

        if ($hasReview) return 'review';
        if ($hasNote) return 'note';
        return null;
    }

    /**
     * Compute aggregate risk weight from flags.
     *
     * Each review-level flag adds 0.15, info-level adds 0.05.
     * Capped at 0.6 — correlation alone never pushes to critical.
     */
    private function computeRiskWeight(array $flags): float
    {
        $weight = 0.0;
        foreach ($flags as $flag) {
            $severity = $flag['severity'] ?? 'info';
            $weight += ($severity === 'warning') ? 0.15 : 0.05;
        }
        return round(min($weight, 0.6), 2);
    }
}
