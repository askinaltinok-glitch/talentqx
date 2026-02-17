<?php

namespace App\Services\Copilot;

use App\Models\CopilotConversation;
use App\Models\CopilotMessage;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CopilotService
{
    private string $apiKey;
    private string $model;
    private int $timeout;

    public function __construct(
        private CopilotGuardrails $guardrails,
        private CopilotContextBuilder $contextBuilder,
        private CopilotPromptBuilder $promptBuilder,
        private CopilotLogger $logger,
        private RedFlagActionService $actionService
    ) {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o');
        $this->timeout = config('services.openai.timeout', 120);
    }

    /**
     * Check if Copilot is enabled.
     */
    public function isEnabled(): bool
    {
        return config('copilot.enabled', true) && !empty($this->apiKey);
    }

    /**
     * Process a chat message and return AI response.
     */
    public function chat(
        User $user,
        string $message,
        ?array $contextSpec = null,
        ?string $conversationId = null,
        string $locale = 'tr'
    ): array {
        // Set locale for prompts
        $this->promptBuilder->setLocale($locale);
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'COPILOT_DISABLED',
                    'message' => 'AI Copilot is not available.',
                ],
            ];
        }

        $startTime = microtime(true);

        // Get or create conversation
        $conversation = $this->getOrCreateConversation(
            $user,
            $conversationId,
            $contextSpec
        );

        // Log the request (KVKK-safe: no message content)
        $this->logger->logRequest($user, $conversation->id, $contextSpec);

        // Step 1: Validate input with guardrails
        $inputValidation = $this->guardrails->validateInput($message);

        if ($inputValidation->isBlocked()) {
            return $this->handleBlockedInput(
                $user,
                $conversation,
                $message,
                $inputValidation
            );
        }

        // Step 2: Classify intent
        $intent = $this->guardrails->classifyIntent($message);

        // Step 3: Build KVKK-safe context (no PII)
        $context = $this->buildContextForRequest($user, $contextSpec);

        // Log context access for audit (type + id only, no PII)
        if ($contextSpec) {
            $this->logger->logContextAccess(
                $user,
                $conversation->id,
                $contextSpec['type'],
                $contextSpec['id']
            );
        }

        // Step 4: Build prompts (structured output enforced)
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($context);
        $userPrompt = $this->promptBuilder->buildUserPrompt($message, $intent, $context);

        // Step 5: Get conversation history for context (limited, no PII)
        $conversationHistory = $conversation->getHistoryForPrompt(6);

        // Step 6: Build input array for Responses API
        $input = $this->promptBuilder->buildInputArray(
            $systemPrompt,
            $conversationHistory,
            $userPrompt
        );

        // Step 7: Save user message (content stored for history)
        CopilotMessage::createUserMessage(
            $conversation->id,
            $message,
            $this->buildContextSnapshot($contextSpec)
        );

        try {
            // Step 8: Call OpenAI Responses API
            $response = $this->callOpenAI($input);

            // Step 9: Parse structured JSON output
            $parsedResponse = $this->parseStructuredResponse($response['content']);

            // Step 10: Filter output with guardrails
            $filteredAnswer = $this->guardrails->filterOutput($parsedResponse['answer']);
            $parsedResponse['answer'] = $filteredAnswer;

            // Step 11: Validate output
            $outputValidation = $this->guardrails->validateOutput($filteredAnswer);

            if ($outputValidation->isBlocked()) {
                $parsedResponse = $this->getFallbackResponse('Response filtered by guardrails.');
                $parsedResponse['needs_human'] = true;
            }

            // Step 11.5: Enrich with recommended actions based on red flags
            $parsedResponse = $this->actionService->enrichWithActions($parsedResponse);

            // Step 12: Calculate latency and costs
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            $tokenUsage = $response['usage'] ?? [];
            $estimatedCost = $this->logger->calculateEstimatedCost(
                $tokenUsage['input_tokens'] ?? $tokenUsage['prompt_tokens'] ?? 0,
                $tokenUsage['output_tokens'] ?? $tokenUsage['completion_tokens'] ?? 0,
                $this->model
            );

            // Step 13: Save assistant message
            $metadata = [
                'model' => $this->model,
                'input_tokens' => $tokenUsage['input_tokens'] ?? $tokenUsage['prompt_tokens'] ?? null,
                'output_tokens' => $tokenUsage['output_tokens'] ?? $tokenUsage['completion_tokens'] ?? null,
                'total_tokens' => $tokenUsage['total_tokens'] ?? null,
                'latency_ms' => $latencyMs,
                'estimated_cost_usd' => $estimatedCost,
                'structured' => true,
            ];

            $assistantMessage = CopilotMessage::createAssistantMessage(
                $conversation->id,
                json_encode($parsedResponse, JSON_UNESCAPED_UNICODE),
                $this->buildContextSnapshot($contextSpec),
                $metadata
            );

            // Update conversation
            $conversation->touchLastMessage();
            $conversation->generateTitle();

            // Log response (no content, only metadata)
            $this->logger->logResponse($conversation->id, $assistantMessage->id, $metadata, $intent);

            return [
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message_id' => $assistantMessage->id,
                    'response' => $parsedResponse,
                    'context_type' => $contextSpec['type'] ?? null,
                    'guardrail_triggered' => false,
                    'metadata' => [
                        'latency_ms' => $latencyMs,
                        'tokens_used' => $tokenUsage['total_tokens'] ?? null,
                    ],
                ],
            ];
        } catch (Exception $e) {
            $this->logger->logError(
                $conversation->id,
                'api_error',
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Get context preview for an entity.
     */
    public function getContextPreview(
        User $user,
        string $type,
        string $id
    ): array {
        return $this->contextBuilder->getContextPreview($type, $id, $user->company_id);
    }

    /**
     * Get conversation history.
     */
    public function getHistory(
        User $user,
        ?string $conversationId = null,
        int $limit = 20
    ): array {
        $query = CopilotConversation::where('user_id', $user->id)
            ->where('company_id', $user->company_id);

        if ($conversationId) {
            $conversation = $query->find($conversationId);

            if (!$conversation) {
                return [
                    'success' => false,
                    'error' => 'Conversation not found',
                ];
            }

            $messages = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();

            return [
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'title' => $conversation->title,
                    'context_type' => $conversation->context_type,
                    'context_id' => $conversation->context_id,
                    'messages' => $messages->map(fn($m) => [
                        'id' => $m->id,
                        'role' => $m->role,
                        'content' => $m->role === 'assistant' ? json_decode($m->content, true) : $m->content,
                        'guardrail_triggered' => $m->guardrail_triggered,
                        'created_at' => $m->created_at->toIso8601String(),
                    ]),
                ],
            ];
        }

        // Return list of conversations
        $conversations = $query->orderByDesc('last_message_at')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'data' => [
                'conversations' => $conversations->map(fn($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'context_type' => $c->context_type,
                    'context_id' => $c->context_id,
                    'last_message_at' => $c->last_message_at?->toIso8601String(),
                    'created_at' => $c->created_at->toIso8601String(),
                ]),
            ],
        ];
    }

    /**
     * Get or create a conversation.
     */
    private function getOrCreateConversation(
        User $user,
        ?string $conversationId,
        ?array $contextSpec
    ): CopilotConversation {
        // Determine company_id - use user's company or derive from context
        $companyId = $user->company_id ?? $this->getCompanyIdFromContext($contextSpec);

        if ($conversationId) {
            $query = CopilotConversation::where('id', $conversationId)
                ->where('user_id', $user->id);

            // Only filter by company_id if it's set
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $conversation = $query->first();

            // Only reuse conversation if context matches (same candidate/interview/job)
            if ($conversation) {
                $contextType = $contextSpec['type'] ?? null;
                $contextId = $contextSpec['id'] ?? null;

                // If context matches, reuse the conversation
                if ($conversation->context_type === $contextType && $conversation->context_id === $contextId) {
                    return $conversation;
                }
                // Context changed - create new conversation instead
            }
        }

        // Create new conversation
        $conversation = CopilotConversation::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'context_type' => $contextSpec['type'] ?? null,
            'context_id' => $contextSpec['id'] ?? null,
            'last_message_at' => now(),
        ]);

        $this->logger->logConversationCreated(
            $user,
            $conversation->id,
            $contextSpec['type'] ?? null,
            $contextSpec['id'] ?? null
        );

        return $conversation;
    }

    /**
     * Get company_id from context entity.
     */
    private function getCompanyIdFromContext(?array $contextSpec): ?string
    {
        if (!$contextSpec || !isset($contextSpec['type'], $contextSpec['id'])) {
            return null;
        }

        return match ($contextSpec['type']) {
            'candidate' => \App\Models\Candidate::find($contextSpec['id'])?->job?->company_id,
            'interview' => \App\Models\Interview::find($contextSpec['id'])?->job?->company_id,
            'job' => \App\Models\Job::find($contextSpec['id'])?->company_id,
            default => null,
        };
    }

    /**
     * Build KVKK-safe context for request.
     */
    private function buildContextForRequest(User $user, ?array $contextSpec): array
    {
        if (!$contextSpec || !isset($contextSpec['type'], $contextSpec['id'])) {
            return ['type' => 'general', 'available_data' => []];
        }

        // Use user's company_id or derive from context for platform admins
        $companyId = $user->company_id ?? $this->getCompanyIdFromContext($contextSpec);

        return $this->contextBuilder->buildContext(
            $contextSpec['type'],
            $contextSpec['id'],
            $companyId
        );
    }

    /**
     * Handle blocked input from guardrails.
     */
    private function handleBlockedInput(
        User $user,
        CopilotConversation $conversation,
        string $message,
        GuardrailResult $result
    ): array {
        // Log the block (no message content)
        $this->logger->logGuardrailBlock(
            $user,
            $conversation->id,
            $result->reason
        );

        // Create structured blocked response
        $blockedResponse = [
            'answer' => $result->alternativeResponse,
            'confidence' => 'high',
            'category' => 'system_help',
            'bullets' => [],
            'risks' => [],
            'red_flags' => [],
            'risk_level' => 'none',
            'next_best_actions' => ['Rephrase your question to focus on assessment data analysis.'],
            'needs_human' => false,
        ];

        // Save the blocked message
        $blockedMessage = CopilotMessage::createBlockedMessage(
            $conversation->id,
            CopilotMessage::ROLE_ASSISTANT,
            $message,
            json_encode($blockedResponse, JSON_UNESCAPED_UNICODE),
            $result->reason
        );

        $conversation->touchLastMessage();

        return [
            'success' => true,
            'data' => [
                'conversation_id' => $conversation->id,
                'message_id' => $blockedMessage->id,
                'response' => $blockedResponse,
                'context_type' => null,
                'guardrail_triggered' => true,
                'guardrail_reason' => $result->reason,
            ],
        ];
    }

    /**
     * Build context snapshot for message (no PII).
     */
    private function buildContextSnapshot(?array $contextSpec): ?array
    {
        if (!$contextSpec) {
            return null;
        }

        return [
            'type' => $contextSpec['type'],
            'id' => $contextSpec['id'],
        ];
    }

    /**
     * Parse structured JSON response from model.
     * Improved extraction: strips markdown fences and extra text before json_decode.
     */
    private function parseStructuredResponse(string $content): array
    {
        $content = trim($content);

        // Step 1: Remove markdown code blocks (```json ... ``` or ``` ... ```)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $content = trim($matches[1]);
        }

        // Step 2: Try to extract JSON object if there's extra text before/after
        // Look for the first { and last } to extract JSON
        $firstBrace = strpos($content, '{');
        $lastBrace = strrpos($content, '}');

        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $jsonCandidate = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);

            // Try to parse the extracted JSON
            $decoded = json_decode($jsonCandidate, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeStructuredResponse($decoded);
            }
        }

        // Step 3: Try parsing the whole content as-is
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->normalizeStructuredResponse($decoded);
        }

        // Fallback for non-JSON response
        return $this->getFallbackResponse($content);
    }

    /**
     * Normalize structured response to ensure all required fields.
     */
    private function normalizeStructuredResponse(array $data): array
    {
        // Normalize red_flags array
        $redFlags = [];
        if (is_array($data['red_flags'] ?? null)) {
            foreach ($data['red_flags'] as $flag) {
                if (is_array($flag) && isset($flag['code'])) {
                    $redFlags[] = [
                        'code' => $flag['code'] ?? '',
                        'level' => (int) ($flag['level'] ?? 3),
                        'label' => $flag['label'] ?? '',
                        'evidence' => $flag['evidence'] ?? '',
                    ];
                }
            }
        }

        // Calculate risk_level from red_flags if not provided
        $riskLevel = $data['risk_level'] ?? $this->calculateRiskLevel($redFlags);

        // Check if needs_human should be true due to Level-1 flags
        $hasLevel1Flag = collect($redFlags)->contains(fn($f) => $f['level'] === 1);
        $needsHuman = (bool) ($data['needs_human'] ?? false) || $hasLevel1Flag;

        return [
            'answer' => $data['answer'] ?? '',
            'confidence' => in_array($data['confidence'] ?? '', ['low', 'medium', 'high'])
                ? $data['confidence']
                : 'medium',
            'category' => in_array($data['category'] ?? '', [
                'candidate_analysis', 'comparison', 'decision_guidance', 'system_help', 'unknown'
            ]) ? $data['category'] : 'unknown',
            'bullets' => is_array($data['bullets'] ?? null) ? $data['bullets'] : [],
            'risks' => is_array($data['risks'] ?? null) ? $data['risks'] : [],
            'red_flags' => $redFlags,
            'risk_level' => in_array($riskLevel, ['none', 'low', 'medium', 'high']) ? $riskLevel : 'none',
            'next_best_actions' => is_array($data['next_best_actions'] ?? null) ? $data['next_best_actions'] : [],
            'needs_human' => $needsHuman,
        ];
    }

    /**
     * Calculate risk level from red flags array.
     */
    private function calculateRiskLevel(array $redFlags): string
    {
        if (empty($redFlags)) {
            return 'none';
        }

        // Check for any Level-1 (Hard) flags
        $level1Count = collect($redFlags)->filter(fn($f) => $f['level'] === 1)->count();
        if ($level1Count > 0) {
            return 'high';
        }

        // Check for 2+ Level-2 (Pattern) flags
        $level2Count = collect($redFlags)->filter(fn($f) => $f['level'] === 2)->count();
        if ($level2Count >= 2) {
            return 'medium';
        }

        // Only Level-3 (Soft) signals or single Level-2
        return 'low';
    }

    /**
     * Get fallback response for non-JSON or failed parsing.
     */
    private function getFallbackResponse(string $rawText): array
    {
        return [
            'answer' => $rawText,
            'confidence' => 'low',
            'category' => 'unknown',
            'bullets' => [],
            'risks' => [],
            'red_flags' => [],
            'risk_level' => 'none',
            'next_best_actions' => [],
            'needs_human' => true,
        ];
    }

    /**
     * Call OpenAI Responses API.
     *
     * @throws CopilotUnavailableException If API fails and fallback is disabled
     */
    private function callOpenAI(array $input): array
    {
        $payload = [
            'model' => $this->model,
            'input' => $input,
        ];

        // Add structured output instruction if enabled
        if (config('copilot.structured_output', true)) {
            $payload['text'] = [
                'format' => [
                    'type' => 'json_object',
                ],
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)->post('https://api.openai.com/v1/responses', $payload);

        if (!$response->successful()) {
            Log::error('OpenAI Copilot Responses API error', [
                'status' => $response->status(),
                'error' => $response->json('error.message') ?? $response->body(),
            ]);

            // Only fallback if explicitly allowed
            if (config('copilot.allow_fallback', false)) {
                return $this->callOpenAIChatCompletions($input);
            }

            // Fallback disabled - throw specific exception for 503 response
            throw new CopilotUnavailableException('Copilot service temporarily unavailable');
        }

        $data = $response->json();

        // Parse Responses API format
        $content = $data['output_text']
            ?? $data['output'][0]['content'][0]['text']
            ?? $data['output'][0]['content']
            ?? '';

        return [
            'content' => $content,
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model,
        ];
    }

    /**
     * Fallback to Chat Completions API.
     */
    private function callOpenAIChatCompletions(array $input): array
    {
        // Convert input array to messages format
        $messages = [];
        foreach ($input as $item) {
            $messages[] = [
                'role' => $item['role'],
                'content' => $item['content'],
            ];
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ];

        // Add JSON mode if structured output enabled
        if (config('copilot.structured_output', true)) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('OpenAI Copilot Chat Completions error', [
                'status' => $response->status(),
                'error' => $response->json('error.message') ?? $response->body(),
            ]);
            throw new Exception('OpenAI API error: ' . $response->status());
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model,
        ];
    }
}
