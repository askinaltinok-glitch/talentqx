<?php

namespace App\Services\Copilot;

class CopilotGuardrails
{
    /**
     * Patterns for blocked intents with their respective responses.
     */
    private array $blockedPatterns = [
        // Hiring decision
        '/should (i|we) hire/i' => [
            'reason' => 'hiring_decision',
            'response' => 'I can provide analysis and insights, but hiring decisions must be made by authorized personnel.',
        ],
        '/hire or (not|reject)/i' => [
            'reason' => 'hiring_decision',
            'response' => 'I can provide analysis and insights, but hiring decisions must be made by authorized personnel.',
        ],
        '/(must|need to|have to) (hire|reject)/i' => [
            'reason' => 'hiring_decision',
            'response' => 'I can provide analysis and insights, but hiring decisions must be made by authorized personnel.',
        ],
        '/is (this|the) candidate (good|bad|suitable)/i' => [
            'reason' => 'hiring_decision',
            'response' => 'I can provide objective analysis of the candidate\'s assessment results, but the final suitability judgment should be made by you.',
        ],

        // Salary recommendations
        '/what salary/i' => [
            'reason' => 'salary_recommendation',
            'response' => 'Salary decisions involve many factors beyond assessment scope. Please consult your compensation guidelines or HR team for salary decisions. I can help with assessment-related questions like competency analysis or candidate comparison.',
        ],
        '/how much (to pay|should.*earn|should.*make|to offer)/i' => [
            'reason' => 'salary_recommendation',
            'response' => 'Salary decisions involve many factors beyond assessment scope. Please consult your compensation guidelines or HR team for salary decisions. I can help with assessment-related questions like competency analysis or candidate comparison.',
        ],
        '/salary (recommendation|suggestion|range)/i' => [
            'reason' => 'salary_recommendation',
            'response' => 'Salary decisions involve many factors beyond assessment scope. Please consult your compensation guidelines or HR team for salary decisions.',
        ],
        '/recommend.*salary/i' => [
            'reason' => 'salary_recommendation',
            'response' => 'I\'m not able to provide salary recommendations as this involves many factors outside the assessment scope.',
        ],

        // Protected characteristics - discrimination
        '/is (he|she|they|the candidate) (married|pregnant|religious|disabled)/i' => [
            'reason' => 'discrimination',
            'response' => 'I cannot make assessments based on protected characteristics. I can only analyze job-related competencies and assessment results.',
        ],
        '/\b(age|ethnicity|nationality|disability|religion|gender|race)\b.*\b(factor|consider|affect|impact)\b/i' => [
            'reason' => 'discrimination',
            'response' => 'I cannot make assessments based on protected characteristics. I can only analyze job-related competencies and assessment results.',
        ],
        '/\bwhat is (his|her|their) (age|ethnicity|nationality|religion|gender|race)\b/i' => [
            'reason' => 'pii_inference',
            'response' => 'I can only work with data already in the system. I cannot infer personal information not provided in the assessment.',
        ],

        // Legal advice
        '/legal (advice|opinion|recommendation|counsel)/i' => [
            'reason' => 'legal_advice',
            'response' => 'For legal questions, please consult your legal department or HR compliance team.',
        ],
        '/(can we|is it legal|legally allowed)/i' => [
            'reason' => 'legal_advice',
            'response' => 'For legal questions about hiring practices, please consult your legal department or HR compliance team.',
        ],
        '/sue|lawsuit|litigation/i' => [
            'reason' => 'legal_advice',
            'response' => 'For legal questions, please consult your legal department or HR compliance team.',
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
        '/\$[\d,]+(\.\d{2})?\s*(per|\/)\s*(year|month|hour|annually)/i', // Salary figures
    ];

    /**
     * Validate user input before processing.
     */
    public function validateInput(string $message): GuardrailResult
    {
        $message = trim($message);

        // Check for empty input
        if (empty($message)) {
            return GuardrailResult::blocked(
                'empty_input',
                'Please provide a message to analyze.'
            );
        }

        // Check for excessively long input
        if (strlen($message) > 2000) {
            return GuardrailResult::blocked(
                'input_too_long',
                'Your message is too long. Please keep it under 2000 characters.'
            );
        }

        // Check against blocked patterns
        foreach ($this->blockedPatterns as $pattern => $config) {
            if (preg_match($pattern, $message)) {
                return GuardrailResult::blocked(
                    $config['reason'],
                    $config['response']
                );
            }
        }

        return GuardrailResult::allowed();
    }

    /**
     * Filter and sanitize AI output before returning to user.
     */
    public function filterOutput(string $response): string
    {
        // Check for definitive hiring statements and add disclaimer
        foreach ($this->outputBlockPatterns as $pattern) {
            if (preg_match($pattern, $response)) {
                // Instead of blocking, we add a disclaimer
                $response = $this->addDisclaimer($response);
                break;
            }
        }

        // Remove any inadvertent PII patterns
        $response = $this->sanitizePII($response);

        return $response;
    }

    /**
     * Validate that the output doesn't make definitive hiring decisions.
     */
    public function validateOutput(string $response): GuardrailResult
    {
        // Check for critical violations that should be blocked entirely
        $criticalPatterns = [
            '/you must (hire|reject) this candidate/i',
            '/I have decided to (hire|reject)/i',
        ];

        foreach ($criticalPatterns as $pattern) {
            if (preg_match($pattern, $response)) {
                return GuardrailResult::blocked(
                    'definitive_hiring_statement',
                    'The response contained a definitive hiring statement which is not allowed.'
                );
            }
        }

        return GuardrailResult::allowed();
    }

    /**
     * Classify the intent of a user message.
     */
    public function classifyIntent(string $message): string
    {
        $message = strtolower($message);

        // Pattern-based intent classification
        $intentPatterns = [
            'candidate_analysis' => [
                '/analyz/i', '/assess/i', '/evaluat/i', '/review.*candidate/i',
                '/candidate.*score/i', '/competenc/i',
            ],
            'risk_summarization' => [
                '/risk/i', '/red flag/i', '/concern/i', '/warning/i',
                '/issue/i', '/problem/i',
            ],
            'candidate_comparison' => [
                '/compar/i', '/versus/i', '/vs\./i', '/between.*candidate/i',
                '/which.*candidate/i', '/better.*candidate/i',
            ],
            'decision_guidance' => [
                '/insight/i', '/recommend/i', '/suggest/i', '/advice/i',
                '/what.*think/i', '/your.*opinion/i',
            ],
            'interview_preparation' => [
                '/question.*ask/i', '/interview.*question/i', '/follow.*up/i',
                '/ask.*candidate/i', '/prepare.*interview/i',
            ],
            'training_needs' => [
                '/training/i', '/develop/i', '/improve/i', '/learn/i',
                '/onboard/i', '/skill.*gap/i',
            ],
            'score_interpretation' => [
                '/what.*mean/i', '/interpret/i', '/explain.*score/i',
                '/score.*mean/i', '/understand.*result/i',
            ],
            'red_flag_explanation' => [
                '/explain.*flag/i', '/why.*flag/i', '/detail.*flag/i',
                '/flag.*mean/i',
            ],
            'batch_analysis' => [
                '/top.*candidate/i', '/all.*candidate/i', '/summari.*candidate/i',
                '/rank/i', '/list.*candidate/i',
            ],
            'position_fit' => [
                '/fit.*position/i', '/position.*fit/i', '/match.*job/i',
                '/suitable.*role/i', '/role.*match/i',
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
     * Remove or mask potential PII from responses.
     */
    private function sanitizePII(string $response): string
    {
        // Mask potential SSN patterns
        $response = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[REDACTED]', $response);

        // Mask potential credit card numbers
        $response = preg_replace('/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', '[REDACTED]', $response);

        // Mask potential phone numbers (various formats)
        $response = preg_replace('/\b(\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b/', '[PHONE]', $response);

        // Mask email addresses (only if they appear to be leaked, not in context)
        // We're careful here not to remove intentionally included emails
        // $response = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '[EMAIL]', $response);

        return $response;
    }

    /**
     * Get the reason message for a blocked intent.
     */
    public function getBlockReasonMessage(string $reason): string
    {
        $messages = [
            'hiring_decision' => 'I can provide analysis and insights, but hiring decisions must be made by authorized personnel.',
            'salary_recommendation' => 'Salary decisions involve many factors. Please consult your compensation guidelines.',
            'legal_advice' => 'For legal questions, please consult your legal department or HR compliance team.',
            'pii_inference' => 'I can only work with data already in the system. I cannot infer personal information.',
            'discrimination' => 'I cannot make assessments based on protected characteristics.',
            'empty_input' => 'Please provide a message to analyze.',
            'input_too_long' => 'Your message is too long. Please keep it under 2000 characters.',
        ];

        return $messages[$reason] ?? 'This type of request is not supported.';
    }
}
