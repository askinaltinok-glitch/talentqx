<?php

namespace App\Services\Stability;

use Illuminate\Support\Collection;

/**
 * StabilityIndex = avg(ContractDuration) / σ(ContractDuration)
 *
 * Inverse coefficient of variation (1/CV).
 * Higher = more consistent contract durations = more stable.
 *
 * v1.1: All thresholds from StabilityConfig — min_contracts, std_threshold, max_cap.
 */
class StabilityIndexCalculator
{
    /**
     * Calculate stability index from a candidate's contracts.
     *
     * @param Collection $contracts  CandidateContract collection
     * @param StabilityConfig|null $cfg  config resolver
     */
    public function calculate(Collection $contracts, ?StabilityConfig $cfg = null): array
    {
        $cfg = $cfg ?? new StabilityConfig();

        $minContracts = (int) $cfg->get('stability_index_min_contracts', 2);
        $stdThreshold = (float) $cfg->get('stability_index_std_threshold', 0.001);
        $maxCap = (float) $cfg->get('stability_index_max_cap', 10.0);

        // Only completed contracts with end_date
        $completed = $contracts->filter(fn($c) => $c->end_date !== null);

        if ($completed->count() < $minContracts) {
            return [
                'stability_index' => null,
                'avg_months' => $completed->count() === 1 ? $completed->first()->durationMonths() : 0,
                'std_months' => 0,
                'contract_count' => $completed->count(),
            ];
        }

        $durations = $completed->map(fn($c) => $c->durationMonths())->values()->all();
        $n = count($durations);
        $mean = array_sum($durations) / $n;

        // Standard deviation (population σ)
        $sumSquaredDiff = 0;
        foreach ($durations as $d) {
            $sumSquaredDiff += ($d - $mean) ** 2;
        }
        $std = sqrt($sumSquaredDiff / $n);

        // Stability index = mean / std (inverse CV)
        $stabilityIndex = $std > $stdThreshold
            ? min($maxCap, round($mean / $std, 4))
            : $maxCap; // σ ≈ 0 → perfectly stable → cap

        return [
            'stability_index' => $stabilityIndex,
            'avg_months' => round($mean, 1),
            'std_months' => round($std, 2),
            'contract_count' => $n,
        ];
    }
}
