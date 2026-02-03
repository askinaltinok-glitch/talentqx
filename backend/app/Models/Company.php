<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'brand_email_reply_to',
        'brand_primary_color',
        'address',
        'city',
        'country',
        'subscription_plan',
        'subscription_ends_at',
        'is_premium',
        'grace_period_ends_at',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_ends_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'is_premium' => 'boolean',
    ];

    /**
     * Valid subscription plans.
     */
    public const PLANS = ['free', 'starter', 'pro', 'enterprise'];

    /**
     * Default grace period in days after subscription expires.
     */
    public const DEFAULT_GRACE_PERIOD_DAYS = 60;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function isSubscriptionActive(): bool
    {
        return $this->subscription_ends_at === null ||
               $this->subscription_ends_at->isFuture();
    }

    /**
     * Check if company is in grace period.
     * Grace period starts when subscription expires and ends at grace_period_ends_at.
     */
    public function isInGracePeriod(): bool
    {
        // Not in grace if subscription is active
        if ($this->isSubscriptionActive()) {
            return false;
        }

        // If no grace period set, calculate default (60 days after subscription end)
        $graceEnd = $this->grace_period_ends_at
            ?? $this->subscription_ends_at?->copy()->addDays(self::DEFAULT_GRACE_PERIOD_DAYS);

        if (!$graceEnd) {
            return false;
        }

        return $graceEnd->isFuture();
    }

    /**
     * Get computed subscription status.
     * Returns: active, grace_period, or expired
     */
    public function getSubscriptionStatus(): string
    {
        if ($this->isSubscriptionActive()) {
            return 'active';
        }

        if ($this->isInGracePeriod()) {
            return 'grace_period';
        }

        return 'expired';
    }

    /**
     * Check if company has marketplace access.
     * Requires: premium + active subscription (not grace period)
     */
    public function hasMarketplaceAccess(): bool
    {
        return $this->is_premium && $this->isSubscriptionActive();
    }

    /**
     * Get computed status snapshot for API responses.
     */
    public function getComputedStatus(): array
    {
        return [
            'status' => $this->getSubscriptionStatus(),
            'is_active' => $this->isSubscriptionActive(),
            'is_in_grace_period' => $this->isInGracePeriod(),
            'has_marketplace_access' => $this->hasMarketplaceAccess(),
        ];
    }

    /**
     * Get the email reply-to address for this company.
     */
    public function getEmailReplyTo(): string
    {
        return $this->brand_email_reply_to ?: config('mail.reply_to.address', 'support@talentqx.com');
    }

    /**
     * Get the primary brand color for emails.
     */
    public function getBrandColor(): string
    {
        return $this->brand_primary_color ?: '#667eea';
    }

    /**
     * Get the email FROM name for this company.
     */
    public function getEmailFromName(): string
    {
        return "TalentQX | {$this->name}";
    }

    /**
     * Get company logo URL or null.
     */
    public function getLogoUrl(): ?string
    {
        return $this->logo_url;
    }

    /**
     * Get company initials (2 letters) for logo placeholder.
     * Example: "Ekler İstanbul" => "Eİ", "Acme Corp" => "AC"
     */
    public function getInitials(): string
    {
        $words = preg_split('/\s+/', trim($this->name));

        if (count($words) >= 2) {
            // Take first letter of first two words
            return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }

        // Single word: take first two letters
        return mb_strtoupper(mb_substr($this->name, 0, 2));
    }
}
