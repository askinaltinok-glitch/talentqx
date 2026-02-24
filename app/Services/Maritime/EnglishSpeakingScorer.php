<?php

namespace App\Services\Maritime;

use App\Models\LanguageAssessment;
use App\Services\AI\OpenAIProvider;
use Illuminate\Support\Facades\Log;

/**
 * Scores English speaking (voice) responses from the English Gate block.
 *
 * Input:  4 rubric scores per prompt (fluency, clarity, accuracy, safety_vocabulary)
 *         each 0-5. Up to 3 prompts × 4 criteria = max 60 points.
 *
 * Output: CEFR level (A2/B1/B2/C1), total score 0-20 (best of 3 prompts),
 *         per-prompt breakdown, pass/fail against role minimum.
 *
 * Scoring rules from ENGLISH_GATE_v1.json:
 *   A2 = 4-8, B1 = 9-12, B2 = 13-16, C1 = 17-20 (per prompt)
 *
 * The final estimated level uses the BEST prompt score (candidate's peak
 * performance), not the average. This gives candidates the benefit of
 * the doubt — one strong response is enough to prove ability.
 */
class EnglishSpeakingScorer
{
    /**
     * CEFR mapping: min_total → max_total per level.
     * Loaded from config/ENGLISH_GATE_v1.json at runtime.
     */
    private const DEFAULT_CEFR_MAP = [
        'A2' => ['min' => 4,  'max' => 8],
        'B1' => ['min' => 9,  'max' => 12],
        'B2' => ['min' => 13, 'max' => 16],
        'C1' => ['min' => 17, 'max' => 20],
    ];

    private const CEFR_ORDER = ['A1' => 0, 'A2' => 1, 'B1' => 2, 'B2' => 3, 'C1' => 4, 'C2' => 5];

    private const CRITERIA = ['fluency', 'clarity', 'accuracy', 'safety_vocabulary'];

    private QuestionBankAssembler $assembler;
    private OpenAIProvider $llm;

    public function __construct(QuestionBankAssembler $assembler, OpenAIProvider $llm)
    {
        $this->assembler = $assembler;
        $this->llm = $llm;
    }

    /**
     * Send English transcript texts to GPT-4o-mini for rubric scoring.
     *
     * @param array $transcripts [['prompt_id' => 'eng-s1', 'prompt_text' => '...', 'transcript' => '...'], ...]
     * @return array [['prompt_id' => 'eng-s1', 'fluency' => 3, 'clarity' => 4, 'accuracy' => 3, 'safety_vocabulary' => 2], ...]
     */
    public function scoreTranscripts(array $transcripts): array
    {
        $systemPrompt = <<<PROMPT
You are a maritime English language assessor. You evaluate spoken English transcripts from seafarer candidates.

For each transcript, score these 4 criteria on a 0-5 scale:
- fluency: smoothness, natural flow, absence of hesitations
- clarity: pronunciation clarity, intelligibility, articulation
- accuracy: grammatical correctness, appropriate vocabulary usage
- safety_vocabulary: use of IMO standard maritime phrases, safety terminology (e.g., SMCP, emergency terms)

Scoring guide:
0 = No meaningful speech / unintelligible
1 = Very limited, mostly incomprehensible
2 = Basic, frequent errors, limited vocabulary
3 = Adequate, some errors but generally understood
4 = Good, minor errors, appropriate vocabulary
5 = Excellent, near-native fluency and accuracy

OUTPUT FORMAT (JSON):
{
  "scores": [
    {"prompt_id": "eng-s1", "fluency": 3, "clarity": 4, "accuracy": 3, "safety_vocabulary": 2, "notes": "brief note"}
  ]
}

Return ONLY valid JSON, no other text.
PROMPT;

        $userContent = "TRANSCRIPTS TO EVALUATE:\n\n";
        foreach ($transcripts as $i => $t) {
            $num = $i + 1;
            $userContent .= "--- PROMPT {$num} (ID: {$t['prompt_id']}) ---\n";
            $userContent .= "Speaking prompt: {$t['prompt_text']}\n";
            $userContent .= "Candidate transcript:\n{$t['transcript']}\n\n";
        }
        $userContent .= "Score each transcript on the 4 criteria. Return JSON.";

        try {
            $raw = $this->llm->chatJson($systemPrompt, $userContent, [
                'model' => 'gpt-4o-mini',
            ]);

            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded['scores'])) {
                Log::error('EnglishSpeakingScorer::scoreTranscripts: invalid JSON from GPT', [
                    'raw' => $raw,
                ]);
                return $this->fallbackScores($transcripts);
            }

            // Normalize: ensure all criteria present and clamped 0-5
            return array_map(function ($score) {
                return [
                    'prompt_id'         => $score['prompt_id'] ?? null,
                    'fluency'           => max(0, min(5, (int) ($score['fluency'] ?? 0))),
                    'clarity'           => max(0, min(5, (int) ($score['clarity'] ?? 0))),
                    'accuracy'          => max(0, min(5, (int) ($score['accuracy'] ?? 0))),
                    'safety_vocabulary' => max(0, min(5, (int) ($score['safety_vocabulary'] ?? 0))),
                ];
            }, $decoded['scores']);
        } catch (\Throwable $e) {
            Log::error('EnglishSpeakingScorer::scoreTranscripts: GPT call failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackScores($transcripts);
        }
    }

    /**
     * Fallback scores when GPT scoring fails — conservative mid-range.
     */
    private function fallbackScores(array $transcripts): array
    {
        return array_map(fn($t) => [
            'prompt_id'         => $t['prompt_id'] ?? null,
            'fluency'           => 2,
            'clarity'           => 2,
            'accuracy'          => 2,
            'safety_vocabulary' => 2,
        ], $transcripts);
    }

    /**
     * Score English speaking responses for a candidate.
     *
     * @param string $roleCode  DB role_key (e.g., 'captain', 'oiler')
     * @param array  $responses Array of prompt responses:
     *   [
     *     ['prompt_id' => 'eng-s1', 'fluency' => 3, 'clarity' => 4, 'accuracy' => 3, 'safety_vocabulary' => 2],
     *     ['prompt_id' => 'eng-s2', ...],
     *     ['prompt_id' => 'eng-s3', ...],
     *   ]
     *
     * @return array{
     *   estimated_level: string,
     *   best_prompt_score: int,
     *   min_level_required: string,
     *   pass: bool,
     *   prompts: array,
     *   confidence: float
     * }
     */
    public function score(string $roleCode, array $responses): array
    {
        $englishConfig = $this->loadEnglishConfig($roleCode);
        $cefrMap = $englishConfig['scoring']['cefr_mapping'] ?? self::DEFAULT_CEFR_MAP;
        $minLevel = $englishConfig['min_level'];

        $promptResults = [];
        $bestScore = 0;

        foreach ($responses as $response) {
            $promptScore = $this->scorePrompt($response);
            $promptLevel = $this->mapToCefr($promptScore, $cefrMap);

            $promptResults[] = [
                'prompt_id'  => $response['prompt_id'] ?? null,
                'scores'     => array_intersect_key($response, array_flip(self::CRITERIA)),
                'total'      => $promptScore,
                'level'      => $promptLevel,
            ];

            if ($promptScore > $bestScore) {
                $bestScore = $promptScore;
            }
        }

        $estimatedLevel = $this->mapToCefr($bestScore, $cefrMap);
        $pass = $this->meetsMinimum($estimatedLevel, $minLevel);

        // Confidence: based on consistency across prompts
        $confidence = $this->computeConfidence($promptResults);

        return [
            'estimated_level'    => $estimatedLevel,
            'best_prompt_score'  => $bestScore,
            'min_level_required' => $minLevel,
            'pass'               => $pass,
            'prompts'            => $promptResults,
            'confidence'         => $confidence,
        ];
    }

    /**
     * Score a candidate's speaking and update their LanguageAssessment record.
     * Integrates with the existing language assessment pipeline.
     *
     * @return array The scoring result
     */
    public function scoreAndStore(string $candidateId, string $roleCode, array $responses): array
    {
        $result = $this->score($roleCode, $responses);

        // Store in language_assessments.interview_score + interview_evidence
        $assessment = LanguageAssessment::where('candidate_id', $candidateId)->first();

        if ($assessment) {
            // Map 0-20 → 0-100 for the interview_score field
            $normalizedScore = (int) round(($result['best_prompt_score'] / 20) * 100);

            $assessment->update([
                'interview_score'    => $normalizedScore,
                'interview_evidence' => [
                    'source'             => 'english_speaking_gate',
                    'version'            => '1.0',
                    'role_code'          => $roleCode,
                    'estimated_level'    => $result['estimated_level'],
                    'min_level_required' => $result['min_level_required'],
                    'pass'               => $result['pass'],
                    'best_prompt_score'  => $result['best_prompt_score'],
                    'confidence'         => $result['confidence'],
                    'prompts'            => $result['prompts'],
                ],
            ]);
        }

        return $result;
    }

    /**
     * Score a single prompt response: sum of 4 criteria (each 0-5), max 20.
     */
    private function scorePrompt(array $response): int
    {
        $total = 0;

        foreach (self::CRITERIA as $criterion) {
            $score = (int) ($response[$criterion] ?? 0);
            $total += max(0, min(5, $score)); // clamp 0-5
        }

        return $total;
    }

    /**
     * Map a total score (0-20) to a CEFR level.
     */
    private function mapToCefr(int $score, array $cefrMap): string
    {
        // Walk from highest to lowest
        foreach (array_reverse($cefrMap, true) as $level => $range) {
            $min = $range['min_total'] ?? $range['min'] ?? 0;
            if ($score >= $min) {
                return $level;
            }
        }

        return 'A1'; // Below A2 minimum
    }

    /**
     * Check if estimated level meets or exceeds the role minimum.
     */
    private function meetsMinimum(string $estimated, string $required): bool
    {
        return (self::CEFR_ORDER[$estimated] ?? 0) >= (self::CEFR_ORDER[$required] ?? 0);
    }

    /**
     * Compute confidence based on prompt score consistency.
     * High consistency across all 3 prompts = high confidence.
     */
    private function computeConfidence(array $promptResults): float
    {
        if (count($promptResults) < 2) {
            return 0.60; // Single prompt = lower confidence
        }

        $scores = array_column($promptResults, 'total');
        $mean = array_sum($scores) / count($scores);
        $variance = array_sum(array_map(fn($s) => ($s - $mean) ** 2, $scores)) / count($scores);
        $stdDev = sqrt($variance);

        // Max std dev for 0-20 range is ~10. Normalize to 0-1 penalty.
        $consistencyPenalty = min($stdDev / 10, 0.40);

        $base = 0.85;
        $confidence = $base - $consistencyPenalty;

        // Penalty for very low scores (all below 4 = A1 territory)
        if ($mean < 4) {
            $confidence -= 0.10;
        }

        return round(max(0.30, min(1.0, $confidence)), 2);
    }

    /**
     * Load English gate config for a role via the assembler.
     */
    private function loadEnglishConfig(string $roleCode): array
    {
        $bank = $this->assembler->forRole($roleCode, 'en');
        return $bank['blocks']['english_gate'];
    }
}
