<?php

namespace App\Config;

/**
 * Single source of truth for maritime roles and departments.
 *
 * Every file that needs role/department data MUST reference this class.
 * Do NOT duplicate these lists elsewhere.
 */
final class MaritimeRole
{
    // ── Departments ──────────────────────────────────────────────
    public const DEPT_DECK   = 'deck';
    public const DEPT_ENGINE = 'engine';
    public const DEPT_GALLEY = 'galley';
    public const DEPT_CADET  = 'cadet';

    public const DEPARTMENTS = [
        self::DEPT_DECK,
        self::DEPT_ENGINE,
        self::DEPT_GALLEY,
        self::DEPT_CADET,
    ];

    // ── Role → Department map (canonical) ────────────────────────
    public const ROLE_DEPARTMENT_MAP = [
        // Deck
        'captain'         => self::DEPT_DECK,
        'chief_officer'   => self::DEPT_DECK,
        'second_officer'  => self::DEPT_DECK,
        'third_officer'   => self::DEPT_DECK,
        'bosun'           => self::DEPT_DECK,
        'able_seaman'     => self::DEPT_DECK,
        'ordinary_seaman' => self::DEPT_DECK,

        // Engine
        'chief_engineer'  => self::DEPT_ENGINE,
        'second_engineer' => self::DEPT_ENGINE,
        'third_engineer'  => self::DEPT_ENGINE,
        'motorman'        => self::DEPT_ENGINE,
        'oiler'           => self::DEPT_ENGINE,
        'electrician'     => self::DEPT_ENGINE,
        'fitter'          => self::DEPT_ENGINE,

        // Galley
        'cook'            => self::DEPT_GALLEY,
        'steward'         => self::DEPT_GALLEY,
        'messman'         => self::DEPT_GALLEY,

        // Cadet
        'deck_cadet'      => self::DEPT_CADET,
        'engine_cadet'    => self::DEPT_CADET,

        // Specialized
        'pumpman'         => self::DEPT_DECK,
        'dp_operator'     => self::DEPT_DECK,
        'crane_operator'  => self::DEPT_DECK,
    ];

    // ── Derived lists (auto-generated from map) ──────────────────

    /** All valid role codes */
    public const ROLES = [
        // Deck
        'captain',
        'chief_officer',
        'second_officer',
        'third_officer',
        'bosun',
        'able_seaman',
        'ordinary_seaman',
        // Engine
        'chief_engineer',
        'second_engineer',
        'third_engineer',
        'motorman',
        'oiler',
        'electrician',
        'fitter',
        // Galley
        'cook',
        'steward',
        'messman',
        // Cadet
        'deck_cadet',
        'engine_cadet',
        // Specialized
        'pumpman',
        'dp_operator',
        'crane_operator',
    ];

    // ── Role aliases (for tolerant import / fuzzy matching) ──────
    public const ROLE_ALIASES = [
        'master'        => 'captain',
        'ab_seaman'     => 'able_seaman',
        'ab'            => 'able_seaman',
        'os'            => 'ordinary_seaman',
        '2nd_officer'   => 'second_officer',
        '3rd_officer'   => 'third_officer',
        '2nd_engineer'  => 'second_engineer',
        '3rd_engineer'  => 'third_engineer',
        'chief_cook'    => 'cook',
        'head_cook'     => 'cook',
        'cadet_deck'    => 'deck_cadet',
        'cadet_engine'  => 'engine_cadet',
        'trainee'       => 'deck_cadet',
        'wiper'         => 'oiler',
        'eto'           => 'electrician',
        'fourth_engineer' => 'third_engineer',
        'radio_officer' => 'electrician',
        // Frontend / legacy codes
        'deck_officer'     => 'chief_officer',
        'marine_engineer'  => 'chief_engineer',
        'engineer_officer' => 'chief_engineer',
        'ship_cook'        => 'cook',
        'deck_rating'      => 'able_seaman',
        'engine_rating'    => 'motorman',
        'catering'         => 'cook',
        // DNA registry aliases
        'pump_man'         => 'pumpman',
        'dynamic_positioning' => 'dp_operator',
    ];

    // ── Roles grouped by department ──────────────────────────────
    public const DECK_ROLES       = ['captain', 'chief_officer', 'second_officer', 'third_officer', 'bosun', 'able_seaman', 'ordinary_seaman'];
    public const ENGINE_ROLES     = ['chief_engineer', 'second_engineer', 'third_engineer', 'motorman', 'oiler', 'electrician', 'fitter'];
    public const GALLEY_ROLES     = ['cook', 'steward', 'messman'];
    public const CADET_ROLES      = ['deck_cadet', 'engine_cadet'];
    public const SPECIALIZED_ROLES = ['pumpman', 'dp_operator', 'crane_operator'];

    // ── Human labels ─────────────────────────────────────────────
    public const ROLE_LABELS = [
        'captain'         => 'Captain / Master',
        'chief_officer'   => 'Chief Officer',
        'second_officer'  => 'Second Officer',
        'third_officer'   => 'Third Officer',
        'bosun'           => 'Bosun',
        'able_seaman'     => 'Able Seaman (AB)',
        'ordinary_seaman' => 'Ordinary Seaman (OS)',
        'chief_engineer'  => 'Chief Engineer',
        'second_engineer' => 'Second Engineer',
        'third_engineer'  => 'Third Engineer',
        'motorman'        => 'Motorman',
        'oiler'           => 'Oiler',
        'electrician'     => 'Electrician / ETO',
        'fitter'          => 'Fitter',
        'cook'            => 'Cook',
        'steward'         => 'Steward',
        'messman'         => 'Messman',
        'deck_cadet'      => 'Deck Cadet',
        'engine_cadet'    => 'Engine Cadet',
        'pumpman'         => 'Pumpman',
        'dp_operator'     => 'DP Operator',
        'crane_operator'  => 'Crane Operator',
    ];

    public const DEPARTMENT_LABELS = [
        self::DEPT_DECK   => 'Deck',
        self::DEPT_ENGINE => 'Engine',
        self::DEPT_GALLEY => 'Galley',
        self::DEPT_CADET  => 'Cadet',
    ];

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Resolve department from role code. Returns null if unknown.
     */
    public static function departmentFor(string $roleCode): ?string
    {
        return self::ROLE_DEPARTMENT_MAP[strtolower($roleCode)] ?? null;
    }

    /**
     * Normalize a role code using aliases. Returns canonical code or null.
     */
    public static function normalize(string $raw): ?string
    {
        $key = strtolower(trim($raw));

        // Direct match
        if (isset(self::ROLE_DEPARTMENT_MAP[$key])) {
            return $key;
        }

        // Alias match
        return self::ROLE_ALIASES[$key] ?? null;
    }

    /**
     * Check if a role code (or alias) is valid.
     */
    public static function isValid(string $roleCode): bool
    {
        return self::normalize($roleCode) !== null;
    }

    /**
     * Get roles for a given department.
     */
    public static function rolesForDepartment(string $department): array
    {
        return array_keys(array_filter(
            self::ROLE_DEPARTMENT_MAP,
            fn(string $dept) => $dept === strtolower($department)
        ));
    }

    /**
     * All valid role codes including aliases (for validation 'in:' rules).
     */
    public static function allAcceptedCodes(): array
    {
        return array_unique(array_merge(
            self::ROLES,
            array_keys(self::ROLE_ALIASES)
        ));
    }
}
