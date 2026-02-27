<?php

namespace App\Services\Billing;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Billing Service Abstraction Layer
 *
 * Phase 1: Sales-driven model (manual subscription activation)
 * Future: Integrate with Stripe/payment provider
 *
 * IMPORTANT: All checkout buttons are currently disabled.
 * Subscriptions are activated manually by admin after sales process.
 */
class BillingService
{
    /**
     * Available subscription plans
     */
    public const PLANS = [
        'free' => [
            'name' => 'Free Trial',
            'assessments_per_month' => 5,
            'price_tl' => 0,
            'price_eur' => 0,
            'features' => ['basic_reports'],
        ],
        'mini' => [
            'name' => 'MINI',
            'assessments_per_month' => 100,
            'price_tl' => 9900,
            'price_eur' => 199,
            'features' => ['pdf_reports', 'email_support'],
        ],
        'midi' => [
            'name' => 'MIDI',
            'assessments_per_month' => 250,
            'price_tl' => 19900,
            'price_eur' => 399,
            'features' => ['pdf_reports', 'priority_support', 'analytics'],
        ],
        'pro' => [
            'name' => 'PRO',
            'assessments_per_month' => 600,
            'price_tl' => 49900,
            'price_eur' => 999,
            'features' => ['pdf_reports', 'dedicated_support', 'analytics', 'api_access', 'marketplace'],
        ],
    ];

    /**
     * Grace period duration in days after subscription expiration
     */
    public const GRACE_PERIOD_DAYS = 60;

    /**
     * Check if online checkout is enabled
     *
     * @return bool Always false in Phase 1 (sales-driven)
     */
    public function isCheckoutEnabled(): bool
    {
        return config('billing.checkout_enabled', false);
    }

    /**
     * Get available plans for display
     *
     * @param string $currency 'TL' or 'EUR'
     * @return array
     */
    public function getAvailablePlans(string $currency = 'TL'): array
    {
        $plans = [];
        foreach (self::PLANS as $key => $plan) {
            if ($key === 'free') continue; // Don't show free in pricing

            $plans[] = [
                'key' => strtoupper($key),
                'name' => $plan['name'],
                'assessments' => $plan['assessments_per_month'],
                'price' => $currency === 'EUR' ? $plan['price_eur'] : $plan['price_tl'],
                'currency' => $currency,
                'features' => $plan['features'],
            ];
        }
        return $plans;
    }

    /**
     * Activate subscription for a company (Admin only)
     *
     * @param Company $company
     * @param string $plan Plan key (mini, midi, pro)
     * @param int $months Duration in months
     * @param bool $isPremium Enable marketplace access
     * @return array Activation result
     */
    public function activateSubscription(
        Company $company,
        string $plan,
        int $months = 1,
        bool $isPremium = false
    ): array {
        if (!isset(self::PLANS[$plan])) {
            return [
                'success' => false,
                'error' => 'Invalid plan: ' . $plan,
            ];
        }

        $now = Carbon::now();
        $endsAt = $now->copy()->addMonths($months);
        $graceEndsAt = $endsAt->copy()->addDays(self::GRACE_PERIOD_DAYS);

        $company->update([
            'subscription_plan' => $plan,
            'subscription_ends_at' => $endsAt,
            'grace_period_ends_at' => $graceEndsAt,
            'is_premium' => $isPremium || $plan === 'pro', // PRO always gets premium
        ]);

        Log::info('Subscription activated', [
            'company_id' => $company->id,
            'plan' => $plan,
            'months' => $months,
            'ends_at' => $endsAt->toIso8601String(),
            'is_premium' => $company->is_premium,
        ]);

        return [
            'success' => true,
            'company_id' => $company->id,
            'plan' => $plan,
            'subscription_ends_at' => $endsAt->toIso8601String(),
            'grace_period_ends_at' => $graceEndsAt->toIso8601String(),
            'is_premium' => $company->is_premium,
        ];
    }

    /**
     * Extend existing subscription
     *
     * @param Company $company
     * @param int $months Additional months
     * @return array Extension result
     */
    public function extendSubscription(Company $company, int $months): array
    {
        $currentEnd = $company->subscription_ends_at ?? Carbon::now();

        // If expired, start from now
        if ($currentEnd->isPast()) {
            $currentEnd = Carbon::now();
        }

        $newEnd = $currentEnd->copy()->addMonths($months);
        $graceEndsAt = $newEnd->copy()->addDays(self::GRACE_PERIOD_DAYS);

        $company->update([
            'subscription_ends_at' => $newEnd,
            'grace_period_ends_at' => $graceEndsAt,
        ]);

        Log::info('Subscription extended', [
            'company_id' => $company->id,
            'months_added' => $months,
            'new_end' => $newEnd->toIso8601String(),
        ]);

        return [
            'success' => true,
            'company_id' => $company->id,
            'subscription_ends_at' => $newEnd->toIso8601String(),
            'grace_period_ends_at' => $graceEndsAt->toIso8601String(),
        ];
    }

    /**
     * Cancel subscription (sets end date to now, enters grace period)
     *
     * @param Company $company
     * @return array Cancellation result
     */
    public function cancelSubscription(Company $company): array
    {
        $now = Carbon::now();
        $graceEndsAt = $now->copy()->addDays(self::GRACE_PERIOD_DAYS);

        $company->update([
            'subscription_ends_at' => $now,
            'grace_period_ends_at' => $graceEndsAt,
        ]);

        Log::info('Subscription cancelled', [
            'company_id' => $company->id,
            'grace_period_ends_at' => $graceEndsAt->toIso8601String(),
        ]);

        return [
            'success' => true,
            'company_id' => $company->id,
            'cancelled_at' => $now->toIso8601String(),
            'grace_period_ends_at' => $graceEndsAt->toIso8601String(),
            'message' => 'Subscription cancelled. You have ' . self::GRACE_PERIOD_DAYS . ' days to export your data.',
        ];
    }

    /**
     * Get company's current billing status
     *
     * @param Company $company
     * @return array
     */
    public function getBillingStatus(Company $company): array
    {
        $plan = self::PLANS[$company->subscription_plan] ?? self::PLANS['free'];

        return [
            'plan' => [
                'key' => $company->subscription_plan,
                'name' => $plan['name'],
                'assessments_per_month' => $plan['assessments_per_month'],
            ],
            'subscription' => $company->getSubscriptionStatus(),
            'checkout_enabled' => $this->isCheckoutEnabled(),
            'contact_sales' => [
                'email' => 'sales@octopus-ai.net',
                'phone' => '+90 212 123 45 67',
            ],
        ];
    }

    /**
     * Placeholder for future Stripe integration
     *
     * @param Company $company
     * @param string $plan
     * @return array
     */
    public function createCheckoutSession(Company $company, string $plan): array
    {
        // Phase 1: Checkout is disabled
        if (!$this->isCheckoutEnabled()) {
            return [
                'success' => false,
                'error' => 'Online checkout is not available. Please contact sales.',
                'code' => 'CHECKOUT_DISABLED',
                'contact' => [
                    'email' => 'sales@octopus-ai.net',
                    'phone' => '+90 212 123 45 67',
                ],
            ];
        }

        // Future: Stripe integration
        // $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        // return $stripe->checkout->sessions->create([...]);

        return [
            'success' => false,
            'error' => 'Payment provider not configured',
            'code' => 'PAYMENT_NOT_CONFIGURED',
        ];
    }
}
