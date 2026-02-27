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
        $locale = $context['locale'] ?? 'tr';
        $systemPrompt = $this->buildQuestionGenerationSystemPrompt($locale);
        $userPrompt = $this->buildQuestionGenerationUserPrompt($competencies, $questionRules, $context, $locale);

        $response = $this->chat($systemPrompt, $userPrompt, [
            'response_format' => ['type' => 'json_object'],
        ]);

        return $this->parseJsonResponse($response);
    }

    public function analyzeInterview(array $responses, array $competencies, array $redFlags, array $culturalContext = []): array
    {
        $systemPrompt = $this->buildAnalysisSystemPrompt($competencies, $redFlags);
        $userPrompt = $this->buildAnalysisUserPrompt($responses);

        $response = $this->chat($systemPrompt, $userPrompt, [
            'response_format' => ['type' => 'json_object'],
        ]);

        return $this->parseJsonResponse($response);
    }

    public function analyzeFormInterview(array $responses, array $competencies, array $culturalContext = []): array
    {
        // Delegate to the same prompt structure as OpenAIProvider
        // Kimi uses the same chat completion format
        $locale = $culturalContext['locale'] ?? 'tr';
        $systemPrompt = $this->buildFormInterviewAnalysisSystemPrompt($competencies, $culturalContext, $locale);
        $userPrompt = $this->buildFormInterviewAnalysisUserPrompt($responses, $culturalContext, $locale);

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

    private function buildQuestionGenerationSystemPrompt(string $locale = 'tr'): string
    {
        $localeInstructions = [
            'tr' => [
                'role' => 'Sen deneyimli bir İK mülakat uzmanısın. Verilen yetkinlik seti ve kurallara göre mülakat soruları üreteceksin.',
                'rules' => "KURALLAR:\n1. Her soru tek bir yetkinliği ölçmeli\n2. Sorular Türkçe ve anlaşılır olmalı\n3. Senaryo soruları gerçekçi iş durumlarına dayanmalı\n4. Davranışsal sorular STAR metoduna uygun olmalı\n5. Teknik sorular pozisyona özgü olmalı\n6. Sektöre ve iş tanımına uygun sorular üret",
                'footer' => 'Sadece JSON formatında cevap ver, başka açıklama ekleme.',
            ],
            'en' => [
                'role' => 'You are an experienced HR interview specialist. You will generate interview questions based on the given competency set and rules.',
                'rules' => "RULES:\n1. Each question must measure a single competency\n2. Questions must be in English, clear and professional\n3. Scenario questions must be based on realistic work situations\n4. Behavioral questions must follow the STAR method\n5. Technical questions must be position-specific\n6. Generate questions relevant to the industry and job description",
                'footer' => 'Respond ONLY in JSON format, do not add any other explanation.',
            ],
            'de' => [
                'role' => 'Sie sind ein erfahrener HR-Interview-Spezialist. Sie werden Interviewfragen basierend auf dem gegebenen Kompetenzset und den Regeln generieren.',
                'rules' => "REGELN:\n1. Jede Frage muss eine einzelne Kompetenz messen\n2. Fragen müssen auf Deutsch, klar und professionell sein\n3. Szenariofragen müssen auf realistischen Arbeitssituationen basieren\n4. Verhaltensfragen müssen der STAR-Methode folgen\n5. Technische Fragen müssen positionsspezifisch sein\n6. Generieren Sie Fragen, die zur Branche und Stellenbeschreibung passen",
                'footer' => 'Antworten Sie NUR im JSON-Format, fügen Sie keine weitere Erklärung hinzu.',
            ],
            'fr' => [
                'role' => 'Vous êtes un spécialiste expérimenté en entretiens RH. Vous allez générer des questions d\'entretien basées sur l\'ensemble de compétences et les règles données.',
                'rules' => "RÈGLES :\n1. Chaque question doit mesurer une seule compétence\n2. Les questions doivent être en français, claires et professionnelles\n3. Les questions de mise en situation doivent être basées sur des situations de travail réalistes\n4. Les questions comportementales doivent suivre la méthode STAR\n5. Les questions techniques doivent être spécifiques au poste\n6. Générez des questions pertinentes pour le secteur et la description du poste",
                'footer' => 'Répondez UNIQUEMENT en format JSON, n\'ajoutez aucune autre explication.',
            ],
            'ar' => [
                'role' => 'أنت متخصص خبير في مقابلات الموارد البشرية. ستقوم بإنشاء أسئلة مقابلة بناءً على مجموعة الكفاءات والقواعد المعطاة.',
                'rules' => "القواعد:\n1. يجب أن يقيس كل سؤال كفاءة واحدة\n2. يجب أن تكون الأسئلة باللغة العربية وواضحة ومهنية\n3. يجب أن تستند أسئلة السيناريو إلى مواقف عمل واقعية\n4. يجب أن تتبع الأسئلة السلوكية طريقة STAR\n5. يجب أن تكون الأسئلة التقنية خاصة بالمنصب\n6. أنشئ أسئلة ذات صلة بالقطاع والوصف الوظيفي",
                'footer' => 'أجب بتنسيق JSON فقط، لا تضف أي شرح آخر.',
            ],
        ];

        $inst = $localeInstructions[$locale] ?? $localeInstructions['tr'];

        return <<<PROMPT
{$inst['role']}

{$inst['rules']}

CIKTI FORMATI / OUTPUT FORMAT (JSON):
{
    "questions": [
        {
            "question_type": "technical|behavioral|scenario|culture",
            "question_text": "Question text",
            "competency_code": "competency_code",
            "ideal_answer_points": ["Expected point 1", "Point 2", "Point 3"],
            "time_limit_seconds": 180
        }
    ]
}

{$inst['footer']}
PROMPT;
    }

    private function buildQuestionGenerationUserPrompt(array $competencies, array $questionRules, array $context, string $locale = 'tr'): string
    {
        $competenciesJson = json_encode($competencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $rulesJson = json_encode($questionRules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $langLabels = [
            'tr' => ['competencies' => 'YETKİNLİK SETİ', 'rules' => 'SORU ÜRETİM KURALLARI', 'position' => 'POZİSYON', 'sector' => 'SEKTÖR BAĞLAMI', 'domain' => 'Sektör', 'subdomain' => 'Alt Kol', 'title' => 'Pozisyon', 'desc' => 'İş Tanımı', 'samples' => 'ÖRNEK SORULAR (bunlara benzer üret)', 'footer' => 'Yukarıdaki yetkinliklere ve kurallara göre mülakat sorularını JSON formatında üret.'],
            'en' => ['competencies' => 'COMPETENCY SET', 'rules' => 'QUESTION GENERATION RULES', 'position' => 'POSITION', 'sector' => 'SECTOR CONTEXT', 'domain' => 'Industry', 'subdomain' => 'Sub-sector', 'title' => 'Position', 'desc' => 'Job Description', 'samples' => 'SAMPLE QUESTIONS (generate similar)', 'footer' => 'Generate interview questions in JSON format based on the competencies and rules above.'],
            'de' => ['competencies' => 'KOMPETENZSET', 'rules' => 'FRAGENGENERIERUNGSREGELN', 'position' => 'POSITION', 'sector' => 'BRANCHENKONTEXT', 'domain' => 'Branche', 'subdomain' => 'Unterbereich', 'title' => 'Position', 'desc' => 'Stellenbeschreibung', 'samples' => 'BEISPIELFRAGEN (ähnliche generieren)', 'footer' => 'Generieren Sie Interviewfragen im JSON-Format basierend auf den obigen Kompetenzen und Regeln.'],
            'fr' => ['competencies' => 'ENSEMBLE DE COMPÉTENCES', 'rules' => 'RÈGLES DE GÉNÉRATION', 'position' => 'POSTE', 'sector' => 'CONTEXTE SECTORIEL', 'domain' => 'Secteur', 'subdomain' => 'Sous-secteur', 'title' => 'Poste', 'desc' => 'Description du poste', 'samples' => 'QUESTIONS EXEMPLES (générer similaires)', 'footer' => 'Générez des questions d\'entretien au format JSON basées sur les compétences et règles ci-dessus.'],
            'ar' => ['competencies' => 'مجموعة الكفاءات', 'rules' => 'قواعد إنشاء الأسئلة', 'position' => 'المنصب', 'sector' => 'سياق القطاع', 'domain' => 'القطاع', 'subdomain' => 'القطاع الفرعي', 'title' => 'المنصب', 'desc' => 'الوصف الوظيفي', 'samples' => 'أسئلة نموذجية (أنشئ مشابهة)', 'footer' => 'أنشئ أسئلة المقابلة بتنسيق JSON بناءً على الكفاءات والقواعد أعلاه.'],
        ];

        $l = $langLabels[$locale] ?? $langLabels['tr'];

        $prompt = "{$l['competencies']}:\n{$competenciesJson}\n\n{$l['rules']}:\n{$rulesJson}\n\n";

        if (!empty($context['position_name'])) {
            $prompt .= "{$l['position']}: " . $context['position_name'] . "\n";
        }

        // Sector context block
        $hasSector = !empty($context['domain']) || !empty($context['subdomain']) || !empty($context['job_description']);
        if ($hasSector) {
            $prompt .= "\n{$l['sector']}:\n";
            if (!empty($context['domain'])) {
                $prompt .= "- {$l['domain']}: {$context['domain']}\n";
            }
            if (!empty($context['subdomain'])) {
                $prompt .= "- {$l['subdomain']}: {$context['subdomain']}\n";
            }
            if (!empty($context['job_title'])) {
                $prompt .= "- {$l['title']}: {$context['job_title']}\n";
            }
            if (!empty($context['job_description'])) {
                $desc = mb_substr($context['job_description'], 0, 500);
                $prompt .= "- {$l['desc']}: {$desc}\n";
            }
        }

        if (!empty($context['sample_questions'])) {
            $samplesJson = json_encode($context['sample_questions'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $prompt .= "\n{$l['samples']}:\n{$samplesJson}";
        }

        $prompt .= "\n\n{$l['footer']}";

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

    private function buildFormInterviewAnalysisSystemPrompt(array $competencies, array $culturalContext, string $locale): string
    {
        $competenciesJson = json_encode($competencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $localeInstructions = [
            'tr' => [
                'role' => 'Sen deneyimli bir IK analisti ve davranis bilimleri uzmanisin. Yazili mulakat cevaplarini analiz edecek ve detayli degerlendirme yapacaksin.',
                'rules' => "1. Her yetkinligi 0-100 arasi puanla\n2. Puanlama somut kanita dayali olmali\n3. Kirmizi bayraklari tespit et\n4. Kultur uyumunu degerlendir\n5. Karar onerisini hire/hold/reject olarak belirt\n6. Bos/kisa cevaplari dusuk puanla",
                'format_note' => 'Sadece JSON formatinda cevap ver.',
            ],
            'en' => [
                'role' => 'You are an experienced HR analyst. Analyze written interview responses and provide detailed assessment.',
                'rules' => "1. Score each competency 0-100\n2. Evidence-based scoring\n3. Detect red flags\n4. Evaluate culture fit\n5. Recommendation: hire/hold/reject\n6. Score empty/short answers low",
                'format_note' => 'Respond ONLY in JSON format.',
            ],
        ];

        $inst = $localeInstructions[$locale] ?? $localeInstructions['tr'];

        return <<<PROMPT
{$inst['role']}

COMPETENCY SET:
{$competenciesJson}

RULES:
{$inst['rules']}

CRITICAL RED FLAGS — AUTO REJECT:
- Violence/threat → max 30, reject
- Profanity → max 30, reject
- Discrimination → reject

OUTPUT FORMAT (JSON):
{
    "competency_scores": { "code": { "score": 85, "raw_score": 4.25, "max_score": 5, "evidence": ["..."], "improvement_areas": ["..."] } },
    "overall_score": 78.5,
    "behavior_analysis": { "clarity_score": 80, "consistency_score": 85, "stress_tolerance": 75, "communication_style": "professional", "confidence_level": "medium-high" },
    "red_flag_analysis": { "flags_detected": false, "flags": [], "overall_risk": "none" },
    "culture_fit": { "discipline_fit": 80, "overall_fit": 82, "notes": "..." },
    "decision_snapshot": { "recommendation": "hire|hold|reject", "confidence_percent": 78, "reasons": ["..."], "suggested_questions": ["..."] },
    "question_analyses": [{ "question_order": 1, "score": 4, "competency_code": "...", "analysis": "...", "positive_points": ["..."], "negative_points": ["..."] }]
}

{$inst['format_note']}
PROMPT;
    }

    private function buildFormInterviewAnalysisUserPrompt(array $responses, array $culturalContext, string $locale): string
    {
        $prompt = "WRITTEN INTERVIEW RESPONSES:\n\n";

        foreach ($responses as $index => $response) {
            $order = $index + 1;
            $prompt .= "--- QUESTION {$order} ---\n";
            $prompt .= "Question: {$response['question_text']}\n";
            $prompt .= "Competency: {$response['competency_code']}\n";
            $prompt .= "Answer ({$response['answer_length']} chars):\n";
            $prompt .= "{$response['answer_text']}\n\n";
        }

        if (!empty($culturalContext['position'])) {
            $prompt .= "POSITION: {$culturalContext['position']}\n";
        }
        if (!empty($culturalContext['industry'])) {
            $prompt .= "INDUSTRY: {$culturalContext['industry']}\n";
        }

        $prompt .= "\nAnalyze the written responses above and produce a detailed JSON report.";
        return $prompt;
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
