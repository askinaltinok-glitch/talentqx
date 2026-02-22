<?php

namespace Database\Seeders;

use App\Models\MaritimeScenario;
use Illuminate\Database\Seeder;

/**
 * Populate OFFSHORE scenarios with production-quality content.
 *
 * Idempotent: updates existing rows by scenario_code.
 * Run: php82 artisan db:seed --class=OffshoreScenarioContentSeeder --force
 */
class OffshoreScenarioContentSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = array_merge(
            $this->getScenariosSlot1to4(),
            $this->getScenariosSlot5to8(),
        );

        foreach ($scenarios as $code => $data) {
            MaritimeScenario::where('scenario_code', $code)->update($data);
            $this->command->info("Updated: {$code}");
        }

        $activated = MaritimeScenario::where('command_class', 'OFFSHORE')
            ->where('version', 'v2')
            ->update(['is_active' => true]);

        $this->command->info("OFFSHORE scenario content seeded and activated ({$activated} scenarios).");
    }

    private function getScenariosSlot1to4(): array
    {
        return [

            // ══════════════════════════════════════════════════════════════
            // SLOT 1 — NAV_COMPLEX — DP transit / close-quarters / supply traffic
            // ══════════════════════════════════════════════════════════════
            'OFFSHORE_S01_NAV_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 4,500 DWT Platform Supply Vessel (PSV) approaching a congested offshore field in the UK North Sea for cargo discharge at a semi-submersible drilling rig. The 500m safety zone is active with two other supply vessels waiting for their approach window. An anchor-handling vessel is deploying anchors for a jack-up rig being positioned 0.8 NM from your destination. A standby vessel is conducting emergency response duties in the field. Visibility has reduced to 1.5 NM in fog patches and the forecast shows further deterioration. The rig has assigned you a specific approach heading of 315° which takes you across the prevailing current at 45°. Your DP system is in transit mode. The rig radio operator is handling multiple vessel communications simultaneously and responses are delayed. ECDIS shows two charted subsea structures within 200m of your approach corridor.',
                        'your_position'       => 'Bridge, command. C/O as co-pilot on DP desk, DPO monitoring DP status, helmsman standby for manual backup.',
                        'available_resources' => 'DP Class 2 system with dual DGPS, HPR/USBL, and 3 gyros, ECDIS with field layout and subsea hazard overlay, two ARPA radars, AIS, VHF Ch 16 and field operations channel, FMEA documented, vessel specific DP operations manual, 500m zone entry checklist, 4 main engines and 2 bow thrusters, 2 stern thrusters.',
                        'current_conditions'  => 'Visibility 1.5 NM (fog patches), wind WSW 22 knots, current 1.2 knots setting E, significant wave height 2.5m, swell 1.5m from NW, rig heave ±1.5m, multiple vessels operating in field.',
                    ],
                    'tr' => [
                        'situation'           => 'Kuzey Denizi\'nde yoğun bir açık deniz sahasında yarı batık sondaj kulesine yük boşaltmak üzere yaklaşan 4.500 DWT Platform Tedarik Gemisi\'nin (PSV) kaptanısınız. 500m güvenlik bölgesi aktif, iki başka tedarik gemisi yaklaşım sıralarını bekliyor. Bir çapa alma gemisi, varış noktanızdan 0,8 mil mesafedeki jack-up kule için çapa atıyor. Sahada bir standby gemisi acil müdahale görevinde. Sis nedeniyle görüş 1,5 mile düşmüş, tahmin daha da kötüleşme gösteriyor. Kule size hakim akıntıya 45° açıyla çaprazlayan 315° yaklaşım rotası atamış. DP sisteminiz transit modunda. Kule radyo operatörü çoklu gemi iletişimlerini yönetiyor ve yanıtlar gecikiyor. ECDIS yaklaşım koridorunuz içinde 200m mesafede iki denizaltı yapısı gösteriyor.',
                        'your_position'       => 'Köprüüstü, komuta. Birinci zabit DP masasında yardımcı pilot, DPO DP durumunu izliyor, dümenci manuel yedek için hazır.',
                        'available_resources' => 'DP Sınıf 2 sistem (çift DGPS, HPR/USBL, 3 cayro), saha düzeni ve denizaltı tehlike katmanlı ECDIS, iki ARPA radar, AIS, VHF ve saha operasyon kanalı, FMEA belgelenmiş, gemi spesifik DP operasyon kılavuzu, 500m bölge giriş kontrol listesi, 4 ana makine ve 2 baş itici, 2 kıç itici.',
                        'current_conditions'  => 'Görüş 1,5 mil (sis), rüzgar BGB 22 knot, akıntı 1,2 knot D yönlü, dalga 2,5m, kabarma 1,5m KB, kule heave ±1,5m, sahada çoklu gemi operasyonu.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан PSV 4 500 DWT, подход к полупогружной платформе в загруженном месторождении Северного моря. В 500-м зоне два других PSV ждут очереди. AHTS ставит якоря для джекапа в 0,8 мили. Видимость 1,5 мили (туман), ухудшается. Платформа назначила подход 315° через течение (1,2 узла на E). DP в транзитном режиме. Оператор платформы перегружен. ЭКНИС показывает подводные конструкции в 200 м от коридора.',
                        'your_position'       => 'Мостик, командование. Старпом на DP-пульте, DPO мониторит, рулевой наготове.',
                        'available_resources' => 'DP Класс 2 (DGPS×2, HPR/USBL, 3 гирокомпаса), ЭКНИС с подводными объектами, два САРП, AIS, УКВ, FMEA, чеклист входа в 500-м зону, 4 ГД + 2 носовых + 2 кормовых подруливающих.',
                        'current_conditions'  => 'Видимость 1,5 мили, ветер ЗЮЗ 22 узла, течение 1,2 узла на E, волна 2,5 м, зыбь 1,5 м, множество судов.',
                    ],
                    'az' => [
                        'situation'           => 'Şimali Dənizdə sıx ofşor sahəsində yarımbatıq platforma yaxınlaşan 4.500 DWT PSV-nin kapitanısınız. 500m zonada 2 digər PSV növbə gözləyir. AHTS 0,8 mil məsafədə cekap üçün lövbər atır. Dumanda görmə 1,5 mil, pisləşir. Platforma 315° yaxınlaşma kursu təyin edib (axına 45° çarpaz). DP transit rejimdədir. Operator yüklənib. ECDIS yaxınlaşma koridorunda sualtı konstruksiyalar göstərir.',
                        'your_position'       => 'Körpüüstü, komanda. Birinci stürman DP-masada, DPO monitorinq, sükandar hazır.',
                        'available_resources' => 'DP Sinif 2 (DGPS×2, HPR/USBL, 3 giro), ECDIS, iki radar, AIS, VHF, FMEA, 500m zona çeklisti, 4 əsas mühərrik + 2 baş + 2 arxa itələyici.',
                        'current_conditions'  => 'Görmə 1,5 mil, QŞQ külək 22 knot, axın 1,2 knot Ş, dalğa 2,5 m, çoxsaylı gəmilər.',
                    ],
                ],
                'decision_prompt'      => 'Describe your approach plan for entering the 500m safety zone and positioning alongside the rig in these conditions. Address DP setup, reference system selection, traffic deconfliction, subsea hazard management, and your abort criteria.',
                'decision_prompt_i18n' => [
                    'tr' => '500m güvenlik bölgesine giriş ve bu koşullarda kuleye yanaşma planınızı açıklayın. DP kurulumu, referans sistemi seçimi, trafik yönetimi, denizaltı tehlike yönetimi ve iptal kriterlerinizi ele alın.',
                    'ru' => 'Опишите план входа в 500-м зону и позиционирования: настройка DP, выбор реферeнс-систем, разрешение трафика, подводные опасности и критерии прерывания.',
                    'az' => '500m zonaya giriş və platformaya yaxınlaşma planınızı təsvir edin: DP quraşdırma, referans sistem seçimi, trafik idarəsi, sualtı təhlükələr və ləğv meyarları.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'dp_setup_and_approach_planning',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Enters 500m zone without completing DP checklists or verifying reference systems. No consequence analysis performed. DP mode not appropriate for the operation.',
                            '2' => 'Completes basic DP checks but does not verify all reference systems against each other. Consequence analysis incomplete. Approach heading accepted without considering cross-current effects.',
                            '3' => 'Proper DP setup: completes 500m zone entry checklist, verifies DGPS and HPR/USBL references are tracking and agreeing, performs consequence analysis (worst-case failure), selects appropriate DP mode for approach, accounts for cross-current on the 315° heading.',
                            '4' => 'Thorough DP management: full pre-approach checklist with all reference systems verified and compared, consequence analysis documented (identifies worst-case failure scenario — loss of one DGPS with remaining references adequate), DP footprint calculated for the environmental conditions, approach speed planned considering stopping distance, power and thruster margins confirmed adequate, discusses cross-current approach with rig and requests alternative heading if margins insufficient.',
                            '5' => 'Expert DP approach: comprehensive pre-entry preparation including FMEA review for the specific environmental conditions, all reference systems (DGPS×2, HPR, USBL, gyros) verified, compared, and weighted appropriately, consequence analysis for multiple failure modes documented, DP capability plot reviewed against current wind/wave/current to confirm adequate margins for approach heading, approach speed profile planned with defined waypoints, cross-current effect calculated and compensated in approach plan, communication protocol agreed with rig and all field vessels, manual backup plan rehearsed with helmsman, clear go/no-go criteria established before entering 500m zone.',
                        ],
                    ],
                    [
                        'axis'   => 'traffic_and_field_management',
                        'weight' => 0.25,
                        'rubric_levels' => [
                            '1' => 'Enters the field without awareness of other vessel operations. No coordination with AHTS or waiting PSVs. Ignores the 500m zone management protocol.',
                            '2' => 'Aware of other vessels but does not actively coordinate. Relies solely on rig operator for deconfliction despite the operator being overloaded.',
                            '3' => 'Coordinates with the rig and monitors other vessels on AIS and radar. Establishes communication with the AHTS to deconflict anchor operations. Waits for clear approach window.',
                            '4' => 'Active field management: communicates directly with all vessels in the field (PSVs, AHTS, standby vessel) on the operations channel, confirms AHTS anchor pattern clear of approach corridor, verifies standby vessel position, establishes passing arrangements with waiting PSVs, maintains continuous radar/AIS plot of all field traffic.',
                            '5' => 'Expert field coordination: comprehensive situational awareness of all operations — confirms AHTS anchor pattern and wire positions plotted on ECDIS, coordinates approach timing to avoid conflicts with anchor deployment, communicates with all field vessels establishing a clear traffic management sequence, monitors subsea hazards on ECDIS overlay continuously during approach, contingency plan for anchor wire interaction, ensures standby vessel is aware of own approach for emergency coordination purposes.',
                        ],
                    ],
                    [
                        'axis'   => 'environmental_limits_and_abort_criteria',
                        'weight' => 0.25,
                        'rubric_levels' => [
                            '1' => 'No defined abort criteria. Continues approach regardless of deteriorating visibility or environmental conditions exceeding DP capability.',
                            '2' => 'Vague abort criteria ("if conditions get too bad"). No reference to DP capability plot or specific environmental limits.',
                            '3' => 'Defined abort criteria based on DP capability plot: maximum wind, wave, and current limits established, visibility minimum defined, specific DP alarms triggering abort (e.g., reference system loss, thruster fault, position excursion).',
                            '4' => 'Comprehensive abort criteria: DP capability plot reviewed for current conditions with safety margins, specific trigger points for each environmental parameter (wind > 25kts, Hs > 3m, visibility < 0.5NM), DP alarm hierarchy defined (yellow → reduce operations, red → immediate drive-off), escape heading pre-planned clear of subsea structures and other vessels.',
                            '5' => 'Expert environmental management: dynamic abort criteria that account for forecast deterioration (not just current conditions), DP capability plot continuously monitored with trend analysis, multiple abort scenarios pre-planned (controlled drive-off, emergency drive-off, manual backup), escape routes plotted on ECDIS clear of all subsea and surface hazards, weather window calculated for the complete operation (approach + cargo + departure), clear decision point: if conditions are forecast to exceed limits during cargo operations, delay approach rather than risk being on location when limits are exceeded.',
                        ],
                    ],
                    [
                        'axis'   => 'bridge_team_and_dp_crew_coordination',
                        'weight' => 0.20,
                        'rubric_levels' => [
                            '1' => 'No team briefing before approach. DPO, C/O, and Master roles unclear. No communication protocol for the operation.',
                            '2' => 'Basic role assignment but no structured briefing. Communication during approach is reactive rather than proactive.',
                            '3' => 'Pre-approach briefing conducted: roles defined (Master command, C/O co-pilot, DPO monitoring DP status), communication protocol agreed, abort actions assigned to each team member.',
                            '4' => 'Structured team management: full toolbox talk/briefing covering approach plan, DP configuration, environmental conditions, abort criteria, and role assignments with backup responsibilities. Closed-loop communication protocol. Manual backup plan discussed with helmsman.',
                            '5' => 'Exemplary crew coordination: comprehensive pre-approach briefing documented, roles and responsibilities clear including backup transitions (DPO takes manual if system fails, helmsman ready with pre-set heading), communication protocol with standard phraseology, specific monitoring assignments (C/O: traffic/radar, DPO: DP status/references, Master: overall command/rig comms), escalation triggers defined, debrief planned after operation.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No DP consequence analysis before entering 500m zone',       'severity' => 'critical'],
                    ['flag' => 'No verification of DP reference system agreement',           'severity' => 'critical'],
                    ['flag' => 'No abort criteria defined before approach',                  'severity' => 'critical'],
                    ['flag' => 'No coordination with other vessels in the field',             'severity' => 'major'],
                    ['flag' => 'No subsea hazard awareness or ECDIS overlay check',          'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'IMCA M 103 — Guidelines for the design and operation of DP vessels',
                    'IMCA M 182 — DP station keeping incidents',
                    'IMO MSC/Circ.645 — Guidelines for vessels with DP systems',
                    'Company DP operations manual and FMEA',
                    'UKCS 500m zone regulations and field-specific operating procedures',
                ],
                'red_flags_json' => [
                    'Entering 500m zone without completing DP checklists and consequence analysis',
                    'No reference system cross-check before close-quarters operations',
                    'Approaching with cross-current without assessing DP capability margins',
                    'No abort criteria or escape plan defined',
                    'Ignoring visibility deterioration during approach to installation',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 2 — CMD_SCALE — Client/rig superintendent pressure + stop authority
            // ══════════════════════════════════════════════════════════════
            'OFFSHORE_S02_CMD_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 5,000 DWT PSV conducting cargo operations alongside a production FPSO in West Africa. The FPSO OIM (Offshore Installation Manager) is demanding you continue cargo discharge despite weather conditions approaching your vessel\'s DP operational limits. Wind has increased to 28 knots from the beam with gusts to 35 knots, and the significant wave height is now 3.2m. Your DP capability plot shows you are at the yellow zone boundary. The client representative on the FPSO is putting pressure via radio, stating "the production shutdown costs $2 million per day and we need these chemicals on board before the weather window closes completely." Your company\'s operations manager ashore is also pressing you to "do what you can to complete." The C/O reports that the crane operator on the FPSO has expressed concern about dynamic loads on the crane wire with the current vessel motions. You have 15 of 40 lifts remaining. The forecast shows conditions worsening to 40 knots and 4.5m Hs within 6 hours.',
                        'your_position'       => 'Bridge, DP operations. You are simultaneously monitoring DP, managing communications with the FPSO, and assessing the environmental conditions.',
                        'available_resources' => 'DP Class 2 system with environmental monitoring, DP capability plot (wind/wave envelope), crane operations checklist, company SMS with defined operational limits, vessel-specific DP operations manual, VHF to FPSO and company, weather forecast service, C/O on deck managing cargo, DPO at DP desk.',
                        'current_conditions'  => 'Wind beam 28 knots (gusts 35), Hs 3.2m, DP yellow zone boundary, 15 lifts remaining of 40, crane dynamic loads increasing, forecast worsening, night approaching.',
                    ],
                    'tr' => [
                        'situation'           => 'Batı Afrika\'da bir FPSO yanında yük operasyonu yapan 5.000 DWT PSV\'nin kaptanısınız. FPSO OIM, hava koşulları geminizin DP operasyonel limitlerine yaklaşmasına rağmen yük boşaltmaya devam etmenizi talep ediyor. Rüzgar bortttan 28 knota yükseldi, 35 knot hamleleri var, dalga yüksekliği 3,2m. DP kapasite çizelgeniz sarı bölge sınırında olduğunuzu gösteriyor. FPSO\'daki müşteri temsilcisi "üretim durması günde 2 milyon dolara mal oluyor, bu kimyasalları hava penceresi tamamen kapanmadan almamız gerekiyor" diyerek baskı yapıyor. Kıyıdaki şirket operasyon müdürünüz de "yapabildiğiniz kadarını yapın" diyor. Birinci Zabit, FPSO\'daki vinç operatörünün mevcut gemi hareketleriyle vinç halatındaki dinamik yüklerden endişe duyduğunu bildiriyor. 40 kaldırmanın 15\'i kalmış. Tahmin 6 saat içinde 40 knot ve 4,5m dalga gösteriyor.',
                        'your_position'       => 'Köprüüstü, DP operasyonları. DP izleme, FPSO ile iletişim ve çevresel koşul değerlendirmesini eş zamanlı yönetiyorsunuz.',
                        'available_resources' => 'Çevre izlemeli DP Sınıf 2, DP kapasite çizelgesi, vinç operasyon kontrol listesi, tanımlı operasyonel limitlerle şirket SMS, gemi spesifik DP kılavuzu, FPSO ve şirkete VHF, hava tahmin servisi, güvertede Birinci Zabit, DP masasında DPO.',
                        'current_conditions'  => 'Bort rüzgarı 28 knot (hamle 35), Hs 3,2m, DP sarı bölge sınırı, 40 kaldırmadan 15 kalmış, vinç dinamik yükleri artıyor, tahmin kötüleşiyor, gece yaklaşıyor.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан PSV 5 000 DWT, грузовые операции у FPSO в Западной Африке. OIM FPSO требует продолжать, несмотря на приближение к пределам DP: ветер 28 узлов (порывы 35) в борт, Hs 3,2 м — жёлтая зона DP. Представитель клиента давит: "остановка добычи — $2 млн/день". Береговой менеджер тоже настаивает. Крановщик выражает обеспокоенность динамическими нагрузками. Осталось 15 из 40 подъёмов. Прогноз: через 6 часов — 40 узлов, 4,5 м.',
                        'your_position'       => 'Мостик, DP-операции. Параллельно мониторинг DP, связь с FPSO и оценка погоды.',
                        'available_resources' => 'DP Класс 2, графики способности DP, чеклист крановых операций, SMS компании с установленными лимитами, руководство DP, УКВ, метеосервис, старпом на палубе, DPO за пультом.',
                        'current_conditions'  => 'Ветер в борт 28 (порывы 35), Hs 3,2 м, граница жёлтой зоны DP, 15 из 40 подъёмов, прогноз ухудшения.',
                    ],
                    'az' => [
                        'situation'           => 'Qərbi Afrikada FPSO yanında yük əməliyyatı aparan 5.000 DWT PSV-nin kapitanısınız. FPSO OIM hava şəraiti DP limitlərinə yaxınlaşmasına baxmayaraq davam etməyi tələb edir: bort küləyi 28 knot (şıdırğı 35), Hs 3,2 m — DP sarı zona sərhədi. Müştəri: "dayanma günə $2 mln". Şirkət müdiri də israr edir. Kran operatoru dinamik yüklərdən narahatdır. 40 qaldırışdan 15 qalıb. Proqnoz: 6 saatda 40 knot, 4,5 m.',
                        'your_position'       => 'Körpüüstü, DP əməliyyatları. Paralel DP monitorinqi, FPSO ilə rabitə və hava qiymətləndirməsi.',
                        'available_resources' => 'DP Sinif 2, DP qabiliyyət qrafiki, kran çeklisti, SMS, DP təlimatı, VHF, meteoroloji xidmət, birinci stürman göyərtədə, DPO pultda.',
                        'current_conditions'  => 'Bort küləyi 28 (şıdırğı 35), Hs 3,2 m, DP sarı zona, 15/40 qaldırış, proqnoz pisləşir.',
                    ],
                ],
                'decision_prompt'      => 'How do you respond to the pressure from the OIM and your company to continue operations? Explain your decision process, how you apply your stop-work authority, and how you manage the commercial/safety conflict.',
                'decision_prompt_i18n' => [
                    'tr' => 'OIM ve şirketinizden operasyonlara devam etme baskısına nasıl yanıt veriyorsunuz? Karar sürecinizi, işi durdurma yetkinizi nasıl kullandığınızı ve ticari/güvenlik çelişkisini nasıl yönettiğinizi açıklayın.',
                    'ru' => 'Как вы реагируете на давление OIM и компании? Опишите процесс решения, применение полномочий на остановку работ и управление конфликтом безопасность/коммерция.',
                    'az' => 'OIM və şirkətdən davam etmə təzyiqinə necə cavab verirsiniz? Qərar prosesini, iş dayandırma səlahiyyətinizi və kommersiya/təhlükəsizlik münaqişəsini necə idarə etdiyinizi izah edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'operational_stop_authority',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Continues operations under pressure without assessing DP capability or crane safety limits. Does not exercise stop-work authority. Allows commercial pressure to override safety.',
                            '2' => 'Hesitates and continues a few more lifts "to see how it goes." Does not make a clear decision. Allows the situation to escalate into the red zone before acting.',
                            '3' => 'Recognises the yellow zone status and pauses to assess. Reviews DP capability against forecast conditions. Decides to stop if conditions approach red zone. Communicates decision clearly to OIM.',
                            '4' => 'Clear exercise of stop-work authority: reviews DP capability plot against current AND forecast conditions, recognises that with conditions worsening, remaining at yellow zone boundary during 6+ hours of cargo operations is unsafe, stops crane operations, informs OIM and company with specific technical justification (DP capability data, crane load limits, forecast), offers to resume if weather improves.',
                            '5' => 'Expert operational decision: stops operations proactively before conditions deteriorate further, provides comprehensive technical justification referencing DP capability plot data, crane manufacturer\'s dynamic load limits, company SMS operational limits, and IMCA guidelines. Documents the decision with environmental data timestamps. Proposes a plan for the remaining 15 lifts when conditions improve. Maintains professional relationship with OIM while being firm on safety. Demonstrates understanding that ISM Code Master\'s authority and company stop-work policy support this decision. Considers planning for departure from location if conditions will exceed safe station-keeping limits.',
                        ],
                    ],
                    [
                        'axis'   => 'stakeholder_communication',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No structured communication. Either capitulates silently or refuses without explanation. Does not manage the multiple stakeholders.',
                            '2' => 'Communicates the decision but without technical justification. Allows argument with OIM to become confrontational. Does not inform company until after the fact.',
                            '3' => 'Communicates clearly with OIM citing DP limits and forecast, informs company operations of the situation, maintains professional tone.',
                            '4' => 'Effective multi-party management: formal notification to OIM with specific technical limits, company informed in real-time with data supporting the decision, FPSO crane operator\'s concerns cited as additional safety factor, all communications logged.',
                            '5' => 'Exemplary communication: professional and data-driven discussion with OIM referencing specific DP envelope data and crane limits, company briefed with full risk assessment, written confirmation of operational limits to OIM, positive proposal for resumption plan, crew briefed on decision rationale, all communications documented with timestamps for potential incident investigation.',
                        ],
                    ],
                    [
                        'axis'   => 'dp_capability_and_risk_assessment',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No reference to DP capability plot. Ignores yellow zone status. No consideration of forecast conditions or crane dynamic limits.',
                            '2' => 'Notes yellow zone but does not analyse trend or forecast impact. No systematic risk assessment for remaining operations.',
                            '3' => 'Reviews DP capability plot, confirms yellow zone, considers forecast deterioration. Identifies that remaining operations would extend into conditions exceeding DP limits.',
                            '4' => 'Systematic risk assessment: DP capability reviewed against current and forecast conditions, calculates time to red zone based on forecast trend, assesses crane dynamic loads at current vessel motions, evaluates risk of position excursion near FPSO, considers night operations factor.',
                            '5' => 'Comprehensive dynamic risk assessment: continuous DP capability monitoring with trend analysis, detailed forecast integration showing when red zone will be reached, crane dynamic load analysis including shock loading risk in increasing seas, position excursion risk analysis near the FPSO structure, assessment of emergency drive-off capability in current conditions, considers accumulated crew fatigue from the operation, evaluates the risk of the remaining 15 lifts vs. the total exposure time needed, documents full risk analysis for company and client records.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No exercise of stop-work authority despite DP yellow zone and worsening forecast', 'severity' => 'critical'],
                    ['flag' => 'No DP capability assessment against forecast conditions',                         'severity' => 'critical'],
                    ['flag' => 'No consideration of crane dynamic load limits',                                    'severity' => 'major'],
                    ['flag' => 'No formal communication of decision to OIM with technical justification',          'severity' => 'major'],
                    ['flag' => 'No documentation of environmental data and operational decision',                   'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'IMCA M 103 — Guidelines for DP vessel operations',
                    'IMCA SEL 019 — Guidelines for lifting operations',
                    'ISM Code Section 5 — Master\'s overriding authority',
                    'Company SMS — DP operational limits and stop-work policy',
                    'IMO MSC/Circ.645 — DP system guidelines',
                    'Client/field-specific operating procedures and SIMOPS plan',
                ],
                'red_flags_json' => [
                    'Continuing operations in DP yellow/red zone under commercial pressure',
                    'No reference to DP capability plot when assessing operational limits',
                    'Allowing OIM or client to override Master\'s safety decision',
                    'No consideration of forecast deterioration when deciding to continue',
                    'No documentation of decision to stop or continue operations',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 3 — TECH_DEPTH — DP alert cascade + reference disagreement
            // ══════════════════════════════════════════════════════════════
            'OFFSHORE_S03_TECH_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a DP Class 2 AHTS (Anchor Handling Tug Supply) vessel positioned 50m off the stern of a semi-submersible drilling rig in the Norwegian Sea, providing stand-by DP support during drilling operations. At 0315, the DP system generates multiple alarms in rapid succession: first, DGPS Reference 1 shows a position jump of 8 metres; within 30 seconds, the HPR (Hydro-acoustic Position Reference) transponder drops out with a "no signal" alarm; your heading reference shows a 2.5° disagreement between Gyro 1 (feeding the primary DP) and the MRU (Motion Reference Unit). The DP system has automatically rejected DGPS-1 and is now running on DGPS-2 and the remaining HPR transponder only. Your DPO reports the vessel is showing a 3-metre position excursion to starboard, toward the rig. The rig has live well operations underway — any vessel contact with the rig structure or subsea equipment could trigger a catastrophic well control event. Your FMEA identifies loss of two position references as a "high consequence" scenario requiring immediate action.',
                        'your_position'       => 'Bridge, awakened by DP alarm. DPO on watch at DP desk, 2/O as OOW on bridge.',
                        'available_resources' => 'DP Class 2 system (now degraded: DGPS-2 only + 1 HPR transponder), Gyro 1 and 2 (disagreement), MRU, wind sensor, 3 main engines, 2 bow thrusters, 2 stern thrusters, FMEA documentation, DP operations manual, VHF to rig, rig standby vessel nearby, ETO available for reference system troubleshooting.',
                        'current_conditions'  => 'Night, wind NW 20 knots, Hs 2.0m, visibility good, positioned 50m off rig stern, rig has live well, 3m starboard excursion observed, DP operating on degraded reference configuration.',
                    ],
                    'tr' => [
                        'situation'           => 'Norveç Denizi\'nde yarı batık sondaj kulesinin kıç tarafından 50m mesafede konumlanmış DP Sınıf 2 AHTS gemisinin kaptanısınız, sondaj operasyonları sırasında beklemede DP desteği sağlıyorsunuz. 0315\'te DP sistemi art arda birden fazla alarm üretiyor: önce DGPS Referans 1, 8 metrelik pozisyon sıçraması gösteriyor; 30 saniye içinde HPR transponder "sinyal yok" alarmıyla devre dışı kalıyor; yön referansı Cayro 1 ile MRU arasında 2,5° uyumsuzluk gösteriyor. DP sistemi DGPS-1\'i otomatik reddetmiş ve şimdi sadece DGPS-2 ve kalan HPR transponder ile çalışıyor. DPO\'nuz geminin sancağa (kuleye doğru) 3 metrelik pozisyon sapması gösterdiğini bildiriyor. Kulede canlı kuyu operasyonu devam ediyor — herhangi bir temas felaket bir kuyu kontrol olayını tetikleyebilir. FMEA\'nız iki pozisyon referansı kaybını acil eylem gerektiren "yüksek sonuçlu" senaryo olarak tanımlıyor.',
                        'your_position'       => 'Köprüüstü, DP alarmıyla uyandınız. DPO DP masasında vardiyada, 2. Zabit köprüde VZ.',
                        'available_resources' => 'DP Sınıf 2 (düşük kapasite: sadece DGPS-2 + 1 HPR), Cayro 1 ve 2 (uyumsuz), MRU, rüzgar sensörü, 3 ana makine, 2 baş + 2 kıç itici, FMEA, DP kılavuzu, kule ile VHF, yakında standby gemisi, referans sistemi sorun giderme için ETO.',
                        'current_conditions'  => 'Gece, rüzgar KB 20 knot, Hs 2,0m, görüş iyi, kule kıçından 50m, canlı kuyu, 3m sancak sapması, DP düşük referans konfigürasyonunda.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан DP Class 2 AHTS, позиция 50 м от кормы полупогружной буровой в Норвежском море. В 03:15 каскад аларм DP: DGPS-1 — скачок 8 м, HPR — потеря сигнала, гирокомпас/MRU — расхождение 2,5°. DP отклонил DGPS-1, работает на DGPS-2 + 1 HPR. Судно смещается на 3 м к правому борту (к буровой). На платформе — активная скважина. FMEA: потеря двух референсов — «высокие последствия».',
                        'your_position'       => 'Мостик, разбужен алармом. DPO на вахте, 2-й помощник — ВП.',
                        'available_resources' => 'DP Класс 2 (деградация: DGPS-2 + 1 HPR), 2 гирокомпаса (расхождение), MRU, 3 ГД + 2 носовых + 2 кормовых подруливающих, FMEA, руководство DP, УКВ, ETO для диагностики.',
                        'current_conditions'  => 'Ночь, ветер СЗ 20, Hs 2 м, видимость хорошая, 50 м от буровой, скважина активна, смещение 3 м к пр. борту.',
                    ],
                    'az' => [
                        'situation'           => 'Norveç dənizində yarımbatıq platformanın arxasından 50 m-də mövqelənmiş DP Sinif 2 AHTS-nin kapitanısınız. 03:15-də DP alarm kaskadı: DGPS-1 — 8 m sıçrayış, HPR — siqnal itkisi, girokompas/MRU — 2,5° fərq. DP DGPS-2 + 1 HPR-də işləyir. Gəmi 3 m sağa (platformaya doğru) sürüşür. Aktiv quyu var. FMEA: iki referansın itkisi — "yüksək nəticəli" ssenari.',
                        'your_position'       => 'Körpüüstü, alarm ilə oyandınız. DPO növbədə, 2-ci stürman ВП.',
                        'available_resources' => 'DP Sinif 2 (DGPS-2 + 1 HPR), 2 girokompas, MRU, 3 əsas mühərrik + 4 itələyici, FMEA, DP təlimatı, VHF, ETO.',
                        'current_conditions'  => 'Gecə, ŞQ külək 20, Hs 2 m, görmə yaxşı, platformadan 50 m, aktiv quyu, 3 m sürüşmə.',
                    ],
                ],
                'decision_prompt'      => 'Describe your immediate response to this DP alarm cascade. How do you diagnose the reference system failures, what is your decision regarding station-keeping vs. drive-off, and how do you manage the risk of proximity to the rig with live well operations?',
                'decision_prompt_i18n' => [
                    'tr' => 'Bu DP alarm kaskadına acil müdahalenizi açıklayın. Referans sistemi arızalarını nasıl teşhis edersiniz, pozisyon tutma vs. uzaklaşma kararınız nedir ve canlı kuyu operasyonu olan kuleye yakınlık riskini nasıl yönetirsiniz?',
                    'ru' => 'Опишите немедленную реакцию на каскад аларм DP. Как диагностируете отказы, каково решение — удержание позиции или отход, как управляете риском близости к буровой с активной скважиной?',
                    'az' => 'Bu DP alarm kaskadına dərhal reaksiyanızı təsvir edin. Referans nasazlıqlarını necə diaqnoz edirsiniz, mövqe saxlama/uzaqlaşma qərarınız nədir, aktiv quyusu olan platformaya yaxınlıq riskini necə idarə edirsiniz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'dp_alarm_response_and_diagnosis',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Panics or freezes at the alarm cascade. No systematic diagnosis. Does not reference FMEA or understand the consequence analysis implications.',
                            '2' => 'Acknowledges alarms but diagnosis is haphazard. Tries to troubleshoot individual alarms without assessing the overall DP redundancy status.',
                            '3' => 'Systematic alarm response: identifies DGPS-1 rejected (position jump), HPR lost, gyro/MRU disagreement. References FMEA for the degraded configuration. Assesses remaining DP capability. Decides on appropriate action based on redundancy status.',
                            '4' => 'Thorough diagnosis: analyses each alarm in sequence — DGPS-1 position jump (possible ionospheric/multipath), HPR dropout (acoustic interference from rig operations?), gyro/MRU disagreement (which is correct? checks Gyro 2 against GPS heading). References FMEA: loss of DGPS-1 + HPR = insufficient redundancy for close-quarters rig work. Assesses remaining DGPS-2 reliability (single point of failure). Makes informed decision on station-keeping viability.',
                            '5' => 'Expert DP systems diagnosis: rapid triage of alarm cascade, identifies potential common-cause failure (simultaneous DGPS and HPR loss suggests possible interference source or power supply issue), directs ETO to check reference system power supply and antenna connections, isolates gyro discrepancy (compares both gyros with MRU and GPS heading to identify the faulty unit), references FMEA specifically for "loss of 2 position references near installation with live well" scenario, calculates remaining DP integrity against IMCA guidelines, considers whether the alarm cascade could indicate a more fundamental system issue requiring immediate drive-off rather than troubleshooting on location.',
                        ],
                    ],
                    [
                        'axis'   => 'drive_off_decision_and_execution',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Does not consider drive-off despite degraded DP and position excursion toward the rig. Continues station-keeping with inadequate redundancy. No awareness of consequences near live well.',
                            '2' => 'Considers drive-off but delays the decision attempting to restore references first, while the vessel continues drifting toward the rig.',
                            '3' => 'Decides to execute controlled drive-off given insufficient DP redundancy per FMEA. Moves to a safe distance. Informs rig. Re-evaluates once at safe distance.',
                            '4' => 'Clear drive-off execution: recognises FMEA trigger (loss of 2 references = high consequence), initiates controlled drive-off on pre-planned heading clear of rig structure and subsea equipment, informs rig immediately of drive-off, monitors position throughout drive-off, establishes safe standby distance, then systematically troubleshoots references from safe position.',
                            '5' => 'Expert drive-off management: immediate recognition that 3m excursion toward rig with degraded DP and live well constitutes an intolerable risk, initiates drive-off without delay using pre-planned escape heading from the DP operations manual, controls drive-off speed and heading to avoid rig structure/anchor chains/subsea equipment, communicates with rig using pre-agreed emergency protocol ("driving off, driving off, driving off"), monitors all remaining references during drive-off for consistency, once at safe distance conducts systematic diagnosis, considers whether to attempt re-approach only after full redundancy restored, documents entire event timeline for IMCA incident reporting.',
                        ],
                    ],
                    [
                        'axis'   => 'rig_communication_and_well_safety',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No communication with rig about the DP situation. No awareness that vessel proximity to live well requires rig notification.',
                            '2' => 'Informs rig after drive-off but does not provide sufficient information for the rig to assess well safety implications.',
                            '3' => 'Communicates DP situation to rig promptly, informing them of the reference failures and position excursion. Rig can assess whether to initiate well control precautions.',
                            '4' => 'Proactive rig communication: immediate notification of DP degradation and position excursion, uses pre-agreed alarm phraseology, provides position data, advises rig to stand by for potential well control action, maintains communication throughout drive-off.',
                            '5' => 'Expert emergency communication: uses the established DP/rig emergency communication protocol (green/yellow/red status), immediate notification with specific data (position excursion magnitude and direction, reference status, intended action), coordinates with rig\'s well control team, confirms rig has initiated appropriate precautionary measures, maintains continuous situation updates during and after drive-off, participates in post-incident review with rig to identify lessons learned.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No reference to FMEA for the degraded DP configuration',         'severity' => 'critical'],
                    ['flag' => 'No drive-off decision despite position excursion toward rig',     'severity' => 'critical'],
                    ['flag' => 'No notification to rig of DP degradation and position excursion', 'severity' => 'critical'],
                    ['flag' => 'No systematic diagnosis of reference system failures',            'severity' => 'major'],
                    ['flag' => 'No awareness of live well risk from vessel position excursion',   'severity' => 'critical'],
                ],
                'expected_references_json' => [
                    'IMCA M 103 — DP vessel design and operation guidelines',
                    'IMCA M 182 — DP station keeping incidents and lessons learned',
                    'IMO MSC/Circ.645 — Guidelines for DP systems',
                    'IMO MSC.1/Circ.1580 — Guidelines for vessels and units with DP systems',
                    'Vessel-specific FMEA and DP operations manual',
                    'Company SMS — DP emergency procedures and drive-off criteria',
                ],
                'red_flags_json' => [
                    'Continuing station-keeping with degraded DP references near live well installation',
                    'Ignoring FMEA high-consequence trigger for loss of position references',
                    'No drive-off decision despite position excursion toward the rig',
                    'Attempting to troubleshoot references while drifting toward installation',
                    'No communication with rig about DP status and position excursion',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 4 — RISK_MGMT — SIMOPS + PTW + JSA management
            // ══════════════════════════════════════════════════════════════
            'OFFSHORE_S04_RISK_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a DP Class 2 PSV alongside a production platform in the Persian Gulf conducting simultaneous operations (SIMOPS): cargo discharge by crane (deck cargo and bulk chemicals), helicopter operations on the platform (30-minute intervals), and an ROV support vessel operating on the opposite side of the platform conducting subsea inspection. Your vessel is also scheduled to begin bunker transfer (MGO) from the platform in 45 minutes. The C/O has presented the Permit to Work (PTW) board showing 6 active permits: crane operations, chemical hose connection, hot work on the aft deck (generator repair), working at height (antenna maintenance), bunker transfer preparation, and a confined space entry for ballast tank inspection. The platform Safety Officer has called to say they are adding a 7th simultaneous activity — divers entering the water on the platform\'s port side for riser inspection. You notice that the JSA (Job Safety Analysis) for the crane operations does not reference the helicopter operations schedule, and the hot work permit location is within 10 metres of the chemical hose connection point. The PTW for confined space entry has not been signed by the C/O as the vessel\'s responsible officer.',
                        'your_position'       => 'Bridge, reviewing PTW board with C/O before the bunker transfer begins. DP operations ongoing.',
                        'available_resources' => 'PTW system (permit board with all active permits), JSA templates, SIMOPS plan agreed with platform, company SMS safety management procedures, platform Safety Officer on radio, C/O, Bosun, deck crew (8 available), DPO at DP desk, ETO for technical support, chemical cargo documentation (SDS sheets).',
                        'current_conditions'  => 'Daylight, wind SE 15 knots, calm seas, DP green zone, 6 active PTWs with conflicts identified, diving operations about to commence, helicopter schedule active.',
                    ],
                    'tr' => [
                        'situation'           => 'Basra Körfezi\'nde bir üretim platformu yanında eş zamanlı operasyonlar (SIMOPS) yürüten DP Sınıf 2 PSV\'nin kaptanısınız: vinçle yük boşaltma (güverte yükü ve dökme kimyasal), platformda helikopter operasyonları (30 dakika aralıklarla), ve platformun karşı tarafında ROV destek gemisi sualtı incelemesi yapıyor. Geminiz 45 dakika içinde platformdan yakıt transferine (MGO) başlayacak. Birinci Zabit, 6 aktif izin gösteren Çalışma İzni (PTW) panosunu sunuyor: vinç operasyonları, kimyasal hortum bağlantısı, kıç güvertede sıcak çalışma (jeneratör tamiri), yüksekte çalışma (anten bakımı), yakıt transfer hazırlığı ve balast tankı denetimi için kapalı alan girişi. Platform Emniyet Zabiti, 7. eş zamanlı faaliyet eklediğini bildiriyor — dalgıçlar boru muayenesi için suya giriyor. Vinç operasyonları JSA\'sının helikopter tarifesine atıfta bulunmadığını ve sıcak çalışma izin konumunun kimyasal hortum bağlantı noktasına 10 metreden yakın olduğunu fark ediyorsunuz. Kapalı alan girişi PTW\'si Birinci Zabit tarafından sorumlu zabit olarak imzalanmamış.',
                        'your_position'       => 'Köprüüstü, yakıt transferi başlamadan Birinci Zabit ile PTW panosunu inceliyorsunuz. DP operasyonları devam ediyor.',
                        'available_resources' => 'PTW sistemi, JSA şablonları, platformla mutabık SIMOPS planı, şirket SMS prosedürleri, platform Emniyet Zabiti (telsiz), Birinci Zabit, Lostromo, güverte mürettebatı (8 kişi), DPO, ETO, kimyasal yük belgeleri (SDS).',
                        'current_conditions'  => 'Gündüz, rüzgar GD 15 knot, sakin deniz, DP yeşil bölge, çakışmaları tespit edilmiş 6 aktif PTW, dalma operasyonları başlamak üzere, helikopter tarifesi aktif.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан PSV DP Класс 2 у производственной платформы в Персидском заливе. SIMOPS: краново-грузовые операции + химические шланги, вертолётные операции (каждые 30 мин), ROV с другой стороны платформы. Через 45 минут — бункеровка MGO. На доске PTW — 6 активных разрешений: кран, хим. шланг, огневые работы (ремонт генератора, 10 м от хим. шланга), работа на высоте, подготовка бункеровки, вход в замкнутое пространство (балластный танк, не подписан старпомом). Платформа добавляет 7-ю операцию — водолазы. JSA крановых работ не упоминает вертолётные операции.',
                        'your_position'       => 'Мостик, обзор PTW со старпомом. DP-операции продолжаются.',
                        'available_resources' => 'Система PTW, JSA, SIMOPS-план, SMS компании, платформенный офицер безопасности (радио), старпом, боцман, 8 палубных матросов, DPO, ETO, документация химгрузов (SDS).',
                        'current_conditions'  => 'День, ветер ЮВ 15, штиль, DP зелёная зона, 6 PTW с конфликтами, водолазные работы начинаются, вертолёты по графику.',
                    ],
                    'az' => [
                        'situation'           => 'Fars körfəzində istehsal platforması yanında SIMOPS aparan DP Sinif 2 PSV-nin kapitanısınız: kran ilə yük boşaltma + kimyəvi şlanq, helikopter əməliyyatları (30 dəq aralıq), ROV. 45 dəqiqəyə yanacaq transferi (MGO). PTW lövhəsində 6 aktiv icazə — kran, kimyəvi şlanq, isti iş (generatora təmir, kimyəvi şlanqa 10 m), hündürlükdə iş, yanacaq hazırlığı, qapalı sahəyə giriş (birinci stürman imzalamayıb). Platforma 7-ci əməliyyat əlavə edir — dalğıclar. Kran JSA-sı helikopter cədvəlinə istinad etmir.',
                        'your_position'       => 'Körpüüstü, birinci stürmanla PTW nəzərdən keçirmə. DP davam edir.',
                        'available_resources' => 'PTW sistemi, JSA, SIMOPS planı, SMS, platforma Təhlükəsizlik Zabiti, birinci stürman, losman, 8 matros, DPO, ETO, kimyəvi sənədlər (SDS).',
                        'current_conditions'  => 'Gündüz, CŞ külək 15, sakit, DP yaşıl zona, 6 PTW münaqişələrlə, dalğıc əməliyyatları başlayır, helikopter cədvəli aktiv.',
                    ],
                ],
                'decision_prompt'      => 'How do you manage this SIMOPS situation? Address: (1) the PTW conflicts you\'ve identified and how you resolve them, (2) your decision on whether to accept the 7th simultaneous activity (diving), (3) how you manage the bunker transfer in this context, and (4) your overall SIMOPS risk assessment approach.',
                'decision_prompt_i18n' => [
                    'tr' => 'Bu SIMOPS durumunu nasıl yönetiyorsunuz? Ele alın: (1) tespit ettiğiniz PTW çakışmaları ve çözümünüz, (2) 7. eş zamanlı faaliyet (dalma) kabulü kararınız, (3) bu bağlamda yakıt transferini nasıl yönetirsiniz, (4) genel SIMOPS risk değerlendirme yaklaşımınız.',
                    'ru' => 'Как управляете SIMOPS? Рассмотрите: (1) конфликты PTW и их разрешение, (2) решение по 7-й операции (водолазы), (3) управление бункеровкой, (4) общий подход к оценке рисков SIMOPS.',
                    'az' => 'Bu SIMOPS vəziyyətini necə idarə edirsiniz? Nəzərə alın: (1) müəyyən edilmiş PTW münaqişələri və həlli, (2) 7-ci əməliyyat (dalğıclar) qəbul qərarı, (3) yanacaq transferi idarəsi, (4) ümumi SIMOPS risk qiymətləndirmə yanaşması.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'ptw_conflict_identification_and_resolution',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Does not identify any PTW conflicts. Allows all 6 permits to run simultaneously without review. Does not notice the hot work/chemical proximity or unsigned confined space permit.',
                            '2' => 'Identifies one or two conflicts but does not systematically review all permits. Addresses issues individually without considering the overall SIMOPS picture.',
                            '3' => 'Identifies key conflicts: hot work within 10m of chemical hose (fire/explosion risk), unsigned confined space PTW, JSA missing helicopter reference. Suspends conflicting permits until resolved. Requires C/O to sign confined space permit before entry.',
                            '4' => 'Thorough PTW management: systematically reviews all 6 permits for conflicts, identifies hot work/chemical proximity (suspends hot work until chemical operations complete or relocates), identifies unsigned confined space entry (suspends until properly authorised with C/O signature and gas-free certificate), identifies JSA gap (crane operations must reference helicopter schedule — suspends crane during helo ops), establishes a PTW priority matrix for the SIMOPS.',
                            '5' => 'Expert PTW/SIMOPS management: complete audit of all permits including cross-referencing with SIMOPS plan, identifies all three conflicts plus additional risks (working at height during crane operations overhead interaction, bunker transfer gas-freeing requirements vs. hot work), suspends conflicting activities, re-sequences operations to eliminate simultaneous conflicts, ensures all PTWs are properly authorised with correct responsible officers, reviews JSAs for all activities to ensure cross-referencing, establishes a live SIMOPS board with time-based activity matrix, communicates revised plan to platform Safety Officer, documents all PTW actions.',
                        ],
                    ],
                    [
                        'axis'   => 'simops_risk_assessment',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No SIMOPS risk assessment. Treats each activity independently without considering interactions. Accepts all activities including diving without assessment.',
                            '2' => 'Acknowledges SIMOPS complexity but does not perform a structured risk assessment. Accepts diving without considering the impact on vessel operations.',
                            '3' => 'Performs basic SIMOPS assessment: considers interactions between crane/helo/bunker/diving, recognises that diving restricts vessel movement (cannot drive off while divers in water near rig), evaluates the 7th activity against the vessel\'s and crew\'s capacity to manage.',
                            '4' => 'Structured SIMOPS risk assessment: reviews the SIMOPS plan for all 7 proposed activities, identifies that diving on the platform port side restricts emergency drive-off options, assesses crew workload for managing 7 simultaneous activities, evaluates the DP implications (diving requires vessel to maintain position — no drive-off capability), considers whether the SIMOPS plan was designed for this many simultaneous activities.',
                            '5' => 'Comprehensive SIMOPS management: formal risk assessment of all 7 activities using the SIMOPS matrix, identifies critical incompatibilities (diving + crane operations = restricted vessel movement + overhead hazards, hot work + chemical transfer = fire/explosion risk, bunker transfer + all activities = additional pollution risk), refuses the 7th activity (diving) on the basis that it exceeds the safe SIMOPS capacity and restricts emergency response, proposes a phased operation plan that sequences incompatible activities, ensures each activity has a dedicated responsible person, establishes clear stop-work triggers for each activity and an overall SIMOPS stop-work protocol.',
                        ],
                    ],
                    [
                        'axis'   => 'bunker_transfer_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Proceeds with bunker transfer without considering SIMOPS implications. No additional safety measures for bunkering alongside crane and chemical operations.',
                            '2' => 'Plans to bunker but does not assess the combined risk with ongoing operations. Basic checklist completed but no cross-referencing with SIMOPS.',
                            '3' => 'Reviews bunker transfer requirements against SIMOPS: identifies that bunkering adds pollution risk during simultaneous crane and chemical operations, ensures hot work suspended during bunkering, completes ship/platform transfer checklist.',
                            '4' => 'Systematic bunker management in SIMOPS context: delays bunker start until hot work completed, ensures chemical operations supervised during bunkering, reviews spill response readiness, confirms DP stability for the combined operation, ship/platform checklist completed with both parties signing.',
                            '5' => 'Expert bunker management: refuses to commence bunker transfer while hot work is active anywhere on the vessel, sequences bunkering after chemical operations complete to avoid cumulative pollution risk, ensures dedicated bunker watch officer separate from cargo operations, pre-positions spill response equipment, confirms DP status adequate for bunker transfer with all other ongoing operations, documents the decision to delay bunkering in the operations log with justification.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No identification of hot work / chemical hose proximity conflict',  'severity' => 'critical'],
                    ['flag' => 'No action on unsigned confined space entry PTW',                    'severity' => 'critical'],
                    ['flag' => 'No review of JSAs for cross-referencing between activities',        'severity' => 'major'],
                    ['flag' => 'No SIMOPS risk assessment for 7 simultaneous activities',           'severity' => 'critical'],
                    ['flag' => 'No consideration of diving impact on vessel emergency response',    'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'IMCA SEL 019 — Guidelines for lifting operations from offshore supply vessels',
                    'IMCA M 202 — Guidelines for SIMOPS',
                    'Company SMS — PTW system and SIMOPS procedures',
                    'ISM Code Section 7 — Shipboard operations planning',
                    'ISGOTT Chapter 26 (bunkering operations)',
                    'Field-specific SIMOPS plan and platform operating procedures',
                    'IMCA D 014 — Guidelines on safety in diving operations',
                ],
                'red_flags_json' => [
                    'Allowing hot work within 10m of chemical transfer operations',
                    'Permitting confined space entry without properly authorised PTW',
                    'Accepting unlimited simultaneous activities without SIMOPS risk assessment',
                    'Not suspending crane operations during helicopter approach/departure',
                    'Commencing bunker transfer during active hot work',
                    'Not considering diving operations impact on DP drive-off capability',
                ],
            ],

        ];
    }

    private function getScenariosSlot5to8(): array
    {
        return [

            // ══════════════════════════════════════════════════════════════
            // SLOT 5 — CREW_LEAD — Toolbox talk failure + near-miss culture
            // ══════════════════════════════════════════════════════════════
            'OFFSHORE_S05_CREW_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a DP Class 2 PSV operating in the Gulf of Mexico. Over the past two weeks, three near-miss incidents have occurred: (1) a 2-tonne cargo container swung uncontrolled during crane operations because the tag line handler was not briefed on wind conditions — no injury but the container struck the handrail; (2) a deck crew member entered the chemical storage area without PPE because "we always do it this way"; (3) the DPO acknowledged a DP position alarm and reset it without investigating — the vessel had drifted 5m before correction. Upon investigation, you find that toolbox talks before operations are being conducted as a tick-box exercise — the Bosun reads from a script while the crew signs the attendance sheet without discussion. The JSAs are being copied from previous operations without updating for current conditions. Three crew members (2 Filipino ABs and 1 Indian Motorman) have told the Safety Officer informally that they are afraid to report issues because "the Bosun shouts at us." Your company\'s behavioural safety programme requires a near-miss reporting culture, but the vessel has reported only 2 near-misses in 3 months compared to fleet average of 12.',
                        'your_position'       => 'Master, reviewing incident reports and Safety Officer\'s confidential feedback.',
                        'available_resources' => 'Safety Officer (dedicated), Bosun (experienced, 15 years, but authoritarian style), crew of 18 (mixed nationality), company behavioural safety programme documentation, near-miss reporting system, toolbox talk templates, JSA forms, vessel training record book, company DPA and HSEQ department reachable by email/satellite, IMCA safety culture resources.',
                        'current_conditions'  => 'In port between offshore trips, 2 days before next deployment. Crew morale mixed — experienced officers frustrated with crew complacency, junior crew intimidated. No injuries to date but clear near-miss trend.',
                    ],
                    'tr' => [
                        'situation'           => 'Meksika Körfezi\'nde faaliyet gösteren DP Sınıf 2 PSV\'nin kaptanısınız. Son iki haftada üç ramak kala olay meydana geldi: (1) mandal halatçısı rüzgar koşulları hakkında bilgilendirilmediği için vinç operasyonlarında 2 tonluk konteyner kontrolsüz sallandı — korkuluğa çarptı ama yaralanma olmadı; (2) güverte mürettebatı "biz hep böyle yapıyoruz" diyerek KKE olmadan kimyasal depolama alanına girdi; (3) DPO bir DP pozisyon alarmını araştırmadan onaylayıp sıfırladı — gemi düzeltmeden önce 5m sapmıştı. İncelemenizde, operasyonlar öncesi araç kutusu konuşmalarının formalite olarak yapıldığını — Lostromo bir metinden okurken ekibin tartışma olmadan katılım kağıdını imzaladığını buluyorsunuz. JSA\'lar mevcut koşullar için güncellenmeden önceki operasyonlardan kopyalanıyor. Üç mürettebat (2 Filipinli güverte eri ve 1 Hintli Motorcu) Emniyet Zabitine gayri resmi olarak "Lostromo bize bağırıyor" diyerek sorun bildirmekten korktuklarını söylemiş. Şirketin davranışsal güvenlik programı ramak kala raporlama kültürü gerektiriyor, ancak gemi 3 ayda filo ortalaması 12\'ye karşı sadece 2 ramak kala bildirmiş.',
                        'your_position'       => 'Kaptan, olay raporlarını ve Emniyet Zabitinin gizli geri bildirimini inceliyorsunuz.',
                        'available_resources' => 'Emniyet Zabiti, Lostromo (deneyimli, 15 yıl, otoriter tarz), 18 mürettebat (karma milliyet), şirket davranışsal güvenlik programı, ramak kala raporlama sistemi, araç kutusu konuşması şablonları, JSA formları, eğitim kayıt defteri, DPA ve HSEQ departmanı.',
                        'current_conditions'  => 'Açık deniz seferleri arası limanda, bir sonraki göreve 2 gün. Mürettebat morali karışık — deneyimli zabitler kayıtsızlıktan rahatsız, genç mürettebat çekingen. Şimdiye kadar yaralanma yok ama net ramak kala trendi.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан PSV DP Класс 2, Мексиканский залив. За 2 недели — 3 инцидента: (1) 2-тонный контейнер на кране качнулся без контроля (линемен не был проинформирован о ветре), (2) матрос зашёл в химсклад без СИЗ, (3) DPO квитировал аларм позиции без расследования — дрейф 5 м. Тулбокс-токи формальны — боцман читает скрипт, экипаж подписывает лист. JSA копируются без обновления. 3 члена экипажа боятся докладывать — "боцман кричит". За 3 месяца — только 2 near-miss (среднее по флоту — 12).',
                        'your_position'       => 'Капитан, обзор инцидентов и конфиденциальной обратной связи от офицера безопасности.',
                        'available_resources' => 'Офицер безопасности, боцман (опытный, авторитарный), 18 экипажа, программа поведенческой безопасности компании, система near-miss, шаблоны тулбокс-ток, JSA, связь с DPA/HSEQ.',
                        'current_conditions'  => 'В порту, до следующего рейса 2 дня. Моральный дух смешанный, ясный тренд near-miss.',
                    ],
                    'az' => [
                        'situation'           => 'Meksika körfəzində DP Sinif 2 PSV-nin kapitanısınız. 2 həftədə 3 near-miss: (1) kran əməliyyatında 2 tonluq konteyner nəzarətsiz yelləndi, (2) matros KFV-sız kimyəvi anbara girdi, (3) DPO DP alarmını araşdırmadan sıfırladı (5 m sürüşmə). Toolbox-toklar formal — losman skriptdən oxuyur, heyət imza atır. JSA-lar köhnə əməliyyatlardan kopyalanır. 3 heyət üzvü "losman qışqırır" deyə məruzə etməkdən qorxur. 3 ayda cəmi 2 near-miss (flot ortalaması 12).',
                        'your_position'       => 'Kapitan, hadisə hesabatlarını və Təhlükəsizlik Zabitinin gizli rəyini nəzərdən keçirirsiniz.',
                        'available_resources' => 'Təhlükəsizlik Zabiti, losman (təcrübəli, avtoritar), 18 heyət, şirkətin davranış təhlükəsizliyi proqramı, near-miss sistemi, toolbox şablonları, JSA, DPA/HSEQ əlaqəsi.',
                        'current_conditions'  => 'Limanda, növbəti səfərə 2 gün. Əhval-ruhiyyə qarışıq, aydın near-miss trendi.',
                    ],
                ],
                'decision_prompt'      => 'How do you address this safety culture breakdown? Describe your plan for: (1) the immediate near-miss incidents and corrective actions, (2) improving the quality and effectiveness of toolbox talks and JSAs, (3) addressing the Bosun\'s leadership style and the crew intimidation issue, (4) building a genuine near-miss reporting culture, and (5) your communication with the company about the systemic issues.',
                'decision_prompt_i18n' => [
                    'tr' => 'Bu güvenlik kültürü çöküşünü nasıl ele alıyorsunuz? Planınızı açıklayın: (1) ramak kala olayları ve düzeltici eylemler, (2) araç kutusu konuşmaları ve JSA\'ların kalitesini artırma, (3) Lostromo\'nun liderlik tarzı ve mürettebat yıldırma sorunu, (4) gerçek ramak kala raporlama kültürü oluşturma, (5) sistemik sorunlar hakkında şirketle iletişim.',
                    'ru' => 'Как исправите ситуацию с культурой безопасности? Опишите: (1) корректирующие действия по инцидентам, (2) повышение качества тулбокс-ток и JSA, (3) стиль руководства боцмана и проблема запугивания, (4) создание культуры near-miss отчётности, (5) коммуникация с компанией.',
                    'az' => 'Bu təhlükəsizlik mədəniyyəti pozuntusunu necə həll edirsiniz? Planınızı təsvir edin: (1) near-miss hadisələri və düzəldici tədbirlər, (2) toolbox-tok və JSA keyfiyyətinin artırılması, (3) losmanın liderlik tərzi və heyət qorxutma problemi, (4) near-miss hesabat mədəniyyəti, (5) şirkətlə ünsiyyət.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'near_miss_investigation_and_corrective_action',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Dismisses near-misses as minor events. No investigation. No corrective actions. Blames individual crew members.',
                            '2' => 'Investigates the incidents superficially. Issues warnings to individuals. Does not identify systemic root causes.',
                            '3' => 'Investigates each near-miss: identifies root causes (poor briefing, PPE culture, alarm response), implements corrective actions (revised briefing, PPE enforcement, DPO alarm response protocol), documents findings.',
                            '4' => 'Thorough investigation with root cause analysis: identifies that all three incidents share a common root cause — inadequate pre-task safety briefings and a culture of normalised deviance. Implements systemic corrective actions addressing the root cause, not just individual incidents. Documents and shares lessons learned.',
                            '5' => 'Comprehensive safety response: formal investigation of all three incidents using structured methodology (e.g., TapRooT, Reason\'s Swiss Cheese), identifies multiple contributing factors (toolbox quality, JSA relevance, supervisory style, crew confidence), develops an integrated corrective action plan, assigns responsibilities and timelines, establishes leading indicators to track improvement, reports to company HSEQ with systemic analysis and recommendations, uses the incidents as learning opportunities in crew meetings.',
                        ],
                    ],
                    [
                        'axis'   => 'safety_culture_and_leadership',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Ignores the Bosun intimidation issue. Does not address the reporting fear. No action on safety culture. Focuses only on procedures not behaviours.',
                            '2' => 'Acknowledges the Bosun issue but only tells him to "be nicer." No structured approach to changing the safety culture.',
                            '3' => 'Addresses the Bosun directly about his leadership style and its impact on safety. Implements an open-door policy. Encourages near-miss reporting. Conducts a crew safety meeting.',
                            '4' => 'Structured culture change: has a private, constructive conversation with the Bosun about the impact of his behaviour on safety reporting (not punitive but developmental), implements anonymous near-miss reporting option, personally leads a toolbox talk to model the expected standard, establishes regular safety meetings where crew can raise concerns, creates safety incentives for reporting.',
                            '5' => 'Comprehensive leadership intervention: addresses Bosun through coaching (recognises his experience while explaining the damage of intimidation to safety), implements multiple reporting channels (anonymous box, Safety Officer, direct to Master), personally conducts visible safety leadership (leads toolbox talks, participates in JSAs, walks the deck), establishes "just culture" principles where reporting is valued not punished, creates a mentoring programme pairing experienced and junior crew, involves the company HSEQ for shore-based support (possible supervisory training for Bosun), measures culture change through near-miss reporting rate as a leading indicator.',
                        ],
                    ],
                    [
                        'axis'   => 'toolbox_talk_and_jsa_quality_improvement',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No action on toolbox talk quality. JSAs continue to be copied. Status quo maintained.',
                            '2' => 'Issues instruction to "improve toolbox talks" without guidance on how. JSA template updated but no training.',
                            '3' => 'Implements specific improvements: toolbox talks must include discussion of current conditions, crew must contribute (not just sign), JSAs must be reviewed and updated for each operation, C/O or Master to attend random toolbox talks for quality verification.',
                            '4' => 'Systematic quality improvement: redesigns toolbox talk format to be interactive (questions, scenario discussion), requires JSAs to be completed fresh for each operation referencing current weather/SIMOPS/personnel, establishes toolbox talk quality audit by Safety Officer, provides coaching to Bosun on facilitation skills.',
                            '5' => 'Expert safety management: transforms toolbox talks from compliance exercise to genuine risk identification tool — uses structured format with open questions, recent incident discussion, crew risk identification round, and commitment to specific safety actions. JSAs redesigned with mandatory fields for current conditions, SIMOPS cross-references, and crew signatures confirming understanding (not just attendance). Implements a feedback loop where near-miss lessons feed into next toolbox talk. Safety Officer conducts formal toolbox talk quality assessments. Reports culture change initiative to company as a best practice case.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No investigation of the three near-miss incidents',                    'severity' => 'critical'],
                    ['flag' => 'No action on crew intimidation issue affecting safety reporting',       'severity' => 'critical'],
                    ['flag' => 'No improvement of toolbox talk and JSA quality',                       'severity' => 'major'],
                    ['flag' => 'No reporting of systemic safety culture issues to company',             'severity' => 'major'],
                    ['flag' => 'No establishment of genuine near-miss reporting culture',               'severity' => 'major'],
                    ['flag' => 'No action on DPO alarm response failure',                              'severity' => 'critical'],
                ],
                'expected_references_json' => [
                    'ISM Code Section 9 — Non-conformities, incidents, and corrective actions',
                    'IMCA SEL 040 — Guidelines on safety culture and behavioural safety',
                    'Company SMS — Near-miss reporting and behavioural safety programme',
                    'STCW Code — Competence and training requirements',
                    'MLC 2006 — Occupational safety and health protection',
                    'IMCA M 182 — DP station keeping incident analysis',
                    'IOGP Report 510 — Life-Saving Rules for the energy industry',
                ],
                'red_flags_json' => [
                    'Dismissing near-miss incidents without investigation',
                    'Tolerating a supervisory style that suppresses safety reporting',
                    'Allowing toolbox talks to remain a tick-box exercise',
                    'Permitting JSAs to be copied from previous operations without updating',
                    'No action on low near-miss reporting rate compared to fleet average',
                    'Not addressing DPO alarm acknowledgement without investigation',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 6 — AUTO_DEP — Auto DP reliance + mode awareness + manual fallback
            // ══════════════════════════════════════════════════════════════
            'OFFSHORE_S06_AUTO_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a DP Class 2 AHTS vessel maintaining position 80m off the stern of a jack-up drilling rig in the South China Sea, providing emergency stand-by during drilling operations. The vessel has been on DP for 18 hours. At 0230, a sudden thunderstorm cell passes through the area. Wind shifts rapidly from SE 15 knots to NW 35 knots with gusts to 45 knots in under 3 minutes. The DP system, configured in "auto position" mode with weather-optimal heading enabled, attempts to weathervane but the heading change rate exceeds the thruster response capability for the sudden wind shift. The DP generates a "capability warning" followed by an "excursion warning" — the vessel moves 15 metres toward the rig before the DP stabilises on the new heading. During this event, the DPO freezes and does not intervene manually. The 2/O (OOW) was monitoring the weather radar but did not call the DPO\'s attention to the approaching squall cell. Post-event, you find the DP is now stable but one stern thruster has tripped on thermal overload from the sudden demand. You are now at 80% of DP capability with the wind still at 35 knots sustained. The rig is continuing drilling with live well.',
                        'your_position'       => 'Bridge, awakened by alarms. DPO at desk, 2/O on watch.',
                        'available_resources' => 'DP Class 2 (one stern thruster tripped — degraded), 3 main engines, 2 bow thrusters, 1 stern thruster remaining, all reference systems operational, weather radar, VHF to rig, FMEA for degraded thruster configuration, manual joystick control available.',
                        'current_conditions'  => 'NW 35 knots sustained (gusts 45), Hs 2.5m building, thunderstorm cells on radar, night, 80m from rig (live well), DP stabilised but degraded (80% capability), one stern thruster offline.',
                    ],
                    'tr' => [
                        'situation'           => 'Güney Çin Denizi\'nde bir jack-up sondaj kulesinin kıç tarafından 80m mesafede pozisyon tutan DP Sınıf 2 AHTS gemisinin kaptanısınız, sondaj sırasında acil bekleme desteği sağlıyorsunuz. Gemi 18 saattir DP\'de. 0230\'da ani bir fırtına hücresi bölgeden geçiyor. Rüzgar 3 dakikadan kısa sürede GD 15 knottan KB 35 knota (hamle 45) dönüyor. "Otomatik pozisyon" modundaki DP sistemi rüzgar yönüne dönmeye çalışıyor ama yön değişim hızı iticilerin kapasitesini aşıyor. DP "kapasite uyarısı" ve ardından "sapma uyarısı" veriyor — gemi stabilize olmadan önce kuleye doğru 15 metre hareket ediyor. Bu olay sırasında DPO donakalıyor ve manuel müdahale etmiyor. 2. Zabit hava radarını izliyordu ama yaklaşan fırtına hücresine DPO\'nun dikkatini çekmedi. Olay sonrası, bir kıç iticinin ani talepten termal aşırı yük nedeniyle devre dışı kaldığını buluyorsunuz. Rüzgar hala 35 knot sürerken DP kapasitesinin %80\'indesiniz. Kule canlı kuyuyla sondaja devam ediyor.',
                        'your_position'       => 'Köprüüstü, alarmlarla uyandınız. DPO masasında, 2. Zabit vardiyada.',
                        'available_resources' => 'DP Sınıf 2 (bir kıç itici devre dışı — düşük kapasite), 3 ana makine, 2 baş itici, 1 kalan kıç itici, tüm referans sistemleri çalışıyor, hava radarı, kuleye VHF, düşük itici konfigürasyonu FMEA, manuel joystick.',
                        'current_conditions'  => 'KB 35 knot (hamle 45), Hs 2,5m artıyor, radar fırtına hücreleri, gece, kuleden 80m (canlı kuyu), DP stabil ama düşük kapasite (%80), bir kıç itici çalışmıyor.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан AHTS DP Класс 2, 80 м от кормы джекапа в Южно-Китайском море. Судно на DP 18 часов. В 02:30 — внезапная гроза: ветер за 3 минуты развернулся с ЮВ 15 на СЗ 35 (порывы 45). DP пытается развернуться по ветру — трастеры не успевают, «предупреждение по способности», затем «предупреждение по отклонению» — судно сместилось на 15 м к буровой. DPO не вмешался вручную. 2-й помощник видел ячейку на радаре, но не предупредил. Один кормовой подруливающий отключился (тепловая перегрузка). DP стабилен, но на 80% мощности. Ветер 35 узлов, буровая с активной скважиной.',
                        'your_position'       => 'Мостик, разбужен алармами. DPO за пультом, 2-й помощник на вахте.',
                        'available_resources' => 'DP Класс 2 (деградация: 1 кормовой подруливающий выключен), 3 ГД, 2 носовых, 1 кормовой подруливающий, референсы в норме, метеорадар, УКВ, FMEA, ручной джойстик.',
                        'current_conditions'  => 'СЗ 35 (порывы 45), Hs 2,5 м растёт, грозовые ячейки, ночь, 80 м от буровой (скважина), DP 80%.',
                    ],
                    'az' => [
                        'situation'           => 'Cənubi Çin dənizində cekap platformasından 80 m-də olan DP Sinif 2 AHTS-nin kapitanısınız. 18 saatdır DP-dəsiniz. 02:30-da qəfil tufan: külək 3 dəqiqədə CŞ 15-dən ŞQ 35-ə (şıdırğı 45) dönür. DP-nin avtomatik dönmə cəhdi uğursuz, 15 m platformaya doğru sürüşmə. DPO əl ilə müdaxilə etmədi. Bir arxa itələyici sıradan çıxıb. DP 80% qabiliyyətdə. Külək 35 knot, aktiv quyu.',
                        'your_position'       => 'Körpüüstü, alarmla oyandınız. DPO pultda, 2-ci stürman növbədə.',
                        'available_resources' => 'DP Sinif 2 (1 arxa itələyici yox), 3 əsas mühərrik, 2 baş + 1 arxa itələyici, referanslar normal, meteoradar, VHF, FMEA, əl joystiki.',
                        'current_conditions'  => 'ŞQ 35 (şıdırğı 45), Hs 2,5 m artır, tufan hüceyrələri, gecə, platformadan 80 m (aktiv quyu), DP 80%.',
                    ],
                ],
                'decision_prompt'      => 'Describe your response. Address: (1) your assessment of current DP capability with one thruster offline and sustained 35-knot winds, (2) your decision on whether to remain on location or drive off, (3) how you address the DPO freeze/failure to intervene manually, (4) the OOW failure to warn of the approaching squall, and (5) what changes you implement for automation mode awareness and manual backup readiness.',
                'decision_prompt_i18n' => [
                    'tr' => 'Müdahalenizi açıklayın: (1) bir itici çalışmazken 35 knot rüzgarda DP kapasitesi değerlendirmesi, (2) konumda kalma veya uzaklaşma kararı, (3) DPO\'nun donması/manuel müdahale hatası, (4) VZ\'nin fırtına uyarısı hatası, (5) otomasyon farkındalığı ve manuel yedek hazırlığı değişiklikleri.',
                    'ru' => 'Опишите реакцию: (1) оценка DP с одним выключенным подруливающим при 35 узлах, (2) решение — оставаться или отходить, (3) DPO — заморозка и невмешательство, (4) ВП — не предупредил о шквале, (5) изменения в автоматизации и готовности ручного управления.',
                    'az' => 'Cavabınızı təsvir edin: (1) 1 itələyici olmadan 35 knot küləkdə DP qabiliyyəti, (2) yerində qalmaq/uzaqlaşmaq qərarı, (3) DPO-nun donması, (4) ВП-nin tufan xəbərdarlığı uğursuzluğu, (5) avtomatlaşdırma fərqindəliyi və əl ehtiyatı dəyişiklikləri.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'dp_capability_assessment_and_decision',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Does not reassess DP capability after thruster loss. Remains on location without considering degraded status. No reference to FMEA.',
                            '2' => 'Notes the thruster loss but does not calculate the impact on DP capability envelope. Vague assessment of remaining margins.',
                            '3' => 'Reviews DP capability with degraded thruster configuration against current 35-knot wind. References FMEA for single thruster failure scenario. Makes informed decision on whether remaining capability is adequate.',
                            '4' => 'Systematic assessment: reviews FMEA for the specific degraded configuration (one stern thruster offline), checks DP capability plot for 35-knot sustained with degraded thrusters, considers gust factor (45 knots), assesses whether remaining 80% capability provides adequate safety margin, considers forecast (more thunderstorm cells on radar), makes a clear decision to remain or drive off with documented justification.',
                            '5' => 'Expert DP assessment: comprehensive review of degraded capability against current and forecast conditions, FMEA analysis for the specific failure (stern thruster thermal trip — will it recover? ETO assessment), DP capability plot review for worst-case gusts (45 knots) with degraded configuration, trend analysis (more cells approaching = sustained exposure), considers that 80% capability in 35-knot sustained with 45-knot gusts near a live well installation leaves inadequate safety margin, decides on controlled drive-off to safe distance, plans to return only when thruster restored AND weather stabilised, documents full analysis.',
                        ],
                    ],
                    [
                        'axis'   => 'automation_awareness_and_manual_backup',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No recognition of the automation complacency issue. Does not address the DPO freeze or the gap in manual override readiness.',
                            '2' => 'Acknowledges the DPO froze but attributes it only to individual failure. No systemic changes to automation reliance or manual backup.',
                            '3' => 'Addresses the DPO performance: debriefs the incident, establishes that manual override must be immediate when DP excursion occurs, requires manual joystick familiarisation training.',
                            '4' => 'Comprehensive automation review: identifies that 18 hours of "auto DP" created complacency, DPO lost manual skills readiness, OOW was not monitoring DP as a secondary check. Implements: regular manual control exercises during DP watches, mandatory weather radar monitoring by OOW with defined squall warning triggers, DPO manual override drill protocol.',
                            '5' => 'Expert automation management: identifies the incident as a systemic automation dependency failure — the DP system was trusted without monitoring, the DPO had no practiced manual response for rapid environmental change, the bridge team did not function as an integrated unit. Implements: mandatory periodic manual control exercises (e.g., 15 minutes per watch), structured DP monitoring protocol (position trend, capability margin, weather radar), defined squall response procedure (OOW warns DPO at defined radar range, DPO pre-positions heading and reduces auto-heading change rate), manual override drills at watch handover, bridge team exercise for rapid environmental change scenarios, reports to company for fleet-wide learning.',
                        ],
                    ],
                    [
                        'axis'   => 'bridge_team_performance_review',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No debrief of the incident. Does not address individual or team performance. Continues as before.',
                            '2' => 'Brief discussion with DPO and OOW but no structured debrief. Focuses on blame rather than learning.',
                            '3' => 'Structured debrief with DPO and OOW: discusses the squall detection gap, the DPO freeze, and the communication failure. Establishes corrective actions.',
                            '4' => 'Comprehensive team review: formal debrief of the entire event chain (squall approach → no warning → DP excursion → DPO freeze → thruster trip), assigns clear responsibilities (OOW: weather monitoring + communication, DPO: DP monitoring + manual readiness), reviews bridge team communication protocol.',
                            '5' => 'Expert team development: formal incident debrief using structured methodology, identifies each failure point in the event chain, develops corrective actions for each, uses the incident for a constructive bridge team training session (not punitive), establishes improved communication protocol between OOW and DPO, implements DP-specific BRM training, reports incident to company per IMCA M 182 for industry learning, uses the event to reinforce that DP automation does not replace seamanship.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No DP capability reassessment after thruster loss in sustained high winds', 'severity' => 'critical'],
                    ['flag' => 'No FMEA reference for degraded thruster configuration',                   'severity' => 'major'],
                    ['flag' => 'No action on DPO failure to manually intervene during excursion',          'severity' => 'critical'],
                    ['flag' => 'No address of OOW failure to warn of approaching squall',                  'severity' => 'major'],
                    ['flag' => 'No manual backup readiness improvements implemented',                      'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'IMCA M 103 — DP vessel design and operation guidelines',
                    'IMCA M 182 — DP station keeping incidents',
                    'IMO MSC.1/Circ.1580 — Guidelines for vessels with DP systems',
                    'Company SMS — DP operations and emergency procedures',
                    'Vessel FMEA — Thruster failure consequence analysis',
                    'STCW Code A-VIII/2 — Watchkeeping principles',
                ],
                'red_flags_json' => [
                    'Remaining on station at 80% DP capability in 35-knot gusting 45-knot winds near live well',
                    'Not reassessing DP capability after thruster loss',
                    'Ignoring DPO automation complacency and failure to intervene manually',
                    'No manual backup readiness or practice during extended DP operations',
                    'Not using weather radar to anticipate rapid environmental changes',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 7 — CRISIS_RSP — MOB / collision / loss of position near installation
            // ══════════════════════════════════════════════════════════════
            'OFFSHORE_S07_CRIS_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a DP Class 2 PSV completing cargo operations alongside a fixed production platform in the North Sea. During the final crane lift — a 4-tonne container of well completion chemicals — the crane wire parts under load. The container falls onto the vessel\'s aft deck, striking the pipe rack and bouncing overboard into the sea between the vessel and the platform jacket structure. The impact catapults one deck crew member (AB) overboard into the water approximately 10 metres from the platform jacket legs. A second crew member on deck is struck by debris and is lying injured on the aft deck with suspected spinal injuries. The falling container has damaged the vessel\'s port side aft deck rail and a hydraulic line for the stern crane, which is now leaking hydraulic fluid onto the deck and into the sea. The AB in the water is wearing a lifejacket and is conscious but appears injured — he is caught in the current between the vessel and the platform and is being carried toward the jacket structure. Your vessel is still on DP alongside the platform.',
                        'your_position'       => 'Bridge, heard the impact and crew shouting. DPO at DP desk, C/O was on deck near the incident.',
                        'available_resources' => 'DP Class 2 (fully operational), rescue boat with 2 trained crew, MOB equipment (lifebuoys with lights and smoke, rescue sling), first aid equipment and stretcher with spinal immobilisation kit, VHF to platform and standby vessel, platform medic available, Bristow/CHC helicopter (30 minutes from shore base), coastguard MRCC, 15 crew on board (C/O may be injured — status unknown).',
                        'current_conditions'  => 'Daylight, wind W 20 knots, Hs 1.5m, current 0.8 knots setting toward platform jacket, good visibility, man in water 10m from jacket legs, injured crew on deck, hydraulic fluid leaking.',
                    ],
                    'tr' => [
                        'situation'           => 'Kuzey Denizi\'nde sabit bir üretim platformu yanında yük operasyonlarını tamamlayan DP Sınıf 2 PSV\'nin kaptanısınız. Son vinç kaldırma — 4 tonluk kuyu tamamlama kimyasalları konteynerı — sırasında vinç halatı yük altında kopuyor. Konteyner geminin kıç güvertesine düşüyor, boru sehpasına çarpıp denize, gemi ile platform ceket yapısı arasına düşüyor. Çarpma bir güverte erisini (AB) platformun ceket bacaklarından yaklaşık 10 metre mesafede suya fırlatıyor. Güvertedeki ikinci mürettebat enkazla yaralanmış, olası omurga yaralanmasıyla kıç güvertede yatıyor. Düşen konteyner iskele kıç güverte korkuluğuna ve kıç vinç hidrolik hattına hasar vermiş — hidrolik sıvısı güverteye ve denize sızıyor. Sudaki AB can yeleği giymiş ve bilinçli ama yaralı görünüyor — gemi ile platform arasındaki akıntıda sürükleniyor ve ceket yapısına doğru taşınıyor. Geminiz hâlâ platform yanında DP\'de.',
                        'your_position'       => 'Köprüüstü, çarpma sesini ve mürettebat bağrışmalarını duydunuz. DPO DP masasında, Birinci Zabit güvertede olayın yakınındaydı.',
                        'available_resources' => 'DP Sınıf 2 (tam operasyonel), eğitimli 2 kişilik kurtarma botu, MOB ekipmanı, ilk yardım ve omurga immobilizasyon kitli sedye, platforma ve standby gemisine VHF, platform sağlıkçısı, 30 dakikada helikopter, kıyı güvenliği MRCC, gemide 15 mürettebat.',
                        'current_conditions'  => 'Gündüz, rüzgar B 20 knot, Hs 1,5m, akıntı 0,8 knot ceket yapısına doğru, iyi görüş, suda adam ceket bacaklarına 10m, güvertede yaralı, hidrolik sızıntı.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан PSV DP Класс 2, Северное море, грузовые операции у стационарной платформы. При последнем подъёме (4 тонны) рвётся крановый трос. Контейнер падает на палубу, отскакивает в воду между судном и опорами платформы. Один матрос оказался в воде (10 м от опор), второй ранен на палубе (подозрение на повреждение позвоночника). Повреждена гидролиния кормового крана — утечка на палубу и в воду. Человек в воде в спасжилете, в сознании, но травмирован — течение несёт к конструкции платформы.',
                        'your_position'       => 'Мостик. DPO за пультом, старпом был на палубе (статус неизвестен).',
                        'available_resources' => 'DP Класс 2 (исправен), спасательная шлюпка, MOB-оборудование, носилки с иммобилизацией, УКВ к платформе и standby-судну, медик на платформе, вертолёт (30 мин), МСКЦ, 15 экипажа.',
                        'current_conditions'  => 'День, ветер З 20, Hs 1,5 м, течение 0,8 узла к опорам, хорошая видимость, человек в воде 10 м от опор.',
                    ],
                    'az' => [
                        'situation'           => 'Şimali dənizdə stasionar platformanın yanında yük əməliyyatlarını tamamlayan DP Sinif 2 PSV-nin kapitanısınız. Son qaldırışda (4 ton) kran trosu qırılır. Konteyner göyərtəyə düşür, suya sıçrayır (gəmi ilə platforma konstruksiyası arasına). Bir matros suya düşür (platformanın ayaqlarından 10 m), ikincisi göyərtədə yaralıdır (onurğa şübhəsi). Kran hidravlik xətti zədələnib — sızıntı. Suda olan adam xilasetmə jiletindədir, şüurundan, amma yaralıdır — axın onu platformanın konstruksiyasına doğru aparır.',
                        'your_position'       => 'Körpüüstü. DPO pultda, birinci stürman göyərtədə idi (vəziyyəti bilinmir).',
                        'available_resources' => 'DP Sinif 2 (tam işlək), xilasetmə qayığı, MOB avadanlığı, xərək (onurğa immobilizasiyası), VHF, platforma mediki, helikopter (30 dəq), MRCC, 15 heyət.',
                        'current_conditions'  => 'Gündüz, Q külək 20, Hs 1,5 m, axın 0,8 knot platformaya doğru, yaxşı görmə, suda adam ayaqlardan 10 m.',
                    ],
                ],
                'decision_prompt'      => 'Describe your complete emergency response. Address: (1) prioritisation of the two casualties (MOB vs. injured on deck), (2) MOB recovery considering the current carrying the person toward the platform jacket, (3) management of the injured crew member with suspected spinal injury, (4) DP management during the emergency — do you drive off or maintain position, (5) external communications and medevac coordination, and (6) pollution response for the hydraulic fluid leak.',
                'decision_prompt_i18n' => [
                    'tr' => 'Tam acil müdahalenizi açıklayın: (1) iki yaralının önceliklendirilmesi (MOB vs güvertedeki), (2) akıntıda ceket yapısına sürüklenen kişinin MOB kurtarması, (3) omurga yaralanması şüpheli mürettebatın yönetimi, (4) acil durum sırasında DP yönetimi, (5) dış iletişim ve medevac, (6) hidrolik sızıntıya kirlilik müdahalesi.',
                    'ru' => 'Опишите полный аварийный план: (1) приоритизация пострадавших (MOB / на палубе), (2) спасение из воды с учётом течения к конструкции, (3) помощь раненому на палубе (позвоночник), (4) DP — оставаться или отходить, (5) внешние коммуникации и медэвакуация, (6) ликвидация разлива гидравлической жидкости.',
                    'az' => 'Tam fövqəladə cavabınızı təsvir edin: (1) iki zərərçəkənin prioritetləşdirilməsi, (2) axında olan MOB xilasetmə, (3) onurğa xəsarətli heyətin idarəsi, (4) fövqəladə zamanı DP idarəsi, (5) xarici rabitə və medevac, (6) hidravlik sızıntısına çirklənmə müdaxiləsi.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'mob_response_and_casualty_prioritisation',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'No structured emergency response. Does not immediately raise MOB alarm. Does not prioritise between casualties. Delays rescue boat launch.',
                            '2' => 'Raises MOB alarm but response is disorganised. Attempts to deal with both casualties simultaneously without clear prioritisation. Rescue boat launch delayed.',
                            '3' => 'Raises MOB alarm, deploys lifebuoy to man in water, prioritises MOB as immediate life threat (current carrying toward jacket). Launches rescue boat. Assigns separate team for injured crew on deck. Coordinates with platform for assistance.',
                            '4' => 'Structured dual-casualty response: immediate MOB alarm, lifebuoy deployed, rescue boat launched with experienced crew, MOB prioritised as imminent life threat (current toward steel structure), dedicated team assigned to deck casualty with spinal immobilisation protocol, C/O status checked (may be a third casualty), platform notified for medic and standby vessel assistance, clear command structure established.',
                            '5' => 'Expert emergency management: immediate MOB protocol (alarm, lifebuoy, EPIRB/MOB marker, rescue boat), recognises that current carrying person toward jacket legs creates urgency — rescue boat directed to intercept before person reaches steel structure, considers requesting platform to deploy scramble net or rope from jacket if accessible, parallel response for deck casualty (spinal protocol, no movement until assessed), C/O status determined, command structure: Master coordinates overall, 2/O manages deck casualty, DPO maintains DP, rescue boat coxswain executes MOB recovery, platform standby vessel activated as backup for MOB if needed, all actions logged with times.',
                        ],
                    ],
                    [
                        'axis'   => 'dp_management_during_emergency',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Ignores DP during emergency. Does not consider vessel position relative to MOB or platform. No DP management plan.',
                            '2' => 'Leaves DP on auto without reassessing. Does not consider whether vessel position helps or hinders MOB recovery.',
                            '3' => 'Maintains DP alongside but considers whether vessel position can aid MOB recovery. Ensures DP is stable before committing to rescue operations from the vessel.',
                            '4' => 'Active DP management: maintains position to keep vessel between MOB and platform (providing lee), considers whether controlled repositioning could aid rescue boat, ensures DPO monitors DP throughout emergency (not distracted by deck events), plans for potential drive-off if vessel becomes a hazard to the MOB.',
                            '5' => 'Expert DP decision: assesses whether maintaining DP alongside the platform or driving off is safer for the MOB recovery — considers that the vessel on DP provides a reference point and potential lee for rescue operations, but could also trap the MOB between vessel and jacket. Makes a dynamic decision based on MOB position relative to vessel and platform, ensures DPO is focused solely on DP (not diverted to other tasks), considers using DP to slowly reposition the vessel to create clear water for rescue boat access, plans for immediate drive-off if vessel position becomes dangerous to either casualty.',
                        ],
                    ],
                    [
                        'axis'   => 'external_coordination_and_medevac',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No external communications. Does not contact platform, standby vessel, or shore. No medevac request.',
                            '2' => 'Contacts platform but communications are disorganised. Does not request medevac early enough. Does not coordinate with standby vessel.',
                            '3' => 'Contacts platform (medic, crane stop, standby vessel activation), requests helicopter medevac for spinal injury, informs MRCC, coordinates with standby vessel for additional MOB support.',
                            '4' => 'Comprehensive coordination: platform notified immediately (all crane operations ceased, medic dispatched to helideck, standby vessel redirected to assist), helicopter medevac requested with casualty details (suspected spinal, MOB with injuries), MRCC informed per GMDSS, company notified, pollution response coordinated with platform for hydraulic spill.',
                            '5' => 'Expert multi-agency coordination: immediate platform notification triggering their emergency response (crane stop, deck cleared, medic to helideck, standby vessel activated), helicopter medevac requested with precise casualty information for medical team preparation, MRCC formal notification, company DPA and operations informed, standby vessel positioned for backup MOB recovery, platform safety officer coordinated for joint investigation, pollution response (hydraulic fluid — SOPEP activation, absorbent deployed, platform notified for joint response), all communications logged with timestamps for subsequent investigation.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No immediate MOB alarm and rescue boat deployment',                     'severity' => 'critical'],
                    ['flag' => 'No prioritisation of MOB given current toward platform structure',      'severity' => 'critical'],
                    ['flag' => 'No spinal immobilisation protocol for injured crew on deck',            'severity' => 'critical'],
                    ['flag' => 'No DP management plan during the emergency',                            'severity' => 'major'],
                    ['flag' => 'No medevac request for seriously injured casualties',                    'severity' => 'critical'],
                    ['flag' => 'No pollution response for hydraulic fluid leak',                         'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter III — Life-saving appliances (MOB procedures)',
                    'ISM Code Section 8 — Emergency preparedness',
                    'IMCA SEL 019 — Guidelines for lifting operations',
                    'Company SMS — MOB procedures and emergency response plan',
                    'MARPOL — Oil pollution prevention (hydraulic fluid)',
                    'GMDSS procedures — Urgency communications',
                    'IOGP Report 434 — Medical emergency response',
                ],
                'red_flags_json' => [
                    'Delaying MOB alarm or rescue boat launch',
                    'Not prioritising MOB being carried toward platform structure by current',
                    'Moving injured crew member with suspected spinal injury without immobilisation',
                    'Abandoning DP control during emergency — vessel drifting uncontrolled',
                    'No helicopter medevac request for serious casualties',
                    'No pollution response for hydraulic fluid in the sea',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 8 — TRADEOFF — Weather window vs contract penalties vs DP capability
            // ══════════════════════════════════════════════════════════════
            'OFFSHORE_S08_TRADE_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a DP Class 2 AHTS vessel contracted for a 5-day anchor handling campaign for a semi-submersible drilling rig relocation in the Norwegian sector. You are on day 4 with 3 of 8 anchors remaining to be set. A weather window analysis from the meteorological service shows a 36-hour favourable period starting now, followed by a 4-day storm (NW 45-55 knots, Hs 6-8m). The next favourable window after the storm is uncertain — could be 5-7 days. The charter party includes a penalty clause: if the campaign exceeds 5 days due to vessel-related delays, a $150,000/day penalty applies. The drilling company project manager is demanding you work through the current weather window continuously — including night operations — to set the remaining 3 anchors before the storm arrives. Your DP capability plot for anchor handling shows a maximum operating limit of 30 knots for this type of operation. Current conditions are 25 knots with forecast increasing to 30 knots in 12 hours. Your crew has been working 16-hour days for the past 3 days due to the compressed schedule. The C/O has privately told you he is concerned about crew fatigue affecting anchor handling safety. The tow wire on the starboard drum shows wear at the fairlead contact point — the C/O estimates 70% of original breaking strength.',
                        'your_position'       => 'Bridge, reviewing the weather forecast, DP capability data, and charter party terms with the C/O before responding to the project manager.',
                        'available_resources' => 'DP Class 2 with full anchor handling equipment (two drums, shark jaws, work wire, pennant wires), DP capability plot for AH operations, weather routing/forecast service, charter party documentation, company operations manager (by phone), crew rest hour records, tow wire inspection records, rig\'s anchor pattern plan, marine warranty surveyor (MWS) approval required for AH operations.',
                        'current_conditions'  => 'Wind NW 25 knots (forecast 30 in 12 hours, then 45+ in 36 hours), Hs 2.5m building, 3 anchors remaining, crew fatigued (16-hr days × 3 days), tow wire at 70% strength, 36-hour weather window, night approaching, charter penalty clock at day 4 of 5.',
                    ],
                    'tr' => [
                        'situation'           => 'Norveç sektöründe yarı batık sondaj kulesinin yer değiştirmesi için 5 günlük çapa operasyonu sözleşmeli DP Sınıf 2 AHTS gemisinin kaptanısınız. 4. gündesiniz, 8 çapadan 3\'ü yerleştirilmeyi bekliyor. Meteoroloji servisi 36 saatlik uygun pencere ve ardından 4 günlük fırtına (KB 45-55 knot, Hs 6-8m) gösteriyor. Çarter parti ceza maddesi: kampanya 5 günü gemi kaynaklı gecikmelerle aşarsa günlük 150.000$ ceza. Sondaj şirketi proje müdürü fırtına gelmeden kalan 3 çapayı yetiştirmek için gece dahil sürekli çalışmanızı talep ediyor. Çapa operasyonu DP kapasite çizelgeniz 30 knot azami limit gösteriyor. Mevcut 25 knot, 12 saatte 30\'a yükselecek. Mürettebat son 3 gündür 16 saatlik vardiya çalışıyor. Birinci Zabit mürettebat yorgunluğunun çapa güvenliğini etkilemesinden endişeli. Sancak tamburdaki çekme halatı sürtünme noktasında aşınma gösteriyor — orijinal kopma mukavemetinin %70\'i.',
                        'your_position'       => 'Köprüüstü, proje müdürüne yanıt vermeden önce hava tahmini, DP kapasitesi ve çarter parti şartlarını Birinci Zabit ile inceliyorsunuz.',
                        'available_resources' => 'Tam çapa donanımlı DP Sınıf 2 (iki tambur, köpekbalığı çeneleri, çalışma halatı), AH operasyonları DP kapasite çizelgesi, hava tahmini, çarter parti belgeleri, şirket operasyon müdürü (telefon), mürettebat dinlenme kayıtları, halat denetim kayıtları, kulenin çapa düzeni planı, MWS onayı gerekli.',
                        'current_conditions'  => 'Rüzgar KB 25 knot (12 saatte 30, 36 saatte 45+), Hs 2,5m artıyor, 3 çapa kalmış, mürettebat yorgun (3 gün × 16 saat), çekme halatı %70 mukavemet, 36 saat pencere, gece yaklaşıyor, ceza saati 5\'in 4. günü.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан AHTS DP Класс 2, контракт на 5-дневную якорную операцию по перестановке полупогружной в Норвежском секторе. 4-й день, осталось 3 из 8 якорей. Метеоокно: 36 часов хорошей погоды, затем 4-дневный шторм (45-55 узлов, Hs 6-8 м). Чартер-партия: штраф $150 000/день при задержке >5 дней. Менеджер проекта требует непрерывной работы (включая ночь) чтобы установить 3 якоря до шторма. DP-предел для AH: 30 узлов. Сейчас 25, через 12 ч — 30. Экипаж 3 дня по 16 часов. Старпом обеспокоен усталостью. Буксирный трос изношен — 70% прочности.',
                        'your_position'       => 'Мостик, анализ прогноза, DP-данных и чартер-партии со старпомом.',
                        'available_resources' => 'DP Класс 2 с AH-оборудованием, графики способности DP для AH, метеосервис, чартер-партия, оперативный менеджер компании (телефон), записи отдыха экипажа, акты осмотра троса, план якорей буровой, MWS.',
                        'current_conditions'  => 'СЗ 25 (через 12 ч — 30, через 36 ч — 45+), Hs 2,5 м, 3 якоря, усталость экипажа, трос 70%, 36-ч окно, приближается ночь, день 4 из 5.',
                    ],
                    'az' => [
                        'situation'           => 'Norveç sektorunda yarımbatıq platformanın köçürülməsi üçün 5 günlük lövbər əməliyyatı üzrə müqavilə bağlanmış DP Sinif 2 AHTS-nin kapitanısınız. 4-cü gün, 8 lövbərdən 3-ü qalıb. Proqnoz: 36 saat yaxşı hava, sonra 4 gün fırtına (45-55 knot, Hs 6-8 m). Çarter-partiya: gecikmə >5 gün olarsa $150.000/gün cərimə. Layihə meneceri fırtınadan əvvəl gecə daxil fasiləsiz iş tələb edir. AH üçün DP limiti 30 knot. İndi 25, 12 saatda 30. Heyət 3 gündür 16 saat işləyir. Birinci stürman yorğunluqdan narahatdır. Yedək trosu 70% möhkəmlik.',
                        'your_position'       => 'Körpüüstü, proqnoz, DP və çarter-partiya şərtlərini birinci stürmanla nəzərdən keçirirsiniz.',
                        'available_resources' => 'DP Sinif 2 + AH avadanlığı, DP qabiliyyət qrafiki, meteoroloji xidmət, çarter-partiya, şirkət müdiri (telefon), istirahət qeydləri, tros yoxlama aktları, lövbər planı, MWS.',
                        'current_conditions'  => 'ŞQ 25 (12 saatda 30, 36 saatda 45+), Hs 2,5 m, 3 lövbər, heyət yorğun, tros 70%, 36 saat pəncərə, gecə yaxınlaşır, 5 günün 4-cü günü.',
                    ],
                ],
                'decision_prompt'      => 'Present your operational decision and justification. Address: (1) whether you attempt to set the remaining 3 anchors in the current weather window, (2) how you manage the crew fatigue issue with night operations, (3) your assessment of the tow wire condition and whether it is safe to use, (4) your response to the project manager\'s demand and the charter party penalty, and (5) what you communicate to the MWS, your company, and the rig.',
                'decision_prompt_i18n' => [
                    'tr' => 'Operasyonel kararınızı ve gerekçesini sunun: (1) mevcut pencerede kalan 3 çapayı yerleştirmeye çalışıp çalışmayacağınız, (2) gece operasyonlarında mürettebat yorgunluğu yönetimi, (3) çekme halatı durumu değerlendirmesi, (4) proje müdürü talebi ve ceza maddesine yanıtınız, (5) MWS, şirket ve kuleye iletişiminiz.',
                    'ru' => 'Представьте решение: (1) пытаетесь ли установить 3 якоря в текущем окне, (2) управление усталостью при ночных операциях, (3) оценка состояния троса, (4) ответ менеджеру проекта и штрафная оговорка, (5) коммуникации с MWS, компанией и буровой.',
                    'az' => 'Əməliyyat qərarınızı və əsaslandırmanızı təqdim edin: (1) cari pəncərədə 3 lövbəri yerləşdirməyə cəhd edib-etməyəcəyiniz, (2) gecə əməliyyatlarında yorğunluq idarəsi, (3) tros vəziyyətinin qiymətləndirilməsi, (4) layihə menecerinin tələbi və cərimə bəndina cavab, (5) MWS, şirkət və platformaya ünsiyyət.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'weather_window_and_operational_planning',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Agrees to work continuously without assessing whether 36 hours is sufficient for 3 anchors given the conditions and constraints. No consideration of deteriorating conditions during the window.',
                            '2' => 'Attempts the 3 anchors but without detailed time and conditions analysis. Does not account for conditions approaching 30 knots during the later anchors.',
                            '3' => 'Analyses the time needed for 3 anchors (typically 6-10 hours each for AH operations) against the 36-hour window. Recognises that conditions will approach 30-knot limit during the operation. Plans to set anchors in priority order and reassess after each one.',
                            '4' => 'Detailed operational planning: calculates time for each anchor (considering current positions, wire runs, tensioning), maps the timeline against the weather forecast, identifies that anchor 3 may coincide with 30-knot conditions (DP limit), plans contingencies (stop after 2 if conditions deteriorate early, prioritise the most critical anchor positions), obtains MWS approval for the plan.',
                            '5' => 'Expert weather window management: comprehensive time/weather correlation for all 3 anchors, identifies optimal sequence (most difficult operations first while conditions are better), plans rest periods for crew between anchors, establishes clear go/no-go criteria for each anchor based on forecast conditions at that time, coordinates with rig and MWS for a phased plan (e.g., 2 anchors now, assess conditions, attempt 3rd only if within limits), prepares a "storm preparation" contingency plan if only 2 anchors can be set, communicates the full plan with all stakeholders.',
                        ],
                    ],
                    [
                        'axis'   => 'crew_fatigue_and_safety_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Ignores crew fatigue entirely. Plans continuous night operations without rest assessment. No awareness of STCW/MLC rest requirements.',
                            '2' => 'Acknowledges fatigue but plans to push through. "We\'ll rest after the storm." No formal fatigue assessment.',
                            '3' => 'Assesses crew rest records and identifies non-compliance risk. Plans rest periods between anchors. Considers whether night AH operations are safe with fatigued crew.',
                            '4' => 'Systematic fatigue management: reviews rest hours for all key personnel (deck crew, DPO, officers), identifies that continuous night operations would violate STCW/MLC and create unacceptable safety risk for high-risk AH operations, plans the operation to include mandatory rest periods, considers reduced crew readiness for night anchor handling (higher risk of error), adjusts the plan to prioritise safety over schedule.',
                            '5' => 'Expert fatigue and safety integration: formal fatigue risk assessment for the planned operations, identifies that 3 days of 16-hour work plus planned night AH creates a critical fatigue scenario for high-consequence operations, refuses night AH unless adequate rest can be provided, proposes a realistic schedule (daylight operations only, with rest between anchors), calculates that attempting to force fatigued night AH operations creates greater risk of incident (wire handling accident, DP excursion) than accepting a schedule delay, documents the fatigue assessment and operational decision for MWS and charter party records.',
                        ],
                    ],
                    [
                        'axis'   => 'commercial_pressure_and_safety_boundary',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Yields to penalty pressure and agrees to continuous operations without considering safety limits. Does not consult company. No documentation.',
                            '2' => 'Attempts a compromise that satisfies the project manager but stretches safety margins. Does not formally address the tow wire condition.',
                            '3' => 'Balances commercial and safety: informs project manager of the constraints (DP limits, crew fatigue, wire condition), proposes a revised plan, refuses operations that exceed defined safety limits, informs company.',
                            '4' => 'Clear safety-first decision: refuses to operate beyond DP capability limits or with fatigued crew in high-risk operations, addresses tow wire at 70% strength (requires inspection and possible replacement or downrated load), communicates to project manager with specific technical justification, informs company with full analysis, proposes optimised plan to maximise work within safety boundaries, acknowledges penalty clause but documents that delay is weather-related not vessel-related.',
                            '5' => 'Expert tradeoff management: comprehensive risk-based decision — refuses unsafe operations, addresses tow wire proactively (at 70% BL the safe working load is significantly reduced for AH — may need to reduce pennant wire tensions or replace), documents that the schedule compression was caused by weather not vessel performance (defence against penalty clause), proposes optimised plan to company and project manager (2 anchors in daylight, rest, assess for 3rd), obtains MWS endorsement of the safety-based plan, communicates to all parties that an anchor handling incident would cause far greater delay and cost than a weather-related schedule extension, prepares formal notice to charterer that any delay beyond day 5 is attributable to weather conditions (force majeure defence), demonstrates mastery of both technical and commercial aspects.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No weather window analysis against time needed for operations',      'severity' => 'critical'],
                    ['flag' => 'No crew fatigue assessment for night anchor handling operations',     'severity' => 'critical'],
                    ['flag' => 'No tow wire condition assessment before high-load AH operations',    'severity' => 'critical'],
                    ['flag' => 'No DP capability check against forecast conditions during operations','severity' => 'major'],
                    ['flag' => 'No formal response to project manager with safety justification',    'severity' => 'major'],
                    ['flag' => 'No MWS consultation for the revised operational plan',               'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'IMCA M 103 — DP vessel operations guidelines',
                    'IMCA M 203 — Guidelines for anchor handling operations',
                    'IMO MSC.1/Circ.1580 — DP system guidelines',
                    'ISM Code — Master\'s overriding authority',
                    'STCW Code A-VIII/1 — Fitness for duty, rest hours',
                    'MLC 2006 — Hours of work and rest',
                    'Company SMS — Anchor handling procedures and weather limits',
                ],
                'red_flags_json' => [
                    'Agreeing to continuous night anchor handling with fatigued crew',
                    'Using tow wire at 70% strength without assessment or downrating',
                    'Ignoring DP capability limits approaching during planned operations',
                    'Allowing charter party penalties to override safety decision-making',
                    'No crew fatigue assessment despite 3 days of 16-hour operations',
                    'Not consulting MWS before proceeding with operations in marginal conditions',
                ],
            ],

        ];
    }
}
