<?php

namespace App\Services\Stability;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Phase 4: Temporal Decay
 *
 * Weights recent instability heavier than old instability.
 * Contracts within recent_months are weighted higher.
 * Contracts older than old_months are weighted lower.
 *
 * Output: a "temporal recency score" (0.0 - 1.0) that represents
 * how much of the candidate's instability is recent.
 * 0.0 = all instability is old (less concerning)
 * 1.0 = all instability is recent (very concerning)
 */
class TemporalDecayCalculator
{
    /**
     * Calculate temporal recency score for a candidate's contracts.
     *
     * @param Collection $contracts  CandidateContract collection (with start_date, end_date)
     * @param StabilityConfig $cfg
     * @return array{temporal_recency_score: float, recent_short_ratio: float, old_short_ratio: float, weights_applied: array}
     */
    public function calculate(Collection $contracts, StabilityConfig $cfg): array
    {
        if ($contracts->isEmpty()) {
            return [
                'temporal_recency_score' => 0.0,
                'recent_short_ratio' => 0.0,
                'old_short_ratio' => 0.0,
                'weights_applied' => [],
            ];
        }

        $decay = $cfg->getTemporalDecay();
        $recentMonths = (int) $decay['recent_months'];
        $oldMonths = (int) $decay['old_months'];
        $recentWeight = (float) $decay['recent_weight'];
        $oldWeight = (float) $decay['old_weight'];
        $defaultWeight = (float) $decay['default_weight'];

        $now = Carbon::now();
        $recentCutoff = $now->copy()->subMonths($recentMonths);
        $oldCutoff = $now->copy()->subMonths($oldMonths);

        $sorted = $contracts->sortBy('start_date')->values();

        $recentShortCount = 0;
        $recentTotal = 0;
        $oldShortCount = 0;
        $oldTotal = 0;
        $weightedShortSum = 0.0;
        $weightedTotalSum = 0.0;
        $weightsApplied = [];

        $globalShortThreshold = (float) $cfg->get('short_contract_months', 6);

        foreach ($sorted as $contract) {
            $startDate = $contract->start_date;
            $duration = $contract->durationMonths();

            // Determine temporal weight
            $weight = $defaultWeight;
            $bucket = 'middle';
            if ($startDate->gte($recentCutoff)) {
                $weight = $recentWeight;
                $bucket = 'recent';
                $recentTotal++;
            } elseif ($startDate->lt($oldCutoff)) {
                $weight = $oldWeight;
                $bucket = 'old';
                $oldTotal++;
            }

            $isShort = $duration < $globalShortThreshold;

            if ($isShort) {
                $weightedShortSum += $weight;
                if ($bucket === 'recent') $recentShortCount++;
                if ($bucket === 'old') $oldShortCount++;
            }
            $weightedTotalSum += $weight;

            $weightsApplied[] = [
                'contract_id' => $contract->id,
                'bucket' => $bucket,
                'weight' => $weight,
                'duration_months' => $duration,
                'is_short' => $isShort,
            ];
        }

        // Temporal recency score: how much of weighted short contracts are recent
        $temporalRecencyScore = $weightedTotalSum > 0
            ? min(1.0, $weightedShortSum / $weightedTotalSum)
            : 0.0;

        $recentShortRatio = $recentTotal > 0 ? $recentShortCount / $recentTotal : 0.0;
        $oldShortRatio = $oldTotal > 0 ? $oldShortCount / $oldTotal : 0.0;

        return [
            'temporal_recency_score' => round($temporalRecencyScore, 4),
            'recent_short_ratio' => round($recentShortRatio, 4),
            'old_short_ratio' => round($oldShortRatio, 4),
            'weights_applied' => $weightsApplied,
        ];
    }
}
