<?php

namespace App\Services\Copilot;

class CopilotPromptBuilder
{
    /**
     * Build the system prompt for the copilot.
     */
    public function buildSystemPrompt(array $context): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are TalentQX Copilot, an AI assistant helping HR professionals analyze candidate assessments and make informed hiring decisions.

CAPABILITIES:
- Analyze assessment results and competency scores
- Summarize risk indicators and red flags
- Compare candidates based on objective criteria
- Provide data-driven insights for decision making
- Suggest interview questions to explore gaps
- Interpret scores and explain what they mean
- Identify training and development needs

LIMITATIONS (NEVER DO):
- Make hiring/rejection decisions - always defer to the HR professional
- Provide salary recommendations or compensation advice
- Give legal advice or interpret employment law
- Infer information not present in the provided data
- Make judgments based on protected characteristics (age, gender, ethnicity, religion, etc.)
- Access or reference data not provided in the context

RESPONSE STYLE:
- Be concise and professional
- Use bullet points and tables for clarity when appropriate
- Cite specific data points and scores when available
- Acknowledge uncertainty when data is incomplete
- Always defer final decisions to the HR professional
- Use markdown formatting for readability
- When discussing risk factors, maintain objectivity and avoid alarmist language

SCORING GUIDELINES:
- Scores are typically on a 0-100 scale
- 70+ is generally considered meeting threshold for most positions
- 85+ indicates high performer potential
- Below 50 suggests significant gaps requiring attention
- Red flags should be verified through follow-up questions, not used as disqualifiers alone

CONTEXT DATA:
{$contextJson}
PROMPT;
    }

    /**
     * Build the user prompt with the message and additional instructions.
     */
    public function buildUserPrompt(string $message, string $intent): string
    {
        $instructions = $this->getIntentInstructions($intent);

        return <<<PROMPT
Based on the context data provided, please help with the following:

{$message}

{$instructions}

Remember to:
- Only use data from the provided context
- Be specific with scores and metrics when available
- Highlight both strengths and concerns objectively
- Do not make the final hiring decision - that's for the HR professional
PROMPT;
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
