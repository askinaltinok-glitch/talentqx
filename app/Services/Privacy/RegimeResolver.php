<?php

namespace App\Services\Privacy;

use Illuminate\Support\Facades\Log;

class RegimeResolver
{
    /**
     * EU/EEA country codes (ISO 3166-1 alpha-2)
     * Includes EU member states + EEA (Norway, Iceland, Liechtenstein) + UK (post-Brexit maintains GDPR)
     */
    private const EU_COUNTRIES = [
        'AT', // Austria
        'BE', // Belgium
        'BG', // Bulgaria
        'HR', // Croatia
        'CY', // Cyprus
        'CZ', // Czech Republic
        'DK', // Denmark
        'EE', // Estonia
        'FI', // Finland
        'FR', // France
        'DE', // Germany
        'GR', // Greece
        'HU', // Hungary
        'IE', // Ireland
        'IT', // Italy
        'LV', // Latvia
        'LT', // Lithuania
        'LU', // Luxembourg
        'MT', // Malta
        'NL', // Netherlands
        'PL', // Poland
        'PT', // Portugal
        'RO', // Romania
        'SK', // Slovakia
        'SI', // Slovenia
        'ES', // Spain
        'SE', // Sweden
        // EEA countries
        'NO', // Norway
        'IS', // Iceland
        'LI', // Liechtenstein
        // UK maintains GDPR-equivalent standards
        'GB', // United Kingdom
    ];

    /**
     * Resolve the applicable privacy regime
     *
     * Priority:
     * 1. If locale is 'tr' -> KVKK (Turkey's data protection law)
     * 2. If IP country is in EU/EEA list -> GDPR
     * 3. Otherwise -> GLOBAL (fallback)
     */
    public function resolve(?string $locale = null, ?string $ipCountry = null): array
    {
        $locale = strtolower($locale ?? 'en');
        $ipCountry = strtoupper($ipCountry ?? '');

        // Rule 1: Turkish locale -> KVKK
        if ($locale === 'tr') {
            return $this->buildResult('KVKK', $locale, 'TR');
        }

        // Rule 2: EU/EEA country -> GDPR
        if ($ipCountry && in_array($ipCountry, self::EU_COUNTRIES, true)) {
            return $this->buildResult('GDPR', $locale, $ipCountry);
        }

        // Rule 3: Turkey by IP (even if locale is not 'tr')
        if ($ipCountry === 'TR') {
            return $this->buildResult('KVKK', $locale, $ipCountry);
        }

        // Fallback: GLOBAL
        return $this->buildResult('GLOBAL', $locale, $ipCountry ?: 'XX');
    }

    /**
     * Resolve regime from HTTP request
     */
    public function resolveFromRequest($request): array
    {
        $locale = $this->extractLocale($request);
        $ipCountry = $this->resolveCountryFromIp($request->ip());

        return $this->resolve($locale, $ipCountry);
    }

    /**
     * Extract locale from request
     */
    private function extractLocale($request): string
    {
        // Check explicit locale parameter
        if ($request->has('locale')) {
            return $request->input('locale');
        }

        // Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $parts = explode(',', $acceptLanguage);
            $primaryLang = explode('-', $parts[0])[0];
            return strtolower(trim($primaryLang));
        }

        return 'en';
    }

    /**
     * Resolve country from IP address
     * Uses a simple GeoIP lookup (can be extended with proper GeoIP service)
     */
    private function resolveCountryFromIp(?string $ip): ?string
    {
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            return null;
        }

        // Try to use GeoIP if available
        if (function_exists('geoip_country_code_by_name')) {
            try {
                $country = geoip_country_code_by_name($ip);
                if ($country) {
                    return strtoupper($country);
                }
            } catch (\Exception $e) {
                Log::debug('GeoIP lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
            }
        }

        // Fallback: Check for Cloudflare header
        $cfCountry = request()->header('CF-IPCountry');
        if ($cfCountry && $cfCountry !== 'XX') {
            return strtoupper($cfCountry);
        }

        // Fallback: Check for X-Geo-Country header (common in CDNs)
        $geoCountry = request()->header('X-Geo-Country');
        if ($geoCountry) {
            return strtoupper($geoCountry);
        }

        return null;
    }

    /**
     * Build the result array
     */
    private function buildResult(string $regime, string $locale, string $country): array
    {
        return [
            'regime' => $regime,
            'locale' => $locale,
            'country' => $country,
            'policy_version' => config('privacy.current_version', '2026-01'),
            'policy_urls' => $this->getPolicyUrls($regime, $locale),
            'regime_info' => $this->getRegimeInfo($regime),
        ];
    }

    /**
     * Get policy URLs for the regime
     */
    private function getPolicyUrls(string $regime, string $locale): array
    {
        $baseUrl = config('app.url', 'https://octopus-ai.net');

        return [
            'privacy_policy' => "{$baseUrl}/privacy/{$regime}/{$locale}",
            'cookie_policy' => "{$baseUrl}/cookies/{$regime}/{$locale}",
            'data_processing' => "{$baseUrl}/dpa/{$regime}/{$locale}",
        ];
    }

    /**
     * Get regime-specific information
     */
    private function getRegimeInfo(string $regime): array
    {
        return match ($regime) {
            'KVKK' => [
                'name' => 'KVKK',
                'full_name' => 'Kişisel Verilerin Korunması Kanunu',
                'authority' => 'Kişisel Verileri Koruma Kurumu (KVKK)',
                'authority_url' => 'https://www.kvkk.gov.tr',
                'rights' => ['access', 'rectification', 'erasure', 'objection', 'portability'],
            ],
            'GDPR' => [
                'name' => 'GDPR',
                'full_name' => 'General Data Protection Regulation',
                'authority' => 'Data Protection Authority (varies by country)',
                'authority_url' => 'https://edpb.europa.eu',
                'rights' => ['access', 'rectification', 'erasure', 'restriction', 'portability', 'objection', 'automated_decision'],
            ],
            default => [
                'name' => 'GLOBAL',
                'full_name' => 'Global Privacy Standards',
                'authority' => 'N/A',
                'authority_url' => null,
                'rights' => ['access', 'rectification', 'erasure'],
            ],
        };
    }

    /**
     * Check if a country is in EU/EEA
     */
    public function isEuCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), self::EU_COUNTRIES, true);
    }

    /**
     * Get list of EU countries
     */
    public function getEuCountries(): array
    {
        return self::EU_COUNTRIES;
    }
}
