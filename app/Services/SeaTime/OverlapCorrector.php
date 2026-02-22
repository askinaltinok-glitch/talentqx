<?php

namespace App\Services\SeaTime;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class OverlapCorrector
{
    /**
     * Correct overlapping contract periods using a greedy chronological approach.
     *
     * Input: Collection of contracts sorted by start_date, each having:
     *   - id, start_date (Carbon), end_date (Carbon|null), vessel_type, rank_code, vessel_id
     *
     * Output: array of corrected entries:
     *   [
     *     'contract_id'    => string,
     *     'original_start' => Carbon,
     *     'original_end'   => Carbon,
     *     'effective_start' => Carbon,
     *     'effective_end'  => Carbon,
     *     'raw_days'       => int,
     *     'calculated_days' => int,
     *     'overlap_deducted' => int,
     *     'vessel_type'    => string|null,
     *     'rank_code'      => string|null,
     *     'vessel_id'      => string|null,
     *   ]
     */
    public function correct(Collection $contracts): array
    {
        $results = [];
        $coveredUpTo = null; // Carbon date tracking the latest covered day

        foreach ($contracts as $contract) {
            $start = $contract->start_date;
            $end = $contract->end_date ?? Carbon::today();

            if (!$start || $start->greaterThan($end)) {
                continue;
            }

            $rawDays = $start->diffInDays($end);
            if ($rawDays === 0) {
                continue;
            }

            $effectiveStart = $start->copy();
            $effectiveEnd = $end->copy();
            $calculatedDays = $rawDays;
            $overlapDeducted = 0;

            if ($coveredUpTo !== null && $effectiveStart->lessThanOrEqualTo($coveredUpTo)) {
                // This contract overlaps with previously covered period
                $effectiveStart = $coveredUpTo->copy()->addDay();

                if ($effectiveStart->greaterThan($effectiveEnd)) {
                    // Fully overlapped by previous contracts
                    $calculatedDays = 0;
                    $overlapDeducted = $rawDays;
                    $effectiveStart = $start->copy();
                    $effectiveEnd = $start->copy();
                } else {
                    $calculatedDays = $effectiveStart->diffInDays($effectiveEnd);
                    $overlapDeducted = $rawDays - $calculatedDays;
                }
            }

            // Update covered timeline
            if ($coveredUpTo === null || $end->greaterThan($coveredUpTo)) {
                $coveredUpTo = $end->copy();
            }

            $results[] = [
                'contract_id' => $contract->id,
                'original_start' => $start,
                'original_end' => $contract->end_date ?? Carbon::today(),
                'effective_start' => $effectiveStart,
                'effective_end' => $effectiveEnd,
                'raw_days' => $rawDays,
                'calculated_days' => $calculatedDays,
                'overlap_deducted' => $overlapDeducted,
                'vessel_type' => $contract->vessel_type,
                'rank_code' => $contract->rank_code,
                'vessel_id' => $contract->vessel_id,
            ];
        }

        return $results;
    }

    /**
     * Compute total unique sea days by merging all intervals.
     * Used for verification: total_merged should equal sum of calculated_days.
     */
    public function mergedTotalDays(Collection $contracts): int
    {
        $intervals = [];

        foreach ($contracts as $contract) {
            $start = $contract->start_date;
            $end = $contract->end_date ?? Carbon::today();
            if (!$start || $start->greaterThan($end)) {
                continue;
            }
            $intervals[] = [$start->copy(), $end->copy()];
        }

        if (empty($intervals)) {
            return 0;
        }

        // Sort by start date
        usort($intervals, fn ($a, $b) => $a[0]->timestamp <=> $b[0]->timestamp);

        // Merge overlapping intervals
        $merged = [$intervals[0]];
        for ($i = 1; $i < count($intervals); $i++) {
            $last = &$merged[count($merged) - 1];
            if ($intervals[$i][0]->lessThanOrEqualTo($last[1])) {
                if ($intervals[$i][1]->greaterThan($last[1])) {
                    $last[1] = $intervals[$i][1]->copy();
                }
            } else {
                $merged[] = $intervals[$i];
            }
        }

        $total = 0;
        foreach ($merged as [$start, $end]) {
            $total += $start->diffInDays($end);
        }

        return $total;
    }
}
