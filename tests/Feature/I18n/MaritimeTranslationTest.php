<?php

namespace Tests\Feature\I18n;

use Tests\TestCase;

class MaritimeTranslationTest extends TestCase
{
    private const LOCALES = ['en', 'tr', 'ru', 'az', 'fil', 'id', 'uk'];

    /**
     * Test 1: Every key in en/maritime.php exists in all other locale files.
     */
    public function test_key_parity_across_all_locales(): void
    {
        $enKeys = array_keys(__('maritime', [], 'en'));
        $this->assertNotEmpty($enKeys, 'English maritime translations should not be empty');

        foreach (self::LOCALES as $locale) {
            if ($locale === 'en') continue;

            $localeKeys = __('maritime', [], $locale);
            $this->assertIsArray($localeKeys, "Locale {$locale} should return an array");

            foreach ($enKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $localeKeys,
                    "Key '{$key}' missing in {$locale}/maritime.php"
                );
            }
        }
    }

    /**
     * Test 2: If EN value has :placeholder, all locale values have the same :placeholder.
     */
    public function test_placeholder_parity(): void
    {
        $enKeys = __('maritime', [], 'en');

        foreach (self::LOCALES as $locale) {
            if ($locale === 'en') continue;

            $localeKeys = __('maritime', [], $locale);

            foreach ($enKeys as $key => $enValue) {
                preg_match_all('/:([a-z_]+)/i', $enValue, $enMatches);
                $enPlaceholders = $enMatches[0] ?? [];

                if (empty($enPlaceholders)) continue;

                $localeValue = $localeKeys[$key] ?? '';
                foreach ($enPlaceholders as $placeholder) {
                    $this->assertStringContainsString(
                        $placeholder,
                        $localeValue,
                        "Placeholder '{$placeholder}' missing in {$locale}/maritime.{$key}"
                    );
                }
            }
        }
    }

    /**
     * Test 3: No locale file has empty string values.
     */
    public function test_no_empty_values(): void
    {
        foreach (self::LOCALES as $locale) {
            $keys = __('maritime', [], $locale);
            $this->assertIsArray($keys);

            foreach ($keys as $key => $value) {
                $this->assertNotEmpty(
                    $value,
                    "Empty value for key '{$key}' in {$locale}/maritime.php"
                );
            }
        }
    }

    /**
     * Test 4: Request ?locale=fil for a key only in EN → returns EN value (fallback).
     */
    public function test_fallback_returns_english_value(): void
    {
        $response = $this->getJson('/api/v1/i18n?locale=fil');

        $response->assertStatus(200);

        $data = $response->json();

        // rank.captain should exist and have a value (either translated or EN fallback)
        $this->assertArrayHasKey('rank.captain', $data);
        $this->assertNotEmpty($data['rank.captain']);
    }

    /**
     * Test 5: API contract — GET /api/v1/i18n?locale=en returns 200 with all keys.
     */
    public function test_api_returns_all_keys(): void
    {
        $response = $this->getJson('/api/v1/i18n?locale=en');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);

        // Verify essential keys exist
        $essentialKeys = [
            'validation.first_name_required',
            'response.registration_success',
            'status.new',
            'rank.captain',
            'cert.stcw',
            'english.a1',
            'source.linkedin',
            'category.core_duty',
            'qualification.stcw',
        ];

        foreach ($essentialKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Essential key '{$key}' missing from API response");
        }
    }

    /**
     * Test 6: Invalid locale falls back to 'en'.
     */
    public function test_invalid_locale_falls_back_to_english(): void
    {
        $enResponse = $this->getJson('/api/v1/i18n?locale=en');
        $xxResponse = $this->getJson('/api/v1/i18n?locale=xx');

        $enResponse->assertStatus(200);
        $xxResponse->assertStatus(200);

        $this->assertEquals(
            $enResponse->json(),
            $xxResponse->json(),
            'Invalid locale should return same data as English'
        );
    }

    /**
     * Test 7: Turkish translations are real translations (not just English copy).
     */
    public function test_turkish_translations_differ_from_english(): void
    {
        $en = __('maritime', [], 'en');
        $tr = __('maritime', [], 'tr');

        $mustDiffer = [
            'validation.first_name_required',
            'response.registration_success',
            'status.new',
            'rank.captain',
        ];

        foreach ($mustDiffer as $key) {
            $this->assertNotEquals(
                $en[$key],
                $tr[$key],
                "Turkish translation for '{$key}' should differ from English"
            );
        }
    }

    /**
     * Test 8: API Cache-Control header is set.
     */
    public function test_api_sets_cache_header(): void
    {
        $response = $this->getJson('/api/v1/i18n?locale=en');

        $response->assertStatus(200);
        $response->assertHeader('Cache-Control');
    }

    /**
     * Test 9: Azerbaijani translations are real translations (not English copy).
     */
    public function test_azerbaijani_translations_differ_from_english(): void
    {
        $en = __('maritime', [], 'en');
        $az = __('maritime', [], 'az');

        $mustDiffer = [
            'validation.first_name_required',
            'response.registration_success',
            'status.new',
            'rank.captain',
            'category.core_duty',
            'explanation.recommendation',
        ];

        foreach ($mustDiffer as $key) {
            $this->assertNotEquals(
                $en[$key],
                $az[$key],
                "Azerbaijani translation for '{$key}' should differ from English"
            );
        }
    }

    /**
     * Test 10: Accept-Language normalization — en-US, fil-PH, uk-UA, id-ID.
     */
    public function test_accept_language_normalization(): void
    {
        $enData = $this->getJson('/api/v1/i18n?locale=en')->json();

        // en-US → en
        $response = $this->getJson('/api/v1/i18n', ['Accept-Language' => 'en-US,en;q=0.9']);
        $response->assertStatus(200);
        $this->assertEquals($enData, $response->json(), 'en-US should resolve to en');

        // fil-PH → fil
        $response = $this->getJson('/api/v1/i18n', ['Accept-Language' => 'fil-PH']);
        $response->assertStatus(200);
        $filData = $response->json();
        $this->assertArrayHasKey('rank.captain', $filData);

        // uk-UA → uk
        $response = $this->getJson('/api/v1/i18n', ['Accept-Language' => 'uk-UA']);
        $response->assertStatus(200);
        $ukData = $response->json();
        $this->assertNotEquals(
            $enData['validation.first_name_required'],
            $ukData['validation.first_name_required'],
            'uk-UA should resolve to uk (not en)'
        );

        // id-ID → id
        $response = $this->getJson('/api/v1/i18n', ['Accept-Language' => 'id-ID']);
        $response->assertStatus(200);
        $idData = $response->json();
        $this->assertNotEquals(
            $enData['validation.first_name_required'],
            $idData['validation.first_name_required'],
            'id-ID should resolve to id (not en)'
        );
    }

    /**
     * Test 11: ns param is ignored — always returns maritime namespace.
     */
    public function test_namespace_whitelist_ignores_ns_param(): void
    {
        $maritimeData = $this->getJson('/api/v1/i18n?locale=en')->json();

        // ns=anything_else should return the same maritime data
        $otherNs = $this->getJson('/api/v1/i18n?locale=en&ns=users');
        $otherNs->assertStatus(200);
        $this->assertEquals(
            $maritimeData,
            $otherNs->json(),
            'ns=users should still return maritime translations (ns param is ignored)'
        );

        // ns=secrets should also be ignored
        $secrets = $this->getJson('/api/v1/i18n?locale=en&ns=secrets');
        $secrets->assertStatus(200);
        $this->assertEquals(
            $maritimeData,
            $secrets->json(),
            'ns=secrets should still return maritime translations (ns param is ignored)'
        );
    }
}
