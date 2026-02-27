<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Mailbox Polling Configuration
    |--------------------------------------------------------------------------
    |
    | Each mailbox is polled via IMAP to match inbound emails to CRM leads.
    | Matching: In-Reply-To / References header -> crm_email_messages.message_id
    | Fallback: subject + from_email matching.
    |
    */

    'mailboxes' => [
        'crew' => [
            'host' => env('CRM_IMAP_CREW_HOST', ''),
            'port' => (int) env('CRM_IMAP_CREW_PORT', 993),
            'encryption' => env('CRM_IMAP_CREW_ENCRYPTION', 'ssl'),
            'username' => env('CRM_IMAP_CREW_USERNAME', 'crew@octopus-ai.net'),
            'password' => env('CRM_IMAP_CREW_PASSWORD', ''),
            'folder' => env('CRM_IMAP_CREW_FOLDER', 'INBOX'),
        ],
        'companies' => [
            'host' => env('CRM_IMAP_COMPANIES_HOST', ''),
            'port' => (int) env('CRM_IMAP_COMPANIES_PORT', 993),
            'encryption' => env('CRM_IMAP_COMPANIES_ENCRYPTION', 'ssl'),
            'username' => env('CRM_IMAP_COMPANIES_USERNAME', 'companies@octopus-ai.net'),
            'password' => env('CRM_IMAP_COMPANIES_PASSWORD', ''),
            'folder' => env('CRM_IMAP_COMPANIES_FOLDER', 'INBOX'),
        ],
        'info' => [
            'host' => env('CRM_IMAP_INFO_HOST', ''),
            'port' => (int) env('CRM_IMAP_INFO_PORT', 993),
            'encryption' => env('CRM_IMAP_INFO_ENCRYPTION', 'ssl'),
            'username' => env('CRM_IMAP_INFO_USERNAME', 'info@octopus-ai.net'),
            'password' => env('CRM_IMAP_INFO_PASSWORD', ''),
            'folder' => env('CRM_IMAP_INFO_FOLDER', 'INBOX'),
        ],
    ],

    // Maximum emails to process per poll run
    'max_per_run' => (int) env('CRM_IMAP_MAX_PER_RUN', 50),

    // Only process emails from the last N days
    'lookback_days' => (int) env('CRM_IMAP_LOOKBACK_DAYS', 7),
];
