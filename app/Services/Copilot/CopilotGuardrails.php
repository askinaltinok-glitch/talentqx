<?php

namespace App\Services\Copilot;

/**
 * Copilot Guardrails for input validation and output filtering.
 *
 * Implements "No Assumptions - Evidence Based - Silence is a Virtue" doctrine.
 * All blocking uses status codes for locale-independent API responses.
 */
class CopilotGuardrails
{
    /**
     * Disallowed topics - hard block (no LLM call).
     * Includes Turkish and English patterns.
     */
    private array $disallowedTopicPatterns = [
        // Salary / Compensation - Turkish
        '/\b(maaş|ücret|maas|ucret)\b/iu',
        '/\b(maaş.*teklif|teklif.*maaş)\b/iu',
        '/\bne kadar.*(kazan|öde|teklif)/iu',
        '/\b(ödeme|odeme)\b.*\b(öneri|oneri|tavsiye)\b/iu',

        // Salary / Compensation - English
        '/\b(salary|wage|compensation|pay)\b/i',
        '/what.*(salary|pay|compensation|wage)/i',
        '/how much.*(pay|offer|earn|make)/i',
        '/(salary|pay|compensation).*(recommendation|suggestion|range|offer)/i',
        '/recommend.*(salary|pay|compensation)/i',

        // Discrimination / Protected Characteristics - Turkish
        '/\b(yaş|yas|cinsiyet|etnisite|ırk|irk|engellilik|hamilelik)\b/iu',
        '/\b(evli|bekar)\b.*\b(mi|mı|mu|mü)\b/iu',
        '/\bhamile\b.*\b(mi|mı)\b/iu',
        '/\byaşı kaç\b/iu',
        '/\bdin(e|i|in)?\b.*\b(mensup|bağlı|inananlar|inanç)\b/iu',
        '/\bhangi\b.*\bdin/iu',

        // Discrimination / Protected Characteristics - English
        '/\b(age|ethnicity|race|religion|disability|gender|sex|nationality)\b.*\b(factor|consider|affect|impact|matter)\b/i',
        '/is (he|she|they|the candidate).*(married|pregnant|religious|disabled)/i',
        '/what is (his|her|their).*(age|ethnicity|religion|gender|race)/i',
        '/\b(protected characteristic|discrimination)\b/i',

        // Legal Advice - Turkish
        '/\b(hukuki|yasal)\b.*\b(tavsiye|danışma|görüş)\b/iu',
        '/\bdava aç/iu',

        // Legal Advice - English
        '/\b(legal|lawsuit|litigation|sue)\b/i',
        '/\b(legal).*(advice|opinion|recommendation|counsel)\b/i',
        '/(can we|is it legal|legally allowed)/i',
    ];

    /**
     * Patterns for blocked intents with their respective status codes.
     */
    private array $blockedPatterns = [
        // Hiring decision - these get special treatment (allowed but with disclaimer)
        '/should (i|we) hire/i' => [
            'reason' => 'hiring_decision',
            'status_code' => GuardrailResult::STATUS_HIRING_DECISION,
        ],
        '/hire or (not|reject)/i' => [
            'reason' => 'hiring_decision',
            'status_code' => GuardrailResult::STATUS_HIRING_DECISION,
        ],
        '/(must|need to|have to) (hire|reject)/i' => [
            'reason' => 'hiring_decision',
            'status_code' => GuardrailResult::STATUS_HIRING_DECISION,
        ],
        '/is (this|the) candidate (good|bad|suitable)/i' => [
            'reason' => 'hiring_decision',
            'status_code' => GuardrailResult::STATUS_HIRING_DECISION,
        ],
        // Turkish hiring decision
        '/\bişe al(ayım|alım|ınız)\b/iu' => [
            'reason' => 'hiring_decision',
            'status_code' => GuardrailResult::STATUS_HIRING_DECISION,
        ],
        '/\bu adayı.*\b(almalı|reddetmeli)\b/iu' => [
            'reason' => 'hiring_decision',
            'status_code' => GuardrailResult::STATUS_HIRING_DECISION,
        ],
    ];

    /**
     * Allowed intent categories for classification.
     */
    private array $allowedIntents = [
        'candidate_analysis',
        'risk_summarization',
        'candidate_comparison',
        'decision_guidance',
        'interview_preparation',
        'training_needs',
        'score_interpretation',
        'red_flag_explanation',
        'batch_analysis',
        'position_fit',
        'general_question',
    ];

    /**
     * Patterns that indicate output contains definitive hiring statements.
     */
    private array $outputBlockPatterns = [
        '/you (should|must|need to) (hire|reject|not hire)/i',
        '/definitely (hire|reject|not hire)/i',
        '/I (recommend|suggest) (hiring|rejecting|not hiring)/i',
        '/\$[\d,]+(\.\d{2})?\s*(per|\/)\s*(year|month|hour|annually)/i',
        // Turkish patterns
        '/kesinlikle.*(işe al|reddet)/iu',
        '/(işe almalısınız|reddetmelisiniz)/iu',
    ];

    /**
     * Assumption language patterns - flag for post-processing.
     * These phrases indicate speculation without evidence.
     */
    private array $assumptionPatterns = [
        // English assumption indicators
        '/\b(probably|likely|might|could be|seems like|appears to)\b/i',
        '/\b(i think|i believe|i assume|i guess|in my opinion)\b/i',
        '/\b(maybe|perhaps|possibly)\b.*\b(is|are|has|have|would|will)\b/i',
        '/\bthis suggests that\b/i',
        '/\bit (is|seems|appears) (possible|probable|likely) that\b/i',
        '/\b(based on my experience|typically|generally speaking)\b/i',
        '/\bwithout (evidence|data|information),?\s*(but|however|i|we)\b/i',

        // Turkish assumption indicators
        '/\b(muhtemelen|büyük ihtimalle|belki|galiba|sanırım|bence)\b/iu',
        '/\b(olabilir|görünüyor|tahminim)\b/iu',
        '/\bkanıt olmadan\b/iu',
        '/\b(genellikle|tipik olarak)\b/iu',
    ];

    /**
     * Validate user input before processing.
     *
     * @param string $message User message
     * @return GuardrailResult Result with status code
     */
    public function validateInput(string $message): GuardrailResult
    {
        $message = trim($message);

        // Check for empty input
        if (empty($message)) {
            return GuardrailResult::blocked(
                'empty_input',
                GuardrailResult::STATUS_EMPTY_INPUT
            );
        }

        // Check for excessively long input
        if (strlen($message) > 2000) {
            return GuardrailResult::blocked(
                'input_too_long',
                GuardrailResult::STATUS_INPUT_TOO_LONG
            );
        }

        // Check for disallowed topics (hard block - no LLM call)
        foreach ($this->disallowedTopicPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return GuardrailResult::blocked(
                    'disallowed_topic',
                    GuardrailResult::STATUS_DISALLOWED_TOPIC
                );
            }
        }

        // Check against blocked patterns (soft block - with guidance)
        foreach ($this->blockedPatterns as $pattern => $config) {
            if (preg_match($pattern, $message)) {
                return GuardrailResult::blocked(
                    $config['reason'],
                    $config['status_code']
                );
            }
        }

        return GuardrailResult::allowed();
    }

    /**
     * Filter and sanitize AI output before returning to user.
     *
     * @param string $response LLM response
     * @return string Filtered response
     */
    public function filterOutput(string $response): string
    {
        // Check for definitive hiring statements and add disclaimer
        foreach ($this->outputBlockPatterns as $pattern) {
            if (preg_match($pattern, $response)) {
                $response = $this->addDisclaimer($response);
                break;
            }
        }

        // Filter assumption language
        $response = $this->filterAssumptionLanguage($response);

        // Remove any inadvertent PII patterns using PiiSanitizer
        $response = PiiSanitizer::sanitizeText($response);

        return $response;
    }

    /**
     * Filter assumption language from LLM output.
     *
     * Replaces speculative phrases with evidence-based alternatives
     * or removes them if they cannot be substantiated.
     *
     * @param string $response LLM response
     * @return string Filtered response
     */
    public function filterAssumptionLanguage(string $response): string
    {
        // Define replacements for common assumption phrases
        $replacements = [
            // English replacements
            '/\bI think\b/i' => 'Based on the assessment data',
            '/\bI believe\b/i' => 'The data indicates',
            '/\bprobably\b/i' => '',
            '/\blikely\b/i' => '',
            '/\bmight be\b/i' => 'is',
            '/\bcould be\b/i' => 'is',
            '/\bseems like\b/i' => '',
            '/\bappears to be\b/i' => 'is',
            '/\bin my opinion,?\s*/i' => '',
            '/\bI assume\b/i' => 'According to the data',
            '/\bI guess\b/i' => 'The evidence suggests',
            '/\bmaybe\b/i' => '',
            '/\bperhaps\b/i' => '',
            '/\bpossibly\b/i' => '',

            // Turkish replacements
            '/\bmuhtemelen\b/iu' => '',
            '/\bbüyük ihtimalle\b/iu' => '',
            '/\bbelki\b/iu' => '',
            '/\bgaliba\b/iu' => '',
            '/\bsanırım\b/iu' => 'Verilere göre',
            '/\bbence\b/iu' => 'Değerlendirme verilerine göre',
            '/\bolabilir\b/iu' => '',
            '/\bgörünüyor\b/iu' => '',
            '/\btahminim\b/iu' => 'Veriler gösteriyor ki',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $response = preg_replace($pattern, $replacement, $response);
        }

        // Clean up double spaces and trailing/leading whitespace
        $response = preg_replace('/\s+/', ' ', $response);
        $response = trim($response);

        return $response;
    }

    /**
     * Check if response contains assumption language.
     *
     * @param string $response LLM response
     * @return bool True if assumption language detected
     */
    public function containsAssumptionLanguage(string $response): bool
    {
        foreach ($this->assumptionPatterns as $pattern) {
            if (preg_match($pattern, $response)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of assumption phrases found in response.
     *
     * @param string $response LLM response
     * @return array List of found assumption phrases
     */
    public function detectAssumptionPhrases(string $response): array
    {
        $found = [];

        foreach ($this->assumptionPatterns as $pattern) {
            if (preg_match_all($pattern, $response, $matches)) {
                $found = array_merge($found, $matches[0]);
            }
        }

        return array_unique($found);
    }

    /**
     * Validate that the output doesn't make definitive hiring decisions.
     *
     * @param string $response LLM response
     * @return GuardrailResult Validation result
     */
    public function validateOutput(string $response): GuardrailResult
    {
        // Check for critical violations that should be blocked entirely
        $criticalPatterns = [
            '/you must (hire|reject) this candidate/i',
            '/I have decided to (hire|reject)/i',
            // Turkish critical patterns
            '/kesinlikle.*(işe al|işe alma|reddet)/iu',
            '/\bkarar verdim.*(işe al|reddet)/iu',
        ];

        foreach ($criticalPatterns as $pattern) {
            if (preg_match($pattern, $response)) {
                return GuardrailResult::blocked(
                    'definitive_hiring_statement',
                    GuardrailResult::STATUS_HIRING_DECISION
                );
            }
        }

        // Check for assumption language violations
        $assumptionPhrases = $this->detectAssumptionPhrases($response);
        if (count($assumptionPhrases) > 3) {
            // Too many assumption phrases - flag but don't block
            // This will be logged for audit
            return GuardrailResult::blocked(
                'excessive_assumption_language',
                GuardrailResult::STATUS_ASSUMPTION_LANGUAGE
            );
        }

        return GuardrailResult::allowed();
    }

    /**
     * Classify the intent of a user message.
     *
     * @param string $message User message
     * @return string Intent classification
     */
    public function classifyIntent(string $message): string
    {
        $message = strtolower($message);

        // Pattern-based intent classification
        $intentPatterns = [
            'candidate_analysis' => [
                '/analyz/i', '/assess/i', '/evaluat/i', '/review.*candidate/i',
                '/candidate.*score/i', '/competenc/i',
                // Turkish
                '/analiz/i', '/değerlendir/i', '/aday.*puan/i',
            ],
            'risk_summarization' => [
                '/risk/i', '/red flag/i', '/concern/i', '/warning/i',
                '/issue/i', '/problem/i',
                // Turkish
                '/riskl/i', '/kırmızı bayrak/i', '/endişe/i', '/sorun/i',
            ],
            'candidate_comparison' => [
                '/compar/i', '/versus/i', '/vs\./i', '/between.*candidate/i',
                '/which.*candidate/i', '/better.*candidate/i',
                // Turkish
                '/karşılaştır/i', '/hangi.*aday/i', '/daha iyi.*aday/i',
            ],
            'decision_guidance' => [
                '/insight/i', '/recommend/i', '/suggest/i', '/advice/i',
                '/what.*think/i', '/your.*opinion/i',
                // Turkish
                '/öner/i', '/tavsiye/i', '/ne.*düşün/i',
            ],
            'interview_preparation' => [
                '/question.*ask/i', '/interview.*question/i', '/follow.*up/i',
                '/ask.*candidate/i', '/prepare.*interview/i',
                // Turkish
                '/soru.*sor/i', '/mülakat.*soru/i', '/aday.*sor/i',
            ],
            'training_needs' => [
                '/training/i', '/develop/i', '/improve/i', '/learn/i',
                '/onboard/i', '/skill.*gap/i',
                // Turkish
                '/eğitim/i', '/geliştir/i', '/öğren/i', '/yetkinlik.*açığı/i',
            ],
            'score_interpretation' => [
                '/what.*mean/i', '/interpret/i', '/explain.*score/i',
                '/score.*mean/i', '/understand.*result/i',
                // Turkish
                '/ne.*anlam/i', '/açıkla.*puan/i', '/sonuç.*anla/i',
            ],
            'red_flag_explanation' => [
                '/explain.*flag/i', '/why.*flag/i', '/detail.*flag/i',
                '/flag.*mean/i',
                // Turkish
                '/bayrak.*açıkla/i', '/neden.*bayrak/i',
            ],
            'batch_analysis' => [
                '/top.*candidate/i', '/all.*candidate/i', '/summari.*candidate/i',
                '/rank/i', '/list.*candidate/i',
                // Turkish
                '/en iyi.*aday/i', '/tüm.*aday/i', '/sırala/i',
            ],
            'position_fit' => [
                '/fit.*position/i', '/position.*fit/i', '/match.*job/i',
                '/suitable.*role/i', '/role.*match/i',
                // Turkish
                '/pozisyon.*uygun/i', '/uygun.*rol/i', '/iş.*uyum/i',
            ],
        ];

        foreach ($intentPatterns as $intent => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    return $intent;
                }
            }
        }

        return 'general_question';
    }

    /**
     * Add a disclaimer to responses about risk factors.
     *
     * @param string $response LLM response
     * @return string Response with disclaimer
     */
    private function addDisclaimer(string $response): string
    {
        $disclaimer = "\n\n**Note:** This analysis is provided to support your decision-making process. The final hiring decision should be made by authorized personnel considering all relevant factors.";

        // Only add if not already present
        if (stripos($response, 'final hiring decision') === false) {
            $response .= $disclaimer;
        }

        return $response;
    }

    /**
     * Get status code message for a given status code.
     *
     * @param string $statusCode Status code
     * @param string $locale Locale (tr/en)
     * @return string Localized message
     */
    public function getStatusCodeMessage(string $statusCode, string $locale = 'tr'): string
    {
        $messages = [
            'tr' => [
                GuardrailResult::STATUS_HIRING_DECISION => 'Analiz ve içgörü sağlayabilirim, ancak işe alım kararları yetkili personel tarafından verilmelidir.',
                GuardrailResult::STATUS_SALARY_RECOMMENDATION => 'Maaş kararları değerlendirme kapsamı dışında birçok faktör içerir. Lütfen ücret politikalarınıza veya İK ekibinize danışın.',
                GuardrailResult::STATUS_DISCRIMINATION => 'Korunan özelliklere dayalı değerlendirme yapamam. Yalnızca işle ilgili yetkinlikleri ve değerlendirme sonuçlarını analiz edebilirim.',
                GuardrailResult::STATUS_LEGAL_ADVICE => 'Hukuki sorular için lütfen hukuk departmanınıza veya İK uyum ekibinize danışın.',
                GuardrailResult::STATUS_PII_INFERENCE => 'Yalnızca sistemdeki verilerle çalışabilirim. Değerlendirmede sağlanmayan kişisel bilgileri çıkarsayamam.',
                GuardrailResult::STATUS_EMPTY_INPUT => 'Lütfen analiz edilecek bir mesaj girin.',
                GuardrailResult::STATUS_INPUT_TOO_LONG => 'Mesajınız çok uzun. Lütfen 2000 karakterin altında tutun.',
                GuardrailResult::STATUS_DISALLOWED_TOPIC => 'Bu konu değerlendirme kapsamı dışındadır.',
                GuardrailResult::STATUS_ASSUMPTION_LANGUAGE => 'Analiz yalnızca kanıta dayalı ifadeler içermelidir.',
                GuardrailResult::STATUS_NO_INTERVIEW => 'Bu aday için henüz mülakat yapılmamıştır. Copilot analizi için en az bir mülakat tamamlanmalıdır.',
                GuardrailResult::STATUS_KVKK_VIOLATION => 'Bu istek KVKK/GDPR uyumluluğu nedeniyle engellenmiştir.',
                GuardrailResult::STATUS_INSUFFICIENT_EVIDENCE => 'Yetersiz veri: Değerlendirme yapılabilmesi için en az 3 soruya detaylı yanıt verilmesi gerekmektedir. Lütfen yanıtlarınızı gözden geçirin.',
                GuardrailResult::STATUS_INTERNAL_ERROR => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
            ],
            'en' => [
                GuardrailResult::STATUS_HIRING_DECISION => 'I can provide analysis and insights, but hiring decisions must be made by authorized personnel.',
                GuardrailResult::STATUS_SALARY_RECOMMENDATION => 'Salary decisions involve many factors beyond assessment scope. Please consult your compensation guidelines or HR team.',
                GuardrailResult::STATUS_DISCRIMINATION => 'I cannot make assessments based on protected characteristics. I can only analyze job-related competencies and assessment results.',
                GuardrailResult::STATUS_LEGAL_ADVICE => 'For legal questions, please consult your legal department or HR compliance team.',
                GuardrailResult::STATUS_PII_INFERENCE => 'I can only work with data already in the system. I cannot infer personal information not provided in the assessment.',
                GuardrailResult::STATUS_EMPTY_INPUT => 'Please provide a message to analyze.',
                GuardrailResult::STATUS_INPUT_TOO_LONG => 'Your message is too long. Please keep it under 2000 characters.',
                GuardrailResult::STATUS_DISALLOWED_TOPIC => 'This topic is outside the scope of assessment.',
                GuardrailResult::STATUS_ASSUMPTION_LANGUAGE => 'Analysis should contain only evidence-based statements.',
                GuardrailResult::STATUS_NO_INTERVIEW => 'No interview has been completed for this candidate yet. At least one interview must be completed for Copilot analysis.',
                GuardrailResult::STATUS_KVKK_VIOLATION => 'This request has been blocked due to KVKK/GDPR compliance.',
                GuardrailResult::STATUS_INSUFFICIENT_EVIDENCE => 'Insufficient data: At least 3 questions must be answered in detail for assessment. Please review your responses.',
                GuardrailResult::STATUS_INTERNAL_ERROR => 'An error occurred. Please try again later.',
            ],
        ];

        $locale = in_array($locale, ['tr', 'en']) ? $locale : 'en';

        return $messages[$locale][$statusCode] ?? $messages[$locale][GuardrailResult::STATUS_INTERNAL_ERROR] ?? 'An error occurred.';
    }

    /**
     * Get the reason message for a blocked intent (deprecated - use getStatusCodeMessage).
     *
     * @deprecated Use getStatusCodeMessage() instead
     * @param string $reason Reason code
     * @return string Message
     */
    public function getBlockReasonMessage(string $reason): string
    {
        return $this->getStatusCodeMessage(
            GuardrailResult::mapReasonToStatusCode($reason) ?? GuardrailResult::STATUS_INTERNAL_ERROR,
            'en'
        );
    }
}
