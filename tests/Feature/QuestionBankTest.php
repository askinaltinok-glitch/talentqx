<?php

namespace Tests\Feature;

use App\Models\InterviewQuestionSet;
use App\Models\MaritimeRoleRecord;
use App\Services\Maritime\EnglishSpeakingScorer;
use App\Services\Maritime\QuestionBankAssembler;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    private QuestionBankAssembler $assembler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assembler = app(QuestionBankAssembler::class);
        QuestionBankAssembler::clearCache();
    }

    // ── Assembler Tests ──────────────────────────────────────────────

    /**
     * Test 1: All active DB roles produce exactly 25 questions.
     * (23 active: 22 canonical + wiper alias)
     */
    public function test_all_roles_assemble_25_questions(): void
    {
        $roles = MaritimeRoleRecord::active()->pluck('role_key')->toArray();
        $this->assertGreaterThanOrEqual(22, count($roles), 'Should have at least 22 active roles');

        foreach ($roles as $roleKey) {
            $bank = $this->assembler->forRole($roleKey, 'en');
            $this->assertEquals(25, $bank['question_count'],
                "Role {$roleKey} should assemble 25 questions, got {$bank['question_count']}");
        }
    }

    /**
     * Test 2: Block counts are correct: 12 CORE + 6 ROLE + 4 DEPT + 3 ENGLISH.
     */
    public function test_block_counts_are_correct(): void
    {
        $bank = $this->assembler->forRole('captain', 'en');

        $this->assertCount(12, $bank['blocks']['core'], 'CORE block should have 12 questions');
        $this->assertCount(6, $bank['blocks']['role_specific'], 'ROLE block should have 6 questions');
        $this->assertCount(4, $bank['blocks']['dept_safety'], 'DEPT block should have 4 questions');
        $this->assertCount(3, $bank['blocks']['english_gate']['prompts'], 'ENGLISH block should have 3 prompts');
    }

    /**
     * Test 3: Cross-domain protection — deck role gets deck DEPT, not engine.
     */
    public function test_cross_domain_protection_deck(): void
    {
        $bank = $this->assembler->forRole('captain', 'en');

        foreach ($bank['blocks']['dept_safety'] as $q) {
            $this->assertStringStartsWith('dept-deck-', $q['id'],
                "Captain (deck) should get deck dept questions, got: {$q['id']}");
        }
    }

    /**
     * Test 4: Cross-domain protection — engine role gets engine DEPT.
     */
    public function test_cross_domain_protection_engine(): void
    {
        $bank = $this->assembler->forRole('chief_engineer', 'en');

        foreach ($bank['blocks']['dept_safety'] as $q) {
            $this->assertStringStartsWith('dept-engine-', $q['id'],
                "Chief Engineer (engine) should get engine dept questions, got: {$q['id']}");
        }
    }

    /**
     * Test 5: Cross-domain protection — service/galley role gets galley DEPT.
     */
    public function test_cross_domain_protection_galley(): void
    {
        $bank = $this->assembler->forRole('cook', 'en');

        foreach ($bank['blocks']['dept_safety'] as $q) {
            $this->assertStringStartsWith('dept-galley-', $q['id'],
                "Cook (galley) should get galley dept questions, got: {$q['id']}");
        }
    }

    /**
     * Test 6: Turkish locale returns TR prompts, not EN.
     */
    public function test_turkish_locale_returns_tr_prompts(): void
    {
        $bank = $this->assembler->forRole('captain', 'tr');

        $this->assertEquals('tr', $bank['locale']);

        // Core questions should be in Turkish
        $firstCore = $bank['blocks']['core'][0];
        // Turkish content has Turkish characters
        $this->assertMatchesRegularExpression('/[çğıöşüÇĞİÖŞÜ]/', $firstCore['prompt'],
            'Turkish prompt should contain Turkish characters');
    }

    /**
     * Test 7: English gate min levels match role hierarchy.
     */
    public function test_english_min_levels(): void
    {
        // C1 roles
        foreach (['captain', 'chief_engineer'] as $role) {
            $bank = $this->assembler->forRole($role, 'en');
            $this->assertEquals('C1', $bank['english_min_level'],
                "{$role} should require C1 English");
        }

        // B2 roles
        foreach (['chief_officer', 'second_officer', 'second_engineer'] as $role) {
            $bank = $this->assembler->forRole($role, 'en');
            $this->assertEquals('B2', $bank['english_min_level'],
                "{$role} should require B2 English");
        }

        // A2 roles
        foreach (['able_seaman', 'oiler', 'cook'] as $role) {
            $bank = $this->assembler->forRole($role, 'en');
            $this->assertEquals('A2', $bank['english_min_level'],
                "{$role} should require A2 English");
        }
    }

    /**
     * Test 8: Alias mapping works — DB keys resolve to bank keys.
     */
    public function test_alias_mapping(): void
    {
        // captain → master_captain (role questions should be for master_captain)
        $bank = $this->assembler->forRole('captain', 'en');
        $roleIds = array_column($bank['blocks']['role_specific'], 'id');
        $this->assertStringStartsWith('role-mc-', $roleIds[0],
            'Captain should get master_captain role questions');

        // electrician → eto_electrician
        $bank2 = $this->assembler->forRole('electrician', 'en');
        $roleIds2 = array_column($bank2['blocks']['role_specific'], 'id');
        $this->assertStringStartsWith('role-eto-', $roleIds2[0],
            'Electrician should get eto_electrician role questions');

        // messman → steward (galley dept questions)
        $bank3 = $this->assembler->forRole('messman', 'en');
        foreach ($bank3['blocks']['dept_safety'] as $q) {
            $this->assertStringStartsWith('dept-galley-', $q['id'],
                'Messman should get galley dept questions');
        }
    }

    /**
     * Test 9: Unknown role throws InvalidArgumentException.
     */
    public function test_unknown_role_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown role_code: nonexistent_role');

        $this->assembler->forRole('nonexistent_role', 'en');
    }

    /**
     * Test 10: Questions sorted by difficulty within each block.
     */
    public function test_questions_sorted_by_difficulty(): void
    {
        $bank = $this->assembler->forRole('bosun', 'en');

        foreach (['core', 'role_specific', 'dept_safety'] as $block) {
            $questions = $bank['blocks'][$block];
            $prevDifficulty = 0;
            foreach ($questions as $q) {
                $this->assertGreaterThanOrEqual($prevDifficulty, $q['difficulty'],
                    "Block '{$block}' not sorted by difficulty at question {$q['id']}");
                $prevDifficulty = $q['difficulty'];
            }
        }
    }

    // ── English Speaking Scorer Tests ─────────────────────────────────

    /**
     * Test 11: Scorer produces correct CEFR level for known scores.
     */
    public function test_english_scorer_cefr_mapping(): void
    {
        $scorer = app(EnglishSpeakingScorer::class);

        // Perfect scores (5+5+5+5=20) → C1
        $result = $scorer->score('captain', [
            ['prompt_id' => 'eng-s1', 'fluency' => 5, 'clarity' => 5, 'accuracy' => 5, 'safety_vocabulary' => 5],
        ]);
        $this->assertEquals('C1', $result['estimated_level']);
        $this->assertEquals(20, $result['best_prompt_score']);
        $this->assertTrue($result['pass'], 'Captain with C1 score should pass C1 requirement');

        // Low scores (1+1+1+1=4) → A2
        $result2 = $scorer->score('captain', [
            ['prompt_id' => 'eng-s1', 'fluency' => 1, 'clarity' => 1, 'accuracy' => 1, 'safety_vocabulary' => 1],
        ]);
        $this->assertEquals('A2', $result2['estimated_level']);
        $this->assertFalse($result2['pass'], 'Captain with A2 score should fail C1 requirement');

        // Mid scores (3+3+3+3=12) → B1
        $result3 = $scorer->score('oiler', [
            ['prompt_id' => 'eng-s1', 'fluency' => 3, 'clarity' => 3, 'accuracy' => 3, 'safety_vocabulary' => 3],
        ]);
        $this->assertEquals('B1', $result3['estimated_level']);
        $this->assertTrue($result3['pass'], 'Oiler with B1 score should pass A2 requirement');
    }

    /**
     * Test 12: Scorer uses best prompt score, not average.
     */
    public function test_english_scorer_uses_best_prompt(): void
    {
        $scorer = app(EnglishSpeakingScorer::class);

        // Prompt 1: low (6 → A2), Prompt 2: high (18 → C1)
        $result = $scorer->score('captain', [
            ['prompt_id' => 'eng-s1', 'fluency' => 1, 'clarity' => 2, 'accuracy' => 2, 'safety_vocabulary' => 1],
            ['prompt_id' => 'eng-s2', 'fluency' => 5, 'clarity' => 4, 'accuracy' => 5, 'safety_vocabulary' => 4],
        ]);

        $this->assertEquals(18, $result['best_prompt_score']);
        $this->assertEquals('C1', $result['estimated_level']);
        $this->assertCount(2, $result['prompts']);
    }

    /**
     * Test 13: Scorer enforces role minimum levels.
     */
    public function test_english_scorer_enforces_role_minimum(): void
    {
        $scorer = app(EnglishSpeakingScorer::class);

        // B1 score (10) for captain (needs C1) → fail
        $result = $scorer->score('captain', [
            ['prompt_id' => 'eng-s1', 'fluency' => 3, 'clarity' => 2, 'accuracy' => 3, 'safety_vocabulary' => 2],
        ]);
        $this->assertEquals('B1', $result['estimated_level']);
        $this->assertEquals('C1', $result['min_level_required']);
        $this->assertFalse($result['pass']);

        // B1 score (10) for bosun (needs B1) → pass
        $result2 = $scorer->score('bosun', [
            ['prompt_id' => 'eng-s1', 'fluency' => 3, 'clarity' => 2, 'accuracy' => 3, 'safety_vocabulary' => 2],
        ]);
        $this->assertEquals('B1', $result2['estimated_level']);
        $this->assertEquals('B1', $result2['min_level_required']);
        $this->assertTrue($result2['pass']);
    }

    /**
     * Test 14: Confidence is higher for consistent scores.
     */
    public function test_english_scorer_confidence_consistency(): void
    {
        $scorer = app(EnglishSpeakingScorer::class);

        // Consistent scores → high confidence
        $consistent = $scorer->score('oiler', [
            ['prompt_id' => 'eng-s1', 'fluency' => 3, 'clarity' => 3, 'accuracy' => 3, 'safety_vocabulary' => 3],
            ['prompt_id' => 'eng-s2', 'fluency' => 3, 'clarity' => 3, 'accuracy' => 3, 'safety_vocabulary' => 3],
            ['prompt_id' => 'eng-s3', 'fluency' => 3, 'clarity' => 3, 'accuracy' => 3, 'safety_vocabulary' => 3],
        ]);

        // Wildly inconsistent scores → lower confidence
        $inconsistent = $scorer->score('oiler', [
            ['prompt_id' => 'eng-s1', 'fluency' => 1, 'clarity' => 1, 'accuracy' => 1, 'safety_vocabulary' => 1],
            ['prompt_id' => 'eng-s2', 'fluency' => 5, 'clarity' => 5, 'accuracy' => 5, 'safety_vocabulary' => 5],
            ['prompt_id' => 'eng-s3', 'fluency' => 1, 'clarity' => 1, 'accuracy' => 1, 'safety_vocabulary' => 1],
        ]);

        $this->assertGreaterThan($inconsistent['confidence'], $consistent['confidence'],
            'Consistent scores should produce higher confidence');
    }

    // ── Seeder Tests ─────────────────────────────────────────────────

    /**
     * Test 15: Seeder command runs without errors.
     */
    public function test_seeder_command_dry_run(): void
    {
        $this->artisan('maritime:seed-question-bank', ['--dry-run' => true])
            ->assertExitCode(0);
    }

    /**
     * Test 16: Seeder creates correct number of question sets.
     */
    public function test_seeder_creates_question_sets(): void
    {
        // Clear any existing sets with this code
        InterviewQuestionSet::where('code', 'role_question_bank_v1')->delete();

        $this->artisan('maritime:seed-question-bank', [
            '--locale' => ['en'],
            '--role'   => ['captain', 'oiler'],
        ])->assertExitCode(0);

        $count = InterviewQuestionSet::where('code', 'role_question_bank_v1')->count();
        $this->assertEquals(2, $count, 'Should create 2 question sets (captain + oiler, en only)');

        // Verify structure
        $set = InterviewQuestionSet::where('code', 'role_question_bank_v1')
            ->where('position_code', 'captain')
            ->where('locale', 'en')
            ->first();

        $this->assertNotNull($set);
        $this->assertEquals('maritime', $set->industry_code);
        $this->assertTrue($set->is_active);
        $this->assertIsArray($set->questions_json);
        $this->assertCount(22, $set->questions_json, 'Should have 22 questions (12 core + 6 role + 4 dept)');
        $this->assertArrayHasKey('english_gate', $set->rules_json);
        $this->assertArrayHasKey('question_count', $set->rules_json);
        $this->assertEquals(25, $set->rules_json['question_count']);
    }

    // ── Config Tests ─────────────────────────────────────────────────

    /**
     * Test 17: Config keys exist.
     */
    public function test_config_keys_exist(): void
    {
        $this->assertIsBool(config('maritime.question_bank_v1'));
        $this->assertIsArray(config('maritime.question_bank'));
        $this->assertIsArray(config('maritime.english_gate'));

        $this->assertArrayHasKey('source_path', config('maritime.question_bank'));
        $this->assertArrayHasKey('cache_ttl', config('maritime.question_bank'));
        $this->assertArrayHasKey('enabled', config('maritime.english_gate'));
        $this->assertArrayHasKey('criteria', config('maritime.english_gate'));
    }

    /**
     * Test 18: Validate all JSON source files are valid.
     */
    public function test_json_source_files_valid(): void
    {
        $basePath = storage_path('app/question_bank');

        $files = [
            'CORE_v1.json',
            'ROLE_SPECIFIC_v1.json',
            'DEPT_SAFETY_v1.json',
            'ENGLISH_GATE_v1.json',
        ];

        foreach ($files as $file) {
            $path = "{$basePath}/{$file}";
            $this->assertFileExists($path, "Missing question bank file: {$file}");

            $data = json_decode(file_get_contents($path), true);
            $this->assertNotNull($data, "Invalid JSON in {$file}: " . json_last_error_msg());
        }
    }

    /**
     * Test 19: CORE has exactly 12 questions.
     */
    public function test_core_json_has_12_questions(): void
    {
        $core = json_decode(file_get_contents(storage_path('app/question_bank/CORE_v1.json')), true);
        $this->assertCount(12, $core['questions']);
    }

    /**
     * Test 20: ROLE_SPECIFIC has exactly 22 roles × 6 questions each.
     */
    public function test_role_specific_has_22_roles(): void
    {
        $role = json_decode(file_get_contents(storage_path('app/question_bank/ROLE_SPECIFIC_v1.json')), true);
        $this->assertCount(22, $role['roles']);

        foreach ($role['roles'] as $roleKey => $roleData) {
            $this->assertCount(6, $roleData['questions'],
                "Role {$roleKey} should have 6 questions");
        }
    }

    // ── Role Normalization Contract Tests ─────────────────────────────

    /**
     * Test 21: MaritimeRole::normalize() contracts — aliases resolve to canonical keys.
     *
     * wiper → oiler (alias), motorman stays motorman (canonical).
     * The ROLE_ALIAS in QuestionBankAssembler handles bank-level mapping separately.
     */
    public function test_role_normalization_contracts(): void
    {
        // normalize() returns canonical key for both direct matches and aliases,
        // null only for completely unknown roles.
        $contracts = [
            'wiper'          => 'oiler',           // alias → canonical
            'master'         => 'captain',         // alias → canonical
            'engine_rating'  => 'motorman',        // alias → canonical
            'os'             => 'ordinary_seaman', // alias → canonical
            'captain'        => 'captain',         // direct match → self
            'chief_engineer' => 'chief_engineer',  // direct match → self
            'motorman'       => 'motorman',        // direct match → self
            'oiler'          => 'oiler',           // direct match → self
            'nonexistent_xyz' => null,             // unknown → null
        ];

        foreach ($contracts as $input => $expected) {
            $result = \App\Config\MaritimeRole::normalize($input);
            $this->assertSame($expected, $result,
                "normalize('{$input}') should return " . ($expected ?? 'null') . ", got " . ($result ?? 'null'));
        }
    }

    /**
     * Test 22: wiper resolves to oiler; motorman is separate but both produce 25 questions.
     *
     * wiper is an alias of oiler (MaritimeRole::normalize).
     * motorman is its own canonical role but shares question bank with wiper (ROLE_ALIAS).
     */
    public function test_wiper_oiler_alias_produces_identical_questions(): void
    {
        $wiperBank = $this->assembler->forRole('wiper', 'en');
        $oilerBank = $this->assembler->forRole('oiler', 'en');

        // wiper normalizes to oiler
        $this->assertEquals('oiler', $wiperBank['role_code']);
        $this->assertEquals('oiler', $oilerBank['role_code']);

        // Same question IDs
        $wiperIds = $this->extractAllQuestionIds($wiperBank);
        $oilerIds = $this->extractAllQuestionIds($oilerBank);
        $this->assertEquals($wiperIds, $oilerIds, 'wiper should produce same questions as oiler');

        // motorman is separate canonical role, maps to 'wiper' in bank via ROLE_ALIAS
        $motormanBank = $this->assembler->forRole('motorman', 'en');
        $this->assertEquals('motorman', $motormanBank['role_code']);
        $this->assertEquals(25, $motormanBank['question_count'],
            'motorman should also produce 25 questions');
    }

    /**
     * Test 23: Question set integrity snapshot — deterministic 25-question distribution.
     *
     * If question bank JSON files are modified, this test must be updated.
     * This prevents accidental drift in question counts per block.
     */
    public function test_question_set_deterministic_distribution(): void
    {
        $roles = MaritimeRoleRecord::active()->pluck('role_key')->toArray();

        foreach ($roles as $roleKey) {
            $bank = $this->assembler->forRole($roleKey, 'en');
            $allIds = $this->extractAllQuestionIds($bank);

            // Exactly 25 questions
            $this->assertCount(25, $allIds,
                "Role {$roleKey}: expected 25 total question IDs, got " . count($allIds));

            // All IDs unique (no duplicates across blocks)
            $uniqueIds = array_unique($allIds);
            $this->assertCount(count($allIds), $uniqueIds,
                "Role {$roleKey}: duplicate question IDs detected");

            // Block distribution: 12+6+4+3
            $this->assertCount(12, $bank['blocks']['core'],
                "Role {$roleKey}: core block should have 12");
            $this->assertCount(6, $bank['blocks']['role_specific'],
                "Role {$roleKey}: role_specific block should have 6");
            $this->assertCount(4, $bank['blocks']['dept_safety'],
                "Role {$roleKey}: dept_safety block should have 4");
            $this->assertCount(3, $bank['blocks']['english_gate']['prompts'],
                "Role {$roleKey}: english_gate block should have 3");
        }
    }

    /**
     * Test 24: wiper exists in DB but is not selectable (alias role).
     */
    public function test_wiper_in_db_not_selectable(): void
    {
        $wiper = MaritimeRoleRecord::where('role_key', 'wiper')->first();
        $this->assertNotNull($wiper, 'wiper should exist in maritime_roles');
        $this->assertFalse((bool) $wiper->is_selectable, 'wiper should not be selectable');
        $this->assertEquals('oiler', $wiper->meta['canonical_alias'] ?? null,
            'wiper.meta.canonical_alias should be oiler');
    }

    /**
     * Test 25: All 7 locales produce valid question sets for a representative role.
     */
    public function test_all_locales_produce_valid_sets(): void
    {
        $locales = ['en', 'tr', 'ru', 'az', 'fil', 'id', 'uk'];

        foreach ($locales as $locale) {
            $bank = $this->assembler->forRole('captain', $locale);
            $this->assertEquals(25, $bank['question_count'],
                "Captain in locale {$locale} should produce 25 questions");
            $this->assertEquals($locale, $bank['locale']);
        }
    }

    // ── Helper ─────────────────────────────────────────────────────────

    /**
     * Extract all question IDs from a bank result (all 4 blocks).
     */
    private function extractAllQuestionIds(array $bank): array
    {
        $ids = [];
        foreach ($bank['blocks']['core'] as $q) {
            $ids[] = $q['id'];
        }
        foreach ($bank['blocks']['role_specific'] as $q) {
            $ids[] = $q['id'];
        }
        foreach ($bank['blocks']['dept_safety'] as $q) {
            $ids[] = $q['id'];
        }
        foreach ($bank['blocks']['english_gate']['prompts'] as $q) {
            $ids[] = $q['id'];
        }
        return $ids;
    }
}
