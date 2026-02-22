<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateReminderLog extends Model
{
    use HasUuids;

    protected $table = 'candidate_reminder_logs';

    // Append-only: no updated_at
    public const UPDATED_AT = null;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BLOCKED_SAFETY = 'blocked_safety';

    // Reminder type constants
    public const TYPE_EXPIRY_60 = 'credential_expiry_60';
    public const TYPE_EXPIRY_30 = 'credential_expiry_30';
    public const TYPE_EXPIRY_7 = 'credential_expiry_7';
    public const TYPE_EXPIRY_1 = 'credential_expiry_1';

    public const REMINDER_DAYS = [
        self::TYPE_EXPIRY_60 => 60,
        self::TYPE_EXPIRY_30 => 30,
        self::TYPE_EXPIRY_7  => 7,
        self::TYPE_EXPIRY_1  => 1,
    ];

    protected $fillable = [
        'pool_candidate_id',
        'credential_id',
        'reminder_type',
        'channel',
        'to',
        'language',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(CandidateCredential::class, 'credential_id');
    }

    /**
     * Mark as sent.
     */
    public function markSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }
}
