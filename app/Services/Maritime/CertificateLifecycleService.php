<?php

namespace App\Services\Maritime;

use App\Models\SeafarerCertificate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * CertificateLifecycleService
 *
 * Computes expiry risk levels for seafarer certificates and provides
 * batch helpers for compliance dashboards and decision packets.
 */
class CertificateLifecycleService
{
    public const RISK_VALID = 'valid';
    public const RISK_EXPIRING_SOON = 'expiring_soon';
    public const RISK_CRITICAL = 'critical';
    public const RISK_EXPIRED = 'expired';
    public const RISK_UNKNOWN = 'unknown';

    // CSS-friendly color codes for frontend
    public const RISK_COLORS = [
        self::RISK_VALID         => 'green',
        self::RISK_EXPIRING_SOON => 'yellow',
        self::RISK_CRITICAL      => 'red',
        self::RISK_EXPIRED       => 'red',
        self::RISK_UNKNOWN       => 'gray',
    ];

    /**
     * Compute the expiry risk level for a single certificate.
     *
     * @return array{level: string, color: string, days_remaining: int|null, label: string}
     */
    public function getExpiryRiskLevel(SeafarerCertificate $cert): array
    {
        // Use CertificateValidityResolver for 3-tier hierarchy
        $resolver = app(CertificateValidityResolver::class);
        [$expiresAt, $expirySource] = $resolver->resolve($cert);

        // No expiry date and can't compute → unknown
        if (!$expiresAt) {
            return [
                'level' => self::RISK_UNKNOWN,
                'color' => self::RISK_COLORS[self::RISK_UNKNOWN],
                'days_remaining' => null,
                'label' => 'No expiry data',
                'expiry_source' => $expirySource,
            ];
        }

        $now = Carbon::now();
        $expiresAt = Carbon::parse($expiresAt);
        $daysRemaining = (int) $now->diffInDays($expiresAt, false);

        $thresholds = config('certificate_validity.thresholds', [
            'expiring_soon' => 90,
            'critical' => 30,
        ]);

        if ($daysRemaining < 0) {
            $level = self::RISK_EXPIRED;
            $label = 'Expired ' . abs($daysRemaining) . ' days ago';
        } elseif ($daysRemaining <= $thresholds['critical']) {
            $level = self::RISK_CRITICAL;
            $label = $daysRemaining === 0 ? 'Expires today' : "Expires in {$daysRemaining} days";
        } elseif ($daysRemaining <= $thresholds['expiring_soon']) {
            $level = self::RISK_EXPIRING_SOON;
            $label = "Expires in {$daysRemaining} days";
        } else {
            $level = self::RISK_VALID;
            $label = "Valid ({$daysRemaining} days)";
        }

        return [
            'level' => $level,
            'color' => self::RISK_COLORS[$level],
            'days_remaining' => $daysRemaining,
            'label' => $label,
            'expiry_source' => $expirySource,
        ];
    }

    /**
     * Enrich a collection of certificates with risk level data.
     *
     * @param Collection<int, SeafarerCertificate> $certificates
     * @return Collection<int, array>
     */
    public function enrichWithRiskLevels(Collection $certificates): Collection
    {
        return $certificates->map(function (SeafarerCertificate $cert) {
            $risk = $this->getExpiryRiskLevel($cert);
            return [
                'id' => $cert->id,
                'certificate_type' => $cert->certificate_type,
                'certificate_code' => $cert->certificate_code,
                'issuing_authority' => $cert->issuing_authority,
                'issuing_country' => $cert->issuing_country,
                'issued_at' => $cert->issued_at?->toDateString(),
                'expires_at' => $cert->expires_at?->toDateString(),
                'verification_status' => $cert->verification_status,
                'risk_level' => $risk['level'],
                'risk_color' => $risk['color'],
                'days_remaining' => $risk['days_remaining'],
                'risk_label' => $risk['label'],
                'expiry_source' => $risk['expiry_source'],
                'self_declared' => (bool) $cert->self_declared,
            ];
        });
    }

    /**
     * Get overall compliance summary for a candidate's certificates.
     *
     * @param Collection<int, SeafarerCertificate> $certificates
     * @return array{total: int, valid: int, expiring_soon: int, critical: int, expired: int, unknown: int, overall_status: string}
     */
    public function getComplianceSummary(Collection $certificates): array
    {
        $counts = [
            'total' => $certificates->count(),
            self::RISK_VALID => 0,
            self::RISK_EXPIRING_SOON => 0,
            self::RISK_CRITICAL => 0,
            self::RISK_EXPIRED => 0,
            self::RISK_UNKNOWN => 0,
        ];

        foreach ($certificates as $cert) {
            $risk = $this->getExpiryRiskLevel($cert);
            $counts[$risk['level']]++;
        }

        // Overall status: worst risk level found
        $overallStatus = self::RISK_VALID;
        if ($counts[self::RISK_EXPIRED] > 0) {
            $overallStatus = self::RISK_EXPIRED;
        } elseif ($counts[self::RISK_CRITICAL] > 0) {
            $overallStatus = self::RISK_CRITICAL;
        } elseif ($counts[self::RISK_EXPIRING_SOON] > 0) {
            $overallStatus = self::RISK_EXPIRING_SOON;
        } elseif ($counts[self::RISK_UNKNOWN] > 0 && $counts[self::RISK_VALID] === 0) {
            $overallStatus = self::RISK_UNKNOWN;
        }

        return [
            'total' => $counts['total'],
            'valid' => $counts[self::RISK_VALID],
            'expiring_soon' => $counts[self::RISK_EXPIRING_SOON],
            'critical' => $counts[self::RISK_CRITICAL],
            'expired' => $counts[self::RISK_EXPIRED],
            'unknown' => $counts[self::RISK_UNKNOWN],
            'overall_status' => $overallStatus,
            'overall_color' => self::RISK_COLORS[$overallStatus],
        ];
    }
}
