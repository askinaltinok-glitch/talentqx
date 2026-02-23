<?php

/**
 * Certificate Validity Configuration
 *
 * Default validity periods (in months) per certificate type.
 * Used by CertificateService to compute expiry risk levels when
 * no explicit expiry_date is set on the certificate record.
 *
 * Risk levels:
 *   - valid:         > 90 days remaining
 *   - expiring_soon: 30-90 days remaining (yellow)
 *   - critical:      1-30 days remaining (red)
 *   - expired:       past expiry date
 */

return [

    // Default validity periods in months (from issue date)
    'default_validity_months' => [
        'stcw'               => 60,   // 5 years
        'coc'                => 60,   // 5 years
        'goc'                => 60,   // 5 years
        'medical'            => 24,   // 2 years
        'passport'           => 120,  // 10 years
        'seamans_book'       => 60,   // 5 years
        'flag_endorsement'   => 12,   // 1 year (varies by flag state)
        'tanker_endorsement' => 60,   // 5 years
        'ecdis'              => 60,   // 5 years
        'arpa'               => 60,   // 5 years
        'brm'                => 60,   // 5 years
        'erm'                => 60,   // 5 years
        'hazmat'             => 60,   // 5 years
    ],

    // Risk threshold in days
    'thresholds' => [
        'expiring_soon' => 90,  // Yellow: <= 90 days
        'critical'      => 30,  // Red: <= 30 days
    ],

    // Certificate types that NEVER expire (no expiry check)
    'no_expiry' => [
        // None by default — all maritime certs have validity periods
    ],

];
