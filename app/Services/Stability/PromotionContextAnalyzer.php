<?php

namespace App\Services\Stability;

use App\Services\RankStcw\PromotionGapCalculator;
use Illuminate\Support\Collection;

/**
 * Phase 3: Promotion Window Context
 *
 * Detects if a candidate is within a promotion window (near expected promotion).
 * When true, short contract and frequent switch penalties are reduced
 * by a configurable modifier.
 *
 * Logic: If promotion_gap_days is within ±N months of 0 (configurable window),
 * the candidate is in "promotion transition" and short stints are expected.
 */
class PromotionContextAnalyzer
{
    /**
     * Analyze promotion context and return penalty modifier.
     *
     * @param string $poolCandidateId
     * @param StabilityConfig $cfg
     * @return array{in_promotion_window: bool, modifier: float, promotion_gap: array|null}
     */
    public function analyze(string $poolCandidateId, StabilityConfig $cfg): array
    {
        $windowMonths = (int) $cfg->get('promotion_window_months', 12);
        $modifier = (float) $cfg->get('promotion_penalty_modifier', 0.5);

        try {
            $calculator = app(PromotionGapCalculator::class);
            $gap = $calculator->calculate($poolCandidateId);
        } catch (\Throwable $e) {
            return [
                'in_promotion_window' => false,
                'modifier' => 1.0,
                'promotion_gap' => null,
            ];
        }

        if (!$gap || $gap['is_top_rank']) {
            return [
                'in_promotion_window' => false,
                'modifier' => 1.0,
                'promotion_gap' => $gap,
            ];
        }

        // Check if within promotion window
        // promotion_gap_months: positive = ready, negative = not yet
        $gapMonths = $gap['promotion_gap_months'] ?? null;
        if ($gapMonths === null) {
            return [
                'in_promotion_window' => false,
                'modifier' => 1.0,
                'promotion_gap' => $gap,
            ];
        }

        // In window if: gap is between -windowMonths and +windowMonths
        // i.e. candidate is close to meeting (or has recently met) promotion requirements
        $inWindow = $gapMonths >= -$windowMonths && $gapMonths <= $windowMonths;

        return [
            'in_promotion_window' => $inWindow,
            'modifier' => $inWindow ? $modifier : 1.0,
            'promotion_gap' => $gap,
        ];
    }
}
