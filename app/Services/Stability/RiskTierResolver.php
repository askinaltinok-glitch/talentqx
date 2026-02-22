<?php

namespace App\Services\Stability;

use App\Models\CandidateTrustProfile;

/**
 * Maps a risk score (0.0–1.0) to a risk tier.
 *
 * v1.1: All thresholds read from StabilityConfig — zero hardcoded values.
 */
class RiskTierResolver
{
    /**
     * Resolve risk tier from risk score.
     */
    public function resolve(float $riskScore, ?StabilityConfig $cfg = null): string
    {
        $cfg = $cfg ?? new StabilityConfig();
        $thresholds = $cfg->getRiskTierThresholds();

        // Sort descending by threshold value
        arsort($thresholds);

        foreach ($thresholds as $tier => $threshold) {
            if ($riskScore >= $threshold) {
                return $tier;
            }
        }

        return CandidateTrustProfile::RISK_LOW;
    }

    /**
     * Get all tier definitions with thresholds.
     */
    public static function tiers(?StabilityConfig $cfg = null): array
    {
        $cfg = $cfg ?? new StabilityConfig();
        $t = $cfg->getRiskTierThresholds();

        return [
            CandidateTrustProfile::RISK_LOW => [
                'min' => 0.00,
                'max' => $t['medium'] ?? 0.25,
                'label' => 'Low Risk',
                'action' => 'Standard processing',
            ],
            CandidateTrustProfile::RISK_MEDIUM => [
                'min' => $t['medium'] ?? 0.25,
                'max' => $t['high'] ?? 0.50,
                'label' => 'Medium Risk',
                'action' => 'Monitor closely',
            ],
            CandidateTrustProfile::RISK_HIGH => [
                'min' => $t['high'] ?? 0.50,
                'max' => $t['critical'] ?? 0.75,
                'label' => 'High Risk',
                'action' => 'Enhanced review required',
            ],
            CandidateTrustProfile::RISK_CRITICAL => [
                'min' => $t['critical'] ?? 0.75,
                'max' => 1.00,
                'label' => 'Critical Risk',
                'action' => 'Manual review required',
            ],
        ];
    }
}
