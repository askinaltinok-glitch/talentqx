<?php

namespace App\Services\RankStcw;

use App\Models\CandidateContract;
use App\Models\CandidateTrustProfile;
use App\Models\PoolCandidate;
use App\Models\RankHierarchy;
use App\Models\SeaTimeLog;
use App\Services\Trust\RankProgressionAnalyzer;

class PromotionGapCalculator
{
    private RankProgressionAnalyzer $rankAnalyzer;

    public function __construct(RankProgressionAnalyzer $rankAnalyzer)
    {
        $this->rankAnalyzer = $rankAnalyzer;
    }

    /**
     * Calculate promotion gap for a candidate's current rank.
     *
     * PromotionGap = ActualRankDays - RequiredRankDays
     *   positive → served more than required (ready for promotion)
     *   negative → not yet met minimum requirement
     *   null     → top rank or insufficient data
     *
     * Returns: [
     *   current_rank        => string (canonical),
     *   department           => string,
     *   level                => int,
     *   next_rank            => string|null,
     *   actual_rank_days     => int,
     *   required_rank_days   => int,
     *   promotion_gap_days   => int|null,
     *   promotion_gap_months => float|null,
     *   is_eligible          => bool,
     *   is_top_rank          => bool,
     *   total_sea_days       => int,
     *   min_total_required   => int,
     *   total_gap_days       => int|null,
     * ]
     */
    public function calculate(string $poolCandidateId): ?array
    {
        $candidate = PoolCandidate::find($poolCandidateId);

        if (!$candidate) {
            return null;
        }

        // Determine current rank from latest contract (most recent start_date)
        $latestContract = CandidateContract::where('pool_candidate_id', $poolCandidateId)
            ->orderByDesc('start_date')
            ->first();
        if (!$latestContract || !$latestContract->rank_code) {
            return null;
        }

        $canonical = $this->rankAnalyzer->normalizeRank($latestContract->rank_code);
        if (!$canonical) {
            return null;
        }

        $hierarchy = RankHierarchy::findByCanonical($canonical);
        if (!$hierarchy) {
            return null;
        }

        // Get actual days at current rank from sea-time logs (latest batch)
        $actualRankDays = $this->getActualRankDays($poolCandidateId, $canonical);

        // Get total sea days from trust profile
        $totalSeaDays = $this->getTotalSeaDays($poolCandidateId);

        // Required days at this rank for promotion
        $requiredRankDays = $hierarchy->requiredDaysInRank();
        $isTopRank = $hierarchy->isTopRank();

        // Promotion gap at rank level
        $promotionGapDays = null;
        $promotionGapMonths = null;
        $isEligible = false;

        if (!$isTopRank && $requiredRankDays > 0) {
            $promotionGapDays = $actualRankDays - $requiredRankDays;
            $promotionGapMonths = round($promotionGapDays / 30.44, 1);
            $isEligible = $promotionGapDays >= 0;
        } elseif ($isTopRank) {
            $isEligible = false; // top rank, no further promotion
        } else {
            $isEligible = true; // no min required (0 months)
        }

        // Total sea time gap (vs min_total_sea_months)
        $minTotalRequired = (int) round($hierarchy->min_total_sea_months * 30.44);
        $totalGapDays = null;
        if ($minTotalRequired > 0) {
            $totalGapDays = $totalSeaDays - $minTotalRequired;
        }

        return [
            'current_rank' => $canonical,
            'department' => $hierarchy->department,
            'level' => $hierarchy->level,
            'next_rank' => $hierarchy->next_rank_code,
            'actual_rank_days' => $actualRankDays,
            'required_rank_days' => $requiredRankDays,
            'promotion_gap_days' => $promotionGapDays,
            'promotion_gap_months' => $promotionGapMonths,
            'is_eligible' => $isEligible,
            'is_top_rank' => $isTopRank,
            'total_sea_days' => $totalSeaDays,
            'min_total_required' => $minTotalRequired,
            'total_gap_days' => $totalGapDays,
        ];
    }

    /**
     * Get actual calculated days at a specific rank from sea_time_logs.
     * Uses the latest computation batch.
     */
    private function getActualRankDays(string $poolCandidateId, string $canonicalRank): int
    {
        // Get the latest batch ID
        $latestLog = SeaTimeLog::where('pool_candidate_id', $poolCandidateId)
            ->orderByDesc('computed_at')
            ->first();

        if (!$latestLog) {
            return 0;
        }

        // Sum calculated_days for this rank in the latest batch
        // Normalize all rank codes to canonical for matching
        $logs = SeaTimeLog::where('pool_candidate_id', $poolCandidateId)
            ->where('computation_batch_id', $latestLog->computation_batch_id)
            ->get();

        $days = 0;
        foreach ($logs as $log) {
            $logCanonical = $this->rankAnalyzer->normalizeRank($log->rank_code ?? '');
            if ($logCanonical === $canonicalRank) {
                $days += $log->calculated_days;
            }
        }

        return $days;
    }

    /**
     * Get total sea days from trust profile (sea_time summary).
     */
    private function getTotalSeaDays(string $poolCandidateId): int
    {
        $trustProfile = CandidateTrustProfile::where('pool_candidate_id', $poolCandidateId)->first();

        if (!$trustProfile) {
            return 0;
        }

        $detailJson = $trustProfile->detail_json ?? [];
        return (int) ($detailJson['sea_time']['total_sea_days'] ?? 0);
    }
}
