<?php

namespace Database\Seeders;

use App\Models\InterviewTemplate;
use Illuminate\Database\Seeder;

class FilInterviewTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGeneric();

        $this->seedDeckGeneric();
        $this->seedDeckCaptain();
        $this->seedDeckChiefOfficer();
        $this->seedDeckSecondOfficer();
        $this->seedDeckThirdOfficer();
        $this->seedDeckBosun();
        $this->seedDeckAbleSeaman();
        $this->seedDeckOrdinarySeaman();

        $this->seedEngineGeneric();
        $this->seedEngineChiefEngineer();
        $this->seedEngineSecondEngineer();
        $this->seedEngineThirdEngineer();
        $this->seedEngineMotorman();
        $this->seedEngineOiler();
        $this->seedEngineElectrician();

        $this->seedGalleyGeneric();
        $this->seedGalleyCook();
        $this->seedGalleySteward();
        $this->seedGalleyMessman();

        $this->seedCadetGeneric();
        $this->seedCadetDeckCadet();
        $this->seedCadetEngineCadet();

        $this->command->info('Filipino (fil) interview templates seeded: 23 templates.');
    }

    /* ================================================================
     *  GENERIC TEMPLATE
     * ================================================================ */

    private function seedGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => '__generic__'],
            [
                'title' => 'Generic Interview Template (Filipino)',
                'template_json' => json_encode([
                    'version' => 'v1',
                    'language' => 'fil',
                    'generic_template' => [
                        'questions' => [
                            [
                                'slot' => 1,
                                'competency' => 'communication',
                                'question' => 'Maaari mo bang ilarawan ang isang sitwasyon kung saan kailangan mong ipaliwanag ang isang kumplikadong paksa sa simpleng paraan? Ano ang ginawa mo at ano ang naging resulta?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Hindi naipaliwanag, walang perspektiba ng nakikinig, magulo at walang direksyon ang paliwanag',
                                    '2' => 'Naipasa ang pangunahing impormasyon pero walang istruktura, hindi naayon sa nakikinig',
                                    '3' => 'Malinaw na paliwanag, pangunahing istruktura, bukas sa feedback',
                                    '4' => 'Malinaw at organisado, naayon sa antas ng nakikinig, handang sumagot sa mga tanong',
                                    '5' => 'Napakagaling na istruktura, empatikong paliwanag na nakatuon sa nakikinig, epektibong feedback loop',
                                ],
                                'positive_signals' => [
                                    'Tinanong ang antas ng kaalaman ng nakikinig',
                                    'Gumamit ng mga halimbawa at paghahambing',
                                    'Sinigurong naunawaan ang mensahe',
                                    'Inangkop ang diskarte batay sa feedback',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Pag-iwas sa responsibilidad sa komunikasyon: "hindi ko trabaho yan", "iba na lang ang bahala"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 2,
                                'competency' => 'accountability',
                                'question' => 'Maaari mo bang ilarawan ang isang sitwasyon sa trabaho kung saan nagkamali ka o may nangyaring mali? Paano mo hinarap ito?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Itinanggi ang pagkakamali o sinisi ang iba, walang kinuhang responsibilidad',
                                    '2' => 'Inamin ang pagkakamali pero walang ginawang aksyon, nanatiling pasibo',
                                    '3' => 'Inamin ang pagkakamali at gumawa ng pangunahing hakbang sa pagwawasto',
                                    '4' => 'Buong responsibilidad, proaktibong nakahanap ng solusyon, nagpaalam sa mga stakeholder',
                                    '5' => 'Inangkin ang pagkakamali, bumuo ng sistematikong solusyon, nagmungkahi ng pagpapabuti sa proseso',
                                ],
                                'positive_signals' => [
                                    'Malinaw na inamin ang pagkakamali',
                                    'Hindi sinisi ang iba',
                                    'Naglahad ng kongkretong pagwawasto',
                                    'Ibinahagi ang mga aral na natutunan',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_BLAME',
                                        'trigger_guidance' => 'Palaging tinuturo ang mga panlabas na dahilan: "hindi ako sinuportahan ng team", "mali ang utos ng manager"',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Hindi magkatugma ang kwento: sinisi muna ang iba tapos inangkin, magkasalungat na mga detalye',
                                        'severity' => 'high',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 3,
                                'competency' => 'teamwork',
                                'question' => 'Maaari mo bang ilarawan ang isang proyekto kung saan nagtrabaho ka kasama ang mga team member na may magkakaibang pananaw? Paano mo pinamamahalaan ang mga magkaibang perspektiba?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Umiwas sa teamwork o ipinataw ang sariling pananaw, hindi naghanap ng konsensus',
                                    '2' => 'Pasibong partisipasyon, hindi nagpahayag ng pananaw o binalewala ang conflict',
                                    '3' => 'Nakinig sa iba\'t ibang pananaw, gumawa ng pangunahing pagsisikap para sa kasunduan',
                                    '4' => 'Aktibong in-integrate ang iba\'t ibang perspektiba, lumikha ng konstruktibong kapaligiran sa diskusyon',
                                    '5' => 'Lumikha ng synergy mula sa pagkakaiba, siniguradong lahat ay kasali, ginabayan patungo sa iisang layunin',
                                ],
                                'positive_signals' => [
                                    'Aktibong hiningi ang ideya ng iba',
                                    'Bukas na baguhin ang sariling pananaw',
                                    'Konstruktibong pinamahalaan ang conflict',
                                    'Iniuna ang tagumpay ng team kaysa sa sarili',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_EGO',
                                        'trigger_guidance' => 'Inangkin ang tagumpay ng team: "sa totoo lang ideya ko yun", "hindi nila magagawa kung wala ako"',
                                        'severity' => 'medium',
                                    ],
                                    [
                                        'code' => 'RF_AGGRESSION',
                                        'trigger_guidance' => 'Nakakainsultong ekspresyon sa mga kasama sa team: insulto, personal na atake, galit na tono',
                                        'severity' => 'critical',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 4,
                                'competency' => 'stress_resilience',
                                'question' => 'Maaari mo bang ilarawan ang isang panahon kung kailan nagtrabaho ka sa ilalim ng matinding presyon na may maraming priority nang sabay-sabay? Paano ka nakaya?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Bumigay sa presyon, hindi nakumpleto ang mga gawain, panic o pag-iwas',
                                    '2' => 'Nakumpleto nang may kahirapan, walang estratehiya sa pamamahala ng stress, reaktibong diskarte',
                                    '3' => 'Nakumpleto ang mga gawain, pangunahing prioritization, katamtamang pamamahala ng stress',
                                    '4' => 'Epektibong prioritization, nanatiling kalmado na may sistematikong diskarte, napanatili ang kalidad',
                                    '5' => 'Natatanging pagganap sa ilalim ng presyon, pinakalma ang iba, ginamit ang stress bilang motibasyon',
                                ],
                                'positive_signals' => [
                                    'Naglahad ng kongkretong paraan ng prioritization',
                                    'Nagpakita ng emosyonal na kontrol',
                                    'Humingi ng tulong kapag kailangan',
                                    'May natutunan para sa hinaharap',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_UNSTABLE',
                                        'trigger_guidance' => 'Hindi kontroladong reaksyon sa stress: "sumabog ako", "umalis ako", "nawalan ako ng kontrol"',
                                        'severity' => 'medium',
                                    ],
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Sistematikong pag-iwas sa mga sitwasyong may stress: "hindi ko klase yan na trabaho", "hindi ko kinukuha yang responsibilidad na yan"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 5,
                                'competency' => 'adaptability',
                                'question' => 'Paano ka nag-adapt nang magkaroon ng hindi inaasahang pagbabago sa iyong trabaho? Maaari ka bang magbigay ng halimbawa?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Lumaban sa pagbabago, hindi nag-adapt, nagreklamo o humarang',
                                    '2' => 'Nag-adapt nang may pag-aatubili, napanatili ang negatibong saloobin',
                                    '3' => 'Tinanggap ang pagbabago, nag-adapt sa makatwirang oras',
                                    '4' => 'Mabilis na tinanggap ang pagbabago, epektibong nagtrabaho sa bagong sitwasyon, tinulungan ang iba na mag-adapt',
                                    '5' => 'Ginawang oportunidad ang pagbabago, nagbigay ng proaktibong mungkahi, nanguna sa pagbabago',
                                ],
                                'positive_signals' => [
                                    'Sinubukang intindihin ang dahilan ng pagbabago',
                                    'Mabilis na natutunan ang bagong kasanayan',
                                    'Napanatili ang positibong saloobin',
                                    'Tinulungan ang iba na mag-adapt',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Pagtakas at pagtanggi sa pagbabago: "hindi ko ginagawa yan", "hindi ko trabaho matuto ng bagong sistema"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 6,
                                'competency' => 'learning_agility',
                                'question' => 'Maaari mo bang ilarawan ang isang sitwasyon kung saan kailangan mong matuto ng isang ganap na bagong paksa o kasanayan nang mabilis? Paano mo nilapitan ito?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Ayaw matuto, pasibong saloobin, umaasa sa iba',
                                    '2' => 'Natuto sa pangunahing antas pero hindi nakapagpalawak, ginawa lang ang kailangan',
                                    '3' => 'Aktibong pagsisikap sa pagkatuto, gumamit ng karaniwang resources, natuto sa makatwirang oras',
                                    '4' => 'Mabilis at epektibong pagkatuto, gumamit ng maraming resources, agad na inilapat sa praktika',
                                    '5' => 'Natatanging bilis ng pagkatuto, pinabuti ang natutunan, nagturo sa iba',
                                ],
                                'positive_signals' => [
                                    'Gumamit ng maraming learning resources',
                                    'Hindi takot magtanong',
                                    'Inilapat ang natutunan sa praktika',
                                    'Nagpahayag ng kasiyahan sa pagkatuto',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Pag-iwas sa responsibilidad sa pagkatuto: "hindi ko trabaho matuto ng bagong bagay", "iba na lang ang magturo sa akin"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 7,
                                'competency' => 'integrity',
                                'question' => 'Maaari mo bang ilarawan ang isang sitwasyon kung saan naharap ka sa isang mahirap na desisyon sa etika? Paano ka kumilos?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Naglahad ng hindi etikal na pag-uugali o ni-normalize ang paglabag sa patakaran',
                                    '2' => 'Nakilala ang etikal na dilemma pero hindi kumilos, nanatiling pasibo',
                                    '3' => 'Ginawa ang tama pero dahil lang kinakailangan, hindi malinaw ang panloob na motibasyon',
                                    '4' => 'Nanatiling tapat sa mga prinsipyo ng etika, gumawa ng tamang desisyon kahit sa mahirap na sitwasyon, konsistenteng pag-uugali',
                                    '5' => 'Nagpakita ng etikal na pamumuno, ginabayan ang iba sa tamang pag-uugali, nag-risk para ipagtanggol ang tama',
                                ],
                                'positive_signals' => [
                                    'Naglahad ng malinaw at konsistenteng etikal na balangkas',
                                    'Ginawa ang tama kahit may personal na gastos',
                                    'Binigyang-diin ang transparency at katapatan',
                                    'Lumaban sa hindi etikal na presyon',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Hindi konsistente sa etika: mga patakaran na nagbabago depende sa sitwasyon, "lahat naman ginagawa yan" na normalization',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_BLAME',
                                        'trigger_guidance' => 'Sinisisi ang iba sa mga paglabag sa etika: "pinilit ako ng manager", "ganoon ang sistema"',
                                        'severity' => 'high',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 8,
                                'competency' => 'role_competence',
                                'question' => 'Maaari mo bang ilarawan ang isang karanasan kung saan ginanap mo ang isa sa mga pangunahing kinakailangan ng posisyong ito? Anong diskarte ang ginamit mo at ano ang naging resulta?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Walang kaugnay na karanasan o napakababaw, nagpakita ng kawalan ng pag-unawa sa mga pangunahing kinakailangan',
                                    '2' => 'Limitadong karanasan, alam ang pangunahing konsepto pero mahina sa aplikasyon',
                                    '3' => 'Sapat na karanasan, tama ang paggamit ng karaniwang proseso, katanggap-tanggap na resulta',
                                    '4' => 'Malakas na karanasan, de-kalidad at nasusukat na resulta, pinabuti ang proseso',
                                    '5' => 'Natatanging pagganap, bumuo ng makabagong diskarte, kayang magturo sa iba',
                                ],
                                'positive_signals' => [
                                    'Nagbahagi ng kongkreto at nasusukat na resulta',
                                    'Tama at lohikal na inilarawan ang mga hakbang sa proseso',
                                    'Ipinaliwanag kung paano nalutas ang mga problema',
                                    'Nagbigay ng mga halimbawa ng patuloy na pagpapabuti',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Pagmamalabis sa kakayahan: hindi magkatugma kapag hiningi ang detalye, malabo ang sagot kapag hiningi ng paliwanag',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_EGO',
                                        'trigger_guidance' => 'Hindi realistikong kumpiyansa: "ako ang pinakamahusay sa gawaing ito", "walang ibang kasinghusay ko"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'positions' => [],
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] __generic__');
    }

    /* ================================================================
     *  DECK DEPARTMENT
     * ================================================================ */

    private function deckQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'deck_fil_s1', 'type' => 'open', 'text' => 'Anong uri ng barko ang pinagtatrabahuhan mo? tonelada/bandila/ruta/tagal.'],
                ['id' => 'deck_fil_s2', 'type' => 'open', 'text' => 'Ilarawan ang iyong mga tungkulin at watch system.'],
                ['id' => 'deck_fil_s3', 'type' => 'scale', 'text' => 'I-rate ang Bridge English (SMCP) 1\u20135.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'deck_fil_t1', 'type' => 'open', 'text' => 'COLREG crossing: lohika ng desisyon + senaryo.'],
                ['id' => 'deck_fil_t2', 'type' => 'open', 'text' => 'Ano ang ini-endorse mo sa watch handover?'],
                ['id' => 'deck_fil_t3', 'type' => 'open', 'text' => 'Anong ECDIS safety settings ang vine-verify mo?'],
                ['id' => 'deck_fil_t4', 'type' => 'open', 'text' => 'Top 3 mooring risks at mga kontrol?'],
            ],
            'safety' => [
                ['id' => 'deck_fil_sa1', 'type' => 'open', 'text' => 'MOB: unang 60 segundo na aksyon?'],
                ['id' => 'deck_fil_sa2', 'type' => 'open', 'text' => 'Fire alarm: papel ng bridge/deck team?'],
                ['id' => 'deck_fil_sa3', 'type' => 'open', 'text' => 'Saan mandatory ang PTW? 3 halimbawa.'],
            ],
            'behaviour' => [
                ['id' => 'deck_fil_b1', 'type' => 'open', 'text' => 'Paano mo ine-escalate ang safety concern sa mga senior?'],
                ['id' => 'deck_fil_b2', 'type' => 'open', 'text' => 'Paano mo pinamamahalaan ang fatigue sa aktwal?'],
            ],
        ];
    }

    private function deckSections(): array
    {
        $q = $this->deckQuestions();
        return [
            ['key' => 'screening',  'title' => 'Paunang Pagsusuri',       'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Operasyon / Teknikal',     'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Kaligtasan / Emergency',   'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Ugali / Disiplina',        'questions' => $q['behaviour']],
        ];
    }

    private function deckScoring(): array
    {
        return ['weights' => ['screening' => 0.2, 'technical' => 0.4, 'safety' => 0.3, 'behaviour' => 0.1]];
    }

    private function seedDeckGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'deck___generic__'],
            [
                'title' => 'Deck Department Generic Template (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Deck / Generic',
                    'department' => 'deck',
                    'language' => 'fil',
                    'role_scope' => '__generic__',
                    'sections' => $this->deckSections(),
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] deck___generic__');
    }

    private function seedDeckCaptain(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_captain_fil_s1', 'type' => 'open', 'text' => 'Bilang Kapitan, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];

        $sections[1]['questions'][] = ['id' => 'rs_captain_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Kapitan, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[1]['questions'][] = ['id' => 'rs_captain_fil_t2', 'type' => 'open', 'text' => 'Ilarawan ang mahirap na COLREG senaryo (crossing/visibility). Anong data ang nagda-drive ng desisyon mo?'];
        $sections[1]['questions'][] = ['id' => 'rs_captain_fil_t3', 'type' => 'open', 'text' => 'Sa passage planning, paano mo pinamamahalaan ang no-go areas, UKC, at weather windows?'];

        $sections[2]['questions'][] = ['id' => 'rs_captain_fil_sa2', 'type' => 'open', 'text' => 'Sa emergency (fire/MOB/blackout), ano ang unang 5 utos mo bilang Kapitan at bakit?'];

        $sections[3]['questions'][] = ['id' => 'rs_captain_fil_b1', 'type' => 'open', 'text' => 'Bilang Kapitan, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'deck_captain'],
            [
                'title' => 'Maritime / Role / Kapitan (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Kapitan',
                    'department' => 'deck',
                    'language' => 'fil',
                    'role_scope' => 'captain',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] deck_captain');
    }

    private function seedDeckChiefOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_chief_officer_fil_s1', 'type' => 'open', 'text' => 'Bilang Chief Officer, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_chief_officer_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Chief Officer, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_chief_officer_fil_b1', 'type' => 'open', 'text' => 'Bilang Chief Officer, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'deck_chief_officer'],
            [
                'title' => 'Maritime / Role / Chief Officer (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Chief Officer',
                    'department' => 'deck',
                    'language' => 'fil',
                    'role_scope' => 'chief_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] deck_chief_officer');
    }

    private function seedDeckSecondOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_second_officer_fil_s1', 'type' => 'open', 'text' => 'Bilang 2nd Officer, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_second_officer_fil_t1', 'type' => 'open', 'text' => 'Sa papel na 2nd Officer, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_second_officer_fil_b1', 'type' => 'open', 'text' => 'Bilang 2nd Officer, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'deck_second_officer'],
            [
                'title' => 'Maritime / Role / 2nd Officer (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / 2nd Officer',
                    'department' => 'deck',
                    'language' => 'fil',
                    'role_scope' => 'second_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] deck_second_officer');
    }

    private function seedDeckThirdOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_third_officer_fil_s1', 'type' => 'open', 'text' => 'Bilang 3rd Officer, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_third_officer_fil_t1', 'type' => 'open', 'text' => 'Sa papel na 3rd Officer, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_third_officer_fil_b1', 'type' => 'open', 'text' => 'Bilang 3rd Officer, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'deck_third_officer'],
            [
                'title' => 'Maritime / Role / 3rd Officer (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / 3rd Officer',
                    'department' => 'deck',
                    'language' => 'fil',
                    'role_scope' => 'third_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] deck_third_officer');
    }

    private function seedDeckBosun(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_bosun_fil_s1', 'type' => 'open', 'text' => 'Bilang Bosun, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_bosun_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Bosun, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_bosun_fil_b1', 'type' => 'open', 'text' => 'Bilang Bosun, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'deck_bosun'],
            [
                'title' => 'Maritime / Role / Bosun (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Bosun',
                    'department' => 'deck',
                    'language' => 'fil',
                    'role_scope' => 'bosun',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] deck_bosun');
    }

    private function seedDeckAbleSeaman(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_able_seaman_fil_s1', 'type' => 'open', 'text' => 'Bilang AB Seaman, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_able_seaman_fil_t1', 'type' => 'open', 'text' => 'Sa papel na AB Seaman, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_able_seaman_fil_b1', 'type' => 'open', 'text' => 'Bilang AB Seaman, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'deck_able_seaman'],
            [
                'title' => 'Maritime / Role / AB Seaman (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / AB Seaman',
                    'department' => 'deck',
                    'language' => 'fil',
                    'role_scope' => 'able_seaman',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] deck_able_seaman');
    }

    private function seedDeckOrdinarySeaman(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_ordinary_seaman_fil_s1', 'type' => 'open', 'text' => 'Bilang OS, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_ordinary_seaman_fil_t1', 'type' => 'open', 'text' => 'Sa papel na OS, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_ordinary_seaman_fil_b1', 'type' => 'open', 'text' => 'Bilang OS, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'deck_ordinary_seaman'],
            [
                'title' => 'Maritime / Role / OS (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / OS',
                    'department' => 'deck',
                    'language' => 'fil',
                    'role_scope' => 'ordinary_seaman',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] deck_ordinary_seaman');
    }

    /* ================================================================
     *  ENGINE DEPARTMENT
     * ================================================================ */

    private function engineQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'eng_fil_s1', 'type' => 'open', 'text' => 'Anong makina/fuel system ang pinagtatrabahuhan mo?'],
                ['id' => 'eng_fil_s2', 'type' => 'open', 'text' => 'Gumamit ka na ba ng PMS? Ilarawan ang isang trabaho mula simula hanggang dulo.'],
                ['id' => 'eng_fil_s3', 'type' => 'scale', 'text' => 'I-rate ang engine-room reporting discipline 1\u20135.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'eng_fil_t1', 'type' => 'open', 'text' => 'Bumaba ang LO pressure: tamang troubleshooting sequence?'],
                ['id' => 'eng_fil_t2', 'type' => 'open', 'text' => 'Mataas na jacket water temp: 3 dahilan + tsek?'],
                ['id' => 'eng_fil_t3', 'type' => 'open', 'text' => 'Purifier alarm/vibration: diagnose + safe shutdown?'],
                ['id' => 'eng_fil_t4', 'type' => 'open', 'text' => 'Hindi pwedeng i-compromise sa electrical isolation/LOTO?'],
            ],
            'safety' => [
                ['id' => 'eng_fil_sa1', 'type' => 'open', 'text' => 'FO leak/fire: unang mga priority?'],
                ['id' => 'eng_fil_sa2', 'type' => 'open', 'text' => 'Blackout: unang 2 minuto na aksyon?'],
                ['id' => 'eng_fil_sa3', 'type' => 'open', 'text' => 'Enclosed space entry checklist?'],
            ],
            'behaviour' => [
                ['id' => 'eng_fil_b1', 'type' => 'open', 'text' => 'Pinepressure na i-bypass ang safety: ano ang gagawin mo?'],
                ['id' => 'eng_fil_b2', 'type' => 'open', 'text' => 'Paano mo tinuturuan ang junior motorman?'],
            ],
        ];
    }

    private function engineSections(): array
    {
        $q = $this->engineQuestions();
        return [
            ['key' => 'screening',  'title' => 'Paunang Pagsusuri',        'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Teknikal / Makinarya',      'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Kaligtasan / Emergency',    'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Ugali / Disiplina',         'questions' => $q['behaviour']],
        ];
    }

    private function engineScoring(): array
    {
        return ['weights' => ['screening' => 0.2, 'technical' => 0.45, 'safety' => 0.25, 'behaviour' => 0.1]];
    }

    private function seedEngineGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'engine___generic__'],
            [
                'title' => 'Engine Department Generic Template (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Engine / Generic',
                    'department' => 'engine',
                    'language' => 'fil',
                    'role_scope' => '__generic__',
                    'sections' => $this->engineSections(),
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] engine___generic__');
    }

    private function seedEngineChiefEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_chief_engineer_fil_s1', 'type' => 'open', 'text' => 'Bilang Chief Engineer, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];

        $sections[1]['questions'][] = ['id' => 'rs_chief_engineer_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Chief Engineer, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[1]['questions'][] = ['id' => 'rs_ce_fil_t2', 'type' => 'open', 'text' => 'Pagkatapos ng blackout, ano ang recovery sequence mo? Aling mga sistema ang unang nag-o-on at bakit?'];
        $sections[1]['questions'][] = ['id' => 'rs_ce_fil_t3', 'type' => 'open', 'text' => 'Kung delayed ang PMS, paano mo ire-recover? Paano mo pina-prioritize at pinangungunahan ang team?'];

        $sections[2]['questions'][] = ['id' => 'rs_ce_fil_sa2', 'type' => 'open', 'text' => 'Kung nakita mo ang LOTO/PTW violation, ano ang gagawin mo? Paano mo ie-enforce ang stop-work authority?'];

        $sections[3]['questions'][] = ['id' => 'rs_chief_engineer_fil_b1', 'type' => 'open', 'text' => 'Bilang Chief Engineer, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'engine_chief_engineer'],
            [
                'title' => 'Maritime / Role / Chief Engineer (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Chief Engineer',
                    'department' => 'engine',
                    'language' => 'fil',
                    'role_scope' => 'chief_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] engine_chief_engineer');
    }

    private function seedEngineSecondEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_second_engineer_fil_s1', 'type' => 'open', 'text' => 'Bilang 2nd Engineer, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_second_engineer_fil_t1', 'type' => 'open', 'text' => 'Sa papel na 2nd Engineer, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_second_engineer_fil_b1', 'type' => 'open', 'text' => 'Bilang 2nd Engineer, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'engine_second_engineer'],
            [
                'title' => 'Maritime / Role / 2nd Engineer (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / 2nd Engineer',
                    'department' => 'engine',
                    'language' => 'fil',
                    'role_scope' => 'second_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] engine_second_engineer');
    }

    private function seedEngineThirdEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_third_engineer_fil_s1', 'type' => 'open', 'text' => 'Bilang 3rd Engineer, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_third_engineer_fil_t1', 'type' => 'open', 'text' => 'Sa papel na 3rd Engineer, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_third_engineer_fil_b1', 'type' => 'open', 'text' => 'Bilang 3rd Engineer, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'engine_third_engineer'],
            [
                'title' => 'Maritime / Role / 3rd Engineer (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / 3rd Engineer',
                    'department' => 'engine',
                    'language' => 'fil',
                    'role_scope' => 'third_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] engine_third_engineer');
    }

    private function seedEngineMotorman(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_motorman_fil_s1', 'type' => 'open', 'text' => 'Bilang Motorman, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_motorman_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Motorman, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_motorman_fil_b1', 'type' => 'open', 'text' => 'Bilang Motorman, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'engine_motorman'],
            [
                'title' => 'Maritime / Role / Motorman (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Motorman',
                    'department' => 'engine',
                    'language' => 'fil',
                    'role_scope' => 'motorman',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] engine_motorman');
    }

    private function seedEngineOiler(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_oiler_fil_s1', 'type' => 'open', 'text' => 'Bilang Oiler, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_oiler_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Oiler, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_oiler_fil_b1', 'type' => 'open', 'text' => 'Bilang Oiler, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'engine_oiler'],
            [
                'title' => 'Maritime / Role / Oiler (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Oiler',
                    'department' => 'engine',
                    'language' => 'fil',
                    'role_scope' => 'oiler',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] engine_oiler');
    }

    private function seedEngineElectrician(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_electrician_fil_s1', 'type' => 'open', 'text' => 'Bilang Electrician, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_electrician_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Electrician, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_electrician_fil_b1', 'type' => 'open', 'text' => 'Bilang Electrician, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'engine_electrician'],
            [
                'title' => 'Maritime / Role / Electrician (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Electrician',
                    'department' => 'engine',
                    'language' => 'fil',
                    'role_scope' => 'electrician',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] engine_electrician');
    }

    /* ================================================================
     *  GALLEY DEPARTMENT
     * ================================================================ */

    private function galleyQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'gal_fil_s1', 'type' => 'open', 'text' => 'Ano ang papel mo sa barko at ilang crew ang sini-serve?'],
                ['id' => 'gal_fil_s2', 'type' => 'open', 'text' => 'Paano mo ina-apply ang HACCP/temp logs/cross-contamination controls?'],
                ['id' => 'gal_fil_s3', 'type' => 'open', 'text' => 'Menu planning kapag limitado ang stock sa mahabang biyahe?'],
            ],
            'technical' => [
                ['id' => 'gal_fil_t1', 'type' => 'open', 'text' => 'Cold chain at hot holding temperature control?'],
                ['id' => 'gal_fil_t2', 'type' => 'open', 'text' => 'Allergen management at labeling approach?'],
                ['id' => 'gal_fil_t3', 'type' => 'open', 'text' => 'Unang aksyon kapag may suspected food poisoning?'],
            ],
            'safety' => [
                ['id' => 'gal_fil_sa1', 'type' => 'open', 'text' => 'Tamang tugon sa grease fire?'],
                ['id' => 'gal_fil_sa2', 'type' => 'open', 'text' => 'Pamamaraan at pag-uulat para sa sugat/injury?'],
            ],
            'behaviour' => [
                ['id' => 'gal_fil_b1', 'type' => 'open', 'text' => 'Pamamahala ng conflict sa multicultural crew?'],
                ['id' => 'gal_fil_b2', 'type' => 'open', 'text' => 'Pagpapanatili ng kalidad sa panahon ng port-call peaks?'],
            ],
        ];
    }

    private function galleySections(): array
    {
        $q = $this->galleyQuestions();
        return [
            ['key' => 'screening',  'title' => 'Paunang Pagsusuri',        'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Teknikal / Pagluluto',      'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Kaligtasan / Emergency',    'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Ugali / Disiplina',         'questions' => $q['behaviour']],
        ];
    }

    private function galleyScoring(): array
    {
        return ['weights' => ['screening' => 0.25, 'technical' => 0.35, 'safety' => 0.25, 'behaviour' => 0.15]];
    }

    private function seedGalleyGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'galley___generic__'],
            [
                'title' => 'Galley Department Generic Template (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Galley / Generic',
                    'department' => 'galley',
                    'language' => 'fil',
                    'role_scope' => '__generic__',
                    'sections' => $this->galleySections(),
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] galley___generic__');
    }

    private function seedGalleyCook(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_cook_fil_s1', 'type' => 'open', 'text' => 'Bilang Cook, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_cook_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Cook, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_cook_fil_b1', 'type' => 'open', 'text' => 'Bilang Cook, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'galley_cook'],
            [
                'title' => 'Maritime / Role / Cook (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Cook',
                    'department' => 'galley',
                    'language' => 'fil',
                    'role_scope' => 'cook',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] galley_cook');
    }

    private function seedGalleySteward(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_steward_fil_s1', 'type' => 'open', 'text' => 'Bilang Steward, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_steward_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Steward, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_steward_fil_b1', 'type' => 'open', 'text' => 'Bilang Steward, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'galley_steward'],
            [
                'title' => 'Maritime / Role / Steward (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Steward',
                    'department' => 'galley',
                    'language' => 'fil',
                    'role_scope' => 'steward',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] galley_steward');
    }

    private function seedGalleyMessman(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_messman_fil_s1', 'type' => 'open', 'text' => 'Bilang Messman, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_messman_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Messman, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_messman_fil_b1', 'type' => 'open', 'text' => 'Bilang Messman, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'galley_messman'],
            [
                'title' => 'Maritime / Role / Messman (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Messman',
                    'department' => 'galley',
                    'language' => 'fil',
                    'role_scope' => 'messman',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] galley_messman');
    }

    /* ================================================================
     *  CADET DEPARTMENT
     * ================================================================ */

    private function cadetQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'cad_fil_s1', 'type' => 'open', 'text' => 'Anong paaralan/programa? Sea-time goal?'],
                ['id' => 'cad_fil_s2', 'type' => 'open', 'text' => 'Mga natutunan/inaasahan sa sea training?'],
                ['id' => 'cad_fil_s3', 'type' => 'scale', 'text' => 'I-rate ang disiplina sa pang-araw-araw na routine 1\u20135.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'cad_fil_t1', 'type' => 'open', 'text' => 'Ipaliwanag ang ship hierarchy at reporting lines.'],
                ['id' => 'cad_fil_t2', 'type' => 'open', 'text' => 'Mga pangunahing responsibilidad sa watchkeeping?'],
                ['id' => 'cad_fil_t3', 'type' => 'open', 'text' => 'Bakit mahalaga ang PPE at toolbox talks?'],
            ],
            'safety' => [
                ['id' => 'cad_fil_sa1', 'type' => 'open', 'text' => 'Mga panganib sa enclosed space at bakit hindi pwedeng pumasok nang mag-isa?'],
                ['id' => 'cad_fil_sa2', 'type' => 'open', 'text' => 'Papel mo sa muster/headcount kapag may alarm?'],
            ],
            'behaviour' => [
                ['id' => 'cad_fil_b1', 'type' => 'open', 'text' => 'Paano mo tinatanggap ang feedback pagkatapos ng pagkakamali?'],
                ['id' => 'cad_fil_b2', 'type' => 'open', 'text' => 'Kung mahirap ang komunikasyon sa multinational crew, ano ang gagawin mo?'],
            ],
        ];
    }

    private function cadetSections(): array
    {
        $q = $this->cadetQuestions();
        return [
            ['key' => 'screening',  'title' => 'Paunang Pagsusuri',        'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Teknikal / Kaalaman',       'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Kaligtasan / Emergency',    'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Ugali / Disiplina',         'questions' => $q['behaviour']],
        ];
    }

    private function cadetScoring(): array
    {
        return ['weights' => ['screening' => 0.3, 'technical' => 0.3, 'safety' => 0.25, 'behaviour' => 0.15]];
    }

    private function seedCadetGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'cadet___generic__'],
            [
                'title' => 'Cadet Department Generic Template (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Cadet / Generic',
                    'department' => 'cadet',
                    'language' => 'fil',
                    'role_scope' => '__generic__',
                    'sections' => $this->cadetSections(),
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] cadet___generic__');
    }

    private function seedCadetDeckCadet(): void
    {
        $sections = $this->cadetSections();

        $sections[0]['questions'][] = ['id' => 'rs_deck_cadet_fil_s1', 'type' => 'open', 'text' => 'Bilang Deck Cadet, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_deck_cadet_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Deck Cadet, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_deck_cadet_fil_b1', 'type' => 'open', 'text' => 'Bilang Deck Cadet, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'cadet_deck_cadet'],
            [
                'title' => 'Maritime / Role / Deck Cadet (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Deck Cadet',
                    'department' => 'cadet',
                    'language' => 'fil',
                    'role_scope' => 'deck_cadet',
                    'sections' => $sections,
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] cadet_deck_cadet');
    }

    private function seedCadetEngineCadet(): void
    {
        $sections = $this->cadetSections();

        $sections[0]['questions'][] = ['id' => 'rs_engine_cadet_fil_s1', 'type' => 'open', 'text' => 'Bilang Engine Cadet, ano ang mga kritikal na pang-araw-araw na responsibilidad mo? Magbigay ng tunay na halimbawa mula sa huli mong barko.'];
        $sections[1]['questions'][] = ['id' => 'rs_engine_cadet_fil_t1', 'type' => 'open', 'text' => 'Sa papel na Engine Cadet, ano ang top 3 operational risks na madalas mong nakikita at mga hakbang sa kontrol?'];
        $sections[3]['questions'][] = ['id' => 'rs_engine_cadet_fil_b1', 'type' => 'open', 'text' => 'Bilang Engine Cadet, magkuwento ng pagkakataon na nag-intervene ka nang may napansin kang pagkakamali o panganib sa team.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'fil', 'position_code' => 'cadet_engine_cadet'],
            [
                'title' => 'Maritime / Role / Engine Cadet (Filipino)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Engine Cadet',
                    'department' => 'cadet',
                    'language' => 'fil',
                    'role_scope' => 'engine_cadet',
                    'sections' => $sections,
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [fil] cadet_engine_cadet');
    }
}
