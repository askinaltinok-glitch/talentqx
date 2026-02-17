<?php

namespace Database\Seeders;

use App\Models\StcwRequirement;
use Illuminate\Database\Seeder;

/**
 * Seeds stcw_requirements table with real STCW regulation mappings.
 *
 * Maps each rank to its mandatory certificate requirements.
 * Based on STCW 2010 Manila Amendments.
 */
class StcwRequirementSeeder extends Seeder
{
    public function run(): void
    {
        // Shared base certificates required for ALL seafarers
        $baseSTCW = ['BST', 'PSSR', 'SAT', 'MEDICAL_FITNESS', 'SEAMANS_BOOK', 'PASSPORT'];

        $requirements = [
            // ========== DECK DEPARTMENT ==========
            [
                'rank_code' => 'master',
                'department' => 'deck',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, [
                    'COC_MASTER', 'GMDSS', 'ARPA', 'BRM', 'AFF', 'PSCRB',
                    'ECDIS', 'MEDICAL_CARE',
                ]),
                'mandatory' => true,
                'notes' => 'STCW II/2: Master unlimited. Full bridge team + emergency management.',
            ],
            [
                'rank_code' => 'chief_officer',
                'department' => 'deck',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, [
                    'COC_CHIEF_OFFICER', 'GMDSS', 'ARPA', 'BRM', 'AFF', 'PSCRB', 'ECDIS',
                ]),
                'mandatory' => true,
                'notes' => 'STCW II/2: Chief mate unlimited.',
            ],
            [
                'rank_code' => 'second_officer',
                'department' => 'deck',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, [
                    'COC_OOW', 'GMDSS', 'ARPA', 'BRM', 'ECDIS',
                ]),
                'mandatory' => true,
                'notes' => 'STCW II/1: OOW navigational watch.',
            ],
            [
                'rank_code' => 'third_officer',
                'department' => 'deck',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, [
                    'COC_OOW', 'GMDSS', 'ARPA', 'ECDIS',
                ]),
                'mandatory' => true,
                'notes' => 'STCW II/1: OOW navigational watch (junior).',
            ],
            [
                'rank_code' => 'deck_cadet',
                'department' => 'deck',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'STCW II/1: Deck cadet in training.',
            ],
            [
                'rank_code' => 'bosun',
                'department' => 'deck',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, ['FPFF', 'PST']),
                'mandatory' => true,
                'notes' => 'Boatswain: deck crew leader.',
            ],
            [
                'rank_code' => 'ab_seaman',
                'department' => 'deck',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, ['PST']),
                'mandatory' => true,
                'notes' => 'STCW II/5: Able seaman.',
            ],
            [
                'rank_code' => 'ordinary_seaman',
                'department' => 'deck',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'STCW II/4: Ordinary seaman / rating.',
            ],

            // ========== ENGINE DEPARTMENT ==========
            [
                'rank_code' => 'chief_engineer',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, [
                    'COC_CHIEF_ENGINEER', 'ERM', 'AFF',
                ]),
                'mandatory' => true,
                'notes' => 'STCW III/2: Chief engineer officer unlimited.',
            ],
            [
                'rank_code' => 'second_engineer',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, [
                    'COC_2ND_ENGINEER', 'ERM', 'AFF',
                ]),
                'mandatory' => true,
                'notes' => 'STCW III/2: Second engineer officer.',
            ],
            [
                'rank_code' => 'third_engineer',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, [
                    'COC_ENGINEER', 'ERM',
                ]),
                'mandatory' => true,
                'notes' => 'STCW III/1: OOW engineering watch.',
            ],
            [
                'rank_code' => 'fourth_engineer',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, [
                    'COC_ENGINEER',
                ]),
                'mandatory' => true,
                'notes' => 'STCW III/1: OOW engineering watch (junior).',
            ],
            [
                'rank_code' => 'engine_cadet',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'Engine cadet in training.',
            ],
            [
                'rank_code' => 'electrician',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, ['HV_SAFETY']),
                'mandatory' => true,
                'notes' => 'STCW III/7: Electro-technical officer / ship electrician.',
            ],
            [
                'rank_code' => 'motorman',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'Engine room rating.',
            ],
            [
                'rank_code' => 'oiler',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'STCW III/4-5: Engine room oiler / rating.',
            ],
            [
                'rank_code' => 'wiper',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'Engine room wiper / junior rating.',
            ],
            [
                'rank_code' => 'fitter',
                'department' => 'engine',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'Mechanical fitter.',
            ],

            // ========== HOTEL / OTHER ==========
            [
                'rank_code' => 'chief_cook',
                'department' => 'hotel',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, ['MLC_MEDICAL']),
                'mandatory' => true,
                'notes' => 'MLC Reg 3.2: Ship cook certificate required.',
            ],
            [
                'rank_code' => 'cook',
                'department' => 'hotel',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, ['MLC_MEDICAL']),
                'mandatory' => true,
                'notes' => 'MLC Reg 3.2: Cook / 2nd cook.',
            ],
            [
                'rank_code' => 'messman',
                'department' => 'hotel',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'Catering rating.',
            ],
            [
                'rank_code' => 'steward',
                'department' => 'hotel',
                'vessel_type' => 'any',
                'required_certificates' => $baseSTCW,
                'mandatory' => true,
                'notes' => 'Ship steward.',
            ],
            [
                'rank_code' => 'pumpman',
                'department' => 'other',
                'vessel_type' => 'tanker',
                'required_certificates' => array_merge($baseSTCW, ['TANKER_FAM']),
                'mandatory' => true,
                'notes' => 'Tanker pumpman — requires tanker familiarization.',
            ],
            [
                'rank_code' => 'radio_officer',
                'department' => 'other',
                'vessel_type' => 'any',
                'required_certificates' => array_merge($baseSTCW, ['GMDSS']),
                'mandatory' => true,
                'notes' => 'STCW IV/2: Radio officer / GMDSS operator.',
            ],

            // ========== TANKER VESSEL-SPECIFIC (additional) ==========
            [
                'rank_code' => 'master',
                'department' => 'deck',
                'vessel_type' => 'tanker',
                'required_certificates' => ['TANKER_FAM', 'TANKER_OIL'],
                'mandatory' => true,
                'notes' => 'STCW V/1: Additional tanker training for masters on tanker vessels.',
            ],
            [
                'rank_code' => 'chief_officer',
                'department' => 'deck',
                'vessel_type' => 'tanker',
                'required_certificates' => ['TANKER_FAM', 'TANKER_OIL'],
                'mandatory' => true,
                'notes' => 'STCW V/1: Additional tanker training.',
            ],
            [
                'rank_code' => 'chief_engineer',
                'department' => 'engine',
                'vessel_type' => 'tanker',
                'required_certificates' => ['TANKER_FAM'],
                'mandatory' => true,
                'notes' => 'STCW V/1: Tanker familiarization for chief engineers on tankers.',
            ],
        ];

        foreach ($requirements as $req) {
            StcwRequirement::updateOrCreate(
                [
                    'rank_code' => $req['rank_code'],
                    'department' => $req['department'],
                    'vessel_type' => $req['vessel_type'],
                ],
                $req
            );
        }

        $this->command->info('Seeded ' . count($requirements) . ' STCW requirements.');
    }
}
