<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class OpenAIProvider implements LLMProviderInterface
{
    private ?string $apiKey;
    private string $model;
    private string $whisperModel;
    private int $timeout;

    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?string $whisperModel = null,
        ?int $timeout = null
    ) {
        // Use provided values or fall back to config (for backward compatibility)
        $this->apiKey = $apiKey ?? config('services.openai.api_key');
        $this->model = $model ?? config('services.openai.model', 'gpt-4-turbo-preview');
        $this->whisperModel = $whisperModel ?? config('services.openai.whisper_model', 'whisper-1');
        $this->timeout = $timeout ?? config('services.openai.timeout', 120);
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
        $systemPrompt = $this->buildAnalysisSystemPrompt($competencies, $redFlags, $culturalContext);
        $userPrompt = $this->buildAnalysisUserPrompt($responses);

        $response = $this->chat($systemPrompt, $userPrompt, [
            'response_format' => ['type' => 'json_object'],
        ]);

        return $this->parseJsonResponse($response);
    }

    public function analyzeFormInterview(array $responses, array $competencies, array $culturalContext = []): array
    {
        $locale = $culturalContext['locale'] ?? 'tr';
        $systemPrompt = $this->buildFormInterviewAnalysisSystemPrompt($competencies, $culturalContext, $locale);
        $userPrompt = $this->buildFormInterviewAnalysisUserPrompt($responses, $culturalContext, $locale);

        $response = $this->chat($systemPrompt, $userPrompt, [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
        ]);

        return $this->parseJsonResponse($response);
    }

    public function transcribeAudio(string $audioPath, string $language = 'tr'): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key is not configured');
        }

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

    /**
     * Public JSON-mode chat: returns raw JSON string from GPT.
     * Uses low temperature for deterministic scoring outputs.
     */
    public function chatJson(string $systemPrompt, string $userPrompt, array $extraOptions = []): string
    {
        return $this->chat($systemPrompt, $userPrompt, array_merge([
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.2,
        ], $extraOptions));
    }

    private function chat(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key is not configured');
        }

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

    private function buildAnalysisSystemPrompt(array $competencies, array $redFlags, array $culturalContext = []): string
    {
        $competenciesJson = json_encode($competencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $redFlagsJson = json_encode($redFlags, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $culturalSection = '';
        if (!empty($culturalContext)) {
            $countryCode = $culturalContext['country'] ?? 'N/A';
            $interviewLocale = $culturalContext['locale'] ?? 'tr';
            $cultureNotes = $culturalContext['culture_notes'] ?? '';

            $culturalSection = <<<CULTURAL

KULTUREL DEGERLENDIRME KURALLARI:
1. Adayin ulkesi/kulturu: {$countryCode}
2. Mulakat dili: {$interviewLocale}
3. Kulturel iletisim farkliliklerini goz onune al:
   - TR: Dolayli iletisim, saygi odakli cumleler, hiyerarsi vurgusu normal
   - DE: Dogrudan/kisa cevaplar normal, abartili ifade eksikligi olumsuz degil
   - FR: Diplomatik dil, entelektuel yaklasim, uzun aciklamalar normal
   - AR: Resmi hitap, dini/kulturel referanslar normal, aile vurgusu pozitif
   - EN: Yapilandirilmis cevap beklentisi, STAR metodu bilgisi yuksek
4. Kulturel normlara gore "red flag" esiklerini ayarla
5. Iletisim tarzini kulturel baglamda degerlendir
{$cultureNotes}
CULTURAL;
        }

        return <<<PROMPT
Sen deneyimli bir HR analisti ve davranis bilimleri uzmanisin. Mulakat cevaplarini analiz edecek ve detayli degerlendirme yapacaksin.

YETKINLIK SETI:
{$competenciesJson}

KIRMIZI BAYRAKLAR:
{$redFlagsJson}
{$culturalSection}

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
        "cultural_communication_style": "direct|indirect|formal|informal",
        "cultural_adjustments_applied": ["Uygulanan kulturel duzeltme aciklamasi"],
        "cross_cultural_adaptability": 78,
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
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'provider' => 'openai',
                'error' => 'API key is not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(30)->get('https://api.openai.com/v1/models');

            return [
                'success' => $response->successful(),
                'provider' => 'openai',
                'models_available' => $response->successful(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'provider' => 'openai',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getModelInfo(): array
    {
        return [
            'provider' => 'openai',
            'model' => $this->model,
        ];
    }

    private function buildFormInterviewAnalysisSystemPrompt(array $competencies, array $culturalContext, string $locale): string
    {
        $competenciesJson = json_encode($competencies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $localeInstructions = [
            'tr' => [
                'role' => 'Sen deneyimli bir IK analisti ve davranis bilimleri uzmanisin. Yazili mulakat cevaplarini analiz edecek ve detayli degerlendirme yapacaksin.',
                'rules_header' => 'ANALIZ KURALLARI',
                'rules' => "1. Her yetkinligi 0-100 arasi puanla\n2. Puanlama somut kanita dayali olmali — adayin kendi yazdigi cumleleri referans goster\n3. Kirmizi bayraklari tespit et (siddet, kufur, tutarsizlik, sorumluluk atma, kacinma vb.)\n4. Kultur uyumunu is disiplini, iletisim tonu ve takim uyumu acisindan degerlendir\n5. Karar onerisini hire/hold/reject olarak belirt\n6. Guven yuzdesi ver (ne kadar emin oldugun)\n7. Bos veya cok kisa cevaplari (< 30 karakter) dusuk puanla\n8. Uzun ama anlamsiz/kopyala-yapistir cevaplari tespit et",
                'format_note' => 'Sadece JSON formatinda cevap ver, baska aciklama ekleme.',
            ],
            'en' => [
                'role' => 'You are an experienced HR analyst and behavioral science expert. You will analyze written interview responses and provide a detailed assessment.',
                'rules_header' => 'ANALYSIS RULES',
                'rules' => "1. Score each competency 0-100\n2. Scoring must be evidence-based — reference the candidate's own written statements\n3. Detect red flags (violence, profanity, inconsistency, blame-shifting, avoidance, etc.)\n4. Evaluate culture fit in terms of work discipline, communication tone, and team compatibility\n5. Provide recommendation as hire/hold/reject\n6. Give confidence percentage\n7. Score empty or very short answers (< 30 chars) low\n8. Detect long but meaningless/copy-paste answers",
                'format_note' => 'Respond ONLY in JSON format, do not add any other explanation.',
            ],
            'de' => [
                'role' => 'Sie sind ein erfahrener HR-Analyst und Verhaltenswissenschaftsexperte. Sie werden schriftliche Interviewantworten analysieren und eine detaillierte Bewertung abgeben.',
                'rules_header' => 'ANALYSEREGELN',
                'rules' => "1. Bewerten Sie jede Kompetenz von 0-100\n2. Die Bewertung muss evidenzbasiert sein — beziehen Sie sich auf die eigenen Aussagen des Kandidaten\n3. Erkennen Sie rote Flaggen (Gewalt, Beleidigungen, Inkonsistenz, Schuldzuweisungen, Vermeidung usw.)\n4. Bewerten Sie die kulturelle Passung hinsichtlich Arbeitsdisziplin, Kommunikationston und Teamkompatibilitaet\n5. Geben Sie eine Empfehlung als hire/hold/reject\n6. Geben Sie ein Konfidenzprozent an\n7. Bewerten Sie leere oder sehr kurze Antworten (< 30 Zeichen) niedrig\n8. Erkennen Sie lange aber bedeutungslose/kopierte Antworten",
                'format_note' => 'Antworten Sie NUR im JSON-Format, fuegen Sie keine weitere Erklaerung hinzu.',
            ],
            'fr' => [
                'role' => "Vous etes un analyste RH experimente et un expert en sciences comportementales. Vous analyserez les reponses ecrites d'entretien et fournirez une evaluation detaillee.",
                'rules_header' => "REGLES D'ANALYSE",
                'rules' => "1. Notez chaque competence de 0 a 100\n2. La notation doit etre basee sur des preuves — referencez les propres declarations ecrites du candidat\n3. Detectez les signaux d'alerte (violence, grossierete, incoherence, report de blame, evitement, etc.)\n4. Evaluez l'adequation culturelle en termes de discipline de travail, ton de communication et compatibilite d'equipe\n5. Fournissez une recommandation: hire/hold/reject\n6. Donnez un pourcentage de confiance\n7. Notez bas les reponses vides ou tres courtes (< 30 caracteres)\n8. Detectez les reponses longues mais vides de sens/copiees-collees",
                'format_note' => 'Repondez UNIQUEMENT en format JSON, n\'ajoutez aucune autre explication.',
            ],
            'ar' => [
                'role' => 'انت محلل موارد بشرية خبير ومتخصص في العلوم السلوكية. ستقوم بتحليل اجابات المقابلة المكتوبة وتقديم تقييم مفصل.',
                'rules_header' => 'قواعد التحليل',
                'rules' => "1. قيم كل كفاءة من 0 الى 100\n2. يجب ان يكون التقييم مبنيا على ادلة — اشر الى عبارات المرشح المكتوبة\n3. اكتشف الاشارات الحمراء (عنف، شتائم، تناقض، القاء اللوم، التهرب، الخ)\n4. قيم التوافق الثقافي من حيث انضباط العمل ونبرة التواصل والتوافق مع الفريق\n5. قدم التوصية: hire/hold/reject\n6. اعط نسبة ثقة\n7. اعط درجات منخفضة للاجابات الفارغة او القصيرة جدا (اقل من 30 حرف)\n8. اكتشف الاجابات الطويلة ولكن بلا معنى او المنسوخة",
                'format_note' => 'اجب بتنسيق JSON فقط، لا تضف اي شرح اخر.',
            ],
        ];

        $inst = $localeInstructions[$locale] ?? $localeInstructions['tr'];

        $culturalSection = '';
        if (!empty($culturalContext)) {
            $country = $culturalContext['country'] ?? 'N/A';
            $interviewLocale = $culturalContext['locale'] ?? $locale;

            $culturalSection = <<<CULTURAL_CTX

CULTURAL EVALUATION CONTEXT:
- Candidate country/culture: {$country}
- Interview language: {$interviewLocale}
- Adjust red flag thresholds based on cultural norms
- Written text analysis: focus on content quality, not speaking style
CULTURAL_CTX;
        }

        return <<<PROMPT
{$inst['role']}

COMPETENCY SET:
{$competenciesJson}
{$culturalSection}

{$inst['rules_header']}:
{$inst['rules']}

CRITICAL RED FLAGS — AUTO REJECT:
- Violence/threat language in ANY language → max score 30, reject
- Profanity, severe insults → max score 30, reject
- Discrimination, racist or sexist statements → reject
- Illegal activity implications → reject

SCORING CRITERIA (0-100):
- 90-100: Exceptional — exceeds all expectations, concrete examples, detailed explanations
- 75-89: Good — meets most expectations, some concrete examples
- 60-74: Average — meets basic expectations, lacks depth
- 40-59: Weak — below expectations, superficial answers
- 0-39: Insufficient — irrelevant or incomprehensible answer
- 0: Empty or no meaningful content

OUTPUT FORMAT (JSON):
{
    "competency_scores": {
        "competency_code": {
            "score": 85,
            "raw_score": 4.25,
            "max_score": 5,
            "evidence": ["Quote from candidate's answer showing this competency"],
            "improvement_areas": ["Area that needs improvement"]
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
        "flags": [
            {
                "code": "flag_code",
                "detected_phrase": "exact phrase detected",
                "severity": "low|medium|high|critical",
                "question_order": 3
            }
        ],
        "overall_risk": "none|low|medium|high"
    },
    "culture_fit": {
        "discipline_fit": 80,
        "hygiene_quality_fit": 90,
        "schedule_tempo_fit": 75,
        "overall_fit": 82,
        "notes": "Brief explanation"
    },
    "decision_snapshot": {
        "recommendation": "hire|hold|reject",
        "confidence_percent": 78,
        "reasons": ["Reason 1", "Reason 2", "Reason 3"],
        "suggested_questions": ["Suggested follow-up question"]
    },
    "question_analyses": [
        {
            "question_order": 1,
            "score": 4,
            "competency_code": "competency_code",
            "analysis": "Detailed analysis of this answer",
            "positive_points": ["Positive observation"],
            "negative_points": ["Negative observation"]
        }
    ]
}

{$inst['format_note']}
PROMPT;
    }

    private function buildFormInterviewAnalysisUserPrompt(array $responses, array $culturalContext, string $locale): string
    {
        $headers = [
            'tr' => ['title' => 'YAZILI MULAKAT CEVAPLARI', 'question' => 'Soru', 'competency' => 'Yetkinlik', 'answer' => 'Cevap', 'chars' => 'karakter', 'position' => 'POZISYON', 'industry' => 'SEKTOR', 'footer' => 'Yukaridaki yazili cevaplari analiz et ve JSON formatinda detayli rapor olustur.'],
            'en' => ['title' => 'WRITTEN INTERVIEW RESPONSES', 'question' => 'Question', 'competency' => 'Competency', 'answer' => 'Answer', 'chars' => 'characters', 'position' => 'POSITION', 'industry' => 'INDUSTRY', 'footer' => 'Analyze the written responses above and produce a detailed report in JSON format.'],
            'de' => ['title' => 'SCHRIFTLICHE INTERVIEWANTWORTEN', 'question' => 'Frage', 'competency' => 'Kompetenz', 'answer' => 'Antwort', 'chars' => 'Zeichen', 'position' => 'POSITION', 'industry' => 'BRANCHE', 'footer' => 'Analysieren Sie die obigen schriftlichen Antworten und erstellen Sie einen detaillierten Bericht im JSON-Format.'],
            'fr' => ['title' => "REPONSES D'ENTRETIEN ECRITES", 'question' => 'Question', 'competency' => 'Competence', 'answer' => 'Reponse', 'chars' => 'caracteres', 'position' => 'POSTE', 'industry' => 'SECTEUR', 'footer' => "Analysez les reponses ecrites ci-dessus et produisez un rapport detaille au format JSON."],
            'ar' => ['title' => 'اجابات المقابلة المكتوبة', 'question' => 'السؤال', 'competency' => 'الكفاءة', 'answer' => 'الاجابة', 'chars' => 'حرف', 'position' => 'المنصب', 'industry' => 'القطاع', 'footer' => 'حلل الاجابات المكتوبة اعلاه وانتج تقريرا مفصلا بتنسيق JSON.'],
        ];

        $h = $headers[$locale] ?? $headers['tr'];

        $prompt = "{$h['title']}:\n\n";

        foreach ($responses as $index => $response) {
            $order = $index + 1;
            $prompt .= "--- {$h['question']} {$order} ---\n";
            $prompt .= "{$h['question']}: {$response['question_text']}\n";
            $prompt .= "{$h['competency']}: {$response['competency_code']}\n";
            $prompt .= "{$h['answer']} ({$response['answer_length']} {$h['chars']}):\n";
            $prompt .= "{$response['answer_text']}\n\n";
        }

        if (!empty($culturalContext['position'])) {
            $prompt .= "{$h['position']}: {$culturalContext['position']}\n";
        }
        if (!empty($culturalContext['industry'])) {
            $prompt .= "{$h['industry']}: {$culturalContext['industry']}\n";
        }

        $prompt .= "\n{$h['footer']}";

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
                'temperature' => 0.6,
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
