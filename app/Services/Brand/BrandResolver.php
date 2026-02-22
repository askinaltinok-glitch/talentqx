<?php

namespace App\Services\Brand;

use App\Models\FormInterview;
use App\Models\PoolCandidate;

class BrandResolver
{
    /**
     * Resolve brand config array by platform code.
     * Falls back to default brand if code is unknown.
     */
    public static function resolve(string $platformCode): array
    {
        $brand = config("brands.brands.{$platformCode}");

        if ($brand) {
            return $brand;
        }

        $default = config('brands.default', 'octopus');
        return config("brands.brands.{$default}", []);
    }

    /**
     * Resolve brand from a FormInterview.
     * Chain: interview.platform_code → industry_code mapping → default.
     */
    public static function fromInterview(FormInterview $interview): array
    {
        // 1. Explicit platform_code on the interview
        if ($interview->platform_code) {
            return self::resolve($interview->platform_code);
        }

        // 2. Industry code mapping
        $code = self::codeFromIndustry($interview->industry_code);
        if ($code) {
            return self::resolve($code);
        }

        // 3. Default
        return self::resolve(config('brands.default', 'octopus'));
    }

    /**
     * Resolve brand from a PoolCandidate.
     * Chain: candidate.primary_industry mapping → latest interview's platform_code → default.
     */
    public static function fromCandidate(PoolCandidate $candidate): array
    {
        // 1. Industry mapping from candidate's primary_industry
        $code = self::codeFromIndustry($candidate->primary_industry);
        if ($code) {
            return self::resolve($code);
        }

        // 2. Latest interview's platform_code
        $latestInterview = $candidate->formInterviews()->first();
        if ($latestInterview && $latestInterview->platform_code) {
            return self::resolve($latestInterview->platform_code);
        }

        // 3. Latest interview's industry_code mapping
        if ($latestInterview) {
            $code = self::codeFromIndustry($latestInterview->industry_code);
            if ($code) {
                return self::resolve($code);
            }
        }

        // 4. Default
        return self::resolve(config('brands.default', 'octopus'));
    }

    /**
     * Look up platform code from industry code via config map.
     */
    public static function codeFromIndustry(?string $industryCode): ?string
    {
        if (!$industryCode) {
            return null;
        }

        return config("brands.industry_map.{$industryCode}");
    }

    /**
     * Get localized email subject for a brand and mail type.
     * Falls back to EN if the requested locale is not available.
     */
    public static function subject(array $brand, string $mailType, string $locale): string
    {
        $subjects = $brand['subjects'][$mailType] ?? [];

        return $subjects[$locale] ?? $subjects['en'] ?? '';
    }
}
