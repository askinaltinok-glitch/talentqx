<?php

namespace App\Services\Behavioral;

use App\Models\CandidateQuestionAttempt;
use App\Models\CandidateScoringVector;
use Illuminate\Support\Facades\Log;

/**
 * Behavioral Scoring Service v2 — structured 12-question scoring.
 *
 * Scores each answer against its dimension rubric, computes:
 * - dimension_scores (7 dimensions, 0-100)
 * - confidence (0.00–1.00)
 * - red_flags (manipulation/quality issues)
 *
 * Then recomputes the CandidateScoringVector behavioral component.
 */
class BehavioralScoringServiceV2
{
    private const DIMENSIONS = [
        'responsibility',
        'teamwork',
        'decision_under_pressure',
        'communication',
        'discipline',
        'leadership',
        'adaptability',
    ];

    /**
     * Score a completed attempt and recompute vector.
     */
    public function score(CandidateQuestionAttempt $attempt): array
    {
        $questions = $attempt->questionSet->questions_json ?? [];
        $answers = $attempt->answers_json ?? [];
        $answerMap = collect($answers)->keyBy('question_id');

        // Score each question
        $dimensionScores = [];
        $redFlags = [];

        foreach ($questions as $question) {
            $qId = $question['id'];
            $dimension = $question['dimension'];
            $difficulty = $question['difficulty'] ?? 1;
            $answer = $answerMap->get($qId);
            $text = trim($answer['answer_text'] ?? '');
            $length = mb_strlen($text);

            // Base score: length-based heuristic (0-5 scale)
            $rawScore = $this->scoreAnswer($text, $difficulty);

            if (!isset($dimensionScores[$dimension])) {
                $dimensionScores[$dimension] = ['scores' => [], 'weights' => []];
            }

            // Weight by difficulty
            $weight = match ($difficulty) {
                1 => 0.8,
                2 => 1.0,
                3 => 1.2,
                default => 1.0,
            };

            $dimensionScores[$dimension]['scores'][] = $rawScore;
            $dimensionScores[$dimension]['weights'][] = $weight;

            // Red flag: very short answer
            if ($length > 0 && $length < 20) {
                $redFlags[] = ['type' => 'very_short', 'question_id' => $qId, 'length' => $length];
            }

            // Red flag: skipped question
            if ($length === 0) {
                $redFlags[] = ['type' => 'skipped', 'question_id' => $qId];
            }
        }

        // Detect repetition across answers
        $redFlags = array_merge($redFlags, $this->detectRepetition($answers));

        // Compute final dimension scores (0-100)
        $finalDimensions = [];
        foreach (self::DIMENSIONS as $dim) {
            if (isset($dimensionScores[$dim]) && !empty($dimensionScores[$dim]['scores'])) {
                $scores = $dimensionScores[$dim]['scores'];
                $weights = $dimensionScores[$dim]['weights'];
                $weightedSum = 0;
                $weightTotal = 0;
                foreach ($scores as $i => $s) {
                    $weightedSum += $s * $weights[$i];
                    $weightTotal += $weights[$i];
                }
                $avgScore = $weightTotal > 0 ? $weightedSum / $weightTotal : 0;
                // Normalize 0-5 → 0-100
                $normalized = (int) round(($avgScore / 5) * 100);
                $finalDimensions[$dim] = max(0, min(100, $normalized));
            } else {
                $finalDimensions[$dim] = null; // no questions for this dimension
            }
        }

        // Confidence
        $confidence = $this->computeConfidence($answers, $redFlags, count($questions));

        $scorePayload = [
            'dimension_scores' => $finalDimensions,
            'confidence' => $confidence,
            'red_flags' => $redFlags,
        ];

        // Write score to attempt
        $attempt->update([
            'score_json' => $scorePayload,
            'completed_at' => $attempt->completed_at ?? now(),
        ]);

        // Recompute candidate vector
        $this->recomputeVector($attempt->candidate_id, $finalDimensions, $confidence);

        return $scorePayload;
    }

    /**
     * Score a single answer text (0-5 scale).
     */
    private function scoreAnswer(string $text, int $difficulty): float
    {
        $length = mb_strlen($text);

        if ($length === 0) {
            return 0;
        }

        // Base: length contribution
        $lengthScore = match (true) {
            $length >= 200 => 3.0,
            $length >= 100 => 2.5,
            $length >= 50 => 2.0,
            $length >= 20 => 1.5,
            default => 0.5,
        };

        // Sentence count bonus (indicates structured thinking)
        $sentenceCount = preg_match_all('/[.!?]+/', $text);
        $structureBonus = min(1.0, $sentenceCount * 0.25);

        // Total capped at 5
        $total = min(5.0, $lengthScore + $structureBonus);

        // Difficulty penalty: harder questions need more substance
        if ($difficulty >= 3 && $length < 80) {
            $total *= 0.7;
        }

        return round($total, 2);
    }

    /**
     * Detect copy-paste / repetition across answers.
     */
    private function detectRepetition(array $answers): array
    {
        $flags = [];
        $texts = [];

        foreach ($answers as $a) {
            $t = trim($a['answer_text'] ?? '');
            if (mb_strlen($t) > 50) {
                $texts[$a['question_id']] = $t;
            }
        }

        $ids = array_keys($texts);
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $a = $texts[$ids[$i]];
                $b = $texts[$ids[$j]];
                $shorter = mb_strlen($a) < mb_strlen($b) ? $a : $b;
                $longer = mb_strlen($a) >= mb_strlen($b) ? $a : $b;

                if (mb_strlen($shorter) > 60) {
                    $chunk = mb_substr($shorter, 0, (int) (mb_strlen($shorter) * 0.6));
                    if (mb_stripos($longer, $chunk) !== false) {
                        $flags[] = [
                            'type' => 'repetition',
                            'questions' => [$ids[$i], $ids[$j]],
                        ];
                    }
                }
            }
        }

        return $flags;
    }

    /**
     * Compute confidence 0.00-1.00.
     */
    private function computeConfidence(array $answers, array $redFlags, int $totalQuestions): float
    {
        // Base: structured format gives 0.50
        $base = 0.50;

        // Answer count contribution
        $answered = count(array_filter($answers, fn($a) => mb_strlen(trim($a['answer_text'] ?? '')) >= 10));
        $answerConf = min(0.42, ($answered / max($totalQuestions, 1)) * 0.42);

        // Flag penalty
        $flagPenalty = min(0.20, count($redFlags) * 0.04);

        return round(max(0.00, min(1.00, $base + $answerConf - $flagPenalty)), 2);
    }

    /**
     * Recompute CandidateScoringVector behavioral component.
     */
    private function recomputeVector(string $candidateId, array $dimensionScores, float $confidence): void
    {
        try {
            $validScores = array_filter($dimensionScores, fn($s) => $s !== null);

            if (empty($validScores)) {
                return;
            }

            $avgBehavioral = array_sum($validScores) / count($validScores);
            $normalizedBehavioral = round($avgBehavioral / 100, 2); // 0-1 scale

            CandidateScoringVector::updateOrCreate(
                ['candidate_id' => $candidateId],
                [
                    'behavioral_score' => $normalizedBehavioral,
                    'vector_json' => array_merge(
                        CandidateScoringVector::where('candidate_id', $candidateId)->value('vector_json') ?? [],
                        [
                            'behavioral_v2' => [
                                'dimensions' => $dimensionScores,
                                'confidence' => $confidence,
                                'computed_at' => now()->toIso8601String(),
                            ],
                        ]
                    ),
                    'computed_at' => now(),
                    'version' => 'v2',
                ]
            );
        } catch (\Throwable $e) {
            Log::channel('single')->warning('BehavioralScoringServiceV2::recomputeVector failed', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
