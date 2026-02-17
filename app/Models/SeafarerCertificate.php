<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeafarerCertificate — Individual certificate held by a pool candidate.
 *
 * Tracks certificate details, verification status, expiry,
 * and generates risk flags for the decision engine.
 */
class SeafarerCertificate extends Model
{
    use HasUuids;

    protected $fillable = [
        'pool_candidate_id',
        'certificate_type',
        'certificate_code',
        'issuing_authority',
        'issuing_country',
        'issued_at',
        'expires_at',
        'document_url',
        'document_hash',
        'verification_status',
        'verification_notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'verified_at' => 'datetime',
    ];

    // Verification statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
    ];

    // Risk flag codes (output structure for DecisionEngine)
    public const RF_CERT_EXPIRED = 'RF_CERT_EXPIRED';
    public const RF_CERT_MISSING = 'RF_CERT_MISSING';
    public const RF_CERT_FAKE_PATTERN = 'RF_CERT_FAKE_PATTERN';
    public const RF_MEDICAL_EXPIRED = 'RF_MEDICAL_EXPIRED';

    /**
     * The candidate who holds this certificate.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    /**
     * Get the certificate type definition.
     */
    public function certificateTypeDefinition(): ?CertificateType
    {
        return CertificateType::findByCode($this->certificate_type);
    }

    /**
     * Check if certificate is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    /**
     * Check if certificate is expiring within given days.
     */
    public function isExpiringSoon(int $days = 90): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return !$this->isExpired() && $this->expires_at->lte(now()->addDays($days));
    }

    /**
     * Check if certificate is valid (verified + not expired).
     */
    public function isValid(): bool
    {
        return $this->verification_status === self::STATUS_VERIFIED
            && !$this->isExpired();
    }

    /**
     * Mark as verified.
     */
    public function verify(?string $verifiedBy = null, ?string $notes = null): void
    {
        $this->update([
            'verification_status' => self::STATUS_VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function reject(?string $verifiedBy = null, ?string $notes = null): void
    {
        $this->update([
            'verification_status' => self::STATUS_REJECTED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): void
    {
        $this->update([
            'verification_status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Scope: for a specific candidate.
     */
    public function scopeForCandidate($query, string $candidateId)
    {
        return $query->where('pool_candidate_id', $candidateId);
    }

    /**
     * Scope: by verification status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('verification_status', $status);
    }

    /**
     * Scope: expired certificates.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope: expiring within N days.
     */
    public function scopeExpiringSoon($query, int $days = 90)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays($days));
    }

    /**
     * Scope: valid (verified + not expired).
     */
    public function scopeValid($query)
    {
        return $query->where('verification_status', self::STATUS_VERIFIED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            });
    }
}
