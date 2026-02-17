<?php

/**
 * Data Retention Policy Configuration
 *
 * Compliant with:
 * - KVKK (Turkish Personal Data Protection Law)
 * - GDPR (General Data Protection Regulation)
 *
 * Retention periods are defined in days.
 * null = keep forever (until explicit deletion request)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Retention Periods (in days)
    |--------------------------------------------------------------------------
    |
    | These values define how long each type of data is retained.
    | After the retention period, data should be anonymized or deleted.
    |
    */

    'periods' => [
        // Form interview data (candidate responses)
        'form_interviews' => [
            'completed' => 365 * 2, // 2 years after completion
            'incomplete' => 90,     // 90 days if never completed
        ],

        // Interview outcomes (ground truth)
        'interview_outcomes' => [
            'default' => 365 * 5, // 5 years (for long-term model training)
        ],

        // Consent records (kept longer for compliance proof)
        'candidate_consents' => [
            'default' => 365 * 10, // 10 years (legal requirement)
        ],

        // Audit logs
        'audit_logs' => [
            'default' => 365 * 7, // 7 years (accounting requirement)
        ],

        // Template change logs
        'template_audit_logs' => [
            'default' => 365 * 3, // 3 years
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Anonymization Rules
    |--------------------------------------------------------------------------
    |
    | Define which fields should be anonymized vs deleted.
    | Anonymized data can still be used for aggregate analytics.
    |
    */

    'anonymization' => [
        'form_interviews' => [
            // Fields to completely remove
            'delete_fields' => [
                'meta',           // Candidate-provided context (PII)
                'admin_notes',    // May contain identifying info
            ],

            // Fields to hash/anonymize
            'anonymize_fields' => [
                'template_json' => 'sha256_only', // Keep only hash, remove content
            ],

            // Fields to keep (for analytics)
            'keep_fields' => [
                'id',
                'version',
                'language',
                'position_code',
                'template_position_code',
                'industry_code',
                'status',
                'template_json_sha256',
                'final_score',
                'decision',
                'raw_final_score',
                'calibrated_score',
                'z_score',
                'policy_code',
                'created_at',
                'completed_at',
            ],
        ],

        'form_interview_answers' => [
            // Fields to completely remove
            'delete_fields' => [
                'answer_text', // Candidate's actual response (PII)
            ],

            // Fields to keep (for analytics)
            'keep_fields' => [
                'id',
                'form_interview_id',
                'slot',
                'competency',
                'created_at',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | KVKK-Specific Configuration
    |--------------------------------------------------------------------------
    */

    'kvkk' => [
        // Required consent types for Turkish citizens
        'required_consents' => [
            'data_processing',  // Explicit consent for processing
            'data_retention',   // Consent to data retention period
        ],

        // Maximum retention without explicit consent (days)
        'max_retention_without_consent' => 30,

        // Notification period before deletion (days)
        'deletion_notice_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | GDPR-Specific Configuration
    |--------------------------------------------------------------------------
    */

    'gdpr' => [
        // Required consent types for EU citizens
        'required_consents' => [
            'data_processing',
        ],

        // Right to be forgotten: max processing time (days)
        'erasure_request_deadline' => 30,

        // Data portability: max processing time (days)
        'portability_request_deadline' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated Cleanup Schedule
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        // Enable automated cleanup
        'enabled' => env('RETENTION_CLEANUP_ENABLED', true),

        // Run cleanup at this time (cron expression)
        'schedule' => '0 3 * * *', // 3 AM daily

        // Batch size per run (to avoid memory issues)
        'batch_size' => 1000,

        // Dry run mode (log only, don't delete)
        'dry_run' => env('RETENTION_CLEANUP_DRY_RUN', false),

        // Notification email for cleanup reports
        'notify_email' => env('RETENTION_CLEANUP_NOTIFY_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Jurisdiction Detection
    |--------------------------------------------------------------------------
    |
    | Rules for determining which regulation applies to a record.
    |
    */

    'jurisdiction' => [
        // Default regulation
        'default' => 'KVKK',

        // Country to regulation mapping
        'country_map' => [
            'TR' => 'KVKK',
            'DE' => 'GDPR',
            'FR' => 'GDPR',
            'IT' => 'GDPR',
            'ES' => 'GDPR',
            'NL' => 'GDPR',
            'BE' => 'GDPR',
            'AT' => 'GDPR',
            'PL' => 'GDPR',
            'PT' => 'GDPR',
            'GR' => 'GDPR',
            'CZ' => 'GDPR',
            'HU' => 'GDPR',
            'RO' => 'GDPR',
            'BG' => 'GDPR',
            'SE' => 'GDPR',
            'FI' => 'GDPR',
            'DK' => 'GDPR',
            'IE' => 'GDPR',
            'SK' => 'GDPR',
            'HR' => 'GDPR',
            'SI' => 'GDPR',
            'EE' => 'GDPR',
            'LV' => 'GDPR',
            'LT' => 'GDPR',
            'CY' => 'GDPR',
            'LU' => 'GDPR',
            'MT' => 'GDPR',
            // EEA
            'NO' => 'GDPR',
            'IS' => 'GDPR',
            'LI' => 'GDPR',
            // UK (post-Brexit has similar rules)
            'GB' => 'GDPR',
        ],
    ],
];
