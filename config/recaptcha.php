<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA v3 Settings
    |--------------------------------------------------------------------------
    |
    | reCAPTCHA v3 (invisible) - score-based validation.
    | Score: 0.0 = bot, 1.0 = human
    |
    */

    'enabled' => env('RECAPTCHA_ENABLED', true),

    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),

    // Minimum score threshold (0.0-1.0, reject below this)
    'min_score' => env('RECAPTCHA_MIN_SCORE', 0.3),

    // Actions to verify
    'actions' => [
        'demo_form' => 'submit_demo',
        'contact_form' => 'submit_contact',
        'newsletter' => 'subscribe_newsletter',
    ],
];
