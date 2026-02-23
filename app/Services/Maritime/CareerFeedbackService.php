<?php

namespace App\Services\Maritime;

class CareerFeedbackService
{
    /**
     * Friendly labels for competency keys.
     */
    private const COMPETENCY_LABELS = [
        'communication'     => 'Clear and effective communication',
        'accountability'    => 'Strong sense of responsibility',
        'teamwork'          => 'Collaborative team player',
        'stress_resilience' => 'Composure under pressure',
        'adaptability'      => 'Flexible and adaptable',
        'learning_agility'  => 'Quick learner',
        'integrity'         => 'High ethical standards',
        'role_competence'   => 'Strong technical knowledge',
    ];

    /**
     * Friendly labels for behavioral dimension keys.
     */
    private const DIMENSION_LABELS = [
        'responsibility'          => 'Reliable and accountable',
        'teamwork'                => 'Team-oriented mindset',
        'decision_under_pressure' => 'Sound decision-making under pressure',
        'communication'           => 'Effective communicator',
        'discipline'              => 'Disciplined and organized',
        'leadership'              => 'Leadership potential',
        'adaptability'            => 'Adaptable to change',
    ];

    /**
     * Encouraging development-area labels for competency keys.
     */
    private const COMPETENCY_DEV_LABELS = [
        'communication'     => 'Continue building communication skills',
        'accountability'    => 'Strengthen accountability habits',
        'teamwork'          => 'Develop collaborative working style',
        'stress_resilience' => 'Build strategies for high-pressure situations',
        'adaptability'      => 'Practice adapting to new environments',
        'learning_agility'  => 'Invest in continuous learning',
        'integrity'         => 'Deepen professional ethics awareness',
        'role_competence'   => 'Expand technical knowledge base',
    ];

    /**
     * Encouraging development-area labels for dimension keys.
     */
    private const DIMENSION_DEV_LABELS = [
        'responsibility'          => 'Build stronger ownership of tasks',
        'teamwork'                => 'Practice working more closely with teams',
        'decision_under_pressure' => 'Develop confidence in time-sensitive decisions',
        'communication'           => 'Practice clear and structured communication',
        'discipline'              => 'Strengthen organizational habits',
        'leadership'              => 'Seek opportunities to lead small teams',
        'adaptability'            => 'Embrace new challenges and change',
    ];

    /**
     * Convert competency scores into candidate-friendly feedback.
     *
     * @param  array<string, int|float>  $competencyScores  key => 0-100
     * @param  string|null               $decision          HIRE/HOLD/REJECT (not exposed)
     * @param  array|null                $riskFlags         internal risk codes (not exposed)
     */
    public function fromCompetencyScores(
        array $competencyScores,
        ?string $decision = null,
        ?array $riskFlags = [],
    ): array {
        // Sort descending by score
        arsort($competencyScores);

        $sorted = array_keys($competencyScores);

        // Strengths: top 3 with score >= 60
        $strengths = [];
        foreach ($sorted as $key) {
            if ($competencyScores[$key] >= 60 && count($strengths) < 3) {
                $strengths[] = self::COMPETENCY_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
            }
        }

        // Development areas: bottom 3 (< 60, or lowest overall)
        $reversed = array_reverse($sorted);
        $devAreas = [];
        // First pass: items below 60
        foreach ($reversed as $key) {
            if ($competencyScores[$key] < 60 && count($devAreas) < 3) {
                $devAreas[] = self::COMPETENCY_DEV_LABELS[$key] ?? 'Continue developing ' . str_replace('_', ' ', $key);
            }
        }
        // Second pass: if we still need more, take the lowest that aren't already strengths text
        if (count($devAreas) < 3) {
            foreach ($reversed as $key) {
                $label = self::COMPETENCY_DEV_LABELS[$key] ?? 'Continue developing ' . str_replace('_', ' ', $key);
                if (!in_array($label, $devAreas) && count($devAreas) < 3) {
                    $devAreas[] = $label;
                }
            }
        }

        $roleFit = $this->mapCompetencyRoleFit($competencyScores);

        return [
            'strengths'            => $strengths,
            'development_areas'    => $devAreas,
            'role_fit_suggestions' => $roleFit,
        ];
    }

    /**
     * Convert behavioral v2 dimension scores into candidate-friendly feedback.
     *
     * @param  array<string, int|float>  $dimensionScores  key => 0-100
     * @param  float|null                $confidence        internal confidence (not exposed)
     */
    public function fromDimensionScores(
        array $dimensionScores,
        ?float $confidence = null,
    ): array {
        arsort($dimensionScores);

        $sorted = array_keys($dimensionScores);

        // Strengths: top 3 with score >= 60
        $strengths = [];
        foreach ($sorted as $key) {
            if ($dimensionScores[$key] >= 60 && count($strengths) < 3) {
                $strengths[] = self::DIMENSION_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
            }
        }

        // Development areas: bottom 3 (< 60, or lowest overall)
        $reversed = array_reverse($sorted);
        $devAreas = [];
        foreach ($reversed as $key) {
            if ($dimensionScores[$key] < 60 && count($devAreas) < 3) {
                $devAreas[] = self::DIMENSION_DEV_LABELS[$key] ?? 'Continue developing ' . str_replace('_', ' ', $key);
            }
        }
        if (count($devAreas) < 3) {
            foreach ($reversed as $key) {
                $label = self::DIMENSION_DEV_LABELS[$key] ?? 'Continue developing ' . str_replace('_', ' ', $key);
                if (!in_array($label, $devAreas) && count($devAreas) < 3) {
                    $devAreas[] = $label;
                }
            }
        }

        $roleFit = $this->mapDimensionRoleFit($dimensionScores);

        return [
            'strengths'            => $strengths,
            'development_areas'    => $devAreas,
            'role_fit_suggestions' => $roleFit,
        ];
    }

    /**
     * Convert English assessment score into candidate-friendly feedback.
     *
     * @param  float   $overallScore    0-100
     * @param  string  $estimatedLevel  e.g. A1, A2, B1, B2, C1, C2
     */
    public function fromEnglishScore(float $overallScore, string $estimatedLevel): array
    {
        $level = strtoupper(trim($estimatedLevel));
        $strengths = [];
        $devAreas = [];

        // Build strengths and development areas based on proficiency level
        if (in_array($level, ['C2', 'C1'])) {
            $strengths[] = 'Strong reading comprehension';
            $strengths[] = 'Confident spoken English';
            $strengths[] = 'Professional written communication';
            $devAreas[] = 'Maintain language skills through regular practice';
        } elseif ($level === 'B2') {
            $strengths[] = 'Strong reading comprehension';
            $strengths[] = 'Good conversational English';
            $devAreas[] = 'Refine written communication for professional contexts';
            $devAreas[] = 'Continue practicing spoken English in technical settings';
        } elseif ($level === 'B1') {
            $strengths[] = 'Functional reading comprehension';
            $strengths[] = 'Ability to communicate in everyday situations';
            $devAreas[] = 'Continue practicing spoken English';
            $devAreas[] = 'Expand professional vocabulary';
            $devAreas[] = 'Practice writing in work-related scenarios';
        } elseif ($level === 'A2') {
            $strengths[] = 'Basic communication ability';
            $devAreas[] = 'Continue practicing spoken English';
            $devAreas[] = 'Build reading comprehension with work-related materials';
            $devAreas[] = 'Expand everyday vocabulary';
        } else {
            // A1 or below
            $strengths[] = 'Willingness to engage in English communication';
            $devAreas[] = 'Continue practicing spoken English';
            $devAreas[] = 'Build foundational vocabulary';
            $devAreas[] = 'Practice basic reading comprehension';
        }

        // Role fit based on English level
        $roleFit = [];
        if (in_array($level, ['C2', 'C1', 'B2'])) {
            $roleFit[] = 'Roles requiring regular English communication';
        }
        if (in_array($level, ['C2', 'C1'])) {
            $roleFit[] = 'International crew coordination';
        }

        return [
            'strengths'            => array_slice($strengths, 0, 3),
            'development_areas'    => array_slice($devAreas, 0, 3),
            'role_fit_suggestions' => array_slice($roleFit, 0, 2),
        ];
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────

    /**
     * Map high-scoring competencies to maritime role suggestions (max 2).
     */
    private function mapCompetencyRoleFit(array $scores): array
    {
        $suggestions = [];

        $high = fn(string $key) => ($scores[$key] ?? 0) >= 70;

        if ($high('accountability') && $high('communication')) {
            $suggestions[] = 'Bridge team roles';
        }
        if ($high('stress_resilience') && $high('role_competence')) {
            $suggestions[] = 'Senior officer positions';
        }
        if ($high('teamwork') && $high('accountability')) {
            $suggestions[] = 'Engine room operations';
        }
        if ($high('adaptability')) {
            $suggestions[] = 'Multi-vessel deployment';
        }

        return array_slice(array_unique($suggestions), 0, 2);
    }

    /**
     * Map high-scoring dimensions to maritime role suggestions (max 2).
     */
    private function mapDimensionRoleFit(array $scores): array
    {
        $suggestions = [];

        $high = fn(string $key) => ($scores[$key] ?? 0) >= 70;

        if ($high('leadership') && $high('decision_under_pressure')) {
            $suggestions[] = 'Senior officer positions';
        }
        if ($high('teamwork') && $high('communication')) {
            $suggestions[] = 'Bridge team roles';
        }
        if ($high('discipline') && $high('responsibility')) {
            $suggestions[] = 'Engine room operations';
        }
        if ($high('adaptability')) {
            $suggestions[] = 'Multi-vessel deployment';
        }

        return array_slice(array_unique($suggestions), 0, 2);
    }
}
