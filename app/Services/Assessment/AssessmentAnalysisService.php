<?php

namespace App\Services\Assessment;

use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\PromptVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssessmentAnalysisService
{
    private string $apiKey;
    private string $model;
    private string $fallbackModel;
    private int $timeout;
    private float $maxCostPerSession;
    private JsonSchemaValidator $validator;
    private AssessmentAntiCheatService $antiCheatService;

    // Pricing per 1K tokens (approximate)
    private const MODEL_PRICING = [
        'gpt-4-turbo-preview' => ['input' => 0.01, 'output' => 0.03],
        'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
        'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
        'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
    ];

    public function __construct(
        ?JsonSchemaValidator $validator = null,
        ?AssessmentAntiCheatService $antiCheatService = null
    ) {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4-turbo-preview');
        $this->fallbackModel = config('services.openai.fallback_model', 'gpt-4o-mini');
        $this->timeout = config('services.openai.timeout', 120);
        $this->maxCostPerSession = config('services.openai.max_cost_per_session', 0.50);
        $this->validator = $validator ?? new JsonSchemaValidator();
        $this->antiCheatService = $antiCheatService ?? new AssessmentAntiCheatService();
    }

    public function analyze(AssessmentSession $session): AssessmentResult
    {
        $session->load(['employee', 'template']);

        $template = $session->template;
        $responses = $session->responses ?? [];

        // Get active prompt version
        $promptVersion = PromptVersion::getActive('assessment_analysis', $template->role_category);

        $analysisResult = null;
        $validationErrors = [];
        $retryCount = 0;
        $usedModel = $this->model;
        $costLimited = false;
        $totalInputTokens = 0;
        $totalOutputTokens = 0;
        $totalCost = 0;

        // Try up to 3 times (initial + 2 retries)
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                // Check cost limit before making request
                $estimatedCost = $this->estimateCost($session, $usedModel);
                if ($totalCost + $estimatedCost > $this->maxCostPerSession) {
                    $usedModel = $this->fallbackModel;
                    $costLimited = true;
                    Log::info('Switching to fallback model due to cost limit', [
                        'session_id' => $session->id,
                        'current_cost' => $totalCost,
                        'estimated_cost' => $estimatedCost,
                    ]);
                }

                $response = $this->callAI($session, $template, $responses, $usedModel, $promptVersion);

                $analysisResult = $response['content'];
                $totalInputTokens += $response['input_tokens'];
                $totalOutputTokens += $response['output_tokens'];
                $totalCost += $response['cost'];

                // Attempt to fix common issues
                $analysisResult = $this->validator->attemptFix($analysisResult);

                // Validate the response
                if ($this->validator->validate($analysisResult)) {
                    break; // Valid response, exit retry loop
                }

                $validationErrors = $this->validator->getErrors();
                $retryCount = $attempt + 1;

                Log::warning('Assessment analysis validation failed, retrying', [
                    'session_id' => $session->id,
                    'attempt' => $attempt + 1,
                    'errors' => $validationErrors,
                ]);

                // Switch to fallback model on second retry
                if ($attempt === 1) {
                    $usedModel = $this->fallbackModel;
                }

            } catch (\Exception $e) {
                Log::error('Assessment analysis attempt failed', [
                    'session_id' => $session->id,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                ]);

                $retryCount = $attempt + 1;

                if ($attempt === 2) {
                    // All retries failed - create failed result
                    return $this->saveFailedResult($session, [
                        'error' => $e->getMessage(),
                        'validation_errors' => $validationErrors,
                    ], $usedModel, $totalInputTokens, $totalOutputTokens, $totalCost, $promptVersion);
                }

                // Switch to fallback model on second retry
                if ($attempt === 1) {
                    $usedModel = $this->fallbackModel;
                }
            }
        }

        // If all retries failed validation
        if ($analysisResult === null || !$this->validator->validate($analysisResult)) {
            return $this->saveFailedResult($session, [
                'validation_errors' => $validationErrors,
                'last_response' => $analysisResult,
            ], $usedModel, $totalInputTokens, $totalOutputTokens, $totalCost, $promptVersion);
        }

        // Run anti-cheat analysis
        $antiCheatResult = $this->antiCheatService->analyze($session);

        // Update session cost
        $session->update(['total_cost_usd' => $totalCost]);

        return $this->saveResult(
            $session,
            $analysisResult,
            $template,
            $usedModel,
            $totalInputTokens,
            $totalOutputTokens,
            $totalCost,
            $costLimited,
            $retryCount,
            $promptVersion,
            $antiCheatResult
        );
    }

    private function callAI(
        AssessmentSession $session,
        $template,
        array $responses,
        string $model,
        ?PromptVersion $promptVersion
    ): array {
        $systemPrompt = $promptVersion
            ? $promptVersion->render(['template' => json_encode($template)])
            : $this->buildSystemPrompt($template);

        $userPrompt = $this->buildUserPrompt($session, $template, $responses);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 4096,
            'response_format' => ['type' => 'json_object'],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('OpenAI Assessment Analysis error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content', '');
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse OpenAI JSON response', [
                'response' => $content,
                'error' => json_last_error_msg(),
            ]);
            throw new \Exception('Invalid JSON response from OpenAI');
        }

        // Extract token usage
        $usage = $response->json('usage', []);
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        // Calculate cost
        $cost = $this->calculateCost($model, $inputTokens, $outputTokens);

        return [
            'content' => $decoded,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost' => $cost,
        ];
    }

    private function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $pricing = self::MODEL_PRICING[$model] ?? self::MODEL_PRICING['gpt-4-turbo-preview'];

        $inputCost = ($inputTokens / 1000) * $pricing['input'];
        $outputCost = ($outputTokens / 1000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }

    private function estimateCost(AssessmentSession $session, string $model): float
    {
        // Rough estimate based on typical token usage
        $estimatedInputTokens = 2000; // System + user prompt
        $estimatedOutputTokens = 2000; // JSON response

        return $this->calculateCost($model, $estimatedInputTokens, $estimatedOutputTokens);
    }

    private function buildSystemPrompt($template): string
    {
        $competenciesJson = json_encode($template->competencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $redFlagsJson = json_encode($template->red_flags, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $scoringJson = json_encode($template->scoring_config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Sen deneyimli bir HR analisti ve davranis bilimleri uzmanisin. Mevcut calisanlarin yetkinlik degerlendirmesini yapiyorsun.

ROL: {$template->name}
ROL KATEGORISI: {$template->role_category}

YETKINLIK SETI VE AGIRLIKLARI:
{$competenciesJson}

KIRMIZI BAYRAKLAR (Risk Gostergeleri):
{$redFlagsJson}

PUANLAMA YAPISI:
{$scoringJson}

ANALIZ KURALLARI:
1. Her yetkinligi 0-100 arasi puanla (agirliga gore)
2. Senaryo sorularinda verimsiz/yavas/hatali cevaplari tespit et
3. Davranissal sorularda tutarsizliklari ve kirmizi bayraklari tespit et
4. Bilgi sorularinda hatali cevaplari degerlendir
5. Risk seviyesini belirle: low (dusuk), medium (orta), high (yuksek), critical (kritik)
6. Seviye etiketi belirle: basarisiz (0-39), gelisime_acik (40-54), yeterli (55-69), iyi (70-84), mukemmel (85-100)
7. Terfi uygunlugunu ve hazirligini degerlendir
8. Gelisim plani olustur

CIKTI FORMATI (JSON):
{
    "overall_score": 72.5,
    "competency_scores": {
        "yetkinlik_kodu": {
            "score": 75,
            "weight": 0.20,
            "weighted_score": 15,
            "feedback": "Detayli geri bildirim",
            "evidence": ["Kanit 1", "Kanit 2"]
        }
    },
    "risk_flags": [
        {
            "code": "bayrak_kodu",
            "label": "Bayrak aciklamasi",
            "severity": "low|medium|high|critical",
            "detected_in_question": 3,
            "evidence": "Tespit edilen durum"
        }
    ],
    "risk_level": "low|medium|high|critical",
    "level_numeric": 4,
    "level_label": "iyi",
    "strengths": [
        {
            "competency": "yetkinlik_kodu",
            "description": "Guclu yan aciklamasi"
        }
    ],
    "improvement_areas": [
        {
            "competency": "yetkinlik_kodu",
            "description": "Gelistirilecek alan aciklamasi",
            "priority": "high|medium|low"
        }
    ],
    "development_plan": [
        {
            "area": "Gelisim alani",
            "actions": ["Aksiyon 1", "Aksiyon 2"],
            "priority": "high|medium|low",
            "timeline": "1-3 ay"
        }
    ],
    "promotion_suitable": true,
    "promotion_readiness": "not_ready|developing|ready|highly_ready",
    "promotion_notes": "Terfi degerlendirmesi notlari",
    "question_analyses": [
        {
            "question_order": 1,
            "competency_code": "yetkinlik_kodu",
            "score": 4,
            "max_score": 5,
            "analysis": "Detayli analiz",
            "positive_points": ["Olumlu nokta"],
            "negative_points": ["Olumsuz nokta"]
        }
    ]
}

Sadece JSON formatinda cevap ver, baska aciklama ekleme.
PROMPT;
    }

    private function buildUserPrompt(AssessmentSession $session, $template, array $responses): string
    {
        $employee = $session->employee;
        $questions = $template->questions;

        $prompt = "CALISAN BILGILERI:\n";
        $prompt .= "Ad Soyad: {$employee->full_name}\n";
        $prompt .= "Mevcut Rol: {$employee->current_role}\n";
        $prompt .= "Departman: {$employee->department}\n";
        $prompt .= "Sube: {$employee->branch}\n";
        $prompt .= "Degerlendirme Suresi: {$session->time_spent_seconds} saniye\n\n";

        $prompt .= "DEGERLENDIRME YANITLARI:\n\n";

        foreach ($questions as $index => $question) {
            $order = $index + 1;
            $response = collect($responses)->firstWhere('question_order', $order);

            $prompt .= "--- SORU {$order} ---\n";
            $prompt .= "Tur: {$question['type']}\n";
            $competencyCode = $question['competency_code'] ?? ($question['competency_codes'][0] ?? 'N/A');
            $prompt .= "Yetkinlik: {$competencyCode}\n";
            $prompt .= "Soru: {$question['text']}\n";

            if (isset($question['options']) && is_array($question['options'])) {
                $prompt .= "Secenekler:\n";
                foreach ($question['options'] as $optIndex => $option) {
                    $letter = chr(65 + $optIndex);
                    $prompt .= "  {$letter}) {$option['text']}\n";
                }
            }

            if ($response) {
                $prompt .= "Verilen Cevap: {$response['answer']}\n";
                $prompt .= "Cevap Suresi: {$response['time_spent']} saniye\n";
            } else {
                $prompt .= "Verilen Cevap: [CEVAPLANMADI]\n";
            }

            $prompt .= "\n";
        }

        $prompt .= "Yukaridaki degerlendirme yanitlarini analiz et ve JSON formatinda detayli rapor olustur.";

        return $prompt;
    }

    private function saveResult(
        AssessmentSession $session,
        array $result,
        $template,
        string $model,
        int $inputTokens,
        int $outputTokens,
        float $cost,
        bool $costLimited,
        int $retryCount,
        ?PromptVersion $promptVersion,
        array $antiCheatResult
    ): AssessmentResult {
        return DB::transaction(function () use (
            $session, $result, $template, $model, $inputTokens, $outputTokens,
            $cost, $costLimited, $retryCount, $promptVersion, $antiCheatResult
        ) {
            // Calculate overall score if not provided
            $overallScore = $result['overall_score'] ?? $this->calculateOverallScore(
                $result['competency_scores'] ?? [],
                $template->competencies
            );

            // Determine level from score if not provided
            $levelNumeric = $result['level_numeric'] ?? $this->determineLevelNumeric($overallScore);
            $levelLabel = $result['level_label'] ?? $this->determineLevelLabel($levelNumeric);

            // Determine risk level if not provided
            $riskLevel = $result['risk_level'] ?? $this->determineRiskLevel($result['risk_flags'] ?? []);

            return AssessmentResult::create([
                'session_id' => $session->id,
                'status' => AssessmentResult::STATUS_COMPLETED,
                'ai_model' => $model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost_usd' => $cost,
                'cost_limited' => $costLimited,
                'used_prompt_version_id' => $promptVersion?->id,
                'analyzed_at' => now(),
                'overall_score' => $overallScore,
                'competency_scores' => $result['competency_scores'] ?? [],
                'risk_flags' => $result['risk_flags'] ?? [],
                'risk_level' => $riskLevel,
                'level_label' => $levelLabel,
                'level_numeric' => $levelNumeric,
                'development_plan' => $result['development_plan'] ?? [],
                'strengths' => $result['strengths'] ?? [],
                'improvement_areas' => $result['improvement_areas'] ?? [],
                'promotion_suitable' => $result['promotion_suitable'] ?? false,
                'promotion_readiness' => $result['promotion_readiness'] ?? 'not_ready',
                'promotion_notes' => $result['promotion_notes'] ?? null,
                'cheating_risk_score' => $antiCheatResult['cheating_risk_score'] ?? null,
                'cheating_level' => $antiCheatResult['cheating_level'] ?? null,
                'cheating_flags' => $antiCheatResult['cheating_flags'] ?? [],
                'raw_ai_response' => $result,
                'validation_errors' => null,
                'retry_count' => $retryCount,
                'question_analyses' => $result['question_analyses'] ?? [],
            ]);
        });
    }

    private function saveFailedResult(
        AssessmentSession $session,
        array $errorData,
        string $model,
        int $inputTokens,
        int $outputTokens,
        float $cost,
        ?PromptVersion $promptVersion
    ): AssessmentResult {
        Log::error('Assessment analysis failed after all retries', [
            'session_id' => $session->id,
            'error_data' => $errorData,
        ]);

        return AssessmentResult::create([
            'session_id' => $session->id,
            'status' => AssessmentResult::STATUS_ANALYSIS_FAILED,
            'ai_model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_usd' => $cost,
            'cost_limited' => false,
            'used_prompt_version_id' => $promptVersion?->id,
            'analyzed_at' => now(),
            'overall_score' => 0,
            'competency_scores' => [],
            'risk_flags' => [],
            'risk_level' => 'low',
            'level_label' => 'basarisiz',
            'level_numeric' => 1,
            'development_plan' => [],
            'strengths' => [],
            'improvement_areas' => [],
            'promotion_suitable' => false,
            'promotion_readiness' => 'not_ready',
            'raw_ai_response' => $errorData['last_response'] ?? null,
            'validation_errors' => $errorData['validation_errors'] ?? [$errorData['error'] ?? 'Unknown error'],
            'retry_count' => 3,
            'question_analyses' => [],
        ]);
    }

    private function calculateOverallScore(array $competencyScores, array $competencies): float
    {
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($competencies as $competency) {
            $code = $competency['code'];
            $weight = $competency['weight'] ?? 0;

            if (isset($competencyScores[$code]['score'])) {
                $totalWeightedScore += $competencyScores[$code]['score'] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : 0;
    }

    private function determineLevelNumeric(float $score): int
    {
        return match (true) {
            $score >= 85 => 5,
            $score >= 70 => 4,
            $score >= 55 => 3,
            $score >= 40 => 2,
            default => 1,
        };
    }

    private function determineLevelLabel(int $level): string
    {
        return match ($level) {
            5 => 'mukemmel',
            4 => 'iyi',
            3 => 'yeterli',
            2 => 'gelisime_acik',
            default => 'basarisiz',
        };
    }

    private function determineRiskLevel(array $riskFlags): string
    {
        if (empty($riskFlags)) {
            return 'low';
        }

        $severities = collect($riskFlags)->pluck('severity')->toArray();

        if (in_array('critical', $severities)) {
            return 'critical';
        }

        if (in_array('high', $severities)) {
            return 'high';
        }

        if (count(array_filter($severities, fn($s) => $s === 'medium')) >= 2) {
            return 'high';
        }

        if (in_array('medium', $severities)) {
            return 'medium';
        }

        return 'low';
    }
}
