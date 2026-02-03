<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUuids;

    /**
     * Grace period duration in days after subscription expires.
     */
    public const GRACE_PERIOD_DAYS = 60;

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
        'is_premium' => 'boolean',
        'grace_period_ends_at' => 'datetime',
    ];

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
     * Check if the company is in grace period (subscription expired but within 60 days).
     */
    public function isInGracePeriod(): bool
    {
        // If subscription is active, not in grace period
        if ($this->isSubscriptionActive()) {
            return false;
        }

        // If grace_period_ends_at is set, check against it
        if ($this->grace_period_ends_at) {
            return $this->grace_period_ends_at->isFuture();
        }

        // Otherwise, calculate based on subscription_ends_at + 60 days
        if ($this->subscription_ends_at) {
            return $this->subscription_ends_at->addDays(self::GRACE_PERIOD_DAYS)->isFuture();
        }

        return false;
    }

    /**
     * Get the grace period end date.
     */
    public function getGracePeriodEndDate(): ?Carbon
    {
        if ($this->grace_period_ends_at) {
            return $this->grace_period_ends_at;
        }

        if ($this->subscription_ends_at) {
            return $this->subscription_ends_at->copy()->addDays(self::GRACE_PERIOD_DAYS);
        }

        return null;
    }

    /**
     * Check if the company has marketplace access (requires premium subscription).
     */
    public function hasMarketplaceAccess(): bool
    {
        return $this->is_premium && $this->isSubscriptionActive();
    }

    /**
     * Get the comprehensive subscription status.
     */
    public function getSubscriptionStatus(): array
    {
        $isActive = $this->isSubscriptionActive();
        $isInGrace = $this->isInGracePeriod();

        if ($isActive) {
            $status = 'active';
        } elseif ($isInGrace) {
            $status = 'grace_period';
        } else {
            $status = 'expired';
        }

        return [
            'status' => $status,
            'is_active' => $isActive,
            'is_in_grace_period' => $isInGrace,
            'is_premium' => $this->is_premium,
            'subscription_plan' => $this->subscription_plan,
            'subscription_ends_at' => $this->subscription_ends_at?->toIso8601String(),
            'grace_period_ends_at' => $this->getGracePeriodEndDate()?->toIso8601String(),
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
