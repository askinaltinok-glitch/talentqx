<?php

namespace App\Services\Maritime;

use App\Config\MaritimeRole;
use App\Models\MaritimeRoleRecord;
use Illuminate\Support\Facades\Cache;

/**
 * Assembles a role-specific question set from 4 JSON source files:
 *
 *   CORE_v1.json          → 12 behavioral questions (same for all roles)
 *   ROLE_SPECIFIC_v1.json → 6 questions per role (22 roles)
 *   DEPT_SAFETY_v1.json   → 4 questions per department (deck/engine/galley)
 *   ENGLISH_GATE_v1.json  → 3 speaking prompts + scoring + min CEFR level table
 *
 * Output: 25 questions per role = 12 CORE + 6 ROLE + 4 DEPT + 3 ENGLISH
 *
 * Non-negotiables enforced:
 *   - Cross-domain forbidden (deck role never gets engine DEPT questions)
 *   - Role code must exist in MaritimeRoleRecord
 *   - English gate uses role-based min CEFR level (never auto-changed)
 *   - Difficulty ordering preserved within each block
 */
class QuestionBankAssembler
{
    private const CACHE_KEY = 'question_bank:sources';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * DB role_key → question bank role_key (only keys that differ).
     * The role registry DB uses short canonical keys; the question bank
     * spec uses the full maritime role names from the original spec doc.
     */
    private const ROLE_ALIAS = [
        'captain'     => 'master_captain',
        'electrician' => 'eto_electrician',
        'motorman'    => 'wiper',       // both junior engine ratings
        'messman'     => 'steward',     // both galley/service department
    ];

    /**
     * DB department → question bank department.
     * The DB stores 'service'; the question bank uses 'galley'.
     */
    private const DEPT_ALIAS = [
        'service' => 'galley',
    ];

    /**
     * Assemble the full 25-question set for a given role and locale.
     *
     * @return array{
     *   role_code: string,
     *   department: string,
     *   locale: string,
     *   english_min_level: string,
     *   blocks: array{core: array, role_specific: array, dept_safety: array, english_gate: array},
     *   question_count: int,
     *   version: string
     * }
     *
     * @throws \InvalidArgumentException If role_code unknown or dept not found
     */
    public function forRole(string $roleCode, string $locale = 'en'): array
    {
        // Normalize alias BEFORE DB lookup (e.g. "wiper" → "oiler")
        $normalized = MaritimeRole::normalize($roleCode);
        if ($normalized !== null) {
            $roleCode = $normalized;
        }

        $role = MaritimeRoleRecord::findByKey($roleCode);

        if (!$role) {
            throw new \InvalidArgumentException("Unknown role_code: {$roleCode}");
        }

        // Resolve aliases: DB key → question bank key
        $bankRoleKey = self::ROLE_ALIAS[$roleCode] ?? $roleCode;
        $department = $role->department;
        $bankDept = self::DEPT_ALIAS[$department] ?? $department;

        $sources = $this->loadSources();

        // ── Block 1: CORE (12 questions) ──
        $coreQuestions = $this->buildCoreBlock($sources['core'], $locale);

        // ── Block 2: ROLE-SPECIFIC (6 questions) ──
        $roleQuestions = $this->buildRoleBlock($sources['role'], $bankRoleKey, $locale);

        // ── Block 3: DEPT SAFETY (4 questions) ──
        $deptQuestions = $this->buildDeptBlock($sources['dept'], $bankDept, $locale);

        // ── Block 4: ENGLISH GATE (3 prompts) ──
        $englishBlock = $this->buildEnglishBlock($sources['english'], $bankRoleKey);

        $totalCount = count($coreQuestions) + count($roleQuestions) + count($deptQuestions) + count($englishBlock['prompts']);

        return [
            'role_code'         => $roleCode,
            'department'        => $department,
            'locale'            => $locale,
            'english_min_level' => $englishBlock['min_level'],
            'blocks'            => [
                'core'          => $coreQuestions,
                'role_specific' => $roleQuestions,
                'dept_safety'   => $deptQuestions,
                'english_gate'  => $englishBlock,
            ],
            'question_count'    => $totalCount,
            'version'           => '1.0',
        ];
    }

    /**
     * List all available role codes from the role-specific bank.
     *
     * @return array<string, string> role_code => department
     */
    public function availableRoles(): array
    {
        $sources = $this->loadSources();
        $roles = [];

        foreach ($sources['role']['roles'] as $roleCode => $roleData) {
            $roles[$roleCode] = $roleData['department'];
        }

        return $roles;
    }

    /**
     * Validate that all roles in the JSON bank exist in the MaritimeRoleRecord table.
     *
     * @return array{valid: string[], missing: string[]}
     */
    public function validateAgainstRegistry(): array
    {
        $bankRoles = array_keys($this->loadSources()['role']['roles']);
        $dbRoles = MaritimeRoleRecord::active()->pluck('role_key')->toArray();

        // Resolve DB keys through alias to match bank keys
        $resolvedDbRoles = array_map(
            fn($k) => self::ROLE_ALIAS[$k] ?? $k,
            $dbRoles
        );

        $valid = array_intersect($bankRoles, $resolvedDbRoles);
        $missing = array_diff($bankRoles, $resolvedDbRoles);

        return [
            'valid'   => array_values($valid),
            'missing' => array_values($missing),
        ];
    }

    /**
     * Invalidate cached source files.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ── Private builders ─────────────────────────────────────────────

    /**
     * Build CORE block: 12 behavioral questions, locale-resolved.
     */
    private function buildCoreBlock(array $coreSource, string $locale): array
    {
        $questions = [];

        foreach ($coreSource['questions'] as $q) {
            $prompt = $q['prompts'][$locale] ?? $q['prompts']['en'];

            $questions[] = [
                'id'         => $q['id'],
                'block'      => 'core',
                'dimension'  => $q['dimension'],
                'difficulty' => $q['difficulty'],
                'prompt'     => $prompt,
                'rubric'     => $q['rubric'],
                'red_flags'  => $q['red_flags'] ?? [],
            ];
        }

        // Sort by difficulty ascending (preserve easy → hard ordering)
        usort($questions, fn($a, $b) => $a['difficulty'] <=> $b['difficulty']);

        return $questions;
    }

    /**
     * Build ROLE-SPECIFIC block: 6 questions for the given role, locale-resolved.
     *
     * @throws \InvalidArgumentException If role not found in bank
     */
    private function buildRoleBlock(array $roleSource, string $roleCode, string $locale): array
    {
        if (!isset($roleSource['roles'][$roleCode])) {
            throw new \InvalidArgumentException("Role '{$roleCode}' not found in ROLE_SPECIFIC bank.");
        }

        $roleData = $roleSource['roles'][$roleCode];
        $questions = [];

        foreach ($roleData['questions'] as $q) {
            $prompt = $q['prompts'][$locale] ?? $q['prompts']['en'];

            $questions[] = [
                'id'         => $q['id'],
                'block'      => 'role_specific',
                'dimension'  => $q['dimension'],
                'difficulty' => $q['difficulty'],
                'prompt'     => $prompt,
                'rubric'     => $q['rubric'],
            ];
        }

        usort($questions, fn($a, $b) => $a['difficulty'] <=> $b['difficulty']);

        return $questions;
    }

    /**
     * Build DEPT SAFETY block: 4 questions for the role's department, locale-resolved.
     * Cross-domain protection: only the role's own department is used.
     *
     * @throws \InvalidArgumentException If department not found in bank
     */
    private function buildDeptBlock(array $deptSource, string $department, string $locale): array
    {
        if (!isset($deptSource['departments'][$department])) {
            throw new \InvalidArgumentException("Department '{$department}' not found in DEPT_SAFETY bank.");
        }

        $deptData = $deptSource['departments'][$department];
        $questions = [];

        foreach ($deptData['questions'] as $q) {
            $prompt = $q['prompts'][$locale] ?? $q['prompts']['en'];

            $questions[] = [
                'id'         => $q['id'],
                'block'      => 'dept_safety',
                'dimension'  => $q['dimension'],
                'difficulty' => $q['difficulty'],
                'prompt'     => $prompt,
                'rubric'     => $q['rubric'],
            ];
        }

        usort($questions, fn($a, $b) => $a['difficulty'] <=> $b['difficulty']);

        return $questions;
    }

    /**
     * Build ENGLISH GATE block: 3 prompts + scoring config + min CEFR level.
     * English prompts are always in English (single-language gate).
     */
    private function buildEnglishBlock(array $englishSource, string $roleCode): array
    {
        $minLevel = $englishSource['min_level_by_role'][$roleCode] ?? 'A2';

        $prompts = [];
        foreach ($englishSource['prompts'] as $p) {
            $prompts[] = [
                'id'               => $p['id'],
                'block'            => 'english_gate',
                'dimension'        => $p['dimension'],
                'difficulty'       => $p['difficulty'],
                'prompt'           => $p['prompt'],
                'scoring_criteria' => $p['scoring_criteria'],
                'max_seconds'      => $p['max_seconds'],
            ];
        }

        return [
            'prompts'      => $prompts,
            'min_level'    => $minLevel,
            'scoring'      => $englishSource['scoring'],
        ];
    }

    // ── Source loading with cache ────────────────────────────────────

    /**
     * Load all 4 JSON source files, cached for performance.
     *
     * @return array{core: array, role: array, dept: array, english: array}
     */
    private function loadSources(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $basePath = storage_path('app/question_bank');

            $core = $this->loadJson("{$basePath}/CORE_v1.json", 'CORE');
            $role = $this->loadJson("{$basePath}/ROLE_SPECIFIC_v1.json", 'ROLE_SPECIFIC');
            $dept = $this->loadJson("{$basePath}/DEPT_SAFETY_v1.json", 'DEPT_SAFETY');
            $english = $this->loadJson("{$basePath}/ENGLISH_GATE_v1.json", 'ENGLISH_GATE');

            return compact('core', 'role', 'dept', 'english');
        });
    }

    /**
     * Load and validate a JSON file.
     *
     * @throws \RuntimeException If file missing or invalid JSON
     */
    private function loadJson(string $path, string $label): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("{$label} question bank file not found: {$path}");
        }

        $data = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("{$label} JSON decode error: " . json_last_error_msg());
        }

        return $data;
    }
}
