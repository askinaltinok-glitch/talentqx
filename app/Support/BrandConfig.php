<?php

namespace App\Support;

use App\Models\Company;

/**
 * Centralized brand configuration — single source of truth.
 *
 * Maps platform key ('octopus' or 'talentqx') to:
 *   - domain, login URL, support email, brand name, tagline, colors
 *
 * RULES (never change without reviewing all email templates):
 *   octopus   → octopus-ai.net  → Maritime companies → DB: talentqx
 *   talentqx  → talentqx.com    → General HR         → DB: talentqx_hr
 */
final class BrandConfig
{
    private const BRANDS = [
        Company::PLATFORM_OCTOPUS => [
            'domain'        => 'octopus-ai.net',
            'login_url'     => 'https://octopus-ai.net/portal/login',
            'support_email' => 'support@octopus-ai.net',
            'brand_name'    => 'Octopus AI',
            'tagline'       => 'AI-Powered Maritime Crew Assessment',
            'primary_color' => '#0f4c81',
            'gradient'      => 'linear-gradient(135deg, #0f4c81 0%, #1a6fb5 100%)',
            'footer_bg'     => '#0f2b40',
            'logo_url'      => 'https://talentqx.com/assets/octopus-logo-email.png',
        ],
        Company::PLATFORM_TALENTQX => [
            'domain'        => 'talentqx.com',
            'login_url'     => 'https://app.talentqx.com/portal/login',
            'support_email' => 'support@talentqx.com',
            'brand_name'    => 'TalentQX',
            'tagline'       => 'Yapay Zeka Destekli Ise Alim Platformu',
            'primary_color' => '#667eea',
            'gradient'      => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'footer_bg'     => '#1a1a2e',
            'logo_url'      => 'https://talentqx.com/assets/logo-email.png',
        ],
    ];

    /**
     * Get full brand config array for a platform key.
     */
    public static function for(string $platform): array
    {
        return self::BRANDS[$platform] ?? self::BRANDS[Company::PLATFORM_TALENTQX];
    }

    /**
     * Get brand config from a Company model.
     */
    public static function forCompany(Company $company): array
    {
        return self::for($company->platform ?? Company::PLATFORM_TALENTQX);
    }

    /**
     * Resolve platform from the current request brand context.
     * Falls back to 'octopus' if unset.
     */
    public static function currentPlatform(): string
    {
        return app()->bound('current_brand')
            ? (app('current_brand') === 'octopus' ? Company::PLATFORM_OCTOPUS : Company::PLATFORM_TALENTQX)
            : Company::PLATFORM_TALENTQX;
    }

    /**
     * Get a single config value for a platform.
     */
    public static function get(string $platform, string $key): string
    {
        return self::for($platform)[$key] ?? '';
    }

    /**
     * Get login URL for a platform.
     */
    public static function loginUrl(string $platform): string
    {
        return self::get($platform, 'login_url');
    }

    /**
     * Get support email for a platform.
     */
    public static function supportEmail(string $platform): string
    {
        return self::get($platform, 'support_email');
    }

    /**
     * Get brand name for a platform.
     */
    public static function brandName(string $platform): string
    {
        return self::get($platform, 'brand_name');
    }
}
