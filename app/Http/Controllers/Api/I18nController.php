<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class I18nController extends Controller
{
    private const SUPPORTED_LOCALES = ['en', 'tr', 'ru', 'az', 'fil', 'id', 'uk'];
    private const CACHE_VERSION = 'v1';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Map Accept-Language subtags to our supported locale codes.
     * e.g. en-US → en, fil-PH → fil, uk-UA → uk, id-ID → id
     */
    private const LOCALE_ALIASES = [
        'en-us' => 'en', 'en-gb' => 'en', 'en-au' => 'en',
        'tr-tr' => 'tr',
        'ru-ru' => 'ru',
        'az-az' => 'az',
        'fil-ph' => 'fil', 'tl' => 'fil', 'tl-ph' => 'fil',
        'id-id' => 'id', 'ms-id' => 'id',
        'uk-ua' => 'uk',
    ];

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $cacheKey = 'i18n:' . self::CACHE_VERSION . ':maritime:' . $locale;

        $merged = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            $enKeys = __('maritime', [], 'en');

            if (!is_array($enKeys)) {
                return [];
            }

            if ($locale !== 'en') {
                $localeKeys = __('maritime', [], $locale);
                if (!is_array($localeKeys)) {
                    $localeKeys = [];
                }
            } else {
                $localeKeys = $enKeys;
            }

            $merged = [];
            foreach ($enKeys as $key => $enValue) {
                $merged[$key] = $localeKeys[$key] ?? $enValue;
            }

            return $merged;
        });

        return response()->json($merged)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Resolve locale from:
     *   1. ?locale= query param (exact match or alias)
     *   2. Accept-Language header (first supported match)
     *   3. Default to 'en'
     */
    private function resolveLocale(Request $request): string
    {
        // 1. Explicit query param
        $raw = $request->query('locale');
        if ($raw !== null) {
            $normalized = $this->normalizeLocaleTag($raw);
            if (in_array($normalized, self::SUPPORTED_LOCALES, true)) {
                return $normalized;
            }
        }

        // 2. Accept-Language header
        $accept = $request->header('Accept-Language', '');
        if ($accept) {
            // Parse tags like "en-US,en;q=0.9,fil-PH;q=0.8"
            $tags = preg_split('/\s*,\s*/', $accept);
            foreach ($tags as $tag) {
                $parts = explode(';', $tag);
                $langTag = trim($parts[0]);
                $normalized = $this->normalizeLocaleTag($langTag);
                if (in_array($normalized, self::SUPPORTED_LOCALES, true)) {
                    return $normalized;
                }
            }
        }

        return 'en';
    }

    /**
     * Normalize a locale tag to our supported locale code.
     * "en-US" → "en", "fil-PH" → "fil", "uk-UA" → "uk", etc.
     */
    private function normalizeLocaleTag(string $tag): string
    {
        $lower = strtolower(trim($tag));

        // Direct match
        if (in_array($lower, self::SUPPORTED_LOCALES, true)) {
            return $lower;
        }

        // Alias match (en-us → en, fil-ph → fil)
        if (isset(self::LOCALE_ALIASES[$lower])) {
            return self::LOCALE_ALIASES[$lower];
        }

        // Try base language (en-US → en, ru-RU → ru)
        $base = explode('-', $lower)[0];
        if (in_array($base, self::SUPPORTED_LOCALES, true)) {
            return $base;
        }

        // Alias on base
        if (isset(self::LOCALE_ALIASES[$base])) {
            return self::LOCALE_ALIASES[$base];
        }

        return 'en';
    }
}
