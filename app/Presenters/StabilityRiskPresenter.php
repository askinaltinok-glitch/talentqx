<?php

namespace App\Presenters;

use App\Models\CandidateTrustProfile;

class StabilityRiskPresenter
{
    /**
     * Present stability & risk data from trust profile.
     */
    public static function fromTrustProfile(?CandidateTrustProfile $trustProfile): ?array
    {
        if (!$trustProfile) {
            return null;
        }

        $detail = $trustProfile->detail_json ?? [];
        $stabilityRisk = $detail['stability_risk'] ?? null;

        if (!$stabilityRisk) {
            return null;
        }

        return [
            'stability_index' => $stabilityRisk['stability_index'] ?? null,
            'stability' => $stabilityRisk['stability'] ?? null,
            'risk_score' => $stabilityRisk['risk_score'] ?? 0,
            'risk_tier' => $stabilityRisk['risk_tier'] ?? null,
            'risk_factors' => $stabilityRisk['risk_factors'] ?? [],
            'contract_summary' => $stabilityRisk['contract_summary'] ?? null,
            'rank_anomalies' => $stabilityRisk['rank_anomalies'] ?? [],
            'flags' => $stabilityRisk['flags'] ?? [],
            'computed_at' => $stabilityRisk['computed_at'] ?? null,
        ];
    }
}
