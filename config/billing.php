<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Phase 1: Sales-driven model (checkout disabled)
    | All subscriptions are activated manually by admin after sales process.
    |
    */

    // Online checkout is DISABLED in Phase 1
    // Subscriptions are managed through sales process
    'checkout_enabled' => env('BILLING_CHECKOUT_ENABLED', false),

    // Grace period after subscription expiration (days)
    'grace_period_days' => env('BILLING_GRACE_PERIOD_DAYS', 60),

    // Sales contact information
    'sales_email' => env('BILLING_SALES_EMAIL', 'sales@talentqx.com'),
    'sales_phone' => env('BILLING_SALES_PHONE', '+90 212 123 45 67'),

    // Future: Stripe configuration
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // Subscription plans (sync with BillingService::PLANS)
    'plans' => [
        'mini' => [
            'stripe_price_id' => env('STRIPE_PRICE_MINI'),
        ],
        'midi' => [
            'stripe_price_id' => env('STRIPE_PRICE_MIDI'),
        ],
        'pro' => [
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
        ],
    ],
];
