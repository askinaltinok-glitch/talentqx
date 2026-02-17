<?php

namespace App\Services\Consent;

use App\Models\CandidateConsent;
use App\Models\FormInterview;
use Illuminate\Http\Request;

class ConsentService
{
    /**
     * All valid consent types (whitelist).
     */
    public const VALID_CONSENT_TYPES = [
        'data_processing',
        'data_retention',
        'data_sharing',
        'marketing',
    ];

    /**
     * Required consents by regulation.
     */
    public const REQUIRED_BY_REGULATION = [
        'KVKK' => ['data_processing', 'data_retention'],
        'GDPR' => ['data_processing', 'data_retention'],
        'GENERIC' => ['data_processing'],
    ];

    /**
     * EU/EEA country codes for GDPR.
     */
    private const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        'IS', 'LI', 'NO', // EEA
        'GB', // UK post-Brexit
    ];

    /**
     * Detect regulation based on country code.
     */
    public function detectRegulation(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode);

        if ($countryCode === 'TR') {
            return 'KVKK';
        }

        if (in_array($countryCode, self::EU_COUNTRIES)) {
            return 'GDPR';
        }

        return 'GENERIC';
    }

    /**
     * Get required consents for a regulation.
     */
    public function getRequiredConsents(string $regulation): array
    {
        return self::REQUIRED_BY_REGULATION[$regulation] ?? self::REQUIRED_BY_REGULATION['GENERIC'];
    }

    /**
     * Record consents for a form interview.
     * Uses updateOrCreate for idempotent behavior.
     *
     * @param FormInterview $interview
     * @param array $consents Array of consent type strings
     * @param string $regulation KVKK, GDPR, or GENERIC
     * @param Request|null $request For IP/UA capture
     * @return array Created/updated consent records
     */
    public function recordConsents(
        FormInterview $interview,
        array $consents,
        string $regulation,
        ?Request $request = null
    ): array {
        $records = [];
        $now = now();
        $version = $this->getCurrentConsentVersion($regulation);

        foreach ($consents as $consentType) {
            // Only record valid consent types
            if (!in_array($consentType, self::VALID_CONSENT_TYPES)) {
                continue;
            }

            // Use updateOrCreate for idempotent behavior
            $record = CandidateConsent::updateOrCreate(
                [
                    'form_interview_id' => $interview->id,
                    'consent_type' => $consentType,
                ],
                [
                    'consent_version' => $version,
                    'regulation' => $regulation,
                    'granted' => true,
                    'ip_address' => $request?->ip(),
                    'user_agent' => $request?->userAgent(),
                    'collection_method' => 'web',
                    'consented_at' => $now,
                    'withdrawn_at' => null, // Clear any previous withdrawal
                    'expires_at' => $this->calculateExpiryDate($consentType),
                ]
            );

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Get consent status for an interview.
     * Returns status per consent type: valid, withdrawn, expired, missing
     */
    public function getConsentStatus(FormInterview $interview): array
    {
        $consents = $interview->consents()->get()->keyBy('consent_type');

        // Determine regulation from first consent or default
        $firstConsent = $consents->first();
        $regulation = $firstConsent?->regulation ?? 'GENERIC';

        $status = [];
        $lastConsentedAt = null;

        // Check all consent types
        foreach (self::VALID_CONSENT_TYPES as $type) {
            $consent = $consents->get($type);

            if (!$consent) {
                $status[$type] = 'missing';
                continue;
            }

            // Update last consented at
            if ($consent->consented_at && (!$lastConsentedAt || $consent->consented_at > $lastConsentedAt)) {
                $lastConsentedAt = $consent->consented_at;
            }

            // Determine status
            if ($consent->withdrawn_at !== null) {
                $status[$type] = 'withdrawn';
            } elseif ($consent->expires_at !== null && $consent->expires_at->isPast()) {
                $status[$type] = 'expired';
            } elseif ($consent->granted) {
                $status[$type] = 'valid';
            } else {
                $status[$type] = 'missing';
            }
        }

        // Check if all required consents are valid
        $requiredConsents = $this->getRequiredConsents($regulation);
        $consentsRecorded = true;
        foreach ($requiredConsents as $required) {
            if (($status[$required] ?? 'missing') !== 'valid') {
                $consentsRecorded = false;
                break;
            }
        }

        return [
            'regulation' => $regulation,
            'data_processing' => $status['data_processing'] ?? 'missing',
            'data_retention' => $status['data_retention'] ?? 'missing',
            'data_sharing' => $status['data_sharing'] ?? 'missing',
            'marketing' => $status['marketing'] ?? 'missing',
            'consents_recorded' => $consentsRecorded,
            'last_consented_at' => $lastConsentedAt?->toIso8601String(),
        ];
    }

    /**
     * Get current consent text version.
     */
    private function getCurrentConsentVersion(string $regulation): string
    {
        return strtolower($regulation) . '-' . date('Y-m');
    }

    /**
     * Calculate consent expiry date based on type.
     */
    private function calculateExpiryDate(string $type): ?\DateTime
    {
        // Data processing and retention don't expire (until withdrawn)
        if (in_array($type, ['data_processing', 'data_retention'])) {
            return null;
        }

        // Marketing consent expires after 1 year
        if ($type === 'marketing') {
            return now()->addYear()->toDateTime();
        }

        // Data sharing doesn't expire
        return null;
    }
}
