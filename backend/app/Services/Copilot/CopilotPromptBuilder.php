<?php

namespace App\Services\Copilot;

class CopilotPromptBuilder
{
    /**
     * JSON output schema for structured responses.
     */
    private const OUTPUT_SCHEMA = <<<'SCHEMA'
{
  "answer": "Your main response text (can include markdown formatting)",
  "confidence": "low|medium|high",
  "category": "candidate_analysis|comparison|decision_guidance|system_help|unknown",
  "bullets": ["Key point 1", "Key point 2"],
  "risks": ["Risk or concern 1", "Risk or concern 2"],
  "next_best_actions": ["Suggested action 1", "Suggested action 2"],
  "needs_human": false
}
SCHEMA;

    /**
     * Build the system prompt for the copilot.
     * Enforces structured JSON output format.
     */
    public function buildSystemPrompt(array $context): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $schema = self::OUTPUT_SCHEMA;

        return <<<PROMPT
You are TalentQX Copilot, an AI assistant helping HR professionals analyze candidate assessments and make informed hiring decisions.

## STRICT OUTPUT FORMAT

You MUST respond ONLY with valid JSON matching this exact schema:
{$schema}

Field requirements:
- "answer": Required string. Your main response with analysis and insights. Can use markdown formatting.
- "confidence": Required. Must be exactly one of: "low", "medium", "high"
- "category": Required. Must be exactly one of: "candidate_analysis", "comparison", "decision_guidance", "system_help", "unknown"
- "bullets": Required array of strings. Key points or summary items. Can be empty array [].
- "risks": Required array of strings. Any risks or concerns identified. Can be empty array [].
- "next_best_actions": Required array of strings. Recommended next steps. Can be empty array [].
- "needs_human": Required boolean. Set true if human review/decision is needed.

NEVER include any text outside the JSON object. NEVER use markdown code blocks around the JSON.

## CAPABILITIES

- Analyze assessment results and competency scores
- Summarize risk indicators and red flags
- Compare candidates based on objective criteria
- Provide data-driven insights for decision making
- Suggest interview questions to explore gaps
- Interpret scores and explain what they mean
- Identify training and development needs

## LIMITATIONS (NEVER DO)

- Make hiring/rejection decisions - always defer to the HR professional
- Provide salary recommendations or compensation advice
- Give legal advice or interpret employment law
- Infer information not present in the provided data
- Make judgments based on protected characteristics (age, gender, ethnicity, religion, etc.)
- Access or reference data not provided in the context

## RESPONSE GUIDELINES

- Be concise and professional
- Use bullet points and tables in the "answer" field for clarity
- Cite specific data points and scores when available
- Acknowledge uncertainty when data is incomplete
- Always defer final decisions to the HR professional
- When discussing risk factors, maintain objectivity and avoid alarmist language
- Set "needs_human": true for any decision that requires HR judgment

## SCORING GUIDELINES

- Scores are typically on a 0-100 scale
- 70+ is generally considered meeting threshold for most positions
- 85+ indicates high performer potential
- Below 50 suggests significant gaps requiring attention
- Red flags should be verified through follow-up questions, not used as disqualifiers alone

## CONTEXT DATA (KVKK-Safe, No PII)

The following context contains anonymized data. Candidate names, emails, and other personal identifiers have been removed for privacy compliance.

{$contextJson}
PROMPT;
    }

    /**
     * Build the user prompt with the message and additional instructions.
     * Reinforces JSON output requirement.
     */
    public function buildUserPrompt(string $message, string $intent): string
    {
        $instructions = $this->getIntentInstructions($intent);

        return <<<PROMPT
Based on the context data provided, please help with the following:

{$message}

{$instructions}

Guidelines:
- Only use data from the provided context
- Be specific with scores and metrics when available
- Highlight both strengths and concerns objectively
- Do not make the final hiring decision - that's for the HR professional

IMPORTANT: Respond with valid JSON only, following the exact schema from the system prompt.
PROMPT;
    }

    /**
     * Build input array for OpenAI Responses API.
     * This format is used by the /v1/responses endpoint.
     */
    public function buildInputArray(
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        $input = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history (limit to last 6 messages for context window)
        $recentHistory = array_slice($conversationHistory, -6);
        foreach ($recentHistory as $msg) {
            $input[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        // Add current user message
        $input[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $input;
    }

    /**
     * Build prompt for candidate analysis.
     */
    public function buildCandidateAnalysisPrompt(array $context): string
    {
        $hasAssessment = $context['available_data']['has_assessment'] ?? false;
        $hasInterview = $context['available_data']['has_interview'] ?? false;

        $prompt = "Analyze this candidate based on the available data.\n\n";

        if ($hasAssessment) {
            $prompt .= "Include:\n";
            $prompt .= "- Overall assessment summary\n";
            $prompt .= "- Key competency strengths\n";
            $prompt .= "- Areas of concern or gaps\n";
            $prompt .= "- Risk factors to consider\n";
        }

        if ($hasInterview) {
            $prompt .= "- Interview performance notes\n";
        }

        $prompt .= "\nProvide actionable insights for the HR professional.";

        return $prompt;
    }

    /**
     * Build prompt for candidate comparison.
     */
    public function buildComparisonPrompt(array $context): string
    {
        $candidateCount = count($context['candidates'] ?? []);
        $position = $context['position']['title'] ?? 'the position';

        return <<<PROMPT
Compare these {$candidateCount} candidates for the {$position} position.

Create a comparison table showing:
- Overall scores
- Key competency differences
- Risk flag counts
- Strengths and weaknesses

Then provide:
- Key differentiators between candidates
- Specific areas where each candidate excels
- Any notable concerns for each candidate

Remember: Do not recommend who to hire - present the objective data and let the HR professional decide.
PROMPT;
    }

    /**
     * Build prompt for risk summarization.
     */
    public function buildRiskSummaryPrompt(array $context): string
    {
        $riskCount = count($context['risk_factors'] ?? []);

        return <<<PROMPT
Summarize the risk factors identified for this candidate.

For each risk factor:
1. Describe what was flagged
2. Explain the potential implications
3. Suggest follow-up actions or questions

There are {$riskCount} risk factors identified. Be thorough but balanced - these are concerns to investigate, not automatic disqualifiers.
PROMPT;
    }

    /**
     * Build prompt for interview preparation.
     */
    public function buildInterviewPrepPrompt(array $context): string
    {
        return <<<PROMPT
Based on the assessment gaps and concerns identified, suggest follow-up interview questions.

For each area of concern:
1. Identify the specific gap or concern
2. Provide 2-3 targeted questions to explore it further
3. Explain what to look for in the candidate's response

Focus on questions that:
- Verify understanding vs. memorized answers
- Explore real-world application of skills
- Assess soft skills and situational judgment
PROMPT;
    }

    /**
     * Build prompt for training needs assessment.
     */
    public function buildTrainingNeedsPrompt(array $context): string
    {
        return <<<PROMPT
Based on the competency assessment, identify training and development needs.

Categorize recommendations by priority:
- Priority 1 (Immediate): Critical gaps that need addressing before or immediately after hire
- Priority 2 (Within 30 days): Important areas for early development
- Priority 3 (Ongoing): Areas for continued growth

For each area:
- Identify the specific competency gap
- Suggest appropriate training or development activities
- Note any strengths that don't require training

Also estimate relative onboarding time compared to a standard new hire.
PROMPT;
    }

    /**
     * Build prompt for score interpretation.
     */
    public function buildScoreInterpretationPrompt(float $score): string
    {
        return <<<PROMPT
Explain what a score of {$score}/100 means in the context of this assessment.

Include:
- Position relative to typical thresholds (70 minimum, 85 high performer)
- Percentile interpretation if applicable
- How the score breaks down into components
- What this score typically indicates about candidate performance
- Context for decision-making (e.g., "meets requirements with standard support")
PROMPT;
    }

    /**
     * Build prompt for position fit analysis.
     */
    public function buildPositionFitPrompt(array $context): string
    {
        $position = $context['position']['title'] ?? 'the position';

        return <<<PROMPT
Analyze how well this candidate fits the requirements for {$position}.

Create a competency match table showing:
- Each required competency
- Its weight/importance
- Candidate's score
- Match assessment (Strong, Adequate, Below threshold)

Then provide:
- Overall fit score calculation
- Key observations about the match
- Comparison to position average if available
- Areas that may need additional exploration
PROMPT;
    }

    /**
     * Build prompt for batch/top candidates analysis.
     */
    public function buildBatchAnalysisPrompt(array $context, int $limit = 5): string
    {
        $position = $context['position']['title'] ?? 'the position';

        return <<<PROMPT
Summarize the top {$limit} candidates for {$position}.

Create a ranking table with:
- Rank
- Candidate ID
- Overall Score
- Key Strengths
- Notable Concerns

Then provide quick insights:
- Who stands out and why
- Any patterns across candidates
- Recommendations for which candidates warrant deeper review

Remember: Present the data objectively for the HR professional to make final decisions.
PROMPT;
    }

    /**
     * Get intent-specific instructions.
     */
    private function getIntentInstructions(string $intent): string
    {
        return match ($intent) {
            'candidate_analysis' => 'Focus on providing a comprehensive analysis of the candidate\'s assessment results, highlighting both strengths and areas of concern.',
            'risk_summarization' => 'Focus on clearly explaining each risk factor, its severity, and what follow-up actions might be appropriate.',
            'candidate_comparison' => 'Focus on objective, data-driven comparison. Use tables where helpful. Don\'t recommend a specific candidate.',
            'decision_guidance' => 'Provide data-driven insights but always emphasize that the final decision rests with the HR professional.',
            'interview_preparation' => 'Suggest specific, targeted questions that address gaps identified in the assessment.',
            'training_needs' => 'Categorize development needs by priority and provide actionable training suggestions.',
            'score_interpretation' => 'Explain what the scores mean in practical terms, with context for decision-making.',
            'red_flag_explanation' => 'Explain each flag clearly, what triggered it, and its implications. Maintain balance - flags are for investigation, not automatic rejection.',
            'batch_analysis' => 'Summarize multiple candidates efficiently using tables and brief insights.',
            'position_fit' => 'Analyze the match between candidate competencies and position requirements.',
            default => 'Provide helpful, objective analysis based on the available data.',
        };
    }

    /**
     * Build conversation context from previous messages.
     */
    public function buildConversationContext(array $messages): array
    {
        $context = [];

        foreach ($messages as $message) {
            $context[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        return $context;
    }

    /**
     * Build messages array for OpenAI API.
     */
    public function buildMessagesArray(
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history (limit to last 10 messages to manage context)
        $recentHistory = array_slice($conversationHistory, -10);
        foreach ($recentHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }
}
