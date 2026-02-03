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

    /**
     * Token expiry duration in days.
     */
    public const TOKEN_EXPIRY_DAYS = 7;

    protected $fillable = [
        'requesting_company_id',
        'requesting_user_id',
        'candidate_id',
        'status',
        'request_message',
        'response_message',
        'responded_at',
        'approval_token',
        'token_expires_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->approval_token)) {
                $model->approval_token = Str::random(64);
            }
            if (empty($model->token_expires_at)) {
                $model->token_expires_at = now()->addDays(self::TOKEN_EXPIRY_DAYS);
            }
        });
    }

    /**
     * Get the company that made the request.
     */
    public function requestingCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'requesting_company_id');
    }

    /**
     * Get the user that made the request.
     */
    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    /**
     * Get the candidate being requested.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
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
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the request is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Check if the token is still valid.
     */
    public function isTokenValid(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isFuture();
    }

    /**
     * Approve the access request.
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
     * Reject the access request.
     */
    public function reject(?string $message = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'response_message' => $message,
            'responded_at' => now(),
        ]);
    }

    /**
     * Mark the request as expired.
     */
    public function markExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Scope to filter by approval token.
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('approval_token', $token);
    }

    /**
     * Scope to filter pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to filter approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Find a request by token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::byToken($token)->first();
    }
}
