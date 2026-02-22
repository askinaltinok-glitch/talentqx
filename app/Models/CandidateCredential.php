<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateCredential extends Model
{
    use HasUuids;

    protected $table = 'candidate_credentials';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_SELF_DECLARED = 'self_declared';
    public const VERIFICATION_VERIFIED = 'verified';

    // Common maritime credential types
    public const TYPE_STCW = 'STCW';
    public const TYPE_GMDSS = 'GMDSS';
    public const TYPE_TANKER_FAM = 'Tanker Familiarization';
    public const TYPE_MEDICAL = 'Medical';
    public const TYPE_PASSPORT = 'Passport';
    public const TYPE_SEAMAN_BOOK = 'Seaman Book';

    protected $fillable = [
        'pool_candidate_id',
        'credential_type',
        'credential_number',
        'issuer',
        'issued_at',
        'expires_at',
        'file_url',
        'verification_status',
        'last_reminded_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'last_reminded_at' => 'datetime',
    ];

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(CandidateReminderLog::class, 'credential_id');
    }

    /**
     * Check if credential is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if credential expires within N days.
     */
    public function expiresWithinDays(int $days): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->diffInDays(now(), false) <= $days
            && $this->expires_at->isFuture();
    }

    /**
     * Get days until expiry (negative if expired).
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
    }

    /**
     * Scope: credentials expiring on exact date.
     */
    public function scopeExpiringOn($query, $date)
    {
        return $query->whereDate('expires_at', $date);
    }

    /**
     * Scope: credentials with expiry dates.
     */
    public function scopeHasExpiry($query)
    {
        return $query->whereNotNull('expires_at');
    }
}
