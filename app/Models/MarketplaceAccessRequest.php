<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MarketplaceAccessRequest extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'requesting_company_id',
        'requesting_user_id',
        'candidate_id',
        'owning_company_id',
        'status',
        'request_message',
        'response_message',
        'access_token',
        'token_expires_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique access token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('access_token', $token)->exists());

        return $token;
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if access token is expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }

    /**
     * Approve the request.
     */
    public function approve(?string $message = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'response_message' => $message,
            'responded_at' => now(),
        ]);
    }

    /**
     * Reject the request.
     */
    public function reject(?string $message = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'response_message' => $message,
            'responded_at' => now(),
        ]);
    }

    // ========================
    // RELATIONSHIPS
    // ========================

    public function requestingCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'requesting_company_id');
    }

    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function owningCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'owning_company_id');
    }

    // ========================
    // TENANT SCOPES
    // ========================

    /**
     * Scope for requests made BY a specific company.
     */
    public function scopeForRequestingCompany($query, string $companyId)
    {
        return $query->where('requesting_company_id', $companyId);
    }

    /**
     * Scope for requests made TO a specific company (as candidate owner).
     */
    public function scopeForOwningCompany($query, string $companyId)
    {
        return $query->where('owning_company_id', $companyId);
    }

    /**
     * Scope for requests involving a company (either as requester or owner).
     */
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('requesting_company_id', $companyId)
              ->orWhere('owning_company_id', $companyId);
        });
    }
}
