<?php

namespace App\Services\Fleet;

use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\Vessel;
use App\Models\VesselRiskSnapshot;
use App\Services\Maritime\FleetTypeResolver;

class VesselRiskAggregator
{
    /**
     * Compute aggregated risk data for a vessel. Returns null if feature
     * is disabled or the vessel has no active crew.
     */
    public function compute(string $vesselId): ?array
    {
        if (!config('maritime.vessel_risk_v1')) {
            return null;
        }

        $vessel = Vessel::find($vesselId);
        if (!$vessel) {
            return null;
        }

        // Resolve active crew: contracts linked by vessel_id OR vessel_imo
        $candidateIds = CandidateContract::where(function ($q) use ($vesselId, $vessel) {
            $q->where('vessel_id', $vesselId);
            if ($vessel->imo) {
                $q->orWhere('vessel_imo', $vessel->imo);
            }
        })
            ->whereNull('end_date')
            ->pluck('pool_candidate_id')
            ->unique()
            ->values();

        if ($candidateIds->isEmpty()) {
            return null;
        }

        // Load trust profiles for active crew
        $profiles = CandidateTrustProfile::whereIn('pool_candidate_id', $candidateIds)->get();

        if ($profiles->isEmpty()) {
            return null;
        }

        // Extract scores
        $predictiveRisks = [];
        $stabilityIndices = [];
        $complianceScores = [];
        $competencyScores = [];
        $predictiveTiers = [];
        $crewDetails = [];

        foreach ($profiles as $profile) {
            $detail = $profile->detail_json ?? [];
            $predictiveRisk = $detail['predictive_risk']['predictive_risk_index'] ?? null;
            $predictiveTier = $detail['predictive_risk']['predictive_tier'] ?? null;

            if ($predictiveRisk !== null) {
                $predictiveRisks[] = (float) $predictiveRisk;
            }
            if ($predictiveTier !== null) {
                $predictiveTiers[] = $predictiveTier;
            }
            if ($profile->stability_index !== null) {
                $stabilityIndices[] = (float) $profile->stability_index;
            }
            if ($profile->compliance_score !== null) {
                $complianceScores[] = (float) $profile->compliance_score;
            }
            if ($profile->competency_score !== null) {
                $competencyScores[] = (float) $profile->competency_score;
            }

            $crewDetails[] = [
                'candidate_id' => $profile->pool_candidate_id,
                'predictive_risk_index' => $predictiveRisk,
                'predictive_tier' => $predictiveTier,
                'stability_index' => $profile->stability_index,
                'compliance_score' => $profile->compliance_score,
                'competency_score' => $profile->competency_score,
            ];
        }

        $crewCount = $candidateIds->count();
        $avgPredictive = !empty($predictiveRisks) ? round(array_sum($predictiveRisks) / count($predictiveRisks), 4) : null;
        $avgStability = !empty($stabilityIndices) ? round(array_sum($stabilityIndices) / count($stabilityIndices), 4) : null;
        $avgCompliance = !empty($complianceScores) ? round(array_sum($complianceScores) / count($complianceScores), 4) : null;
        $avgCompetency = !empty($competencyScores) ? round(array_sum($competencyScores) / count($competencyScores), 4) : null;

        $highRiskCount = collect($predictiveTiers)->filter(fn($t) => $t === 'high')->count();
        $criticalRiskCount = collect($predictiveTiers)->filter(fn($t) => $t === 'critical')->count();

        // Determine vessel tier
        $vesselTier = $this->resolveTier($avgPredictive, $highRiskCount, $criticalRiskCount);

        // Fleet type from vessel
        $fleetType = FleetTypeResolver::mapVesselType($vessel->vessel_type_normalized ?? $vessel->type ?? '');

        return [
            'vessel_id' => $vesselId,
            'fleet_type' => $fleetType,
            'vessel_tier' => $vesselTier,
            'crew_count' => $crewCount,
            'avg_predictive_risk' => $avgPredictive,
            'avg_stability_index' => $avgStability,
            'avg_compliance_score' => $avgCompliance,
            'avg_competency_score' => $avgCompetency,
            'high_risk_count' => $highRiskCount,
            'critical_risk_count' => $criticalRiskCount,
            'detail_json' => $crewDetails,
            'computed_at' => now(),
        ];
    }

    /**
     * Compute and persist a risk snapshot for the vessel.
     */
    public function computeAndStore(string $vesselId): ?VesselRiskSnapshot
    {
        $data = $this->compute($vesselId);
        if (!$data) {
            return null;
        }

        return VesselRiskSnapshot::create($data);
    }

    /**
     * Resolve vessel tier from aggregate risk metrics.
     */
    private function resolveTier(?float $avgPredictiveRisk, int $highRiskCount, int $criticalRiskCount): string
    {
        if ($avgPredictiveRisk !== null && $avgPredictiveRisk >= 70) return 'critical';
        if ($criticalRiskCount >= 2) return 'critical';

        if ($avgPredictiveRisk !== null && $avgPredictiveRisk >= 55) return 'high';
        if ($highRiskCount >= 3) return 'high';

        if ($avgPredictiveRisk !== null && $avgPredictiveRisk >= 40) return 'medium';

        return 'low';
    }
}
