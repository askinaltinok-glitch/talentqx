<?php

namespace Tests\Feature\I18n;

use App\Models\InterviewTemplate;
use Tests\TestCase;

class InterviewTemplateParityTest extends TestCase
{
    /**
     * Maritime position codes that must exist in every language.
     * Excludes retail/non-maritime codes (customer_support, sales_associate, warehouse_picker).
     */
    private const MARITIME_CODES = [
        '__generic__',
        'cadet___generic__',
        'cadet_deck_cadet',
        'cadet_engine_cadet',
        'deck___generic__',
        'deck_able_seaman',
        'deck_bosun',
        'deck_captain',
        'deck_chief_officer',
        'deck_ordinary_seaman',
        'deck_second_officer',
        'deck_third_officer',
        'engine___generic__',
        'engine_chief_engineer',
        'engine_electrician',
        'engine_motorman',
        'engine_oiler',
        'engine_second_engineer',
        'engine_third_engineer',
        'galley___generic__',
        'galley_cook',
        'galley_messman',
        'galley_steward',
    ];

    private const LOCALES = ['en', 'tr', 'ru', 'az', 'fil', 'id', 'uk'];

    /**
     * Test 1: Every EN maritime position_code exists in all target locales.
     */
    public function test_position_code_parity_across_all_locales(): void
    {
        foreach (self::LOCALES as $locale) {
            $existing = InterviewTemplate::where('is_active', true)
                ->where('language', $locale)
                ->where('version', 'v1')
                ->pluck('position_code')
                ->toArray();

            foreach (self::MARITIME_CODES as $code) {
                $this->assertContains(
                    $code,
                    $existing,
                    "Position code '{$code}' missing in locale '{$locale}'"
                );
            }
        }
    }

    /**
     * Test 2: Role-specific templates have the same number of sections as EN.
     */
    public function test_section_count_matches_english(): void
    {
        $roleCodes = array_filter(self::MARITIME_CODES, fn($c) => $c !== '__generic__');

        foreach ($roleCodes as $code) {
            $enTemplate = $this->getTemplate('en', $code);
            $this->assertNotNull($enTemplate, "EN template for {$code} should exist");

            $enJson = json_decode($enTemplate->template_json, true);
            $enSections = $enJson['sections'] ?? [];

            foreach (self::LOCALES as $locale) {
                if ($locale === 'en') continue;

                $localeTemplate = $this->getTemplate($locale, $code);
                if (!$localeTemplate) continue; // parity test catches this

                $localeJson = json_decode($localeTemplate->template_json, true);
                $localeSections = $localeJson['sections'] ?? [];

                $this->assertCount(
                    count($enSections),
                    $localeSections,
                    "{$locale}/{$code}: section count mismatch (EN has " . count($enSections) . ")"
                );
            }
        }
    }

    /**
     * Test 3: Question counts per section match EN for all role-specific templates.
     */
    public function test_question_count_per_section_matches_english(): void
    {
        $roleCodes = array_filter(self::MARITIME_CODES, fn($c) => $c !== '__generic__');

        foreach ($roleCodes as $code) {
            $enJson = $this->getTemplateJson('en', $code);
            if (!$enJson) continue;

            foreach (self::LOCALES as $locale) {
                if ($locale === 'en') continue;

                $localeJson = $this->getTemplateJson($locale, $code);
                if (!$localeJson) continue;

                foreach ($enJson['sections'] ?? [] as $i => $enSection) {
                    $enCount = count($enSection['questions'] ?? []);
                    $localeCount = count($localeJson['sections'][$i]['questions'] ?? []);

                    $this->assertEquals(
                        $enCount,
                        $localeCount,
                        "{$locale}/{$code} section '{$enSection['key']}': EN has {$enCount} questions, {$locale} has {$localeCount}"
                    );
                }
            }
        }
    }

    /**
     * Test 4: Section keys (screening, technical, safety, behaviour) match EN order.
     */
    public function test_section_keys_match_english_order(): void
    {
        $roleCodes = array_filter(self::MARITIME_CODES, fn($c) => $c !== '__generic__');

        foreach ($roleCodes as $code) {
            $enJson = $this->getTemplateJson('en', $code);
            if (!$enJson) continue;

            $enKeys = array_map(fn($s) => $s['key'], $enJson['sections'] ?? []);

            foreach (self::LOCALES as $locale) {
                if ($locale === 'en') continue;

                $localeJson = $this->getTemplateJson($locale, $code);
                if (!$localeJson) continue;

                $localeKeys = array_map(fn($s) => $s['key'], $localeJson['sections'] ?? []);

                $this->assertEquals(
                    $enKeys,
                    $localeKeys,
                    "{$locale}/{$code}: section key order mismatch"
                );
            }
        }
    }

    /**
     * Test 5: Scoring weights are identical across all locales.
     */
    public function test_scoring_weights_match_english(): void
    {
        $roleCodes = array_filter(self::MARITIME_CODES, fn($c) => $c !== '__generic__');

        foreach ($roleCodes as $code) {
            $enJson = $this->getTemplateJson('en', $code);
            if (!$enJson) continue;

            $enWeights = $enJson['scoring']['weights'] ?? [];

            foreach (self::LOCALES as $locale) {
                if ($locale === 'en') continue;

                $localeJson = $this->getTemplateJson($locale, $code);
                if (!$localeJson) continue;

                $localeWeights = $localeJson['scoring']['weights'] ?? [];

                $this->assertEquals(
                    $enWeights,
                    $localeWeights,
                    "{$locale}/{$code}: scoring weights differ from EN"
                );
            }
        }
    }

    /**
     * Test 6: Question IDs contain the correct language marker.
     */
    public function test_question_ids_contain_language_marker(): void
    {
        $roleCodes = array_filter(self::MARITIME_CODES, fn($c) => $c !== '__generic__');

        foreach (self::LOCALES as $locale) {
            $templates = InterviewTemplate::where('is_active', true)
                ->where('language', $locale)
                ->where('version', 'v1')
                ->whereIn('position_code', $roleCodes)
                ->get();

            foreach ($templates as $template) {
                $json = json_decode($template->template_json, true);
                foreach ($json['sections'] ?? [] as $section) {
                    foreach ($section['questions'] ?? [] as $q) {
                        $id = $q['id'];
                        $this->assertStringContainsString(
                            "_{$locale}_",
                            $id,
                            "{$locale}/{$template->position_code}: question ID '{$id}' missing language marker '_{$locale}_'"
                        );
                    }
                }
            }
        }
    }

    /**
     * Test 7: Generic templates have exactly 8 questions with correct competencies.
     */
    public function test_generic_template_structure(): void
    {
        $expectedCompetencies = [
            'communication',
            'accountability',
            'teamwork',
            'stress_resilience',
            'adaptability',
            'learning_agility',
            'integrity',
            'role_competence',
        ];

        foreach (self::LOCALES as $locale) {
            $template = $this->getTemplate($locale, '__generic__');
            $this->assertNotNull($template, "{$locale}/__generic__ should exist");

            $json = json_decode($template->template_json, true);
            $questions = $json['generic_template']['questions'] ?? [];

            $this->assertCount(8, $questions, "{$locale}/__generic__: should have 8 questions");

            $competencies = array_map(fn($q) => $q['competency'], $questions);
            $this->assertEquals(
                $expectedCompetencies,
                $competencies,
                "{$locale}/__generic__: competency order mismatch"
            );

            // Each question must have scoring_rubric, positive_signals, red_flag_hooks
            foreach ($questions as $q) {
                $this->assertArrayHasKey('scoring_rubric', $q, "{$locale}/__generic__ slot {$q['slot']}: missing scoring_rubric");
                $this->assertCount(5, $q['scoring_rubric'], "{$locale}/__generic__ slot {$q['slot']}: scoring_rubric should have 5 levels");
                $this->assertArrayHasKey('positive_signals', $q, "{$locale}/__generic__ slot {$q['slot']}: missing positive_signals");
                $this->assertNotEmpty($q['positive_signals'], "{$locale}/__generic__ slot {$q['slot']}: positive_signals should not be empty");
                $this->assertArrayHasKey('red_flag_hooks', $q, "{$locale}/__generic__ slot {$q['slot']}: missing red_flag_hooks");
                $this->assertNotEmpty($q['red_flag_hooks'], "{$locale}/__generic__ slot {$q['slot']}: red_flag_hooks should not be empty");
            }
        }
    }

    /**
     * Test 8: Scale-type questions have the same min/max across locales.
     */
    public function test_scale_questions_have_correct_range(): void
    {
        $roleCodes = array_filter(self::MARITIME_CODES, fn($c) => $c !== '__generic__');

        foreach (self::LOCALES as $locale) {
            $templates = InterviewTemplate::where('is_active', true)
                ->where('language', $locale)
                ->where('version', 'v1')
                ->whereIn('position_code', $roleCodes)
                ->get();

            foreach ($templates as $template) {
                $json = json_decode($template->template_json, true);
                foreach ($json['sections'] ?? [] as $section) {
                    foreach ($section['questions'] ?? [] as $q) {
                        if (($q['type'] ?? '') === 'scale') {
                            $this->assertArrayHasKey(
                                'scale',
                                $q,
                                "{$locale}/{$template->position_code}: scale question '{$q['id']}' missing 'scale' field"
                            );
                            $this->assertEquals(1, $q['scale']['min'] ?? null, "{$locale}/{$template->position_code}: scale min should be 1");
                            $this->assertEquals(5, $q['scale']['max'] ?? null, "{$locale}/{$template->position_code}: scale max should be 5");
                        }
                    }
                }
            }
        }
    }

    /**
     * Test 9: Template language field inside JSON matches the DB language column.
     */
    public function test_json_language_matches_db_language(): void
    {
        foreach (self::LOCALES as $locale) {
            $templates = InterviewTemplate::where('is_active', true)
                ->where('language', $locale)
                ->where('version', 'v1')
                ->whereIn('position_code', self::MARITIME_CODES)
                ->get();

            foreach ($templates as $template) {
                $json = json_decode($template->template_json, true);
                $jsonLang = $json['language'] ?? null;

                $this->assertEquals(
                    $locale,
                    $jsonLang,
                    "{$locale}/{$template->position_code}: JSON language '{$jsonLang}' doesn't match DB language '{$locale}'"
                );
            }
        }
    }

    /**
     * Test 10: Translated templates have different question text from EN (not just copies).
     */
    public function test_translated_templates_differ_from_english(): void
    {
        // Check deck_captain as representative sample
        $enJson = $this->getTemplateJson('en', 'deck_captain');
        $this->assertNotNull($enJson);
        $enFirstQuestion = $enJson['sections'][0]['questions'][0]['text'] ?? '';

        foreach (['tr', 'ru', 'az'] as $locale) {
            $localeJson = $this->getTemplateJson($locale, 'deck_captain');
            if (!$localeJson) continue;

            $localeFirstQuestion = $localeJson['sections'][0]['questions'][0]['text'] ?? '';

            $this->assertNotEquals(
                $enFirstQuestion,
                $localeFirstQuestion,
                "{$locale}/deck_captain: first question should differ from EN (not a copy)"
            );
        }

        // Check __generic__ as well
        $enGeneric = $this->getTemplate('en', '__generic__');
        $enGenericJson = json_decode($enGeneric->template_json, true);
        $enGenericQ1 = $enGenericJson['generic_template']['questions'][0]['question'] ?? '';

        foreach (['tr', 'ru', 'az'] as $locale) {
            $localeGeneric = $this->getTemplate($locale, '__generic__');
            if (!$localeGeneric) continue;

            $localeGenericJson = json_decode($localeGeneric->template_json, true);
            $localeGenericQ1 = $localeGenericJson['generic_template']['questions'][0]['question'] ?? '';

            $this->assertNotEquals(
                $enGenericQ1,
                $localeGenericQ1,
                "{$locale}/__generic__: first generic question should differ from EN"
            );
        }
    }

    /**
     * Test 11: Department field is correct for all role-specific templates.
     */
    public function test_department_field_matches_position_code(): void
    {
        $deptMap = [
            'deck' => 'deck',
            'engine' => 'engine',
            'galley' => 'galley',
            'cadet' => 'cadet',
        ];

        $roleCodes = array_filter(self::MARITIME_CODES, fn($c) => $c !== '__generic__');

        foreach (self::LOCALES as $locale) {
            $templates = InterviewTemplate::where('is_active', true)
                ->where('language', $locale)
                ->where('version', 'v1')
                ->whereIn('position_code', $roleCodes)
                ->get();

            foreach ($templates as $template) {
                $json = json_decode($template->template_json, true);
                $jsonDept = $json['department'] ?? null;
                $expectedDept = explode('_', $template->position_code)[0];

                $this->assertEquals(
                    $expectedDept,
                    $jsonDept,
                    "{$locale}/{$template->position_code}: department should be '{$expectedDept}' but got '{$jsonDept}'"
                );
            }
        }
    }

    // --- Helpers ---

    private function getTemplate(string $locale, string $positionCode): ?InterviewTemplate
    {
        return InterviewTemplate::where('is_active', true)
            ->where('language', $locale)
            ->where('version', 'v1')
            ->where('position_code', $positionCode)
            ->first();
    }

    private function getTemplateJson(string $locale, string $positionCode): ?array
    {
        $template = $this->getTemplate($locale, $positionCode);
        if (!$template) return null;
        return json_decode($template->template_json, true);
    }
}
