<?php

namespace Database\Seeders;

use App\Models\MaritimeScenario;
use Illuminate\Database\Seeder;

class MaritimeScenarioSeeder extends Seeder
{
    /**
     * Seed 72 maritime scenarios (9 classes × 8 slots).
     * All scenarios start as is_active=false.
     * Admin must fill content via Scenario Bank and activate manually.
     */
    public function run(): void
    {
        $scenarios = $this->getDefinitions();

        foreach ($scenarios as $scenario) {
            MaritimeScenario::updateOrCreate(
                ['scenario_code' => $scenario['scenario_code']],
                $scenario,
            );
        }

        $this->command->info('Seeded ' . count($scenarios) . ' maritime scenarios (all inactive).');
    }

    private function getDefinitions(): array
    {
        // Slot → capability mapping
        $slotCapability = [
            1 => 'NAV_COMPLEX',
            2 => 'CMD_SCALE',
            3 => 'TECH_DEPTH',
            4 => 'RISK_MGMT',
            5 => 'CREW_LEAD',
            6 => 'AUTO_DEP',
            7 => 'CRISIS_RSP',
            8 => 'TRADEOFF',
        ];

        // Capability short codes for scenario_code
        $capShort = [
            'NAV_COMPLEX' => 'NAV',
            'CMD_SCALE'   => 'CMD',
            'TECH_DEPTH'  => 'TECH',
            'RISK_MGMT'   => 'RISK',
            'CREW_LEAD'   => 'CREW',
            'AUTO_DEP'    => 'AUTO',
            'CRISIS_RSP'  => 'CRIS',
            'TRADEOFF'    => 'TRADE',
        ];

        // Domain assignments per class per slot (from architecture doc)
        $classDomains = [
            'RIVER' => [
                1 => 'PORT_OPS',   // departure from river lock
                2 => 'NAV_HAZ',    // night transit narrow channel
                3 => 'NAV_HAZ',    // shallow water grounding
                4 => 'CARGO_EMG',  // barge breakaway
                5 => 'CREW_EMG',   // crew incapacitated
                6 => 'NAV_HAZ',    // GPS failure
                7 => 'ENG_MACH',   // flooding hull breach
                8 => 'TRADEOFF',   // schedule vs safety
            ],
            'COASTAL' => [
                1 => 'NAV_HAZ',    // fog approach
                2 => 'COMM_PRESS', // SAR divert
                3 => 'ENG_MACH',   // engine cooling
                4 => 'CARGO_EMG',  // cargo shift
                5 => 'CREW_EMG',   // crew injury
                6 => 'NAV_HAZ',    // radar failure
                7 => 'CREW_EMG',   // fire
                8 => 'TRADEOFF',   // weather delay
            ],
            'SHORT_SEA' => [
                1 => 'NAV_HAZ',    // strait transit
                2 => 'PORT_OPS',   // port turnaround
                3 => 'CARGO_EMG',  // container lashing
                4 => 'NAV_HAZ',    // piracy risk
                5 => 'CREW_EMG',   // multinational crew
                6 => 'NAV_HAZ',    // ECDIS discrepancy
                7 => 'COLAV',      // collision fishing vessel
                8 => 'TRADEOFF',   // emissions compliance
            ],
            'DEEP_SEA' => [
                1 => 'WX_DEC',     // ocean routing typhoon
                2 => 'CREW_EMG',   // crew morale
                3 => 'ENG_MACH',   // hold flooding
                4 => 'ENG_MACH',   // structural crack
                5 => 'CREW_EMG',   // medical emergency
                6 => 'NAV_HAZ',    // gyro failure
                7 => 'CREW_EMG',   // abandon ship
                8 => 'TRADEOFF',   // cargo care vs schedule
            ],
            'CONTAINER_ULCS' => [
                1 => 'PORT_OPS',   // port approach UKC
                2 => 'PORT_OPS',   // multi-terminal
                3 => 'WX_DEC',     // parametric rolling
                4 => 'CARGO_EMG',  // DG container
                5 => 'CREW_EMG',   // stevedore injury
                6 => 'NAV_HAZ',    // ECDIS route check
                7 => 'CARGO_EMG',  // container fire
                8 => 'TRADEOFF',   // schedule vs weather
            ],
            'TANKER' => [
                1 => 'PORT_OPS',   // STS operation
                2 => 'REG_ENC',    // SIRE vetting
                3 => 'CARGO_EMG',  // tank cleaning
                4 => 'CARGO_EMG',  // manifold leak
                5 => 'CREW_EMG',   // pump room rescue
                6 => 'ENG_MACH',   // cargo monitoring failure
                7 => 'CARGO_EMG',  // explosion
                8 => 'TRADEOFF',   // VOC emissions
            ],
            'LNG' => [
                1 => 'PORT_OPS',   // FSRU approach
                2 => 'CARGO_EMG',  // cargo transfer
                3 => 'CARGO_EMG',  // containment alarm
                4 => 'ENG_MACH',   // boil-off management
                5 => 'CREW_EMG',   // emergency drill
                6 => 'ENG_MACH',   // ESD link failure
                7 => 'CARGO_EMG',  // gas detection
                8 => 'TRADEOFF',   // heel management
            ],
            'OFFSHORE' => [
                1 => 'WX_DEC',     // DP operations weather
                2 => 'PORT_OPS',   // multi-vessel coordination
                3 => 'ENG_MACH',   // anchor handling
                4 => 'CARGO_EMG',  // dropped object
                5 => 'CREW_EMG',   // helicopter operations
                6 => 'NAV_HAZ',    // DP reference loss
                7 => 'ENV_COMP',   // blowout scenario
                8 => 'TRADEOFF',   // weather window
            ],
            'PASSENGER' => [
                1 => 'PORT_OPS',   // port maneuver wind shift
                2 => 'CREW_EMG',   // norovirus outbreak
                3 => 'ENG_MACH',   // blackout
                4 => 'REG_ENC',    // PSC inspection
                5 => 'CREW_EMG',   // crowd management
                6 => 'NAV_HAZ',    // bridge integration malfunction
                7 => 'NAV_HAZ',    // flooding grounding
                8 => 'TRADEOFF',   // port of refuge
            ],
        ];

        $scenarios = [];

        foreach ($classDomains as $class => $domains) {
            for ($slot = 1; $slot <= 8; $slot++) {
                $cap = $slotCapability[$slot];
                $short = $capShort[$cap];
                $code = sprintf('%s_S%02d_%s_001', $class, $slot, $short);
                $domain = $domains[$slot];
                $tier = ($slot <= 6) ? 2 : 3;

                $scenarios[] = [
                    'scenario_code'               => $code,
                    'command_class'                => $class,
                    'slot'                         => $slot,
                    'domain'                       => $domain,
                    'primary_capability'           => $cap,
                    'secondary_capabilities'       => null,
                    'difficulty_tier'              => $tier,
                    'briefing_json'                => $this->placeholderBriefing($class, $slot, $cap),
                    'decision_prompt'              => "Describe your immediate actions, communications, and decision-making process.",
                    'decision_prompt_i18n'         => null,
                    'evaluation_axes_json'         => $this->placeholderAxes(),
                    'critical_omission_flags_json' => $this->placeholderOmissions(),
                    'expected_references_json'     => null,
                    'red_flags_json'               => null,
                    'version'                      => 'v2',
                    'is_active'                    => false,
                ];
            }
        }

        return $scenarios;
    }

    /**
     * Minimal but valid briefing JSON per the schema.
     * Contains all 4 languages with TBD content.
     */
    private function placeholderBriefing(string $class, int $slot, string $cap): array
    {
        $template = [
            'situation'           => "TBD — {$class} scenario for slot {$slot} ({$cap}).",
            'your_position'       => 'TBD — To be defined by content team.',
            'available_resources' => ['Bridge team', 'Duty engineer', 'Deck crew'],
            'current_conditions'  => [
                'weather' => 'TBD',
                'time'    => 'TBD',
                'tide'    => 'TBD',
            ],
        ];

        return [
            'en' => $template,
            'tr' => array_merge($template, [
                'situation'     => "TBD — {$class} senaryosu, slot {$slot} ({$cap}).",
                'your_position' => 'TBD — İçerik ekibi tarafından belirlenecek.',
            ]),
            'ru' => array_merge($template, [
                'situation'     => "TBD — Сценарий {$class}, слот {$slot} ({$cap}).",
                'your_position' => 'TBD — Будет определено командой контента.',
            ]),
            'az' => array_merge($template, [
                'situation'     => "TBD — {$class} ssenarisi, slot {$slot} ({$cap}).",
                'your_position' => 'TBD — Məzmun komandası tərəfindən müəyyən ediləcək.',
            ]),
        ];
    }

    /**
     * Placeholder evaluation axes with incomplete rubric (only 3 levels).
     * Activation requires 5 levels — admin must complete rubrics before activating.
     */
    private function placeholderAxes(): array
    {
        return [
            [
                'axis'   => 'primary_action',
                'weight' => 0.40,
                'rubric_levels' => [
                    '1' => 'TBD',
                    '3' => 'TBD',
                    '5' => 'TBD',
                ],
            ],
            [
                'axis'   => 'decision_quality',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'TBD',
                    '3' => 'TBD',
                    '5' => 'TBD',
                ],
            ],
            [
                'axis'   => 'procedural_compliance',
                'weight' => 0.25,
                'rubric_levels' => [
                    '1' => 'TBD',
                    '3' => 'TBD',
                    '5' => 'TBD',
                ],
            ],
        ];
    }

    /**
     * Minimal but valid omission flags.
     */
    private function placeholderOmissions(): array
    {
        return [
            ['flag' => 'TBD — Critical omission to be defined', 'severity' => 'critical'],
            ['flag' => 'TBD — Major omission to be defined', 'severity' => 'major'],
        ];
    }
}
