<?php

namespace App\Services\Copilot;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class CopilotLogger
{
    private const LOG_CHANNEL = 'copilot';

    /**
     * Log a chat request (before processing).
     */
    public function logRequest(
        User $user,
        string $conversationId,
        string $message,
        ?array $context = null
    ): void {
        $this->log('request', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'message_length' => strlen($message),
            'context_type' => $context['type'] ?? null,
            'context_id' => $context['id'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a successful chat response.
     */
    public function logResponse(
        string $conversationId,
        string $messageId,
        array $metadata
    ): void {
        $this->log('response', [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'model' => $metadata['model'] ?? null,
            'prompt_tokens' => $metadata['prompt_tokens'] ?? null,
            'completion_tokens' => $metadata['completion_tokens'] ?? null,
            'total_tokens' => $metadata['total_tokens'] ?? null,
            'latency_ms' => $metadata['latency_ms'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a guardrail block event.
     */
    public function logGuardrailBlock(
        User $user,
        string $conversationId,
        string $reason,
        string $message
    ): void {
        $this->log('guardrail_block', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'reason' => $reason,
            'message_preview' => $this->truncateMessage($message, 100),
            'timestamp' => now()->toIso8601String(),
        ], 'warning');
    }

    /**
     * Log an error during processing.
     */
    public function logError(
        string $conversationId,
        string $errorType,
        string $errorMessage,
        ?array $additionalContext = null
    ): void {
        $this->log('error', [
            'conversation_id' => $conversationId,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'additional_context' => $additionalContext,
            'timestamp' => now()->toIso8601String(),
        ], 'error');
    }

    /**
     * Log context access for audit purposes (GDPR/KVKK).
     */
    public function logContextAccess(
        User $user,
        string $conversationId,
        string $contextType,
        string $contextId,
        array $fieldsAccessed
    ): void {
        $this->log('context_access', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'fields_accessed' => $fieldsAccessed,
            'timestamp' => now()->toIso8601String(),
            'purpose' => 'ai_copilot_analysis',
        ]);
    }

    /**
     * Log data export for compliance tracking.
     */
    public function logDataExport(
        User $user,
        string $conversationId,
        array $dataTypes
    ): void {
        $this->log('data_export', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'data_types' => $dataTypes,
            'timestamp' => now()->toIso8601String(),
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
            'prompt_tokens' => $tokenUsage['prompt_tokens'] ?? 0,
            'completion_tokens' => $tokenUsage['completion_tokens'] ?? 0,
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
        // This would typically query the database or log storage
        // For now, return the structure expected
        return [
            'conversation_id' => $conversationId,
            'generated_at' => now()->toIso8601String(),
            'note' => 'Full audit trail available in copilot log channel',
        ];
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
     * Truncate message for logging (avoid logging full user messages).
     */
    private function truncateMessage(string $message, int $length): string
    {
        if (strlen($message) <= $length) {
            return $message;
        }

        return substr($message, 0, $length) . '...';
    }

    /**
     * Calculate estimated cost based on token usage.
     * Prices are approximate and should be updated based on current OpenAI pricing.
     */
    public function calculateEstimatedCost(int $promptTokens, int $completionTokens, string $model): float
    {
        // Pricing per 1000 tokens (approximate, update as needed)
        $pricing = [
            'gpt-4-turbo-preview' => ['prompt' => 0.01, 'completion' => 0.03],
            'gpt-4' => ['prompt' => 0.03, 'completion' => 0.06],
            'gpt-4o' => ['prompt' => 0.005, 'completion' => 0.015],
            'gpt-4o-mini' => ['prompt' => 0.00015, 'completion' => 0.0006],
            'gpt-3.5-turbo' => ['prompt' => 0.0005, 'completion' => 0.0015],
        ];

        $modelPricing = $pricing[$model] ?? $pricing['gpt-4o-mini'];

        $promptCost = ($promptTokens / 1000) * $modelPricing['prompt'];
        $completionCost = ($completionTokens / 1000) * $modelPricing['completion'];

        return round($promptCost + $completionCost, 6);
    }
}
