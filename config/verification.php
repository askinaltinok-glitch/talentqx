<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Registration Verification Settings
    |--------------------------------------------------------------------------
    |
    | Phase 1: Feature flags only, no user-facing flow yet.
    | Turkey -> SMS (Verimor) = primary
    | Global -> Email verification = primary
    |
    */

    // Feature flags (all disabled for Phase 1)
    'sms_enabled' => env('ENABLE_SMS_VERIFICATION', false),
    'email_enabled' => env('ENABLE_EMAIL_VERIFICATION', false),

    // SMS Provider (Turkey)
    'sms_provider' => env('SMS_PROVIDER', 'verimor'),

    // Verimor SMS settings
    'verimor' => [
        'api_id' => env('VERIMOR_API_ID'),
        'api_key' => env('VERIMOR_API_KEY'),
        'sender' => env('VERIMOR_SENDER', 'TalentQX'),
    ],

    // Verification code settings
    'code_length' => 6,
    'code_expiry_minutes' => 10,
    'max_attempts' => 3,
    'cooldown_minutes' => 1,

    // Country-based routing
    'country_routing' => [
        'TR' => 'sms',      // Turkey -> SMS primary
        'default' => 'email', // Others -> Email primary
    ],
];
