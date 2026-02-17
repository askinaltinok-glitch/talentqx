<?php

namespace App\Services\Interview;

use App\Models\InterviewSessionAnalysis;
use App\Models\JobContext;

class ContextScoringService
{
    /**
     * Standard dimension mapping to analysis dimensions
     */
    private const DIMENSION_MAP = [
        'clarity' => 'communication',
        'ownership' => 'integrity',
        'problem' => 'problem_solving',
        'stress' => 'stress_tolerance',
        'consistency' => 'consistency', // From behavior_analysis
    ];

    /**
     * Calculate context-weighted scores for an analysis
     */
    public function calculateWeightedScores(
        InterviewSessionAnalysis $analysis,
        ?JobContext $context = null
    ): array {
        $dimensionScores = $this->extractDimensionScores($analysis);

        if (!$context) {
            // No context = no weighting, return raw scores
            return [
                'raw_scores' => $dimensionScores,
                'weighted_scores' => $dimensionScores,
                'weights_applied' => JobContext::DEFAULT_WEIGHTS,
                'weighted_total' => $this->calculateTotal($dimensionScores),
            ];
        }

        $weights = $context->getWeights();
        $weightedScores = [];

        foreach ($dimensionScores as $dim => $score) {
            $multiplier = $weights[$dim] ?? 1.0;
            $weightedScores[$dim] = round($score * $multiplier / max(array_values($weights)), 1);
        }

        return [
            'raw_scores' => $dimensionScores,
            'weighted_scores' => $weightedScores,
            'weights_applied' => $weights,
            'weighted_total' => $this->calculateWeightedTotal($dimensionScores, $weights),
            'context_key' => $context->context_key,
            'context_label' => $context->label_tr,
        ];
    }

    /**
     * Calculate scores for all contexts of a role
     */
    public function calculateAllContextScores(
        InterviewSessionAnalysis $analysis,
        string $roleKey
    ): array {
        $contexts = JobContext::getForRole($roleKey);
        $dimensionScores = $this->extractDimensionScores($analysis);

        $results = [];
        foreach ($contexts as $context) {
            $weights = $context->getWeights();
            $weightedTotal = $this->calculateWeightedTotal($dimensionScores, $weights);

            $results[] = [
                'context_key' => $context->context_key,
                'label_tr' => $context->label_tr,
                'label_en' => $context->label_en,
                'risk_level' => $context->risk_level,
                'score' => $weightedTotal,
                'fit_indicator' => $this->getFitIndicator($weightedTotal),
                'weights' => $weights,
            ];
        }

        // Sort by score descending
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Extract standard dimension scores from analysis
     */
    private function extractDimensionScores(InterviewSessionAnalysis $analysis): array
    {
        $dimensions = $analysis->dimension_scores ?? [];
        $behavior = $analysis->behavior_analysis ?? [];

        return [
            'clarity' => $dimensions['communication']['score'] ?? 50,
            'ownership' => $dimensions['integrity']['score'] ?? 50,
            'problem' => $dimensions['problem_solving']['score'] ?? 50,
            'stress' => $dimensions['stress_tolerance']['score'] ?? 50,
            'consistency' => $behavior['consistency_score'] ?? 50,
        ];
    }

    /**
     * Calculate simple average total
     */
    private function calculateTotal(array $scores): float
    {
        return round(array_sum($scores) / count($scores), 1);
    }

    /**
     * Calculate weighted total normalized to 0-100
     */
    private function calculateWeightedTotal(array $scores, array $weights): float
    {
        $totalWeight = array_sum($weights);
        $weightedSum = 0;

        foreach ($scores as $dim => $score) {
            $weight = $weights[$dim] ?? 1.0;
            $weightedSum += $score * $weight;
        }

        // Normalize: weightedSum / totalWeight gives us the weighted average
        return round($weightedSum / $totalWeight, 1);
    }

    /**
     * Get fit indicator based on score
     */
    private function getFitIndicator(float $score): array
    {
        if ($score >= 75) {
            return ['symbol' => '⭐', 'label_tr' => 'En Uygun', 'label_en' => 'Best Fit', 'level' => 'excellent'];
        }
        if ($score >= 65) {
            return ['symbol' => '✅', 'label_tr' => 'Uygun', 'label_en' => 'Suitable', 'level' => 'good'];
        }
        if ($score >= 50) {
            return ['symbol' => '⚠️', 'label_tr' => 'Değerlendir', 'label_en' => 'Consider', 'level' => 'moderate'];
        }
        return ['symbol' => '❌', 'label_tr' => 'Önerilmez', 'label_en' => 'Not Recommended', 'level' => 'poor'];
    }

    /**
     * Get context comparison data for PDF
     */
    public function getContextComparisonForPdf(
        InterviewSessionAnalysis $analysis,
        string $roleKey,
        string $locale = 'tr'
    ): array {
        $contextScores = $this->calculateAllContextScores($analysis, $roleKey);

        return array_map(function ($ctx) use ($locale) {
            return [
                'context_key' => $ctx['context_key'],
                'context' => $locale === 'en' ? $ctx['label_en'] : $ctx['label_tr'],
                'score' => $ctx['score'],
                'indicator' => $ctx['fit_indicator']['symbol'],
                'status' => $locale === 'en'
                    ? $ctx['fit_indicator']['label_en']
                    : $ctx['fit_indicator']['label_tr'],
                'level' => $ctx['fit_indicator']['level'],
            ];
        }, $contextScores);
    }
}
