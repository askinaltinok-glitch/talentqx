<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_id',
        'role_key',
        'context_key',
        'locale',
        'status',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';

    public function answers(): HasMany
    {
        return $this->hasMany(InterviewAnswer::class, 'session_id');
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(InterviewSessionAnalysis::class, 'session_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(InterviewReport::class, 'session_id');
    }

    public function consent(): BelongsTo
    {
        return $this->belongsTo(PrivacyConsent::class, 'privacy_consent_id');
    }

    public function context(): BelongsTo
    {
        return $this->belongsTo(JobContext::class, 'context_key', 'context_key')
            ->where('role_key', $this->role_key);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'finished_at' => now(),
        ]);
    }
}
