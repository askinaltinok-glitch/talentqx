<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmOutboundQueue extends Model
{
    use HasUuids;

    protected $table = 'crm_outbound_queue';

    protected $fillable = [
        'lead_id', 'email_thread_id', 'from_email', 'to_email',
        'subject', 'body_text', 'body_html', 'template_key',
        'source', 'status', 'approved_by', 'approved_at',
        'scheduled_at', 'sent_at', 'error_log',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public const SOURCE_DRAFT = 'draft';
    public const SOURCE_SEQUENCE = 'sequence';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_AI_REPLY = 'ai_reply';

    public const SOURCES = [
        self::SOURCE_DRAFT, self::SOURCE_SEQUENCE,
        self::SOURCE_MANUAL, self::SOURCE_AI_REPLY,
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT, self::STATUS_APPROVED, self::STATUS_SENDING,
        self::STATUS_SENT, self::STATUS_FAILED, self::STATUS_CANCELLED,
    ];

    // Relationships

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CrmEmailThread::class, 'email_thread_id');
    }

    // Scopes

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_APPROVED]);
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    // Methods

    public function approve(?string $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function markSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_log' => $error,
        ]);
    }
}
