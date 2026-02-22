<?php

namespace App\Services\Decision;

use App\Models\CandidateRiskSnapshot;
use Illuminate\Support\Collection;

/**
 * Risk Trend Analyzer v1
 *
 * Analyzes historical risk snapshots to detect 6 aggressive patterns:
 *   1. Escalating Instability (+20)
 *   2. Frequent Switching Acceleration (+20)
 *   3. Gap Growth (+15)
 *   4. Promotion Pressure (+15)
 *   5. Compliance Drift (+25)
 *   6. Behavioral-Technical Mismatch (+10)
 *
 * Returns:
 *   - trend_score: 0-100 (sum of triggered patterns, capped)
 *   - triggered_patterns: array of pattern details with reason chains
 *   - trend_direction: 'improving' | 'stable' | 'worsening'
 */
class RiskTrendAnalyzer
{
    /**
     * Analyze trend from historical snapshots.
     *
     * @param Collection<CandidateRiskSnapshot> $snapshots Ordered by computed_at ASC
     * @param array $cfg Config overrides (from CalibrationConfig)
     */
    public function analyze(Collection $snapshots, array $cfg = []): array
    {
        if ($snapshots->count() < 2) {
            return [
                'trend_score' => 0,
                'triggered_patterns' => [],
                'trend_direction' => 'stable',
                'snapshot_count' => $snapshots->count(),
            ];
        }

        $patterns = [];
        $totalScore = 0;

        // Pattern 1: Escalating Instability
        $p1 = $this->detectEscalatingInstability($snapshots, $cfg);
        if ($p1) {
            $patterns[] = $p1;
            $totalScore += $p1['points'];
        }

        // Pattern 2: Frequent Switching Acceleration
        $p2 = $this->detectSwitchingAcceleration($snapshots, $cfg);
        if ($p2) {
            $patterns[] = $p2;
            $totalScore += $p2['points'];
        }

        // Pattern 3: Gap Growth
        $p3 = $this->detectGapGrowth($snapshots, $cfg);
        if ($p3) {
            $patterns[] = $p3;
            $totalScore += $p3['points'];
        }

        // Pattern 4: Promotion Pressure
        $p4 = $this->detectPromotionPressure($snapshots, $cfg);
        if ($p4) {
            $patterns[] = $p4;
            $totalScore += $p4['points'];
        }

        // Pattern 5: Compliance Drift
        $p5 = $this->detectComplianceDrift($snapshots, $cfg);
        if ($p5) {
            $patterns[] = $p5;
            $totalScore += $p5['points'];
        }

        // Pattern 6: Behavioral-Technical Mismatch
        $p6 = $this->detectBehavioralTechnicalMismatch($snapshots, $cfg);
        if ($p6) {
            $patterns[] = $p6;
            $totalScore += $p6['points'];
        }

        $trendScore = min(100, $totalScore);
        $direction = $this->resolveTrendDirection($snapshots, $trendScore);

        return [
            'trend_score' => $trendScore,
            'triggered_patterns' => $patterns,
            'trend_direction' => $direction,
            'snapshot_count' => $snapshots->count(),
        ];
    }

    /**
     * Pattern 1: Escalating Instability (+20)
     * Risk score increasing across consecutive snapshots.
     */
    private function detectEscalatingInstability(Collection $snapshots, array $cfg): ?array
    {
        $points = (int) ($cfg['escalating_instability_points'] ?? 20);
        $minIncrease = (float) ($cfg['escalating_instability_min_increase'] ?? 0.10);

        $riskScores = $snapshots->map(fn($s) => $s->inputs_json['risk_score'] ?? null)
            ->filter(fn($v) => $v !== null)
            ->values();

        if ($riskScores->count() < 2) {
            return null;
        }

        $first = $riskScores->first();
        $last = $riskScores->last();
        $delta = $last - $first;

        // Check monotonic increase in at least 2/3 of transitions
        $increases = 0;
        $transitions = 0;
        for ($i = 1; $i < $riskScores->count(); $i++) {
            $transitions++;
            if ($riskScores[$i] > $riskScores[$i - 1]) {
                $increases++;
            }
        }

        if ($delta >= $minIncrease && $transitions > 0 && ($increases / $transitions) >= 0.6) {
            return [
                'pattern' => 'escalating_instability',
                'points' => $points,
                'reason' => "Risk score increased by " . round($delta, 2) . " across {$snapshots->count()} snapshots ({$increases}/{$transitions} transitions rising)",
                'data' => [
                    'first_risk' => round($first, 3),
                    'last_risk' => round($last, 3),
                    'delta' => round($delta, 3),
                    'rising_ratio' => round($increases / $transitions, 2),
                ],
            ];
        }

        return null;
    }

    /**
     * Pattern 2: Frequent Switching Acceleration (+20)
     * Recent unique companies increasing faster than historical.
     */
    private function detectSwitchingAcceleration(Collection $snapshots, array $cfg): ?array
    {
        $points = (int) ($cfg['switching_acceleration_points'] ?? 20);

        $companyCounts = $snapshots->map(fn($s) => $s->inputs_json['recent_unique_companies_3y'] ?? null)
            ->filter(fn($v) => $v !== null)
            ->values();

        if ($companyCounts->count() < 2) {
            return null;
        }

        $first = $companyCounts->first();
        $last = $companyCounts->last();

        if ($last > $first && ($last - $first) >= 2) {
            return [
                'pattern' => 'switching_acceleration',
                'points' => $points,
                'reason' => "Recent unique companies grew from {$first} to {$last} across snapshots — accelerating job hopping",
                'data' => [
                    'first_companies' => $first,
                    'last_companies' => $last,
                    'increase' => $last - $first,
                ],
            ];
        }

        return null;
    }

    /**
     * Pattern 3: Gap Growth (+15)
     * Total gap months increasing over time.
     */
    private function detectGapGrowth(Collection $snapshots, array $cfg): ?array
    {
        $points = (int) ($cfg['gap_growth_points'] ?? 15);
        $minGrowth = (int) ($cfg['gap_growth_min_months'] ?? 3);

        $gapMonths = $snapshots->map(fn($s) => $s->inputs_json['gap_months_total'] ?? null)
            ->filter(fn($v) => $v !== null)
            ->values();

        if ($gapMonths->count() < 2) {
            return null;
        }

        $first = $gapMonths->first();
        $last = $gapMonths->last();
        $growth = $last - $first;

        if ($growth >= $minGrowth) {
            return [
                'pattern' => 'gap_growth',
                'points' => $points,
                'reason' => "Career gaps grew by {$growth} months (from {$first} to {$last}) — increasing disengagement",
                'data' => [
                    'first_gap' => $first,
                    'last_gap' => $last,
                    'growth_months' => $growth,
                ],
            ];
        }

        return null;
    }

    /**
     * Pattern 4: Promotion Pressure (+15)
     * Rank anomaly flags appearing or stability dropping post-promotion.
     */
    private function detectPromotionPressure(Collection $snapshots, array $cfg): ?array
    {
        $points = (int) ($cfg['promotion_pressure_points'] ?? 15);

        // Check if rank_anomaly_flag appeared in recent snapshots but not early ones
        $earlyHalf = $snapshots->take((int) ceil($snapshots->count() / 2));
        $lateHalf = $snapshots->skip((int) ceil($snapshots->count() / 2));

        $earlyAnomalies = $earlyHalf->filter(fn($s) => !empty($s->inputs_json['rank_anomaly_flag']))->count();
        $lateAnomalies = $lateHalf->filter(fn($s) => !empty($s->inputs_json['rank_anomaly_flag']))->count();

        // Also check stability drop in late half
        $earlyStability = $earlyHalf->avg(fn($s) => $s->inputs_json['stability_index'] ?? 5.0);
        $lateStability = $lateHalf->avg(fn($s) => $s->inputs_json['stability_index'] ?? 5.0);

        if ($lateAnomalies > $earlyAnomalies && $lateStability < $earlyStability) {
            return [
                'pattern' => 'promotion_pressure',
                'points' => $points,
                'reason' => "Rank anomalies increased ({$earlyAnomalies} early → {$lateAnomalies} late) with stability dropping (" . round($earlyStability, 1) . " → " . round($lateStability, 1) . ")",
                'data' => [
                    'early_anomalies' => $earlyAnomalies,
                    'late_anomalies' => $lateAnomalies,
                    'early_stability' => round($earlyStability, 2),
                    'late_stability' => round($lateStability, 2),
                ],
            ];
        }

        return null;
    }

    /**
     * Pattern 5: Compliance Drift (+25)
     * Compliance score dropping across snapshots.
     */
    private function detectComplianceDrift(Collection $snapshots, array $cfg): ?array
    {
        $points = (int) ($cfg['compliance_drift_points'] ?? 25);
        $minDrop = (int) ($cfg['compliance_drift_min_drop'] ?? 10);

        $complianceScores = $snapshots->map(fn($s) => $s->inputs_json['compliance_score'] ?? null)
            ->filter(fn($v) => $v !== null)
            ->values();

        if ($complianceScores->count() < 2) {
            return null;
        }

        $first = $complianceScores->first();
        $last = $complianceScores->last();
        $drop = $first - $last;

        if ($drop >= $minDrop) {
            return [
                'pattern' => 'compliance_drift',
                'points' => $points,
                'reason' => "Compliance score dropped by {$drop} points ({$first} → {$last}) — certificate/document deterioration",
                'data' => [
                    'first_compliance' => $first,
                    'last_compliance' => $last,
                    'drop' => $drop,
                ],
            ];
        }

        return null;
    }

    /**
     * Pattern 6: Behavioral-Technical Mismatch (+10)
     * Competency score diverging from technical depth.
     */
    private function detectBehavioralTechnicalMismatch(Collection $snapshots, array $cfg): ?array
    {
        $points = (int) ($cfg['behavioral_technical_mismatch_points'] ?? 10);
        $minDivergence = (float) ($cfg['behavioral_technical_min_divergence'] ?? 25);

        // Use most recent snapshot for this check
        $latest = $snapshots->last();
        $competencyScore = $latest->inputs_json['competency_score'] ?? null;
        $depthIndex = $latest->inputs_json['technical_depth_index'] ?? null;

        if ($competencyScore === null || $depthIndex === null) {
            return null;
        }

        $divergence = abs($competencyScore - $depthIndex);

        if ($divergence >= $minDivergence) {
            $direction = $competencyScore > $depthIndex
                ? 'High interview competency but low technical evidence'
                : 'Strong technical evidence but poor interview competency';

            return [
                'pattern' => 'behavioral_technical_mismatch',
                'points' => $points,
                'reason' => "{$direction} — divergence of " . round($divergence) . " points (competency: {$competencyScore}, depth: " . round($depthIndex) . ")",
                'data' => [
                    'competency_score' => $competencyScore,
                    'depth_index' => round($depthIndex, 1),
                    'divergence' => round($divergence, 1),
                ],
            ];
        }

        return null;
    }

    /**
     * Determine overall trend direction from risk score trajectory.
     */
    private function resolveTrendDirection(Collection $snapshots, int $trendScore): string
    {
        if ($trendScore >= 30) {
            return 'worsening';
        }

        $riskScores = $snapshots->map(fn($s) => $s->inputs_json['risk_score'] ?? null)
            ->filter(fn($v) => $v !== null)
            ->values();

        if ($riskScores->count() < 2) {
            return 'stable';
        }

        $first = $riskScores->first();
        $last = $riskScores->last();
        $delta = $last - $first;

        if ($delta < -0.05) {
            return 'improving';
        }
        if ($delta > 0.05) {
            return 'worsening';
        }

        return 'stable';
    }
}
