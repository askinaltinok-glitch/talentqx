<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopilotMessage extends Model
{
    use HasFactory, HasUuids;

    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_SYSTEM = 'system';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'context_snapshot',
        'metadata',
        'guardrail_triggered',
        'guardrail_reason',
    ];

    protected $casts = [
        'context_snapshot' => 'array',
        'metadata' => 'array',
        'guardrail_triggered' => 'boolean',
    ];

    protected $attributes = [
        'guardrail_triggered' => false,
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CopilotConversation::class, 'conversation_id');
    }

    /**
     * Check if message is from user.
     */
    public function isFromUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Check if message is from assistant.
     */
    public function isFromAssistant(): bool
    {
        return $this->role === self::ROLE_ASSISTANT;
    }

    /**
     * Check if this message was blocked by guardrails.
     */
    public function wasBlocked(): bool
    {
        return $this->guardrail_triggered;
    }

    /**
     * Get token usage from metadata.
     */
    public function getTokenUsage(): array
    {
        return [
            'prompt_tokens' => $this->metadata['prompt_tokens'] ?? 0,
            'completion_tokens' => $this->metadata['completion_tokens'] ?? 0,
            'total_tokens' => $this->metadata['total_tokens'] ?? 0,
        ];
    }

    /**
     * Get the model used for this response.
     */
    public function getModel(): ?string
    {
        return $this->metadata['model'] ?? null;
    }

    /**
     * Get response latency in milliseconds.
     */
    public function getLatencyMs(): ?int
    {
        return $this->metadata['latency_ms'] ?? null;
    }

    /**
     * Create a user message.
     */
    public static function createUserMessage(
        string $conversationId,
        string $content,
        ?array $contextSnapshot = null
    ): self {
        return self::create([
            'conversation_id' => $conversationId,
            'role' => self::ROLE_USER,
            'content' => $content,
            'context_snapshot' => $contextSnapshot,
        ]);
    }

    /**
     * Create an assistant message.
     */
    public static function createAssistantMessage(
        string $conversationId,
        string $content,
        ?array $contextSnapshot = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'conversation_id' => $conversationId,
            'role' => self::ROLE_ASSISTANT,
            'content' => $content,
            'context_snapshot' => $contextSnapshot,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a blocked message (guardrail triggered).
     */
    public static function createBlockedMessage(
        string $conversationId,
        string $role,
        string $originalContent,
        string $blockedResponse,
        string $reason
    ): self {
        return self::create([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $blockedResponse,
            'guardrail_triggered' => true,
            'guardrail_reason' => $reason,
            'metadata' => [
                'original_content_length' => strlen($originalContent),
            ],
        ]);
    }
}
