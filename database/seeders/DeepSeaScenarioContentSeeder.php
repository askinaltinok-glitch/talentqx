<?php

namespace Database\Seeders;

use App\Models\MaritimeScenario;
use Illuminate\Database\Seeder;

/**
 * Populate DEEP_SEA scenarios with production-quality content.
 *
 * Idempotent: updates existing rows by scenario_code.
 * Run: php82 artisan db:seed --class=DeepSeaScenarioContentSeeder
 */
class DeepSeaScenarioContentSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = $this->getScenarios();

        foreach ($scenarios as $code => $data) {
            MaritimeScenario::where('scenario_code', $code)->update($data);
            $this->command->info("Updated: {$code}");
        }

        $this->command->info('DEEP_SEA scenario content seeded (8 scenarios).');
    }

    private function getScenarios(): array
    {
        return [

            // ────────────────────────────────────────────────────────────
            // SLOT 1 — NAV_COMPLEX — Tropical storm on Pacific crossing
            // ────────────────────────────────────────────────────────────
            'DEEP_SEA_S01_NAV_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 65,000 DWT bulk carrier on Pacific crossing. You receive a tropical storm warning intersecting your intended track within 18 hours.',
                        'your_position'       => 'Ocean passage, 1,200 NM from nearest safe harbor.',
                        'available_resources' => 'Weather routing system, ECDIS, engine at 85% MCR.',
                        'current_conditions'  => 'Wind force 6 increasing.',
                    ],
                    'tr' => [
                        'situation'           => 'Pasifik geçişi yapan 65.000 DWT dökme yük gemisinin kaptanısınız. 18 saat içinde rotanızla kesişecek tropik fırtına uyarısı aldınız.',
                        'your_position'       => 'Açık deniz geçişi, en yakın emniyetli limana 1.200 mil.',
                        'available_resources' => 'Weather routing sistemi, ECDIS, ana makine %85 MCR.',
                        'current_conditions'  => 'Rüzgar kuvvet 6 artış eğiliminde.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан балкера 65 000 DWT в Тихоокеанском переходе. Получено штормовое предупреждение о тропическом шторме, пересекающем ваш маршрут через 18 часов.',
                        'your_position'       => 'Открытый океан, 1 200 миль до ближайшего укрытия.',
                        'available_resources' => 'Погодная маршрутизация, ЭКНИС, двигатель 85% MCR.',
                        'current_conditions'  => 'Ветер 6 баллов, усиливается.',
                    ],
                    'az' => [
                        'situation'           => 'Sakit okean keçidində 65.000 DWT-lik dökme yük gəmisinin kapitanısınız. 18 saat ərzində marşrutunuzla kəsişəcək tropik fırtına xəbərdarlığı aldınız.',
                        'your_position'       => 'Açıq okean, ən yaxın sığınacağa 1.200 mil.',
                        'available_resources' => 'Hava marşrutlaşdırma sistemi, ECDIS, mühərrik 85% MCR.',
                        'current_conditions'  => 'Külək 6 bal, güclənir.',
                    ],
                ],
                'decision_prompt'      => 'How will you respond to the tropical storm warning? Describe your routing strategy, speed adjustments, and preparatory actions.',
                'decision_prompt_i18n' => [
                    'tr' => 'Tropik fırtına uyarısına nasıl yanıt vereceksiniz? Rotalama stratejinizi, hız ayarlamalarınızı ve hazırlık eylemlerinizi açıklayın.',
                    'ru' => 'Как вы отреагируете на предупреждение о тропическом шторме? Опишите стратегию маршрутизации, корректировку скорости и подготовительные действия.',
                    'az' => 'Tropik fırtına xəbərdarlığına necə cavab verəcəksiniz? Marşrutlaşdırma strategiyanızı, sürət düzəlişlərini və hazırlıq tədbirlərini təsvir edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'weather_route_strategy',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Ignores the tropical storm warning entirely and maintains original track with no routing adjustment.',
                            '2' => 'Acknowledges the storm but proposes only a minor course change without consulting weather routing data or analysing the storm track forecast.',
                            '3' => 'Reviews weather routing information, identifies a single alternative route to avoid the storm centre, and adjusts course accordingly.',
                            '4' => 'Develops multiple routing options using weather routing system and ECDIS, evaluates each for distance, fuel, and safety; selects the optimum with clear reasoning.',
                            '5' => 'Provides a comprehensive routing strategy integrating weather routing service advice, ECDIS overlays of storm uncertainty cone, staged decision points for re-assessment, and contingency routes if the storm track shifts unexpectedly.',
                        ],
                    ],
                    [
                        'axis'   => 'voyage_planning_depth',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No updated voyage plan; continues with the original plan despite the storm warning.',
                            '2' => 'Makes a verbal plan to alter course but does not update the ECDIS voyage plan or perform fuel/distance calculations.',
                            '3' => 'Updates the ECDIS voyage plan with a new route, performs basic fuel and ETA calculations, and informs the bridge team.',
                            '4' => 'Produces a revised voyage plan with waypoints, fuel consumption estimates for different speed options, safety contour checks, and communicates to company and charterer with revised ETA.',
                            '5' => 'Delivers a fully documented revised voyage plan including waypoints, fuel calculations at multiple speed scenarios, UKC checks on any new routing, heavy weather preparation checklist for cargo and deck, master\'s night orders updated, and formal notification to all stakeholders.',
                        ],
                    ],
                    [
                        'axis'   => 'risk_forecasting',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Shows no awareness of the risks posed by the approaching storm; does not consider wave height, wind speed, or cargo safety.',
                            '2' => 'Mentions the storm is a risk but does not quantify or plan for specific impacts on vessel, crew, or cargo.',
                            '3' => 'Identifies key risks (heavy seas, cargo shifting, structural stress) and takes basic precautions such as reducing speed and securing deck equipment.',
                            '4' => 'Conducts a structured risk assessment covering cargo securing review, ballast plan for heavy weather, crew safety measures, and establishes criteria for further action if conditions worsen.',
                            '5' => 'Performs a comprehensive risk forecast including worst-case storm track scenarios, cargo liquefaction/shift risk review, structural stress considerations at different headings, crew fatigue planning for extended heavy weather, and defines abort criteria to seek shelter.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No alternate routing plan',   'severity' => 'critical'],
                    ['flag' => 'No speed reduction discussion', 'severity' => 'major'],
                    ['flag' => 'No cargo securing review',     'severity' => 'critical'],
                ],
                'expected_references_json' => [
                    'Heavy Weather Procedures',
                    'SOLAS Chapter V',
                ],
                'red_flags_json' => [
                    'No weather avoidance action',
                    'Continuing on collision course with storm',
                ],
            ],

            // ────────────────────────────────────────────────────────────
            // SLOT 2 — CMD_SCALE — Long-voyage crew morale and watch-keeping
            // ────────────────────────────────────────────────────────────
            'DEEP_SEA_S02_CMD_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 75,000 DWT bulk carrier, 28 days into a 42-day ocean passage from Brazil to China. Crew morale is deteriorating. The Bosun reports increased tension between Filipino and Ukrainian crew members. Two crew members have requested to see you about personal problems. The Chief Officer reports that watch-keeping standards are slipping — the 2/O was found with no proper lookout posted during the 0400-0800 watch. Internet connectivity has been down for 5 days.',
                        'your_position'       => 'Master\'s office, morning meeting with C/O.',
                        'available_resources' => 'Thirty crew from mixed nationalities, DPA contact via satellite phone, ship\'s recreational facilities, welfare provisions, ISM documentation.',
                        'current_conditions'  => 'Mid-ocean, fair weather, routine operations, next port 14 days.',
                    ],
                    'tr' => [
                        'situation'           => '75.000 DWT dökme yük gemisinin kaptanısınız, Brezilya\'dan Çin\'e 42 günlük okyanus geçişinin 28. günündesiniz. Mürettebat morali düşüyor. Lostromo, Filipinli ve Ukraynalı mürettebat arasında artan gerginlik bildiriyor. İki mürettebat kişisel sorunları hakkında sizinle görüşmek istiyor. Birinci zabit vardiya standartlarının düştüğünü bildiriyor — 2. zabit 0400-0800 vardiyasında uygun gözcü koymamış halde bulunmuş. İnternet bağlantısı 5 gündür kesik.',
                        'your_position'       => 'Kaptan ofisi, Birinci Zabitle sabah toplantısı.',
                        'available_resources' => 'Karışık uyruklu 30 mürettebat, uydu telefonuyla DPA irtibatı, geminin dinlenme tesisleri, refah hükümleri, ISM belgeleri.',
                        'current_conditions'  => 'Açık okyanus, güzel hava, rutin operasyonlar, sonraki liman 14 gün.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан балкера 75 000 DWT, 28-й день 42-дневного перехода из Бразилии в Китай. Моральный дух экипажа падает, напряжение между филиппинскими и украинскими моряками.',
                        'your_position'       => 'Каюта капитана, утреннее совещание.',
                        'available_resources' => '30 человек экипажа, связь с DPA, рекреационные помещения, ISM документы.',
                        'current_conditions'  => 'Открытый океан, хорошая погода, до следующего порта 14 дней.',
                    ],
                    'az' => [
                        'situation'           => '75.000 DWT-lik dökme yük gəmisinin kapitanısınız, Braziliyadan Çinə 42 günlük keçidin 28-ci günündə. Ekipaj ruh düşkünlüyü, milli qruplar arasında gərginlik.',
                        'your_position'       => 'Kapitan ofisi, səhər görüşü.',
                        'available_resources' => '30 nəfər ekipaj, DPA əlaqəsi, istirahət imkanları, ISM sənədləri.',
                        'current_conditions'  => 'Açıq okean, yaxşı hava, növbəti liman 14 gün.',
                    ],
                ],
                'decision_prompt'      => 'How will you address the morale issues, the watch-keeping deficiency, and the intercultural tensions during the remaining 14 days of passage?',
                'decision_prompt_i18n' => [
                    'tr' => 'Kalan 14 günlük geçiş boyunca moral sorunlarını, vardiya eksikliğini ve kültürler arası gerginlikleri nasıl ele alacaksınız?',
                    'ru' => 'Как вы будете решать проблемы морального духа, нарушения вахтенной службы и межкультурную напряжённость в оставшиеся 14 дней перехода?',
                    'az' => 'Qalan 14 günlük keçid müddətində ruh düşkünlüyü, növbə qüsurları və mədəniyyətlərarası gərginlikləri necə həll edəcəksiniz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'crew_welfare_management',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Ignores morale problems entirely; takes no welfare action and dismisses concerns as normal at sea.',
                            '2' => 'Acknowledges low morale but only makes a single vague gesture such as promising shore leave, without a structured welfare plan.',
                            '3' => 'Implements basic welfare measures: organises a social event, addresses internet issue through company, and meets the two crew members individually.',
                            '4' => 'Creates a structured welfare programme for the remaining 14 days including scheduled recreational activities, fair internet access plan once restored, individual meetings with distressed crew, and delegation of welfare roles to senior officers.',
                            '5' => 'Develops a comprehensive welfare strategy: immediate individual counselling for the two crew, organised multicultural social events, escalates internet repair with company urgency, establishes a peer-support system, monitors morale indicators daily, and communicates transparently with the entire crew about the situation and plans.',
                        ],
                    ],
                    [
                        'axis'   => 'leadership_and_communication',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Shows no leadership initiative; remains in cabin and delegates all issues without follow-up.',
                            '2' => 'Addresses the watch-keeping issue with a reprimand only; no broader communication or team-building effort.',
                            '3' => 'Holds a meeting with senior officers to discuss the issues, addresses the 2/O privately, and makes a general announcement about expected standards.',
                            '4' => 'Demonstrates visible leadership: personally meets with the 2/O, addresses the Bosun about intercultural tensions, holds a crew meeting explaining expectations, and establishes an open-door policy for the remaining voyage.',
                            '5' => 'Exhibits exemplary leadership: conducts one-on-one sessions with key personnel, holds a transparent all-hands meeting acknowledging challenges, demonstrates cultural sensitivity in addressing tensions, assigns mentorship roles, establishes feedback channels, and leads by personal example through increased bridge and deck presence.',
                        ],
                    ],
                    [
                        'axis'   => 'watch_keeping_standards',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Takes no action on the 2/O watch-keeping failure; does not review or enforce bridge procedures.',
                            '2' => 'Issues a verbal warning to the 2/O but does not investigate root cause or check rest-hour compliance.',
                            '3' => 'Investigates the 2/O incident, reviews rest-hour records, addresses the deficiency formally, and reinforces bridge standing orders.',
                            '4' => 'Conducts a thorough investigation of the watch-keeping failure including rest-hour audit, issues a formal written warning, reviews and re-briefs all OOWs on bridge procedures, and implements spot checks for the remaining voyage.',
                            '5' => 'Performs a root-cause analysis (fatigue, morale, workload), audits rest hours for all watch-keepers, re-issues amended night orders, implements a bridge audit programme, considers temporary watch rotation adjustment, reports the non-conformity per ISM, and ensures corrective action is documented.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No action on watch-keeping deficiency',                'severity' => 'critical'],
                    ['flag' => 'No crew welfare intervention',                          'severity' => 'major'],
                    ['flag' => 'No cultural sensitivity in conflict resolution',         'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'MLC 2006 (seafarer welfare)',
                    'STCW rest hours',
                    'ISM Code Section 6 (resources and personnel)',
                    'Company crew welfare policy',
                ],
                'red_flags_json' => [
                    'Ignoring intercultural tension',
                    'No action on 2/O watch-keeping failure',
                    'Dismissing crew welfare concerns',
                ],
            ],

            // ────────────────────────────────────────────────────────────
            // SLOT 3 — TECH_DEPTH — Main engine fuel injector failure
            // ────────────────────────────────────────────────────────────
            'DEEP_SEA_S03_TECH_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 58,000 DWT bulk carrier, mid-Pacific, 8 days from the nearest port. The Chief Engineer reports that fuel injector failures on cylinders 2 and 5 have caused the main engine to run on only 4 of 6 cylinders. Maximum available speed has dropped from 12.5 to 8 knots. The C/E has spare injectors but replacing them at sea requires shutting down the engine for 6-8 hours. Weather is currently stable but a low-pressure system is forecast in 72 hours.',
                        'your_position'       => 'Bridge, morning status call with C/E.',
                        'available_resources' => 'Spare fuel injectors, engine room workshop, C/E with MAN B&W experience, satellite communication for technical support from manufacturer, weather routing service.',
                        'current_conditions'  => 'Mid-Pacific, calm seas, wind Force 3, next port 8 days at reduced speed.',
                    ],
                    'tr' => [
                        'situation'           => '58.000 DWT dökme yük gemisinin kaptanısınız, Pasifik ortasında, en yakın limana 8 gün mesafede. Başmühendis, 2 ve 5 numaralı silindirlerdeki yakıt enjektör arızalarının ana makinenin 6 silindirden sadece 4\'üyle çalışmasına neden olduğunu bildiriyor. Mümkün azami hız 12,5\'ten 8 knota düşmüş. Başmühendis\'te yedek enjektörler var ama denizde değişim, motorun 6-8 saat kapatılmasını gerektiriyor. Hava şu an stabil ama 72 saat sonra alçak basınç sistemi öngörülüyor.',
                        'your_position'       => 'Köprüüstü, Başmühendisle sabah durum görüşmesi.',
                        'available_resources' => 'Yedek yakıt enjektörleri, makine dairesi atölyesi, MAN B&W deneyimli Başmühendis, üretici teknik destek için uydu iletişimi, hava rotalama servisi.',
                        'current_conditions'  => 'Pasifik ortası, sakin deniz, rüzgar Kuvvet 3, düşük hızla sonraki liman 8 gün.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан балкера 58 000 DWT в середине Тихого океана, 8 дней до порта. Отказ топливных форсунок на цилиндрах 2 и 5 — двигатель работает на 4 из 6 цилиндров, скорость упала до 8 узлов.',
                        'your_position'       => 'Мостик, утренний доклад стармеха.',
                        'available_resources' => 'Запасные форсунки, мастерская МО, стармех с опытом MAN B&W, спутниковая связь с производителем, метеомаршрутизация.',
                        'current_conditions'  => 'Тихий океан, штиль, ветер 3 балла, низкое давление через 72 часа.',
                    ],
                    'az' => [
                        'situation'           => 'Sakit okeanın ortasında 58.000 DWT-lik gəminin kapitanısınız. 2 və 5 saylı silindirlərdə yanacaq enjektoru nasazlığı — mühərrik 6-dan yalnız 4 silindr ilə işləyir, sürət 8 düyünə düşüb.',
                        'your_position'       => 'Körpüüstü, Baş mühəndislə səhər görüşü.',
                        'available_resources' => 'Ehtiyat enjektorlar, emalatxana, MAN B&W təcrübəli mühəndis, istehsalçı ilə peyk əlaqəsi, hava marşrutlaşdırma.',
                        'current_conditions'  => 'Sakit okean, sakit dəniz, külək 3 bal, aşağı təzyiq 72 saatdan sonra.',
                    ],
                ],
                'decision_prompt'      => 'What is your decision: continue at reduced speed or shut down the engine for repairs at sea? Consider the weather window, voyage timeline, and technical risks.',
                'decision_prompt_i18n' => [
                    'tr' => 'Kararınız nedir: düşük hızla devam mı, yoksa denizde onarım için motoru kapatmak mı? Hava penceresi, sefer zaman çizelgesi ve teknik riskleri göz önünde bulundurun.',
                    'ru' => 'Ваше решение: продолжать на сниженной скорости или остановить двигатель для ремонта в море? Учтите погодное окно, график рейса и технические риски.',
                    'az' => 'Qərarınız nədir: azaldılmış sürətlə davam etmək, yoxsa dənizdə təmir üçün mühərriki dayandırmaq? Hava pəncərəsini, səfər vaxt cədvəlini və texniki riskləri nəzərə alın.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'technical_decision_making',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Makes no decision or defers entirely to the C/E without any master-level assessment of the options.',
                            '2' => 'Chooses one option (continue or repair) without evaluating trade-offs, risks, or the C/E\'s technical assessment.',
                            '3' => 'Evaluates both options — continuing at reduced speed vs. engine shutdown for repair — considering basic factors such as repair duration and current weather.',
                            '4' => 'Conducts a structured assessment: discusses repair procedure with C/E, evaluates risks of running on 4 cylinders (thermal stress, vibration), considers the approaching weather, and selects a course of action with clear justification.',
                            '5' => 'Delivers an expert decision integrating: detailed technical discussion with C/E on injector failure mode, thermal/vibration risk of prolonged 4-cylinder operation, manufacturer consultation, weather window analysis for the 6-8 hour shutdown, contingency if repair takes longer, and a phased plan (e.g., repair one cylinder first, test, then second).',
                        ],
                    ],
                    [
                        'axis'   => 'voyage_impact_assessment',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No consideration of voyage delay, fuel consumption change, or commercial impact.',
                            '2' => 'Mentions the delay but makes no calculation or stakeholder notification.',
                            '3' => 'Calculates revised ETA for both options, assesses fuel sufficiency at reduced speed, and notifies the company of the expected delay.',
                            '4' => 'Provides a detailed comparison: ETA under reduced speed vs. ETA after repair and resumed full speed; fuel consumption analysis for both; informs company, charterer, and agent with revised schedule.',
                            '5' => 'Delivers a comprehensive voyage impact analysis: detailed ETA comparison for multiple scenarios (repair now, repair later, continue reduced), fuel consumption and bunker sufficiency for each, commercial implications communicated to company and charterer, considers port scheduling and berth availability, and documents decision rationale for post-voyage review.',
                        ],
                    ],
                    [
                        'axis'   => 'timing_and_risk_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Ignores the approaching low-pressure system; makes no time-sensitive assessment.',
                            '2' => 'Mentions the weather forecast but does not factor it into the repair timing decision.',
                            '3' => 'Recognises the 72-hour weather window and plans the engine shutdown within it, accounting for the calm conditions needed for safe repair.',
                            '4' => 'Integrates weather timing into a risk-managed plan: schedules repair during the current calm, establishes abort criteria if conditions change, prepares contingency (anchoring, drift management during shutdown), and notifies MRCC of position if drifting.',
                            '5' => 'Develops a full risk-management plan: optimal repair window identified with weather routing service, drift plan during shutdown (sea room assessment, traffic clearance), emergency anchoring not possible in deep ocean so considers sea-anchor/drift rate calculation, contingency if repair fails (limp to nearest port at reduced speed vs. request tow), and pre-positions crew for rapid engine restart if weather deteriorates.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No assessment of running on reduced cylinders vs repair at sea', 'severity' => 'critical'],
                    ['flag' => 'No weather window analysis for engine shutdown',                'severity' => 'major'],
                    ['flag' => 'No notification to company about delay',                        'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'Engine manufacturer technical manual',
                    'Company SMS machinery failure procedures',
                    'SOLAS Chapter II-1',
                    'ISM Code emergency preparedness',
                ],
                'red_flags_json' => [
                    'Refusing to shut down engine despite available spares',
                    'No communication with company about delay',
                    'Ignoring approaching weather system',
                ],
            ],

            // ────────────────────────────────────────────────────────────
            // SLOT 4 — RISK_MGMT — Bulk cargo liquefaction risk
            // ────────────────────────────────────────────────────────────
            'DEEP_SEA_S04_RISK_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 55,000 DWT bulk carrier loaded with nickel ore from the Philippines, bound for Qingdao, China. Three days into the voyage, the weather deteriorates to Beaufort 7. You notice that the cargo surface in Hold 2 appears wet and there is free water visible at the cargo boundaries. The cargo moisture certificate states the cargo was loaded at 28% moisture — the Transportable Moisture Limit (TML) is 30%. The Chief Officer reports that Hold 2 bilge alarms are activating frequently.',
                        'your_position'       => 'Bridge, C/O reporting from cargo hold inspection.',
                        'available_resources' => 'Cargo documentation (moisture certificates, shipper declaration), loading port surveyor report, P&I Club emergency contact, company DPA, nearest port of refuge (Taiwan, 2 days).',
                        'current_conditions'  => 'Beaufort 7, swell from NE 3-4m, vessel rolling moderately, forecast deterioration.',
                    ],
                    'tr' => [
                        'situation'           => 'Filipinler\'den Çin Qingdao\'ya nikel cevheri yüklü 55.000 DWT dökme yük gemisinin kaptanısınız. Seferin 3. gününde hava Beaufort 7\'ye kötüleşiyor. 2 numaralı ambardaki yük yüzeyinin ıslak göründüğünü ve yük sınırlarında serbest su olduğunu fark ediyorsunuz. Yük nem sertifikası, yükün %28 nemde yüklendiğini belirtiyor — Taşınabilir Nem Limiti (TML) %30. Birinci zabit, 2 numaralı ambar sintine alarmlarının sık sık aktive olduğunu bildiriyor.',
                        'your_position'       => 'Köprüüstü, Birinci Zabit yük ambarı denetiminden bildiriyor.',
                        'available_resources' => 'Yük belgeleri (nem sertifikaları, yükleyici beyanı), yükleme limanı sörveyör raporu, P&I Kulübü acil irtibat, şirket DPA, en yakın sığınma limanı (Tayvan, 2 gün).',
                        'current_conditions'  => 'Beaufort 7, KD\'den 3-4m kabarma, gemi orta derecede sallanıyor, kötüleşme tahmini.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан балкера 55 000 DWT с никелевой рудой из Филиппин в Циндао. На 3-й день погода ухудшилась до 7 баллов; в трюме №2 видна свободная вода на поверхности груза.',
                        'your_position'       => 'Мостик, доклад старпома после осмотра трюма.',
                        'available_resources' => 'Грузовые документы, сюрвейерский отчёт, P&I клуб, DPA, ближайший порт-убежище (Тайвань, 2 дня).',
                        'current_conditions'  => 'Бофорт 7, зыбь 3-4м, умеренная качка, прогноз ухудшения.',
                    ],
                    'az' => [
                        'situation'           => 'Filippindən Çin Sinqdaoya nikel filizi yüklü 55.000 DWT-lik gəminin kapitanısınız. 3-cü gün hava Beaufort 7-yə pisləşdi; 2 saylı anbarda yükün üstündə sərbəst su görünür.',
                        'your_position'       => 'Körpüüstü, birinci stürman anbar yoxlamasından bildirir.',
                        'available_resources' => 'Yük sənədləri, sürveyer hesabatı, P&I klub, DPA, ən yaxın sığınacaq limanı (Tayvan, 2 gün).',
                        'current_conditions'  => 'Beaufort 7, 3-4m dalğa, orta yırğalanma, pisləşmə proqnozu.',
                    ],
                ],
                'decision_prompt'      => 'What is your assessment of the cargo situation in Hold 2? What actions will you take and what factors will influence your decision to continue or deviate?',
                'decision_prompt_i18n' => [
                    'tr' => '2 numaralı ambardaki yük durumunu nasıl değerlendiriyorsunuz? Hangi önlemleri alacaksınız ve devam etme veya sapma kararınızı hangi faktörler etkileyecek?',
                    'ru' => 'Как вы оцениваете ситуацию с грузом в трюме №2? Какие действия предпримете и какие факторы повлияют на решение о продолжении или отклонении?',
                    'az' => '2 saylı anbardakı yük vəziyyətini necə qiymətləndirirsiniz? Hansı tədbirləri görəcəksiniz və davam etmə və ya sapma qərarınıza hansı amillər təsir edəcək?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'cargo_risk_assessment',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Does not recognise the signs of potential cargo liquefaction; treats free water as a routine bilge issue.',
                            '2' => 'Notes the wet surface but does not connect it to liquefaction risk or review the IMSBC Code requirements for nickel ore.',
                            '3' => 'Identifies the liquefaction risk based on visible free water and proximity to TML, reviews moisture certificate data, and orders increased monitoring of Hold 2.',
                            '4' => 'Conducts a thorough cargo risk assessment: compares actual moisture observations against TML, questions the reliability of the loading port moisture test, reviews IMSBC Code schedule for nickel ore, assesses stability impact of potential cargo shift, and orders monitoring of all holds.',
                            '5' => 'Delivers an expert assessment: recognises that 28% moisture near 30% TML is dangerously close, understands that the certificate may understate actual moisture due to sampling issues, evaluates free-surface and shift effects on stability, considers that Beaufort 7 rolling accelerates liquefaction, and systematically assesses all holds for similar signs.',
                        ],
                    ],
                    [
                        'axis'   => 'decision_on_continuation',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Continues the voyage without any consideration of deviation or risk mitigation.',
                            '2' => 'Considers deviation but decides to continue without a structured risk assessment or defined criteria for changing course.',
                            '3' => 'Evaluates the deviation option to Taiwan (2 days), weighs it against continuing, sets basic criteria for when to deviate (e.g., if stability changes), and communicates with company.',
                            '4' => 'Makes a well-reasoned decision with clear criteria: defines stability thresholds that trigger deviation, calculates ETA to port of refuge, considers weather deterioration forecast, consults company and P&I Club, and prepares contingency plans for both continuing and deviating.',
                            '5' => 'Delivers an expert decision framework: immediate deviation recommendation given the combination of visible free water, approaching weather deterioration, and proximity to TML; backs decision with stability calculations, IMSBC Code references, P&I Club consultation, documented risk assessment, and pre-arranged port of refuge arrangements.',
                        ],
                    ],
                    [
                        'axis'   => 'regulatory_and_reporting',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No reporting to any external party; no regulatory awareness.',
                            '2' => 'Notifies the company but does not contact P&I Club or flag state as appropriate.',
                            '3' => 'Reports to company and P&I Club; makes log entries documenting the cargo condition and actions taken.',
                            '4' => 'Comprehensive reporting: company, P&I Club, classification society if structural concern, detailed log entries with photographs, prepares Noted Protest, and references IMSBC Code obligations.',
                            '5' => 'Full regulatory and reporting response: immediate P&I Club notification for surveyor arrangement at port of refuge, company notified with documented risk assessment, flag state notified if required, photographic and video evidence of cargo condition, detailed event log, Noted Protest drafted, considers reporting to loading port authority regarding possible cargo declaration issues.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No cargo liquefaction risk assessment',         'severity' => 'critical'],
                    ['flag' => 'No deviation to port of refuge considered',     'severity' => 'critical'],
                    ['flag' => 'No P&I Club notification',                      'severity' => 'major'],
                    ['flag' => 'No additional cargo monitoring plan',           'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'IMSBC Code (especially Schedule for nickel ore)',
                    'SOLAS Chapter VI',
                    'BLU Code',
                    'P&I Club cargo guidelines',
                    'MSC.1/Circ.1454 (cargo liquefaction)',
                ],
                'red_flags_json' => [
                    'Continuing voyage without additional monitoring',
                    'No awareness of liquefaction risk',
                    'Failing to consider port of refuge',
                    'Not checking cargo documentation validity',
                ],
            ],

            // ────────────────────────────────────────────────────────────
            // SLOT 5 — CREW_LEAD — Systemic fatigue and rest-hour violations
            // ────────────────────────────────────────────────────────────
            'DEEP_SEA_S05_CREW_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 70,000 DWT tanker on a 35-day ocean passage. The Chief Officer reports that rest hour violations are becoming systemic — engine room staff are regularly exceeding maximum work hours due to ongoing maintenance requirements, and the 2/O has logged only 5 hours rest in the last 24 hours due to cargo calculations. The Safety Officer has raised a formal concern that fatigue is becoming a safety risk. The company office is pressing for all maintenance to be completed before arrival.',
                        'your_position'       => 'Master\'s office, reviewing rest hour records.',
                        'available_resources' => 'Rest hour recording system, ISM procedures, DPA contact, company operations department, Safety Committee provisions.',
                        'current_conditions'  => 'Mid-ocean passage, 18 days to destination, routine operations, calm weather.',
                    ],
                    'tr' => [
                        'situation'           => '70.000 DWT tankerinin kaptanısınız, 35 günlük okyanus geçişinde. Birinci zabit, dinlenme saati ihlallerinin sistematik hale geldiğini bildiriyor — makine dairesi personeli devam eden bakım gereksinimleri nedeniyle düzenli olarak azami çalışma saatlerini aşıyor ve 2. zabit son 24 saatte yük hesapları nedeniyle sadece 5 saat dinlenmiş. Güvenlik Zabiti, yorgunluğun güvenlik riski haline geldiğine dair resmi endişe bildirmiş. Şirket ofisi, tüm bakımların varıştan önce tamamlanması için baskı yapıyor.',
                        'your_position'       => 'Kaptan ofisi, dinlenme saati kayıtlarını inceliyorsunuz.',
                        'available_resources' => 'Dinlenme saati kayıt sistemi, ISM prosedürleri, DPA irtibatı, şirket operasyon departmanı, Güvenlik Komitesi hükümleri.',
                        'current_conditions'  => 'Açık okyanus geçişi, varışa 18 gün, rutin operasyonlar, sakin hava.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан танкера 70 000 DWT на 35-дневном переходе. Систематические нарушения часов отдыха: машинная команда регулярно перерабатывает, 2-й помощник отдохнул только 5 часов за последние сутки. Офицер по безопасности поднял формальную тревогу.',
                        'your_position'       => 'Каюта капитана, проверка записей часов отдыха.',
                        'available_resources' => 'Система учёта часов отдыха, ISM процедуры, DPA, операционный отдел компании, положения Комитета безопасности.',
                        'current_conditions'  => 'Открытый океан, 18 дней до порта, спокойная погода.',
                    ],
                    'az' => [
                        'situation'           => '70.000 DWT-lik tankerin kapitanısınız. İstirahət saatı pozuntuları sistematik xarakter almışdır — maşın otağı heyəti mütəmadi olaraq həddən artıq işləyir, 2-ci stürman son 24 saatda yalnız 5 saat dincəlib.',
                        'your_position'       => 'Kapitan ofisi, istirahət saatı qeydlərini nəzərdən keçirir.',
                        'available_resources' => 'İstirahət saatı qeyd sistemi, ISM prosedurları, DPA əlaqəsi, şirkətin əməliyyat şöbəsi.',
                        'current_conditions'  => 'Açıq okean, limana 18 gün, sakit hava.',
                    ],
                ],
                'decision_prompt'      => 'How will you address the systemic rest hour violations, balance company maintenance demands with crew safety, and manage the fatigue risk for the remaining voyage?',
                'decision_prompt_i18n' => [
                    'tr' => 'Sistemik dinlenme saati ihlallerini nasıl ele alacaksınız, şirketin bakım taleplerini mürettebat güvenliğiyle nasıl dengeleyeceksiniz ve kalan sefer boyunca yorgunluk riskini nasıl yöneteceksiniz?',
                    'ru' => 'Как вы решите проблему систематических нарушений часов отдыха, сбалансируете требования компании по техобслуживанию с безопасностью экипажа и будете управлять риском усталости в оставшейся части рейса?',
                    'az' => 'Sistematik istirahət saatı pozuntularını necə həll edəcəksiniz, şirkətin texniki xidmət tələblərini ekipaj təhlükəsizliyi ilə necə tarazlayacaqsınız və qalan səfər müddətində yorğunluq riskini necə idarə edəcəksiniz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'fatigue_risk_management',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Ignores the fatigue issue entirely; does not review rest-hour records or take any action.',
                            '2' => 'Acknowledges the problem but only instructs crew to "get more rest" without changing schedules or workload.',
                            '3' => 'Reviews rest-hour records, identifies the worst cases, adjusts work schedules for the 2/O and engine room, and orders compliance with minimum rest hours immediately.',
                            '4' => 'Conducts a systematic fatigue risk assessment: audits all rest-hour records, creates a revised work and maintenance schedule that complies with STCW minimums, prioritises critical vs. deferrable maintenance, addresses the 2/O case specifically, and holds a safety meeting.',
                            '5' => 'Delivers a comprehensive fatigue management response: full rest-hour audit with corrective action for all non-compliant personnel, revised maintenance plan prioritising safety-critical items only, formal response to the Safety Officer\'s concern via Safety Committee, personal supervision of 2/O recovery, establishes a fatigue monitoring system for the remaining voyage, and documents all actions for ISM compliance.',
                        ],
                    ],
                    [
                        'axis'   => 'regulatory_compliance',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Shows no awareness of STCW rest-hour requirements or MLC obligations.',
                            '2' => 'Knows rest hours are regulated but does not cite specific requirements or take compliance action.',
                            '3' => 'References STCW minimum rest hours (10 hours in 24h, 77 hours in 7 days), ensures immediate compliance, and makes appropriate log entries.',
                            '4' => 'Demonstrates thorough regulatory knowledge: STCW Section A-VIII/1 rest-hour requirements, MLC 2006 Regulation 2.3 on hours of work, ISM Code reporting obligations, and takes documented corrective actions to bring all personnel into compliance.',
                            '5' => 'Expert regulatory response: ensures immediate STCW compliance for all crew, references MLC provisions, addresses ISM non-conformity reporting requirements, considers port state control implications if records show violations at next port, documents corrective actions in a format that satisfies PSC inspection, and contacts DPA regarding the systemic nature of the issue.',
                        ],
                    ],
                    [
                        'axis'   => 'company_pushback_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Complies fully with company pressure without any pushback, forcing crew to continue exceeding work hours.',
                            '2' => 'Expresses discomfort but ultimately follows company demands at the expense of rest hours.',
                            '3' => 'Communicates to the company that maintenance completion before arrival is not possible while maintaining rest-hour compliance, and proposes a revised maintenance plan.',
                            '4' => 'Asserts master\'s overriding authority on safety: formally responds to company that current demands create an unsafe condition, provides a revised maintenance schedule prioritising safety-critical items, defers non-critical work to port, and documents the communication.',
                            '5' => 'Exemplary pushback management: formally invokes ISM Code master\'s overriding authority, provides the company with a documented risk assessment showing why current demands are unsustainable, proposes a phased maintenance plan with safety-critical items completed at sea and remaining work scheduled for port, contacts DPA for support, and ensures all communications are documented for regulatory and ISM audit purposes.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No rest hour violation corrective action',                            'severity' => 'critical'],
                    ['flag' => 'No communication with company about unrealistic expectations',         'severity' => 'major'],
                    ['flag' => 'No revised work schedule',                                            'severity' => 'critical'],
                ],
                'expected_references_json' => [
                    'STCW Code Section A-VIII/1 (rest hours)',
                    'MLC 2006 Regulation 2.3',
                    'ISM Code',
                    'Company SMS fatigue management policy',
                ],
                'red_flags_json' => [
                    'Instructing crew to falsify rest hour records',
                    'Ignoring Safety Officer\'s formal concern',
                    'Prioritizing company maintenance demands over crew safety',
                    'No action on 2/O\'s 5-hour rest period',
                ],
            ],

            // ────────────────────────────────────────────────────────────
            // SLOT 6 — AUTO_DEP — ECDIS failover + gyro compass error
            // ────────────────────────────────────────────────────────────
            'DEEP_SEA_S06_AUTO_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 60,000 DWT container vessel, mid-Atlantic crossing. The primary gyro compass has developed a precession error of 3.5 degrees and is drifting. The ECDIS heading input is affected, causing chart display offset. The backup gyro shows a 1.2 degree deviation from the primary. The magnetic compass is available but its deviation card was last checked 8 months ago. You are 4 days from the nearest port and in an area with moderate traffic.',
                        'your_position'       => 'Bridge, night watch. 2/O on watch, Master called.',
                        'available_resources' => 'Primary and backup gyro compass, magnetic compass (8-month old deviation card), GPS, radar with ARPA, ECDIS (2 units), celestial navigation equipment, company technical support via email.',
                        'current_conditions'  => 'Atlantic crossing, wind Force 4, clear sky (stars visible for celestial navigation), moderate traffic.',
                    ],
                    'tr' => [
                        'situation'           => '60.000 DWT konteyner gemisinin kaptanısınız, Atlantik geçişi ortasında. Birincil cayro pusula 3,5 derecelik presesyon hatası geliştirdi ve sapıyor. ECDIS baş yönü girişi etkilenmiş, harita gösteriminde kayma var. Yedek cayro, birincilden 1,2 derece sapma gösteriyor. Manyetik pusula mevcut ama sapma kartı son 8 ay önce kontrol edilmiş. En yakın limana 4 gün mesafedesiniz ve orta yoğunlukta trafikli bir bölgedesiniz.',
                        'your_position'       => 'Köprüüstü, gece vardiyası. 2. zabit vardiyada, kaptan çağırıldı.',
                        'available_resources' => 'Birincil ve yedek cayro pusula, manyetik pusula (8 aylık sapma kartı), GPS, ARPA\'lı radar, ECDIS (2 ünite), gök cismi navigasyonu ekipmanı, e-posta yoluyla şirket teknik desteği.',
                        'current_conditions'  => 'Atlantik geçişi, rüzgar Kuvvet 4, açık gökyüzü (gök cismi navigasyonu için yıldızlar görünür), orta trafik.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан контейнеровоза 60 000 DWT в середине Атлантики. Основной гирокомпас дал ошибку прецессии 3,5°; резервный показывает отклонение 1,2° от основного. Магнитный компас доступен, но таблица девиации проверялась 8 месяцев назад.',
                        'your_position'       => 'Мостик, ночная вахта, капитан вызван.',
                        'available_resources' => 'Основной и резервный гирокомпас, магнитный компас, GPS, радар с ARPA, 2 ЭКНИС, секстант, техподдержка компании.',
                        'current_conditions'  => 'Атлантика, ветер 4 балла, ясное небо, умеренный трафик.',
                    ],
                    'az' => [
                        'situation'           => '60.000 DWT-lik konteyner gəmisinin kapitanısınız, Atlantik keçidinin ortasında. Əsas giro kompas 3,5° presesiya xətası verib; ehtiyat giro əsasdan 1,2° fərqlənir. Maqnit kompas var, lakin deviasiya kartı 8 ay əvvəl yoxlanılıb.',
                        'your_position'       => 'Körpüüstü, gecə növbəsi, kapitan çağırılıb.',
                        'available_resources' => 'Əsas və ehtiyat giro, maqnit kompas, GPS, ARPA radari, 2 ECDIS, göy cisimləri naviqasiya avadanlığı, şirkət texniki dəstəyi.',
                        'current_conditions'  => 'Atlantik, külək 4 bal, açıq səma, orta trafik.',
                    ],
                ],
                'decision_prompt'      => 'How will you determine your correct heading and ensure safe navigation with compromised compass systems? Describe your troubleshooting approach and navigation methodology.',
                'decision_prompt_i18n' => [
                    'tr' => 'Bozulmuş pusula sistemleriyle doğru baş yönünüzü nasıl belirleyecek ve güvenli navigasyonu nasıl sağlayacaksınız? Arıza giderme yaklaşımınızı ve navigasyon metodolojinizi açıklayın.',
                    'ru' => 'Как вы определите правильный курс и обеспечите безопасную навигацию при неисправных компасах? Опишите подход к диагностике и навигационную методологию.',
                    'az' => 'Nasaz kompas sistemləri ilə düzgün istiqamətinizi necə müəyyən edəcəksiniz və təhlükəsiz naviqasiyanı necə təmin edəcəksiniz? Nasazlıq aradan qaldırma yanaşmanızı və naviqasiya metodologiyanızı təsvir edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'navigation_methodology',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Continues to rely on the faulty primary gyro without any cross-check or verification of heading.',
                            '2' => 'Switches to the backup gyro but does not verify its accuracy against any independent source.',
                            '3' => 'Cross-checks between primary gyro, backup gyro, magnetic compass, and GPS COG to identify the most reliable heading reference; applies magnetic compass deviation corrections from the available card.',
                            '4' => 'Implements a systematic cross-referencing regime: compares all compass systems, takes a celestial azimuth observation to determine true heading independently, uses the result to calculate the error in each compass, and selects the best reference with known correction applied.',
                            '5' => 'Delivers expert navigation methodology: takes star azimuth or Polaris observation to establish true heading, calculates exact error for primary gyro, backup gyro, and magnetic compass independently, manually inputs corrected heading to ECDIS, establishes a heading verification schedule using celestial and GPS COG cross-checks, and documents all corrections for the bridge team.',
                        ],
                    ],
                    [
                        'axis'   => 'technical_troubleshooting',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Makes no attempt to diagnose or troubleshoot the gyro compass fault.',
                            '2' => 'Asks the electrician to check the gyro but does not follow up or seek external technical support.',
                            '3' => 'Initiates a structured troubleshooting process: checks power supply, reviews gyro error log, restarts the unit if appropriate, and contacts company technical support for guidance.',
                            '4' => 'Conducts thorough diagnosis: checks both gyros independently for common-cause failure (power, environment), reviews manufacturer troubleshooting guide, contacts company and considers manufacturer remote support, assesses whether the backup gyro 1.2° deviation is within normal settling tolerance or indicates a separate fault.',
                            '5' => 'Expert troubleshooting: systematic fault isolation of both gyros, checks for common power supply issues, speed/latitude error inputs, follows manufacturer diagnostic procedure, arranges remote technical support via satellite, determines if the 1.2° backup deviation is settling error or independent fault, pre-arranges gyro service technician at next port, and documents findings for class survey.',
                        ],
                    ],
                    [
                        'axis'   => 'safety_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Takes no additional safety precautions despite the navigation equipment degradation.',
                            '2' => 'Posts an additional lookout but does not adjust speed, amend standing orders, or enhance the bridge watch.',
                            '3' => 'Enhances the bridge watch with a second officer, reduces speed, updates night orders to reflect degraded navigation mode, and increases radar monitoring in the moderate traffic area.',
                            '4' => 'Implements comprehensive safety measures: two-officer bridge watch, reduced speed, amended standing orders, increased radar and AIS monitoring, manual ECDIS heading correction applied, all OOWs briefed on the compass situation and verification procedures.',
                            '5' => 'Full safety management response: immediate two-officer watch, reduced speed appropriate to conditions, amended night orders and standing orders, all OOWs briefed with written instructions for compass verification at each watch handover, company and flag state notified of the SOLAS equipment deficiency, heading verification schedule posted on the bridge, and contingency plan for transit through any traffic separation scheme.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No cross-checking of compass systems',        'severity' => 'critical'],
                    ['flag' => 'No celestial navigation to verify heading',   'severity' => 'major'],
                    ['flag' => 'No manual ECDIS heading input correction',    'severity' => 'critical'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter V Reg 19 (navigation equipment)',
                    'Magnetic compass deviation procedures',
                    'Celestial navigation principles',
                    'ECDIS heading input requirements',
                ],
                'red_flags_json' => [
                    'Relying on faulty gyro without verification',
                    'No cross-check between navigation systems',
                    'Ignoring 3.5-degree heading error in ocean crossing',
                ],
            ],

            // ────────────────────────────────────────────────────────────
            // SLOT 7 — CRISIS_RSP — Engine room fire mid-ocean
            // ────────────────────────────────────────────────────────────
            'DEEP_SEA_S07_CRIS_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 62,000 DWT bulk carrier, mid-Indian Ocean, 5 days from the nearest port. At 0300, the engine room fire detection system activates. The duty engineer reports a significant fire in the main engine exhaust manifold area — likely a fuel oil leak onto hot surfaces. Flames are spreading toward the fuel oil service tank area. The engine room is filling with thick black smoke. Four engine room crew are evacuating. The fixed CO2 system is ready but the Chief Engineer warns that releasing CO2 will require complete engine shutdown, leaving the vessel without propulsion.',
                        'your_position'       => 'Bridge, called from cabin. C/E on emergency phone from engine room entrance.',
                        'available_resources' => 'Fixed CO2 fire suppression system, two fire parties with BA sets, emergency fire pump (deck-operated), emergency generator, EPIRB and SART, satellite communication for MRCC, 23 crew total.',
                        'current_conditions'  => 'Mid-Indian Ocean, wind Force 5, moderate swell, nearest MRCC coordination: Mauritius, next vessel on AIS within 45 NM.',
                    ],
                    'tr' => [
                        'situation'           => '62.000 DWT dökme yük gemisinin kaptanısınız, Hint Okyanusu ortasında, en yakın limana 5 gün mesafede. Saat 03:00\'te makine dairesi yangın algılama sistemi aktive oluyor. Nöbetçi mühendis, ana makine egzoz manifoldu bölgesinde önemli bir yangın bildiriyor — muhtemelen sıcak yüzeylere yakıt sızıntısı. Alevler yakıt servis tankı bölgesine doğru yayılıyor. Makine dairesi kalın siyah dumanla doluyor. Dört makine mürettebatı tahliye ediliyor. Sabit CO2 sistemi hazır ama Başmühendis CO2 salmanın motorun tamamen kapatılmasını gerektireceğini, geminin tahrikçisiz kalacağını uyarıyor.',
                        'your_position'       => 'Köprüüstü, kamaradan çağırıldınız. Başmühendis makine dairesi girişinden acil telefonla.',
                        'available_resources' => 'Sabit CO2 yangın söndürme sistemi, BA setli iki yangın ekibi, acil yangın pompası (güverteden çalıştırılan), acil jeneratör, EPIRB ve SART, MRCC için uydu iletişimi, toplam 23 mürettebat.',
                        'current_conditions'  => 'Hint Okyanusu ortası, rüzgar Kuvvet 5, orta kabarma, en yakın MRCC koordinasyonu: Mauritius, AIS\'te en yakın gemi 45 mil mesafede.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан балкера 62 000 DWT в Индийском океане, 5 дней от порта. В 03:00 сработала пожарная сигнализация МО. Пожар в районе выхлопного коллектора — утечка топлива на горячие поверхности. Пламя распространяется к топливной цистерне. МО заполняется дымом, 4 человека эвакуируются.',
                        'your_position'       => 'Мостик, вызван из каюты. Стармех у входа в МО.',
                        'available_resources' => 'Система CO2, две пожарные партии с дыхательными аппаратами, аварийный пожарный насос, аварийный генератор, EPIRB, SART, спутниковая связь, 23 человека.',
                        'current_conditions'  => 'Индийский океан, ветер 5 баллов, ближайший МСКЦ: Маврикий, ближайшее судно на AIS 45 миль.',
                    ],
                    'az' => [
                        'situation'           => '62.000 DWT-lik gəminin kapitanısınız, Hind okeanının ortasında, limana 5 gün. Saat 03:00-da MO yanğın sistemi işə düşür. Egzoz kollektor bölgəsində yanğın — isti səthlərə yanacaq sızması. Alov yanacaq çəninə yayılır. MO tüstü ilə dolur, 4 nəfər təxliyə olunur.',
                        'your_position'       => 'Körpüüstü, kamaradan çağırılıb. Baş mühəndis MO girişindən.',
                        'available_resources' => 'CO2 sistemi, iki yanğın briqadası, təcili yanğın pompası, təcili generator, EPIRB, SART, peyk əlaqəsi, 23 nəfər.',
                        'current_conditions'  => 'Hind okeanı, külək 5 bal, ən yaxın MRCC: Mauritius, AIS-də ən yaxın gəmi 45 mil.',
                    ],
                ],
                'decision_prompt'      => 'What is your sequence of actions for the engine room fire? How do you balance firefighting, crew safety, vessel propulsion, and emergency communications mid-ocean?',
                'decision_prompt_i18n' => [
                    'tr' => 'Makine dairesi yangını için eylem sıranız nedir? Okyanus ortasında yangınla mücadele, mürettebat güvenliği, gemi tahrikçisi ve acil iletişimleri nasıl dengeliyorsunuz?',
                    'ru' => 'Какова ваша последовательность действий при пожаре в МО? Как вы балансируете между тушением, безопасностью экипажа, движением судна и аварийной связью в открытом океане?',
                    'az' => 'Maşın otağı yanğını üçün hərəkət ardıcıllığınız nədir? Okeanda yanğınla mübarizəni, ekipaj təhlükəsizliyini, gəmi hərəkəsini və təcili rabitəni necə tarazlayırsınız?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'fire_emergency_response',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No structured fire response; panics or delays action while the fire spreads unchecked.',
                            '2' => 'Orders CO2 release immediately without confirming engine room evacuation or securing ventilation.',
                            '3' => 'Follows a basic fire emergency sequence: sounds general alarm, confirms ER evacuation headcount, orders ventilation shutdown, boundary cooling initiated, then authorises CO2 release once all personnel confirmed clear.',
                            '4' => 'Executes a well-structured fire response: general alarm, muster and headcount, ventilation and fuel supply shutdown, boundary cooling with fire parties, confirmed ER evacuation before CO2, monitors CO2 effectiveness, and prepares for re-entry assessment after cooling period.',
                            '5' => 'Delivers an expert fire response: immediate general alarm and emergency stations, simultaneous ER evacuation verification by name/headcount, ventilation dampers closed and confirmed, remote fuel shutoff activated, boundary cooling teams deployed with BA sets, CO2 released only after confirmed full evacuation, fire containment monitored via thermal imaging if available, re-entry protocol planned with safety precautions, and contingency for CO2 failure (prepare additional firefighting or abandon ship).',
                        ],
                    ],
                    [
                        'axis'   => 'crew_safety_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No headcount; no verification of engine room evacuation before taking firefighting action.',
                            '2' => 'Assumes ER is evacuated based on verbal report without formal headcount or muster.',
                            '3' => 'Conducts muster at emergency stations, verifies headcount including all four ER personnel, ensures injured are attended to, and accounts for all 23 crew.',
                            '4' => 'Thorough crew safety management: formal muster with name-by-name verification, medical assessment of any ER evacuees for smoke inhalation, assigns buddy pairs for all fire party members, prepares abandon-ship as a contingency, and ensures immersion suits are accessible.',
                            '5' => 'Exemplary crew safety response: immediate name-by-name muster, medical triage for smoke inhalation and burns, assigns dedicated medical team member, all fire party members briefed and equipped with BA and communication, standby rescue team positioned outside ER, abandon-ship preparations initiated as a precaution (lifeboats swung out, EPIRB ready), and continuous crew accountability throughout the emergency.',
                        ],
                    ],
                    [
                        'axis'   => 'crisis_communication',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No external communication; does not contact MRCC, company, or nearby vessels.',
                            '2' => 'Contacts company only after the situation has developed significantly; no MRCC notification.',
                            '3' => 'Sends a PanPan to MRCC Mauritius, notifies company, and establishes communication with the nearest vessel on AIS at 45 NM.',
                            '4' => 'Comprehensive communication plan: PanPan broadcast on VHF Ch16 and DSC, MRCC Mauritius notified with position and situation, company informed via satellite, nearest vessel contacted for standby assistance, and internal communication maintained between bridge and fire teams.',
                            '5' => 'Expert crisis communication: immediate DSC distress alert and PanPan on all relevant frequencies, MRCC Mauritius informed with full SITREP (position, nature of emergency, number of POB, current actions, assistance required), company notified with regular updates, nearest vessel requested for standby or approach, EPIRB prepared for activation if situation escalates to Mayday, internal comms protocol established between bridge, fire parties, and medical team, and communication log maintained.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No engine room evacuation headcount before CO2 release', 'severity' => 'critical'],
                    ['flag' => 'No general alarm sounded',                              'severity' => 'critical'],
                    ['flag' => 'No ventilation shutdown',                               'severity' => 'critical'],
                    ['flag' => 'No MRCC notification',                                  'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter II-2 (fire protection)',
                    'SOLAS Chapter III (life-saving)',
                    'GMDSS distress procedures',
                    'Company fire emergency procedures',
                    'ISM Code emergency preparedness',
                ],
                'red_flags_json' => [
                    'Releasing CO2 without confirmed ER evacuation',
                    'No general alarm',
                    'No distress communication when vessel without propulsion mid-ocean',
                    'Failing to prepare abandon ship as contingency',
                ],
            ],

            // ────────────────────────────────────────────────────────────
            // SLOT 8 — TRADEOFF — Charter party speed vs fuel vs weather
            // ────────────────────────────────────────────────────────────
            'DEEP_SEA_S08_TRADE_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 68,000 DWT bulk carrier on charter, en route from Australia to Japan with iron ore. The charter party specifies 12.5 knots and 28 MT/day fuel consumption. Your actual consumption at that speed is 31 MT/day due to hull fouling. You are also receiving weather routing advice to divert 200 NM south to avoid a developing storm system, which will add 18 hours to the voyage. The charterer is pressing for ETA compliance. If you slow to 10.5 knots (economical speed), consumption drops to 22 MT/day but arrival is delayed by 2 days. Fuel remaining is tight for the higher speed with the diversion.',
                        'your_position'       => 'Bridge, reviewing voyage plan with C/O and C/E.',
                        'available_resources' => 'Weather routing service, noon report data showing actual vs CP performance, fuel remaining calculation, charterer operations contact, company operations department.',
                        'current_conditions'  => 'Currently fair weather, storm system 800 NM ahead, wind increasing forecast.',
                    ],
                    'tr' => [
                        'situation'           => '68.000 DWT dökme yük gemisinin kaptanısınız, kiralık olarak Avustralya\'dan Japonya\'ya demir cevheri taşıyorsunuz. Çarter parti 12,5 knot ve günlük 28 MT yakıt tüketimi belirtiyor. Tekne kirlenmesi nedeniyle bu hızdaki gerçek tüketiminiz günde 31 MT. Ayrıca gelişmekte olan fırtına sisteminden kaçınmak için 200 deniz mili güneye sapma önerisi alıyorsunuz — bu sefere 18 saat ekleyecek. Kiracı ETA uyumu için baskı yapıyor. 10,5 knota (ekonomik hız) düşürürseniz tüketim günde 22 MT\'ye düşer ama varış 2 gün gecikir. Sapma ile yüksek hız için kalan yakıt sıkışık.',
                        'your_position'       => 'Köprüüstü, Birinci Zabit ve Başmühendisle sefer planını gözden geçiriyorsunuz.',
                        'available_resources' => 'Hava rotalama servisi, gerçek performansın CP ile karşılaştırmasını gösteren öğle raporu verileri, kalan yakıt hesaplaması, kiracı operasyon irtibatı, şirket operasyon departmanı.',
                        'current_conditions'  => 'Şu an güzel hava, 800 mil ileride fırtına sistemi, rüzgar artış tahmini.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан балкера 68 000 DWT на чартере из Австралии в Японию с железной рудой. Чартер-партия: 12,5 узлов, 28 т/сут. Фактический расход — 31 т/сут из-за обрастания корпуса. Рекомендация метеослужбы: отклонение 200 миль к югу (+18 часов). Фрахтователь настаивает на соблюдении ETA. При 10,5 узлах расход снижается до 22 т/сут, но задержка 2 дня. Запас топлива на высокой скорости с отклонением ограничен.',
                        'your_position'       => 'Мостик, обсуждение плана рейса со старпомом и стармехом.',
                        'available_resources' => 'Метеомаршрутизация, данные полуденных рапортов, расчёт остатка топлива, связь с фрахтователем и компанией.',
                        'current_conditions'  => 'Хорошая погода, шторм в 800 милях, ветер усиливается.',
                    ],
                    'az' => [
                        'situation'           => '68.000 DWT-lik gəminin kapitanısınız, çarterlə Avstraliyadan Yaponiyaya dəmir filizi daşıyırsınız. Çarter 12,5 düyün və 28 MT/gün yanacaq göstərir. Gövdə çirklənməsi üzündən faktiki sərfiyyat 31 MT/gün. Fırtınadan yayınmaq üçün 200 mil cənuba sapma tövsiyəsi var (+18 saat). Fraxtçı ETA riayətini tələb edir. 10,5 düyünə endirsəniz sərfiyyat 22 MT/günə düşür, lakin 2 gün gecikmə olur. Yüksək sürətlə sapma üçün yanacaq ehtiyatı sıxdır.',
                        'your_position'       => 'Körpüüstü, birinci stürman və baş mühəndislə səfər planını nəzərdən keçirir.',
                        'available_resources' => 'Hava marşrutlaşdırma, günorta hesabat məlumatları, yanacaq qalığı hesablaması, fraxtçı və şirkət əlaqəsi.',
                        'current_conditions'  => 'Hazırda yaxşı hava, 800 mil irəlidə fırtına, külək güclənmə proqnozu.',
                    ],
                ],
                'decision_prompt'      => 'How will you balance the charter party speed requirements, the weather diversion advice, fuel constraints, and the charterer\'s ETA pressure? Explain your decision and its commercial and safety implications.',
                'decision_prompt_i18n' => [
                    'tr' => 'Çarter parti hız gereksinimlerini, hava sapma tavsiyesini, yakıt kısıtlamalarını ve kiracının ETA baskısını nasıl dengeleyeceksiniz? Kararınızı ve ticari ile güvenlik sonuçlarını açıklayın.',
                    'ru' => 'Как вы сбалансируете скоростные требования чартер-партии, рекомендации по отклонению от шторма, ограничения по топливу и давление фрахтователя по ETA? Объясните ваше решение и его коммерческие и навигационные последствия.',
                    'az' => 'Çarter sürət tələblərini, hava sapma tövsiyəsini, yanacaq məhdudiyyətlərini və fraxtçının ETA təzyiqini necə tarazlayacaqsınız? Qərarınızı və onun kommersiya və təhlükəsizlik nəticələrini izah edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'commercial_awareness',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Shows no understanding of charter party obligations or commercial implications of any decision.',
                            '2' => 'Aware of CP speed requirement but does not analyse the deviation clause, fuel overconsumption, or ETA impact in commercial terms.',
                            '3' => 'Understands the CP speed/consumption warranty, recognises the hull fouling overconsumption issue, and calculates the commercial impact of delay in terms of hire and potential claims.',
                            '4' => 'Conducts a detailed commercial analysis: compares the cost of weather deviation (18 hours additional hire vs. heavy weather damage risk), evaluates CP weather deviation clause rights, calculates overconsumption exposure for owners, and proposes a strategy that protects the owner commercially while maintaining safety.',
                            '5' => 'Expert commercial management: detailed CP clause analysis including speed/consumption warranty, weather deviation rights, off-hire triggers, and fuel clause; quantifies overconsumption liability vs. weather deviation defence; prepares Noted Protest for hull fouling overconsumption; advises company on optimal commercial position; and documents all decisions for potential arbitration.',
                        ],
                    ],
                    [
                        'axis'   => 'weather_risk_management',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Ignores the weather routing advice; continues directly toward the storm to maintain ETA.',
                            '2' => 'Acknowledges the storm but decides to transit through it rather than divert, without a risk assessment.',
                            '3' => 'Accepts the weather routing diversion, calculates the revised ETA and fuel impact, and notifies relevant parties of the new plan.',
                            '4' => 'Evaluates the weather diversion thoroughly: compares the 200 NM diversion against a smaller deviation, assesses storm intensity and track forecast, considers cargo safety (iron ore — no liquefaction risk but structural loading in heavy weather), and selects the optimal balance of safety and schedule.',
                            '5' => 'Expert weather management: analyses multiple routing options with the weather service, considers storm track uncertainty and re-assessment points, evaluates structural loading on the laden vessel in heavy seas, assesses crew safety in the storm scenario, calculates fuel for each option with safety margin, and establishes decision gates (e.g., reassess in 24 hours when storm track is clearer).',
                        ],
                    ],
                    [
                        'axis'   => 'fuel_and_safety_management',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No fuel sufficiency calculation; does not recognise the tight fuel margin as a safety concern.',
                            '2' => 'Mentions fuel is tight but does not calculate whether sufficient fuel exists for the diversion at CP speed.',
                            '3' => 'Calculates fuel remaining against consumption for both CP speed with diversion and economical speed, identifies that CP speed with diversion may not leave adequate safety margin, and adjusts speed accordingly.',
                            '4' => 'Conducts a comprehensive fuel analysis: fuel remaining vs. consumption at 12.5 knots (31 MT actual), at 10.5 knots (22 MT), and at intermediate speeds; calculates safety margin for each scenario including the 200 NM diversion; considers weather-related consumption increase; and determines the optimal speed that ensures safe arrival with reserve.',
                            '5' => 'Expert fuel and safety management: detailed fuel plan for multiple scenarios with contingency margins, considers bunker quality and actual consumption trends from noon reports, evaluates intermediate speed options for optimal fuel-time balance, identifies emergency bunkering ports on the diverted route, communicates fuel concern to company with quantified options, and documents the fuel decision as part of the voyage risk assessment.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No fuel sufficiency calculation for diversion at higher speed', 'severity' => 'critical'],
                    ['flag' => 'No charter party clause analysis (weather deviation rights)',    'severity' => 'major'],
                    ['flag' => 'No weather risk assessment',                                    'severity' => 'critical'],
                    ['flag' => 'No company consultation on commercial vs safety decision',       'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'Charter party terms (speed/consumption clause)',
                    'Heavy weather avoidance procedures',
                    'SOLAS Chapter V (voyage planning)',
                    'Company SMS voyage planning',
                    'MARPOL Annex VI (fuel efficiency)',
                ],
                'red_flags_json' => [
                    'Ignoring storm to meet ETA',
                    'Running out of fuel by maintaining CP speed with diversion',
                    'No weather routing consideration',
                    'Making commercial decision without safety assessment',
                ],
            ],

        ];
    }
}
