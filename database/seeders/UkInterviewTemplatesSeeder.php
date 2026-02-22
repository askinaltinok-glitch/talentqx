<?php

namespace Database\Seeders;

use App\Models\InterviewTemplate;
use Illuminate\Database\Seeder;

class UkInterviewTemplatesSeeder extends Seeder
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

        $this->command->info('Ukrainian (uk) interview templates seeded: 23 templates.');
    }

    /* ================================================================
     *  GENERIC TEMPLATE
     * ================================================================ */

    private function seedGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => '__generic__'],
            [
                'title' => 'Generic Interview Template (Ukrainian)',
                'template_json' => json_encode([
                    'version' => 'v1',
                    'language' => 'uk',
                    'generic_template' => [
                        'questions' => [
                            [
                                'slot' => 1,
                                'competency' => 'communication',
                                'question' => 'Чи можете ви описати ситуацію, коли вам потрібно було пояснити складну тему простими словами? Що ви зробили і який був результат?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Не зміг пояснити, не враховував перспективу слухача, плутаний і безладний виклад',
                                    '2' => 'Передав основну інформацію, але без структури, не адаптувався до слухача',
                                    '3' => 'Зрозуміле пояснення, базова структура, відкритість до зворотного зв\'язку',
                                    '4' => 'Чітке та організоване пояснення, адаптоване до рівня слухача, готовність відповідати на запитання',
                                    '5' => 'Відмінна структура, емпатичне пояснення орієнтоване на слухача, ефективний цикл зворотного зв\'язку',
                                ],
                                'positive_signals' => [
                                    'Запитав про рівень знань слухача',
                                    'Використовував приклади та аналогії',
                                    'Переконався, що повідомлення зрозуміле',
                                    'Адаптував підхід на основі зворотного зв\'язку',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Уникнення відповідальності за комунікацію: "це не моя робота", "хай хтось інший розбирається"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 2,
                                'competency' => 'accountability',
                                'question' => 'Чи можете ви описати ситуацію на роботі, коли ви припустилися помилки або щось пішло не так? Як ви з цим впоралися?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Заперечив помилку або звинуватив інших, не взяв на себе відповідальність',
                                    '2' => 'Визнав помилку, але не вжив заходів, залишався пасивним',
                                    '3' => 'Визнав помилку та зробив основні кроки для виправлення',
                                    '4' => 'Повна відповідальність, проактивно знайшов рішення, повідомив зацікавлених осіб',
                                    '5' => 'Взяв відповідальність, розробив системне рішення, запропонував покращення процесу',
                                ],
                                'positive_signals' => [
                                    'Чітко визнав помилку',
                                    'Не звинувачував інших',
                                    'Навів конкретні дії з виправлення',
                                    'Поділився засвоєними уроками',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_BLAME',
                                        'trigger_guidance' => 'Постійно вказує на зовнішні причини: "команда мене не підтримала", "менеджер дав неправильні вказівки"',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Непослідовність у розповіді: спочатку звинувачує інших, потім визнає провину, суперечливі деталі',
                                        'severity' => 'high',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 3,
                                'competency' => 'teamwork',
                                'question' => 'Чи можете ви описати проект, де ви працювали з членами команди, які мали різні точки зору? Як ви керували різними перспективами?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Уникав командної роботи або нав\'язував свою думку, не шукав консенсусу',
                                    '2' => 'Пасивна участь, не висловлював думку або ігнорував конфлікт',
                                    '3' => 'Вислухав різні точки зору, зробив базові зусилля для досягнення згоди',
                                    '4' => 'Активно інтегрував різні перспективи, створив конструктивну атмосферу для обговорення',
                                    '5' => 'Створив синергію з розбіжностей, забезпечив участь кожного, спрямував до спільної мети',
                                ],
                                'positive_signals' => [
                                    'Активно запитував ідеї інших',
                                    'Відкритий до зміни власної думки',
                                    'Конструктивно керував конфліктом',
                                    'Ставив успіх команди вище особистого',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_EGO',
                                        'trigger_guidance' => 'Привласнив успіх команди: "насправді це була моя ідея", "вони б не впоралися без мене"',
                                        'severity' => 'medium',
                                    ],
                                    [
                                        'code' => 'RF_AGGRESSION',
                                        'trigger_guidance' => 'Образливі висловлювання щодо колег: образи, особисті нападки, агресивний тон',
                                        'severity' => 'critical',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 4,
                                'competency' => 'stress_resilience',
                                'question' => 'Чи можете ви описати період, коли ви працювали під інтенсивним тиском з кількома пріоритетами одночасно? Як ви впоралися?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Здався під тиском, не виконав завдання, паніка або уникнення',
                                    '2' => 'Виконав з труднощами, без стратегії управління стресом, реактивний підхід',
                                    '3' => 'Виконав завдання, базова пріоритизація, помірне управління стресом',
                                    '4' => 'Ефективна пріоритизація, зберігав спокій із систематичним підходом, підтримував якість',
                                    '5' => 'Видатна продуктивність під тиском, заспокоював інших, використав стрес як мотивацію',
                                ],
                                'positive_signals' => [
                                    'Навів конкретний метод пріоритизації',
                                    'Продемонстрував емоційний контроль',
                                    'Звертався по допомогу коли потрібно',
                                    'Виніс уроки на майбутнє',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_UNSTABLE',
                                        'trigger_guidance' => 'Неконтрольована реакція на стрес: "я зірвався", "я пішов", "я втратив контроль"',
                                        'severity' => 'medium',
                                    ],
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Систематичне уникнення стресових ситуацій: "це не мій тип роботи", "я не беру на себе таку відповідальність"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 5,
                                'competency' => 'adaptability',
                                'question' => 'Як ви адаптувалися, коли на робочому місці відбулася несподівана зміна? Наведіть приклад.',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Чинив опір змінам, не адаптувався, скаржився або блокував',
                                    '2' => 'Адаптувався з неохотою, зберігав негативне ставлення',
                                    '3' => 'Прийняв зміни, адаптувався за прийнятний час',
                                    '4' => 'Швидко прийняв зміни, ефективно працював у новій ситуації, допомагав іншим адаптуватися',
                                    '5' => 'Перетворив зміну на можливість, вніс проактивні пропозиції, очолив зміни',
                                ],
                                'positive_signals' => [
                                    'Намагався зрозуміти причину змін',
                                    'Швидко опанував нові навички',
                                    'Зберігав позитивне ставлення',
                                    'Допомагав іншим адаптуватися',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Втеча та відмова від змін: "я цього не роблю", "це не моя робота вчити нову систему"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 6,
                                'competency' => 'learning_agility',
                                'question' => 'Чи можете ви описати ситуацію, коли вам потрібно було швидко вивчити абсолютно нову тему або навичку? Як ви підійшли до цього?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Небажання вчитися, пасивне ставлення, залежність від інших',
                                    '2' => 'Вивчив на базовому рівні, але не поглиблював, робив лише необхідне',
                                    '3' => 'Активні зусилля з навчання, використав стандартні ресурси, вивчив за прийнятний час',
                                    '4' => 'Швидке та ефективне навчання, використав різноманітні ресурси, негайно застосував на практиці',
                                    '5' => 'Видатна швидкість навчання, покращив засвоєне, навчав інших',
                                ],
                                'positive_signals' => [
                                    'Використовував різні навчальні ресурси',
                                    'Не боявся ставити запитання',
                                    'Застосовував засвоєне на практиці',
                                    'Виявляв задоволення від навчання',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Уникнення відповідальності за навчання: "це не моя робота вчити нове", "хай хтось інший мене навчить"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 7,
                                'competency' => 'integrity',
                                'question' => 'Чи можете ви описати ситуацію, коли ви зіткнулися з етично складним рішенням? Як ви поводилися?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Продемонстрував неетичну поведінку або нормалізував порушення правил',
                                    '2' => 'Визнав етичну дилему, але не діяв, залишався пасивним',
                                    '3' => 'Вчинив правильно, але лише тому що це вимагалось, нечітка внутрішня мотивація',
                                    '4' => 'Дотримувався етичних принципів, прийняв правильне рішення навіть у складній ситуації, послідовна поведінка',
                                    '5' => 'Продемонстрував етичне лідерство, спрямовував інших до правильної поведінки, ризикував заради захисту правильного',
                                ],
                                'positive_signals' => [
                                    'Навів чітку та послідовну етичну позицію',
                                    'Вчинив правильно навіть ціною особистих витрат',
                                    'Підкреслив прозорість та чесність',
                                    'Протистояв неетичному тиску',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Непослідовність в етиці: правила що змінюються залежно від ситуації, "всі так роблять" нормалізація',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_BLAME',
                                        'trigger_guidance' => 'Звинувачує інших у порушеннях етики: "мене змусив менеджер", "така система"',
                                        'severity' => 'high',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 8,
                                'competency' => 'role_competence',
                                'question' => 'Чи можете ви описати досвід, коли ви виконували одну з основних вимог цієї посади? Який підхід ви використали і який був результат?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Відсутній відповідний досвід або дуже поверхневий, продемонстрував нерозуміння основних вимог',
                                    '2' => 'Обмежений досвід, знає базові концепції, але слабкий у застосуванні',
                                    '3' => 'Достатній досвід, правильне застосування стандартних процесів, прийнятний результат',
                                    '4' => 'Сильний досвід, якісні та вимірювані результати, покращив процес',
                                    '5' => 'Видатна продуктивність, розробив інноваційний підхід, здатний навчати інших',
                                ],
                                'positive_signals' => [
                                    'Поділився конкретними та вимірюваними результатами',
                                    'Правильно та логічно описав кроки процесу',
                                    'Пояснив як вирішував проблеми',
                                    'Навів приклади постійного вдосконалення',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Перебільшення компетенцій: не збігається при запиті деталей, розмиті відповіді при уточненні',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_EGO',
                                        'trigger_guidance' => 'Нереалістична впевненість: "я найкращий у цій роботі", "ніхто не робить це краще за мене"',
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

        $this->command->info('  [uk] __generic__');
    }

    /* ================================================================
     *  DECK DEPARTMENT
     * ================================================================ */

    private function deckQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'deck_uk_s1', 'type' => 'open', 'text' => 'На яких типах суден ви працювали? тоннаж/прапор/маршрут/тривалість.'],
                ['id' => 'deck_uk_s2', 'type' => 'open', 'text' => 'Опишіть ваші обов\'язки та систему вахт.'],
                ['id' => 'deck_uk_s3', 'type' => 'scale', 'text' => 'Оцініть Bridge English (SMCP) 1–5.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'deck_uk_t1', 'type' => 'open', 'text' => 'COLREG crossing: логіка рішення + сценарій.'],
                ['id' => 'deck_uk_t2', 'type' => 'open', 'text' => 'Що ви передаєте при зміні вахти?'],
                ['id' => 'deck_uk_t3', 'type' => 'open', 'text' => 'Які налаштування безпеки ECDIS ви перевіряєте?'],
                ['id' => 'deck_uk_t4', 'type' => 'open', 'text' => 'Топ-3 ризики швартування та заходи контролю?'],
            ],
            'safety' => [
                ['id' => 'deck_uk_sa1', 'type' => 'open', 'text' => 'MOB: дії в перші 60 секунд?'],
                ['id' => 'deck_uk_sa2', 'type' => 'open', 'text' => 'Пожежна тривога: роль команди містка/палуби?'],
                ['id' => 'deck_uk_sa3', 'type' => 'open', 'text' => 'Де PTW обов\'язковий? 3 приклади.'],
            ],
            'behaviour' => [
                ['id' => 'deck_uk_b1', 'type' => 'open', 'text' => 'Як ви ескалюєте питання безпеки до старших?'],
                ['id' => 'deck_uk_b2', 'type' => 'open', 'text' => 'Як ви керуєте втомою на практиці?'],
            ],
        ];
    }

    private function deckSections(): array
    {
        $q = $this->deckQuestions();
        return [
            ['key' => 'screening',  'title' => 'Попередній відбір',          'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Операційний / Технічний',    'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Безпека / Аварійний',        'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Поведінка / Дисципліна',     'questions' => $q['behaviour']],
        ];
    }

    private function deckScoring(): array
    {
        return ['weights' => ['screening' => 0.2, 'technical' => 0.4, 'safety' => 0.3, 'behaviour' => 0.1]];
    }

    private function seedDeckGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'deck___generic__'],
            [
                'title' => 'Deck Department Generic Template (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Deck / Generic',
                    'department' => 'deck',
                    'language' => 'uk',
                    'role_scope' => '__generic__',
                    'sections' => $this->deckSections(),
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] deck___generic__');
    }

    private function seedDeckCaptain(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_captain_uk_s1', 'type' => 'open', 'text' => 'Як Капітан, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];

        $sections[1]['questions'][] = ['id' => 'rs_captain_uk_t1', 'type' => 'open', 'text' => 'На посаді Капітан, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[1]['questions'][] = ['id' => 'rs_captain_uk_t2', 'type' => 'open', 'text' => 'Опишіть складний сценарій COLREG (crossing/visibility). Які дані визначають ваше рішення?'];
        $sections[1]['questions'][] = ['id' => 'rs_captain_uk_t3', 'type' => 'open', 'text' => 'При плануванні переходу, як ви керуєте no-go areas, UKC та weather windows?'];

        $sections[2]['questions'][] = ['id' => 'rs_captain_uk_sa2', 'type' => 'open', 'text' => 'У надзвичайній ситуації (пожежа/MOB/блекаут), які ваші перші 5 команд як Капітана і чому?'];

        $sections[3]['questions'][] = ['id' => 'rs_captain_uk_b1', 'type' => 'open', 'text' => 'Як Капітан, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'deck_captain'],
            [
                'title' => 'Maritime / Role / Капітан (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Капітан',
                    'department' => 'deck',
                    'language' => 'uk',
                    'role_scope' => 'captain',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] deck_captain');
    }

    private function seedDeckChiefOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_chief_officer_uk_s1', 'type' => 'open', 'text' => 'Як Старший помічник, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_chief_officer_uk_t1', 'type' => 'open', 'text' => 'На посаді Старший помічник, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_chief_officer_uk_b1', 'type' => 'open', 'text' => 'Як Старший помічник, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'deck_chief_officer'],
            [
                'title' => 'Maritime / Role / Старший помічник (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Старший помічник',
                    'department' => 'deck',
                    'language' => 'uk',
                    'role_scope' => 'chief_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] deck_chief_officer');
    }

    private function seedDeckSecondOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_second_officer_uk_s1', 'type' => 'open', 'text' => 'Як 2-й помічник, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_second_officer_uk_t1', 'type' => 'open', 'text' => 'На посаді 2-й помічник, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_second_officer_uk_b1', 'type' => 'open', 'text' => 'Як 2-й помічник, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'deck_second_officer'],
            [
                'title' => 'Maritime / Role / 2-й помічник (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / 2-й помічник',
                    'department' => 'deck',
                    'language' => 'uk',
                    'role_scope' => 'second_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] deck_second_officer');
    }

    private function seedDeckThirdOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_third_officer_uk_s1', 'type' => 'open', 'text' => 'Як 3-й помічник, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_third_officer_uk_t1', 'type' => 'open', 'text' => 'На посаді 3-й помічник, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_third_officer_uk_b1', 'type' => 'open', 'text' => 'Як 3-й помічник, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'deck_third_officer'],
            [
                'title' => 'Maritime / Role / 3-й помічник (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / 3-й помічник',
                    'department' => 'deck',
                    'language' => 'uk',
                    'role_scope' => 'third_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] deck_third_officer');
    }

    private function seedDeckBosun(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_bosun_uk_s1', 'type' => 'open', 'text' => 'Як Боцман, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_bosun_uk_t1', 'type' => 'open', 'text' => 'На посаді Боцман, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_bosun_uk_b1', 'type' => 'open', 'text' => 'Як Боцман, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'deck_bosun'],
            [
                'title' => 'Maritime / Role / Боцман (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Боцман',
                    'department' => 'deck',
                    'language' => 'uk',
                    'role_scope' => 'bosun',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] deck_bosun');
    }

    private function seedDeckAbleSeaman(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_able_seaman_uk_s1', 'type' => 'open', 'text' => 'Як Старший матрос (AB), які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_able_seaman_uk_t1', 'type' => 'open', 'text' => 'На посаді Старший матрос (AB), які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_able_seaman_uk_b1', 'type' => 'open', 'text' => 'Як Старший матрос (AB), розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'deck_able_seaman'],
            [
                'title' => 'Maritime / Role / Старший матрос (AB) (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Старший матрос (AB)',
                    'department' => 'deck',
                    'language' => 'uk',
                    'role_scope' => 'able_seaman',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] deck_able_seaman');
    }

    private function seedDeckOrdinarySeaman(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_ordinary_seaman_uk_s1', 'type' => 'open', 'text' => 'Як Матрос (OS), які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_ordinary_seaman_uk_t1', 'type' => 'open', 'text' => 'На посаді Матрос (OS), які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_ordinary_seaman_uk_b1', 'type' => 'open', 'text' => 'Як Матрос (OS), розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'deck_ordinary_seaman'],
            [
                'title' => 'Maritime / Role / Матрос (OS) (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Матрос (OS)',
                    'department' => 'deck',
                    'language' => 'uk',
                    'role_scope' => 'ordinary_seaman',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] deck_ordinary_seaman');
    }

    /* ================================================================
     *  ENGINE DEPARTMENT
     * ================================================================ */

    private function engineQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'eng_uk_s1', 'type' => 'open', 'text' => 'З якими двигунами/паливними системами ви працювали?'],
                ['id' => 'eng_uk_s2', 'type' => 'open', 'text' => 'Чи використовували ви PMS? Опишіть одну роботу від початку до кінця.'],
                ['id' => 'eng_uk_s3', 'type' => 'scale', 'text' => 'Оцініть дисципліну звітності в машинному відділенні 1–5.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'eng_uk_t1', 'type' => 'open', 'text' => 'Падіння тиску LO: безпечна послідовність діагностики?'],
                ['id' => 'eng_uk_t2', 'type' => 'open', 'text' => 'Висока температура jacket water: 3 причини + перевірки?'],
                ['id' => 'eng_uk_t3', 'type' => 'open', 'text' => 'Тривога/вібрація пурифікатора: діагностика + безпечна зупинка?'],
                ['id' => 'eng_uk_t4', 'type' => 'open', 'text' => 'Що не підлягає компромісу в електричній ізоляції/LOTO?'],
            ],
            'safety' => [
                ['id' => 'eng_uk_sa1', 'type' => 'open', 'text' => 'Витік/пожежа FO: перші пріоритети?'],
                ['id' => 'eng_uk_sa2', 'type' => 'open', 'text' => 'Блекаут: дії в перші 2 хвилини?'],
                ['id' => 'eng_uk_sa3', 'type' => 'open', 'text' => 'Чек-лист входу в закритий простір?'],
            ],
            'behaviour' => [
                ['id' => 'eng_uk_b1', 'type' => 'open', 'text' => 'Тиск обійти безпеку: що ви робите?'],
                ['id' => 'eng_uk_b2', 'type' => 'open', 'text' => 'Як ви навчаєте молодшого моториста?'],
            ],
        ];
    }

    private function engineSections(): array
    {
        $q = $this->engineQuestions();
        return [
            ['key' => 'screening',  'title' => 'Попередній відбір',          'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Технічний / Машинний',       'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Безпека / Аварійний',        'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Поведінка / Дисципліна',     'questions' => $q['behaviour']],
        ];
    }

    private function engineScoring(): array
    {
        return ['weights' => ['screening' => 0.2, 'technical' => 0.45, 'safety' => 0.25, 'behaviour' => 0.1]];
    }

    private function seedEngineGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'engine___generic__'],
            [
                'title' => 'Engine Department Generic Template (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Engine / Generic',
                    'department' => 'engine',
                    'language' => 'uk',
                    'role_scope' => '__generic__',
                    'sections' => $this->engineSections(),
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] engine___generic__');
    }

    private function seedEngineChiefEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_chief_engineer_uk_s1', 'type' => 'open', 'text' => 'Як Старший механік, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];

        $sections[1]['questions'][] = ['id' => 'rs_chief_engineer_uk_t1', 'type' => 'open', 'text' => 'На посаді Старший механік, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[1]['questions'][] = ['id' => 'rs_ce_uk_t2', 'type' => 'open', 'text' => 'Після блекауту, яка ваша послідовність відновлення? Які системи запускаються першими і чому?'];
        $sections[1]['questions'][] = ['id' => 'rs_ce_uk_t3', 'type' => 'open', 'text' => 'Якщо PMS відстає від графіка, як ви відновлюєте? Як ви пріоритизуєте та керуєте командою?'];

        $sections[2]['questions'][] = ['id' => 'rs_ce_uk_sa2', 'type' => 'open', 'text' => 'Якщо ви бачите порушення LOTO/PTW, що ви робите? Як ви впроваджуєте stop-work authority?'];

        $sections[3]['questions'][] = ['id' => 'rs_chief_engineer_uk_b1', 'type' => 'open', 'text' => 'Як Старший механік, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'engine_chief_engineer'],
            [
                'title' => 'Maritime / Role / Старший механік (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Старший механік',
                    'department' => 'engine',
                    'language' => 'uk',
                    'role_scope' => 'chief_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] engine_chief_engineer');
    }

    private function seedEngineSecondEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_second_engineer_uk_s1', 'type' => 'open', 'text' => 'Як 2-й механік, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_second_engineer_uk_t1', 'type' => 'open', 'text' => 'На посаді 2-й механік, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_second_engineer_uk_b1', 'type' => 'open', 'text' => 'Як 2-й механік, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'engine_second_engineer'],
            [
                'title' => 'Maritime / Role / 2-й механік (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / 2-й механік',
                    'department' => 'engine',
                    'language' => 'uk',
                    'role_scope' => 'second_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] engine_second_engineer');
    }

    private function seedEngineThirdEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_third_engineer_uk_s1', 'type' => 'open', 'text' => 'Як 3-й механік, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_third_engineer_uk_t1', 'type' => 'open', 'text' => 'На посаді 3-й механік, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_third_engineer_uk_b1', 'type' => 'open', 'text' => 'Як 3-й механік, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'engine_third_engineer'],
            [
                'title' => 'Maritime / Role / 3-й механік (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / 3-й механік',
                    'department' => 'engine',
                    'language' => 'uk',
                    'role_scope' => 'third_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] engine_third_engineer');
    }

    private function seedEngineMotorman(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_motorman_uk_s1', 'type' => 'open', 'text' => 'Як Моторист, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_motorman_uk_t1', 'type' => 'open', 'text' => 'На посаді Моторист, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_motorman_uk_b1', 'type' => 'open', 'text' => 'Як Моторист, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'engine_motorman'],
            [
                'title' => 'Maritime / Role / Моторист (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Моторист',
                    'department' => 'engine',
                    'language' => 'uk',
                    'role_scope' => 'motorman',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] engine_motorman');
    }

    private function seedEngineOiler(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_oiler_uk_s1', 'type' => 'open', 'text' => 'Як Мастильник, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_oiler_uk_t1', 'type' => 'open', 'text' => 'На посаді Мастильник, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_oiler_uk_b1', 'type' => 'open', 'text' => 'Як Мастильник, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'engine_oiler'],
            [
                'title' => 'Maritime / Role / Мастильник (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Мастильник',
                    'department' => 'engine',
                    'language' => 'uk',
                    'role_scope' => 'oiler',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] engine_oiler');
    }

    private function seedEngineElectrician(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_electrician_uk_s1', 'type' => 'open', 'text' => 'Як Електромеханік, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_electrician_uk_t1', 'type' => 'open', 'text' => 'На посаді Електромеханік, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_electrician_uk_b1', 'type' => 'open', 'text' => 'Як Електромеханік, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'engine_electrician'],
            [
                'title' => 'Maritime / Role / Електромеханік (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Електромеханік',
                    'department' => 'engine',
                    'language' => 'uk',
                    'role_scope' => 'electrician',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] engine_electrician');
    }

    /* ================================================================
     *  GALLEY DEPARTMENT
     * ================================================================ */

    private function galleyQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'gal_uk_s1', 'type' => 'open', 'text' => 'Ваша роль на борту та скільки екіпажу обслуговуєте?'],
                ['id' => 'gal_uk_s2', 'type' => 'open', 'text' => 'Як ви застосовуєте HACCP/температурні журнали/контроль перехресного забруднення?'],
                ['id' => 'gal_uk_s3', 'type' => 'open', 'text' => 'Планування меню з обмеженим запасом у довгих рейсах?'],
            ],
            'technical' => [
                ['id' => 'gal_uk_t1', 'type' => 'open', 'text' => 'Контроль температури холодного ланцюга та гарячого зберігання?'],
                ['id' => 'gal_uk_t2', 'type' => 'open', 'text' => 'Управління алергенами та підхід до маркування?'],
                ['id' => 'gal_uk_t3', 'type' => 'open', 'text' => 'Перші дії при підозрі на харчове отруєння?'],
            ],
            'safety' => [
                ['id' => 'gal_uk_sa1', 'type' => 'open', 'text' => 'Правильна реакція на пожежу жиру?'],
                ['id' => 'gal_uk_sa2', 'type' => 'open', 'text' => 'Процедура та звітність при порізах/травмах?'],
            ],
            'behaviour' => [
                ['id' => 'gal_uk_b1', 'type' => 'open', 'text' => 'Управління конфліктами в мультикультурному екіпажі?'],
                ['id' => 'gal_uk_b2', 'type' => 'open', 'text' => 'Підтримка якості під час піків port-call?'],
            ],
        ];
    }

    private function galleySections(): array
    {
        $q = $this->galleyQuestions();
        return [
            ['key' => 'screening',  'title' => 'Попередній відбір',          'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Технічний / Кухня',          'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Безпека / Аварійний',        'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Поведінка / Дисципліна',     'questions' => $q['behaviour']],
        ];
    }

    private function galleyScoring(): array
    {
        return ['weights' => ['screening' => 0.25, 'technical' => 0.35, 'safety' => 0.25, 'behaviour' => 0.15]];
    }

    private function seedGalleyGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'galley___generic__'],
            [
                'title' => 'Galley Department Generic Template (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Galley / Generic',
                    'department' => 'galley',
                    'language' => 'uk',
                    'role_scope' => '__generic__',
                    'sections' => $this->galleySections(),
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] galley___generic__');
    }

    private function seedGalleyCook(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_cook_uk_s1', 'type' => 'open', 'text' => 'Як Кок, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_cook_uk_t1', 'type' => 'open', 'text' => 'На посаді Кок, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_cook_uk_b1', 'type' => 'open', 'text' => 'Як Кок, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'galley_cook'],
            [
                'title' => 'Maritime / Role / Кок (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Кок',
                    'department' => 'galley',
                    'language' => 'uk',
                    'role_scope' => 'cook',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] galley_cook');
    }

    private function seedGalleySteward(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_steward_uk_s1', 'type' => 'open', 'text' => 'Як Стюард, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_steward_uk_t1', 'type' => 'open', 'text' => 'На посаді Стюард, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_steward_uk_b1', 'type' => 'open', 'text' => 'Як Стюард, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'galley_steward'],
            [
                'title' => 'Maritime / Role / Стюард (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Стюард',
                    'department' => 'galley',
                    'language' => 'uk',
                    'role_scope' => 'steward',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] galley_steward');
    }

    private function seedGalleyMessman(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_messman_uk_s1', 'type' => 'open', 'text' => 'Як Мессмен, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_messman_uk_t1', 'type' => 'open', 'text' => 'На посаді Мессмен, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_messman_uk_b1', 'type' => 'open', 'text' => 'Як Мессмен, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'galley_messman'],
            [
                'title' => 'Maritime / Role / Мессмен (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Мессмен',
                    'department' => 'galley',
                    'language' => 'uk',
                    'role_scope' => 'messman',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] galley_messman');
    }

    /* ================================================================
     *  CADET DEPARTMENT
     * ================================================================ */

    private function cadetQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'cad_uk_s1', 'type' => 'open', 'text' => 'Яка школа/програма? Мета sea-time?'],
                ['id' => 'cad_uk_s2', 'type' => 'open', 'text' => 'Що ви дізналися/очікуєте від морської практики?'],
                ['id' => 'cad_uk_s3', 'type' => 'scale', 'text' => 'Оцініть дисципліну щоденного розпорядку 1–5.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'cad_uk_t1', 'type' => 'open', 'text' => 'Поясніть ієрархію на судні та лінії звітності.'],
                ['id' => 'cad_uk_t2', 'type' => 'open', 'text' => 'Основні обов\'язки при несенні вахти?'],
                ['id' => 'cad_uk_t3', 'type' => 'open', 'text' => 'Чому важливі ЗІЗ та toolbox talks?'],
            ],
            'safety' => [
                ['id' => 'cad_uk_sa1', 'type' => 'open', 'text' => 'Небезпеки закритих просторів та чому не можна входити самостійно?'],
                ['id' => 'cad_uk_sa2', 'type' => 'open', 'text' => 'Ваша роль під час збору/перевірки при тривозі?'],
            ],
            'behaviour' => [
                ['id' => 'cad_uk_b1', 'type' => 'open', 'text' => 'Як ви приймаєте зворотний зв\'язок після помилки?'],
                ['id' => 'cad_uk_b2', 'type' => 'open', 'text' => 'Якщо комунікація складна в міжнародному екіпажі, що ви робите?'],
            ],
        ];
    }

    private function cadetSections(): array
    {
        $q = $this->cadetQuestions();
        return [
            ['key' => 'screening',  'title' => 'Попередній відбір',          'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Технічний / Знання',         'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Безпека / Аварійний',        'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Поведінка / Дисципліна',     'questions' => $q['behaviour']],
        ];
    }

    private function cadetScoring(): array
    {
        return ['weights' => ['screening' => 0.3, 'technical' => 0.3, 'safety' => 0.25, 'behaviour' => 0.15]];
    }

    private function seedCadetGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'cadet___generic__'],
            [
                'title' => 'Cadet Department Generic Template (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Cadet / Generic',
                    'department' => 'cadet',
                    'language' => 'uk',
                    'role_scope' => '__generic__',
                    'sections' => $this->cadetSections(),
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] cadet___generic__');
    }

    private function seedCadetDeckCadet(): void
    {
        $sections = $this->cadetSections();

        $sections[0]['questions'][] = ['id' => 'rs_deck_cadet_uk_s1', 'type' => 'open', 'text' => 'Як Курсант палуби, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_deck_cadet_uk_t1', 'type' => 'open', 'text' => 'На посаді Курсант палуби, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_deck_cadet_uk_b1', 'type' => 'open', 'text' => 'Як Курсант палуби, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'cadet_deck_cadet'],
            [
                'title' => 'Maritime / Role / Курсант палуби (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Курсант палуби',
                    'department' => 'cadet',
                    'language' => 'uk',
                    'role_scope' => 'deck_cadet',
                    'sections' => $sections,
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] cadet_deck_cadet');
    }

    private function seedCadetEngineCadet(): void
    {
        $sections = $this->cadetSections();

        $sections[0]['questions'][] = ['id' => 'rs_engine_cadet_uk_s1', 'type' => 'open', 'text' => 'Як Курсант машини, які ваші критичні щоденні обов\'язки? Наведіть реальний приклад з останнього судна.'];
        $sections[1]['questions'][] = ['id' => 'rs_engine_cadet_uk_t1', 'type' => 'open', 'text' => 'На посаді Курсант машини, які 3 найчастіші операційні ризики ви бачите та ваші кроки контролю?'];
        $sections[3]['questions'][] = ['id' => 'rs_engine_cadet_uk_b1', 'type' => 'open', 'text' => 'Як Курсант машини, розкажіть про випадок, коли ви втрутилися, помітивши помилку або ризик у команді.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'uk', 'position_code' => 'cadet_engine_cadet'],
            [
                'title' => 'Maritime / Role / Курсант машини (Ukrainian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Курсант машини',
                    'department' => 'cadet',
                    'language' => 'uk',
                    'role_scope' => 'engine_cadet',
                    'sections' => $sections,
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [uk] cadet_engine_cadet');
    }
}
