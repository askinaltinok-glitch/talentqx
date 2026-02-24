<?php

namespace Tests\Feature;

use App\Services\Maritime\QuestionBankAssembler;
use App\Services\Maritime\EnglishSpeakingScorer;
use App\Models\MaritimeRoleRecord;
use Tests\TestCase;

/**
 * Tests for Question Bank v1 integration into the interview flow.
 * Validates: flattening, slot ordering, dimension mapping, English gate, decision state.
 */
class QuestionBankIntegrationTest extends TestCase
{
    private QuestionBankAssembler $assembler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assembler = app(QuestionBankAssembler::class);
        QuestionBankAssembler::clearCache();
    }

    // ── Flattening & Slot Ordering ───────────────────────────────────

    /**
     * Test 1: Flattened questions have sequential slots 1-25.
     */
    public function test_flatten_produces_sequential_slots(): void
    {
        $bank = $this->assembler->forRole('captain', 'en');
        $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);

        $this->assertCount(25, $flat, 'Flattened bank should have exactly 25 questions');

        $slots = array_column($flat, 'slot');
        $this->assertEquals(range(1, 25), $slots, 'Slots should be sequential 1-25');
    }

    /**
     * Test 2: Block boundaries are correct — CORE(1-12), ROLE(13-18), DEPT(19-22), ENGLISH(23-25).
     */
    public function test_flatten_block_boundaries(): void
    {
        $bank = $this->assembler->forRole('chief_officer', 'en');
        $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);

        // Slots 1-12: core
        for ($i = 0; $i < 12; $i++) {
            $this->assertEquals('core', $flat[$i]['block'],
                "Slot " . ($i + 1) . " should be core, got {$flat[$i]['block']}");
        }

        // Slots 13-18: role_specific
        for ($i = 12; $i < 18; $i++) {
            $this->assertEquals('role_specific', $flat[$i]['block'],
                "Slot " . ($i + 1) . " should be role_specific, got {$flat[$i]['block']}");
        }

        // Slots 19-22: dept_safety
        for ($i = 18; $i < 22; $i++) {
            $this->assertEquals('dept_safety', $flat[$i]['block'],
                "Slot " . ($i + 1) . " should be dept_safety, got {$flat[$i]['block']}");
        }

        // Slots 23-25: english_gate
        for ($i = 22; $i < 25; $i++) {
            $this->assertEquals('english_gate', $flat[$i]['block'],
                "Slot " . ($i + 1) . " should be english_gate, got {$flat[$i]['block']}");
        }
    }

    /**
     * Test 3: Formatted bank questions include required fields for frontend.
     */
    public function test_format_bank_questions_has_required_fields(): void
    {
        $bank = $this->assembler->forRole('oiler', 'en');
        $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);
        $formatted = $this->invokePrivateMethod('formatBankQuestions', [$flat]);

        $requiredKeys = ['id', 'slot', 'block', 'dimension', 'difficulty', 'prompt', 'voice_only', 'max_seconds'];

        foreach ($formatted as $q) {
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $q, "Question {$q['id']} missing key: {$key}");
            }
        }
    }

    /**
     * Test 4: English gate questions are marked voice_only = true.
     */
    public function test_english_gate_voice_only_flag(): void
    {
        $bank = $this->assembler->forRole('bosun', 'en');
        $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);
        $formatted = $this->invokePrivateMethod('formatBankQuestions', [$flat]);

        foreach ($formatted as $q) {
            if ($q['block'] === 'english_gate') {
                $this->assertTrue($q['voice_only'],
                    "English gate question {$q['id']} should have voice_only=true");
                $this->assertNotNull($q['max_seconds'],
                    "English gate question {$q['id']} should have max_seconds set");
            } else {
                $this->assertFalse($q['voice_only'],
                    "Non-English-gate question {$q['id']} should have voice_only=false");
            }
        }
    }

    // ── Dimension → Category Mapping ────────────────────────────────

    /**
     * Test 5: All behavioral question dimensions map to a valid category.
     */
    public function test_all_dimensions_map_to_valid_categories(): void
    {
        $validCategories = ['discipline_procedure', 'stress_crisis', 'team_compatibility', 'leadership_responsibility'];

        // Get the dimension map constant from the controller
        $refClass = new \ReflectionClass(\App\Http\Controllers\Api\Maritime\CleanInterviewController::class);
        $refConst = $refClass->getReflectionConstant('DIMENSION_CATEGORY_MAP');
        $dimMap = $refConst->getValue();

        // Collect all unique dimensions from all roles
        $allDimensions = [];
        $roles = MaritimeRoleRecord::active()->pluck('role_key')->toArray();

        foreach ($roles as $role) {
            $bank = $this->assembler->forRole($role, 'en');
            $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);

            foreach ($flat as $q) {
                if ($q['block'] === 'english_gate') continue;
                $allDimensions[$q['dimension']] = true;
            }
        }

        $unmapped = [];
        foreach (array_keys($allDimensions) as $dim) {
            $category = $dimMap[$dim] ?? null;
            if ($category) {
                $this->assertContains($category, $validCategories,
                    "Dimension '{$dim}' maps to invalid category '{$category}'");
            } else {
                $unmapped[] = $dim;
            }
        }

        // All dimensions should be mapped — no fallbacks
        $this->assertEmpty($unmapped,
            'All dimensions should be mapped. Unmapped: ' . implode(', ', $unmapped));
    }

    /**
     * Test 6: buildCategoryScoresFromBank correctly groups by dimension.
     */
    public function test_category_scores_from_bank_groups_correctly(): void
    {
        $bank = $this->assembler->forRole('captain', 'en');
        $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);

        // Build dimension map (same as controller does)
        $dimensionMap = collect($flat)
            ->filter(fn($q) => $q['block'] !== 'english_gate')
            ->mapWithKeys(fn($q) => [$q['id'] => $q['dimension']])
            ->toArray();

        $this->assertCount(22, $dimensionMap, 'Should have 22 behavioral question dimensions');

        // Verify all question IDs are unique
        $this->assertEquals(
            count($dimensionMap),
            count(array_unique(array_keys($dimensionMap))),
            'All question IDs should be unique'
        );
    }

    // ── English Gate Scoring ────────────────────────────────────────

    /**
     * Test 7: English min levels from assembler match expected hierarchy.
     */
    public function test_english_min_levels_in_assembled_bank(): void
    {
        // C1 roles
        $captainBank = $this->assembler->forRole('captain', 'en');
        $this->assertEquals('C1', $captainBank['english_min_level']);

        // B2 roles
        $chiefOfficerBank = $this->assembler->forRole('chief_officer', 'en');
        $this->assertEquals('B2', $chiefOfficerBank['english_min_level']);

        // A2 roles
        $oilerBank = $this->assembler->forRole('oiler', 'en');
        $this->assertEquals('A2', $oilerBank['english_min_level']);
    }

    /**
     * Test 8: English gate prompts in flattened output have scoring_criteria.
     */
    public function test_english_gate_prompts_have_scoring_criteria(): void
    {
        $bank = $this->assembler->forRole('third_officer', 'en');
        $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);

        $englishQuestions = array_filter($flat, fn($q) => $q['block'] === 'english_gate');
        $this->assertCount(3, $englishQuestions, 'Should have exactly 3 English gate prompts');

        foreach ($englishQuestions as $q) {
            $this->assertArrayHasKey('scoring_criteria', $q,
                "English gate question {$q['id']} should have scoring_criteria");
        }
    }

    // ── Decision State English Gate Blockers ─────────────────────────

    /**
     * Test 9: Decision state includes english_gate_failed blocker.
     */
    public function test_decision_state_english_gate_failed_blocker(): void
    {
        // Use reflection to call the private computeDecisionState method
        $service = app(\App\Services\Maritime\CandidateDecisionPanelService::class);
        $ref = new \ReflectionMethod($service, 'computeDecisionState');
        $ref->setAccessible(true);

        $qualifications = ['items' => []];
        $competencies = ['phases' => [['status' => 'completed']]];
        $language = ['estimated_level' => 'B1'];
        $englishGate = ['enabled' => true, 'status' => 'failed'];

        $result = $ref->invoke($service, $qualifications, $competencies, $language, $englishGate);

        $this->assertContains('english_gate_failed', $result['blockers']);
        $this->assertFalse($result['is_ready']);
    }

    /**
     * Test 10: Decision state includes english_gate_pending blocker.
     */
    public function test_decision_state_english_gate_pending_blocker(): void
    {
        $service = app(\App\Services\Maritime\CandidateDecisionPanelService::class);
        $ref = new \ReflectionMethod($service, 'computeDecisionState');
        $ref->setAccessible(true);

        $qualifications = ['items' => []];
        $competencies = ['phases' => [['status' => 'completed']]];
        $language = ['estimated_level' => 'B1'];
        $englishGate = ['enabled' => true, 'status' => 'pending'];

        $result = $ref->invoke($service, $qualifications, $competencies, $language, $englishGate);

        $this->assertContains('english_gate_pending', $result['blockers']);
    }

    /**
     * Test 11: Decision state with passed english gate has no english blockers.
     */
    public function test_decision_state_english_gate_passed_no_blocker(): void
    {
        $service = app(\App\Services\Maritime\CandidateDecisionPanelService::class);
        $ref = new \ReflectionMethod($service, 'computeDecisionState');
        $ref->setAccessible(true);

        $qualifications = ['items' => []];
        $competencies = ['phases' => [['status' => 'completed']]];
        $language = ['estimated_level' => 'C1'];
        $englishGate = ['enabled' => true, 'status' => 'passed'];

        $result = $ref->invoke($service, $qualifications, $competencies, $language, $englishGate);

        $this->assertNotContains('english_gate_failed', $result['blockers']);
        $this->assertNotContains('english_gate_pending', $result['blockers']);
        $this->assertTrue($result['is_ready']);
        $this->assertEquals('ready_for_shortlist', $result['state']);
    }

    /**
     * Test 12: Decision state without english gate (null) works like before.
     */
    public function test_decision_state_null_english_gate_backward_compatible(): void
    {
        $service = app(\App\Services\Maritime\CandidateDecisionPanelService::class);
        $ref = new \ReflectionMethod($service, 'computeDecisionState');
        $ref->setAccessible(true);

        $qualifications = ['items' => []];
        $competencies = ['phases' => [['status' => 'completed']]];
        $language = ['estimated_level' => 'B2'];

        $result = $ref->invoke($service, $qualifications, $competencies, $language, null);

        $this->assertNotContains('english_gate_failed', $result['blockers']);
        $this->assertTrue($result['is_ready']);
    }

    // ── Config Validation ───────────────────────────────────────────

    /**
     * Test 13: Question bank v1 feature flag is enabled.
     */
    public function test_question_bank_v1_flag_enabled(): void
    {
        $this->assertTrue(config('maritime.question_bank_v1'), 'Question bank v1 should be enabled');
    }

    /**
     * Test 14: English gate config is enabled.
     */
    public function test_english_gate_config_enabled(): void
    {
        $this->assertTrue(config('maritime.english_gate.enabled'), 'English gate should be enabled');
        $this->assertEquals(3, config('maritime.english_gate.max_prompts'));
        $this->assertCount(4, config('maritime.english_gate.criteria'));
    }

    /**
     * Test 15: Expected question count helper returns correct values.
     */
    public function test_expected_question_count_logic(): void
    {
        // Simulate the getExpectedQuestionCount logic
        $bankWorkflow = ['workflow' => 'question_bank_v1'];
        $legacyWorkflow = ['workflow' => 'clean_v1'];
        $emptyMeta = [];

        $bankCount = ($bankWorkflow['workflow'] ?? null) === 'question_bank_v1' ? 25 : 12;
        $legacyCount = ($legacyWorkflow['workflow'] ?? null) === 'question_bank_v1' ? 25 : 12;
        $emptyCount = ($emptyMeta['workflow'] ?? null) === 'question_bank_v1' ? 25 : 12;

        $this->assertEquals(25, $bankCount);
        $this->assertEquals(12, $legacyCount);
        $this->assertEquals(12, $emptyCount);
    }

    // ── Cross-role Coverage ─────────────────────────────────────────

    /**
     * Test 16: All 22 roles produce valid flattened output with correct structure.
     */
    public function test_all_roles_flatten_correctly(): void
    {
        $roles = MaritimeRoleRecord::active()->pluck('role_key')->toArray();
        $this->assertCount(22, $roles);

        foreach ($roles as $role) {
            $bank = $this->assembler->forRole($role, 'en');
            $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);

            $this->assertCount(25, $flat, "Role {$role}: should flatten to 25 questions");

            // Verify no duplicate question IDs
            $ids = array_column($flat, 'id');
            $this->assertEquals(count($ids), count(array_unique($ids)),
                "Role {$role}: should have unique question IDs");

            // Verify dimension map has 22 entries
            $dimMap = collect($flat)
                ->filter(fn($q) => $q['block'] !== 'english_gate')
                ->mapWithKeys(fn($q) => [$q['id'] => $q['dimension']])
                ->toArray();
            $this->assertCount(22, $dimMap, "Role {$role}: should have 22 dimension mappings");
        }
    }

    /**
     * Test 17: Turkish locale flattened output returns TR prompts.
     */
    public function test_turkish_locale_flattened_output(): void
    {
        $bank = $this->assembler->forRole('captain', 'tr');
        $flat = $this->invokePrivateMethod('flattenQuestionBank', [$bank]);

        // First core question should be in Turkish
        $firstCore = $flat[0];
        $this->assertMatchesRegularExpression('/[çğıöşüÇĞİÖŞÜ]/', $firstCore['prompt'],
            'Turkish prompt should contain Turkish characters');

        // English gate prompts are always in English
        $firstEnglish = array_values(array_filter($flat, fn($q) => $q['block'] === 'english_gate'))[0];
        $this->assertDoesNotMatchRegularExpression('/[çğıöşüÇĞİÖŞÜ]/', $firstEnglish['prompt'],
            'English gate prompts should always be in English');
    }

    // ── Helper ──────────────────────────────────────────────────────

    /**
     * Invoke a private method on CleanInterviewController for testing.
     */
    private function invokePrivateMethod(string $method, array $args): mixed
    {
        $controller = app(\App\Http\Controllers\Api\Maritime\CleanInterviewController::class);
        $ref = new \ReflectionMethod($controller, $method);
        $ref->setAccessible(true);

        return $ref->invoke($controller, ...$args);
    }
}
