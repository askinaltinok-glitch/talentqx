<?php

namespace App\Services\ExecutiveSummary;

use App\Models\CandidateDecisionOverride;
use App\Models\CandidateTrustProfile;
use App\Models\ContractAisVerification;
use App\Models\PoolCandidate;
use App\Models\User;
use App\Services\Decision\CorrelationAnalyzer;
use App\Services\Decision\PredictiveRiskEngine;
use App\Services\Decision\RationaleBuilder;
use App\Services\Decision\WhatIfSimulator;
use App\Services\Maritime\CalibrationConfig;
use App\Services\Maritime\FleetTypeResolver;

class ExecutiveSummaryBuilder
{
    /**
     * Build the executive summary from persisted engine outputs.
     * Returns null if feature flag is off.
     */
    public function build(PoolCandidate $candidate): ?array
    {
        if (!config('maritime.exec_summary_v1')) {
            return null;
        }

        $tp = $candidate->trustProfile;
        $detail = $tp?->detail_json ?? [];

        // Resolve fleet type and create calibration config
        $fleetResolver = app(FleetTypeResolver::class);
        $fleetType = $fleetResolver->resolve($candidate);
        $calibration = new CalibrationConfig($fleetType);

        // Extract engine outputs
        $verification = $this->extractVerification($candidate);
        $technical = $this->extractTechnical($detail);
        $stabilityRisk = $this->extractStabilityRisk($tp);
        $compliance = $this->extractCompliance($tp, $detail);
        $competency = $this->extractCompetency($tp, $detail);

        // Confidence level
        $confidenceLevel = $this->resolveConfidence($tp, $detail, $verification);

        // Cross-engine correlation analysis
        $correlation = $this->runCorrelation($detail, $stabilityRisk, $compliance, $competency, $calibration);

        // Predictive risk (reads from trust profile detail_json, does NOT trigger compute)
        $predictiveRisk = $this->extractPredictiveRisk($detail);

        // Decision (engine-derived)
        $engineDecision = $this->resolveDecision($stabilityRisk, $compliance, $technical, $competency, $correlation, $calibration, $predictiveRisk);

        // Strengths / Risks
        $strengths = $this->extractStrengths($tp, $detail, $verification, $technical, $stabilityRisk, $compliance, $competency, $correlation, $predictiveRisk);
        $risks = $this->extractRisks($tp, $detail, $verification, $technical, $stabilityRisk, $compliance, $competency, $correlation, $predictiveRisk);

        // Action line
        $actionLine = $this->buildActionLine($engineDecision, $strengths, $risks);

        // Override
        $override = $this->resolveOverride($candidate->id);
        $finalDecision = ($override['is_active'] && $override['decision'])
            ? $override['decision']
            : $engineDecision;

        // Build partial summary for rationale + what-if (needs scores, correlation, predictive_risk)
        $partialSummary = [
            'scores' => [
                'verification' => $verification,
                'technical' => $technical,
                'stability_risk' => $stabilityRisk,
                'compliance' => $compliance,
                'competency' => $competency,
            ],
            'correlation' => $correlation,
            'predictive_risk' => $predictiveRisk,
        ];

        $rationaleBuilder = new RationaleBuilder();
        $rationale = $rationaleBuilder->build($detail, $partialSummary);

        $whatIfSimulator = new WhatIfSimulator();
        $whatIf = $whatIfSimulator->simulate($partialSummary, $detail);

        return [
            'decision' => $finalDecision,
            'confidence_level' => $confidenceLevel,
            'computed_at' => now()->toIso8601String(),
            'scores' => [
                'verification' => $verification,
                'technical' => $technical,
                'stability_risk' => $stabilityRisk,
                'compliance' => $compliance,
                'competency' => $competency,
            ],
            'correlation' => $correlation,
            'predictive_risk' => $predictiveRisk,
            'calibration_context' => [
                'fleet_type' => $fleetType,
                'review_threshold' => $calibration->competencyReviewThreshold(),
                'technical_review_below' => $calibration->technicalReviewBelow(),
                'correlation_enabled' => $calibration->isCorrelationEnabled(),
            ],
            'top_strengths' => $strengths,
            'top_risks' => $risks,
            'action_line' => $actionLine,
            'override' => $override,
            'rationale' => $rationale,
            'what_if' => $whatIf,
        ];
    }

    private function extractVerification(PoolCandidate $candidate): array
    {
        $contracts = $candidate->contracts ?? collect();
        $verified = $contracts->filter(fn($c) => $c->latestAisVerification && $c->latestAisVerification->confidence_score !== null);
        $avgConfidence = $verified->isNotEmpty()
            ? round($verified->avg(fn($c) => $c->latestAisVerification->confidence_score), 3)
            : null;
        $anomalyCount = $verified->sum(fn($c) => count($c->latestAisVerification->anomalies_json ?? []));
        $provider = $verified->first()?->latestAisVerification?->provider;

        return [
            'confidence_score' => $avgConfidence,
            'provider' => $provider,
            'anomaly_count' => (int) $anomalyCount,
        ];
    }

    private function extractTechnical(array $detail): array
    {
        $rankStcw = $detail['rank_stcw'] ?? null;
        if (!$rankStcw) {
            return ['technical_score' => null, 'stcw_status' => null, 'missing_cert_count' => 0];
        }

        $stcwCompliance = $rankStcw['stcw_compliance'] ?? [];
        $ratio = $stcwCompliance['compliance_ratio'] ?? null;
        $stcwStatus = null;
        if ($ratio !== null) {
            $stcwStatus = $ratio >= 1.0 ? 'compliant' : ($ratio >= 0.7 ? 'partial' : 'non_compliant');
        }

        return [
            'technical_score' => $rankStcw['technical_score'] ?? null,
            'stcw_status' => $stcwStatus,
            'missing_cert_count' => (int) ($stcwCompliance['missing_count'] ?? 0),
        ];
    }

    private function extractStabilityRisk(?CandidateTrustProfile $tp): array
    {
        return [
            'stability_index' => $tp?->stability_index,
            'risk_score' => $tp?->risk_score,
            'risk_tier' => $tp?->risk_tier,
        ];
    }

    private function extractCompliance(?CandidateTrustProfile $tp, array $detail): array
    {
        $pack = $detail['compliance_pack'] ?? null;
        $criticalFlags = 0;
        if ($pack) {
            $criticalFlags = count(array_filter(
                $pack['flags'] ?? [],
                fn($f) => ($f['severity'] ?? '') === 'critical'
            ));
        }

        return [
            'compliance_score' => $tp?->compliance_score,
            'compliance_status' => $tp?->compliance_status,
            'critical_flag_count' => $criticalFlags,
        ];
    }

    private function extractCompetency(?CandidateTrustProfile $tp, array $detail): array
    {
        $competency = $detail['competency_engine'] ?? null;

        return [
            'competency_score' => $tp?->competency_score,
            'competency_status' => $tp?->competency_status,
            'flags' => $competency['flags'] ?? [],
            'has_critical_flag' => !empty($competency['flags']) && in_array(
                'safety_mindset_missing',
                $competency['flags'] ?? []
            ),
            'language' => $competency['language'] ?? null,
            'language_confidence' => $competency['language_confidence'] ?? null,
            'coverage' => $competency['coverage'] ?? null,
            'technical_depth_index' => $competency['technical_depth_index'] ?? null,
        ];
    }

    /**
     * Fairness guardrail: competency can display but must NOT downgrade
     * decision when language coverage/confidence is insufficient.
     */
    private function competencyCanDowngrade(array $competency): bool
    {
        $langConf = $competency['language_confidence'] ?? null;
        $coverage = $competency['coverage'] ?? null;

        // No metadata = legacy compute before guardrail → allow downgrade
        if ($langConf === null && $coverage === null) {
            return true;
        }

        if ($langConf !== null && $langConf < 0.6) {
            return false;
        }

        if ($coverage !== null && $coverage < 0.2) {
            return false;
        }

        return true;
    }

    // ─── Predictive Risk ───────────────────────────────────────

    private function extractPredictiveRisk(array $detail): array
    {
        $pred = $detail['predictive_risk'] ?? null;
        if (!$pred || !config('maritime.predictive_v1')) {
            return [
                'predictive_risk_index' => null,
                'predictive_tier' => null,
                'trend_direction' => null,
                'policy_impact' => 'none',
                'triggered_patterns' => [],
                'reason_chain' => [],
            ];
        }

        return [
            'predictive_risk_index' => $pred['predictive_risk_index'] ?? null,
            'predictive_tier' => $pred['predictive_tier'] ?? null,
            'trend_direction' => $pred['trend_direction'] ?? null,
            'policy_impact' => $pred['policy_impact'] ?? 'none',
            'triggered_patterns' => $pred['triggered_patterns'] ?? [],
            'reason_chain' => $pred['reason_chain'] ?? [],
        ];
    }

    // ─── Cross-Engine Correlation ────────────────────────────────

    /**
     * Run the CorrelationAnalyzer against extracted engine outputs.
     * Returns empty result if feature flag is off.
     */
    private function runCorrelation(array $detail, array $stabilityRisk, array $compliance, array $competency, ?CalibrationConfig $calibration = null): array
    {
        $corrEnabled = $calibration ? $calibration->isCorrelationEnabled() : config('maritime.correlation_v1');
        if (!$corrEnabled) {
            return [
                'correlation_flags' => [],
                'correlation_summary' => 'Correlation analysis not enabled',
                'correlation_risk_weight' => 0.0,
            ];
        }

        $analyzer = app(CorrelationAnalyzer::class);

        // Extract inputs from already-resolved engine data
        $technicalScore     = $detail['rank_stcw']['technical_score'] ?? null;
        $depthIndex         = $competency['technical_depth_index'] ?? null;
        $stabilityIndex     = $stabilityRisk['stability_index'] ?? null;
        $riskScore          = $stabilityRisk['risk_score'] ?? null;
        $complianceScore    = $compliance['compliance_score'] ?? null;
        $competencyScore    = $competency['competency_score'] ?? null;
        $seaTimeMetrics     = $detail['sea_time'] ?? null;

        // Cast to float where not null
        $technicalScore  = $technicalScore !== null ? (float) $technicalScore : null;
        $depthIndex      = $depthIndex !== null ? (float) $depthIndex : null;
        $stabilityIndex  = $stabilityIndex !== null ? (float) $stabilityIndex : null;
        $riskScore       = $riskScore !== null ? (float) $riskScore : null;
        $complianceScore = $complianceScore !== null ? (float) $complianceScore : null;
        $competencyScore = $competencyScore !== null ? (float) $competencyScore : null;

        $corrThresholds = $calibration ? $calibration->correlationThresholds() : null;

        return $analyzer->analyze(
            $technicalScore,
            $depthIndex,
            $stabilityIndex,
            $riskScore,
            $complianceScore,
            $competencyScore,
            $seaTimeMetrics,
            $corrThresholds,
        );
    }

    // ─── Decision Resolver ────────────────────────────────────────

    private function resolveDecision(array $stabilityRisk, array $compliance, array $technical, array $competency = [], array $correlation = [], ?CalibrationConfig $calibration = null, array $predictiveRisk = []): string
    {
        // Critical risk tier → reject
        if (($stabilityRisk['risk_tier'] ?? null) === 'critical') {
            return 'reject';
        }

        // High risk tier → review
        if (($stabilityRisk['risk_tier'] ?? null) === 'high') {
            return 'review';
        }

        // Not compliant with critical flags → reject
        if (($compliance['compliance_status'] ?? null) === 'not_compliant'
            && ($compliance['critical_flag_count'] ?? 0) > 0) {
            return 'reject';
        }

        // Competency decision impact — gated by fairness guardrail
        if (config('maritime.competency_v1') && $this->competencyCanDowngrade($competency)) {
            // Critical safety flag → reject (calibration-driven)
            $rejectOnCritical = $calibration
                ? $calibration->rejectOnCriticalFlag()
                : config('maritime.competency.reject_on_critical_flag', true);
            if ($rejectOnCritical) {
                if ($competency['has_critical_flag'] ?? false) {
                    return 'reject';
                }
            }

            // Score below review threshold → review
            $reviewThreshold = $calibration
                ? $calibration->competencyReviewThreshold()
                : config('maritime.competency.review_threshold', 45);
            $compScore = $competency['competency_score'] ?? null;
            if ($compScore !== null && $compScore < $reviewThreshold) {
                return 'review';
            }
        }

        // Technical score below threshold → review
        $techThreshold = $calibration
            ? $calibration->technicalReviewBelow()
            : config('maritime.exec_summary_thresholds.technical_review_below', 0.4);
        $techScore = $technical['technical_score'] ?? null;
        if ($techScore !== null && $techScore < $techThreshold) {
            return 'review';
        }

        // Cross-engine correlation → review (never reject)
        $corrEnabled = $calibration
            ? $calibration->isCorrelationEnabled()
            : config('maritime.correlation_v1');
        if ($corrEnabled) {
            $correlationFlags = $correlation['correlation_flags'] ?? [];
            if (!empty($correlationFlags)) {
                $analyzer = app(CorrelationAnalyzer::class);
                $impact = $analyzer->resolveDecisionImpact($correlationFlags);
                if ($impact === 'review') {
                    return 'review';
                }
            }
        }

        // Predictive risk → review or require_confirmation (never reject)
        if (config('maritime.predictive_v1')) {
            $policyImpact = $predictiveRisk['policy_impact'] ?? 'none';
            if ($policyImpact === 'require_confirmation' || $policyImpact === 'review') {
                return 'review';
            }
        }

        return 'approve';
    }

    // ─── Confidence Level ─────────────────────────────────────────

    private function resolveConfidence(?CandidateTrustProfile $tp, array $detail, array $verification): string
    {
        $staleDays = config('maritime.exec_summary_confidence_stale_days', 14);
        $staleThreshold = now()->subDays($staleDays);

        $enginesPresent = 0;
        $staleCount = 0;

        // 1. AIS/Verification — check if data exists
        if ($verification['confidence_score'] !== null) {
            $enginesPresent++;
        }

        // 2. Rank/STCW — check detail_json
        $rankStcw = $detail['rank_stcw'] ?? null;
        if ($rankStcw && isset($rankStcw['technical_score'])) {
            $enginesPresent++;
            $computedAt = $rankStcw['computed_at'] ?? null;
            if ($computedAt && \Carbon\Carbon::parse($computedAt)->lt($staleThreshold)) {
                $staleCount++;
            }
        }

        // 3. Stability/Risk — check columns
        if ($tp?->risk_tier !== null) {
            $enginesPresent++;
            $stabilityComputedAt = ($detail['stability_risk']['computed_at'] ?? null);
            if ($stabilityComputedAt && \Carbon\Carbon::parse($stabilityComputedAt)->lt($staleThreshold)) {
                $staleCount++;
            }
        }

        // 4. Compliance — check columns
        if ($tp?->compliance_status !== null) {
            $enginesPresent++;
            if ($tp->compliance_computed_at && $tp->compliance_computed_at->lt($staleThreshold)) {
                $staleCount++;
            }
        }

        // 5. Competency — check columns
        if ($tp?->competency_status !== null) {
            $enginesPresent++;
            if ($tp->competency_computed_at && $tp->competency_computed_at->lt($staleThreshold)) {
                $staleCount++;
            }
        }

        // Rules
        if ($enginesPresent < 2) {
            return 'low';
        }
        if ($enginesPresent >= 4 && $staleCount === 0) {
            return 'high';
        }
        return 'medium';
    }

    // ─── Strengths / Risks ────────────────────────────────────────

    private function extractStrengths(?CandidateTrustProfile $tp, array $detail, array $verification, array $technical, array $stabilityRisk, array $compliance, array $competency = [], array $correlation = [], array $predictiveRisk = []): array
    {
        $strengths = [];

        // High technical score
        $techScore = $technical['technical_score'] ?? null;
        if ($techScore !== null && $techScore >= 0.7) {
            $strengths[] = 'Strong technical score (' . round($techScore * 100) . '%)';
        }

        // STCW fully compliant
        if (($technical['stcw_status'] ?? null) === 'compliant') {
            $strengths[] = 'STCW fully compliant, no missing certificates';
        }

        // Low risk tier
        if (($stabilityRisk['risk_tier'] ?? null) === 'low') {
            $strengths[] = 'Low career risk — stable employment pattern';
        }

        // High compliance score
        if (($compliance['compliance_score'] ?? 0) >= 70) {
            $strengths[] = 'Compliance score above threshold (' . $compliance['compliance_score'] . '/100)';
        }

        // Strong competency
        $compScore = $competency['competency_score'] ?? null;
        if ($compScore !== null && $compScore >= 70) {
            $strengths[] = 'Strong competency score (' . $compScore . '/100)';
        }

        // High technical depth
        $depthIndex = $competency['technical_depth_index'] ?? null;
        if ($depthIndex !== null && $depthIndex >= 60) {
            $strengths[] = 'Strong maritime technical depth (' . round($depthIndex) . '/100)';
        }

        // High verification confidence
        if (($verification['confidence_score'] ?? 0) >= 0.8) {
            $strengths[] = 'High AIS verification confidence (' . round($verification['confidence_score'] * 100) . '%)';
        }

        // High CRI
        if ($tp && $tp->cri_score >= 75) {
            $strengths[] = 'Strong crew reliability index (' . round($tp->cri_score) . '/100)';
        }

        // No correlation anomalies (clean behavioral intelligence)
        if (config('maritime.correlation_v1') && empty($correlation['correlation_flags'] ?? [])) {
            $strengths[] = 'No cross-engine correlation anomalies detected';
        }

        // Low predictive risk
        $predIndex = $predictiveRisk['predictive_risk_index'] ?? null;
        if ($predIndex !== null && $predIndex < 40 && ($predictiveRisk['trend_direction'] ?? null) !== 'worsening') {
            $strengths[] = 'Low predictive risk (' . round($predIndex) . '/100) — stable trajectory';
        }

        return array_slice($strengths, 0, 3);
    }

    private function extractRisks(?CandidateTrustProfile $tp, array $detail, array $verification, array $technical, array $stabilityRisk, array $compliance, array $competency = [], array $correlation = [], array $predictiveRisk = []): array
    {
        $risks = [];

        // Critical/high risk tier
        $tier = $stabilityRisk['risk_tier'] ?? null;
        if ($tier === 'critical') {
            $risks[] = 'Critical risk tier — immediate review required';
        } elseif ($tier === 'high') {
            $risks[] = 'High risk tier — elevated career volatility';
        }

        // Competency critical safety flag
        if ($competency['has_critical_flag'] ?? false) {
            $risks[] = 'Critical: safety mindset evidence insufficient';
        }

        // Competency flags (non-critical)
        $compFlags = $competency['flags'] ?? [];
        $nonCriticalFlags = array_filter($compFlags, fn($f) => $f !== 'safety_mindset_missing');
        if (count($nonCriticalFlags) >= 2) {
            $risks[] = count($nonCriticalFlags) . ' competency concern flag(s) raised';
        }

        // Compliance critical flags
        if (($compliance['critical_flag_count'] ?? 0) > 0) {
            $n = $compliance['critical_flag_count'];
            $risks[] = "{$n} critical compliance flag(s) detected";
        }

        // Missing certs
        $missing = $technical['missing_cert_count'] ?? 0;
        if ($missing > 0) {
            $risks[] = "{$missing} missing/expired STCW certificate(s)";
        }

        // Low technical score
        $techScore = $technical['technical_score'] ?? null;
        if ($techScore !== null && $techScore < 0.4) {
            $risks[] = 'Technical score below threshold (' . round($techScore * 100) . '%)';
        }

        // Low competency score
        $compScore = $competency['competency_score'] ?? null;
        if ($compScore !== null && $compScore < 45) {
            $risks[] = 'Competency below threshold (' . $compScore . '/100)';
        }

        // Fairness guardrail note: low confidence competency
        if ($compScore !== null && !$this->competencyCanDowngrade($competency)) {
            $risks[] = 'Competency assessment low confidence — not used for decision (competency_low_confidence)';
        }

        // Cross-engine correlation flags
        $corrFlags = $correlation['correlation_flags'] ?? [];
        foreach ($corrFlags as $cf) {
            $risks[] = ($cf['detail'] ?? $cf['flag']);
        }

        // Short contract ratio / overlaps / gaps from trust profile
        if ($tp) {
            if ($tp->short_contract_ratio > 0.5) {
                $risks[] = 'High short-contract ratio (' . round($tp->short_contract_ratio * 100) . '%)';
            }
            if ($tp->overlap_count > 0) {
                $risks[] = "{$tp->overlap_count} timeline overlap(s) detected";
            }
            if ($tp->gap_months_total > 12) {
                $risks[] = "Significant career gaps ({$tp->gap_months_total} months total)";
            }
            if ($tp->rank_anomaly_flag) {
                $risks[] = 'Rank progression anomaly detected';
            }
        }

        // AIS anomalies
        if (($verification['anomaly_count'] ?? 0) > 0) {
            $risks[] = $verification['anomaly_count'] . ' AIS verification anomaly(ies)';
        }

        // Predictive risk
        $predIndex = $predictiveRisk['predictive_risk_index'] ?? null;
        if ($predIndex !== null && $predIndex >= 60) {
            $predTier = $predictiveRisk['predictive_tier'] ?? 'high';
            $direction = $predictiveRisk['trend_direction'] ?? 'unknown';
            $risks[] = "Predictive risk {$predTier} (" . round($predIndex) . "/100, trend: {$direction})";
        }

        return array_slice($risks, 0, 3);
    }

    // ─── Action Line ──────────────────────────────────────────────

    private function buildActionLine(string $decision, array $strengths, array $risks): string
    {
        return match ($decision) {
            'approve' => 'Candidate meets deployment criteria. Proceed with presentation to shipowner.',
            'review'  => 'Manual review required before deployment. Address flagged concerns.',
            'reject'  => 'Candidate does not meet minimum compliance thresholds. Not recommended for deployment.',
        };
    }

    // ─── Override ─────────────────────────────────────────────────

    private function resolveOverride(string $candidateId): array
    {
        if (!config('maritime.exec_summary_override_v1')) {
            return [
                'is_active' => false,
                'decision' => null,
                'reason' => null,
                'created_at' => null,
                'created_by' => null,
                'expires_at' => null,
            ];
        }

        $override = CandidateDecisionOverride::activeFor($candidateId);
        if (!$override) {
            return [
                'is_active' => false,
                'decision' => null,
                'reason' => null,
                'created_at' => null,
                'created_by' => null,
                'expires_at' => null,
            ];
        }

        $user = $override->created_by ? User::find($override->created_by) : null;

        return [
            'is_active' => true,
            'decision' => $override->decision,
            'reason' => $override->reason,
            'created_at' => $override->created_at->toIso8601String(),
            'created_by' => $user ? ['id' => $user->id, 'name' => $user->name] : null,
            'expires_at' => $override->expires_at?->toIso8601String(),
        ];
    }
}
