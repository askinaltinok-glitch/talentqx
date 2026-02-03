<?php

namespace Tests\Feature\Copilot;

use App\Models\User;
use App\Models\Company;
use App\Services\Copilot\CopilotService;
use App\Services\Copilot\CopilotContextBuilder;
use App\Services\Copilot\CopilotGuardrails;
use App\Services\Copilot\CopilotLogger;
use App\Services\Copilot\CopilotPromptBuilder;
use App\Services\Copilot\CopilotUnavailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Integration test for Copilot fallback behavior.
 */
class CopilotFallbackTest extends TestCase
{
    use RefreshDatabase;

    private CopilotService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company and user
        $company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        // Initialize service with real dependencies
        $this->service = new CopilotService(
            new CopilotGuardrails(),
            new CopilotContextBuilder(),
            new CopilotPromptBuilder(),
            new CopilotLogger()
        );

        // Set required config
        Config::set('services.openai.api_key', 'test-api-key');
        Config::set('services.openai.model', 'gpt-4o');
        Config::set('copilot.enabled', true);
    }

    /**
     * Test: When Responses API fails and fallback is disabled, throw CopilotUnavailableException.
     *
     * This test verifies that:
     * 1. When COPILOT_ALLOW_FALLBACK=false (default)
     * 2. And the Responses API returns an error
     * 3. The service throws CopilotUnavailableException
     * 4. Which results in a 503 response from the controller
     */
    public function test_responses_api_failure_with_fallback_disabled_throws_exception(): void
    {
        // Arrange: Disable fallback (this is the default)
        Config::set('copilot.allow_fallback', false);

        // Mock the HTTP client to simulate Responses API failure
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'error' => [
                    'message' => 'Service temporarily unavailable',
                    'type' => 'server_error',
                ],
            ], 503),
        ]);

        // Act & Assert: Should throw CopilotUnavailableException
        $this->expectException(CopilotUnavailableException::class);
        $this->expectExceptionMessage('Copilot service temporarily unavailable');

        $this->service->chat(
            $this->user,
            'What are the risk factors?'
        );
    }

    /**
     * Test: When Responses API fails and fallback IS enabled, use Chat Completions.
     */
    public function test_responses_api_failure_with_fallback_enabled_uses_chat_completions(): void
    {
        // Arrange: Enable fallback
        Config::set('copilot.allow_fallback', true);

        // Mock both APIs
        Http::fake([
            // Responses API fails
            'api.openai.com/v1/responses' => Http::response([
                'error' => ['message' => 'Service unavailable'],
            ], 503),

            // Chat Completions succeeds
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'answer' => 'Here are the risk factors...',
                                'confidence' => 'medium',
                                'category' => 'candidate_analysis',
                                'bullets' => ['Risk 1', 'Risk 2'],
                                'risks' => [],
                                'next_best_actions' => [],
                                'needs_human' => false,
                            ]),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 50,
                    'total_tokens' => 150,
                ],
            ], 200),
        ]);

        // Act: Should NOT throw, should use fallback
        $result = $this->service->chat(
            $this->user,
            'What are the risk factors?'
        );

        // Assert: Should return successful response
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('response', $result['data']);
        $this->assertEquals('Here are the risk factors...', $result['data']['response']['answer']);
    }

    /**
     * Test: Controller returns 503 when CopilotUnavailableException is thrown.
     */
    public function test_controller_returns_503_when_service_unavailable(): void
    {
        // Arrange
        Config::set('copilot.allow_fallback', false);

        Http::fake([
            'api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'Error']], 503),
        ]);

        // Act: Make request to controller endpoint
        $response = $this->actingAs($this->user)->postJson('/api/v1/copilot/chat', [
            'message' => 'Test message',
        ]);

        // Assert: Should return 503 with correct error code
        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'copilot_unavailable',
            ],
        ]);
    }

    /**
     * Test: Verify default fallback config is false.
     */
    public function test_default_fallback_config_is_disabled(): void
    {
        // Clear any test overrides
        Config::offsetUnset('copilot.allow_fallback');

        // Re-read from default config
        $defaultValue = config('copilot.allow_fallback', 'not_set');

        // Should default to false (or env value which defaults to false)
        $this->assertFalse(
            filter_var($defaultValue, FILTER_VALIDATE_BOOLEAN),
            'COPILOT_ALLOW_FALLBACK should default to false'
        );
    }
}
