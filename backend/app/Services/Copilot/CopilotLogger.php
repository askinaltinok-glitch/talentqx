<?php

namespace App\Services\Copilot;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * KVKK-Safe Copilot Logger
 *
 * This logger NEVER stores:
 * - Full message content or prompts
 * - PII (names, emails, phones)
 * - Full context data
 *
 * It only logs:
 * - IDs and references
 * - Metadata (token counts, latency, costs)
 * - Event types and timestamps
 */
class CopilotLogger
{
    private const LOG_CHANNEL = 'copilot';

    /**
     * Log a chat request (before processing).
     * KVKK-safe: Only logs metadata, NOT message content.
     */
    public function logRequest(
        User $user,
        string $conversationId,
        ?array $contextSpec = null
    ): void {
        $this->log('request', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'context_type' => $contextSpec['type'] ?? null,
            'context_id' => $contextSpec['id'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a successful chat response.
     * KVKK-safe: Only logs metadata, NOT response content.
     */
    public function logResponse(
        string $conversationId,
        string $messageId,
        array $metadata,
        ?string $intent = null
    ): void {
        $this->log('response', [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'model' => $metadata['model'] ?? null,
            'input_tokens' => $metadata['input_tokens'] ?? $metadata['prompt_tokens'] ?? null,
            'output_tokens' => $metadata['output_tokens'] ?? $metadata['completion_tokens'] ?? null,
            'total_tokens' => $metadata['total_tokens'] ?? null,
            'latency_ms' => $metadata['latency_ms'] ?? null,
            'estimated_cost_usd' => $metadata['estimated_cost_usd'] ?? null,
            'intent' => $intent,
            'structured' => $metadata['structured'] ?? false,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a guardrail block event.
     * KVKK-safe: Only logs reason, NOT message content.
     */
    public function logGuardrailBlock(
        User $user,
        string $conversationId,
        string $reason
    ): void {
        $this->log('guardrail_block', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ], 'warning');
    }

    /**
     * Log an error during processing.
     * KVKK-safe: Only logs error type and message, no user content.
     */
    public function logError(
        string $conversationId,
        string $errorType,
        string $errorMessage
    ): void {
        $this->log('error', [
            'conversation_id' => $conversationId,
            'error_type' => $errorType,
            'error_message' => $this->sanitizeErrorMessage($errorMessage),
            'timestamp' => now()->toIso8601String(),
        ], 'error');
    }

    /**
     * Log context access for audit purposes (GDPR/KVKK).
     * KVKK-safe: Only logs type and ID, NOT the actual data accessed.
     */
    public function logContextAccess(
        User $user,
        string $conversationId,
        string $contextType,
        string $contextId
    ): void {
        $this->log('context_access', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'timestamp' => now()->toIso8601String(),
            'purpose' => 'ai_copilot_analysis',
        ]);
    }

    /**
     * Log conversation creation.
     */
    public function logConversationCreated(
        User $user,
        string $conversationId,
        ?string $contextType = null,
        ?string $contextId = null
    ): void {
        $this->log('conversation_created', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log API cost tracking.
     */
    public function logApiCost(
        string $conversationId,
        float $estimatedCost,
        array $tokenUsage
    ): void {
        $this->log('api_cost', [
            'conversation_id' => $conversationId,
            'estimated_cost_usd' => $estimatedCost,
            'input_tokens' => $tokenUsage['input_tokens'] ?? $tokenUsage['prompt_tokens'] ?? 0,
            'output_tokens' => $tokenUsage['output_tokens'] ?? $tokenUsage['completion_tokens'] ?? 0,
            'total_tokens' => $tokenUsage['total_tokens'] ?? 0,
            'model' => $tokenUsage['model'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Generate audit summary for a conversation.
     */
    public function generateAuditSummary(string $conversationId): array
    {
        return [
            'conversation_id' => $conversationId,
            'generated_at' => now()->toIso8601String(),
            'note' => 'Full audit trail available in copilot log channel',
        ];
    }

    /**
     * Calculate estimated cost based on token usage.
     * Prices are approximate and should be updated based on current OpenAI pricing.
     */
    public function calculateEstimatedCost(int $inputTokens, int $outputTokens, string $model): float
    {
        // Pricing per 1000 tokens (approximate, update as needed)
        // Using input/output terminology consistent with Responses API
        $pricing = [
            'gpt-4-turbo-preview' => ['input' => 0.01, 'output' => 0.03],
            'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
            'gpt-4' => ['input' => 0.03, 'output' => 0.06],
            'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
            'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
        ];

        $modelPricing = $pricing[$model] ?? $pricing['gpt-4o-mini'];

        $inputCost = ($inputTokens / 1000) * $modelPricing['input'];
        $outputCost = ($outputTokens / 1000) * $modelPricing['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Internal logging method.
     */
    private function log(string $event, array $data, string $level = 'info'): void
    {
        $logData = [
            'event' => "copilot.{$event}",
            'data' => $data,
        ];

        // Use dedicated copilot channel if configured, otherwise default
        $channel = config('logging.channels.copilot') ? self::LOG_CHANNEL : 'stack';

        match ($level) {
            'warning' => Log::channel($channel)->warning(json_encode($logData)),
            'error' => Log::channel($channel)->error(json_encode($logData)),
            default => Log::channel($channel)->info(json_encode($logData)),
        };
    }

    /**
     * Sanitize error messages to remove potential PII.
     * Removes email patterns, phone patterns, and truncates long messages.
     */
    private function sanitizeErrorMessage(string $message): string
    {
        // Remove email patterns
        $message = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL]', $message);

        // Remove phone patterns (various formats)
        $message = preg_replace('/(\+?[0-9]{1,3}[-.\s]?)?(\(?[0-9]{3}\)?[-.\s]?)?[0-9]{3}[-.\s]?[0-9]{4}/', '[PHONE]', $message);

        // Truncate to prevent very long error messages
        if (strlen($message) > 500) {
            $message = substr($message, 0, 500) . '...[truncated]';
        }

        return $message;
    }
}
