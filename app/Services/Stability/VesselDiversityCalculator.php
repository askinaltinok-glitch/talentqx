<?php

namespace App\Services\Stability;

use Illuminate\Support\Collection;

/**
 * Phase 5: Vessel Diversity Factor
 *
 * Evaluates whether a candidate has meaningful experience across multiple
 * vessel types. Diversity WITH tenure is a positive signal (reduces risk).
 * Diversity WITHOUT tenure (hopping) does not earn bonus.
 *
 * Output: vessel_diversity_score (0.0 – 1.0)
 *   0.0 = single vessel type or no qualifying tenure
 *   1.0 = max diverse experience across vessel types with tenure
 */
class VesselDiversityCalculator
{
    /**
     * Calculate vessel diversity score from a candidate's contracts.
     *
     * @param Collection $contracts  CandidateContract collection (with vessel_type, start_date, end_date)
     * @param StabilityConfig $cfg
     * @return array{vessel_diversity_score: float, qualifying_types: int, type_breakdown: array, total_types: int}
     */
    public function calculate(Collection $contracts, StabilityConfig $cfg): array
    {
        if ($contracts->isEmpty()) {
            return [
                'vessel_diversity_score' => 0.0,
                'qualifying_types' => 0,
                'type_breakdown' => [],
                'total_types' => 0,
            ];
        }

        $diversityCfg = $cfg->getVesselDiversity();
        $minTypes = (int) $diversityCfg['min_types_for_bonus'];
        $maxTypes = (int) $diversityCfg['max_types_for_bonus'];
        $minTenure = (float) $diversityCfg['min_tenure_months'];
        $maxScore = (float) $diversityCfg['max_score'];

        // Group contracts by vessel_type and accumulate tenure
        $typeMonths = [];
        $typeContracts = [];

        foreach ($contracts as $contract) {
            $vesselType = $contract->vessel_type;
            if (!$vesselType || $vesselType === '' || $vesselType === 'other') {
                continue; // Skip unknown/other types
            }

            $type = mb_strtolower(trim($vesselType));
            $duration = $contract->durationMonths();

            if (!isset($typeMonths[$type])) {
                $typeMonths[$type] = 0.0;
                $typeContracts[$type] = 0;
            }

            $typeMonths[$type] += $duration;
            $typeContracts[$type]++;
        }

        // Count types with qualifying tenure
        $totalTypes = count($typeMonths);
        $qualifyingTypes = 0;
        $typeBreakdown = [];

        foreach ($typeMonths as $type => $months) {
            $qualifies = $months >= $minTenure;
            if ($qualifies) {
                $qualifyingTypes++;
            }
            $typeBreakdown[] = [
                'vessel_type' => $type,
                'total_months' => round($months, 1),
                'contract_count' => $typeContracts[$type],
                'qualifies' => $qualifies,
            ];
        }

        // Calculate diversity score
        // Need at least min_types qualifying types for any bonus
        if ($qualifyingTypes < $minTypes) {
            return [
                'vessel_diversity_score' => 0.0,
                'qualifying_types' => $qualifyingTypes,
                'type_breakdown' => $typeBreakdown,
                'total_types' => $totalTypes,
            ];
        }

        // Linear scale: min_types → 0.0, max_types → max_score
        // Capped at max_types (diminishing returns above that)
        $effectiveTypes = min($qualifyingTypes, $maxTypes);
        $range = max(1, $maxTypes - $minTypes);
        $score = ($effectiveTypes - $minTypes) / $range;
        $score = min($maxScore, max(0.0, $score * $maxScore));

        // Ensure at least a small bonus when qualifying (min_types met = small reward)
        // At min_types: score = 0.0 from formula, give base bonus
        if ($qualifyingTypes >= $minTypes && $score < 0.1) {
            $score = 0.1 * $maxScore; // 10% base bonus for meeting minimum
        }

        return [
            'vessel_diversity_score' => round($score, 4),
            'qualifying_types' => $qualifyingTypes,
            'type_breakdown' => $typeBreakdown,
            'total_types' => $totalTypes,
        ];
    }
}
