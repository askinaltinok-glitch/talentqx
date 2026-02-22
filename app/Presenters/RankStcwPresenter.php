<?php

namespace App\Presenters;

use App\Models\CandidateTrustProfile;

class RankStcwPresenter
{
    /**
     * Present Rank & STCW technical score data from trust profile.
     */
    public static function fromTrustProfile(?CandidateTrustProfile $trustProfile): ?array
    {
        if (!$trustProfile) {
            return null;
        }

        $detail = $trustProfile->detail_json ?? [];
        $rankStcw = $detail['rank_stcw'] ?? null;

        if (!$rankStcw) {
            return null;
        }

        $promotionGap = $rankStcw['promotion_gap'] ?? null;
        $stcwCompliance = $rankStcw['stcw_compliance'] ?? null;
        $vesselMatch = $rankStcw['vessel_match'] ?? null;

        return [
            'technical_score' => $rankStcw['technical_score'] ?? 0,
            'rank_days_weight' => $rankStcw['rank_days_weight'] ?? 0,
            'vessel_match_weight' => $rankStcw['vessel_match_weight'] ?? 0,
            'certification_weight' => $rankStcw['certification_weight'] ?? 0,
            'computed_at' => $rankStcw['computed_at'] ?? null,

            'promotion_gap' => $promotionGap ? [
                'current_rank' => $promotionGap['current_rank'] ?? null,
                'department' => $promotionGap['department'] ?? null,
                'level' => $promotionGap['level'] ?? null,
                'next_rank' => $promotionGap['next_rank'] ?? null,
                'actual_rank_days' => $promotionGap['actual_rank_days'] ?? 0,
                'required_rank_days' => $promotionGap['required_rank_days'] ?? 0,
                'promotion_gap_days' => $promotionGap['promotion_gap_days'] ?? null,
                'promotion_gap_months' => $promotionGap['promotion_gap_months'] ?? null,
                'is_eligible' => $promotionGap['is_eligible'] ?? false,
                'is_top_rank' => $promotionGap['is_top_rank'] ?? false,
                'total_sea_days' => $promotionGap['total_sea_days'] ?? 0,
            ] : null,

            'stcw_compliance' => $stcwCompliance ? [
                'compliance_ratio' => $stcwCompliance['compliance_ratio'] ?? 0,
                'total_required' => $stcwCompliance['total_required'] ?? 0,
                'total_held' => $stcwCompliance['total_held'] ?? 0,
                'missing_certs' => $stcwCompliance['missing_certs'] ?? [],
                'expired_certs' => $stcwCompliance['expired_certs'] ?? [],
                'expiring_soon' => $stcwCompliance['expiring_soon'] ?? [],
                'rank_code' => $stcwCompliance['rank_code'] ?? null,
                'vessel_type' => $stcwCompliance['vessel_type'] ?? null,
            ] : null,

            'vessel_match' => $vesselMatch ? [
                'weight' => $vesselMatch['weight'] ?? 0,
                'vessel_types' => $vesselMatch['vessel_types'] ?? 0,
                'dominant_pct' => $vesselMatch['dominant_pct'] ?? 0,
                'total_days' => $vesselMatch['total_days'] ?? 0,
            ] : null,
        ];
    }
}
