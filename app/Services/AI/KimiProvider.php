<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class KimiProvider implements LLMProviderInterface
{
    private ?string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $timeout;
    private ?string $openaiApiKey; // For Whisper fallback

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?string $model = null,
        ?int $timeout = null,
        ?string $openaiApiKey = null
    ) {
        $this->apiKey = $apiKey ?? config('services.kimi.api_key');
        $this->baseUrl = $baseUrl ?? config('services.kimi.base_url', 'https://api.moonshot.ai/v1');
        $this->model = $model ?? config('services.kimi.model', 'moonshot-v1-128k');
        $this->timeout = $timeout ?? config('services.kimi.timeout', 120);
        $this->openaiApiKey = $openaiApiKey ?? config('services.openai.api_key');
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
        // Kimi/Moonshot does not have audio transcription API
        // Fall back to OpenAI Whisper for transcription
        if (empty($this->openaiApiKey)) {
            throw new Exception('Audio transcription requires OpenAI API key (Kimi does not support transcription)');
        }

        $whisperModel = config('services.openai.whisper_model', 'whisper-1');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
        ])->timeout($this->timeout)->attach(
            'file',
            Storage::get($audioPath),
            basename($audioPath)
        )->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => $whisperModel,
            'language' => $language,
            'response_format' => 'verbose_json',
        ]);

        if (!$response->successful()) {
            Log::error('OpenAI Whisper error (via Kimi provider)', [
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
        if (empty($this->apiKey)) {
            throw new Exception('Kimi API key is not configured');
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.6, // Recommended for Kimi
            'max_tokens' => 4096,
        ];

        // Kimi supports response_format for JSON mode
        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)->post($this->baseUrl . '/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('Kimi API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Kimi API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    private function parseJsonResponse(string $response): array
    {
        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse Kimi JSON response', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);
            throw new Exception('Invalid JSON response from Kimi');
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
Sen deneyimli bir İK analisti ve endüstriyel psikologsun. Mulakat cevaplarını adım adım analiz edeceksin.

## DEĞERLENDİRME YETKİNLİKLERİ:
{$competenciesJson}

## KIRMIZI BAYRAKLAR (Dikkat Edilecek Olumsuz İşaretler):
{$redFlagsJson}

## KRİTİK KIRMIZI BAYRAKLAR - OTOMATİK RED:
Aşağıdaki ifadeler tespit edilirse MUTLAKA:
- Genel puan MAX 30 olmalı
- Recommendation: "reject" olmalı
- red_flag_analysis.flags_detected: true olmalı
- overall_risk: "high" olmalı

**Şiddet/Tehdit İçeren İfadeler:**
- "kırarım", "döverim", "vururum", "öldürürüm"
- "ağzını burnunu kırarım", "kafanı kırarım"
- Herhangi bir fiziksel şiddet tehdidi

**Küfür ve Argo:**
- Küfürlü ifadeler
- Hakaret içeren sözler
- Aşırı kaba dil kullanımı

**Diğer Kritik Bayraklar:**
- Eski işverenler hakkında ağır suçlamalar
- Yasadışı aktivite imaları
- Ayrımcı veya ırkçı ifadeler
- Cinsel içerikli uygunsuz ifadeler

## ADIM ADIM ANALİZ YÖNTEMİ:

### Adım 1: Her Cevabı Ayrı Değerlendir
Her soru için şunları incele:
- Adayın cevabı soruyu tam karşılıyor mu?
- Somut örnek ve deneyim paylaşmış mı?
- STAR metodu kullanılmış mı? (Durum-Görev-Aksiyon-Sonuç)
- İdeal cevap noktalarından kaç tanesi karşılanmış?

### Adım 2: Puanlama Kriterleri (0-100)
- 90-100: Mükemmel - Tüm beklentileri aşıyor, somut örnekler, detaylı açıklamalar
- 75-89: İyi - Çoğu beklentiyi karşılıyor, bazı somut örnekler var
- 60-74: Orta - Temel beklentileri karşılıyor ama derinlik eksik
- 40-59: Zayıf - Beklentilerin altında, yüzeysel cevaplar
- 0-39: Yetersiz - Soruyu anlamamış veya alakasız cevap

### Adım 3: Kanıt Toplama
Her puan için mutlaka:
- Adayın kendi sözlerinden alıntı yap
- Hangi ifade neden olumlu/olumsuz belirt
- Spesifik örnekleri not et

### Adım 4: Genel Değerlendirme
- Yetkinlik puanlarının ağırlıklı ortalamasını hesapla
- Kırmızı bayrakları değerlendir
- Kültür uyumunu belirle
- Final karar önerisi oluştur

## KARAR KRİTERLERİ:

**OTOMATİK RED (reject):**
- Şiddet, tehdit veya küfür içeren herhangi bir ifade varsa → MUTLAKA REJECT
- "kırarım", "döverim", "vururum" gibi ifadeler → MUTLAKA REJECT, max puan 30
- Genel puan < 60

**BEKLET (hold):**
- Genel puan 60-74
- Bazı endişeler var ama potansiyel görülüyor
- Kritik olmayan kırmızı bayraklar

**İŞE AL (hire):**
- Genel puan >= 75
- HİÇBİR kritik kırmızı bayrak yok
- Şiddet/tehdit/küfür İÇERMİYOR

⚠️ ÖNEMLİ: Şiddet veya tehdit içeren ifade varsa, diğer cevaplar ne kadar iyi olursa olsun REJECT olmalı!

## ÇIKTI FORMATI (Sadece JSON):
{
    "competency_scores": {
        "yetkinlik_kodu": {
            "score": 85,
            "raw_score": 4.25,
            "max_score": 5,
            "evidence": ["Adayın cevabından: '...' - Bu ifade X yetkinliğini gösteriyor"],
            "improvement_areas": ["Daha fazla somut örnek verebilirdi"]
        }
    },
    "overall_score": 78.5,
    "behavior_analysis": {
        "clarity_score": 80,
        "consistency_score": 85,
        "stress_tolerance": 75,
        "communication_style": "professional|casual|formal|hesitant",
        "confidence_level": "high|medium-high|medium|low"
    },
    "red_flag_analysis": {
        "flags_detected": false,
        "flags": [],
        "overall_risk": "none|low|medium|high"
    },
    "culture_fit": {
        "discipline_fit": 80,
        "hygiene_quality_fit": 90,
        "schedule_tempo_fit": 75,
        "overall_fit": 82,
        "notes": "Açıklama"
    },
    "decision_snapshot": {
        "recommendation": "hire|hold|reject",
        "confidence_percent": 78,
        "reasons": ["Ana neden 1", "Ana neden 2", "Ana neden 3"],
        "suggested_questions": ["Takip sorusu önerisi"]
    },
    "question_analyses": [
        {
            "question_order": 1,
            "score": 4,
            "competency_code": "yetkinlik_kodu",
            "analysis": "Detaylı analiz açıklaması",
            "positive_points": ["Olumlu nokta"],
            "negative_points": ["Olumsuz nokta"],
            "ideal_points_matched": ["Karşılanan ideal cevap noktaları"]
        }
    ]
}

⚠️ ÖNEMLİ KURALLAR:
1. Sadece JSON formatında cevap ver
2. Şiddet/tehdit/küfür tespit edilirse → overall_score MAX 30, recommendation: "reject"
3. "kırarım", "döverim", "öldürürüm" gibi ifadeler → OTOMATİK RED
4. Kırmızı bayrak varsa asla "hire" önerme
5. Puanlamada adil ve tutarlı ol
PROMPT;
    }

    private function buildAnalysisUserPrompt(array $responses): string
    {
        $prompt = "## MÜLAKAT CEVAPLARI ANALİZİ\n\n";
        $prompt .= "Aşağıda adayın her soruya verdiği cevaplar ve beklenen ideal cevap noktaları bulunmaktadır.\n";
        $prompt .= "Her cevabı ideal noktalara göre değerlendir ve puan ver.\n\n";

        foreach ($responses as $index => $response) {
            $order = $index + 1;
            $prompt .= "═══════════════════════════════════════\n";
            $prompt .= "### SORU {$order}\n";
            $prompt .= "═══════════════════════════════════════\n\n";
            $prompt .= "**Soru:** {$response['question_text']}\n\n";
            $prompt .= "**Ölçülen Yetkinlik:** {$response['competency_code']}\n\n";

            // Add ideal answer points if available
            if (!empty($response['ideal_answer_points'])) {
                $prompt .= "**Beklenen İdeal Cevap Noktaları:**\n";
                if (is_array($response['ideal_answer_points'])) {
                    foreach ($response['ideal_answer_points'] as $point) {
                        $prompt .= "  • {$point}\n";
                    }
                } else {
                    $prompt .= "  • {$response['ideal_answer_points']}\n";
                }
                $prompt .= "\n";
            }

            $prompt .= "**Cevap Süresi:** {$response['duration_seconds']} saniye\n\n";
            $prompt .= "**ADAYIN CEVABI:**\n";
            $prompt .= "```\n{$response['transcript']}\n```\n\n";

            // Add evaluation hints
            $prompt .= "→ Bu cevabı değerlendirirken: İdeal noktalardan kaçı karşılandı? Somut örnek var mı? STAR metodu kullanılmış mı?\n\n";
        }

        $prompt .= "═══════════════════════════════════════\n";
        $prompt .= "## ANALİZ TALİMATI\n";
        $prompt .= "═══════════════════════════════════════\n\n";
        $prompt .= "Yukarıdaki tüm cevapları dikkatlice analiz et:\n";
        $prompt .= "1. Her cevabı ideal noktalara göre karşılaştır\n";
        $prompt .= "2. Somut örnekleri ve STAR metodunu ara\n";
        $prompt .= "3. Tutarlılık ve özgünlük kontrol et\n";
        $prompt .= "4. Kırmızı bayrakları tespit et\n";
        $prompt .= "5. Adil ve objektif puanlama yap\n\n";
        $prompt .= "Sonucu SADECE JSON formatında döndür. Başka açıklama ekleme.";

        return $prompt;
    }

    public function test(): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'provider' => 'kimi',
                'error' => 'API key is not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(30)->get($this->baseUrl . '/models');

            return [
                'success' => $response->successful(),
                'provider' => 'kimi',
                'models_available' => $response->successful(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'provider' => 'kimi',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getModelInfo(): array
    {
        return [
            'provider' => 'kimi',
            'model' => $this->model,
        ];
    }

    public function improveJobDescription(string $title, string $description, string $positionType = ''): array
    {
        $systemPrompt = <<<PROMPT
Sen profesyonel bir is ilani yazarisin. Kullanicinin girdigi is ilani aciklamasini daha profesyonel, net ve cekici hale getireceksin.

KURALLAR:
1. Turkce dil bilgisi kurallarini uygula
2. Profesyonel bir dil kullan
3. Gereksiz tekrarlari kaldir
4. Net ve anlasilir cumle yapilari kur
5. Is saatlerini ve sartlari duzenli listele
6. Pozitif ve davetkar bir ton kullan
7. Cok uzun olmasin, ozet ve etkili olsun

CIKTI FORMATI (JSON):
{
    "improved_description": "Duzenlenmis aciklama metni",
    "improvements_made": ["Yapilan iyilestirme 1", "Yapilan iyilestirme 2"]
}

Sadece JSON formatinda cevap ver, baska aciklama ekleme.
PROMPT;

        $userPrompt = "ILAN BASLIGI: {$title}\n";
        if ($positionType) {
            $userPrompt .= "POZISYON TURU: {$positionType}\n";
        }
        $userPrompt .= "\nORIJINAL ACIKLAMA:\n{$description}\n\nBu aciklamayi profesyonelce duzenle.";

        try {
            $response = $this->chat($systemPrompt, $userPrompt, [
                'response_format' => ['type' => 'json_object'],
            ]);

            return $this->parseJsonResponse($response);
        } catch (Exception $e) {
            Log::error('Job description improvement failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
