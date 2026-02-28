<?php

namespace App\Models;

use App\Support\BrandConfig;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUuids;

    public const PLATFORM_OCTOPUS = 'octopus';
    public const PLATFORM_TALENTQX = 'talentqx';

    public static function booted(): void
    {
        static::creating(function (Company $company) {
            $settings = $company->settings ?? [];
            $settings['grace_credits_total'] = $settings['grace_credits_total'] ?? 5;
            $settings['grace_credits_used']  = $settings['grace_credits_used']  ?? 0;
            $company->settings = $settings;
        });
    }

    protected $fillable = [
        'name',
        'trade_name',
        'platform',
        'slug',
        'logo_url',
        'brand_email_reply_to',
        'brand_primary_color',
        'address',
        'city',
        'district',
        'country',
        'timezone',
        'fleet_size',
        'management_type',
        'onboarding_completed',
        'subscription_plan',
        'subscription_ends_at',
        'is_premium',
        'grace_period_ends_at',
        'settings',
        'monthly_credits',
        'credits_used',
        'credits_period_start',
        'bonus_credits',
        // Billing fields
        'legal_name',
        'tax_number',
        'tax_office',
        'billing_type',
        'billing_address',
        'billing_city',
        'billing_postal_code',
        'billing_email',
        'billing_phone',
        'phone',
        'website',
        'email',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_ends_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'onboarding_completed' => 'boolean',
        'is_premium' => 'boolean',
        'monthly_credits' => 'integer',
        'credits_used' => 'integer',
        'credits_period_start' => 'date',
        'bonus_credits' => 'integer',
    ];

    /**
     * Valid subscription plans.
     */
    public const PLANS = ['free', 'pilot', 'mini', 'midi', 'starter', 'pro', 'enterprise'];

    /**
     * Valid billing types.
     */
    public const BILLING_TYPES = ['individual', 'corporate'];

    /**
     * Default grace period in days after subscription expires.
     */
    public const DEFAULT_GRACE_PERIOD_DAYS = 60;

    public function scopeOctopus($query)
    {
        return $query->where('platform', self::PLATFORM_OCTOPUS);
    }

    public function scopeTalentqx($query)
    {
        return $query->where('platform', self::PLATFORM_TALENTQX);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function isOctopus(): bool
    {
        return $this->platform === self::PLATFORM_OCTOPUS;
    }

    public function isTalentqx(): bool
    {
        return $this->platform === self::PLATFORM_TALENTQX;
    }

    /**
     * Get the full brand config array for this company's platform.
     */
    public function brandConfig(): array
    {
        return BrandConfig::forCompany($this);
    }

    /**
     * Get the login URL for this company's platform.
     */
    public function getLoginUrl(): string
    {
        return BrandConfig::loginUrl($this->platform ?? self::PLATFORM_OCTOPUS);
    }

    /**
     * Get the support email for this company's platform.
     */
    public function getSupportEmail(): string
    {
        return BrandConfig::supportEmail($this->platform ?? self::PLATFORM_OCTOPUS);
    }

    /**
     * Get the brand display name for this company's platform.
     */
    public function getPlatformBrandName(): string
    {
        return BrandConfig::brandName($this->platform ?? self::PLATFORM_OCTOPUS);
    }

    public function companyVessels(): HasMany
    {
        return $this->hasMany(CompanyVessel::class);
    }

    public function fleetVessels(): HasMany
    {
        return $this->hasMany(FleetVessel::class);
    }

    public function vessels(): BelongsToMany
    {
        return $this->belongsToMany(Vessel::class, 'company_vessels')
            ->withPivot('role', 'is_active', 'assigned_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function applyLinks(): HasMany
    {
        return $this->hasMany(CompanyApplyLink::class);
    }

    public function certificateRules(): HasMany
    {
        return $this->hasMany(CompanyCertificateRule::class);
    }

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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
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
        return $this->brand_email_reply_to ?: config('mail.reply_to.address', 'support@octopus-ai.net');
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
        return "{$this->getPlatformBrandName()} | {$this->name}";
    }

    /**
     * Get company logo as a full public URL or null.
     */
    public function getLogoUrl(): ?string
    {
        if (!$this->logo_url) {
            return null;
        }

        // Already an absolute URL
        if (str_starts_with($this->logo_url, 'http')) {
            return $this->logo_url;
        }

        // DB stores "/storage/company-logos/..." but Nginx serves at "/api/storage/..."
        $path = $this->logo_url;
        if (str_starts_with($path, '/storage/')) {
            $path = '/api' . $path;
        }

        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . $path;
    }

    /**
     * Get the display name: trade_name if set, otherwise name.
     */
    public function getDisplayName(): string
    {
        return $this->trade_name ?: $this->name;
    }

    /**
     * Get remaining credits (monthly + bonus - used).
     */
    public function getRemainingCredits(): int
    {
        return max(0, $this->getTotalCredits() - $this->credits_used);
    }

    /**
     * Check if company has available credits.
     */
    public function hasCredits(): bool
    {
        return $this->getRemainingCredits() > 0;
    }

    /**
     * Get total credits (monthly + bonus).
     */
    public function getTotalCredits(): int
    {
        return ($this->monthly_credits ?? 0) + ($this->bonus_credits ?? 0);
    }

    /**
     * Get credit usage logs relationship.
     */
    public function creditUsageLogs(): HasMany
    {
        return $this->hasMany(CreditUsageLog::class);
    }

    /**
     * Get grace credits total from settings JSON.
     */
    public function getGraceCreditsTotal(): int
    {
        return ($this->settings ?? [])['grace_credits_total'] ?? 5;
    }

    /**
     * Get grace credits used from settings JSON.
     */
    public function getGraceCreditsUsed(): int
    {
        return ($this->settings ?? [])['grace_credits_used'] ?? 0;
    }

    /**
     * Set grace credits total in settings JSON.
     */
    public function setGraceCreditsTotal(int $total): void
    {
        $settings = $this->settings ?? [];
        $settings['grace_credits_total'] = $total;
        $this->settings = $settings;
    }

    /**
     * Set grace credits used in settings JSON.
     */
    public function setGraceCreditsUsed(int $used): void
    {
        $settings = $this->settings ?? [];
        $settings['grace_credits_used'] = $used;
        $this->settings = $settings;
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

    /**
     * Check if company has corporate billing.
     */
    public function isCorporate(): bool
    {
        return $this->billing_type === 'corporate';
    }

    /**
     * Get billing info snapshot for invoices.
     */
    public function getBillingSnapshot(): array
    {
        return [
            'name' => $this->isCorporate() ? ($this->legal_name ?: $this->name) : $this->name,
            'legal_name' => $this->legal_name,
            'billing_type' => $this->billing_type,
            'tax_number' => $this->tax_number,
            'tax_office' => $this->tax_office,
            'address' => $this->billing_address,
            'city' => $this->billing_city,
            'postal_code' => $this->billing_postal_code,
            'email' => $this->billing_email ?: $this->users()->first()?->email,
            'phone' => $this->billing_phone,
        ];
    }

    /**
     * Check if behavioral details should be shown in HR portal.
     * Default: false (safe mode — show only fit/confidence).
     */
    public function showBehavioralDetails(): bool
    {
        return (bool) (($this->settings ?? [])['show_behavioral_details'] ?? false);
    }

    /**
     * Check if AIS trust evidence should be shown in HR portal.
     * Default: false (off until company opts in).
     */
    public function showTrustEvidence(): bool
    {
        return (bool) (($this->settings ?? [])['show_trust_evidence'] ?? false);
    }

    /**
     * Check if company has complete billing info for invoicing.
     */
    public function hasBillingInfo(): bool
    {
        if ($this->billing_type === 'corporate') {
            return !empty($this->tax_number) && !empty($this->tax_office);
        }
        return true; // Individual billing always has enough info
    }
}
