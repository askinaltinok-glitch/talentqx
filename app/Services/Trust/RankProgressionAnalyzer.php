<?php

namespace App\Services\Trust;

use App\Services\Stability\StabilityConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RankProgressionAnalyzer
{
    const RANK_LADDER = [
        'deck' => [
            'DC' => 1, 'OS' => 2, 'AB' => 3, 'BSN' => 4,
            '3/O' => 5, '2/O' => 6, 'C/O' => 7, 'MASTER' => 8,
        ],
        'engine' => [
            'EC' => 1, 'WP' => 2, 'OL' => 3, 'MO' => 4,
            '4/E' => 5, '3/E' => 6, '2/E' => 7, 'C/E' => 8,
        ],
        'electrical' => [
            'ETO' => 1, 'ELECTRO' => 2,
        ],
        'catering' => [
            'MESS' => 1, 'COOK' => 2, 'CH.COOK' => 3,
            'STEWARD' => 4, 'CH.STEWARD' => 5,
        ],
    ];

    const RANK_ALIASES = [
        // Deck – officer
        'master' => 'MASTER', 'captain' => 'MASTER', 'capt' => 'MASTER', 'capt.' => 'MASTER',
        'chief officer' => 'C/O', 'chief mate' => 'C/O', 'c/m' => 'C/O',
        'second officer' => '2/O', '2nd officer' => '2/O', '2nd mate' => '2/O',
        'third officer' => '3/O', '3rd officer' => '3/O', '3rd mate' => '3/O',
        // Deck – rating
        'bosun' => 'BSN', "bo'sun" => 'BSN', 'boatswain' => 'BSN',
        'able seaman' => 'AB', 'a/b' => 'AB', 'able bodied seaman' => 'AB',
        'ordinary seaman' => 'OS', 'o/s' => 'OS',
        'deck cadet' => 'DC', 'd/c' => 'DC',
        // Engine – officer
        'chief engineer' => 'C/E',
        'second engineer' => '2/E', '2nd engineer' => '2/E',
        'third engineer' => '3/E', '3rd engineer' => '3/E',
        'fourth engineer' => '4/E', '4th engineer' => '4/E',
        // Engine – rating
        'engine cadet' => 'EC', 'e/c' => 'EC',
        'wiper' => 'WP', 'oiler' => 'OL', 'motorman' => 'MO',
        // Electrical
        'eto' => 'ETO', 'electro-technical officer' => 'ETO', 'electrical officer' => 'ETO',
        'electrician' => 'ELECTRO',
        // Catering
        'messman' => 'MESS', 'cook' => 'COOK', 'chief cook' => 'CH.COOK',
        'steward' => 'STEWARD', 'chief steward' => 'CH.STEWARD',
    ];

    /**
     * Analyze rank progression from a chronological collection of contracts.
     *
     * v1.1: Anomaly thresholds from StabilityConfig — no hardcoded values.
     */
    public function analyze(Collection $contracts, ?StabilityConfig $cfg = null): array
    {
        if ($contracts->isEmpty()) {
            return [
                'department' => null,
                'progression' => [],
                'anomalies' => [],
                'unknown_ranks' => [],
                'flags' => [],
            ];
        }

        $sorted = $contracts->sortBy('start_date')->values();

        // ── Build progression path ──
        $progression = [];
        $unknownRanks = [];

        foreach ($sorted as $contract) {
            $canonical = $this->normalizeRank($contract->rank_code);

            if ($canonical === null) {
                $unknownRanks[] = [
                    'rank' => $contract->rank_code,
                    'contract_id' => $contract->id,
                ];
                continue;
            }

            $dept = $this->detectDepartment($canonical);
            $level = $dept ? (self::RANK_LADDER[$dept][$canonical] ?? null) : null;

            $progression[] = [
                'rank' => $contract->rank_code,
                'canonical' => $canonical,
                'department' => $dept,
                'level' => $level,
                'start_date' => $contract->start_date->toDateString(),
                'contract_id' => $contract->id,
            ];
        }

        // ── Detect primary department ──
        $deptCounts = [];
        foreach ($progression as $p) {
            if ($p['department']) {
                $deptCounts[$p['department']] = ($deptCounts[$p['department']] ?? 0) + 1;
            }
        }
        arsort($deptCounts);
        $primaryDept = !empty($deptCounts) ? array_key_first($deptCounts) : null;

        // ── Detect anomalies within primary department ──
        $anomalies = [];
        $flags = [];

        $cfg = $cfg ?? new StabilityConfig();
        $promoMinMonths = (int) $cfg->get('unrealistic_promotion_months', 6);
        $promoMinLevels = (int) $cfg->get('unrealistic_promotion_levels', 2);

        $deptProgression = array_values(
            array_filter($progression, fn($p) => $p['department'] === $primaryDept && $p['level'] !== null)
        );

        for ($i = 1; $i < count($deptProgression); $i++) {
            $prev = $deptProgression[$i - 1];
            $curr = $deptProgression[$i];

            // Rank downgrade
            if ($curr['level'] < $prev['level']) {
                $anomalies[] = [
                    'type' => 'rank_downgrade',
                    'from_rank' => $prev['canonical'],
                    'to_rank' => $curr['canonical'],
                    'contract_id' => $curr['contract_id'],
                    'detail' => "Rank dropped from {$prev['canonical']} (level {$prev['level']}) to {$curr['canonical']} (level {$curr['level']})",
                ];
            }

            // Unrealistic promotion (configurable levels in < configurable months)
            $levelJump = $curr['level'] - $prev['level'];
            if ($levelJump >= $promoMinLevels) {
                $prevDate = Carbon::parse($prev['start_date']);
                $currDate = Carbon::parse($curr['start_date']);
                $monthsBetween = $prevDate->diffInMonths($currDate);

                if ($monthsBetween < $promoMinMonths) {
                    $anomalies[] = [
                        'type' => 'unrealistic_promotion',
                        'from_rank' => $prev['canonical'],
                        'to_rank' => $curr['canonical'],
                        'months_between' => $monthsBetween,
                        'contract_id' => $curr['contract_id'],
                        'detail' => "Jumped {$levelJump} levels ({$prev['canonical']} to {$curr['canonical']}) in {$monthsBetween} months",
                    ];
                }
            }
        }

        // ── Produce flags ──
        $hasDowngrade = collect($anomalies)->contains(fn($a) => $a['type'] === 'rank_downgrade');
        $hasUnrealisticPromo = collect($anomalies)->contains(fn($a) => $a['type'] === 'unrealistic_promotion');

        if ($hasDowngrade) {
            $flags[] = 'FLAG_RANK_ANOMALY';
        }
        if ($hasUnrealisticPromo) {
            $flags[] = 'FLAG_UNREALISTIC_PROMOTION';
        }

        return [
            'department' => $primaryDept,
            'progression' => $progression,
            'anomalies' => $anomalies,
            'unknown_ranks' => $unknownRanks,
            'flags' => $flags,
        ];
    }

    /**
     * Normalize a rank string to its canonical code.
     */
    public function normalizeRank(string $rank): ?string
    {
        $lower = mb_strtolower(trim($rank));

        // Direct alias match
        if (isset(self::RANK_ALIASES[$lower])) {
            return self::RANK_ALIASES[$lower];
        }

        // Check if already a canonical code (case-insensitive)
        $upper = strtoupper(trim($rank));
        foreach (self::RANK_LADDER as $ranks) {
            if (isset($ranks[$upper])) {
                return $upper;
            }
        }

        // Normalize separators and retry
        $cleaned = str_replace(['.', '-', '_'], ['', '', ''], $lower);
        if (isset(self::RANK_ALIASES[$cleaned])) {
            return self::RANK_ALIASES[$cleaned];
        }

        return null;
    }

    private function detectDepartment(string $canonical): ?string
    {
        foreach (self::RANK_LADDER as $dept => $ranks) {
            if (isset($ranks[$canonical])) {
                return $dept;
            }
        }
        return null;
    }
}
