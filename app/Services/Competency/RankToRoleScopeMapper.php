<?php

namespace App\Services\Competency;

/**
 * Maps canonical rank codes (MASTER, C/O, AB, etc.) to competency question role_scope values.
 *
 * Ensures that only role-appropriate questions are loaded for each candidate.
 * Non-master candidates must NEVER receive MASTER-scoped questions.
 */
class RankToRoleScopeMapper
{
    /**
     * Canonical rank code → question role_scope value.
     *
     * If a rank is not listed, it falls back to 'ALL' (generic questions only).
     */
    private const RANK_TO_SCOPE = [
        // Deck — Officers
        'MASTER'   => 'MASTER',
        'C/O'      => 'CHIEF_MATE',
        '2/O'      => 'OOW',
        '3/O'      => 'OOW',

        // Deck — Ratings
        'BSN'      => 'AB',
        'AB'       => 'AB',
        'OS'       => 'AB',
        'DC'       => 'AB',

        // Engine — Officers
        'C/E'      => 'CHIEF_ENG',
        '2/E'      => '2ND_ENG',
        '3/E'      => '2ND_ENG',
        '4/E'      => '2ND_ENG',

        // Engine — Ratings
        'OL'       => 'OILER',
        'MO'       => 'OILER',
        'WP'       => 'OILER',
        'EC'       => 'OILER',

        // Electrical
        'ETO'      => 'ALL',
        'ELECTRO'  => 'ALL',

        // Catering
        'COOK'       => 'COOK',
        'CH.COOK'    => 'COOK',
        'MESS'       => 'COOK',
        'STEWARD'    => 'COOK',
        'CH.STEWARD' => 'COOK',
    ];

    /**
     * Common aliases → canonical rank code (lowercased keys).
     */
    private const ALIASES = [
        'master'          => 'MASTER',
        'captain'         => 'MASTER',
        'capt'            => 'MASTER',
        'chief officer'   => 'C/O',
        'chief mate'      => 'C/O',
        'chief_mate'      => 'C/O',
        'second officer'  => '2/O',
        '2nd officer'     => '2/O',
        'third officer'   => '3/O',
        '3rd officer'     => '3/O',
        'bosun'           => 'BSN',
        'boatswain'       => 'BSN',
        'able seaman'     => 'AB',
        'ordinary seaman' => 'OS',
        'chief engineer'  => 'C/E',
        'chief_eng'       => 'C/E',
        'second engineer' => '2/E',
        '2nd engineer'    => '2/E',
        '2nd_eng'         => '2/E',
        'third engineer'  => '3/E',
        '3rd engineer'    => '3/E',
        'fourth engineer' => '4/E',
        '4th engineer'    => '4/E',
        'oiler'           => 'OL',
        'motorman'        => 'MO',
        'wiper'           => 'WP',
        'cook'            => 'COOK',
        'chief cook'      => 'CH.COOK',
        'steward'         => 'STEWARD',
        'chief steward'   => 'CH.STEWARD',
        'messman'         => 'MESS',
        'eto'             => 'ETO',
        'electrician'     => 'ELECTRO',
    ];

    /**
     * Map a position/rank code to the competency question role_scope.
     *
     * @param string $positionCode  Raw position code from interview (e.g., 'MASTER', 'AB', 'C/O')
     * @return string  Role scope for question filtering (e.g., 'MASTER', 'AB', 'ALL')
     */
    public static function map(string $positionCode): string
    {
        $code = strtoupper(trim($positionCode));

        // Direct match
        if (isset(self::RANK_TO_SCOPE[$code])) {
            return self::RANK_TO_SCOPE[$code];
        }

        // Try alias lookup
        $lower = strtolower(trim($positionCode));
        if (isset(self::ALIASES[$lower])) {
            $canonical = self::ALIASES[$lower];
            return self::RANK_TO_SCOPE[$canonical] ?? 'ALL';
        }

        // Underscore-separated variants (e.g., 'CHIEF_MATE' → 'C/O')
        $underscored = str_replace('_', ' ', $lower);
        if (isset(self::ALIASES[$underscored])) {
            $canonical = self::ALIASES[$underscored];
            return self::RANK_TO_SCOPE[$canonical] ?? 'ALL';
        }

        // If the code itself matches a known role_scope value, use it directly
        $knownScopes = ['MASTER', 'CHIEF_MATE', 'OOW', 'AB', 'OS', 'CHIEF_ENG', '2ND_ENG', 'OILER', 'COOK', 'ALL'];
        if (in_array($code, $knownScopes)) {
            return $code;
        }

        // Unknown rank → generic questions only
        return 'ALL';
    }
}
