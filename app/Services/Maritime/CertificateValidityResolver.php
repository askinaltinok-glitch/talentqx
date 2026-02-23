<?php

namespace App\Services\Maritime;

use App\Models\CompanyCertificateRule;
use App\Models\CountryCertificateRule;
use App\Models\SeafarerCertificate;
use Carbon\Carbon;

/**
 * CertificateValidityResolver
 *
 * 3-tier override hierarchy for certificate expiry estimation:
 *   Tier 1 — Company override (strictest)
 *   Tier 2 — Country rule
 *   Tier 3 — Global config default
 *
 * Returns [Carbon|null, string expiry_source].
 */
class CertificateValidityResolver
{
    /**
     * Resolve expiry date and source for a certificate.
     *
     * @return array{0: Carbon|null, 1: string}
     */
    public function resolve(SeafarerCertificate $cert): array
    {
        // If expiry date is already set → uploaded by user
        if ($cert->expires_at) {
            return [Carbon::parse($cert->expires_at), SeafarerCertificate::EXPIRY_SOURCE_UPLOADED];
        }

        // If no issued_at → can't estimate
        if (!$cert->issued_at) {
            return [null, SeafarerCertificate::EXPIRY_SOURCE_UNKNOWN];
        }

        $issuedAt = Carbon::parse($cert->issued_at);
        $certType = $cert->certificate_type;

        // Tier 1 — Company override
        $companyId = $this->resolveCompanyId($cert);
        if ($companyId) {
            $months = CompanyCertificateRule::getValidityMonths($companyId, $certType);
            if ($months !== null) {
                return [
                    $issuedAt->copy()->addMonths($months),
                    SeafarerCertificate::EXPIRY_SOURCE_ESTIMATED_COMPANY,
                ];
            }
        }

        // Tier 2 — Country override
        $countryCode = $cert->issuing_country ?? $cert->candidate?->license_country;
        if ($countryCode) {
            $months = CountryCertificateRule::getValidityMonths($countryCode, $certType);
            if ($months !== null) {
                return [
                    $issuedAt->copy()->addMonths($months),
                    SeafarerCertificate::EXPIRY_SOURCE_ESTIMATED_COUNTRY,
                ];
            }
        }

        // Tier 3 — Global config default
        $defaultMonths = config("certificate_validity.default_validity_months.{$certType}");
        if ($defaultMonths) {
            return [
                $issuedAt->copy()->addMonths($defaultMonths),
                SeafarerCertificate::EXPIRY_SOURCE_ESTIMATED_DEFAULT,
            ];
        }

        // No rule found
        return [null, SeafarerCertificate::EXPIRY_SOURCE_UNKNOWN];
    }

    /**
     * Resolve company ID from the certificate's candidate source.
     */
    private function resolveCompanyId(SeafarerCertificate $cert): ?string
    {
        $candidate = $cert->candidate;
        if (!$candidate) {
            return null;
        }

        return $candidate->source_company_id ?? null;
    }
}
