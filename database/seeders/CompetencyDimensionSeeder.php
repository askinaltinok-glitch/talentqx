<?php

namespace Database\Seeders;

use App\Models\CompetencyDimension;
use App\Models\CompetencyQuestion;
use Illuminate\Database\Seeder;

class CompetencyDimensionSeeder extends Seeder
{
    public function run(): void
    {
        $dimensions = $this->getDimensions();
        $questions = $this->getQuestions();

        foreach ($dimensions as $dim) {
            CompetencyDimension::updateOrCreate(
                ['code' => $dim['code']],
                $dim
            );
        }

        $dimMap = CompetencyDimension::pluck('id', 'code')->all();

        foreach ($questions as $q) {
            $dimCode = $q['dimension_code'];
            unset($q['dimension_code']);
            $q['dimension_id'] = $dimMap[$dimCode];

            // Use question_text EN as a unique key for idempotency
            $enText = $q['question_text']['en'];
            $existing = CompetencyQuestion::where('dimension_id', $q['dimension_id'])
                ->whereJsonContains('question_text->en', $enText)
                ->first();

            if (!$existing) {
                CompetencyQuestion::create($q);
            }
        }
    }

    private function getDimensions(): array
    {
        return [
            [
                'code' => 'DISCIPLINE',
                'department' => 'all',
                'description' => [
                    'en' => 'Adherence to procedures, ISM code, safety protocols, and checklists',
                    'tr' => 'Prosedürlere, ISM koduna, güvenlik protokollerine ve kontrol listelerine uyum',
                    'ru' => 'Соблюдение процедур, кода ISM, протоколов безопасности и чек-листов',
                    'az' => 'Prosedurlar, ISM kodu, təhlükəsizlik protokolları və yoxlama siyahılarına riayət',
                ],
                'weight_default' => 0.20,
            ],
            [
                'code' => 'LEADERSHIP',
                'department' => 'all',
                'description' => [
                    'en' => 'Decision-making under pressure, crew management, authority and delegation',
                    'tr' => 'Baskı altında karar verme, mürettebat yönetimi, yetki ve delegasyon',
                    'ru' => 'Принятие решений под давлением, управление экипажем, полномочия и делегирование',
                    'az' => 'Təzyiq altında qərar qəbulu, ekipaj idarəetməsi, səlahiyyət və delegasiya',
                ],
                'weight_default' => 0.15,
            ],
            [
                'code' => 'STRESS',
                'department' => 'all',
                'description' => [
                    'en' => 'Stress management, composure in emergencies, fatigue resilience',
                    'tr' => 'Stres yönetimi, acil durumlarda sakinlik, yorgunluğa dayanıklılık',
                    'ru' => 'Управление стрессом, самообладание в чрезвычайных ситуациях, устойчивость к усталости',
                    'az' => 'Stres idarəetməsi, fövqəladə hallarda sakitlik, yorğunluğa davamlılıq',
                ],
                'weight_default' => 0.15,
            ],
            [
                'code' => 'TEAMWORK',
                'department' => 'all',
                'description' => [
                    'en' => 'Crew collaboration, conflict resolution, multicultural awareness',
                    'tr' => 'Ekip işbirliği, çatışma çözümü, çok kültürlü farkındalık',
                    'ru' => 'Командная работа, разрешение конфликтов, мультикультурная осведомленность',
                    'az' => 'Komanda əməkdaşlığı, münaqişələrin həlli, çoxmədəni bilik',
                ],
                'weight_default' => 0.20,
            ],
            [
                'code' => 'COMMS',
                'department' => 'all',
                'description' => [
                    'en' => 'Communication clarity, reporting accuracy, bridge/engine room communication',
                    'tr' => 'İletişim netliği, raporlama doğruluğu, köprü/makine dairesi iletişimi',
                    'ru' => 'Ясность коммуникации, точность отчётов, связь мостик/машинное отделение',
                    'az' => 'Ünsiyyət aydınlığı, hesabat dəqiqliyi, körpü/maşın otağı rabitəsi',
                ],
                'weight_default' => 0.15,
            ],
            [
                'code' => 'TECH_PRACTICAL',
                'department' => 'all',
                'description' => [
                    'en' => 'Technical knowledge application, equipment handling, practical problem solving',
                    'tr' => 'Teknik bilgi uygulaması, ekipman kullanımı, pratik problem çözme',
                    'ru' => 'Применение технических знаний, обращение с оборудованием, практическое решение проблем',
                    'az' => 'Texniki bilik tətbiqi, avadanlıq istifadəsi, praktik problem həlli',
                ],
                'weight_default' => 0.15,
            ],
        ];
    }

    private function getQuestions(): array
    {
        return [
            // ── DISCIPLINE (5 questions) ──
            [
                'dimension_code' => 'DISCIPLINE',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'Describe your daily safety routine before starting work on deck or in the engine room.',
                    'tr' => 'Güvertede veya makine dairesinde çalışmaya başlamadan önceki günlük güvenlik rutininizi anlatın.',
                    'ru' => 'Опишите вашу ежедневную процедуру безопасности перед началом работы на палубе или в машинном отделении.',
                    'az' => 'Göyərtədə və ya maşın otağında işə başlamazdan əvvəl gündəlik təhlükəsizlik rutininizi təsvir edin.',
                ],
                'rubric' => [
                    '0' => 'No answer or completely irrelevant',
                    '1' => 'Vague mention of safety without specifics',
                    '2' => 'Mentions one or two safety steps (e.g., PPE)',
                    '3' => 'Describes a structured routine with multiple steps',
                    '4' => 'Detailed routine including PPE check, toolbox talk, hazard assessment',
                    '5' => 'Comprehensive: includes ISM references, permit-to-work, crew briefing, risk assessment',
                ],
            ],
            [
                'dimension_code' => 'DISCIPLINE',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'Tell me about a time when you noticed a colleague not following a safety procedure. What did you do?',
                    'tr' => 'Bir meslektaşınızın güvenlik prosedürüne uymadığını fark ettiğiniz bir durumu anlatın. Ne yaptınız?',
                    'ru' => 'Расскажите о случае, когда вы заметили, что коллега не соблюдает процедуру безопасности. Что вы сделали?',
                    'az' => 'Bir həmkarınızın təhlükəsizlik prosedurasına əməl etmədiyini gördüyünüz bir vəziyyəti danışın. Nə etdiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer or says they would ignore it',
                    '1' => 'Says they would report but gives no example',
                    '2' => 'Gives a vague example without clear action taken',
                    '3' => 'Describes a real situation and action (spoke to colleague)',
                    '4' => 'Spoke to colleague, followed up with officer, ensured correction',
                    '5' => 'Intervened immediately, used near-miss reporting system, contributed to safety improvement',
                ],
            ],
            [
                'dimension_code' => 'DISCIPLINE',
                'role_scope' => 'MASTER',
                'operation_scope' => 'sea',
                'vessel_scope' => 'tanker',
                'difficulty' => 3,
                'question_text' => [
                    'en' => 'How do you ensure your crew follows the Ship Security Plan and ISM Code during cargo operations on a tanker?',
                    'tr' => 'Tanker gemisinde kargo operasyonları sırasında mürettebatınızın Gemi Güvenlik Planı ve ISM Koduna uymasını nasıl sağlarsınız?',
                    'ru' => 'Как вы обеспечиваете соблюдение экипажем Плана охраны судна и Кодекса ISM во время грузовых операций на танкере?',
                    'az' => 'Tanker gəmisində yük əməliyyatları zamanı ekipajınızın Gəmi Təhlükəsizlik Planına və ISM Koduna riayət etməsini necə təmin edirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer or irrelevant',
                    '1' => 'General awareness of ISM without specifics',
                    '2' => 'Mentions drills or briefings in general terms',
                    '3' => 'Describes pre-cargo meeting, safety officer coordination',
                    '4' => 'Details pre-cargo checklist, crew briefing, watch schedule, monitoring procedures',
                    '5' => 'Comprehensive: risk assessment, permit-to-work, emergency shutdown procedures, crew competency verification, near-miss logging',
                ],
            ],
            [
                'dimension_code' => 'DISCIPLINE',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'What personal protective equipment (PPE) do you use regularly, and why is each item important?',
                    'tr' => 'Düzenli olarak hangi kişisel koruyucu ekipmanları (KKE) kullanırsınız ve her biri neden önemlidir?',
                    'ru' => 'Какие средства индивидуальной защиты (СИЗ) вы регулярно используете и почему каждое из них важно?',
                    'az' => 'Müntəzəm olaraq hansı fərdi qoruyucu vasitələrdən (FQV) istifadə edirsiniz və hər biri niyə vacibdir?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Mentions one item without explanation',
                    '2' => 'Lists 2-3 items with minimal reasoning',
                    '3' => 'Lists correct PPE for their role with basic reasoning',
                    '4' => 'Detailed list with specific reasons per item and situational awareness',
                    '5' => 'Lists PPE by task type, explains regulatory requirements, mentions inspection routines',
                ],
            ],
            [
                'dimension_code' => 'DISCIPLINE',
                'role_scope' => 'ALL',
                'operation_scope' => 'river',
                'vessel_scope' => 'river',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'What are the key differences in safety procedures between river and sea operations?',
                    'tr' => 'Nehir ve deniz operasyonları arasındaki güvenlik prosedürlerindeki temel farklar nelerdir?',
                    'ru' => 'Каковы основные различия в процедурах безопасности между речными и морскими операциями?',
                    'az' => 'Çay və dəniz əməliyyatları arasında təhlükəsizlik prosedurlarında əsas fərqlər nələrdir?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Vague response about differences',
                    '2' => 'Mentions one or two differences (e.g., bridge height)',
                    '3' => 'Identifies navigation, traffic, and environmental differences',
                    '4' => 'Discusses lock operations, shallow water, current effects, restricted maneuvering',
                    '5' => 'Comprehensive: navigation rules, bridge team, environmental hazards, emergency procedures, regulatory differences',
                ],
            ],

            // ── LEADERSHIP (5 questions) ──
            [
                'dimension_code' => 'LEADERSHIP',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'Describe a situation where you had to make a critical decision under time pressure at sea.',
                    'tr' => 'Denizde zaman baskısı altında kritik bir karar vermek zorunda kaldığınız bir durumu anlatın.',
                    'ru' => 'Опишите ситуацию, когда вам пришлось принять критическое решение под давлением времени в море.',
                    'az' => 'Dənizdə vaxt təzyiqi altında kritik qərar vermək məcburiyyətində qaldığınız bir vəziyyəti təsvir edin.',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Mentions a vague situation without describing their role',
                    '2' => 'Describes a situation but unclear decision-making process',
                    '3' => 'Clear situation, describes the decision and basic reasoning',
                    '4' => 'Structured approach: assessed situation, consulted team, made decision, evaluated outcome',
                    '5' => 'Full STAR response: Situation-Task-Action-Result, includes learning, crew coordination, risk assessment',
                ],
            ],
            [
                'dimension_code' => 'LEADERSHIP',
                'role_scope' => 'MASTER',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 3,
                'question_text' => [
                    'en' => 'How do you delegate responsibilities among your officers to ensure efficient watch-keeping?',
                    'tr' => 'Etkili vardiya tutma sağlamak için sorumlulukları zabitler arasında nasıl dağıtırsınız?',
                    'ru' => 'Как вы распределяете обязанности между вашими офицерами для обеспечения эффективного несения вахты?',
                    'az' => 'Səmərəli növbə tutmağı təmin etmək üçün məsuliyyətləri zabitlər arasında necə bölüşdürürsünüz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Generic answer about giving orders',
                    '2' => 'Mentions watch schedules but no delegation strategy',
                    '3' => 'Describes clear responsibility distribution',
                    '4' => 'Structured delegation based on experience, competency assessment, and mentoring',
                    '5' => 'Comprehensive: competency-based assignment, cross-training, monitoring, feedback loops, STCW compliance',
                ],
            ],
            [
                'dimension_code' => 'LEADERSHIP',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'How do you handle a disagreement with a senior officer about an operational decision?',
                    'tr' => 'Operasyonel bir karar hakkında üst düzey bir zabit ile olan anlaşmazlığı nasıl yönetirsiniz?',
                    'ru' => 'Как вы справляетесь с разногласием со старшим офицером по поводу оперативного решения?',
                    'az' => 'Əməliyyat qərarı ilə bağlı yuxarı rütbəli zabitlə fikir ayrılığını necə idarə edirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer or says they would just obey',
                    '1' => 'Says they would express concern but no method',
                    '2' => 'Describes basic approach to raising concerns',
                    '3' => 'Uses proper chain of command, documents concern',
                    '4' => 'Respectful challenge, evidence-based argumentation, seeks resolution',
                    '5' => 'Demonstrates BRM/CRM principles, documented objection process, safety override awareness',
                ],
            ],
            [
                'dimension_code' => 'LEADERSHIP',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'How do you motivate crew members who seem disengaged or tired during long voyages?',
                    'tr' => 'Uzun seferlerde ilgisiz veya yorgun görünen mürettebat üyelerini nasıl motive edersiniz?',
                    'ru' => 'Как вы мотивируете членов экипажа, которые выглядят незаинтересованными или уставшими во время длительных рейсов?',
                    'az' => 'Uzun səfərlərdə maraqsız və ya yorğun görünən ekipaj üzvlərini necə motivasiya edirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Generic motivational statement',
                    '2' => 'Mentions one technique (e.g., talking to them)',
                    '3' => 'Multiple techniques with reasoning',
                    '4' => 'Structured approach: fatigue management, welfare activities, recognition, team building',
                    '5' => 'Comprehensive: proactive monitoring, welfare programs, rest hour compliance, psychological support awareness',
                ],
            ],
            [
                'dimension_code' => 'LEADERSHIP',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'Describe how you would train a junior crew member for their first emergency drill.',
                    'tr' => 'Genç bir mürettebat üyesini ilk acil durum tatbikatına nasıl hazırlarsınız?',
                    'ru' => 'Опишите, как вы подготовили бы младшего члена экипажа к его первому аварийному учению.',
                    'az' => 'Gənc ekipaj üzvünü ilk fövqəladə təlim üçün necə hazırlayardınız?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they would show them what to do',
                    '2' => 'Mentions briefing and demonstration',
                    '3' => 'Structured training: briefing, demonstration, practice, debrief',
                    '4' => 'Includes muster station, role assignment, equipment familiarization, confidence building',
                    '5' => 'Full training cycle: theory, practical, mentoring, assessment, SOLAS requirements reference',
                ],
            ],

            // ── STRESS (5 questions) ──
            [
                'dimension_code' => 'STRESS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'Tell me about the most stressful situation you have experienced onboard. How did you cope?',
                    'tr' => 'Gemide yaşadığınız en stresli durumu anlatın. Nasıl başa çıktınız?',
                    'ru' => 'Расскажите о самой стрессовой ситуации на борту. Как вы справились?',
                    'az' => 'Gəmidə yaşadığınız ən stresli vəziyyəti danışın. Necə öhdəsindən gəldiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer or claims never stressed',
                    '1' => 'Vague reference to stress without coping strategy',
                    '2' => 'Describes a stressful event and basic coping',
                    '3' => 'Clear event, describes emotional response and management technique',
                    '4' => 'Structured response: identified trigger, managed response, used support systems',
                    '5' => 'Self-aware, specific techniques, crew support, post-event reflection, professional resilience',
                ],
            ],
            [
                'dimension_code' => 'STRESS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'How do you manage your rest hours to prevent fatigue during your contract?',
                    'tr' => 'Kontratınız süresince yorgunluğu önlemek için dinlenme saatlerinizi nasıl yönetirsiniz?',
                    'ru' => 'Как вы управляете часами отдыха для предотвращения усталости во время контракта?',
                    'az' => 'Müqavilə müddətinizdə yorğunluğun qarşısını almaq üçün istirahət saatlarınızı necə idarə edirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they sleep when they can',
                    '2' => 'Mentions following the watch schedule',
                    '3' => 'Describes rest hour awareness and compliance',
                    '4' => 'Discusses MLC/STCW rest hour requirements, personal strategies',
                    '5' => 'Comprehensive: regulatory compliance, fatigue management plan, self-monitoring, crew welfare',
                ],
            ],
            [
                'dimension_code' => 'STRESS',
                'role_scope' => 'ALL',
                'operation_scope' => 'sea',
                'vessel_scope' => 'all',
                'difficulty' => 3,
                'question_text' => [
                    'en' => 'During heavy weather, how do you maintain operational focus and crew safety?',
                    'tr' => 'Ağır hava koşullarında operasyonel odağı ve mürettebat güvenliğini nasıl sürdürürsünüz?',
                    'ru' => 'Во время штормовой погоды как вы поддерживаете оперативную сосредоточенность и безопасность экипажа?',
                    'az' => 'Ağır hava şəraitində əməliyyat diqqətini və ekipaj təhlükəsizliyini necə qoruyursunuz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Mentions being careful',
                    '2' => 'General awareness of heavy weather precautions',
                    '3' => 'Specific measures: securing cargo, reducing speed, crew briefing',
                    '4' => 'Comprehensive preparation: weather routing, securing procedures, watch reinforcement, communication plan',
                    '5' => 'Expert: SOLAS/ISM compliance, heavy weather plan, crew welfare, monitoring systems, contingency plans',
                ],
            ],
            [
                'dimension_code' => 'STRESS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'What do you do to maintain your mental well-being during long periods away from family?',
                    'tr' => 'Ailenizden uzun süre uzak kaldığınız dönemlerde mental sağlığınızı nasıl korursunuz?',
                    'ru' => 'Что вы делаете для поддержания психического здоровья во время длительного отсутствия рядом с семьёй?',
                    'az' => 'Ailənizdən uzun müddət ayrı olduğunuz dövrlərdə psixi sağlamlığınızı necə qoruyursunuz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they just deal with it',
                    '2' => 'Mentions staying in touch with family',
                    '3' => 'Multiple strategies: communication, hobbies, routine',
                    '4' => 'Discusses welfare resources, structured communication, peer support',
                    '5' => 'Self-aware approach: personal routines, professional support awareness, healthy coping, mentoring others',
                ],
            ],
            [
                'dimension_code' => 'STRESS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'How would you react if you received bad news from home during a critical operation?',
                    'tr' => 'Kritik bir operasyon sırasında evden kötü bir haber alsanız nasıl tepki verirsiniz?',
                    'ru' => 'Как бы вы отреагировали, если бы получили плохие новости из дома во время критической операции?',
                    'az' => 'Kritik bir əməliyyat zamanı evdən pis xəbər alsanız necə reaksiya verərsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they would continue working with no strategy',
                    '2' => 'Mentions informing a colleague',
                    '3' => 'Would inform senior officer and manage emotional response',
                    '4' => 'Structured: inform officer, request relief if needed, professional composure, seek support',
                    '5' => 'Demonstrates professionalism: safety first, chain of command, welfare resources, knows when to step back',
                ],
            ],

            // ── TEAMWORK (5 questions) ──
            [
                'dimension_code' => 'TEAMWORK',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'Describe your experience working with crew members from different nationalities and cultures.',
                    'tr' => 'Farklı milliyetlerden ve kültürlerden mürettebat üyeleriyle çalışma deneyiminizi anlatın.',
                    'ru' => 'Опишите ваш опыт работы с членами экипажа разных национальностей и культур.',
                    'az' => 'Fərqli milliyyətlərdən və mədəniyyətlərdən olan ekipaj üzvləri ilə iş təcrübənizi təsvir edin.',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they get along with everyone without examples',
                    '2' => 'Mentions working with different nationalities',
                    '3' => 'Gives specific examples of cross-cultural collaboration',
                    '4' => 'Describes communication strategies, cultural sensitivity, conflict resolution',
                    '5' => 'Rich examples: language adaptation, cultural mediation, team building activities, inclusive leadership',
                ],
            ],
            [
                'dimension_code' => 'TEAMWORK',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'Tell me about a conflict you helped resolve between crew members.',
                    'tr' => 'Mürettebat üyeleri arasında çözülmesine yardım ettiğiniz bir çatışmayı anlatın.',
                    'ru' => 'Расскажите о конфликте между членами экипажа, который вы помогли разрешить.',
                    'az' => 'Ekipaj üzvləri arasında həll etməyə kömək etdiyiniz bir münaqişəni danışın.',
                ],
                'rubric' => [
                    '0' => 'No answer or says conflicts never happen',
                    '1' => 'Vague reference without specifics',
                    '2' => 'Describes a conflict but unclear resolution',
                    '3' => 'Clear conflict, describes mediation approach',
                    '4' => 'Active mediation: listened to both sides, found compromise, followed up',
                    '5' => 'Professional conflict resolution: impartial, documented, escalated appropriately, preventive measures',
                ],
            ],
            [
                'dimension_code' => 'TEAMWORK',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'How do you ensure smooth handover between watches?',
                    'tr' => 'Vardiyalar arası sorunsuz devir teslimi nasıl sağlarsınız?',
                    'ru' => 'Как вы обеспечиваете плавную передачу вахты?',
                    'az' => 'Növbələr arasında rahat təhvil-təslimi necə təmin edirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they inform the next watch',
                    '2' => 'Mentions basic information exchange',
                    '3' => 'Structured handover: current status, pending tasks, navigation conditions',
                    '4' => 'Detailed: uses checklist, verbal + written handover, equipment status, weather updates',
                    '5' => 'Comprehensive: BRM principles, documented handover, situational awareness transfer, safety concerns logged',
                ],
            ],
            [
                'dimension_code' => 'TEAMWORK',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'Give an example of a task that required close coordination with multiple departments onboard.',
                    'tr' => 'Gemide birden fazla departmanla yakın koordinasyon gerektiren bir görev örneği verin.',
                    'ru' => 'Приведите пример задачи, требующей тесной координации с несколькими отделами на борту.',
                    'az' => 'Gəmidə bir neçə şöbə ilə yaxın əlaqələndirmə tələb edən bir tapşırıq nümunəsi verin.',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Mentions a task without coordination details',
                    '2' => 'Describes basic communication between departments',
                    '3' => 'Clear example with roles of each department explained',
                    '4' => 'Detailed coordination: planning meeting, task distribution, communication channels, monitoring',
                    '5' => 'Expert: pre-operation meeting, risk assessment, communication protocol, contingency plan, debrief',
                ],
            ],
            [
                'dimension_code' => 'TEAMWORK',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'How do you support a new crew member integrating into the team during their first week?',
                    'tr' => 'Yeni bir mürettebat üyesinin ilk haftasında ekibe uyum sağlamasını nasıl desteklersiniz?',
                    'ru' => 'Как вы помогаете новому члену экипажа адаптироваться в команде в течение первой недели?',
                    'az' => 'Yeni ekipaj üzvünün ilk həftəsində komandaya uyğunlaşmasını necə dəstəkləyirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they would be friendly',
                    '2' => 'Mentions showing them around',
                    '3' => 'Structured onboarding: familiarization tour, introductions, mentoring',
                    '4' => 'Comprehensive: safety familiarization, duties explanation, buddy system, regular check-ins',
                    '5' => 'Full integration: ISM familiarization, competency assessment, cultural sensitivity, welfare support, documentation',
                ],
            ],

            // ── COMMS (5 questions) ──
            [
                'dimension_code' => 'COMMS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'How do you ensure clear communication during a critical maneuver such as anchoring or mooring?',
                    'tr' => 'Demir atma veya bağlama gibi kritik manevralarda net iletişimi nasıl sağlarsınız?',
                    'ru' => 'Как вы обеспечиваете чёткую связь во время критического манёвра, такого как постановка на якорь или швартовка?',
                    'az' => 'Lövbər atma və ya bağlama kimi kritik manevrlərdə aydın ünsiyyəti necə təmin edirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they talk on the radio',
                    '2' => 'Mentions using standard commands',
                    '3' => 'Describes communication protocol and equipment',
                    '4' => 'Detailed: pre-operation briefing, standard maritime phrases, closed-loop communication',
                    '5' => 'Expert: SMCP compliance, BRM principles, equipment checks, contingency communication plan',
                ],
            ],
            [
                'dimension_code' => 'COMMS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'How do you report a near-miss incident? Walk me through the process.',
                    'tr' => 'Ramak kala bir olayı nasıl raporlarsınız? Süreci anlatın.',
                    'ru' => 'Как вы отчитываетесь о происшествии, которое едва не произошло? Расскажите о процессе.',
                    'az' => 'Qəzaya yaxın bir hadisəni necə bildirirsiniz? Prosesi izah edin.',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they would tell someone',
                    '2' => 'Mentions filling out a form',
                    '3' => 'Describes basic reporting: who, what, when',
                    '4' => 'Full process: immediate report, written documentation, investigation support, corrective action',
                    '5' => 'Expert: ISM near-miss system, root cause analysis, lessons learned, safety management feedback loop',
                ],
            ],
            [
                'dimension_code' => 'COMMS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'How do you communicate effectively with someone who speaks limited English onboard?',
                    'tr' => 'Gemide sınırlı İngilizce konuşan biriyle etkili iletişimi nasıl kurarsınız?',
                    'ru' => 'Как вы эффективно общаетесь с человеком на борту, который говорит на ограниченном английском?',
                    'az' => 'Gəmidə məhdud İngiliscə danışan biri ilə effektiv ünsiyyəti necə qurursunuz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they speak slowly',
                    '2' => 'Mentions gestures or visual aids',
                    '3' => 'Uses simple language, visual aids, confirms understanding',
                    '4' => 'Multiple strategies: SMCP phrases, demonstrations, buddy pairing, translation tools',
                    '5' => 'Comprehensive: safety-critical communication protocols, cultural awareness, crew language training support',
                ],
            ],
            [
                'dimension_code' => 'COMMS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'Describe how you keep your logbook or maintenance records accurate and up to date.',
                    'tr' => 'Jurnal defterinizi veya bakım kayıtlarınızı doğru ve güncel tutmayı nasıl sağlarsınız?',
                    'ru' => 'Опишите, как вы ведёте судовой журнал или записи о техническом обслуживании точно и актуально.',
                    'az' => 'Jurnal dəftərinizi və ya texniki xidmət qeydlərinizi necə dəqiq və aktual saxlayırsınız?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they write things down',
                    '2' => 'Mentions recording basic information',
                    '3' => 'Describes systematic record keeping with timing',
                    '4' => 'Detailed: real-time recording, cross-referencing, compliance awareness, digital tools',
                    '5' => 'Expert: regulatory requirements, audit readiness, error correction procedures, data integrity',
                ],
            ],
            [
                'dimension_code' => 'COMMS',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 3,
                'question_text' => [
                    'en' => 'How do you communicate an emergency situation to the shore office and port authorities?',
                    'tr' => 'Acil bir durumu kıyı ofisine ve liman otoritelerine nasıl iletirsiniz?',
                    'ru' => 'Как вы сообщаете о чрезвычайной ситуации береговому офису и портовым властям?',
                    'az' => 'Fövqəladə vəziyyəti sahil ofisinə və liman orqanlarına necə bildirirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they would call the office',
                    '2' => 'Mentions using radio or phone',
                    '3' => 'Describes communication channels and basic protocol',
                    '4' => 'Detailed: GMDSS procedures, DPA contact, SITREP format, multi-channel approach',
                    '5' => 'Expert: SOLAS Chapter IV, emergency communication plan, coordination with SAR, documentation, follow-up',
                ],
            ],

            // ── TECH_PRACTICAL (5 questions) ──
            [
                'dimension_code' => 'TECH_PRACTICAL',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'Describe a technical problem you diagnosed and fixed onboard without external help.',
                    'tr' => 'Gemide harici yardım almadan teşhis edip çözdüğünüz teknik bir sorunu anlatın.',
                    'ru' => 'Опишите техническую проблему, которую вы диагностировали и устранили на борту без внешней помощи.',
                    'az' => 'Gəmidə xarici kömək almadan diaqnoz qoyub həll etdiyiniz texniki problemi təsvir edin.',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Vague reference without technical detail',
                    '2' => 'Describes a problem with basic troubleshooting',
                    '3' => 'Clear problem description, logical troubleshooting steps, solution',
                    '4' => 'Systematic approach: diagnosis, root cause, repair, testing, documentation',
                    '5' => 'Expert: methodical troubleshooting, safety measures, spare parts management, preventive recommendation',
                ],
            ],
            [
                'dimension_code' => 'TECH_PRACTICAL',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'How do you approach planned maintenance to prevent equipment failures?',
                    'tr' => 'Ekipman arızalarını önlemek için planlı bakıma nasıl yaklaşırsınız?',
                    'ru' => 'Как вы подходите к планомерному техобслуживанию для предотвращения поломок оборудования?',
                    'az' => 'Avadanlıq nasazlıqlarının qarşısını almaq üçün planlaşdırılmış texniki xidmətə necə yanaşırsınız?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they follow the schedule',
                    '2' => 'Mentions PMS system',
                    '3' => 'Describes PMS compliance and scheduling approach',
                    '4' => 'Detailed: condition monitoring, trending, priority setting, spare parts planning',
                    '5' => 'Expert: predictive maintenance awareness, class requirements, critical equipment focus, documentation, budget management',
                ],
            ],
            [
                'dimension_code' => 'TECH_PRACTICAL',
                'role_scope' => 'ALL',
                'operation_scope' => 'sea',
                'vessel_scope' => 'tanker',
                'difficulty' => 3,
                'question_text' => [
                    'en' => 'Explain the critical checks you perform before and during cargo loading on a tanker.',
                    'tr' => 'Bir tanker gemisinde kargo yüklemesi öncesi ve sırasında yaptığınız kritik kontrolleri açıklayın.',
                    'ru' => 'Объясните критические проверки, которые вы выполняете до и во время погрузки на танкере.',
                    'az' => 'Tanker gəmisində yük yükləməsi öncəsi və zamanı apardığınız kritik yoxlamaları izah edin.',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Vague awareness of cargo procedures',
                    '2' => 'Mentions a few checks (ullage, valve alignment)',
                    '3' => 'Describes pre-loading and monitoring procedures',
                    '4' => 'Detailed: ship-shore safety checklist, cargo plan review, monitoring parameters, communication protocol',
                    '5' => 'Expert: ISGOTT compliance, inert gas system, vapor management, emergency procedures, documentation trail',
                ],
            ],
            [
                'dimension_code' => 'TECH_PRACTICAL',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 2,
                'question_text' => [
                    'en' => 'What steps do you take when a fire detection alarm goes off during your watch?',
                    'tr' => 'Vardiya sırasında yangın algılama alarmı çaldığında hangi adımları atarsınız?',
                    'ru' => 'Какие действия вы предпринимаете, когда во время вашей вахты срабатывает пожарная сигнализация?',
                    'az' => 'Növbəniz zamanı yanğın aşkarlama siqnalı işə düşdükdə hansı addımları atarsınız?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they would check it out',
                    '2' => 'Mentions going to investigate and calling others',
                    '3' => 'Describes verification, notification, and initial response',
                    '4' => 'Detailed: verify alarm, assess, notify bridge/master, muster crew, prepare firefighting equipment',
                    '5' => 'Expert: fire plan reference, boundary cooling, ventilation control, SOLAS procedures, coordination with shore',
                ],
            ],
            [
                'dimension_code' => 'TECH_PRACTICAL',
                'role_scope' => 'ALL',
                'operation_scope' => 'both',
                'vessel_scope' => 'all',
                'difficulty' => 1,
                'question_text' => [
                    'en' => 'How do you ensure that safety equipment (life rafts, fire extinguishers, etc.) is always ready for use?',
                    'tr' => 'Güvenlik ekipmanlarının (can salları, yangın söndürücüler vb.) her zaman kullanıma hazır olmasını nasıl sağlarsınız?',
                    'ru' => 'Как вы обеспечиваете постоянную готовность спасательного оборудования (спасательные плоты, огнетушители и т.д.) к использованию?',
                    'az' => 'Təhlükəsizlik avadanlıqlarının (xilasetmə salları, yanğın söndürücülər və s.) həmişə istifadəyə hazır olmasını necə təmin edirsiniz?',
                ],
                'rubric' => [
                    '0' => 'No answer',
                    '1' => 'Says they check them sometimes',
                    '2' => 'Mentions regular inspections',
                    '3' => 'Describes inspection schedule and basic checks',
                    '4' => 'Detailed: inspection intervals, service dates, documentation, deficiency reporting',
                    '5' => 'Expert: SOLAS requirements, class survey preparation, crew training on equipment, maintenance records, replacement planning',
                ],
            ],
        ];
    }
}
