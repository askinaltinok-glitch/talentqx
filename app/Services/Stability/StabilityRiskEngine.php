<?php

namespace App\Services\Stability;

use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\TrustEvent;
use App\Services\Trust\ContractPatternAnalyzer;
use App\Services\Trust\RankProgressionAnalyzer;
use Illuminate\Support\Facades\Log;

/**
 * Stability & Risk Engine v1.1
 *
 * Context-aware, fully configurable orchestrator.
 * All thresholds flow through StabilityConfig — zero hardcoded values.
 *
 * Combines:
 *   - ContractPatternAnalyzer (rank-aware short contract detection)
 *   - RankProgressionAnalyzer (anomaly detection with configurable thresholds)
 *   - StabilityIndexCalculator (avg/σ with configurable params)
 *   - PromotionContextAnalyzer (promotion window penalty reduction)
 *   - TemporalDecayCalculator (recent vs old instability weighting)
 *   - VesselDiversityCalculator (positive modifier for diverse experience)
 *   - RiskScoreCalculator (8-factor weighted score with all new inputs)
 *   - RiskTierResolver (configurable tier boundaries)
 *
 * Stores results in CandidateTrustProfile columns + detail_json['stability_risk'].
 * Fail-open: catches exceptions, returns null.
 */
class StabilityRiskEngine
{
    public function __construct(
        private StabilityIndexCalculator $stabilityCalc,
        private RiskScoreCalculator $riskCalc,
        private RiskTierResolver $tierResolver,
        private ContractPatternAnalyzer $contractAnalyzer,
        private RankProgressionAnalyzer $rankAnalyzer,
        private PromotionContextAnalyzer $promotionAnalyzer,
        private TemporalDecayCalculator $temporalCalc,
        private VesselDiversityCalculator $diversityCalc,
    ) {}

    /**
     * Compute stability & risk assessment for a candidate.
     *
     * @param string $poolCandidateId
     * @param string|null $fleetType  Optional fleet type for profile-specific thresholds
     */
    public function compute(string $poolCandidateId, ?string $fleetType = null): ?array
    {
        if (!config('maritime.stability_v1')) {
            return null;
        }

        try {
            return $this->doCompute($poolCandidateId, $fleetType);
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('[StabilityRisk] compute failed', [
                'candidate' => $poolCandidateId,
                'fleet_type' => $fleetType,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function doCompute(string $poolCandidateId, ?string $fleetType): ?array
    {
        $candidate = PoolCandidate::find($poolCandidateId);
        if (!$candidate) {
            return null;
        }

        // ── Centralized config (with optional fleet profile) ──
        $cfg = new StabilityConfig($fleetType);

        $contracts = CandidateContract::where('pool_candidate_id', $poolCandidateId)
            ->whereNotNull('start_date')
            ->orderBy('start_date')
            ->get();

        // ── Phase 2: Rank-aware contract pattern analysis ──
        $contractAnalysis = $this->contractAnalyzer->analyze($contracts, $cfg);

        // ── Rank progression analysis (configurable anomaly thresholds) ──
        $rankAnalysis = $this->rankAnalyzer->analyze($contracts, $cfg);

        // ── Stability Index (configurable min_contracts, std_threshold, max_cap) ──
        $stabilityData = $this->stabilityCalc->calculate($contracts, $cfg);

        // ── Phase 3: Promotion window context ──
        $promotionContext = $this->promotionAnalyzer->analyze($poolCandidateId, $cfg);

        // ── Phase 4: Temporal decay ──
        $temporalData = $this->temporalCalc->calculate($contracts, $cfg);

        // ── Phase 5: Vessel diversity ──
        $diversityData = $this->diversityCalc->calculate($contracts, $cfg);

        // ── Risk Score (8 factors, promotion modifier, temporal + diversity inputs) ──
        $rankAnomalyFlag = !empty($rankAnalysis['anomalies']);
        $riskData = $this->riskCalc->calculate(
            shortRatio: $contractAnalysis['short_contract_ratio'],
            totalGapMonths: $contractAnalysis['total_gap_months'],
            overlapCount: $contractAnalysis['overlap_count'],
            rankAnomaly: $rankAnomalyFlag,
            recentUniqueCompanies3y: $contractAnalysis['recent_unique_companies_3y'],
            stabilityIndex: $stabilityData['stability_index'],
            temporalRecencyScore: $temporalData['temporal_recency_score'],
            vesselDiversityScore: $diversityData['vessel_diversity_score'],
            promotionModifier: $promotionContext['modifier'],
            cfg: $cfg,
        );

        // ── Risk Tier (configurable boundaries) ──
        $riskTier = $this->tierResolver->resolve($riskData['risk_score'], $cfg);

        // Build result
        $result = [
            'engine_version' => '1.1',
            'fleet_type' => $fleetType,
            'stability_index' => $stabilityData['stability_index'],
            'stability' => $stabilityData,
            'risk_score' => $riskData['risk_score'],
            'risk_tier' => $riskTier,
            'risk_factors' => $riskData['factors'],
            'contract_summary' => [
                'total_contracts' => $contractAnalysis['total_contracts'],
                'avg_duration_months' => $contractAnalysis['avg_duration_months'],
                'short_contract_ratio' => $contractAnalysis['short_contract_ratio'],
                'short_contract_count' => $contractAnalysis['short_contract_count'],
                'overlap_count' => $contractAnalysis['overlap_count'],
                'total_gap_months' => $contractAnalysis['total_gap_months'],
                'longest_gap_months' => $contractAnalysis['longest_gap_months'],
                'unique_companies' => $contractAnalysis['unique_companies'],
                'company_repeat_ratio' => $contractAnalysis['company_repeat_ratio'],
                'recent_unique_companies_3y' => $contractAnalysis['recent_unique_companies_3y'],
            ],
            'rank_anomalies' => $rankAnalysis['anomalies'],
            'promotion_context' => [
                'in_promotion_window' => $promotionContext['in_promotion_window'],
                'modifier' => $promotionContext['modifier'],
            ],
            'temporal_decay' => [
                'temporal_recency_score' => $temporalData['temporal_recency_score'],
                'recent_short_ratio' => $temporalData['recent_short_ratio'],
                'old_short_ratio' => $temporalData['old_short_ratio'],
            ],
            'vessel_diversity' => [
                'vessel_diversity_score' => $diversityData['vessel_diversity_score'],
                'qualifying_types' => $diversityData['qualifying_types'],
                'total_types' => $diversityData['total_types'],
                'type_breakdown' => $diversityData['type_breakdown'],
            ],
            'flags' => array_merge($contractAnalysis['flags'], $rankAnalysis['flags']),
            'computed_at' => now()->toIso8601String(),
        ];

        // Store in trust profile
        $this->storeResult($poolCandidateId, $result);

        // Audit event
        TrustEvent::create([
            'pool_candidate_id' => $poolCandidateId,
            'event_type' => 'stability_risk_computed',
            'payload_json' => [
                'engine_version' => '1.1',
                'fleet_type' => $fleetType,
                'stability_index' => $stabilityData['stability_index'],
                'risk_score' => $riskData['risk_score'],
                'risk_tier' => $riskTier,
                'promotion_modifier' => $promotionContext['modifier'],
                'temporal_recency_score' => $temporalData['temporal_recency_score'],
                'vessel_diversity_score' => $diversityData['vessel_diversity_score'],
            ],
        ]);

        return $result;
    }

    private function storeResult(string $poolCandidateId, array $result): void
    {
        $trustProfile = CandidateTrustProfile::firstOrNew(
            ['pool_candidate_id' => $poolCandidateId]
        );

        $detailJson = $trustProfile->detail_json ?? [];
        $detailJson['stability_risk'] = $result;

        $trustProfile->detail_json = $detailJson;
        $trustProfile->stability_index = $result['stability_index'];
        $trustProfile->risk_score = $result['risk_score'];
        $trustProfile->risk_tier = $result['risk_tier'];

        if (!$trustProfile->exists) {
            $trustProfile->pool_candidate_id = $poolCandidateId;
            $trustProfile->cri_score = 0;
            $trustProfile->confidence_level = 'low';
            $trustProfile->computed_at = now();
        }

        $trustProfile->save();
    }
}
