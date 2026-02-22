<?php

namespace Database\Seeders;

use App\Models\InterviewTemplate;
use Illuminate\Database\Seeder;

class BehavioralInterviewTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $locales = $this->getLocaleTemplates();

        foreach ($locales as $lang => $template) {
            InterviewTemplate::updateOrCreate(
                [
                    'version' => 'v1',
                    'language' => $lang,
                    'position_code' => '__behavioral__',
                ],
                [
                    'type' => 'behavioral',
                    'title' => "Behavioral Interview ({$lang}) v1",
                    'template_json' => json_encode($template, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    'is_active' => true,
                ]
            );

            echo "  Behavioral template seeded for [{$lang}]\n";
        }

        echo "\nBehavioral interview templates seeded for " . count($locales) . " locales.\n";
    }

    private function getLocaleTemplates(): array
    {
        $scoring = [
            'scale' => ['min' => 1, 'max' => 5],
            'category_weights' => [
                'discipline_procedure' => 0.30,
                'stress_crisis' => 0.25,
                'team_compatibility' => 0.25,
                'leadership_responsibility' => 0.20,
            ],
        ];

        return [
            'en' => $this->buildTemplate('en', $this->enQuestions(), $scoring),
            'tr' => $this->buildTemplate('tr', $this->trQuestions(), $scoring),
            'ru' => $this->buildTemplate('ru', $this->ruQuestions(), $scoring),
            'az' => $this->buildTemplate('az', $this->azQuestions(), $scoring),
            'fil' => $this->buildTemplate('fil', $this->filQuestions(), $scoring),
            'id' => $this->buildTemplate('id', $this->idQuestions(), $scoring),
            'uk' => $this->buildTemplate('uk', $this->ukQuestions(), $scoring),
        ];
    }

    private function buildTemplate(string $lang, array $categories, array $scoring): array
    {
        return [
            'type' => 'behavioral',
            'version' => 'v1',
            'language' => $lang,
            'categories' => $categories,
            'scoring' => $scoring,
        ];
    }

    // ─── EN ─────────────────────────────────────────────────────────────

    private function enQuestions(): array
    {
        return [
            [
                'key' => 'discipline_procedure',
                'title' => 'Discipline & Procedure',
                'dimensions' => ['DISCIPLINE_COMPLIANCE', 'RELIABILITY_STABILITY'],
                'questions' => [
                    [
                        'id' => 'beh_en_dp_01',
                        'slot' => 1,
                        'text' => 'Describe a time when you had to follow a safety procedure you disagreed with. What did you do?',
                        'type' => 'open_text',
                        'scoring_hints' => ['compliance', 'protocol adherence', 'reasoning'],
                    ],
                    [
                        'id' => 'beh_en_dp_02',
                        'slot' => 2,
                        'text' => 'Tell me about a situation where you noticed a colleague not following the proper protocol. How did you handle it?',
                        'type' => 'open_text',
                        'scoring_hints' => ['reporting', 'intervention', 'diplomatic approach'],
                    ],
                    [
                        'id' => 'beh_en_dp_03',
                        'slot' => 3,
                        'text' => 'Give an example of how you ensured that all required checklists and documentation were completed before a critical operation.',
                        'type' => 'open_text',
                        'scoring_hints' => ['documentation', 'systematic approach', 'attention to detail'],
                    ],
                ],
            ],
            [
                'key' => 'stress_crisis',
                'title' => 'Stress & Crisis Management',
                'dimensions' => ['STRESS_CONTROL'],
                'questions' => [
                    [
                        'id' => 'beh_en_sc_01',
                        'slot' => 4,
                        'text' => 'Describe the most stressful emergency situation you have faced on board. How did you respond?',
                        'type' => 'open_text',
                        'scoring_hints' => ['calm response', 'systematic approach', 'prioritization'],
                    ],
                    [
                        'id' => 'beh_en_sc_02',
                        'slot' => 5,
                        'text' => 'Tell me about a time when you had to make a critical decision under severe time pressure. What was the outcome?',
                        'type' => 'open_text',
                        'scoring_hints' => ['decision-making', 'risk assessment', 'composure'],
                    ],
                    [
                        'id' => 'beh_en_sc_03',
                        'slot' => 6,
                        'text' => 'How do you manage your own stress and fatigue during long voyages or extended watch periods?',
                        'type' => 'open_text',
                        'scoring_hints' => ['self-awareness', 'coping strategies', 'routine'],
                    ],
                ],
            ],
            [
                'key' => 'team_compatibility',
                'title' => 'Team Compatibility',
                'dimensions' => ['TEAM_COOPERATION', 'COMM_CLARITY', 'CONFLICT_RISK'],
                'questions' => [
                    [
                        'id' => 'beh_en_tc_01',
                        'slot' => 7,
                        'text' => 'Describe a time when you had a disagreement with a superior officer. How did you resolve it?',
                        'type' => 'open_text',
                        'scoring_hints' => ['conflict resolution', 'respect for hierarchy', 'communication'],
                    ],
                    [
                        'id' => 'beh_en_tc_02',
                        'slot' => 8,
                        'text' => 'Tell me about a situation where you worked with a crew member who spoke a different language. How did you ensure clear communication?',
                        'type' => 'open_text',
                        'scoring_hints' => ['adaptability', 'communication clarity', 'patience'],
                    ],
                    [
                        'id' => 'beh_en_tc_03',
                        'slot' => 9,
                        'text' => 'Give an example of how you contributed to building a positive atmosphere among the crew during a difficult period.',
                        'type' => 'open_text',
                        'scoring_hints' => ['team building', 'morale', 'empathy'],
                    ],
                ],
            ],
            [
                'key' => 'leadership_responsibility',
                'title' => 'Leadership & Responsibility',
                'dimensions' => ['LEARNING_GROWTH', 'RELIABILITY_STABILITY'],
                'questions' => [
                    [
                        'id' => 'beh_en_lr_01',
                        'slot' => 10,
                        'text' => 'Describe a situation where you took responsibility for a mistake. What did you learn from it?',
                        'type' => 'open_text',
                        'scoring_hints' => ['accountability', 'self-reflection', 'growth'],
                    ],
                    [
                        'id' => 'beh_en_lr_02',
                        'slot' => 11,
                        'text' => 'Tell me about a time when you had to train or mentor a junior crew member. How did you approach it?',
                        'type' => 'open_text',
                        'scoring_hints' => ['mentoring', 'patience', 'knowledge transfer'],
                    ],
                    [
                        'id' => 'beh_en_lr_03',
                        'slot' => 12,
                        'text' => 'What is the most important professional lesson you have learned at sea, and how has it changed the way you work?',
                        'type' => 'open_text',
                        'scoring_hints' => ['continuous learning', 'professional development', 'self-improvement'],
                    ],
                ],
            ],
        ];
    }

    // ─── TR ─────────────────────────────────────────────────────────────

    private function trQuestions(): array
    {
        return [
            [
                'key' => 'discipline_procedure',
                'title' => 'Disiplin ve Prosedür',
                'dimensions' => ['DISCIPLINE_COMPLIANCE', 'RELIABILITY_STABILITY'],
                'questions' => [
                    [
                        'id' => 'beh_tr_dp_01',
                        'slot' => 1,
                        'text' => 'Katılmadığınız bir güvenlik prosedürüne uymak zorunda kaldığınız bir durumu anlatın. Ne yaptınız?',
                        'type' => 'open_text',
                        'scoring_hints' => ['uyum', 'protokol bağlılığı', 'muhakeme'],
                    ],
                    [
                        'id' => 'beh_tr_dp_02',
                        'slot' => 2,
                        'text' => 'Bir meslektaşınızın uygun prosedürü takip etmediğini fark ettiğiniz bir durumu anlatın. Nasıl müdahale ettiniz?',
                        'type' => 'open_text',
                        'scoring_hints' => ['raporlama', 'müdahale', 'diplomatik yaklaşım'],
                    ],
                    [
                        'id' => 'beh_tr_dp_03',
                        'slot' => 3,
                        'text' => 'Kritik bir operasyon öncesinde gerekli tüm kontrol listelerinin ve belgelerin tamamlandığından nasıl emin olduğunuza dair bir örnek verin.',
                        'type' => 'open_text',
                        'scoring_hints' => ['dokümantasyon', 'sistematik yaklaşım', 'detaylara dikkat'],
                    ],
                ],
            ],
            [
                'key' => 'stress_crisis',
                'title' => 'Stres ve Kriz Yönetimi',
                'dimensions' => ['STRESS_CONTROL'],
                'questions' => [
                    [
                        'id' => 'beh_tr_sc_01',
                        'slot' => 4,
                        'text' => 'Gemide yaşadığınız en stresli acil durumu anlatın. Nasıl tepki verdiniz?',
                        'type' => 'open_text',
                        'scoring_hints' => ['sakin tepki', 'sistematik yaklaşım', 'önceliklendirme'],
                    ],
                    [
                        'id' => 'beh_tr_sc_02',
                        'slot' => 5,
                        'text' => 'Ciddi bir zaman baskısı altında kritik bir karar vermek zorunda kaldığınız bir durumu anlatın. Sonuç ne oldu?',
                        'type' => 'open_text',
                        'scoring_hints' => ['karar verme', 'risk değerlendirmesi', 'soğukkanlılık'],
                    ],
                    [
                        'id' => 'beh_tr_sc_03',
                        'slot' => 6,
                        'text' => 'Uzun seferler veya uzun vardiya dönemlerinde kendi stresinizi ve yorgunluğunuzu nasıl yönetirsiniz?',
                        'type' => 'open_text',
                        'scoring_hints' => ['öz farkındalık', 'baş etme stratejileri', 'rutin'],
                    ],
                ],
            ],
            [
                'key' => 'team_compatibility',
                'title' => 'Ekip Uyumu',
                'dimensions' => ['TEAM_COOPERATION', 'COMM_CLARITY', 'CONFLICT_RISK'],
                'questions' => [
                    [
                        'id' => 'beh_tr_tc_01',
                        'slot' => 7,
                        'text' => 'Bir üst amirinizle anlaşmazlık yaşadığınız bir durumu anlatın. Nasıl çözdünüz?',
                        'type' => 'open_text',
                        'scoring_hints' => ['çatışma çözümü', 'hiyerarşiye saygı', 'iletişim'],
                    ],
                    [
                        'id' => 'beh_tr_tc_02',
                        'slot' => 8,
                        'text' => 'Farklı bir dil konuşan bir mürettebat üyesiyle çalıştığınız bir durumu anlatın. Açık iletişimi nasıl sağladınız?',
                        'type' => 'open_text',
                        'scoring_hints' => ['uyum sağlama', 'iletişim netliği', 'sabır'],
                    ],
                    [
                        'id' => 'beh_tr_tc_03',
                        'slot' => 9,
                        'text' => 'Zor bir dönemde mürettebat arasında olumlu bir atmosfer oluşturmaya nasıl katkıda bulunduğunuza dair bir örnek verin.',
                        'type' => 'open_text',
                        'scoring_hints' => ['ekip kurma', 'moral', 'empati'],
                    ],
                ],
            ],
            [
                'key' => 'leadership_responsibility',
                'title' => 'Liderlik ve Sorumluluk',
                'dimensions' => ['LEARNING_GROWTH', 'RELIABILITY_STABILITY'],
                'questions' => [
                    [
                        'id' => 'beh_tr_lr_01',
                        'slot' => 10,
                        'text' => 'Bir hata için sorumluluk aldığınız bir durumu anlatın. Bundan ne öğrendiniz?',
                        'type' => 'open_text',
                        'scoring_hints' => ['hesap verebilirlik', 'öz değerlendirme', 'gelişim'],
                    ],
                    [
                        'id' => 'beh_tr_lr_02',
                        'slot' => 11,
                        'text' => 'Bir alt rütbeli mürettebat üyesini eğitmek veya yönlendirmek zorunda kaldığınız bir durumu anlatın. Nasıl yaklaştınız?',
                        'type' => 'open_text',
                        'scoring_hints' => ['mentorluk', 'sabır', 'bilgi aktarımı'],
                    ],
                    [
                        'id' => 'beh_tr_lr_03',
                        'slot' => 12,
                        'text' => 'Denizde öğrendiğiniz en önemli mesleki ders nedir ve çalışma şeklinizi nasıl değiştirdi?',
                        'type' => 'open_text',
                        'scoring_hints' => ['sürekli öğrenme', 'mesleki gelişim', 'kendini geliştirme'],
                    ],
                ],
            ],
        ];
    }

    // ─── RU ─────────────────────────────────────────────────────────────

    private function ruQuestions(): array
    {
        return [
            [
                'key' => 'discipline_procedure',
                'title' => 'Дисциплина и процедуры',
                'dimensions' => ['DISCIPLINE_COMPLIANCE', 'RELIABILITY_STABILITY'],
                'questions' => [
                    [
                        'id' => 'beh_ru_dp_01',
                        'slot' => 1,
                        'text' => 'Опишите ситуацию, когда вам пришлось следовать процедуре безопасности, с которой вы не согласны. Что вы сделали?',
                        'type' => 'open_text',
                        'scoring_hints' => ['соответствие', 'соблюдение протокола', 'обоснование'],
                    ],
                    [
                        'id' => 'beh_ru_dp_02',
                        'slot' => 2,
                        'text' => 'Расскажите о ситуации, когда вы заметили, что коллега не соблюдает надлежащий протокол. Как вы поступили?',
                        'type' => 'open_text',
                        'scoring_hints' => ['докладывание', 'вмешательство', 'дипломатический подход'],
                    ],
                    [
                        'id' => 'beh_ru_dp_03',
                        'slot' => 3,
                        'text' => 'Приведите пример того, как вы обеспечили выполнение всех необходимых чек-листов и документации перед критической операцией.',
                        'type' => 'open_text',
                        'scoring_hints' => ['документация', 'системный подход', 'внимание к деталям'],
                    ],
                ],
            ],
            [
                'key' => 'stress_crisis',
                'title' => 'Стресс и кризисное управление',
                'dimensions' => ['STRESS_CONTROL'],
                'questions' => [
                    [
                        'id' => 'beh_ru_sc_01',
                        'slot' => 4,
                        'text' => 'Опишите самую стрессовую аварийную ситуацию, с которой вы столкнулись на борту. Как вы отреагировали?',
                        'type' => 'open_text',
                        'scoring_hints' => ['спокойная реакция', 'системный подход', 'приоритизация'],
                    ],
                    [
                        'id' => 'beh_ru_sc_02',
                        'slot' => 5,
                        'text' => 'Расскажите о случае, когда вам пришлось принять критическое решение в условиях сильного дефицита времени. Каков был результат?',
                        'type' => 'open_text',
                        'scoring_hints' => ['принятие решений', 'оценка рисков', 'самообладание'],
                    ],
                    [
                        'id' => 'beh_ru_sc_03',
                        'slot' => 6,
                        'text' => 'Как вы справляетесь со стрессом и усталостью во время длительных рейсов или продолжительных вахт?',
                        'type' => 'open_text',
                        'scoring_hints' => ['самосознание', 'стратегии совладания', 'режим'],
                    ],
                ],
            ],
            [
                'key' => 'team_compatibility',
                'title' => 'Совместимость с командой',
                'dimensions' => ['TEAM_COOPERATION', 'COMM_CLARITY', 'CONFLICT_RISK'],
                'questions' => [
                    [
                        'id' => 'beh_ru_tc_01',
                        'slot' => 7,
                        'text' => 'Опишите ситуацию, когда у вас были разногласия с вышестоящим офицером. Как вы это разрешили?',
                        'type' => 'open_text',
                        'scoring_hints' => ['разрешение конфликтов', 'уважение к иерархии', 'коммуникация'],
                    ],
                    [
                        'id' => 'beh_ru_tc_02',
                        'slot' => 8,
                        'text' => 'Расскажите о ситуации, когда вы работали с членом экипажа, говорящим на другом языке. Как вы обеспечили чёткое общение?',
                        'type' => 'open_text',
                        'scoring_hints' => ['адаптивность', 'ясность коммуникации', 'терпение'],
                    ],
                    [
                        'id' => 'beh_ru_tc_03',
                        'slot' => 9,
                        'text' => 'Приведите пример того, как вы способствовали созданию позитивной атмосферы среди экипажа в трудный период.',
                        'type' => 'open_text',
                        'scoring_hints' => ['командообразование', 'моральный дух', 'эмпатия'],
                    ],
                ],
            ],
            [
                'key' => 'leadership_responsibility',
                'title' => 'Лидерство и ответственность',
                'dimensions' => ['LEARNING_GROWTH', 'RELIABILITY_STABILITY'],
                'questions' => [
                    [
                        'id' => 'beh_ru_lr_01',
                        'slot' => 10,
                        'text' => 'Опишите ситуацию, когда вы взяли на себя ответственность за ошибку. Чему вы научились?',
                        'type' => 'open_text',
                        'scoring_hints' => ['ответственность', 'самоанализ', 'рост'],
                    ],
                    [
                        'id' => 'beh_ru_lr_02',
                        'slot' => 11,
                        'text' => 'Расскажите о случае, когда вам пришлось обучать или наставлять младшего члена экипажа. Как вы подошли к этому?',
                        'type' => 'open_text',
                        'scoring_hints' => ['наставничество', 'терпение', 'передача знаний'],
                    ],
                    [
                        'id' => 'beh_ru_lr_03',
                        'slot' => 12,
                        'text' => 'Какой самый важный профессиональный урок вы извлекли в море и как он изменил вашу работу?',
                        'type' => 'open_text',
                        'scoring_hints' => ['непрерывное обучение', 'профессиональное развитие', 'самосовершенствование'],
                    ],
                ],
            ],
        ];
    }

    // ─── AZ ─────────────────────────────────────────────────────────────

    private function azQuestions(): array
    {
        return [
            [
                'key' => 'discipline_procedure',
                'title' => 'İntizam və Prosedur',
                'dimensions' => ['DISCIPLINE_COMPLIANCE', 'RELIABILITY_STABILITY'],
                'questions' => [
                    ['id' => 'beh_az_dp_01', 'slot' => 1, 'text' => 'Razılaşmadığınız bir təhlükəsizlik proseduruna əməl etməli olduğunuz bir vəziyyəti təsvir edin. Nə etdiniz?', 'type' => 'open_text', 'scoring_hints' => ['uyğunluq', 'protokola riayət', 'mühakimə']],
                    ['id' => 'beh_az_dp_02', 'slot' => 2, 'text' => 'Həmkarınızın lazımi protokola əməl etmədiyini gördüyünüz bir vəziyyəti izah edin. Bunu necə həll etdiniz?', 'type' => 'open_text', 'scoring_hints' => ['hesabat', 'müdaxilə', 'diplomatik yanaşma']],
                    ['id' => 'beh_az_dp_03', 'slot' => 3, 'text' => 'Kritik bir əməliyyatdan əvvəl bütün lazımi yoxlama siyahılarının və sənədlərin tamamlandığını necə təmin etdiyinizə dair bir nümunə verin.', 'type' => 'open_text', 'scoring_hints' => ['sənədləşdirmə', 'sistemli yanaşma', 'detallara diqqət']],
                ],
            ],
            [
                'key' => 'stress_crisis',
                'title' => 'Stress və Böhran İdarəetməsi',
                'dimensions' => ['STRESS_CONTROL'],
                'questions' => [
                    ['id' => 'beh_az_sc_01', 'slot' => 4, 'text' => 'Gəmidə üzləşdiyiniz ən stresli təcili vəziyyəti təsvir edin. Necə reaksiya verdiniz?', 'type' => 'open_text', 'scoring_hints' => ['sakit reaksiya', 'sistemli yanaşma', 'prioritetləşdirmə']],
                    ['id' => 'beh_az_sc_02', 'slot' => 5, 'text' => 'Ciddi vaxt təzyiqi altında kritik qərar verməli olduğunuz bir vəziyyəti danışın. Nəticə necə oldu?', 'type' => 'open_text', 'scoring_hints' => ['qərar qəbulu', 'risk qiymətləndirməsi', 'soyuqqanlılıq']],
                    ['id' => 'beh_az_sc_03', 'slot' => 6, 'text' => 'Uzun səfərlər və ya uzadılmış növbə müddətlərində öz stresinizi və yorğunluğunuzu necə idarə edirsiniz?', 'type' => 'open_text', 'scoring_hints' => ['özünüdərk', 'baş etmə strategiyaları', 'rutin']],
                ],
            ],
            [
                'key' => 'team_compatibility',
                'title' => 'Komanda Uyğunluğu',
                'dimensions' => ['TEAM_COOPERATION', 'COMM_CLARITY', 'CONFLICT_RISK'],
                'questions' => [
                    ['id' => 'beh_az_tc_01', 'slot' => 7, 'text' => 'Yuxarı rütbəli zabitlə fikir ayrılığınız olduğu bir vəziyyəti təsvir edin. Necə həll etdiniz?', 'type' => 'open_text', 'scoring_hints' => ['münaqişə həlli', 'iyerarxiyaya hörmət', 'ünsiyyət']],
                    ['id' => 'beh_az_tc_02', 'slot' => 8, 'text' => 'Fərqli dildə danışan ekipaj üzvü ilə işlədiyiniz bir vəziyyəti izah edin. Aydın ünsiyyəti necə təmin etdiniz?', 'type' => 'open_text', 'scoring_hints' => ['uyğunlaşma', 'ünsiyyət aydınlığı', 'səbr']],
                    ['id' => 'beh_az_tc_03', 'slot' => 9, 'text' => 'Çətin bir dövrdə ekipaj arasında müsbət atmosfer yaratmağa necə töhfə verdiyinizə dair bir nümunə verin.', 'type' => 'open_text', 'scoring_hints' => ['komanda quruculuğu', 'mənəviyyat', 'empatiya']],
                ],
            ],
            [
                'key' => 'leadership_responsibility',
                'title' => 'Liderlik və Məsuliyyət',
                'dimensions' => ['LEARNING_GROWTH', 'RELIABILITY_STABILITY'],
                'questions' => [
                    ['id' => 'beh_az_lr_01', 'slot' => 10, 'text' => 'Bir səhvə görə məsuliyyəti öz üzərinizə götürdüyünüz bir vəziyyəti təsvir edin. Bundan nə öyrəndiniz?', 'type' => 'open_text', 'scoring_hints' => ['hesabatlılıq', 'özünüqiymətləndirmə', 'inkişaf']],
                    ['id' => 'beh_az_lr_02', 'slot' => 11, 'text' => 'Kiçik rütbəli ekipaj üzvünü öyrətməli və ya yönləndirməli olduğunuz bir vəziyyəti danışın. Necə yanaşdınız?', 'type' => 'open_text', 'scoring_hints' => ['mentorluq', 'səbr', 'bilik ötürülməsi']],
                    ['id' => 'beh_az_lr_03', 'slot' => 12, 'text' => 'Dənizdə öyrəndiyiniz ən vacib peşə dərsi nədir və iş tərzinizi necə dəyişdirdi?', 'type' => 'open_text', 'scoring_hints' => ['davamlı öyrənmə', 'peşə inkişafı', 'özünü təkmilləşdirmə']],
                ],
            ],
        ];
    }

    // ─── FIL ────────────────────────────────────────────────────────────

    private function filQuestions(): array
    {
        return [
            [
                'key' => 'discipline_procedure',
                'title' => 'Disiplina at Pamamaraan',
                'dimensions' => ['DISCIPLINE_COMPLIANCE', 'RELIABILITY_STABILITY'],
                'questions' => [
                    ['id' => 'beh_fil_dp_01', 'slot' => 1, 'text' => 'Ilarawan ang isang pagkakataon na kinailangan mong sundin ang isang safety procedure na hindi ka sang-ayon. Ano ang ginawa mo?', 'type' => 'open_text', 'scoring_hints' => ['pagsunod', 'pagsunod sa protokol', 'pangangatwiran']],
                    ['id' => 'beh_fil_dp_02', 'slot' => 2, 'text' => 'Kwentuhan mo ako tungkol sa isang sitwasyon na napansin mong hindi sinusunod ng kasamahan mo ang tamang protokol. Paano mo ito hinarap?', 'type' => 'open_text', 'scoring_hints' => ['pag-uulat', 'pakikialam', 'diplomatikong pamamaraan']],
                    ['id' => 'beh_fil_dp_03', 'slot' => 3, 'text' => 'Magbigay ng halimbawa kung paano mo tiniyak na nakumpleto ang lahat ng kinakailangang checklist at dokumentasyon bago ang isang kritikal na operasyon.', 'type' => 'open_text', 'scoring_hints' => ['dokumentasyon', 'sistematikong pamamaraan', 'atensyon sa detalye']],
                ],
            ],
            [
                'key' => 'stress_crisis',
                'title' => 'Stress at Pamamahala ng Krisis',
                'dimensions' => ['STRESS_CONTROL'],
                'questions' => [
                    ['id' => 'beh_fil_sc_01', 'slot' => 4, 'text' => 'Ilarawan ang pinaka-stressful na emergency situation na naranasan mo sa barko. Paano ka tumugon?', 'type' => 'open_text', 'scoring_hints' => ['kalmadong tugon', 'sistematikong pamamaraan', 'pag-prioridad']],
                    ['id' => 'beh_fil_sc_02', 'slot' => 5, 'text' => 'Kwentuhan mo ako ng pagkakataon na kailangan mong gumawa ng kritikal na desisyon sa ilalim ng matinding time pressure. Ano ang naging resulta?', 'type' => 'open_text', 'scoring_hints' => ['pagpapasya', 'pagtataya ng panganib', 'kahinahunan']],
                    ['id' => 'beh_fil_sc_03', 'slot' => 6, 'text' => 'Paano mo pinapangasiwaan ang iyong sariling stress at pagkapagod sa mga mahabang biyahe o pinahaba na panahon ng duty?', 'type' => 'open_text', 'scoring_hints' => ['kamalayan sa sarili', 'mga estratehiya sa pag-cope', 'rutina']],
                ],
            ],
            [
                'key' => 'team_compatibility',
                'title' => 'Pagkakatugma sa Koponan',
                'dimensions' => ['TEAM_COOPERATION', 'COMM_CLARITY', 'CONFLICT_RISK'],
                'questions' => [
                    ['id' => 'beh_fil_tc_01', 'slot' => 7, 'text' => 'Ilarawan ang isang pagkakataon na nagkaroon ka ng hindi pagkakasundo sa isang superior officer. Paano mo ito nalutas?', 'type' => 'open_text', 'scoring_hints' => ['resolusyon ng alitan', 'paggalang sa hierarchy', 'komunikasyon']],
                    ['id' => 'beh_fil_tc_02', 'slot' => 8, 'text' => 'Kwentuhan mo ako ng sitwasyon na nagtrabaho ka kasama ang isang crew member na nagsasalita ng ibang wika. Paano mo tiniyak ang malinaw na komunikasyon?', 'type' => 'open_text', 'scoring_hints' => ['pag-angkop', 'kalinawan ng komunikasyon', 'pasensya']],
                    ['id' => 'beh_fil_tc_03', 'slot' => 9, 'text' => 'Magbigay ng halimbawa kung paano ka nakatulong sa pagbuo ng positibong kapaligiran sa pagitan ng crew sa isang mahirap na panahon.', 'type' => 'open_text', 'scoring_hints' => ['pagbuo ng koponan', 'morale', 'empatiya']],
                ],
            ],
            [
                'key' => 'leadership_responsibility',
                'title' => 'Pamumuno at Responsibilidad',
                'dimensions' => ['LEARNING_GROWTH', 'RELIABILITY_STABILITY'],
                'questions' => [
                    ['id' => 'beh_fil_lr_01', 'slot' => 10, 'text' => 'Ilarawan ang isang sitwasyon na tinanggap mo ang responsibilidad para sa isang pagkakamali. Ano ang natutunan mo?', 'type' => 'open_text', 'scoring_hints' => ['pananagutan', 'pag-iisip sa sarili', 'paglago']],
                    ['id' => 'beh_fil_lr_02', 'slot' => 11, 'text' => 'Kwentuhan mo ako ng pagkakataon na kailangan mong mag-train o mag-mentor ng isang junior crew member. Paano mo ito nilapitan?', 'type' => 'open_text', 'scoring_hints' => ['pagtuturo', 'pasensya', 'paglipat ng kaalaman']],
                    ['id' => 'beh_fil_lr_03', 'slot' => 12, 'text' => 'Ano ang pinakamahalagang propesyonal na aral na natutunan mo sa dagat, at paano nito binago ang paraan ng iyong pagtatrabaho?', 'type' => 'open_text', 'scoring_hints' => ['patuloy na pag-aaral', 'propesyonal na pag-unlad', 'pagpapabuti ng sarili']],
                ],
            ],
        ];
    }

    // ─── ID ─────────────────────────────────────────────────────────────

    private function idQuestions(): array
    {
        return [
            [
                'key' => 'discipline_procedure',
                'title' => 'Disiplin dan Prosedur',
                'dimensions' => ['DISCIPLINE_COMPLIANCE', 'RELIABILITY_STABILITY'],
                'questions' => [
                    ['id' => 'beh_id_dp_01', 'slot' => 1, 'text' => 'Ceritakan saat Anda harus mengikuti prosedur keselamatan yang tidak Anda setujui. Apa yang Anda lakukan?', 'type' => 'open_text', 'scoring_hints' => ['kepatuhan', 'kepatuhan protokol', 'penalaran']],
                    ['id' => 'beh_id_dp_02', 'slot' => 2, 'text' => 'Ceritakan tentang situasi ketika Anda melihat rekan kerja tidak mengikuti protokol yang semestinya. Bagaimana Anda menanganinya?', 'type' => 'open_text', 'scoring_hints' => ['pelaporan', 'intervensi', 'pendekatan diplomatik']],
                    ['id' => 'beh_id_dp_03', 'slot' => 3, 'text' => 'Berikan contoh bagaimana Anda memastikan semua checklist dan dokumentasi yang diperlukan telah diselesaikan sebelum operasi kritis.', 'type' => 'open_text', 'scoring_hints' => ['dokumentasi', 'pendekatan sistematis', 'perhatian terhadap detail']],
                ],
            ],
            [
                'key' => 'stress_crisis',
                'title' => 'Manajemen Stres dan Krisis',
                'dimensions' => ['STRESS_CONTROL'],
                'questions' => [
                    ['id' => 'beh_id_sc_01', 'slot' => 4, 'text' => 'Ceritakan situasi darurat paling menegangkan yang pernah Anda hadapi di kapal. Bagaimana Anda merespons?', 'type' => 'open_text', 'scoring_hints' => ['respons tenang', 'pendekatan sistematis', 'prioritas']],
                    ['id' => 'beh_id_sc_02', 'slot' => 5, 'text' => 'Ceritakan saat Anda harus membuat keputusan kritis di bawah tekanan waktu yang berat. Apa hasilnya?', 'type' => 'open_text', 'scoring_hints' => ['pengambilan keputusan', 'penilaian risiko', 'ketenangan']],
                    ['id' => 'beh_id_sc_03', 'slot' => 6, 'text' => 'Bagaimana Anda mengelola stres dan kelelahan Anda selama pelayaran panjang atau periode jaga yang diperpanjang?', 'type' => 'open_text', 'scoring_hints' => ['kesadaran diri', 'strategi mengatasi', 'rutinitas']],
                ],
            ],
            [
                'key' => 'team_compatibility',
                'title' => 'Kecocokan Tim',
                'dimensions' => ['TEAM_COOPERATION', 'COMM_CLARITY', 'CONFLICT_RISK'],
                'questions' => [
                    ['id' => 'beh_id_tc_01', 'slot' => 7, 'text' => 'Ceritakan saat Anda memiliki ketidaksepakatan dengan atasan. Bagaimana Anda menyelesaikannya?', 'type' => 'open_text', 'scoring_hints' => ['resolusi konflik', 'penghormatan hierarki', 'komunikasi']],
                    ['id' => 'beh_id_tc_02', 'slot' => 8, 'text' => 'Ceritakan situasi ketika Anda bekerja dengan anggota kru yang berbicara bahasa yang berbeda. Bagaimana Anda memastikan komunikasi yang jelas?', 'type' => 'open_text', 'scoring_hints' => ['adaptabilitas', 'kejelasan komunikasi', 'kesabaran']],
                    ['id' => 'beh_id_tc_03', 'slot' => 9, 'text' => 'Berikan contoh bagaimana Anda berkontribusi membangun suasana positif di antara kru selama masa sulit.', 'type' => 'open_text', 'scoring_hints' => ['pembangunan tim', 'semangat', 'empati']],
                ],
            ],
            [
                'key' => 'leadership_responsibility',
                'title' => 'Kepemimpinan dan Tanggung Jawab',
                'dimensions' => ['LEARNING_GROWTH', 'RELIABILITY_STABILITY'],
                'questions' => [
                    ['id' => 'beh_id_lr_01', 'slot' => 10, 'text' => 'Ceritakan situasi ketika Anda mengambil tanggung jawab atas kesalahan. Apa yang Anda pelajari?', 'type' => 'open_text', 'scoring_hints' => ['akuntabilitas', 'refleksi diri', 'pertumbuhan']],
                    ['id' => 'beh_id_lr_02', 'slot' => 11, 'text' => 'Ceritakan saat Anda harus melatih atau membimbing anggota kru junior. Bagaimana pendekatan Anda?', 'type' => 'open_text', 'scoring_hints' => ['pembimbingan', 'kesabaran', 'transfer pengetahuan']],
                    ['id' => 'beh_id_lr_03', 'slot' => 12, 'text' => 'Apa pelajaran profesional terpenting yang Anda pelajari di laut, dan bagaimana hal itu mengubah cara Anda bekerja?', 'type' => 'open_text', 'scoring_hints' => ['pembelajaran berkelanjutan', 'pengembangan profesional', 'peningkatan diri']],
                ],
            ],
        ];
    }

    // ─── UK ─────────────────────────────────────────────────────────────

    private function ukQuestions(): array
    {
        return [
            [
                'key' => 'discipline_procedure',
                'title' => 'Дисципліна та процедури',
                'dimensions' => ['DISCIPLINE_COMPLIANCE', 'RELIABILITY_STABILITY'],
                'questions' => [
                    ['id' => 'beh_uk_dp_01', 'slot' => 1, 'text' => 'Опишіть ситуацію, коли вам довелося дотримуватися процедури безпеки, з якою ви не погоджувалися. Що ви зробили?', 'type' => 'open_text', 'scoring_hints' => ['відповідність', 'дотримання протоколу', 'обґрунтування']],
                    ['id' => 'beh_uk_dp_02', 'slot' => 2, 'text' => 'Розкажіть про ситуацію, коли ви помітили, що колега не дотримується належного протоколу. Як ви це вирішили?', 'type' => 'open_text', 'scoring_hints' => ['звітування', 'втручання', 'дипломатичний підхід']],
                    ['id' => 'beh_uk_dp_03', 'slot' => 3, 'text' => 'Наведіть приклад того, як ви забезпечили виконання всіх необхідних чек-листів та документації перед критичною операцією.', 'type' => 'open_text', 'scoring_hints' => ['документація', 'системний підхід', 'увага до деталей']],
                ],
            ],
            [
                'key' => 'stress_crisis',
                'title' => 'Стрес та кризове управління',
                'dimensions' => ['STRESS_CONTROL'],
                'questions' => [
                    ['id' => 'beh_uk_sc_01', 'slot' => 4, 'text' => 'Опишіть найбільш стресову аварійну ситуацію, з якою ви зіткнулися на борту. Як ви відреагували?', 'type' => 'open_text', 'scoring_hints' => ['спокійна реакція', 'системний підхід', 'пріоритизація']],
                    ['id' => 'beh_uk_sc_02', 'slot' => 5, 'text' => 'Розкажіть про випадок, коли вам довелося прийняти критичне рішення в умовах сильного дефіциту часу. Який був результат?', 'type' => 'open_text', 'scoring_hints' => ['прийняття рішень', 'оцінка ризиків', 'самовладання']],
                    ['id' => 'beh_uk_sc_03', 'slot' => 6, 'text' => 'Як ви справляєтеся зі стресом та втомою під час тривалих рейсів або подовжених вахт?', 'type' => 'open_text', 'scoring_hints' => ['самосвідомість', 'стратегії подолання', 'режим']],
                ],
            ],
            [
                'key' => 'team_compatibility',
                'title' => 'Сумісність з командою',
                'dimensions' => ['TEAM_COOPERATION', 'COMM_CLARITY', 'CONFLICT_RISK'],
                'questions' => [
                    ['id' => 'beh_uk_tc_01', 'slot' => 7, 'text' => 'Опишіть ситуацію, коли у вас були розбіжності з вищим офіцером. Як ви це вирішили?', 'type' => 'open_text', 'scoring_hints' => ['вирішення конфліктів', 'повага до ієрархії', 'комунікація']],
                    ['id' => 'beh_uk_tc_02', 'slot' => 8, 'text' => 'Розкажіть про ситуацію, коли ви працювали з членом екіпажу, який говорив іншою мовою. Як ви забезпечили чітке спілкування?', 'type' => 'open_text', 'scoring_hints' => ['адаптивність', 'чіткість комунікації', 'терпіння']],
                    ['id' => 'beh_uk_tc_03', 'slot' => 9, 'text' => 'Наведіть приклад того, як ви сприяли створенню позитивної атмосфери серед екіпажу у важкий період.', 'type' => 'open_text', 'scoring_hints' => ['командоутворення', 'моральний дух', 'емпатія']],
                ],
            ],
            [
                'key' => 'leadership_responsibility',
                'title' => 'Лідерство та відповідальність',
                'dimensions' => ['LEARNING_GROWTH', 'RELIABILITY_STABILITY'],
                'questions' => [
                    ['id' => 'beh_uk_lr_01', 'slot' => 10, 'text' => 'Опишіть ситуацію, коли ви взяли на себе відповідальність за помилку. Чого ви навчилися?', 'type' => 'open_text', 'scoring_hints' => ['відповідальність', 'самоаналіз', 'зростання']],
                    ['id' => 'beh_uk_lr_02', 'slot' => 11, 'text' => 'Розкажіть про випадок, коли вам довелося навчати або наставляти молодшого члена екіпажу. Як ви підійшли до цього?', 'type' => 'open_text', 'scoring_hints' => ['наставництво', 'терпіння', 'передача знань']],
                    ['id' => 'beh_uk_lr_03', 'slot' => 12, 'text' => 'Який найважливіший професійний урок ви отримали в морі і як він змінив вашу роботу?', 'type' => 'open_text', 'scoring_hints' => ['безперервне навчання', 'професійний розвиток', 'самовдосконалення']],
                ],
            ],
        ];
    }
}
