<?php

namespace App\Services\Trust;

use App\Services\Stability\StabilityConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContractPatternAnalyzer
{
    /**
     * Analyze a collection of CandidateContract models.
     *
     * All thresholds read from StabilityConfig — zero hardcoded values.
     */
    public function analyze(Collection $contracts, ?StabilityConfig $cfg = null): array
    {
        if ($contracts->isEmpty()) {
            return $this->emptyResult();
        }

        $cfg = $cfg ?? new StabilityConfig();

        $sorted = $contracts->sortBy('start_date')->values();
        $total = $sorted->count();

        // ── Duration metrics ──
        $durations = $sorted->map(fn($c) => $c->durationMonths())->all();
        $avgDuration = array_sum($durations) / $total;

        // ── Rank-aware short contract counting ──
        $shortCount = 0;
        foreach ($sorted as $i => $contract) {
            $canonicalRank = $this->resolveCanonicalRank($contract->rank_code);
            $threshold = $cfg->getShortContractMonths($canonicalRank);
            if ($durations[$i] < $threshold) {
                $shortCount++;
            }
        }
        $shortRatio = $shortCount / $total;

        // ── Company metrics ──
        $companies = $sorted->pluck('company_name')
            ->map(fn($c) => mb_strtolower(trim($c)))
            ->all();
        $companyCounts = array_count_values($companies);
        $uniqueCompanies = count($companyCounts);
        $repeatCompanies = count(array_filter($companyCounts, fn($c) => $c >= 2));
        $companyRepeatRatio = $uniqueCompanies > 0
            ? $repeatCompanies / $uniqueCompanies
            : 0;

        // ── Gap analysis ──
        $gaps = [];
        $totalGapMonths = 0;
        $longestGap = 0;

        for ($i = 1; $i < $total; $i++) {
            $prevEnd = $sorted[$i - 1]->end_date;
            $currStart = $sorted[$i]->start_date;

            if ($prevEnd && $currStart->gt($prevEnd)) {
                $gapMonths = round($prevEnd->diffInDays($currStart) / 30.44, 1);
                $gaps[] = [
                    'from' => $prevEnd->toDateString(),
                    'to' => $currStart->toDateString(),
                    'months' => $gapMonths,
                ];
                $totalGapMonths += $gapMonths;
                $longestGap = max($longestGap, $gapMonths);
            }
        }

        // ── Overlap detection ──
        $overlaps = [];
        for ($i = 1; $i < $total; $i++) {
            $prevEnd = $sorted[$i - 1]->end_date;
            $currStart = $sorted[$i]->start_date;

            if ($prevEnd && $currStart->lt($prevEnd)) {
                $overlapDays = $currStart->diffInDays($prevEnd);
                $overlaps[] = [
                    'contract_a_id' => $sorted[$i - 1]->id,
                    'contract_b_id' => $sorted[$i]->id,
                    'overlap_days' => $overlapDays,
                ];
            }
        }

        // ── Frequent switch (unique companies in recent window) ──
        $windowYears = $cfg->get('recent_companies_window_years', 3);
        $windowStart = Carbon::now()->subYears($windowYears);
        $recentUnique = $sorted
            ->filter(fn($c) => $c->start_date->gte($windowStart))
            ->pluck('company_name')
            ->map(fn($c) => mb_strtolower(trim($c)))
            ->unique()
            ->count();

        // ── Flags (all thresholds from config) ──
        $flags = [];
        $shortRatioFlag = (float) $cfg->get('short_ratio_flag_threshold', 0.6);
        $gapFlag = (float) $cfg->get('gap_months_flag_threshold', 18);
        $switchFlag = (int) $cfg->get('frequent_switch_flag_threshold', 6);

        if ($shortRatio > $shortRatioFlag) {
            $flags[] = 'FLAG_SHORT_PATTERN';
        }
        if (count($overlaps) > 0) {
            $flags[] = 'FLAG_OVERLAP';
        }
        if ($totalGapMonths > $gapFlag) {
            $flags[] = 'FLAG_LONG_GAP';
        }
        if ($recentUnique > $switchFlag) {
            $flags[] = 'FLAG_FREQUENT_SWITCH';
        }

        return [
            'total_contracts' => $total,
            'avg_duration_months' => round($avgDuration, 1),
            'shortest_duration' => round(min($durations), 1),
            'longest_duration' => round(max($durations), 1),
            'short_contract_count' => $shortCount,
            'short_contract_ratio' => round($shortRatio, 4),
            'unique_companies' => $uniqueCompanies,
            'company_repeat_ratio' => round($companyRepeatRatio, 4),
            'gap_periods' => $gaps,
            'total_gap_months' => round($totalGapMonths, 1),
            'longest_gap_months' => round($longestGap, 1),
            'overlaps' => $overlaps,
            'overlap_count' => count($overlaps),
            'recent_unique_companies_3y' => $recentUnique,
            'flags' => $flags,
        ];
    }

    /**
     * Best-effort rank normalization without full RankProgressionAnalyzer dependency.
     */
    private function resolveCanonicalRank(?string $rankCode): ?string
    {
        if (!$rankCode) {
            return null;
        }

        $upper = strtoupper(trim($rankCode));
        $knownRanks = [
            'DC','OS','AB','BSN','3/O','2/O','C/O','MASTER',
            'EC','WP','OL','MO','4/E','3/E','2/E','C/E',
            'ETO','ELECTRO','MESS','COOK','CH.COOK','STEWARD','CH.STEWARD',
        ];
        if (in_array($upper, $knownRanks)) {
            return $upper;
        }

        // Common aliases
        $aliases = [
            'CAPTAIN' => 'MASTER', 'CAPT' => 'MASTER',
            'CHIEF OFFICER' => 'C/O', 'CHIEF MATE' => 'C/O',
            'SECOND OFFICER' => '2/O', '2ND OFFICER' => '2/O',
            'THIRD OFFICER' => '3/O', '3RD OFFICER' => '3/O',
            'CHIEF ENGINEER' => 'C/E', 'SECOND ENGINEER' => '2/E',
            'THIRD ENGINEER' => '3/E', 'FOURTH ENGINEER' => '4/E',
            'BOSUN' => 'BSN', 'BOATSWAIN' => 'BSN',
            'ABLE SEAMAN' => 'AB', 'ORDINARY SEAMAN' => 'OS',
            'DECK CADET' => 'DC', 'ENGINE CADET' => 'EC',
            'OILER' => 'OL', 'MOTORMAN' => 'MO', 'WIPER' => 'WP',
        ];

        return $aliases[$upper] ?? null;
    }

    private function emptyResult(): array
    {
        return [
            'total_contracts' => 0,
            'avg_duration_months' => 0,
            'shortest_duration' => 0,
            'longest_duration' => 0,
            'short_contract_count' => 0,
            'short_contract_ratio' => 0,
            'unique_companies' => 0,
            'company_repeat_ratio' => 0,
            'gap_periods' => [],
            'total_gap_months' => 0,
            'longest_gap_months' => 0,
            'overlaps' => [],
            'overlap_count' => 0,
            'recent_unique_companies_3y' => 0,
            'flags' => [],
        ];
    }
}
