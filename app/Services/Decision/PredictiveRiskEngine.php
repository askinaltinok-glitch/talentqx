<?php

namespace App\Services\Decision;

use App\Models\CandidateRiskSnapshot;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\TrustEvent;
use App\Services\Maritime\CalibrationConfig;
use App\Services\Maritime\FleetTypeResolver;
use Illuminate\Support\Facades\Log;

/**
 * Predictive Risk Engine v1
 *
 * Blends current risk score + trend analysis + correlation risk weight
 * into a single predictive_risk_index (0-100).
 *
 * Formula:
 *   predictive_risk_index = clamp(
 *     0.45 * current_risk_score_scaled_to_100
 *   + 0.35 * trend_score
 *   + 0.20 * correlation_risk_weight_scaled_to_100
 *   , 0, 100)
 *
 * Tiers: 0-39 low, 40-59 medium, 60-74 high, 75+ critical
 *
 * Policy:
 *   - REVIEW when predictive_risk_index >= review threshold (default 60)
 *   - REQUIRE_CONFIRMATION when >= confirm threshold (default 75)
 *   - NEVER auto-REJECT
 *
 * All outputs include human-readable reason chains.
 * Stores append-only snapshot + updates trust profile.
 */
class PredictiveRiskEngine
{
    public function __construct(
        private RiskTrendAnalyzer $trendAnalyzer,
    ) {}

    public function compute(string $poolCandidateId, ?CalibrationConfig $calibration = null): ?array
    {
        if (!config('maritime.predictive_v1')) {
            return null;
        }

        try {
            return $this->doCompute($poolCandidateId, $calibration);
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('[PredictiveRisk] compute failed', [
                'candidate' => $poolCandidateId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doCompute(string $poolCandidateId, ?CalibrationConfig $calibration): ?array
    {
        $candidate = PoolCandidate::find($poolCandidateId);
        if (!$candidate) {
            return null;
        }

        $tp = CandidateTrustProfile::where('pool_candidate_id', $poolCandidateId)->first();
        if (!$tp) {
            return null;
        }

        // Resolve fleet type if not provided via calibration
        if (!$calibration) {
            $fleetResolver = app(FleetTypeResolver::class);
            $fleetType = $fleetResolver->resolve($candidate);
            $calibration = new CalibrationConfig($fleetType);
        }

        $detail = $tp->detail_json ?? [];
        $predictiveCfg = $calibration->predictiveConfig();

        // ── Gather current inputs ──
        $currentRiskScore = $tp->risk_score;           // 0.0-1.0
        $stabilityIndex = $tp->stability_index;
        $complianceScore = $tp->compliance_score;
        $competencyScore = $tp->competency_score;
        $riskTier = $tp->risk_tier;

        // From detail_json
        $correlationWeight = $detail['correlation']['correlation_risk_weight'] ?? 0.0;
        $depthIndex = $detail['competency_engine']['technical_depth_index'] ?? null;
        $gapMonthsTotal = $detail['stability_risk']['contract_summary']['total_gap_months'] ?? 0;
        $recentCompanies3y = $detail['stability_risk']['contract_summary']['recent_unique_companies_3y'] ?? 0;
        $rankAnomalyFlag = $tp->rank_anomaly_flag ?? false;

        // ── Create snapshot for trend tracking ──
        $inputs = [
            'risk_score' => $currentRiskScore,
            'stability_index' => $stabilityIndex,
            'compliance_score' => $complianceScore,
            'competency_score' => $competencyScore,
            'risk_tier' => $riskTier,
            'correlation_risk_weight' => $correlationWeight,
            'technical_depth_index' => $depthIndex,
            'gap_months_total' => $gapMonthsTotal,
            'recent_unique_companies_3y' => $recentCompanies3y,
            'rank_anomaly_flag' => $rankAnomalyFlag,
        ];

        // ── Load historical snapshots for trend analysis ──
        $windowMonths = (int) ($predictiveCfg['snapshot_window'] ?? 6);
        $cutoff = now()->subMonths($windowMonths);

        $snapshots = CandidateRiskSnapshot::where('pool_candidate_id', $poolCandidateId)
            ->where('computed_at', '>=', $cutoff)
            ->orderBy('computed_at')
            ->get();

        // ── Run trend analysis ──
        $trendResult = $this->trendAnalyzer->analyze($snapshots, $predictiveCfg);

        // ── Compute blended predictive risk index ──
        $weights = $predictiveCfg['blend_weights'] ?? [
            'current_risk' => 0.45,
            'trend' => 0.35,
            'correlation' => 0.20,
        ];

        $currentRiskScaled = ($currentRiskScore ?? 0) * 100;
        $trendScore = $trendResult['trend_score'];
        $correlationScaled = min(($correlationWeight ?? 0) * 100, 100);

        $rawIndex = ($weights['current_risk'] * $currentRiskScaled)
            + ($weights['trend'] * $trendScore)
            + ($weights['correlation'] * $correlationScaled);

        $predictiveIndex = round(max(0, min(100, $rawIndex)), 1);

        // ── Resolve tier ──
        $tierBoundaries = $predictiveCfg['tier_boundaries'] ?? [
            'critical' => 75,
            'high' => 60,
            'medium' => 40,
        ];

        $predictiveTier = $this->resolveTier($predictiveIndex, $tierBoundaries);

        // ── Build reason chain ──
        $reasonChain = $this->buildReasonChain(
            $predictiveIndex,
            $predictiveTier,
            $currentRiskScore,
            $trendResult,
            $correlationWeight,
            $weights,
        );

        // ── Resolve policy impact ──
        $reviewThreshold = (float) ($predictiveCfg['review_threshold'] ?? 60);
        $confirmThreshold = (float) ($predictiveCfg['confirm_threshold'] ?? 75);
        $policyImpact = $this->resolvePolicyImpact($predictiveIndex, $reviewThreshold, $confirmThreshold);

        // ── Build outputs ──
        $outputs = [
            'predictive_risk_index' => $predictiveIndex,
            'predictive_tier' => $predictiveTier,
            'trend_score' => $trendScore,
            'trend_direction' => $trendResult['trend_direction'],
            'triggered_patterns' => $trendResult['triggered_patterns'],
            'blend_components' => [
                'current_risk_scaled' => round($currentRiskScaled, 1),
                'trend_score' => $trendScore,
                'correlation_scaled' => round($correlationScaled, 1),
                'weights' => $weights,
            ],
            'policy_impact' => $policyImpact,
            'reason_chain' => $reasonChain,
            'snapshot_count' => $trendResult['snapshot_count'],
            'computed_at' => now()->toIso8601String(),
        ];

        // ── Store append-only snapshot ──
        CandidateRiskSnapshot::create([
            'pool_candidate_id' => $poolCandidateId,
            'computed_at' => now(),
            'fleet_type' => $calibration->getFleetType(),
            'inputs_json' => $inputs,
            'outputs_json' => $outputs,
        ]);

        // ── Update trust profile ──
        $detail['predictive_risk'] = $outputs;
        $tp->detail_json = $detail;
        $tp->save();

        // ── Audit event ──
        TrustEvent::create([
            'pool_candidate_id' => $poolCandidateId,
            'event_type' => 'predictive_risk_computed',
            'payload_json' => [
                'predictive_risk_index' => $predictiveIndex,
                'predictive_tier' => $predictiveTier,
                'trend_score' => $trendScore,
                'trend_direction' => $trendResult['trend_direction'],
                'pattern_count' => count($trendResult['triggered_patterns']),
                'policy_impact' => $policyImpact,
            ],
        ]);

        return $outputs;
    }

    private function resolveTier(float $index, array $boundaries): string
    {
        if ($index >= $boundaries['critical']) {
            return 'critical';
        }
        if ($index >= $boundaries['high']) {
            return 'high';
        }
        if ($index >= $boundaries['medium']) {
            return 'medium';
        }
        return 'low';
    }

    private function resolvePolicyImpact(float $index, float $reviewThreshold, float $confirmThreshold): string
    {
        if ($index >= $confirmThreshold) {
            return 'require_confirmation';
        }
        if ($index >= $reviewThreshold) {
            return 'review';
        }
        return 'none';
    }

    private function buildReasonChain(
        float $index,
        string $tier,
        ?float $currentRisk,
        array $trendResult,
        ?float $correlationWeight,
        array $weights,
    ): array {
        $chain = [];

        // Component 1: Current risk contribution
        $currentContrib = round(($currentRisk ?? 0) * 100 * $weights['current_risk'], 1);
        $chain[] = "Current risk contributes {$currentContrib} pts (" . round(($currentRisk ?? 0) * 100) . "% risk * " . round($weights['current_risk'] * 100) . "% weight)";

        // Component 2: Trend contribution
        $trendContrib = round($trendResult['trend_score'] * $weights['trend'], 1);
        $patternCount = count($trendResult['triggered_patterns']);
        if ($patternCount > 0) {
            $patternNames = array_map(fn($p) => $p['pattern'], $trendResult['triggered_patterns']);
            $chain[] = "Trend analysis contributes {$trendContrib} pts ({$patternCount} pattern(s): " . implode(', ', $patternNames) . ")";
        } else {
            $chain[] = "Trend analysis contributes {$trendContrib} pts (no patterns triggered)";
        }

        // Component 3: Correlation contribution
        $corrContrib = round(($correlationWeight ?? 0) * 100 * $weights['correlation'], 1);
        $chain[] = "Correlation contributes {$corrContrib} pts (" . round(($correlationWeight ?? 0) * 100) . "% weight * " . round($weights['correlation'] * 100) . "% blend)";

        // Summary
        $chain[] = "Blended predictive risk index: {$index} → tier: {$tier}";

        return $chain;
    }
}
