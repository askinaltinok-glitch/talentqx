<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class OpenAIProvider implements LLMProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $whisperModel;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4-turbo-preview');
        $this->whisperModel = config('services.openai.whisper_model', 'whisper-1');
        $this->timeout = config('services.openai.timeout', 120);
    }

    public function generateQuestions(array $competencies, array $questionRules, array $context = []): array
    {
        $systemPrompt = $this->buildQuestionGenerationSystemPrompt();
        $userPrompt = $this->buildQuestionGenerationUserPrompt($competencies, $questionRules, $context);

        $response = $this->chat($systemPrompt, $userPrompt, [
            'response_format' => ['type' => 'json_object'],
        ]);

        return $this->parseJsonResponse($response);
    }

    public function analyzeInterview(array $responses, array $competencies, array $redFlags): array
    {
        $systemPrompt = $this->buildAnalysisSystemPrompt($competencies, $redFlags);
        $userPrompt = $this->buildAnalysisUserPrompt($responses);

        $response = $this->chat($systemPrompt, $userPrompt, [
            'response_format' => ['type' => 'json_object'],
        ]);

        return $this->parseJsonResponse($response);
    }

    public function transcribeAudio(string $audioPath, string $language = 'tr'): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->timeout($this->timeout)->attach(
            'file',
            Storage::get($audioPath),
            basename($audioPath)
        )->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => $this->whisperModel,
            'language' => $language,
            'response_format' => 'verbose_json',
        ]);

        if (!$response->successful()) {
            Log::error('OpenAI Whisper error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Transcription failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'transcript' => $data['text'] ?? '',
            'confidence' => $this->calculateAverageConfidence($data['segments'] ?? []),
            'language' => $data['language'] ?? $language,
            'duration' => $data['duration'] ?? null,
            'segments' => $data['segments'] ?? [],
        ];
    }

    private function chat(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ], $options);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('OpenAI Chat error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    private function parseJsonResponse(string $response): array
    {
        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse OpenAI JSON response', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);
            throw new Exception('Invalid JSON response from OpenAI');
        }

        return $decoded;
    }

    private function calculateAverageConfidence(array $segments): float
    {
        if (empty($segments)) {
            return 0.0;
        }

        $totalConfidence = 0;
        $count = 0;

        foreach ($segments as $segment) {
            if (isset($segment['no_speech_prob'])) {
                $totalConfidence += (1 - $segment['no_speech_prob']);
                $count++;
            }
        }

        return $count > 0 ? round($totalConfidence / $count, 4) : 0.85;
    }

    private function buildQuestionGenerationSystemPrompt(): string
    {
        return <<<PROMPT
Sen deneyimli bir HR mulakat uzmanisin. Verilen yetkinlik seti ve kurallara gore mulakat sorulari ureteceksin.

KURALLAR:
1. Her soru tek bir yetkinligi olcmeli
2. Sorular Turkce ve anlasilir olmali
3. Senaryo sorulari gercekci is durumlarina dayanmali
4. Davranissal sorular STAR metoduna uygun olmali
5. Teknik sorular pozisyona ozgu olmali

CIKTI FORMATI (JSON):
{
    "questions": [
        {
            "question_type": "technical|behavioral|scenario|culture",
            "question_text": "Soru metni",
            "competency_code": "yetkinlik_kodu",
            "ideal_answer_points": ["Beklenen cevap maddesi 1", "Madde 2", "Madde 3"],
            "time_limit_seconds": 180
        }
    ]
}

Sadece JSON formatinda cevap ver, baska aciklama ekleme.
PROMPT;
    }

    private function buildQuestionGenerationUserPrompt(array $competencies, array $questionRules, array $context): string
    {
        $competenciesJson = json_encode($competencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $rulesJson = json_encode($questionRules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
YETKINLIK SETI:
{$competenciesJson}

SORU URETIM KURALLARI:
{$rulesJson}

PROMPT;

        if (!empty($context['position_name'])) {
            $prompt .= "\nPOZISYON: " . $context['position_name'];
        }

        if (!empty($context['sample_questions'])) {
            $samplesJson = json_encode($context['sample_questions'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $prompt .= "\n\nORNEK SORULAR (bunlara benzer uret):\n{$samplesJson}";
        }

        $prompt .= "\n\nYukaridaki yetkinliklere ve kurallara gore mulakat sorularini JSON formatinda uret.";

        return $prompt;
    }

    private function buildAnalysisSystemPrompt(array $competencies, array $redFlags): string
    {
        $competenciesJson = json_encode($competencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $redFlagsJson = json_encode($redFlags, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Sen deneyimli bir HR analisti ve davranis bilimleri uzmanisin. Mulakat cevaplarini analiz edecek ve detayli degerlendirme yapacaksin.

YETKINLIK SETI:
{$competenciesJson}

KIRMIZI BAYRAKLAR:
{$redFlagsJson}

ANALIZ KURALLARI:
1. Her yetkinligi 0-100 arasi puanla
2. Puanlama somut kanita dayali olmali
3. Kirmizi bayraklari tespit et ve alintiyla belirt
4. Kultur uyumunu is disiplini, hijyen/kalite ve vardiya/tempo acisinden degerlendir
5. Karar onerisini hire/hold/reject olarak belirt
6. Guven yuzdesi ver (ne kadar emin oldugun)

CIKTI FORMATI (JSON):
{
    "competency_scores": {
        "yetkinlik_kodu": {
            "score": 85,
            "raw_score": 4.25,
            "max_score": 5,
            "evidence": ["Kanit 1", "Kanit 2"],
            "improvement_areas": ["Gelistirilecek alan"]
        }
    },
    "overall_score": 78.5,
    "behavior_analysis": {
        "clarity_score": 80,
        "consistency_score": 85,
        "stress_tolerance": 75,
        "communication_style": "professional",
        "confidence_level": "medium-high"
    },
    "red_flag_analysis": {
        "flags_detected": true,
        "flags": [
            {
                "code": "bayrak_kodu",
                "detected_phrase": "tespit edilen cumle",
                "severity": "low|medium|high",
                "question_order": 3
            }
        ],
        "overall_risk": "low|medium|high"
    },
    "culture_fit": {
        "discipline_fit": 80,
        "hygiene_quality_fit": 90,
        "schedule_tempo_fit": 75,
        "overall_fit": 82,
        "notes": "Aciklama"
    },
    "decision_snapshot": {
        "recommendation": "hire|hold|reject",
        "confidence_percent": 78,
        "reasons": ["Neden 1", "Neden 2", "Neden 3"],
        "suggested_questions": ["Ek soru onerisi"]
    },
    "question_analyses": [
        {
            "question_order": 1,
            "score": 4,
            "competency_code": "yetkinlik_kodu",
            "analysis": "Detayli analiz",
            "positive_points": ["Olumlu"],
            "negative_points": ["Olumsuz"]
        }
    ]
}

Sadece JSON formatinda cevap ver, baska aciklama ekleme.
PROMPT;
    }

    private function buildAnalysisUserPrompt(array $responses): string
    {
        $prompt = "MULAKAT CEVAPLARI:\n\n";

        foreach ($responses as $index => $response) {
            $order = $index + 1;
            $prompt .= "--- SORU {$order} ---\n";
            $prompt .= "Soru: {$response['question_text']}\n";
            $prompt .= "Yetkinlik: {$response['competency_code']}\n";
            $prompt .= "Cevap Suresi: {$response['duration_seconds']} saniye\n";
            $prompt .= "Transkript:\n{$response['transcript']}\n\n";
        }

        $prompt .= "Yukaridaki mulakat cevaplarini analiz et ve JSON formatinda detayli rapor olustur.";

        return $prompt;
    }

    public function test(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get('https://api.openai.com/v1/models');

            return [
                'success' => $response->successful(),
                'models_available' => $response->successful(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
