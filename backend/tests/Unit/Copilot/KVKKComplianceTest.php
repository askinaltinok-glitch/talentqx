<?php

namespace Tests\Unit\Copilot;

use App\Services\Copilot\CopilotContextBuilder;
use App\Services\Copilot\CopilotGuardrails;
use App\Services\Copilot\CopilotPromptBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * KVKK Compliance Tests for AI Copilot
 *
 * These tests verify that the Copilot services do NOT expose
 * Personally Identifiable Information (PII) to the LLM.
 *
 * PII includes: names, emails, phone numbers, addresses, TC kimlik no
 */
class KVKKComplianceTest extends TestCase
{
    private CopilotGuardrails $guardrails;
    private CopilotPromptBuilder $promptBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guardrails = new CopilotGuardrails();
        $this->promptBuilder = new CopilotPromptBuilder();
    }

    /**
     * Test that guardrails filter PII patterns from output.
     */
    public function test_guardrails_filters_email_patterns(): void
    {
        $textWithEmail = 'Contact John at john.doe@example.com for details.';
        $filtered = $this->guardrails->filterOutput($textWithEmail);

        $this->assertStringNotContainsString('john.doe@example.com', $filtered);
        $this->assertStringContainsString('[EMAIL]', $filtered);
    }

    /**
     * Test that guardrails filter phone number patterns.
     */
    public function test_guardrails_filters_phone_patterns(): void
    {
        $textWithPhone = 'Call me at +90 532 123 4567 or 0212-555-1234';
        $filtered = $this->guardrails->filterOutput($textWithPhone);

        $this->assertStringNotContainsString('532 123 4567', $filtered);
        $this->assertStringNotContainsString('555-1234', $filtered);
        $this->assertStringContainsString('[PHONE]', $filtered);
    }

    /**
     * Test that guardrails filter Turkish ID number patterns.
     */
    public function test_guardrails_filters_tc_kimlik_patterns(): void
    {
        $textWithTCKN = 'TC Kimlik: 12345678901';
        $filtered = $this->guardrails->filterOutput($textWithTCKN);

        $this->assertStringNotContainsString('12345678901', $filtered);
        $this->assertStringContainsString('[TC_KIMLIK]', $filtered);
    }

    /**
     * Test that system prompt includes KVKK notice.
     */
    public function test_system_prompt_includes_kvkk_notice(): void
    {
        $context = ['type' => 'general', 'available_data' => []];
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($context);

        $this->assertStringContainsString('KVKK', $systemPrompt);
        $this->assertStringContainsString('anonymized', $systemPrompt);
        $this->assertStringContainsString('personal identifiers have been removed', $systemPrompt);
    }

    /**
     * Test that structured output schema is enforced.
     */
    public function test_system_prompt_enforces_json_schema(): void
    {
        $context = ['type' => 'general', 'available_data' => []];
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($context);

        // Check that the JSON schema fields are mentioned
        $this->assertStringContainsString('"answer"', $systemPrompt);
        $this->assertStringContainsString('"confidence"', $systemPrompt);
        $this->assertStringContainsString('"category"', $systemPrompt);
        $this->assertStringContainsString('"bullets"', $systemPrompt);
        $this->assertStringContainsString('"risks"', $systemPrompt);
        $this->assertStringContainsString('"next_best_actions"', $systemPrompt);
        $this->assertStringContainsString('"needs_human"', $systemPrompt);

        // Check that JSON-only instruction is present
        $this->assertStringContainsString('ONLY with valid JSON', $systemPrompt);
    }

    /**
     * Test that user prompt reinforces JSON output requirement.
     */
    public function test_user_prompt_reinforces_json_format(): void
    {
        $userPrompt = $this->promptBuilder->buildUserPrompt('Test message', 'candidate_analysis');

        $this->assertStringContainsString('valid JSON', $userPrompt);
    }

    /**
     * Test that hiring decision queries are blocked.
     */
    public function test_blocks_hiring_decision_queries(): void
    {
        $blockedQueries = [
            'Should I hire this candidate?',
            'Should we hire John?',
            'Hire or reject this person?',
            'Is this candidate good enough to hire?',
        ];

        foreach ($blockedQueries as $query) {
            $result = $this->guardrails->validateInput($query);
            $this->assertTrue(
                $result->isBlocked(),
                "Query should be blocked: {$query}"
            );
        }
    }

    /**
     * Test that salary queries are blocked.
     */
    public function test_blocks_salary_queries(): void
    {
        $blockedQueries = [
            'What salary should we offer?',
            'How much should this candidate earn?',
            'What is the appropriate compensation?',
        ];

        foreach ($blockedQueries as $query) {
            $result = $this->guardrails->validateInput($query);
            $this->assertTrue(
                $result->isBlocked(),
                "Query should be blocked: {$query}"
            );
        }
    }

    /**
     * Test that discrimination-related queries are blocked.
     */
    public function test_blocks_discrimination_queries(): void
    {
        $blockedQueries = [
            'Is she married?',
            'Is he religious?',
            'What is their ethnicity?',
            'How old is the candidate?',
        ];

        foreach ($blockedQueries as $query) {
            $result = $this->guardrails->validateInput($query);
            $this->assertTrue(
                $result->isBlocked(),
                "Query should be blocked: {$query}"
            );
        }
    }

    /**
     * Test that legitimate analysis queries are allowed.
     */
    public function test_allows_legitimate_analysis_queries(): void
    {
        $allowedQueries = [
            'What are the risk factors for this candidate?',
            'Compare the assessment scores',
            'What training would this candidate need?',
            'Explain the competency gaps',
            'What follow-up questions should I ask?',
        ];

        foreach ($allowedQueries as $query) {
            $result = $this->guardrails->validateInput($query);
            $this->assertFalse(
                $result->isBlocked(),
                "Query should be allowed: {$query}"
            );
        }
    }

    /**
     * Test intent classification.
     */
    public function test_intent_classification(): void
    {
        $cases = [
            'What are the risks?' => 'risk_summarization',
            'Compare candidates A and B' => 'candidate_comparison',
            'Analyze this candidate' => 'candidate_analysis',
            'What questions should I ask?' => 'interview_preparation',
            'What training is needed?' => 'training_needs',
        ];

        foreach ($cases as $query => $expectedIntent) {
            $intent = $this->guardrails->classifyIntent($query);
            $this->assertEquals(
                $expectedIntent,
                $intent,
                "Query '{$query}' should classify as '{$expectedIntent}'"
            );
        }
    }

    /**
     * Test that buildInputArray returns proper format for Responses API.
     */
    public function test_build_input_array_format(): void
    {
        $systemPrompt = 'System prompt content';
        $history = [
            ['role' => 'user', 'content' => 'Previous question'],
            ['role' => 'assistant', 'content' => '{"answer": "Previous answer"}'],
        ];
        $userMessage = 'New question';

        $input = $this->promptBuilder->buildInputArray($systemPrompt, $history, $userMessage);

        // Should have system, history, and current message
        $this->assertCount(4, $input);

        // First should be system
        $this->assertEquals('system', $input[0]['role']);
        $this->assertEquals($systemPrompt, $input[0]['content']);

        // Last should be current user message
        $this->assertEquals('user', $input[count($input) - 1]['role']);
        $this->assertEquals($userMessage, $input[count($input) - 1]['content']);
    }

    /**
     * Test that conversation history is limited.
     */
    public function test_input_array_limits_history(): void
    {
        $systemPrompt = 'System prompt';
        $history = [];

        // Create 20 messages
        for ($i = 0; $i < 20; $i++) {
            $history[] = ['role' => 'user', 'content' => "Message {$i}"];
        }

        $input = $this->promptBuilder->buildInputArray($systemPrompt, $history, 'Current message');

        // Should be limited: 1 system + 6 history + 1 current = 8 max
        $this->assertLessThanOrEqual(8, count($input));
    }
}
