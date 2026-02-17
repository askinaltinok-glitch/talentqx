<?php

namespace App\Jobs;

use App\Models\InterviewSession;
use App\Models\InterviewSessionAnalysis;
use App\Services\Copilot\GuardrailResult;
use App\Services\Interview\ContextScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeInterviewSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $sessionId
    ) {}

    public function handle(): void
    {
        $session = InterviewSession::with(['answers'])->findOrFail($this->sessionId);

        if ($session->analysis) {
            Log::info("Session {$this->sessionId} already analyzed, skipping.");
            return;
        }

        try {
            // Prepare answers for analysis
            $answersData = $session->answers->map(fn($a) => [
                'question_key' => $a->question_key,
                'answer_text' => $a->raw_text ?? $a->processed_text ?? '',
            ])->toArray();

            // P0: Check for minimum valid answers before AI analysis
            $validAnswerCount = $this->countValidAnswers($answersData);

            if ($validAnswerCount < GuardrailResult::MIN_ANSWERED_QUESTIONS) {
                Log::warning("Insufficient evidence for session {$this->sessionId}", [
                    'valid_answers' => $validAnswerCount,
                    'required' => GuardrailResult::MIN_ANSWERED_QUESTIONS,
                ]);

                // Save analysis with insufficient_evidence status (no AI call)
                InterviewSessionAnalysis::updateOrCreate(
                    ['session_id' => $session->id],
                    [
                        'overall_score' => null,
                        'dimension_scores' => [],
                        'question_analyses' => [],
                        'behavior_analysis' => [],
                        'risk_flags' => [],
                        'summary' => null,
                        'recommendations' => [],
                        'raw_response' => [
                            'status' => 'insufficient_evidence',
                            'blocked_reason_code' => GuardrailResult::STATUS_INSUFFICIENT_EVIDENCE,
                            'valid_answer_count' => $validAnswerCount,
                            'required_answer_count' => GuardrailResult::MIN_ANSWERED_QUESTIONS,
                        ],
                        'model_version' => null,
                        'analyzed_at' => now(),
                    ]
                );

                Log::info("Session {$this->sessionId} marked as insufficient_evidence");
                return;
            }

            // Call OpenAI for analysis
            $analysisResult = $this->callOpenAI($session, $answersData);

            // Calculate context scores if applicable
            $contextScores = null;
            if ($session->context_key) {
                $scoringService = app(ContextScoringService::class);
                // We'll calculate this after saving the analysis
            }

            // Save analysis (updateOrCreate for idempotency)
            $analysis = InterviewSessionAnalysis::updateOrCreate(
                ['session_id' => $session->id],
                [
                    'overall_score' => $analysisResult['overall_score'] ?? 50,
                    'dimension_scores' => $analysisResult['dimension_scores'] ?? [],
                    'question_analyses' => $analysisResult['question_analyses'] ?? [],
                    'behavior_analysis' => $analysisResult['behavior_analysis'] ?? [],
                    'risk_flags' => $analysisResult['risk_flags'] ?? [],
                    'summary' => $analysisResult['summary'] ?? null,
                    'recommendations' => $analysisResult['recommendations'] ?? [],
                    'raw_response' => $analysisResult,
                    'model_version' => 'gpt-4o',
                    'analyzed_at' => now(),
                ]
            );

            Log::info("Analysis completed for session {$this->sessionId}", [
                'overall_score' => $analysis->overall_score,
            ]);

        } catch (\Exception $e) {
            Log::error("Analysis failed for session {$this->sessionId}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function callOpenAI(InterviewSession $session, array $answers): array
    {
        $prompt = $this->buildPrompt($session, $answers);

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        return json_decode($content, true) ?? [];
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert HR analyst evaluating interview responses. Analyze the candidate's answers and provide a structured JSON assessment.

Your response MUST be valid JSON with this structure:
{
  "overall_score": <0-100>,
  "dimension_scores": {
    "communication": {"score": <0-100>, "notes": "..."},
    "integrity": {"score": <0-100>, "notes": "..."},
    "problem_solving": {"score": <0-100>, "notes": "..."},
    "stress_tolerance": {"score": <0-100>, "notes": "..."},
    "teamwork": {"score": <0-100>, "notes": "..."},
    "customer_focus": {"score": <0-100>, "notes": "..."},
    "adaptability": {"score": <0-100>, "notes": "..."}
  },
  "question_analyses": [
    {
      "question_id": "...",
      "score": <0-5>,
      "max_score": 5,
      "analysis": "...",
      "positive_points": ["..."],
      "concerns": ["..."]
    }
  ],
  "behavior_analysis": {
    "consistency_score": <0-100>,
    "red_flags": [],
    "green_flags": []
  },
  "risk_flags": [],
  "summary": "...",
  "recommendations": ["..."]
}

Be objective, fair, and base scores on evidence from answers only.
PROMPT;
    }

    private function buildPrompt(InterviewSession $session, array $answers): string
    {
        $roleLabel = ucwords(str_replace('_', ' ', $session->role_key));
        $contextLabel = $session->context_key ? ucwords(str_replace('_', ' ', $session->context_key)) : 'General';

        $prompt = "Analyze this interview for a {$roleLabel} position ({$contextLabel} context).\n\n";
        $prompt .= "CANDIDATE ANSWERS:\n";

        foreach ($answers as $answer) {
            $prompt .= "\nQ ({$answer['question_key']}): {$answer['answer_text']}\n";
        }

        return $prompt;
    }

    /**
     * Count answers that meet minimum length requirement.
     *
     * @param array $answers
     * @return int
     */
    private function countValidAnswers(array $answers): int
    {
        $validCount = 0;

        foreach ($answers as $answer) {
            $text = trim($answer['answer_text'] ?? '');
            if (mb_strlen($text) >= GuardrailResult::MIN_ANSWER_CHARS) {
                $validCount++;
            }
        }

        return $validCount;
    }
}
