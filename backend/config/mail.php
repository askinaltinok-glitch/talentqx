<?php

return [

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@talentqx.com'),
        'name' => env('MAIL_FROM_NAME', 'TalentQX'),
    ],

    'reply_to' => [
        'address' => env('MAIL_REPLY_TO_ADDRESS', 'support@talentqx.com'),
        'name' => env('MAIL_REPLY_TO_NAME', 'TalentQX Support'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, emails will only be sent to whitelisted addresses.
    | This is useful for testing before going live.
    |
    */

    'safety_mode' => env('MAIL_SAFETY_MODE', false),

    'test_whitelist' => array_filter(
        array_map('trim', explode(',', env('MAIL_TEST_WHITELIST', '')))
    ),

];
