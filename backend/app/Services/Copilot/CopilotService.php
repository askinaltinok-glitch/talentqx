<?php

namespace App\Services\Copilot;

use App\Models\CopilotConversation;
use App\Models\CopilotMessage;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CopilotService
{
    private string $apiKey;
    private string $model;
    private int $timeout;

    public function __construct(
        private CopilotGuardrails $guardrails,
        private CopilotContextBuilder $contextBuilder,
        private CopilotPromptBuilder $promptBuilder,
        private CopilotLogger $logger
    ) {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4-turbo-preview');
        $this->timeout = config('services.openai.timeout', 120);
    }

    /**
     * Process a chat message and return AI response.
     */
    public function chat(
        User $user,
        string $message,
        ?array $contextSpec = null,
        ?string $conversationId = null
    ): array {
        $startTime = microtime(true);

        // Get or create conversation
        $conversation = $this->getOrCreateConversation(
            $user,
            $conversationId,
            $contextSpec
        );

        // Log the request
        $this->logger->logRequest($user, $conversation->id, $message, $contextSpec);

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

        // Step 3: Build context
        $context = $this->buildContextForRequest($user, $contextSpec);

        // Log context access for audit
        if ($contextSpec) {
            $this->logger->logContextAccess(
                $user,
                $conversation->id,
                $contextSpec['type'],
                $contextSpec['id'],
                array_keys($context)
            );
        }

        // Step 4: Build prompts
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($context);
        $userPrompt = $this->promptBuilder->buildUserPrompt($message, $intent);

        // Step 5: Get conversation history for context
        $conversationHistory = $conversation->getHistoryForPrompt(10);

        // Step 6: Build messages array
        $messages = $this->promptBuilder->buildMessagesArray(
            $systemPrompt,
            $conversationHistory,
            $userPrompt
        );

        // Step 7: Save user message
        $userMessage = CopilotMessage::createUserMessage(
            $conversation->id,
            $message,
            $this->buildContextSnapshot($contextSpec, $context)
        );

        try {
            // Step 8: Call OpenAI API
            $response = $this->callOpenAI($messages);

            // Step 9: Filter output
            $filteredResponse = $this->guardrails->filterOutput($response['content']);

            // Step 10: Validate output
            $outputValidation = $this->guardrails->validateOutput($filteredResponse);

            if ($outputValidation->isBlocked()) {
                // Regenerate with stricter instructions (rare case)
                $filteredResponse = $this->handleBlockedOutput($context, $message);
            }

            // Step 11: Calculate latency and costs
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            $tokenUsage = $response['usage'] ?? [];
            $estimatedCost = $this->logger->calculateEstimatedCost(
                $tokenUsage['prompt_tokens'] ?? 0,
                $tokenUsage['completion_tokens'] ?? 0,
                $this->model
            );

            // Step 12: Save assistant message
            $metadata = [
                'model' => $this->model,
                'prompt_tokens' => $tokenUsage['prompt_tokens'] ?? null,
                'completion_tokens' => $tokenUsage['completion_tokens'] ?? null,
                'total_tokens' => $tokenUsage['total_tokens'] ?? null,
                'latency_ms' => $latencyMs,
                'estimated_cost_usd' => $estimatedCost,
            ];

            $assistantMessage = CopilotMessage::createAssistantMessage(
                $conversation->id,
                $filteredResponse,
                $this->buildContextSnapshot($contextSpec, $context),
                $metadata
            );

            // Update conversation
            $conversation->touchLastMessage();
            $conversation->generateTitle();

            // Log response
            $this->logger->logResponse($conversation->id, $assistantMessage->id, $metadata);
            $this->logger->logApiCost($conversation->id, $estimatedCost, $tokenUsage);

            return [
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message_id' => $assistantMessage->id,
                    'response' => $filteredResponse,
                    'context_used' => $this->buildContextSnapshot($contextSpec, $context),
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
                $e->getMessage(),
                ['message_length' => strlen($message)]
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
                        'content' => $m->content,
                        'context_used' => $m->context_snapshot,
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
        if ($conversationId) {
            $conversation = CopilotConversation::where('id', $conversationId)
                ->where('user_id', $user->id)
                ->where('company_id', $user->company_id)
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        // Create new conversation
        $conversation = CopilotConversation::create([
            'company_id' => $user->company_id,
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
     * Build context for request.
     */
    private function buildContextForRequest(User $user, ?array $contextSpec): array
    {
        if (!$contextSpec || !isset($contextSpec['type'], $contextSpec['id'])) {
            return ['type' => 'general', 'available_data' => []];
        }

        return $this->contextBuilder->buildContext(
            $contextSpec['type'],
            $contextSpec['id'],
            $user->company_id
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
        // Log the block
        $this->logger->logGuardrailBlock(
            $user,
            $conversation->id,
            $result->reason,
            $message
        );

        // Save the blocked message
        $blockedMessage = CopilotMessage::createBlockedMessage(
            $conversation->id,
            CopilotMessage::ROLE_ASSISTANT,
            $message,
            $result->alternativeResponse,
            $result->reason
        );

        $conversation->touchLastMessage();

        return [
            'success' => true,
            'data' => [
                'conversation_id' => $conversation->id,
                'message_id' => $blockedMessage->id,
                'response' => $result->alternativeResponse,
                'context_used' => null,
                'guardrail_triggered' => true,
                'guardrail_reason' => $result->reason,
            ],
        ];
    }

    /**
     * Handle blocked output (regenerate with stricter prompt).
     */
    private function handleBlockedOutput(array $context, string $originalMessage): string
    {
        // This should be rare - add extra instructions and regenerate
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($context);
        $systemPrompt .= "\n\nIMPORTANT: Do NOT make any hiring decisions or recommendations. Only provide objective analysis of the data.";

        $userPrompt = $this->promptBuilder->buildUserPrompt($originalMessage, 'general_question');
        $userPrompt .= "\n\nRemember: Only provide analysis, not decisions.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $response = $this->callOpenAI($messages);

        return $this->guardrails->filterOutput($response['content']);
    }

    /**
     * Build context snapshot for message.
     */
    private function buildContextSnapshot(?array $contextSpec, array $context): ?array
    {
        if (!$contextSpec) {
            return null;
        }

        return [
            'type' => $contextSpec['type'],
            'id' => $contextSpec['id'],
            'fields' => array_keys($context),
        ];
    }

    /**
     * Call OpenAI API.
     */
    private function callOpenAI(array $messages): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('OpenAI Copilot error', [
                'status' => $response->status(),
                'body' => $response->body(),
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
