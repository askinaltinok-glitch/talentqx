<?php

namespace Database\Seeders;

use App\Models\MaritimeScenario;
use Illuminate\Database\Seeder;

/**
 * Populate SHORT_SEA scenarios with production-quality content.
 *
 * Idempotent: updates existing rows by scenario_code.
 * Run: php82 artisan db:seed --class=ShortSeaScenarioContentSeeder --force
 */
class ShortSeaScenarioContentSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = $this->getScenarios();

        foreach ($scenarios as $code => $data) {
            MaritimeScenario::where('scenario_code', $code)->update($data);
            $this->command->info("Updated: {$code}");
        }

        $this->command->info('SHORT_SEA scenario content seeded (8 scenarios).');
    }

    private function getScenarios(): array
    {
        return [

            // ══════════════════════════════════════════════════════════════
            // SLOT 1 — NAV_COMPLEX — TSS approach at night with fishing
            // ══════════════════════════════════════════════════════════════
            'SHORT_SEA_S01_NAV_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 9,500 DWT short sea cargo vessel operating between Mediterranean ports. You are approaching a narrow traffic separation scheme at night with heavy fishing activity.',
                        'your_position'       => 'Bridge, command. 3/O on watch, pilot boarding in 40 minutes.',
                        'available_resources'  => 'ECDIS, ARPA radar, AIS, VHF, full bridge team.',
                        'current_conditions'   => 'Visibility moderate (3 NM), cross current 1.5 knots.',
                    ],
                    'tr' => [
                        'situation'           => 'Akdeniz limanları arasında çalışan 9.500 DWT kısa mesafe yük gemisinin kaptanısınız. Gece dar bir trafik ayırım düzenine yaklaşıyorsunuz ve yoğun balıkçı faaliyeti var.',
                        'your_position'       => 'Köprüüstü, komuta sizde. 3. zabit vardiyada, 40 dakika sonra pilot alınacak.',
                        'available_resources'  => 'ECDIS, ARPA radar, AIS, VHF, tam köprüüstü ekibi.',
                        'current_conditions'   => 'Görüş orta (3 mil), yan akıntı 1.5 knot.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан грузового судна 9 500 DWT, приближаетесь ночью к узкой схеме разделения движения при интенсивном рыболовстве.',
                        'your_position'       => 'Мостик, командование. 3-й помощник на вахте, лоцман через 40 минут.',
                        'available_resources'  => 'ЭКНИС, САРП радар, АИС, УКВ, полная команда мостика.',
                        'current_conditions'   => 'Видимость средняя (3 мили), боковое течение 1,5 узла.',
                    ],
                    'az' => [
                        'situation'           => 'Siz Aralıq dənizi limanları arasında işləyən 9 500 DWT yük gəmisinin kapitanısınız. Gecə dar trafik ayrılma sxeminə yaxınlaşırsınız, intensiv balıqçılıq fəaliyyəti var.',
                        'your_position'       => 'Körpüüstü, komanda sizdə. 3-cü stürman növbədə, losman 40 dəqiqəyə minəcək.',
                        'available_resources'  => 'ECDIS, ARPA radar, AIS, VHF, tam körpüüstü komandası.',
                        'current_conditions'   => 'Görmə orta (3 mil), yan axın 1,5 düyün.',
                    ],
                ],
                'decision_prompt' => 'Describe your actions as you approach the TSS at night with heavy fishing traffic and moderate visibility.',
                'decision_prompt_i18n' => [
                    'tr' => 'Gece yoğun balıkçı trafiği ve orta görüş koşullarında TSS\'ye yaklaşırken eylemlerinizi tanımlayın.',
                    'ru' => 'Опишите ваши действия при подходе к СРД ночью при интенсивном рыболовецком трафике и средней видимости.',
                    'az' => 'Gecə intensiv balıqçı trafiki və orta görmə şəraitində TSS-ə yaxınlaşarkən hərəkətlərinizi təsvir edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'situational_awareness',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No systematic assessment of traffic picture; fails to identify fishing vessels as a distinct hazard.',
                            '2' => 'Recognises fishing activity but does not cross-check radar, AIS, and visual bearings.',
                            '3' => 'Monitors ARPA/AIS targets, identifies fishing clusters, and maintains a mental model of the traffic picture.',
                            '4' => 'Systematic radar/AIS/visual cross-referencing; identifies CPA/TCPA for all significant targets; updates bridge team on traffic picture regularly.',
                            '5' => 'Full situational awareness: plots all targets including non-AIS small craft via radar, assigns bridge team members to specific sectors, continuously updates risk picture, anticipates fishing pattern changes near TSS lanes.',
                        ],
                    ],
                    [
                        'axis'   => 'traffic_management',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Makes no attempt to manage traffic conflicts; maintains course and speed regardless of fishing traffic.',
                            '2' => 'Reacts to individual close-quarter situations but has no overall plan for transiting through the fishing fleet.',
                            '3' => 'Plans an approach path that accounts for major fishing concentrations; adjusts speed appropriately before entering TSS.',
                            '4' => 'Proactive traffic management: early speed adjustments, planned passing arrangements, VHF coordination with fishing vessels where possible, and clear helm orders.',
                            '5' => 'Expert-level: pre-plots approach through fishing fleet, establishes VHF communication with identifiable fishing vessels, coordinates with VTS if applicable, executes precise COLREG-compliant manoeuvres with documented decision points.',
                        ],
                    ],
                    [
                        'axis'   => 'bridge_resource_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No delegation; Master attempts to handle everything alone or leaves 3/O unsupported.',
                            '2' => 'Some delegation but roles unclear; bridge team communication is minimal.',
                            '3' => 'Assigns lookout, radar monitoring, and helm duties; communicates intentions to bridge team.',
                            '4' => 'Structured BRM: dedicated lookout, radar/AIS monitor, helmsman briefed, closed-loop communication, Master retains oversight and decision authority.',
                            '5' => 'Exemplary BRM: challenge-and-response protocol active, additional lookout posted, pre-approach briefing conducted, pilot boarding plan integrated, all bridge team members aware of abort criteria.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No mention of COLREG compliance', 'severity' => 'critical'],
                    ['flag' => 'No speed adjustment', 'severity' => 'critical'],
                    ['flag' => 'No risk assessment before TSS entry', 'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'COLREG Rule 5',
                    'COLREG Rule 7',
                    'COLREG Rule 10',
                ],
                'red_flags_json' => [
                    'Over-reliance on AIS only',
                    'No BRM delegation',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 2 — CMD_SCALE — Small crew port operation + PSC
            // ══════════════════════════════════════════════════════════════
            'SHORT_SEA_S02_CMD_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 6,800 DWT coaster alongside in a small Greek island port. You have only 12 crew. Cargo loading is underway when Port State Control officers arrive for an unannounced inspection. Simultaneously, the agent informs you that loading must be completed within 4 hours or you lose the berth.',
                        'your_position'       => 'On deck supervising cargo. C/O managing holds, 2/O preparing departure docs.',
                        'available_resources'  => '12 crew total, ship agent, loading plan, ISM manuals, PSC-ready documentation.',
                        'current_conditions'   => 'Fine weather, vessel alongside, 60% cargo loaded.',
                    ],
                    'tr' => [
                        'situation'           => '6.800 DWT kıyı ticaret gemisinin kaptanısınız, küçük bir Yunan ada limanında yanaşık durumdasınız. 12 kişilik mürettebatınız var. Yükleme devam ederken PSC (Liman Devleti Kontrolü) müfettişleri habersiz denetime geliyor. Aynı anda acente, yüklemenin 4 saat içinde tamamlanması gerektiğini yoksa rıhtımı kaybedeceğinizi bildiriyor.',
                        'your_position'       => 'Güvertede yük denetimi. Birinci zabit ambar yönetimi, ikinci zabit kalkış evrakları hazırlıyor.',
                        'available_resources'  => '12 kişi mürettebat, gemi acentesi, yükleme planı, ISM kılavuzları, PSC-hazır belgeler.',
                        'current_conditions'   => 'Güzel hava, gemi yanaşık, yüklemenin %60\'ı tamamlanmış.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан каботажного судна 6 800 DWT в маленьком греческом островном порту. Экипаж 12 человек. Во время погрузки прибывают инспекторы PSC, а агент сообщает, что погрузку нужно завершить за 4 часа или потеряете причал.',
                        'your_position'       => 'На палубе. Старпом управляет трюмами, 2-й помощник готовит документы.',
                        'available_resources'  => '12 человек, агент, план погрузки, ISM документация.',
                        'current_conditions'   => 'Хорошая погода, у причала, 60% груза загружено.',
                    ],
                    'az' => [
                        'situation'           => 'Siz kiçik yunan adası limanında 6 800 DWT sahilboyu gəminin kapitanısınız. 12 nəfər heyətiniz var. Yükləmə davam edərkən PSC müfəttişləri gəlir, eyni zamanda agent 4 saat ərzində yükləməni bitirməsəniz rıhtımı itirəcəyinizi bildirir.',
                        'your_position'       => 'Göyərtədə. Birinci stürman anbar idarə edir, ikinci stürman sənədlər hazırlayır.',
                        'available_resources'  => '12 nəfər heyət, agent, yükləmə planı, ISM sənədləri.',
                        'current_conditions'   => 'Yaxşı hava, gəmi rıhtımda, 60% yüklənib.',
                    ],
                ],
                'decision_prompt' => 'How will you manage the simultaneous demands of cargo loading, PSC inspection, and the berth time constraint with your limited crew?',
                'decision_prompt_i18n' => [
                    'tr' => 'Sınırlı mürettebatınızla eş zamanlı yükleme, PSC denetimi ve rıhtım süre kısıtlamasını nasıl yöneteceksiniz?',
                    'ru' => 'Как вы будете управлять одновременными задачами погрузки, инспекции PSC и ограничением по времени причала при ограниченном экипаже?',
                    'az' => 'Məhdud heyətlə eyni vaxtda yükləmə, PSC yoxlaması və rıhtım vaxt məhdudiyyətini necə idarə edəcəksiniz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'task_prioritization',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Cannot distinguish between urgent and important tasks; attempts everything simultaneously without a plan, resulting in chaos.',
                            '2' => 'Recognises the three demands but addresses them in a random or inefficient sequence.',
                            '3' => 'Sets a clear priority order (safety/PSC compliance first, then cargo, then schedule) and communicates it to the team.',
                            '4' => 'Structured priority matrix: welcomes PSC immediately, maintains safe cargo operations in parallel, informs agent of realistic timeline, adjusts loading sequence if needed.',
                            '5' => 'Expert prioritization: simultaneous but controlled work streams — personally receives PSC while C/O continues cargo under standing orders, 2/O supports PSC document requests, agent negotiates berth extension; contingency plan if PSC finds deficiencies.',
                        ],
                    ],
                    [
                        'axis'   => 'delegation',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Tries to do everything personally; no delegation to officers or crew.',
                            '2' => 'Delegates one task but micromanages others, creating bottlenecks.',
                            '3' => 'Assigns C/O to cargo, 2/O to PSC documents, and personally hosts the inspection; roles are clear.',
                            '4' => 'Effective delegation with check-in points: C/O has authority over cargo decisions, 2/O briefs PSC on documentation while Master joins, bosun assigned to guide inspectors safely on deck.',
                            '5' => 'Optimal crew utilisation: every crew member has a defined role, communication channels established (radio/phone), backup plan if any role is disrupted, agent leveraged as external resource for berth negotiation and additional support.',
                        ],
                    ],
                    [
                        'axis'   => 'stress_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Visibly panicked or aggressive; makes rash decisions that increase risk.',
                            '2' => 'Shows signs of stress that affect communication; snaps at officers or becomes indecisive.',
                            '3' => 'Maintains composure and makes rational decisions despite pressure; communicates calmly.',
                            '4' => 'Leads by example under pressure: calm demeanour, clear instructions, acknowledges the stress openly and manages crew morale.',
                            '5' => 'Exemplary stress leadership: calm and methodical, sets realistic expectations with all parties, demonstrates ISM culture by not cutting corners under pressure, debriefs team after situation resolves.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No delegation to C/O', 'severity' => 'critical'],
                    ['flag' => 'No PSC preparation plan', 'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'ISM Code',
                    'PSC procedures',
                ],
                'red_flags_json' => [
                    'Ignoring PSC to focus on cargo',
                    'No task delegation under pressure',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 3 — TECH_DEPTH — Main engine alarm before port arrival
            // ══════════════════════════════════════════════════════════════
            'SHORT_SEA_S03_TECH_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 8,200 DWT general cargo vessel, 2 hours from your discharge port in the Adriatic. The Chief Engineer reports a high exhaust temperature alarm on cylinder No. 3 of the main engine. The C/E recommends reducing speed to investigate but the port pilot is scheduled and berth availability is tight.',
                        'your_position'       => 'Bridge, approaching port. C/E on phone from engine room.',
                        'available_resources'  => 'Engine monitoring system, C/E with 20 years experience, port agent on standby, pilot boarding at breakwater.',
                        'current_conditions'   => 'Calm weather, good visibility, moderate traffic in approach channel.',
                    ],
                    'tr' => [
                        'situation'           => '8.200 DWT genel kargo gemisinin kaptanısınız, Adriyatik\'teki boşaltma limanınıza 2 saat kala. Başmühendis ana makinenin 3 numaralı silindirinde yüksek egzoz sıcaklığı alarmı bildiriyor. Başmühendis araştırmak için hız düşürmeyi öneriyor fakat liman pilotu planlanmış ve rıhtım müsaitliği sıkışık.',
                        'your_position'       => 'Köprüüstü, limana yaklaşma. Başmühendis makine dairesinden telefonda.',
                        'available_resources'  => 'Motor izleme sistemi, 20 yıl deneyimli Başmühendis, beklemede liman acentesi, dalgakıran hizasında pilot alımı.',
                        'current_conditions'   => 'Sakin hava, iyi görüş, yaklaşma kanalında orta yoğunlukta trafik.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан грузового судна 8 200 DWT, в 2 часах от порта выгрузки в Адриатике. Стармех сообщает о высокой температуре выхлопа на цилиндре №3. Рекомендует снизить скорость, но лоцман назначен и причал ограничен по времени.',
                        'your_position'       => 'Мостик, подход к порту. Стармех на связи из МО.',
                        'available_resources'  => 'Система мониторинга двигателя, опытный стармех, агент, лоцман.',
                        'current_conditions'   => 'Спокойная погода, хорошая видимость, умеренное движение.',
                    ],
                    'az' => [
                        'situation'           => 'Siz 8 200 DWT yük gəmisinin kapitanısınız, Adriatikdəki boşaltma limanına 2 saat qalıb. Baş mühəndis 3 nömrəli silindrdə yüksək istilik alarmı bildirir. Sürəti azaltmağı tövsiyə edir, lakin losman planlaşdırılıb və rıhtım vaxtı sıxdır.',
                        'your_position'       => 'Körpüüstü, limana yaxınlaşma. Baş mühəndis mühərrik otağından əlaqədə.',
                        'available_resources'  => 'Mühərrik monitorinq sistemi, təcrübəli baş mühəndis, agent, losman.',
                        'current_conditions'   => 'Sakit hava, yaxşı görmə, orta trafik.',
                    ],
                ],
                'decision_prompt' => 'What is your decision regarding the engine alarm with port arrival imminent? Detail your coordination with the engine room and contingency plans.',
                'decision_prompt_i18n' => [
                    'tr' => 'Liman varışı yakınken motor alarmı hakkında kararınız nedir? Makine dairesi ile koordinasyonunuzu ve acil durum planlarınızı ayrıntılı anlatın.',
                    'ru' => 'Каково ваше решение по поводу аварийного сигнала двигателя при скором прибытии в порт? Опишите координацию с МО и план действий.',
                    'az' => 'Liman gəlişi yaxınlaşarkən mühərrik alarmı ilə bağlı qərarınız nədir? Mühərrik otağı ilə koordinasiyanızı və ehtiyat planlarınızı ətraflı izah edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'technical_decision_quality',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Ignores the alarm and maintains full speed to keep the schedule; no technical discussion with C/E.',
                            '2' => 'Acknowledges the alarm but delays action, hoping the temperature will stabilise on its own.',
                            '3' => 'Agrees to reduce speed as C/E recommends; requests regular temperature updates and sets a threshold for stopping the engine.',
                            '4' => 'Immediate speed reduction; requests C/E to isolate cylinder No. 3 fuel if feasible, monitors all related parameters (exhaust temp, turbocharger, scavenge), evaluates running on reduced cylinders for port approach.',
                            '5' => 'Expert technical decision: immediate speed reduction, systematic diagnostic process with C/E (fuel injector, exhaust valve, piston ring possibilities), evaluates safe operating envelope for reduced-power approach, documents alarm data, considers requesting tug assistance for berthing at reduced power.',
                        ],
                    ],
                    [
                        'axis'   => 'engine_bridge_coordination',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No meaningful communication with engine room; gives orders without discussion.',
                            '2' => 'Listens to C/E but does not share bridge-level information (ETA, pilot schedule, berthing constraints).',
                            '3' => 'Two-way communication: shares ETA/schedule pressures with C/E and receives technical recommendations; agrees on a plan.',
                            '4' => 'Structured coordination: establishes continuous communication link, agrees on monitoring intervals, sets clear decision triggers (e.g., "if temp exceeds X, we stop"), C/E informed of manoeuvring requirements.',
                            '5' => 'Exemplary engine-bridge teamwork: joint decision matrix, C/E briefed on full approach plan including pilot boarding and berthing, contingency for engine failure during approach pre-agreed, engine room prepared for emergency manoeuvres, all decisions logged.',
                        ],
                    ],
                    [
                        'axis'   => 'risk_containment',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No contingency planning; assumes engine will be fine until berthing.',
                            '2' => 'Vaguely mentions "we will anchor if needed" but no specific plan.',
                            '3' => 'Identifies anchor position on approach chart; notifies pilot and agent of potential delay; considers tug availability.',
                            '4' => 'Comprehensive contingency: pre-selects emergency anchorage, notifies VTS/pilot of engine issue, requests standby tug, agent alerted to possible berthing delay, alternative arrival plan communicated to port.',
                            '5' => 'Full risk containment: pre-plotted emergency anchorage with depth check, VTS/pilot/agent/company all notified with situation assessment, tug requested on standby, anchor cleared and ready, bridge team briefed on emergency stopping procedure, abort criteria defined for each phase of approach.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No engine room consultation', 'severity' => 'critical'],
                    ['flag' => 'No speed reduction consideration', 'severity' => 'major'],
                    ['flag' => 'No contingency if engine fails during approach', 'severity' => 'critical'],
                ],
                'expected_references_json' => [
                    'Engine manufacturer guidelines',
                    'SMS procedures for machinery failure',
                    'SOLAS Chapter II-1',
                ],
                'red_flags_json' => [
                    'Ignoring C/E recommendation to reduce speed',
                    'No contingency plan for engine failure in narrow approach',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 4 — RISK_MGMT — Cargo stability risk / partial shift
            // ══════════════════════════════════════════════════════════════
            'SHORT_SEA_S04_RISK_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 9,000 DWT multipurpose vessel loaded with timber and steel coils. During moderate weather in the Eastern Mediterranean (Beaufort 5-6), the watch officer reports that timber deck cargo lashings on the port side have slackened and some bundles appear to have shifted approximately 0.5 meters to starboard. The vessel has developed a slight list of 3 degrees to starboard.',
                        'your_position'       => 'Bridge, night watch. C/O called to bridge.',
                        'available_resources'  => 'Loading computer, cargo securing manual, lashing equipment in bosun store, ballast system operational.',
                        'current_conditions'   => 'Beaufort 5-6, swell 2-3m from W, night time, nearest shelter 6 hours away.',
                    ],
                    'tr' => [
                        'situation'           => '9.000 DWT çok amaçlı geminin kaptanısınız, kereste ve çelik rulo yüklü. Doğu Akdeniz\'de orta şiddette havada (Beaufort 5-6) vardiya zabiti iskele tarafındaki güverte kereste bağlamalarının gevşediğini ve bazı demetlerin sancağa yaklaşık 0,5 metre kaydığını bildiriyor. Gemide sancağa 3 derece hafif meyil (list) oluşmuş.',
                        'your_position'       => 'Köprüüstü, gece vardiyası. Birinci zabit köprüüstüne çağırıldı.',
                        'available_resources'  => 'Yükleme bilgisayarı, yük emniyeti kılavuzu, lostromo deposunda bağlama ekipmanı, balast sistemi çalışır.',
                        'current_conditions'   => 'Beaufort 5-6, B\'den 2-3m kabarık, gece, en yakın sığınağa 6 saat.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан многоцелевого судна 9 000 DWT с грузом леса и стальных рулонов. При Бофорт 5-6 в Восточном Средиземноморье вахтенный сообщает о сместившемся палубном лесе (0,5 м на правый борт) и крене 3 градуса.',
                        'your_position'       => 'Мостик, ночная вахта. Старпом вызван.',
                        'available_resources'  => 'Загрузочный компьютер, наставление по креплению, крепёжные материалы, балластная система.',
                        'current_conditions'   => 'Бофорт 5-6, зыбь 2-3м с запада, ночь, ближайшее укрытие 6 часов.',
                    ],
                    'az' => [
                        'situation'           => 'Siz taxta və polad rulonlarla yüklənmiş 9 000 DWT gəminin kapitanısınız. Bofort 5-6 zamanı göyərtə taxta bağlamaları boşalıb, demetlər sancağa 0,5m sürüşüb. 3 dərəcə sancaq meyili yaranıb.',
                        'your_position'       => 'Körpüüstü, gecə növbəsi. Birinci stürman çağırılıb.',
                        'available_resources'  => 'Yükləmə kompüteri, yük bağlama təlimatı, bağlama avadanlığı, ballast sistemi.',
                        'current_conditions'   => 'Bofort 5-6, 2-3m dalğa qərbdən, gecə, ən yaxın sığınacaq 6 saat.',
                    ],
                ],
                'decision_prompt' => 'What immediate and follow-up actions will you take regarding the cargo shift and developing list? How do you assess the risk and what are your priorities?',
                'decision_prompt_i18n' => [
                    'tr' => 'Yük kayması ve gelişen meyil konusunda hangi acil ve takip önlemlerini alacaksınız? Riski nasıl değerlendiriyorsunuz ve öncelikleriniz nelerdir?',
                    'ru' => 'Какие немедленные и последующие действия вы предпримете в связи со смещением груза и нарастающим креном? Как оцениваете риск и каковы приоритеты?',
                    'az' => 'Yük sürüşməsi və inkişaf edən meyl ilə bağlı hansı təcili və sonrakı tədbirləri görəcəksiniz? Riski necə qiymətləndirirsiniz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'stability_assessment',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No stability check performed; ignores the 3-degree list as insignificant.',
                            '2' => 'Notices the list but makes no calculation; guesses that stability is still adequate.',
                            '3' => 'Runs a basic stability calculation on the loading computer to verify GM and GZ; monitors list trend.',
                            '4' => 'Systematic assessment: loading computer calculation with shifted cargo position, free surface effect evaluation, GZ curve review, monitors list rate of change, considers counter-ballasting with calculations.',
                            '5' => 'Expert stability management: full recalculation with worst-case shift scenario, evaluates GZ curve for dynamic rolling (roll-back angle), assesses structural loading, plans counter-ballast sequence with incremental monitoring, considers requesting shore-based naval architect support.',
                        ],
                    ],
                    [
                        'axis'   => 'risk_prioritization',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Does not recognise the progressive nature of the hazard; treats it as a routine matter.',
                            '2' => 'Understands cargo shift is dangerous but cannot articulate a clear priority order for response.',
                            '3' => 'Correct priority: (1) assess stability, (2) alter course/speed to reduce rolling, (3) evaluate re-lashing feasibility vs. seeking shelter.',
                            '4' => 'Comprehensive risk prioritization: immediate course change to reduce beam seas, stability assessment, weather forecast analysis, shelter option plotted, crew safety assessment before any deck work, coast guard pre-notification.',
                            '5' => 'Expert-level: develops a decision tree — if list stable and weather improving, proceed with monitoring; if list increasing, seek shelter immediately; if list exceeds X degrees, issue PAN-PAN; all options pre-planned with trigger criteria; company/DPA notified.',
                        ],
                    ],
                    [
                        'axis'   => 'corrective_action_planning',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No corrective actions planned; waits to see if the situation resolves itself.',
                            '2' => 'Plans to re-lash cargo but without considering weather conditions, crew safety, or timing.',
                            '3' => 'Plans counter-ballasting and re-lashing for when weather allows; crew safety measures identified.',
                            '4' => 'Structured corrective plan: counter-ballast as immediate measure (with calculations), re-lashing planned for improved conditions with full safety precautions (lifelines, PPE, buddy system), shelter approach underway.',
                            '5' => 'Comprehensive action plan: phased counter-ballast with continuous monitoring, detailed re-lashing plan with safety officer oversight, abort criteria for deck work, shelter arrival plan with port agent coordination, post-incident documentation and ISM non-conformity report initiated.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No stability calculation performed', 'severity' => 'critical'],
                    ['flag' => 'No consideration of worsening weather', 'severity' => 'major'],
                    ['flag' => 'No decision on seeking shelter', 'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'CSS Code',
                    'IMO Cargo Securing Manual guidelines',
                    'Stability booklet',
                    'SOLAS Chapter VI',
                ],
                'red_flags_json' => [
                    'Sending crew on deck without safety assessment',
                    'Ignoring list trend',
                    'No stability calculation',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 5 — CREW_LEAD — Young officer discipline problem
            // ══════════════════════════════════════════════════════════════
            'SHORT_SEA_S05_CREW_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 7,500 DWT coastal tanker. The Chief Officer reports that the newly promoted Third Officer (25 years old, first command-level position) has been repeatedly late for watch relief, was found sleeping during a cargo watch, and has been disrespectful to senior ratings. Two ABs have complained formally. The 3/O claims he is overworked and that the C/O assigns him unfair duties.',
                        'your_position'       => 'Master\'s office. C/O and 3/O available for meeting.',
                        'available_resources'  => 'Company HR policy, ISM Code procedures, ship\'s logbook, previous performance records, DPA contact.',
                        'current_conditions'   => 'Vessel on regular coastal run, no immediate operational pressure, next port in 18 hours.',
                    ],
                    'tr' => [
                        'situation'           => '7.500 DWT kıyı tankerinin kaptanısınız. Birinci zabit, yeni terfi etmiş Üçüncü Zabitin (25 yaşında, ilk komuta pozisyonu) vardiya teslimlerine defalarca geç geldiğini, yük vardiyasında uyurken yakalandığını ve kıdemli tayfalara saygısız davrandığını bildiriyor. İki Usta Gemici resmi şikayette bulunmuş. 3. zabit fazla çalıştırıldığını ve Birinci Zabitin kendisine haksız görevler verdiğini iddia ediyor.',
                        'your_position'       => 'Kaptan ofisi. Birinci zabit ve 3. zabit görüşme için müsait.',
                        'available_resources'  => 'Şirket İK politikası, ISM Kodu prosedürleri, gemi jurnal defteri, önceki performans kayıtları, DPA irtibatı.',
                        'current_conditions'   => 'Gemi rutin kıyı seferinde, acil operasyonel baskı yok, sonraki liman 18 saat sonra.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан прибрежного танкера 7 500 DWT. Старпом сообщает: новый 3-й помощник (25 лет, первая командная должность) систематически опаздывает на вахту, был найден спящим на грузовой вахте и грубит старшим матросам. 3-й помощник утверждает, что перегружен.',
                        'your_position'       => 'Кабинет капитана. Старпом и 3-й помощник доступны.',
                        'available_resources'  => 'Кадровая политика компании, ISM процедуры, судовой журнал, записи о работе, контакт DPA.',
                        'current_conditions'   => 'Рутинный каботажный рейс, следующий порт через 18 часов.',
                    ],
                    'az' => [
                        'situation'           => 'Siz 7 500 DWT sahilboyu tankerin kapitanısınız. Birinci stürman bildirir ki, yeni 3-cü stürman (25 yaş, ilk komanda vəzifəsi) növbəyə gecikir, yük növbəsində yatmış tapılıb və böyük matroslarla hörmətsiz davranır. 3-cü stürman isə həddindən artıq iş yükündən şikayət edir.',
                        'your_position'       => 'Kapitan ofisi. Birinci və 3-cü stürman görüş üçün hazırdır.',
                        'available_resources'  => 'Şirkət HR siyasəti, ISM prosedurları, jurnal, performans qeydləri, DPA kontaktı.',
                        'current_conditions'   => 'Rutin sahilboyu reys, növbəti liman 18 saatdan sonra.',
                    ],
                ],
                'decision_prompt' => 'How will you handle this situation? Describe your approach to investigating the complaints, addressing the 3/O\'s behavior, and resolving the underlying issues.',
                'decision_prompt_i18n' => [
                    'tr' => 'Bu durumu nasıl ele alacaksınız? Şikayetlerin araştırılması, 3. zabitin davranışlarının ele alınması ve altta yatan sorunların çözülmesine yaklaşımınızı tanımlayın.',
                    'ru' => 'Как вы справитесь с этой ситуацией? Опишите подход к расследованию жалоб, коррекции поведения 3-го помощника и устранению первопричин.',
                    'az' => 'Bu vəziyyəti necə həll edəcəksiniz? Şikayətlərin araşdırılması, 3-cü stürmanın davranışının düzəldilməsi və əsas problemlərin həllinə yanaşmanızı təsvir edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'leadership_approach',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Immediately punishes the 3/O without investigation; authoritarian approach with no mentoring or development consideration.',
                            '2' => 'Talks to the 3/O but only to issue a warning; does not explore root causes or offer constructive guidance.',
                            '3' => 'Holds separate meetings with C/O and 3/O; listens to both sides before deciding; sets clear expectations.',
                            '4' => 'Balanced leadership: investigates thoroughly, considers the 3/O\'s youth and inexperience, identifies both disciplinary and developmental needs, creates a structured improvement plan.',
                            '5' => 'Exemplary leadership: fair investigation of all parties, identifies systemic factors (workload, mentoring gaps), combines appropriate accountability with mentoring plan, sets up regular follow-up, models the leadership culture expected in ISM framework.',
                        ],
                    ],
                    [
                        'axis'   => 'conflict_resolution',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Takes sides without hearing all parties; escalates the conflict rather than resolving it.',
                            '2' => 'Hears both sides but fails to address the underlying tension between C/O and 3/O or the crew complaints.',
                            '3' => 'Mediates between C/O and 3/O; addresses the AB complaints; works toward a resolution that both parties can accept.',
                            '4' => 'Effective mediation: gets to root cause (workload? attitude? poor onboarding?), addresses each complaint specifically, rebuilds working relationship between C/O and 3/O, communicates resolution to complaining ABs.',
                            '5' => 'Expert conflict resolution: addresses individual complaints, systemic issues (watch schedule, mentoring), and interpersonal dynamics; facilitates a constructive conversation between C/O and 3/O; follows up with crew to confirm resolution; documents agreements.',
                        ],
                    ],
                    [
                        'axis'   => 'procedural_compliance',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No documentation; no reference to company procedures or regulatory requirements.',
                            '2' => 'Aware that documentation is needed but only makes informal notes; does not check rest hour compliance.',
                            '3' => 'Documents the complaints and meetings; checks rest hour records; refers to company disciplinary procedure.',
                            '4' => 'Full procedural compliance: formal written record, rest hour analysis (STCW/MLC), company HR policy followed, DPA informed if required, logbook entries for sleeping on watch.',
                            '5' => 'Comprehensive compliance: formal investigation documented per ISM, rest hours analysed against STCW and MLC requirements, company HR and DPA notified, formal warning issued with documented improvement plan, logbook entries, consideration of flag state reporting requirements for sleeping on watch (safety concern).',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No investigation of both sides', 'severity' => 'critical'],
                    ['flag' => 'No documentation of issues', 'severity' => 'major'],
                    ['flag' => 'No consideration of fatigue/workload factors', 'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'ISM Code Section 6',
                    'MLC 2006 (rest hours)',
                    'Company disciplinary procedures',
                    'STCW rest hour requirements',
                ],
                'red_flags_json' => [
                    'One-sided judgment without hearing 3/O',
                    'No documentation',
                    'Ignoring potential fatigue issue',
                    'Threatening dismissal without due process',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 6 — AUTO_DEP — ECDIS update error + chart warning
            // ══════════════════════════════════════════════════════════════
            'SHORT_SEA_S06_AUTO_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 10,000 DWT container feeder. During pre-departure checks, the Second Officer reports that the latest ECDIS chart update failed to install correctly. Several charts for your intended route show \'Update Incomplete\' warnings. The backup ECDIS has the same issue. Paper charts on board are 4 months out of date. Departure is scheduled in 2 hours and the charterer has a strict schedule.',
                        'your_position'       => 'Bridge, pre-departure preparation. 2/O handling chart corrections.',
                        'available_resources'  => 'ECDIS with partial update, paper chart folio (4 months old), NtM (Notices to Mariners) file, chart agent contact, IT support from company.',
                        'current_conditions'   => 'Fine weather, vessel ready for sea in all other respects.',
                    ],
                    'tr' => [
                        'situation'           => '10.000 DWT konteyner besleyici geminin kaptanısınız. Kalkış öncesi kontrollerde İkinci Zabit, son ECDIS harita güncellemesinin doğru yüklenmediğini bildiriyor. Planlanan rotanızdaki birkaç haritada \'Güncelleme Tamamlanmadı\' uyarısı var. Yedek ECDIS\'te de aynı sorun mevcut. Gemideki kağıt haritalar 4 aydır güncellenmemiş. Kalkış 2 saat içinde planlanmış ve kiracının katı bir tarifesi var.',
                        'your_position'       => 'Köprüüstü, kalkış hazırlığı. 2. zabit harita düzeltmeleriyle ilgileniyor.',
                        'available_resources'  => 'Kısmi güncellemeli ECDIS, kağıt harita folyo (4 aylık), NtM (Denizcilere Bildirimler) dosyası, harita acentesi irtibatı, şirket IT desteği.',
                        'current_conditions'   => 'Güzel hava, gemi diğer tüm açılardan denize hazır.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан контейнерного фидера 10 000 DWT. При предотходных проверках 2-й помощник сообщает, что последнее обновление карт ЭКНИС установилось некорректно. На обоих ЭКНИС предупреждения. Бумажные карты не корректировались 4 месяца. Выход через 2 часа, фрахтователь требует соблюдения графика.',
                        'your_position'       => 'Мостик, подготовка к отходу. 2-й помощник занимается картами.',
                        'available_resources'  => 'ЭКНИС с частичным обновлением, бумажные карты (4 мес), ИМ, контакт картографического агента, IT компании.',
                        'current_conditions'   => 'Хорошая погода, судно готово к выходу во всех остальных отношениях.',
                    ],
                    'az' => [
                        'situation'           => 'Siz 10 000 DWT konteyner gəmisinin kapitanısınız. Çıxış yoxlamalarında 2-ci stürman ECDIS xəritə yeniləməsinin düzgün qurulmadığını bildirir. Hər iki ECDIS-də xəbərdarlıq var. Kağız xəritələr 4 aydır yenilənməyib. Çıxış 2 saata planlaşdırılıb.',
                        'your_position'       => 'Körpüüstü, çıxış hazırlığı. 2-ci stürman xəritələrlə məşğuldur.',
                        'available_resources'  => 'Qismən yenilənmiş ECDIS, kağız xəritələr, DDB faylı, xəritə agenti, şirkət IT dəstəyi.',
                        'current_conditions'   => 'Yaxşı hava, gəmi bütün digər cəhətdən dənizə hazırdır.',
                    ],
                ],
                'decision_prompt' => 'What will you do about the ECDIS chart update failure with departure in 2 hours? How do you balance the schedule pressure with navigation safety requirements?',
                'decision_prompt_i18n' => [
                    'tr' => 'Kalkışa 2 saat kala ECDIS harita güncelleme arızası hakkında ne yapacaksınız? Tarife baskısını navigasyon güvenliği gereksinimleriyle nasıl dengeliyorsunuz?',
                    'ru' => 'Что вы будете делать с ошибкой обновления карт ЭКНИС при выходе через 2 часа? Как сбалансируете давление графика с требованиями навигационной безопасности?',
                    'az' => 'Çıxışa 2 saat qala ECDIS xəritə yeniləmə xətası ilə nə edəcəksiniz? Cədvəl təzyiqini naviqasiya təhlükəsizliyi tələbləri ilə necə balanslaşdırırsınız?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'regulatory_compliance',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Ignores ECDIS warnings and sails with known chart deficiency; no awareness of SOLAS requirements.',
                            '2' => 'Recognises the problem but decides to sail anyway, assuming the partial update is "good enough."',
                            '3' => 'Understands SOLAS V requirements for up-to-date charts; delays departure until a solution is found or implements a documented mitigation plan.',
                            '4' => 'Systematic compliance: verifies which specific chart cells are affected, assesses whether the route can be safely navigated with available information, documents the deficiency and mitigation, notifies company.',
                            '5' => 'Full regulatory awareness: identifies specific SOLAS V Reg 19 and MSC.232(82) requirements, determines that sailing without compliant ECDIS requires corrected paper charts as backup, initiates NtM correction of paper charts, contacts chart agent and company IT for ECDIS resolution, documents everything per ISM.',
                        ],
                    ],
                    [
                        'axis'   => 'problem_solving',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No attempt to resolve the issue; either sails as-is or waits passively for someone else to fix it.',
                            '2' => 'Tries one solution (e.g., rebooting ECDIS) and gives up; does not explore alternatives.',
                            '3' => 'Systematic troubleshooting: attempts reinstallation, contacts company IT, begins manual NtM correction of paper charts as parallel backup.',
                            '4' => 'Multi-pronged approach: ECDIS troubleshooting (reinstall, USB update, remote IT support), paper chart NtM correction started, chart agent contacted for replacement data, assesses scope of affected charts vs. route.',
                            '5' => 'Expert problem-solving: parallel work streams — 2/O on ECDIS reinstallation with company IT remote access, 3/O on priority paper chart corrections for the first leg, chart agent arranging emergency update delivery, Master assesses go/no-go decision with timeline, considers partial route departure if first leg charts are unaffected.',
                        ],
                    ],
                    [
                        'axis'   => 'decision_under_commercial_pressure',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Caves to commercial pressure immediately; sails with known safety deficiency to keep the schedule.',
                            '2' => 'Hesitates but ultimately prioritises schedule over safety; makes excuses rather than a safety-based decision.',
                            '3' => 'Makes a safety-first decision and communicates delay to charterer/agent; accepts the commercial consequence.',
                            '4' => 'Balanced approach: assesses minimum safe requirements, communicates proactively with charterer about realistic timeline, proposes mitigation (e.g., delayed departure by X hours with expected resolution), documents the decision rationale.',
                            '5' => 'Exemplary decision-making: clear safety threshold defined, charterer informed early with transparent reasoning, company operations notified, explores creative solutions to minimise delay (e.g., partial correction for first leg, full correction en route), documents commercial impact for charterer claims if any.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No attempt to resolve ECDIS issue before sailing', 'severity' => 'critical'],
                    ['flag' => 'No NtM review for paper chart correction', 'severity' => 'major'],
                    ['flag' => 'No notification to company about navigation equipment deficiency', 'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter V Reg 19',
                    'IMO MSC.232(82) ECDIS performance standards',
                    'ISM Code',
                    'Flag state ECDIS carriage requirements',
                ],
                'red_flags_json' => [
                    'Sailing with known chart deficiency without mitigation',
                    'No alternative navigation plan',
                    'Prioritizing schedule over safety',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 7 — CRISIS_RSP — Fishing vessel collision / damage ctrl
            // ══════════════════════════════════════════════════════════════
            'SHORT_SEA_S07_CRIS_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 8,500 DWT general cargo vessel transiting the Aegean Sea at night. Despite maintaining proper lookout, a small fishing vessel without AIS and showing only a dim stern light has been struck on your port bow. The fishing vessel is capsized alongside with 3-4 crew visible in the water. Your vessel has sustained bow plating damage above the waterline with a small crack extending below. The forepeak tank is slowly flooding.',
                        'your_position'       => 'Bridge, you assumed command at the moment of collision.',
                        'available_resources'  => 'Rescue boat, lifebuoys with lights, MOB equipment, emergency pumps, damage control equipment, VHF/DSC for SAR coordination, full crew available for emergency stations.',
                        'current_conditions'   => 'Clear night, calm seas, water temp 18°C, nearest coast guard station 15 NM.',
                    ],
                    'tr' => [
                        'situation'           => '8.500 DWT genel kargo gemisinin kaptanısınız, gece Ege Denizi\'nde seyrüsefer yapıyorsunuz. Uygun gözcülük yapılmasına rağmen AIS\'siz ve sadece sönük bir kıç feneri gösteren küçük bir balıkçı teknesi iskele baş tarafınızdan çarpılmıştır. Balıkçı teknesi devrilmiş halde yanınızda ve suda 3-4 mürettebat görünüyor. Geminizin baş kaplamasında su hattı üstünde hasar ve su hattı altına uzanan küçük bir çatlak var. Baş pik tankı yavaşça su alıyor.',
                        'your_position'       => 'Köprüüstü, çarpışma anında komutayı aldınız.',
                        'available_resources'  => 'Kurtarma botu, ışıklı can simitleri, MOB ekipmanı, acil pompalar, hasar kontrol ekipmanı, SAR koordinasyonu için VHF/DSC, tam mürettebat acil istasyonlar için müsait.',
                        'current_conditions'   => 'Açık gece, sakin deniz, su sıcaklığı 18°C, en yakın sahil güvenlik istasyonu 15 mil.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан грузового судна 8 500 DWT, ночью в Эгейском море столкнулись с малым рыболовным судном без АИС. Рыболовное судно перевернуто, 3-4 человека в воде. У вашего судна повреждение обшивки с трещиной ниже ватерлинии, форпик затапливается.',
                        'your_position'       => 'Мостик, приняли командование в момент столкновения.',
                        'available_resources'  => 'Спасательная шлюпка, спасательные круги, средства «человек за бортом», аварийные насосы, VHF/DSC, полный экипаж.',
                        'current_conditions'   => 'Ясная ночь, штиль, температура воды 18°C, ближайший пост БО 15 миль.',
                    ],
                    'az' => [
                        'situation'           => 'Siz 8 500 DWT yük gəmisinin kapitanısınız, gecə Egey dənizində kiçik balıqçı gəmisi ilə toqquşmusunuz. Balıqçı gəmisi çevrilib, 3-4 nəfər sudadır. Gəminizin burun hissəsində zədə var, forpik tankı yavaşca su alır.',
                        'your_position'       => 'Körpüüstü, toqquşma anında komandanı qəbul etdiniz.',
                        'available_resources'  => 'Xilasetmə qayığı, işıqlı xilas halqaları, MOB avadanlığı, təcili nasoslar, VHF/DSC, tam heyət.',
                        'current_conditions'   => 'Aydın gecə, sakit dəniz, su temperaturu 18°C, ən yaxın sahil mühafizəsi 15 mil.',
                    ],
                ],
                'decision_prompt' => 'What are your immediate priorities and actions following the collision? How do you manage the rescue of survivors, damage to your vessel, and reporting obligations simultaneously?',
                'decision_prompt_i18n' => [
                    'tr' => 'Çarpışmanın ardından acil öncelikleriniz ve eylemleriniz nelerdir? Kazazedelerin kurtarılması, geminizin hasarı ve raporlama yükümlülüklerini eş zamanlı nasıl yönetiyorsunuz?',
                    'ru' => 'Каковы ваши приоритеты и действия после столкновения? Как одновременно управляете спасением, контролем повреждений и обязательствами по докладыванию?',
                    'az' => 'Toqquşmadan sonra təcili prioritetləriniz və hərəkətləriniz nələrdir? Xilasetmə, gəmi zədəsi və hesabat öhdəliklərini eyni anda necə idarə edirsiniz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'rescue_response',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No immediate rescue action; focuses only on own vessel damage or considers leaving the scene.',
                            '2' => 'Throws lifebuoys but does not launch rescue boat or organise a systematic rescue effort.',
                            '3' => 'Stops engines, deploys lifebuoys with lights, launches rescue boat with trained crew, maintains visual contact with persons in water.',
                            '4' => 'Systematic rescue: MOB alarm sounded, lifebuoys deployed, rescue boat launched with crew briefed on hypothermia risks, Williamson turn or equivalent executed, spotlight on survivors, headcount tracked.',
                            '5' => 'Expert rescue response: immediate MOB alarm, all stop, lifebuoys and dan buoy deployed, rescue boat launched within minutes with thermal blankets/first aid, systematic search pattern for any unseen persons, survivors recovered and medical assessment begun, continuous communication between rescue boat and bridge.',
                        ],
                    ],
                    [
                        'axis'   => 'damage_control',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No damage assessment or control measures initiated for own vessel.',
                            '2' => 'Aware of flooding but takes no structured action; does not assess extent of damage.',
                            '3' => 'Sends team to assess forepeak, starts emergency pumping, monitors flood rate and trim changes.',
                            '4' => 'Structured damage control: forepeak inspected, crack extent assessed, emergency pump deployed, sounding of adjacent tanks, stability impact calculated, considers collision bulkhead integrity.',
                            '5' => 'Comprehensive: immediate sounding of forepeak and all forward tanks, emergency pump running, crack monitored for propagation, stability calculations updated with flooding scenario, collision bulkhead checked for integrity, considers cement box or emergency patching, contingency plan for heading to nearest port if flooding worsens.',
                        ],
                    ],
                    [
                        'axis'   => 'communication_and_reporting',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No external communication; does not report the collision or request assistance.',
                            '2' => 'Contacts coast guard but provides incomplete information; does not follow GMDSS procedures.',
                            '3' => 'Contacts coast guard with position and situation summary; requests SAR assistance; notifies company.',
                            '4' => 'Full communication: MAYDAY or PAN-PAN as appropriate, coast guard SAR notification with full details (position, number of survivors, vessel damage status), company DPA notified, NAVAREA warning requested, VDR data preserved.',
                            '5' => 'Expert communications: DSC distress alert if warranted, coast guard SAR coordination with precise details, company DPA and P&I Club notified, VDR and all evidence preserved (bridge log, ECDIS data, radar recordings), NAVAREA/NAVTEX warning requested, nearby vessels alerted on VHF 16, flag state collision report initiated.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No immediate man overboard / rescue action', 'severity' => 'critical'],
                    ['flag' => 'No damage assessment of own vessel', 'severity' => 'critical'],
                    ['flag' => 'No coast guard / SAR notification', 'severity' => 'critical'],
                    ['flag' => 'No GMDSS distress procedures', 'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'COLREG Rule 8 (action to avoid collision)',
                    'SOLAS Chapter III (life-saving)',
                    'SAR Convention',
                    'COLREG Rule 2 (responsibility)',
                    'SOLAS Chapter II-1 (damage stability)',
                ],
                'red_flags_json' => [
                    'Leaving scene without rescue attempt',
                    'No damage control for own vessel',
                    'Failure to report collision',
                    'Not preserving evidence (logbook, VDR)',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 8 — TRADEOFF — Time pressure vs weather deterioration
            // ══════════════════════════════════════════════════════════════
            'SHORT_SEA_S08_TRADE_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 9,200 DWT general cargo vessel loaded with project cargo (heavy lifts). You are 14 hours from your discharge port in the Black Sea. Weather forecast shows a significant deterioration: wind increasing to Beaufort 8-9 from NW within 8 hours, lasting 36-48 hours. You can reach port before the worst weather if you maintain full speed, but this means arriving in marginal conditions for berthing. Alternatively, you can reduce speed and seek shelter, but this will delay arrival by 2-3 days. The project cargo is urgently needed — delay penalties are USD 8,000/day.',
                        'your_position'       => 'Bridge, reviewing weather forecast with C/O.',
                        'available_resources'  => 'Weather routing updates, port agent, charterer contact, detailed port approach charts, cargo securing additional lashings available.',
                        'current_conditions'   => 'Currently Beaufort 5, wind NW, sea state moderate, vessel handling well.',
                    ],
                    'tr' => [
                        'situation'           => '9.200 DWT ağır yük gemisinin kaptanısınız, proje kargosu (ağır kaldırma) yüklü. Karadeniz\'deki boşaltma limanınıza 14 saat mesafedesiniz. Hava tahmini ciddi kötüleşme gösteriyor: 8 saat içinde KB\'den Beaufort 8-9\'a yükselen rüzgar, 36-48 saat sürecek. Tam hızla devam ederseniz en kötü havadan önce limana ulaşabilirsiniz ama bu yanaşma için sınırdaki koşullarda varış demektir. Alternatif olarak hız düşürüp sığınak arayabilirsiniz ama bu varışı 2-3 gün geciktirir. Proje kargosu acil olarak bekleniyor — gecikme cezası günlük 8.000 USD.',
                        'your_position'       => 'Köprüüstü, Birinci Zabitle hava tahminini değerlendiriyorsunuz.',
                        'available_resources'  => 'Hava rotalama güncellemeleri, liman acentesi, kiracı irtibatı, detaylı liman yaklaşma haritaları, yük emniyeti için ek bağlama malzemesi mevcut.',
                        'current_conditions'   => 'Mevcut Beaufort 5, rüzgar KB, orta deniz durumu, gemi iyi seyrediyor.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан грузового судна 9 200 DWT с тяжеловесным проектным грузом. До порта выгрузки в Черном море 14 часов. Прогноз: усиление ветра до Бофорт 8-9 через 8 часов на 36-48 часов. Можно успеть на полном ходу, но швартовка будет в предельных условиях. Укрытие задержит на 2-3 дня. Штраф за простой — 8 000 USD/день.',
                        'your_position'       => 'Мостик, анализ прогноза со старпомом.',
                        'available_resources'  => 'Погодная маршрутизация, агент, фрахтователь, карты подхода, дополнительный крепёж.',
                        'current_conditions'   => 'Бофорт 5, ветер СЗ, умеренное волнение, судно управляется хорошо.',
                    ],
                    'az' => [
                        'situation'           => 'Siz ağır yük layihə kargosu ilə yüklənmiş 9 200 DWT gəminin kapitanısınız. Qara dənizdəki boşaltma limanına 14 saatlıq məsafədəsiniz. Hava proqnozu pisləşmə göstərir: 8 saat ərzində Bofort 8-9. Tam sürətlə gedərsinizsə çatarsınız, amma yanaşma şərtləri həddindədir. Sığınacaq 2-3 gün gecikmə deməkdir. Cərimə: 8 000 USD/gün.',
                        'your_position'       => 'Körpüüstü, birinci stürmanla hava proqnozunu nəzərdən keçirirsiniz.',
                        'available_resources'  => 'Hava marşrutlaşdırması, agent, icarəçi, yaxınlaşma xəritələri, əlavə bağlama materialı.',
                        'current_conditions'   => 'Bofort 5, külək ŞQ, orta dalğalanma, gəmi yaxşı idarə olunur.',
                    ],
                ],
                'decision_prompt' => 'What is your decision: press on for port arrival or seek shelter? Explain your risk assessment, the factors you considered, and your communication plan.',
                'decision_prompt_i18n' => [
                    'tr' => 'Kararınız nedir: limana devam mı yoksa sığınak mı? Risk değerlendirmenizi, değerlendirdiğiniz faktörleri ve iletişim planınızı açıklayın.',
                    'ru' => 'Каково ваше решение: следовать в порт или искать укрытие? Объясните оценку рисков, факторы и план коммуникации.',
                    'az' => 'Qərarınız nədir: limana davam etmək yoxsa sığınacaq axtarmaq? Risk qiymətləndirmənizi, nəzərə aldığınız faktorları və əlaqə planınızı izah edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'risk_benefit_analysis',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Makes a gut-feel decision with no structured analysis; either blindly presses on or shelters without considering the trade-offs.',
                            '2' => 'Considers either the weather or the commercial penalty but not both; incomplete risk picture.',
                            '3' => 'Analyses both weather risk and commercial penalty; weighs the probability of safe berthing against delay costs; makes a reasoned decision.',
                            '4' => 'Structured risk-benefit: quantifies the weather window (8 hours to Bf 8-9 vs. 14 hours transit), calculates margin for error, assesses berthing feasibility at forecast wind/sea conditions, factors in cargo securing adequacy for heavy weather.',
                            '5' => 'Expert analysis: full decision matrix — weather progression timeline vs. ETA at various speeds, berthing limits for the specific port (wind direction/speed limits), cargo lashing adequacy for Bf 8-9, crew fatigue factor for extended heavy weather, financial comparison (penalty vs. fuel + risk), documented rationale for chosen option.',
                        ],
                    ],
                    [
                        'axis'   => 'seamanship_judgment',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Shows poor seamanship: attempts berthing in clearly dangerous conditions or makes a decision with no regard for vessel type and cargo.',
                            '2' => 'Basic seamanship awareness but underestimates the risk of heavy weather berthing with heavy lift cargo.',
                            '3' => 'Good seamanship: checks port berthing limits, considers vessel handling at Bf 8-9, recognises that heavy lift cargo increases risk of damage during berthing.',
                            '4' => 'Strong seamanship: evaluates port exposure to NW winds, considers tug availability and capability, assesses vessel manoeuvrability in forecast conditions, has abort criteria for approach, checks cargo securing for potentially increased transit time.',
                            '5' => 'Exemplary seamanship: comprehensive evaluation — port exposure, tug capability and availability, pilot willingness to board in forecast conditions, mooring line strength for forecast conditions, cargo securing re-inspection, vessel stress monitoring for heavy weather, contingency shelter identified along route, considers anchoring outside port to wait for weather window.',
                        ],
                    ],
                    [
                        'axis'   => 'stakeholder_communication',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No communication with any stakeholder; makes decision in isolation.',
                            '2' => 'Informs one party (e.g., agent) but does not coordinate with charterer, company, or port.',
                            '3' => 'Communicates with agent and charterer about the situation; provides ETA update; explains the dilemma.',
                            '4' => 'Proactive communication: port agent (berthing conditions, tug availability), charterer (delay risk and mitigation options), company operations (weather routing support, commercial guidance), port authority (berth/pilot availability in forecast conditions).',
                            '5' => 'Comprehensive stakeholder management: early notification to all parties with clear options (A: press on with risks, B: shelter with delay), requests port-specific weather data from agent, confirms pilot/tug availability in forecast conditions, charterer informed with documented risk assessment, company given transparent cost-benefit analysis, decision communicated with rationale to all stakeholders.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No weather forecast analysis', 'severity' => 'critical'],
                    ['flag' => 'No cargo securing check for heavy weather', 'severity' => 'major'],
                    ['flag' => 'No communication with charterer about options', 'severity' => 'major'],
                    ['flag' => 'No consideration of crew safety in heavy weather berthing', 'severity' => 'critical'],
                ],
                'expected_references_json' => [
                    'Heavy weather procedures',
                    'Company SMS voyage planning',
                    'Charter party weather clause',
                    'Cargo securing manual for heavy lifts',
                ],
                'red_flags_json' => [
                    'Attempting berthing in dangerous conditions for commercial gain',
                    'No weather monitoring or updates',
                    'Ignoring crew safety for schedule',
                ],
            ],

        ];
    }
}
