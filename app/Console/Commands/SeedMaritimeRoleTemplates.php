<?php

namespace App\Console\Commands;

use App\Config\MaritimeRole;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedMaritimeRoleTemplates extends Command
{
    protected $signature = 'maritime:seed-role-templates
        {--tpl-version=v1 : Template version}
        {--dry-run : Only show what would be created/updated}
        {--only= : Comma-separated role codes to seed (e.g. deck_captain,engine_chief_engineer)}
    ';

    protected $description = 'Generate 19 maritime role-specific templates x 4 languages (76) by cloning dept generics and injecting role-specific questions.';

    public function handle(): int
    {
        $version = (string) $this->option('tpl-version');
        $dryRun  = (bool) $this->option('dry-run');
        $langs   = ['tr', 'en', 'az', 'ru'];
        $roles   = $this->roleMap();

        $onlyOpt = $this->option('only');
        if ($onlyOpt) {
            $only = array_filter(array_map('trim', explode(',', (string) $onlyOpt)));
            $roles = array_filter($roles, fn($r) => in_array($r['position_code'], $only, true));
        }

        $countCreate = 0;
        $countUpdate = 0;

        foreach ($roles as $r) {
            foreach ($langs as $lang) {
                $basePos = $r['base_generic_position_code'];
                $rolePos = $r['position_code'];

                $base = DB::table('interview_templates')
                    ->where('version', $version)
                    ->where('language', $lang)
                    ->where('position_code', $basePos)
                    ->where('is_active', 1)
                    ->first();

                if (!$base) {
                    $this->error("Missing base template: {$version}/{$lang}/{$basePos}");
                    return self::FAILURE;
                }

                $tpl = json_decode($base->template_json, true);
                if (!is_array($tpl) || empty($tpl['sections'])) {
                    $this->error("Base template JSON invalid: {$version}/{$lang}/{$basePos}");
                    return self::FAILURE;
                }

                // Patch metadata
                $tpl['role_scope'] = $r['role_key'];
                $tpl['name'] = $this->t($lang, 'name_prefix') . $this->roleTitle($lang, $r['role_key']);

                // Inject role-specific questions
                $inject = $this->roleQuestions($lang, $r['role_key'], $r['department']);
                $tpl = $this->injectQuestions($tpl, $inject);

                $json = $this->jsonEncodeStable($tpl);

                $exists = DB::table('interview_templates')
                    ->where('version', $version)
                    ->where('language', $lang)
                    ->where('position_code', $rolePos)
                    ->exists();

                if ($dryRun) {
                    $this->line(($exists ? 'UPDATE' : 'CREATE') . " {$version}/{$lang}/{$rolePos}");
                    continue;
                }

                if ($exists) {
                    DB::table('interview_templates')
                        ->where('version', $version)
                        ->where('language', $lang)
                        ->where('position_code', $rolePos)
                        ->update([
                            'template_json' => $json,
                            'is_active'     => 1,
                            'updated_at'    => Carbon::now(),
                        ]);
                    $countUpdate++;
                } else {
                    DB::table('interview_templates')->insert([
                        'id'            => (string) Str::uuid(),
                        'version'       => $version,
                        'language'      => $lang,
                        'position_code' => $rolePos,
                        'template_json' => $json,
                        'is_active'     => 1,
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now(),
                    ]);
                    $countCreate++;
                }
            }
        }

        $this->info("Done. created={$countCreate}, updated={$countUpdate}");
        return self::SUCCESS;
    }

    /**
     * 19 roles mapped to position_code (must match resolver's {dept}_{normalizedRole}).
     */
    private function roleMap(): array
    {
        return [
            // Deck (7)
            ['position_code' => 'deck_captain',          'role_key' => 'captain',          'department' => 'deck',   'base_generic_position_code' => 'deck___generic__'],
            ['position_code' => 'deck_chief_officer',    'role_key' => 'chief_officer',    'department' => 'deck',   'base_generic_position_code' => 'deck___generic__'],
            ['position_code' => 'deck_second_officer',   'role_key' => 'second_officer',   'department' => 'deck',   'base_generic_position_code' => 'deck___generic__'],
            ['position_code' => 'deck_third_officer',    'role_key' => 'third_officer',    'department' => 'deck',   'base_generic_position_code' => 'deck___generic__'],
            ['position_code' => 'deck_bosun',            'role_key' => 'bosun',            'department' => 'deck',   'base_generic_position_code' => 'deck___generic__'],
            ['position_code' => 'deck_able_seaman',      'role_key' => 'able_seaman',      'department' => 'deck',   'base_generic_position_code' => 'deck___generic__'],
            ['position_code' => 'deck_ordinary_seaman',  'role_key' => 'ordinary_seaman',  'department' => 'deck',   'base_generic_position_code' => 'deck___generic__'],

            // Engine (6)
            ['position_code' => 'engine_chief_engineer',  'role_key' => 'chief_engineer',  'department' => 'engine', 'base_generic_position_code' => 'engine___generic__'],
            ['position_code' => 'engine_second_engineer', 'role_key' => 'second_engineer', 'department' => 'engine', 'base_generic_position_code' => 'engine___generic__'],
            ['position_code' => 'engine_third_engineer',  'role_key' => 'third_engineer',  'department' => 'engine', 'base_generic_position_code' => 'engine___generic__'],
            ['position_code' => 'engine_motorman',        'role_key' => 'motorman',        'department' => 'engine', 'base_generic_position_code' => 'engine___generic__'],
            ['position_code' => 'engine_oiler',           'role_key' => 'oiler',           'department' => 'engine', 'base_generic_position_code' => 'engine___generic__'],
            ['position_code' => 'engine_electrician',     'role_key' => 'electrician',     'department' => 'engine', 'base_generic_position_code' => 'engine___generic__'],

            // Galley (3)
            ['position_code' => 'galley_cook',    'role_key' => 'cook',    'department' => 'galley', 'base_generic_position_code' => 'galley___generic__'],
            ['position_code' => 'galley_steward', 'role_key' => 'steward', 'department' => 'galley', 'base_generic_position_code' => 'galley___generic__'],
            ['position_code' => 'galley_messman', 'role_key' => 'messman', 'department' => 'galley', 'base_generic_position_code' => 'galley___generic__'],

            // Cadet (2) — note: normalized codes are deck_cadet / engine_cadet
            ['position_code' => 'cadet_deck_cadet',   'role_key' => 'deck_cadet',   'department' => 'cadet', 'base_generic_position_code' => 'cadet___generic__'],
            ['position_code' => 'cadet_engine_cadet', 'role_key' => 'engine_cadet', 'department' => 'cadet', 'base_generic_position_code' => 'cadet___generic__'],
        ];
    }

    private function roleQuestions(string $lang, string $roleKey, string $dept): array
    {
        $roleTitle = $this->roleTitle($lang, $roleKey);

        $common = [
            [
                'section'   => 'screening',
                'questions' => [
                    ['id' => "rs_{$roleKey}_{$lang}_s1", 'type' => 'open', 'text' => $this->t($lang, 'q_role_responsibilities', ['role' => $roleTitle])],
                ],
            ],
            [
                'section'   => 'technical',
                'questions' => [
                    ['id' => "rs_{$roleKey}_{$lang}_t1", 'type' => 'open', 'text' => $this->t($lang, 'q_top3_risks', ['role' => $roleTitle])],
                ],
            ],
            [
                'section'   => 'behaviour',
                'questions' => [
                    ['id' => "rs_{$roleKey}_{$lang}_b1", 'type' => 'open', 'text' => $this->t($lang, 'q_leadership_case', ['role' => $roleTitle])],
                ],
            ],
        ];

        if ($roleKey === 'captain') {
            $common[] = [
                'section'   => 'technical',
                'questions' => [
                    ['id' => "rs_captain_{$lang}_t2", 'type' => 'open', 'text' => $this->t($lang, 'q_captain_colreg_scenario')],
                    ['id' => "rs_captain_{$lang}_t3", 'type' => 'open', 'text' => $this->t($lang, 'q_captain_passage_plan')],
                ],
            ];
            $common[] = [
                'section'   => 'safety',
                'questions' => [
                    ['id' => "rs_captain_{$lang}_sa2", 'type' => 'open', 'text' => $this->t($lang, 'q_captain_crisis_command')],
                ],
            ];
        }

        if ($roleKey === 'chief_engineer') {
            $common[] = [
                'section'   => 'technical',
                'questions' => [
                    ['id' => "rs_ce_{$lang}_t2", 'type' => 'open', 'text' => $this->t($lang, 'q_ce_blackout_recovery')],
                    ['id' => "rs_ce_{$lang}_t3", 'type' => 'open', 'text' => $this->t($lang, 'q_ce_maintenance_leadership')],
                ],
            ];
            $common[] = [
                'section'   => 'safety',
                'questions' => [
                    ['id' => "rs_ce_{$lang}_sa2", 'type' => 'open', 'text' => $this->t($lang, 'q_ce_loto_ptw_enforcement')],
                ],
            ];
        }

        return $common;
    }

    private function injectQuestions(array $tpl, array $inject): array
    {
        $byKey = [];
        foreach ($tpl['sections'] as $i => $sec) {
            $byKey[$sec['key']] = $i;
        }

        foreach ($inject as $pack) {
            $key = $pack['section'];
            if (!isset($byKey[$key])) {
                continue;
            }

            $idx = $byKey[$key];
            $tpl['sections'][$idx]['questions'] = array_values(array_merge(
                $tpl['sections'][$idx]['questions'] ?? [],
                $pack['questions'] ?? []
            ));
        }

        return $tpl;
    }

    private function jsonEncodeStable(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function roleTitle(string $lang, string $roleKey): string
    {
        $map = [
            'tr' => [
                'captain' => 'Kaptan', 'chief_officer' => 'Baş Zabit', 'second_officer' => '2. Zabit', 'third_officer' => '3. Zabit',
                'bosun' => 'Lostromo', 'able_seaman' => 'Usta Gemici (AB)', 'ordinary_seaman' => 'Acemi Gemici (OS)',
                'chief_engineer' => 'Baş Mühendis', 'second_engineer' => '2. Mühendis', 'third_engineer' => '3. Mühendis',
                'motorman' => 'Motorman', 'oiler' => 'Oiler', 'electrician' => 'Elektrikçi',
                'cook' => 'Aşçı', 'steward' => 'Kamarot', 'messman' => 'Messman',
                'deck_cadet' => 'Güverte Stajyeri', 'engine_cadet' => 'Makine Stajyeri',
            ],
            'en' => [
                'captain' => 'Master (Captain)', 'chief_officer' => 'Chief Officer', 'second_officer' => '2nd Officer', 'third_officer' => '3rd Officer',
                'bosun' => 'Bosun', 'able_seaman' => 'AB Seaman', 'ordinary_seaman' => 'OS',
                'chief_engineer' => 'Chief Engineer', 'second_engineer' => '2nd Engineer', 'third_engineer' => '3rd Engineer',
                'motorman' => 'Motorman', 'oiler' => 'Oiler', 'electrician' => 'Electrician',
                'cook' => 'Cook', 'steward' => 'Steward', 'messman' => 'Messman',
                'deck_cadet' => 'Deck Cadet', 'engine_cadet' => 'Engine Cadet',
            ],
            'az' => [
                'captain' => 'Kapitan', 'chief_officer' => 'Baş zabit', 'second_officer' => '2-ci zabit', 'third_officer' => '3-cü zabit',
                'bosun' => 'Boatswain (Bosun)', 'able_seaman' => 'AB', 'ordinary_seaman' => 'OS',
                'chief_engineer' => 'Baş mühəndis', 'second_engineer' => '2-ci mühəndis', 'third_engineer' => '3-cü mühəndis',
                'motorman' => 'Motorman', 'oiler' => 'Oiler', 'electrician' => 'Elektrikçi',
                'cook' => 'Aşpaz', 'steward' => 'Steward', 'messman' => 'Messman',
                'deck_cadet' => 'Deck kadet', 'engine_cadet' => 'Engine kadet',
            ],
            'ru' => [
                'captain' => 'Капитан', 'chief_officer' => 'Старпом', 'second_officer' => '2-й помощник', 'third_officer' => '3-й помощник',
                'bosun' => 'Боцман', 'able_seaman' => 'Матрос AB', 'ordinary_seaman' => 'Матрос OS',
                'chief_engineer' => 'Старший механик', 'second_engineer' => '2-й механик', 'third_engineer' => '3-й механик',
                'motorman' => 'Моторист', 'oiler' => 'Масленщик', 'electrician' => 'Электрик',
                'cook' => 'Повар', 'steward' => 'Стюард', 'messman' => 'Мессман',
                'deck_cadet' => 'Кадет палубы', 'engine_cadet' => 'Кадет машины',
            ],
        ];

        return $map[$lang][$roleKey] ?? $roleKey;
    }

    private function t(string $lang, string $key, array $vars = []): string
    {
        $dict = [
            'tr' => [
                'name_prefix'                  => 'Maritime / Role / ',
                'q_role_responsibilities'       => '{role} olarak günlük kritik sorumlulukların neler? Son seferden örnek ver.',
                'q_top3_risks'                  => '{role} rolünde en sık gördüğün 3 operasyonel riski ve kontrol adımlarını anlat.',
                'q_leadership_case'             => '{role} olarak ekip içinde bir hata/risk gördüğünde nasıl müdahale ettin? Somut örnek.',
                'q_captain_colreg_scenario'     => 'COLREG zor bir senaryo anlat (crossing/visibility). Kararını hangi veriye göre verirsin?',
                'q_captain_passage_plan'        => 'Passage plan hazırlarken "no-go area / under-keel clearance / weather window" nasıl yönetilir?',
                'q_captain_crisis_command'       => 'Acil durum (yangın/MOB/blackout) anında kaptan olarak ilk 5 komutun ne olur ve neden?',
                'q_ce_blackout_recovery'         => 'Blackout sonrası toparlama sıralaman: hangi sistemleri önce ayağa kaldırırsın? Riskleri nasıl yönetirsin?',
                'q_ce_maintenance_leadership'    => 'PMS bakım planında gecikme varsa nasıl toparlarsın? Ekip/öncelik yönetimi nasıl olur?',
                'q_ce_loto_ptw_enforcement'      => 'LOTO/PTW ihlali gördüğünde ne yaparsın? "Stop work" kararını nasıl uygularsın?',
            ],
            'en' => [
                'name_prefix'                  => 'Maritime / Role / ',
                'q_role_responsibilities'       => 'As {role}, what are your critical day-to-day responsibilities? Give a real example from your last vessel.',
                'q_top3_risks'                  => 'In the {role} role, what are the top 3 operational risks you see most often and your control steps?',
                'q_leadership_case'             => 'As {role}, describe a time you intervened when you noticed a mistake or risk in the team.',
                'q_captain_colreg_scenario'     => 'Describe a difficult COLREG scenario (crossing/visibility). What data drives your decision?',
                'q_captain_passage_plan'        => 'In passage planning, how do you manage no-go areas, UKC, and weather windows?',
                'q_captain_crisis_command'       => 'In an emergency (fire/MOB/blackout), what are your first 5 commands as Master and why?',
                'q_ce_blackout_recovery'         => 'After a blackout, what is your recovery sequence? Which systems come up first and why?',
                'q_ce_maintenance_leadership'    => 'If PMS is behind schedule, how do you recover? How do you prioritize and lead the team?',
                'q_ce_loto_ptw_enforcement'      => 'If you see a LOTO/PTW violation, what do you do? How do you enforce stop-work authority?',
            ],
            'az' => [
                'name_prefix'                  => 'Maritime / Role / ',
                'q_role_responsibilities'       => '{role} kimi gündəlik kritik məsuliyyətlərin nədir? Son gəmidən real nümunə ver.',
                'q_top3_risks'                  => '{role} rolunda ən çox gördüyün 3 risk və nəzarət addımların nədir?',
                'q_leadership_case'             => '{role} kimi komandada risk/səhv gördükdə necə müdaxilə etmisən? Nümunə.',
                'q_captain_colreg_scenario'     => 'Çətin COLREG ssenarisi danış (crossing/visibility). Qərarını hansı məlumatla verirsən?',
                'q_captain_passage_plan'        => 'Passage plan-da no-go area, UKC və hava pəncərəsini necə idarə edirsən?',
                'q_captain_crisis_command'       => 'Fövqəladə vəziyyətdə (yanğın/MOB/blackout) kapitan kimi ilk 5 əmrin nədir və niyə?',
                'q_ce_blackout_recovery'         => 'Blackout-dan sonra bərpa ardıcıllığın necədir? Hansı sistemlər əvvəl qalxır?',
                'q_ce_maintenance_leadership'    => 'PMS gecikirsə necə toparlayırsan? Prioritet və komanda idarəsi?',
                'q_ce_loto_ptw_enforcement'      => 'LOTO/PTW pozuntusu görsən nə edirsən? Stop-work necə tətbiq olunur?',
            ],
            'ru' => [
                'name_prefix'                  => 'Maritime / Role / ',
                'q_role_responsibilities'       => 'Как {role}, каковы ваши ключевые ежедневные обязанности? Приведите пример с последнего судна.',
                'q_top3_risks'                  => 'Для роли {role}: назовите 3 самых частых операционных риска и ваши меры контроля.',
                'q_leadership_case'             => 'Как {role}, опишите случай, когда вы вмешались, заметив ошибку или риск в команде.',
                'q_captain_colreg_scenario'     => 'Опишите сложный сценарий COLREG (пересечение/видимость). На каких данных основано решение?',
                'q_captain_passage_plan'        => 'В passage plan как вы управляете no-go areas, UKC и погодными окнами?',
                'q_captain_crisis_command'       => 'В аварии (пожар/MOB/blackout) какие первые 5 команд капитана и почему?',
                'q_ce_blackout_recovery'         => 'После blackout: какова последовательность восстановления? Какие системы запускаете первыми?',
                'q_ce_maintenance_leadership'    => 'Если PMS отстает, как наверстываете? Как расставляете приоритеты и руководите?',
                'q_ce_loto_ptw_enforcement'      => 'При нарушении LOTO/PTW что делаете? Как применяете stop-work authority?',
            ],
        ];

        $s = $dict[$lang][$key] ?? $key;
        foreach ($vars as $k => $v) {
            $s = str_replace('{' . $k . '}', (string) $v, $s);
        }
        return $s;
    }
}
