<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfile extends Model
{
    use HasUuids;

    protected $table = 'candidate_profiles';

    public const STATUS_SEEKER = 'seeker';
    public const STATUS_PASSIVE = 'passive';
    public const STATUS_BLOCKED = 'blocked';

    public const STATUSES = [
        self::STATUS_SEEKER,
        self::STATUS_PASSIVE,
        self::STATUS_BLOCKED,
    ];

    protected $fillable = [
        'pool_candidate_id',
        'status',
        'preferred_language',
        'timezone',
        'marketing_opt_in',
        'reminders_opt_in',
        'headhunt_opt_in',
        'data_processing_consent_at',
        'marketing_consent_at',
        'reminders_consent_at',
        'headhunt_consent_at',
        'blocked_reason',
        'blocked_at',
        'trust_opt_in',
        'trust_consent_at',
    ];

    protected $casts = [
        'marketing_opt_in' => 'boolean',
        'reminders_opt_in' => 'boolean',
        'headhunt_opt_in' => 'boolean',
        'data_processing_consent_at' => 'datetime',
        'marketing_consent_at' => 'datetime',
        'reminders_consent_at' => 'datetime',
        'headhunt_consent_at' => 'datetime',
        'blocked_at' => 'datetime',
        'trust_opt_in' => 'boolean',
        'trust_consent_at' => 'datetime',
    ];

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function isSeeker(): bool
    {
        return $this->status === self::STATUS_SEEKER;
    }

    public function isPassive(): bool
    {
        return $this->status === self::STATUS_PASSIVE;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Check if candidate is visible to companies.
     * Only seekers with data processing consent are visible.
     */
    public function isVisibleToCompany(): bool
    {
        return $this->isSeeker() && $this->data_processing_consent_at !== null;
    }

    /**
     * Block this candidate profile.
     */
    public function block(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_BLOCKED,
            'blocked_reason' => $reason,
            'blocked_at' => now(),
        ]);
    }
}
