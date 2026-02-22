<?php

namespace Database\Seeders;

use App\Models\RankHierarchy;
use Illuminate\Database\Seeder;

/**
 * Seeds rank_hierarchy table with STCW-based minimum sea service requirements.
 *
 * Bridges RankProgressionAnalyzer canonical codes (MASTER, C/O, etc.)
 * with StcwRequirementSeeder rank codes (master, chief_officer, etc.)
 *
 * min_sea_months_in_rank: STCW 2010 Manila Amendments minimum service at this rank
 *                         before eligible for promotion to next rank.
 * min_total_sea_months:   Minimum total sea service to hold this rank.
 */
class RankHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            // ========== DECK DEPARTMENT ==========
            // STCW Reg II/4 → II/5 → II/1 → II/2
            [
                'canonical_code' => 'DC',
                'stcw_rank_code' => 'deck_cadet',
                'department' => 'deck',
                'level' => 1,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 0,
                'next_rank_code' => 'OS',
                'notes' => 'STCW II/4: Deck cadet — 12 months approved seagoing training.',
            ],
            [
                'canonical_code' => 'OS',
                'stcw_rank_code' => 'ordinary_seaman',
                'department' => 'deck',
                'level' => 2,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 6,
                'next_rank_code' => 'AB',
                'notes' => 'STCW II/4: Ordinary seaman — 12 months before AB certification.',
            ],
            [
                'canonical_code' => 'AB',
                'stcw_rank_code' => 'ab_seaman',
                'department' => 'deck',
                'level' => 3,
                'min_sea_months_in_rank' => 18,
                'min_total_sea_months' => 18,
                'next_rank_code' => 'BSN',
                'notes' => 'STCW II/5: Able seaman — 18 months sea service as AB.',
            ],
            [
                'canonical_code' => 'BSN',
                'stcw_rank_code' => 'bosun',
                'department' => 'deck',
                'level' => 4,
                'min_sea_months_in_rank' => 24,
                'min_total_sea_months' => 36,
                'next_rank_code' => '3/O',
                'notes' => 'Boatswain — senior rating, 24 months typical before officer track.',
            ],
            [
                'canonical_code' => '3/O',
                'stcw_rank_code' => 'third_officer',
                'department' => 'deck',
                'level' => 5,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 36,
                'next_rank_code' => '2/O',
                'notes' => 'STCW II/1: OOW — 12 months as watchkeeping officer.',
            ],
            [
                'canonical_code' => '2/O',
                'stcw_rank_code' => 'second_officer',
                'department' => 'deck',
                'level' => 6,
                'min_sea_months_in_rank' => 18,
                'min_total_sea_months' => 48,
                'next_rank_code' => 'C/O',
                'notes' => 'STCW II/1: Second officer — 18 months as OOW before Chief Mate.',
            ],
            [
                'canonical_code' => 'C/O',
                'stcw_rank_code' => 'chief_officer',
                'department' => 'deck',
                'level' => 7,
                'min_sea_months_in_rank' => 36,
                'min_total_sea_months' => 66,
                'next_rank_code' => 'MASTER',
                'notes' => 'STCW II/2: Chief mate — 36 months as officer (12 as C/O) before Master.',
            ],
            [
                'canonical_code' => 'MASTER',
                'stcw_rank_code' => 'master',
                'department' => 'deck',
                'level' => 8,
                'min_sea_months_in_rank' => 0,
                'min_total_sea_months' => 102,
                'next_rank_code' => null,
                'notes' => 'STCW II/2: Master unlimited — top rank.',
            ],

            // ========== ENGINE DEPARTMENT ==========
            // STCW Reg III/4-5 → III/1 → III/2
            [
                'canonical_code' => 'EC',
                'stcw_rank_code' => 'engine_cadet',
                'department' => 'engine',
                'level' => 1,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 0,
                'next_rank_code' => 'WP',
                'notes' => 'Engine cadet — 12 months approved seagoing training.',
            ],
            [
                'canonical_code' => 'WP',
                'stcw_rank_code' => 'wiper',
                'department' => 'engine',
                'level' => 2,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 6,
                'next_rank_code' => 'OL',
                'notes' => 'STCW III/4: Wiper — 12 months before oiler.',
            ],
            [
                'canonical_code' => 'OL',
                'stcw_rank_code' => 'oiler',
                'department' => 'engine',
                'level' => 3,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 18,
                'next_rank_code' => 'MO',
                'notes' => 'STCW III/4-5: Oiler — 12 months sea service.',
            ],
            [
                'canonical_code' => 'MO',
                'stcw_rank_code' => 'motorman',
                'department' => 'engine',
                'level' => 4,
                'min_sea_months_in_rank' => 18,
                'min_total_sea_months' => 30,
                'next_rank_code' => '4/E',
                'notes' => 'Motorman — senior rating, 18 months typical.',
            ],
            [
                'canonical_code' => '4/E',
                'stcw_rank_code' => 'fourth_engineer',
                'department' => 'engine',
                'level' => 5,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 36,
                'next_rank_code' => '3/E',
                'notes' => 'STCW III/1: OOW engineering — 12 months as engineer officer.',
            ],
            [
                'canonical_code' => '3/E',
                'stcw_rank_code' => 'third_engineer',
                'department' => 'engine',
                'level' => 6,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 48,
                'next_rank_code' => '2/E',
                'notes' => 'STCW III/1: Third engineer — 12 months as watchkeeping engineer.',
            ],
            [
                'canonical_code' => '2/E',
                'stcw_rank_code' => 'second_engineer',
                'department' => 'engine',
                'level' => 7,
                'min_sea_months_in_rank' => 24,
                'min_total_sea_months' => 60,
                'next_rank_code' => 'C/E',
                'notes' => 'STCW III/2: Second engineer — 24 months as engineer officer before C/E.',
            ],
            [
                'canonical_code' => 'C/E',
                'stcw_rank_code' => 'chief_engineer',
                'department' => 'engine',
                'level' => 8,
                'min_sea_months_in_rank' => 0,
                'min_total_sea_months' => 84,
                'next_rank_code' => null,
                'notes' => 'STCW III/2: Chief engineer officer unlimited — top rank.',
            ],

            // ========== ELECTRICAL DEPARTMENT ==========
            [
                'canonical_code' => 'ETO',
                'stcw_rank_code' => 'eto',
                'department' => 'electrical',
                'level' => 1,
                'min_sea_months_in_rank' => 36,
                'min_total_sea_months' => 12,
                'next_rank_code' => 'ELECTRO',
                'notes' => 'STCW III/6: Electro-technical officer — 36 months before senior electrician.',
            ],
            [
                'canonical_code' => 'ELECTRO',
                'stcw_rank_code' => 'electrician',
                'department' => 'electrical',
                'level' => 2,
                'min_sea_months_in_rank' => 0,
                'min_total_sea_months' => 36,
                'next_rank_code' => null,
                'notes' => 'STCW III/7: Senior electrician — top electrical rank.',
            ],

            // ========== CATERING DEPARTMENT ==========
            [
                'canonical_code' => 'MESS',
                'stcw_rank_code' => 'messman',
                'department' => 'catering',
                'level' => 1,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 0,
                'next_rank_code' => 'COOK',
                'notes' => 'Messman / catering rating — 12 months before cook.',
            ],
            [
                'canonical_code' => 'COOK',
                'stcw_rank_code' => 'cook',
                'department' => 'catering',
                'level' => 2,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 12,
                'next_rank_code' => 'CH.COOK',
                'notes' => 'MLC Reg 3.2: Ship cook — 12 months before chief cook.',
            ],
            [
                'canonical_code' => 'CH.COOK',
                'stcw_rank_code' => 'chief_cook',
                'department' => 'catering',
                'level' => 3,
                'min_sea_months_in_rank' => 24,
                'min_total_sea_months' => 24,
                'next_rank_code' => 'STEWARD',
                'notes' => 'Chief cook — 24 months before steward track.',
            ],
            [
                'canonical_code' => 'STEWARD',
                'stcw_rank_code' => 'steward',
                'department' => 'catering',
                'level' => 4,
                'min_sea_months_in_rank' => 12,
                'min_total_sea_months' => 12,
                'next_rank_code' => 'CH.STEWARD',
                'notes' => 'Steward — 12 months before chief steward.',
            ],
            [
                'canonical_code' => 'CH.STEWARD',
                'stcw_rank_code' => 'chief_steward',
                'department' => 'catering',
                'level' => 5,
                'min_sea_months_in_rank' => 0,
                'min_total_sea_months' => 24,
                'next_rank_code' => null,
                'notes' => 'Chief steward — top catering rank.',
            ],
        ];

        foreach ($ranks as $rank) {
            RankHierarchy::updateOrCreate(
                ['canonical_code' => $rank['canonical_code']],
                $rank
            );
        }

        $this->command->info('Seeded ' . count($ranks) . ' rank hierarchy entries.');
    }
}
