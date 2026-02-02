<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageOutbox extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'message_outbox';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    // Channel constants
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_PUSH = 'push';

    protected $fillable = [
        'company_id',
        'channel',
        'recipient',
        'recipient_name',
        'subject',
        'body',
        'template_data',
        'template_id',
        'related_type',
        'related_id',
        'status',
        'retry_count',
        'max_retries',
        'scheduled_at',
        'processing_at',
        'sent_at',
        'failed_at',
        'error_message',
        'error_details',
        'external_id',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'template_data' => 'array',
        'error_details' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'processing_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'priority' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'retry_count' => 0,
        'max_retries' => 3,
        'priority' => 0,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    /**
     * Get the related model (polymorphic).
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    /**
     * Scope for pending messages ready to be sent.
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            })
            ->orderByDesc('priority')
            ->orderBy('created_at');
    }

    /**
     * Scope for failed messages that can be retried.
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_FAILED)
            ->whereColumn('retry_count', '<', 'max_retries');
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processing_at' => now(),
        ]);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(?string $externalId = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'external_id' => $externalId,
            'error_message' => null,
            'error_details' => null,
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $errorMessage, ?array $errorDetails = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Check if can be retried.
     */
    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries;
    }

    /**
     * Reset for retry.
     */
    public function resetForRetry(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'processing_at' => null,
        ]);
    }

    /**
     * Cancel the message.
     */
    public function cancel(): void
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->update(['status' => self::STATUS_CANCELLED]);
        }
    }
}
