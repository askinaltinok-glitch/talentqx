<?php

namespace Database\Seeders;

use App\Models\MaritimeScenario;
use Illuminate\Database\Seeder;

/**
 * Populate CONTAINER_ULCS scenarios with production-quality content.
 *
 * Idempotent: updates existing rows by scenario_code.
 * Run: php82 artisan db:seed --class=ContainerUlcsScenarioContentSeeder --force
 */
class ContainerUlcsScenarioContentSeeder extends Seeder
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

        // Activate all 8 CONTAINER_ULCS scenarios
        $activated = MaritimeScenario::where('command_class', 'CONTAINER_ULCS')
            ->where('version', 'v2')
            ->update(['is_active' => true]);

        $this->command->info("CONTAINER_ULCS scenario content seeded and activated ({$activated} scenarios).");
    }

    private function getScenariosSlot1to4(): array
    {
        return [

            // ══════════════════════════════════════════════════════════════
            // SLOT 1 — NAV_COMPLEX — TSS + congested waters + mega-ship squat/bank effect
            // ══════════════════════════════════════════════════════════════
            'CONTAINER_ULCS_S01_NAV_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 20,000 TEU Ultra Large Container Ship, draft 16 metres, approaching the Singapore Strait Traffic Separation Scheme westbound from the South China Sea. The Strait is at peak traffic density with over 30 AIS targets within 6 NM. Your vessel\'s stopping distance at current speed (16 knots) is 2.2 NM, and the loaded block coefficient of 0.71 produces significant squat in the shallow patches ahead where charted depth reduces to 21 metres. VTIS Singapore has advised you of a VLCC overtaking in the same lane 1.5 NM astern, and two opposing container feeders are approaching at a combined closing speed of 28 knots in a section where the navigable channel narrows to 0.9 NM. Bank effect will be significant as you pass close to the southern shoal. A tug-and-tow combination moving at 6 knots is directly ahead in your lane, restricting your ability to maintain safe speed.',
                        'your_position'       => 'Bridge, command. C/O as OOW, helmsman on manual steering, two lookouts posted (bridge wings). Pilot not yet embarked.',
                        'available_resources' => 'ECDIS with real-time AIS overlay and safety contour set at 19.5m, two ARPA radars (X-band 6NM and S-band 12NM), UKC monitoring system with dynamic squat calculator, VHF Ch 16 and VTIS Singapore working channel, engine on standby with bow thruster available, echo sounder on continuous recording.',
                        'current_conditions'  => 'Visibility good (8 NM), wind NE 15 knots, tidal stream 1.5 knots setting WSW, depth 21-25m in fairway (draft 16m), traffic density very high, night approaching.',
                    ],
                    'tr' => [
                        'situation'           => '20.000 TEU Ultra Large Container Ship\'in kaptanısınız, draft 16 metre, Güney Çin Denizi\'nden batıya doğru Singapur Boğazı Trafik Ayırım Düzenine yaklaşıyorsunuz. Boğaz en yoğun trafik saatinde, 6 deniz mili içinde 30\'dan fazla AIS hedefi var. Geminizin mevcut hızdaki (16 knot) durma mesafesi 2,2 deniz mili ve 0,71\'lik blok katsayısı, harita derinliğinin 21 metreye düştüğü sığ bölgelerde önemli çökme (squat) üretiyor. VTIS Singapur, aynı şeritte 1,5 mil kıçınızda bir VLCC\'nin sollama yaptığını bildirdi; iki karşı yönlü konteyner feeder\'ı, seyredilebilir kanalın 0,9 mile daraldığı bölümde 28 knot birleşik yaklaşma hızıyla geliyor. Güney sığlığına yakın geçerken kıyı etkisi (bank effect) önemli olacak. Şeridinizde doğrudan önünüzde 6 knot hızla giden bir römorkör-duba kombinasyonu güvenli hızı korumanızı kısıtlıyor.',
                        'your_position'       => 'Köprüüstü, komuta sizde. Birinci zabit vardiya zabiti, dümenci manuel dümen, iki gözcü köprü kanatlarında. Kılavuz henüz binmedi.',
                        'available_resources' => '19,5m güvenlik konturu ayarlı gerçek zamanlı AIS\'li ECDIS, iki ARPA radar (X-band 6NM ve S-band 12NM), dinamik squat hesaplayıcılı UKC izleme sistemi, VHF Kanal 16 ve VTIS çalışma kanalı, makine hazırda baş itici mevcut, sürekli kayıtlı iskandil.',
                        'current_conditions'  => 'Görüş iyi (8 mil), rüzgar KD 15 knot, gelgit akıntısı 1,5 knot BGB yönlü, seyir yolunda derinlik 21-25m (draft 16m), çok yoğun trafik, gece yaklaşıyor.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан контейнеровоза 20 000 TEU, осадка 16 м, подход к СРД Сингапурского пролива. Плотность трафика максимальная — более 30 целей AIS в радиусе 6 миль. Тормозной путь 2,2 мили. Глубина снижается до 21 м, коэффициент полноты 0,71 создаёт значительную просадку. VTIS сообщает о VLCC, обгоняющем в 1,5 милях сзади, и двух встречных фидерах. Канал сужается до 0,9 мили, впереди буксир с баржей (6 узлов).',
                        'your_position'       => 'Мостик, командование. Старпом — ВП, рулевой на ручном, два впередсмотрящих. Лоцман не принят.',
                        'available_resources' => 'ЭКНИС с АИС (контур безопасности 19,5 м), два САРП радара, система контроля УКГ с калькулятором просадки, УКВ + канал VTIS, машина в готовности, подруливающее устройство.',
                        'current_conditions'  => 'Видимость 8 миль, ветер СВ 15 узлов, приливное течение 1,5 узла на ЗЮЗ, глубина 21-25 м, трафик очень плотный, приближается ночь.',
                    ],
                    'az' => [
                        'situation'           => '20.000 TEU konteyner gəmisinin kapitanısınız, çəki 16 m, Sinqapur boğazı TSS-ə yaxınlaşırsınız. Trafik sıxlığı çox yüksək — 6 mil daxilində 30+ AIS hədəf. Dayanma məsafəsi 2,2 mil. Dərinlik 21 m-ə düşür, blok əmsalı 0,71 əhəmiyyətli squat yaradır. VTIS arxadan VLCC-nin ötdüyünü, iki qarşı fiderin yaxınlaşdığını bildirir. Kanal 0,9 milə daralır, qarşıda yedəkçi-barj (6 düyün).',
                        'your_position'       => 'Körpüüstü, komanda sizdə. Birinci stürman ВП, sükanşı əl rejimində, iki baxıcı. Losman minməyib.',
                        'available_resources' => 'ECDIS/AIS (19,5 m kontur), iki ARPA radar, UKC/squat monitorinqi, VHF + VTIS kanalı, mühərrik hazır, baş itələyici, əks-səda ölçən.',
                        'current_conditions'  => 'Görmə 8 mil, ŞQ külək 15 knot, gelgit 1,5 knot QŞQ, dərinlik 21-25 m, çox sıx trafik, gecə yaxınlaşır.',
                    ],
                ],
                'decision_prompt'      => 'Describe your navigation strategy for transiting the Singapore Strait TSS with a 20,000 TEU ULCS at 16m draft, including squat management, traffic handling, bank effect mitigation, and bridge team coordination in this high-density traffic environment.',
                'decision_prompt_i18n' => [
                    'tr' => '16 metre draftlı 20.000 TEU ULCS ile Singapur Boğazı TSS geçişi için navigasyon stratejinizi, squat yönetimi, trafik yönetimi, bank etkisi azaltma ve bu yoğun trafik ortamında köprü ekibi koordinasyonunu açıklayın.',
                    'ru' => 'Опишите навигационную стратегию транзита через СРД Сингапурского пролива на контейнеровозе 20 000 TEU с осадкой 16 м: управление просадкой, трафиком, эффектом берега и координация мостика.',
                    'az' => '16 m çəkili 20.000 TEU ULCS ilə Sinqapur boğazı TSS keçidi üçün naviqasiya strategiyanızı təsvir edin: squat idarəsi, trafik, bank effekti azaldılması və körpü komandasının koordinasiyası.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'tss_navigation_planning',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Enters the TSS without a structured transit plan; no consideration of traffic density, channel narrowing, or stopping distance limitations for a ULCS. Maintains sea speed through the strait.',
                            '2' => 'Reduces speed but has no systematic approach to TSS transit; does not account for the tug-and-tow ahead or VLCC astern; no consideration of COLREG Rule 10 obligations for vessels using the TSS.',
                            '3' => 'Proper TSS transit plan: speed reduced to safe speed per COLREG Rule 6, maintains course through the designated lane, coordinates with VTIS Singapore on traffic management, plots CPA/TCPA for the opposing feeders and overtaking VLCC, plans for the tug-and-tow encounter.',
                            '4' => 'Comprehensive TSS transit: calculates safe speed considering stopping distance (2.2 NM at 16 knots), establishes speed reduction schedule before the narrowing section, communicates overtaking intentions with VLCC via VHF, coordinates with VTIS for safe passing arrangement with opposing traffic, plots multiple target tracks on ARPA, pre-plans the tug-and-tow overtaking with adequate clearance, establishes bridge team monitoring assignments for each traffic sector.',
                            '5' => 'Expert TSS navigation for ULCS: full passage plan briefing with the bridge team before entering the high-density section, speed progressively reduced accounting for 2.2 NM stopping distance (target speed ensuring stop within visible range of traffic), overtaking VLCC managed through VTIS coordination and VHF agreement with clear CPA criteria, opposing feeder encounter planned with speed/course fine-tuning, tug-and-tow overtaking planned for the widest channel section with VTIS clearance, contingency plans for each traffic conflict (including anchor readiness if needed), COLREG Rules 5/6/7/8/9/10 all demonstrably applied, continuous risk assessment shared with bridge team, and explicit abort criteria if traffic density exceeds safe management capacity.',
                        ],
                    ],
                    [
                        'axis'   => 'squat_and_ukc_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No awareness of squat for a deep-draft ULCS; does not monitor UKC; maintains speed without considering the 21m depth patches and 16m draft.',
                            '2' => 'Aware of reduced depth but does not calculate squat or consider the block coefficient effect; relies on static UKC without accounting for speed-dependent squat.',
                            '3' => 'Calculates squat at current speed using vessel-specific data (Cb 0.71), monitors UKC continuously via ECDIS safety contour and echo sounder, reduces speed to maintain minimum UKC as per company policy when approaching 21m patches.',
                            '4' => 'Systematic UKC management: uses dynamic squat calculator for multiple speeds (e.g., Barrass formula: squat = Cb × V² / 100), determines that at 16 knots the squat is approximately 1.8m reducing effective UKC to 3.2m, establishes maximum speed for 21m depth section to maintain company minimum UKC, ECDIS safety contour verified, tidal height factored into real-time UKC calculation, echo sounder on continuous monitoring with depth alarm set.',
                            '5' => 'Expert UKC management for ULCS: comprehensive squat analysis using both open-water and confined-channel formulae (accounting for channel width restriction ratio), considers bow squat vs stern squat for this hull form, factors in tidal stream effect on effective speed over ground and through water for squat calculation, assesses dynamic trim change due to squat, considers interaction-induced squat increase when passing the VLCC in close proximity, ECDIS grounding avoidance activated with look-ahead settings appropriate for ULCS stopping distance, establishes a speed/depth matrix for the entire transit, plans speed reduction waypoints before each shallow patch, considers emergency deepening options (ballast adjustment if time permits), and documents all UKC calculations in the bridge log.',
                        ],
                    ],
                    [
                        'axis'   => 'traffic_management_and_colreg',
                        'weight' => 0.25,
                        'rubric_levels' => [
                            '1' => 'No systematic target tracking; ignores the overtaking VLCC and opposing feeders; no VHF coordination with traffic or VTIS; does not apply COLREG in the TSS context.',
                            '2' => 'Tracks some targets on radar but no systematic CPA/TCPA monitoring; vague awareness of COLREG but does not specifically apply Rules 9 and 10 for narrow channels and TSS.',
                            '3' => 'Systematic traffic management: tracks all significant targets on ARPA, monitors CPA/TCPA, communicates with VTIS about traffic situation, applies COLREG Rule 9 (narrow channels) and Rule 10 (TSS), coordinates the overtaking situation with the VLCC, monitors the opposing feeders, and plans for the tug-and-tow encounter.',
                            '4' => 'Effective traffic management in high-density environment: dedicated radar watch assigned to one officer, all 30+ targets classified by risk priority, CPA alarm set to appropriate threshold for the strait (e.g., 0.5 NM), VTIS coordination for traffic sequencing, VLCC overtaking managed per COLREG Rule 13 with VHF agreement, opposing feeders tracked with speed/course adjustments planned, bank effect and interaction effect anticipated when passing close to other large vessels, bridge team briefed on expected encounter sequence.',
                            '5' => 'Expert traffic management for ULCS in congested waters: comprehensive traffic plot maintained by dedicated officer, risk-prioritised target tracking with defined action triggers, COLREG Rules 5/6/7/8/9/10/13 applied in the specific TSS/narrow channel context, VTIS used proactively to coordinate traffic flow rather than just receiving information, overtaking VLCC handled with calculated interaction effects (suction/repulsion at close quarters), opposing feeder encounter planned with minimum CPA considering the 400m LOA of the ULCS, tug-and-tow overtaking executed at the safest location with appropriate speed differential, bridge team operating as integrated unit with clear sector responsibilities, and contingency plans for each potential close-quarters situation including emergency manoeuvre options (acknowledging ULCS limited manoeuvrability).',
                        ],
                    ],
                    [
                        'axis'   => 'bridge_resource_management',
                        'weight' => 0.15,
                        'rubric_levels' => [
                            '1' => 'Master attempts to manage all navigation tasks single-handedly; no delegation; bridge team not briefed on the strait transit plan.',
                            '2' => 'Some delegation but roles unclear; bridge team not briefed on traffic situation or expected encounters; communication is reactive rather than proactive.',
                            '3' => 'Clear BRM: roles assigned (radar watch, VHF communications, lookout, helm), team briefed on the passage plan and expected traffic encounters, closed-loop communication protocol in use.',
                            '4' => 'Structured BRM for critical transit: full passage plan briefing before TSS entry, each officer assigned specific sector responsibility (forward/starboard/port/astern), communication protocol with standard phraseology, challenge-and-response for helm orders and speed changes, Master maintains strategic overview while delegating tactical monitoring, additional officers called to the bridge for the high-density section.',
                            '5' => 'Exemplary BRM for ULCS critical strait transit: comprehensive pre-transit briefing covering every aspect (traffic, UKC, squat, bank effect, contingencies, abort criteria), team roles documented on a briefing card, communication protocol with mandatory reporting triggers (CPA < threshold, UKC < minimum, roll/pitch increase), Master positioned for optimal oversight, officers assigned to specific radar ranges and sectors, lookouts on both bridge wings with portable VHF, engine room pre-warned of manoeuvring requirements, helmsman briefed on expected rudder orders and bank effect response, continuous situational awareness maintained through structured bridge team communication, and debrief planned after transit completion.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No squat calculation or UKC monitoring for 16m draft ULCS in 21m depth', 'severity' => 'critical'],
                    ['flag' => 'No speed reduction considering stopping distance in congested waters',   'severity' => 'critical'],
                    ['flag' => 'No VTIS coordination for traffic management',                          'severity' => 'critical'],
                    ['flag' => 'No assessment of interaction effects with overtaking VLCC',             'severity' => 'major'],
                    ['flag' => 'No bridge team briefing for critical TSS transit',                      'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'COLREG Rules 6, 8, 9, 10, 13 (safe speed, narrow channels, TSS, overtaking)',
                    'SOLAS Chapter V (voyage planning, VTIS)',
                    'IMO SN.1/Circ.295 — Ship Routeing (Singapore Strait TSS)',
                    'Company SMS — Strait transit and UKC procedures',
                    'MPA Singapore port rules and VTIS procedures',
                ],
                'red_flags_json' => [
                    'Maintaining full sea speed in confined waters with 2.2 NM stopping distance',
                    'No squat awareness for 16m draft ULCS in 21m depth waters',
                    'Overtaking in the narrow section of the TSS',
                    'Ignoring VTIS instructions or not communicating with VTIS',
                    'No bridge team briefing or role assignment for critical strait transit',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 2 — CMD_SCALE — Multi-party pressure: charterer ETA + port window + CII
            // ══════════════════════════════════════════════════════════════
            'CONTAINER_ULCS_S02_CMD_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of an 18,000 TEU ULCS, 36 hours from a major European hub port. The charterer has sent an urgent message demanding you advance your ETA by 6 hours to catch a tidal window for berthing — the port authority has confirmed that the designated deep-water berth is available only within a 4-hour window that closes earlier than your current ETA. Advancing the ETA requires increasing speed from 17 to 22 knots, which your CII tracking software shows will push the vessel from a borderline C rating to D for the annual Carbon Intensity Indicator. The Chief Engineer has reported that the main engine No. 3 turbocharger is showing an increasing vibration trend over the past 72 hours and recommends not exceeding 85% MCR until the turbocharger can be inspected. The charterer\'s operations manager is insistent, stating that a missed berth window will cost $180,000 in waiting time and schedule disruption. Your company fleet operations centre is pressing for a response, as the next available berth slot would delay the vessel by 3 days.',
                        'your_position'       => 'Master, in your office reviewing the charterer\'s message, C/E report, and CII data. You must communicate your decision within the hour.',
                        'available_resources' => 'CII tracking software with voyage simulation capability, engine performance monitoring system showing turbocharger vibration data, company fleet operations centre (24/7), weather routing service for speed/fuel optimisation, charter party documentation, ISM Code Master\'s authority reference in SMS, P&I club advisory line, Chief Engineer available for consultation.',
                        'current_conditions'  => 'Open ocean passage, weather favourable (wind W 15 knots, seas 2m), vessel currently at 17 knots economical speed, fuel reserves adequate, engine load 70% MCR, turbocharger vibration at 8.5 mm/s (alarm threshold 12 mm/s, shutdown 18 mm/s).',
                    ],
                    'tr' => [
                        'situation'           => '18.000 TEU ULCS\'nin kaptanısınız, büyük bir Avrupa hub limanına 36 saat mesafedesiniz. Kiracı acil bir mesajla yanaşma gelgit penceresi yakalamak için ETA\'nızı 6 saat öne almanızı talep etti — liman otoritesi derin su rıhtımının yalnızca mevcut ETA\'nızdan önce kapanan 4 saatlik pencerede müsait olduğunu doğruladı. ETA\'yı öne almak hızı 17\'den 22 knota çıkarmayı gerektiriyor ki CII takip yazılımınız bunun gemiyi sınır C derecesinden D\'ye düşüreceğini gösteriyor. Başmühendis, ana makine 3 No\'lu turboşarjörün son 72 saatte artan titreşim trendi gösterdiğini ve turboşarjör denetlenene kadar %85 MCR\'yi aşmamayı önerdiğini bildirdi. Kiracının operasyon müdürü ısrarcı, kaçırılan rıhtım penceresinin bekleme süresi ve program aksamasında 180.000$ maliyete yol açacağını belirtiyor. Şirket filo operasyon merkezi yanıt bekliyor — bir sonraki müsait rıhtım dilimi gemiyi 3 gün geciktirecek.',
                        'your_position'       => 'Kaptan, ofisinizde kiracı mesajını, Başmühendis raporunu ve CII verilerini inceliyorsunuz. Kararınızı bir saat içinde bildirmelisiniz.',
                        'available_resources' => 'Sefer simülasyonlu CII takip yazılımı, turboşarjör titreşim verili motor performans izleme sistemi, şirket filo operasyon merkezi (7/24), hız/yakıt optimizasyonu için hava rotalama servisi, çarter parti belgeleri, SMS\'de ISM Kodu Kaptan yetkisi referansı, P&I kulüp danışma hattı, Başmühendis danışma için müsait.',
                        'current_conditions'  => 'Açık deniz seyri, hava uygun (rüzgar B 15 knot, deniz 2m), gemi mevcut 17 knot ekonomik hızda, yakıt yeterli, motor yükü %70 MCR, turboşarjör titreşimi 8,5 mm/s (alarm eşiği 12 mm/s, kapatma 18 mm/s).',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан ULCS 18 000 TEU, 36 часов до крупного европейского порта. Фрахтователь требует ускорить ETA на 6 часов для приливного окна. Увеличение скорости с 17 до 22 узлов ухудшит CII рейтинг с C до D. Стармех докладывает о росте вибрации турбонагнетателя ГД №3, рекомендует не превышать 85% MCR. Пропуск окна обойдётся в $180 000, следующий слот через 3 дня.',
                        'your_position'       => 'Капитан, каюта. Анализируете сообщение фрахтователя, отчёт стармеха и данные CII. Решение — в течение часа.',
                        'available_resources' => 'ПО CII-трекинг, мониторинг двигателя (вибрация турбонагнетателя), флотский операционный центр 24/7, метеосервис, чартер-партия, ISM Code, P&I клуб, стармех.',
                        'current_conditions'  => 'Открытый океан, погода хорошая (ветер З 15, волна 2 м), 17 узлов экономход, 70% MCR, вибрация турбонагн. 8,5 мм/с (аларм 12 мм/с).',
                    ],
                    'az' => [
                        'situation'           => '18.000 TEU ULCS-nin kapitanısınız, böyük Avropa limanına 36 saat qalıb. Fraxtçı ETA-nı 6 saat irəli çəkməyi tələb edir — rıhtım yalnız 4 saatlıq pəncərədə mövcuddur. Sürəti 17-dən 22 knota artırmaq CII reytinqini C-dən D-yə düşürəcək. Baş mühəndis 3 saylı turboşarjerin artan vibrasiya göstərdiyini bildirir, 85% MCR-i aşmamağı tövsiyə edir. Rıhtım pəncərəsini qaçırmaq $180.000 xərc yaradacaq, növbəti slot 3 gün sonradır.',
                        'your_position'       => 'Kapitan, kabinetinizdə fraxtçı mesajını, baş mühəndis hesabatını və CII məlumatlarını nəzərdən keçirirsiniz. Qərar 1 saat ərzində bildirilməlidir.',
                        'available_resources' => 'CII proqramı, mühərrik monitorinqi (vibrasiya), filo əməliyyat mərkəzi 24/7, meteoroloji xidmət, çarter partiya, ISM Kodu, P&I klubu, baş mühəndis.',
                        'current_conditions'  => 'Açıq okean, hava yaxşı (Q külək 15, dalğa 2 m), 17 düyün, 70% MCR, turboşarjer vibrasiyası 8,5 mm/s (alarm 12 mm/s).',
                    ],
                ],
                'decision_prompt'      => 'How do you respond to the charterer\'s demand to advance ETA by 6 hours? Explain your decision-making process, how you balance safety (turbocharger condition), regulatory compliance (CII), commercial obligations, and your communication strategy with all stakeholders.',
                'decision_prompt_i18n' => [
                    'tr' => 'Kiracının ETA\'yı 6 saat öne alma talebine nasıl yanıt veriyorsunuz? Karar sürecinizi, güvenlik (turboşarjör durumu), düzenleyici uyumluluk (CII), ticari yükümlülükler ve tüm paydaşlarla iletişim stratejinizi açıklayın.',
                    'ru' => 'Как вы реагируете на требование фрахтователя ускорить ETA на 6 часов? Опишите процесс принятия решения, баланс безопасности (турбонагнетатель), регуляторного соответствия (CII), коммерческих обязательств и коммуникацию.',
                    'az' => 'Fraxtçının ETA-nı 6 saat irəli çəkmə tələbinə necə cavab verirsiniz? Qərar prosesini, təhlükəsizlik (turboşarjer), tənzimləyici uyğunluq (CII), kommersiya öhdəlikləri və maraqlı tərəflərlə ünsiyyət strategiyanızı izah edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'safety_vs_commercial_decision',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Immediately complies with the charterer demand, orders full speed without consulting C/E about turbocharger risk or checking CII implications. Prioritises commercial outcome over all safety considerations.',
                            '2' => 'Acknowledges turbocharger concern but agrees to increase speed above C/E recommended limit, hoping the vibration will not reach alarm threshold. Does not systematically assess the risk.',
                            '3' => 'Consults C/E on turbocharger safety limit (85% MCR), calculates achievable speed within this limit, checks CII impact at this speed, and proposes a compromise ETA based on maximum safe speed. Refuses to exceed C/E recommendation.',
                            '4' => 'Thorough safety analysis: obtains detailed turbocharger vibration trend data from C/E, calculates the speed achievable at 85% MCR (approximately 19-20 knots), simulates CII impact at this speed, determines a revised ETA that partially meets the berth window, communicates the engineering constraint to all parties with technical justification, explores alternatives (earlier pilot boarding, tidal assistance, tug assistance for berthing at reduced draft).',
                            '5' => 'Expert decision-making: comprehensive risk assessment integrating turbocharger failure probability at various loads (using trend analysis), CII boundary analysis for multiple speed scenarios, calculation of optimal speed profile (e.g., increase to 85% MCR for first 24 hours then reduce), considers the catastrophic consequence of turbocharger failure at sea vs. commercial cost of delay, invokes ISM Code Master\'s overriding authority with clear safety justification, documents the decision with supporting data, proposes creative alternatives to minimize delay (weather routing optimization for current assistance, port authority negotiation for extended berth window, partial speed increase within safe limits), and demonstrates that a turbocharger failure would cause far greater delay and cost than the current scheduling issue.',
                        ],
                    ],
                    [
                        'axis'   => 'stakeholder_communication',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No structured communication; either capitulates to charterer or refuses without explanation. Does not inform company or provide technical justification.',
                            '2' => 'Communicates the decision but without adequate technical justification; fails to address all stakeholders; communication is reactive rather than proactive.',
                            '3' => 'Communicates clearly with charterer (explaining the engineering and CII constraints), informs company operations of the situation and decision, provides a revised ETA based on maximum safe speed, and documents the communication.',
                            '4' => 'Effective multi-party communication: formal written message to charterer with specific technical limits (turbocharger vibration data, C/E recommendation, CII impact), company informed with full analysis and recommendation, port authority contacted about berth window flexibility, all communications documented in a structured format, Master demonstrates calm and professional tone under commercial pressure.',
                            '5' => 'Exemplary stakeholder management: comprehensive written communication to charterer referencing ISM Code Master\'s authority, charter party safety clauses, and MARPOL CII obligations, with specific technical data supporting the speed limitation; company briefed with full risk analysis and alternative proposals; port authority engaged proactively for berth window negotiation; P&I club notified preemptively; all communications in writing and logged; Master offers constructive alternatives while maintaining firm safety boundaries; demonstrates understanding of commercial implications while prioritizing safety; prepares for potential charter party dispute by documenting everything meticulously.',
                        ],
                    ],
                    [
                        'axis'   => 'regulatory_compliance_awareness',
                        'weight' => 0.25,
                        'rubric_levels' => [
                            '1' => 'No awareness of CII implications or MARPOL Annex VI requirements. Does not consider that exceeding the CII boundary has regulatory and commercial consequences.',
                            '2' => 'Mentions CII but does not calculate the specific impact of the speed increase. Does not understand the consequences of a D or E rating.',
                            '3' => 'Checks CII tracking software and identifies that 22 knots would push the rating from C to D. Understands that a D rating triggers a corrective action plan. Uses this as part of the decision-making process.',
                            '4' => 'Strong regulatory awareness: calculates CII impact for multiple speed scenarios (17, 19, 20, 22 knots), identifies the maximum speed that maintains C rating, understands that a D rating requires a Ship Energy Efficiency Management Plan (SEEMP) corrective action and may have charter party implications, considers the cumulative CII impact for the remainder of the annual period.',
                            '5' => 'Expert regulatory management: comprehensive CII analysis showing the annual impact of each speed scenario, demonstrates understanding of the MARPOL Annex VI CII framework including the trajectory to 2030, considers that a D rating affects not just this voyage but the annual rating, analyses whether the CII impact can be recovered on subsequent voyages through slower steaming, cross-references charter party CII clauses (if any), considers the reputational and commercial impact of a poor CII rating (port state scrutiny, potential surcharges, customer preferences), and uses the CII constraint as an additional documented reason supporting the safety-first decision.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No assessment of turbocharger failure risk before increasing speed',   'severity' => 'critical'],
                    ['flag' => 'No CII impact calculation for the speed increase',                    'severity' => 'major'],
                    ['flag' => 'No formal communication to charterer documenting the speed limitation','severity' => 'major'],
                    ['flag' => 'No documentation of Master\'s decision and safety justification',      'severity' => 'major'],
                    ['flag' => 'No consultation with weather routing for speed/route optimisation',    'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'ISM Code Section 5 — Master\'s responsibility and overriding authority',
                    'MARPOL Annex VI — CII/EEXI regulations (Carbon Intensity Indicator)',
                    'SOLAS Chapter V — Voyage planning and safe navigation',
                    'Company SMS — Master\'s authority and voyage management procedures',
                    'Charter Party terms — speed and performance warranties, safety clauses',
                ],
                'red_flags_json' => [
                    'Blindly complying with charterer demand without safety or CII assessment',
                    'Ignoring C/E turbocharger vibration warning and exceeding recommended MCR limit',
                    'No CII compliance check before agreeing to increased speed',
                    'No written documentation of decision or protest',
                    'Overriding Chief Engineer safety concerns under commercial pressure',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 3 — TECH_DEPTH — ECDIS route integrity + sensor disagreement
            // ══════════════════════════════════════════════════════════════
            'CONTAINER_ULCS_S03_TECH_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 20,000 TEU ULCS on ocean passage, approaching the Japanese coast for a port call at Yokohama. During the 0400 watch, the OOW reports that the primary ECDIS shows a 3-degree heading offset between the ship\'s heading line and the radar overlay — the radar targets appear shifted to port relative to the ECDIS chart display. Upon investigation, you find that the Gyro No. 1 heading (feeding the primary ECDIS) reads 087.5°T while the GPS heading reads 084.0°T — a 3.5° disagreement. The ARPA targets on the primary radar (also connected to Gyro No. 1) are showing systematic bearing drift on all tracked targets. The secondary ECDIS, which is connected to Gyro No. 2, shows the vessel position 0.2 NM east of the primary ECDIS position. You are 25 NM from the coast, approaching a TSS, with multiple fishing vessels in the area. The pilot boarding station is 15 NM ahead.',
                        'your_position'       => 'Bridge, called by OOW. C/O and 2/O present. Night, approaching dawn.',
                        'available_resources' => 'Two ECDIS units (primary on Gyro No. 1 + GPS-1, secondary on Gyro No. 2 + GPS-2), two ARPA radars (X-band on Gyro 1, S-band on Gyro 2), two gyro compasses, two GPS receivers, magnetic compass with deviation card, echo sounder, AIS transponder, multiple coastal lights and charted landmarks visible as dawn approaches, VHF for VTIS communication.',
                        'current_conditions'  => 'Visibility good (improving with dawn), wind N 10 knots, seas calm, vessel speed 18 knots, depth 80m, coastal lights visible at 15-20 NM, traffic moderate (fishing fleet + commercial traffic in TSS).',
                    ],
                    'tr' => [
                        'situation'           => '20.000 TEU ULCS\'nin kaptanısınız, okyanus geçişinde Yokohama limanı için Japonya kıyısına yaklaşıyorsunuz. 0400 vardiyasında VZ, birincil ECDIS\'te geminin pruva hattı ile radar örtüşmesi arasında 3 derecelik yön sapması olduğunu bildiriyor — radar hedefleri ECDIS harita gösterimine göre iskeleye kaymış görünüyor. İncelediğinizde, 1 No\'lu Cayro yönünün (birincil ECDIS\'i besleyen) 087,5°T okuduğunu, GPS yönünün ise 084,0°T okuduğunu buluyorsunuz — 3,5° uyumsuzluk. Birincil radardaki (1 No\'lu Cayro\'ya bağlı) ARPA hedefleri tüm izlenen hedeflerde sistematik kerteriz kayması gösteriyor. 2 No\'lu Cayro\'ya bağlı ikincil ECDIS, gemi pozisyonunu birincil ECDIS pozisyonunun 0,2 mil doğusunda gösteriyor. Kıyıya 25 mil mesafedesiniz, TSS\'ye yaklaşıyorsunuz, bölgede çok sayıda balıkçı teknesi var. Kılavuz alma noktası 15 mil ileride.',
                        'your_position'       => 'Köprüüstü, VZ tarafından çağrıldınız. Birinci Zabit ve İkinci Zabit mevcut. Gece, şafak yaklaşıyor.',
                        'available_resources' => 'İki ECDIS ünitesi (birincil Cayro 1 + GPS-1, ikincil Cayro 2 + GPS-2), iki ARPA radar (X-band Cayro 1, S-band Cayro 2), iki cayro pusula, iki GPS alıcı, sapma kartlı manyetik pusula, iskandil, AIS transponder, şafakla birlikte görünen kıyı fenerleri ve haritada işaretli yer işaretleri, VTIS için VHF.',
                        'current_conditions'  => 'Görüş iyi (şafakla artıyor), rüzgar K 10 knot, sakin deniz, hız 18 knot, derinlik 80m, kıyı fenerleri 15-20 mil mesafede görünür, orta trafik.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан контейнеровоза 20 000 TEU, подход к Японии (Йокогама). ВП докладывает: на основном ЭКНИС курсовая линия смещена на 3° от радарного наложения. Гирокомпас №1 показывает 087,5°T, GPS курс — 084,0°T (разница 3,5°). САРП показывает систематический дрейф пеленга. Резервный ЭКНИС (на гирокомпасе №2) показывает позицию на 0,2 мили восточнее. До берега 25 миль, впереди СРД, рыболовецкий флот, лоцманская станция через 15 миль.',
                        'your_position'       => 'Мостик, вызван ВП. Старпом и 2-й помощник на мостике. Ночь, рассвет приближается.',
                        'available_resources' => 'Два ЭКНИС (разные гирокомпасы + GPS), два САРП радара, два гирокомпаса, два GPS, магнитный компас, эхолот, АИС, береговые огни видны, УКВ.',
                        'current_conditions'  => 'Видимость хорошая, ветер С 10 узлов, штиль, 18 узлов, глубина 80 м, умеренный трафик.',
                    ],
                    'az' => [
                        'situation'           => '20.000 TEU konteyner gəmisinin kapitanısınız, Yokohama üçün Yaponiya sahilinə yaxınlaşırsınız. ВП birincil ECDIS-də gəmi kursu ilə radar arasında 3° fərq olduğunu bildirir. Girokompas №1: 087,5°T, GPS: 084,0°T — 3,5° uyğunsuzluq. ARPA-da sistematik peleng sürüşməsi var. İkincil ECDIS (girokompas №2) mövqeni 0,2 mil şərqdə göstərir. Sahilə 25 mil, TSS yaxınlaşır, balıqçı gəmiləri bölgədə, losman stansiyası 15 mil irəlidə.',
                        'your_position'       => 'Körpüüstü, ВП tərəfindən çağırılmısınız. 1-ci və 2-ci stürman var. Gecə, şəfəq yaxınlaşır.',
                        'available_resources' => 'İki ECDIS (fərqli girokompas + GPS), iki ARPA radar, iki girokompas, iki GPS, maqnit kompas, əks-səda ölçən, AIS, sahil işıqları görünür, VHF.',
                        'current_conditions'  => 'Görmə yaxşı, Ş külək 10 knot, sakit, 18 düyün, dərinlik 80 m, orta trafik.',
                    ],
                ],
                'decision_prompt'      => 'How do you diagnose and resolve the heading sensor disagreement and ECDIS position discrepancy? Describe your systematic approach to identifying the faulty sensor, verifying the vessel\'s true position and heading, and ensuring safe navigation for the coastal approach and pilot boarding.',
                'decision_prompt_i18n' => [
                    'tr' => 'Yön sensörü uyumsuzluğunu ve ECDIS pozisyon farkını nasıl teşhis edip çözüyorsunuz? Arızalı sensörün tespiti, geminin gerçek pozisyon ve yönünün doğrulanması ve kıyı yaklaşımı ile kılavuz alımı için güvenli navigasyonun sağlanmasına sistematik yaklaşımınızı açıklayın.',
                    'ru' => 'Как вы диагностируете и устраняете расхождение датчиков курса и позиции ЭКНИС? Опишите систематический подход к идентификации неисправного датчика, верификации позиции и курса, обеспечению безопасности прибрежного плавания.',
                    'az' => 'Kurs sensoru uyğunsuzluğunu və ECDIS mövqe fərqini necə diaqnoz edib həll edirsiniz? Nasaz sensorun müəyyənləşdirilməsi, gəminin həqiqi mövqe və kursunun yoxlanması və sahil yaxınlaşması üçün təhlükəsiz naviqasiyanın təmin edilməsinə sistematik yanaşmanızı təsvir edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'sensor_discrepancy_diagnosis',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Does not investigate the heading discrepancy. Continues to navigate using the primary ECDIS without questioning the sensor inputs. No awareness that a 3.5° heading error affects all radar-derived bearings and ECDIS display.',
                            '2' => 'Notices the discrepancy but cannot systematically diagnose which sensor is correct. May switch between ECDIS units without understanding why they disagree. No use of independent verification methods.',
                            '3' => 'Systematic diagnosis: compares Gyro 1, Gyro 2, GPS heading, and magnetic compass readings to identify the outlier. Takes visual bearings on coastal lights to establish a position fix. Concludes that Gyro 1 has developed an error based on correlation with independent references. Switches primary ECDIS heading input to Gyro 2 or GPS heading.',
                            '4' => 'Thorough sensor diagnosis: uses multiple independent methods — takes visual bearings on at least two charted objects for a position fix, checks magnetic compass with deviation card, compares both gyros with GPS heading, uses radar range and bearing of landmarks to verify position independently, checks GPS HDOP for reliability, investigates possible gyro error causes (latitude error, speed error, power supply), verifies both GPS receivers agree, and tabulates all heading sources for comparison.',
                            '5' => 'Expert sensor fault isolation: comprehensive cross-checking establishing ground truth using visual bearings on multiple charted objects, radar ranges for independent position confirmation, magnetic compass with deviation correction, analysis of whether the error is systematic (both gyros drifting vs. single gyro fault), checks Gyro 1 error log for trend, considers if the 0.2 NM position offset is consistent with the heading error applied over the vessel\'s recent track, examines GPS heading antenna baseline calibration, determines the root cause (e.g., Gyro 1 settling error, latitude correction fault, repeater sync loss), and documents all readings with timestamps for the equipment defect report.',
                        ],
                    ],
                    [
                        'axis'   => 'ecdis_competence_and_limitations',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Complete over-reliance on ECDIS. Treats the display as absolute truth. No understanding that ECDIS accuracy depends on sensor input quality. No awareness that this is a system with limitations.',
                            '2' => 'Aware that ECDIS can show errors but does not know how to verify or correct. Cannot explain why the radar overlay is shifted or why the two ECDIS units disagree. Does not consider manual position fixing.',
                            '3' => 'Understands ECDIS limitations: recognises the radar overlay shift is caused by the heading error, understands the position difference relates to different sensor inputs, knows how to select a different heading source in ECDIS configuration, and recognises the need for independent position verification.',
                            '4' => 'Strong ECDIS competence: changes primary ECDIS heading input from Gyro 1 to verified source, understands radar overlay alignment depends on heading source, verifies chart datum consistency, checks antenna position offsets in installation parameters, uses manual position input function to verify against visual/radar fix, ensures backup ECDIS is configured with the most accurate sensor suite.',
                            '5' => 'Expert ECDIS systems knowledge: systematically reconfigures both ECDIS units with verified sensor inputs, understands the sensor priority hierarchy and fallback logic, checks chart corrections are current, verifies CCRP settings for position accuracy, understands accumulated heading error over the track contributes to position offset, configures heading sensor disagreement alarms, knows IMO MSC.232(82) performance standards, and understands SOLAS V/27 obligation to verify ECDIS with independent means.',
                        ],
                    ],
                    [
                        'axis'   => 'position_verification_methodology',
                        'weight' => 0.25,
                        'rubric_levels' => [
                            '1' => 'No independent position verification. Relies entirely on electronic systems even when contradictory. Does not take visual or radar fixes.',
                            '2' => 'Attempts verification but incomplete — takes only one bearing or radar range without bearing. Does not establish a reliable position using traditional methods.',
                            '3' => 'Proper verification: takes visual bearings on at least two charted objects and plots a fix, takes radar ranges to confirm, compares manual fix with both ECDIS displays to determine which is more accurate.',
                            '4' => 'Thorough verification: visual bearings (minimum 3 objects), radar parallel index or range/bearing fixes, AIS cross-reference with known vessels, echo sounder depth vs charted depth, DR calculation from last reliable fix, establishes position-fixing schedule for coastal approach (e.g., every 15 minutes).',
                            '5' => 'Expert position verification for ULCS coastal approach: immediate reliable fix using best available methods (3-bearing visual + radar ranges), comparison of all electronic positions against manual fix, position error budget calculation, increased fix frequency approaching coast and TSS, radar parallel indexing on approach track, planning for pilot boarding area monitoring, X-band radar cross-check and correction, accounts for ULCS dimensions (bridge position significantly aft of bow — critical for channel navigation), and documents verified position for pilot handover.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No cross-check between independent position sources (visual, radar, GPS)',   'severity' => 'critical'],
                    ['flag' => 'No gyrocompass error investigation or identification of the faulty compass',  'severity' => 'critical'],
                    ['flag' => 'No manual position fix using visual bearings or radar ranges',                'severity' => 'critical'],
                    ['flag' => 'No switch to backup heading source or correction of ECDIS heading input',    'severity' => 'major'],
                    ['flag' => 'No reporting of equipment malfunction in log and to company',                 'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter V Regulation 19 — Carriage requirements for navigation equipment',
                    'IMO MSC.232(82) — Revised performance standards for ECDIS',
                    'IMO Resolution A.424(XI) — Performance standards for gyro compasses',
                    'Company SMS — Equipment failure procedures and navigation with degraded systems',
                    'STCW Code Table A-II/2 — Competence standards for masters (position determination)',
                ],
                'red_flags_json' => [
                    'Blindly trusting ECDIS display without cross-checking when discrepancies are known',
                    'Ignoring heading sensor disagreement and continuing coastal approach on incorrect heading',
                    'No manual verification of position using visual bearings or radar',
                    'Continuing passage without resolving the heading/position discrepancy',
                    'No log entry or defect report for the gyrocompass malfunction',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 4 — RISK_MGMT — Lashing/securing + parametric rolling + VGM
            // ══════════════════════════════════════════════════════════════
            'CONTAINER_ULCS_S04_RISK_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 15,000 TEU ULCS on a North Pacific winter crossing from Busan to Long Beach, loaded to near maximum capacity with 40ft containers stacked 9-high on deck in the forward bays. The weather forecast shows a developing low-pressure system producing beam to quartering seas with significant wave height of 5-6 metres and dominant wave period of 10-12 seconds for the next 48 hours. Your vessel\'s natural roll period, calculated from the current loading condition, is 22 seconds — the encounter period in beam seas at current speed could produce a 2:1 resonance ratio, the classic parametric rolling condition. GM is 2.8 metres (high for a container ship), creating a stiff vessel with short natural roll period relative to her size. The Chief Officer reports that lashing rods on Bays 50, 52, and 54 (forward of accommodation) show signs of fatigue — rust at turnbuckle threads, visible elongation on several rods, and two rods on Bay 54 already broken from the previous crossing. The loading computer flags VGM declarations for 23 containers loaded in Busan as "shipper-declared Method 2" with suspicious weights — several 40ft containers declared at 12 tonnes for machinery parts. The C/O estimates actual weights may be 20-30% higher, which could push stack forces beyond Cargo Securing Manual limits.',
                        'your_position'       => 'Bridge, reviewing weather forecast with C/O. Heavy weather expected within 12 hours.',
                        'available_resources' => 'Loading computer with stability and lashing force calculations, Cargo Securing Manual, weather routing service (DTN), VGM documentation from Busan terminal, spare lashing rods (limited stock — 20 rods), bosun and 4 ABs for lashing inspection/repair, company operations and DPA by satellite, stability booklet and ballast adjustment capability.',
                        'current_conditions'  => 'North Pacific, 40°N 170°E, wind W Force 5 increasing to 7-8, seas 3m building to 5-6m, swell W 10-12s period, speed 17 knots, course 085°T (beam seas from west), barometric pressure falling.',
                    ],
                    'tr' => [
                        'situation'           => '15.000 TEU ULCS\'nin kaptanısınız, Busan\'dan Long Beach\'e Kuzey Pasifik kış geçişindesiniz, azami kapasiteye yakın yüklü, ön ambarlarda güvertede 9 kat 40ft konteyner yığılmış. Hava tahmini, 48 saat boyunca 5-6 m dalga yüksekliği ve 10-12 saniye baskın dalga periyodu ile baş-omuz/kıç-omuz denizleri üretecek gelişen bir alçak basınç gösteriyor. Geminizin doğal yalpa periyodu 22 saniye — mevcut hızda baş-omuz denizlerindeki karşılaşma periyodu dalga periyoduyla 2:1 rezonans oranı (klasik parametrik yalpa koşulu) üretebilir. GM 2,8 metre (konteyner gemisi için yüksek). Birinci Zabit, 50-54 Numaralı Ambarlardaki bağlama çubuklarının yorgunluk belirtileri gösterdiğini, gergi dişlerinde pas, görünür uzama ve Bay 54\'te iki kırık çubuk olduğunu bildirdi. Yükleme bilgisayarı Busan\'dan yüklenen 23 konteynerin VGM beyanlarını şüpheli işaretledi — makine parçası olan birkaç 40ft konteyner 12 ton beyan edilmiş. Birinci Zabit gerçek ağırlıkların %20-30 fazla olabileceğini, bu durumda yığın kuvvetlerinin Kargo Bağlama El Kitabı limitlerini aşabileceğini tahmin ediyor.',
                        'your_position'       => 'Köprüüstü, Birinci Zabit ile hava tahminini değerlendiriyorsunuz. Ağır hava 12 saat içinde bekleniyor.',
                        'available_resources' => 'Stabilite ve bağlama kuvveti hesaplamalı yükleme bilgisayarı, Kargo Bağlama El Kitabı, hava rotalama servisi, Busan VGM belgeleri, yedek bağlama çubukları (20 adet), lostromo ve 4 güverte eri, uydu ile şirket/DPA, balast ayar imkanı.',
                        'current_conditions'  => 'Kuzey Pasifik, 40°K 170°D, rüzgar B Kuvvet 5 artarak 7-8, deniz 3m artarak 5-6m, batı dalgası 10-12s periyod, hız 17 knot, rota 085°T (batıdan baş-omuz denizi), basınç düşüyor.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан ULCS 15 000 TEU, зимний переход Пусан—Лонг-Бич, Северная Пацифика. Загрузка близка к максимуму, 40-футовые контейнеры в 9 ярусов в носовых бэях. Прогноз: бортовая/скуловая волна Hs 5-6 м, период 10-12 с, 48 часов. Период качки судна 22 с — возможен параметрический резонанс 2:1. GM 2,8 м (высокая МВ). Старпом: найтовочные штанги бэев 50-54 — коррозия, вытянутые стержни, 2 сломаны. VGM 23 контейнеров (Метод 2) подозрительны — «запчасти» в 40-фут по 12 тонн. Реальный вес может быть на 20-30% больше, превышая нормы Cargo Securing Manual.',
                        'your_position'       => 'Мостик, обзор прогноза со старпомом. Шторм через 12 часов.',
                        'available_resources' => 'Загрузочный компьютер, Cargo Securing Manual, метеосервис, VGM документы, 20 запасных найтовочных штанг, боцман + 4 матроса, спутниковая связь, балластные таблицы.',
                        'current_conditions'  => '40°N 170°E, ветер З 5→7-8, волна 3→5-6 м, зыбь 10-12 с, 17 узлов, курс 085°T, давление падает.',
                    ],
                    'az' => [
                        'situation'           => '15.000 TEU ULCS-nin kapitanısınız, Pusan-Lonq Biç, Şimali Sakit okean qış keçidi. Yüklənmə maksimuma yaxın, burun beylərdə 40ft konteynerlər 9 qat. Proqnoz: bort dalğası Hs 5-6 m, period 10-12 s, 48 saat. Gəminin yırğalanma periodu 22 s — parametrik rezonans 2:1 mümkün. GM 2,8 m. Birinci stürman: bey 50-54 bağlama çubuqlarında yorğunluq, 2 sınıb. VGM 23 konteyner şübhəli — 40ft «maşın hissələri» 12 ton (həqiqi çəki 20-30% artıq ola bilər).',
                        'your_position'       => 'Körpüüstü, birinci stürmanla proqnozu nəzərdən keçirirsiniz. Fırtına 12 saatdan sonra.',
                        'available_resources' => 'Yükləmə kompüteri, Cargo Securing Manual, meteoroloji xidmət, VGM sənədləri, 20 ehtiyat bağlama çubuğu, losman + 4 matros, peyk rabitəsi, ballast cədvəlləri.',
                        'current_conditions'  => '40°N 170°E, Q külək 5→7-8, dalğa 3→5-6 m, 10-12 s period, 17 düyün, 085°T, təzyiq düşür.',
                    ],
                ],
                'decision_prompt'      => 'Describe your comprehensive risk management plan for this situation. Address how you handle the parametric rolling threat, the lashing deficiencies in the forward bays, the suspected VGM discrepancies, and your weather routing decision. What immediate actions do you take and how do you prioritise?',
                'decision_prompt_i18n' => [
                    'tr' => 'Bu durum için kapsamlı risk yönetimi planınızı açıklayın. Parametrik yalpa tehdidini, ön ambarlardaki bağlama eksikliklerini, şüpheli VGM tutarsızlıklarını ve hava rotalama kararınızı ele alın. Hangi acil önlemleri alıyorsunuz ve nasıl önceliklendiriyorsunuz?',
                    'ru' => 'Опишите комплексный план управления рисками: параметрическая качка, дефекты найтовки, подозрительные VGM, маршрутизация. Какие немедленные действия и приоритеты?',
                    'az' => 'Bu vəziyyət üçün hərtərəfli risk idarəetmə planınızı təsvir edin: parametrik yırğalanma, bağlama qüsurları, şübhəli VGM, hava marşrutlaşdırma. Dərhal hansı tədbirləri görürsünüz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'parametric_rolling_awareness',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'No awareness of parametric rolling. Does not recognise that GM 2.8m, natural roll period 22s, wave period 10-12s, and beam seas create a resonance risk. Continues same course and speed.',
                            '2' => 'Vaguely aware of heavy rolling risk but does not understand parametric rolling mechanism. May reduce speed but does not change course or consider encounter period.',
                            '3' => 'Understands parametric rolling: recognises the 2:1 encounter resonance can cause extreme roll angles of 30-40°. Alters course or speed to change encounter period and move away from resonance. Considers reducing GM by adjusting ballast.',
                            '4' => 'Strong parametric rolling management: calculates encounter period at current speed and heading, identifies 2:1 ratio as dangerous, develops course/speed matrix for safe combinations avoiding resonance, adjusts ballast to optimise GM (reduce from 2.8m while maintaining adequate stability), briefs bridge team on parametric rolling onset signs.',
                            '5' => 'Expert response: comprehensive encounter frequency analysis for all feasible course/speed combinations, determines optimal GM window (e.g., 1.5-2.0m) avoiding both parametric and synchronous rolling, references IMO MSC.1/Circ.1228, sets up roll monitoring as early warning, pre-calculates course alterations for various roll limits, coordinates with weather routing for optimal track, considers the interaction between rolling and weakened lashing in forward bays, establishes clear action triggers.',
                        ],
                    ],
                    [
                        'axis'   => 'cargo_securing_assessment',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Ignores C/O lashing deficiency report. Takes no action before weather deteriorates. Does not consider that broken and fatigued rods reduce stack capacity.',
                            '2' => 'Acknowledges but takes only superficial action — tells C/O to "tighten everything" without systematic stack force assessment.',
                            '3' => 'Systematic response: orders immediate replacement of broken rods on Bay 54, inspects all lashings on Bays 50-54, assesses whether remaining rods can withstand forecast conditions per Cargo Securing Manual, reduces beam seas exposure. Reports VGM concern to company.',
                            '4' => 'Thorough securing management: replaces broken rods and re-tensions all affected bays, uses loading computer for lashing force check against Cargo Securing Manual criteria (roll angle, GM), identifies fatigued rods have reduced breaking strength and calculates safety margin, considers VGM understatement impact on stack weight, requests weather routing to minimise beam seas, prepares for further checks during abatement, issues formal VGM discrepancy report.',
                            '5' => 'Expert response treating the situation as a potential cargo safety emergency: performs Cargo Securing Manual calculations with worst-case VGM assumptions (30% above declared), identifies that at 2.8m GM and forecast roll angles the transverse forces may exceed design limits even with intact lashings, replaces all damaged rods, calculates remaining spare stock adequacy, recommends ballast adjustment to reduce GM and roll, coordinates course alteration with weather routing, considers diversion if lashing integrity cannot be assured, reports VGM under SOLAS Ch VI Reg 2, documents everything with photographs for P&I notification.',
                        ],
                    ],
                    [
                        'axis'   => 'weather_routing_decision',
                        'weight' => 0.25,
                        'rubric_levels' => [
                            '1' => 'No weather routing consideration. Continues great circle route regardless of forecast. No course or speed alteration.',
                            '2' => 'Reduces speed but does not consider course alteration or routing alternatives. Does not consult routing service.',
                            '3' => 'Contacts weather routing service for alternative route avoiding worst beam seas. Considers trade-off between distance and cargo risk. Alters course for more favourable encounter angle.',
                            '4' => 'Effective routing: evaluates multiple route options with routing service, calculates encounter period for each, selects route minimising parametric rolling risk, adjusts speed appropriately, communicates revised ETA, monitors weather for forecast changes.',
                            '5' => 'Expert voyage management: comprehensive route analysis (great circle, rhumb line, storm avoidance), parametric rolling risk calculated for each, total voyage risk assessment (lashing failure probability x consequence), fuel/CII impact of diversion, optimal safety/efficiency balance, specific waypoints for heading changes, monitoring criteria for route adjustment, contingency diversion plan (e.g., Dutch Harbor), and full risk assessment communicated to company.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No parametric rolling risk assessment despite 2:1 resonance conditions', 'severity' => 'critical'],
                    ['flag' => 'No lashing inspection or repair action before heavy weather',           'severity' => 'critical'],
                    ['flag' => 'No VGM verification challenge or misdeclaration reporting',             'severity' => 'major'],
                    ['flag' => 'No course or speed alteration to avoid beam seas resonance',             'severity' => 'critical'],
                    ['flag' => 'No stability reassessment or ballast adjustment for GM management',     'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'CSS Code — Code of Safe Practice for Cargo Stowage and Securing (IMO)',
                    'SOLAS Chapter VI Regulation 2 — Verified Gross Mass (VGM) requirements',
                    'IMO MSC.1/Circ.1228 — Revised guidance on parametric rolling for containerships',
                    'Cargo Securing Manual (vessel-specific, classification society approved)',
                    'Company SMS — Heavy weather procedures and cargo securing requirements',
                    'CTU Code — Code of Practice for Packing of Cargo Transport Units',
                    'IMO MSC.1/Circ.1352 — Amendments to CSS Code (lashing standards)',
                ],
                'red_flags_json' => [
                    'No awareness of parametric rolling despite 2:1 resonance ratio conditions',
                    'Continuing beam seas course where encounter period matches natural roll period',
                    'Ignoring lashing rod fatigue signs and broken rods before heavy weather',
                    'No VGM compliance check or challenge of suspicious declared weights',
                    'No heavy weather preparation or crew briefing',
                    'No course alteration to change encounter period and avoid parametric rolling',
                ],
            ],

        ];
    }

    private function getScenariosSlot5to8(): array
    {
        return [

            // ══════════════════════════════════════════════════════════════
            // SLOT 5 — CREW_LEAD — Fatigue + multi-national crew + BRM breakdown
            // ══════════════════════════════════════════════════════════════
            'CONTAINER_ULCS_S05_CREW_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 20,000 TEU ULCS on day 28 of a 35-day Asia-Europe rotation. At 0530 during pilotage approach to Jebel Ali, the pilot boat reports ready but the 2nd Officer, who is OOW, is found asleep at the radar console by the Chief Officer arriving for the pre-arrival briefing. This is the second fatigue incident this voyage — an AB was found sleeping on lookout duty two weeks ago. Upon reviewing the rest hour records, you discover that two ABs have exceeded the maximum work hours under MLC/STCW (exceeding 14 hours in any 24-hour period on three occasions this week), with the records showing borderline entries that suggest possible manipulation. The vessel has a multinational crew — Filipino deck officers, Ukrainian engineers, and Indian ratings. During last week\'s fire drill, critical communication breakdowns were observed: the Indian bosun did not understand the Ukrainian C/E\'s instructions for pump isolation, and the Filipino 3/O gave contradictory muster instructions. A SIRE inspection by OCIMF is scheduled in 3 days at the next port.',
                        'your_position'       => 'Bridge, called urgently by C/O who found 2/O asleep. Pilot boarding in 20 minutes. Port approach in progress.',
                        'available_resources' => 'Full bridge team (C/O, 3/O, helmsman, lookout), pilot boarding arrangements in progress, rest hour recording system (electronic), company DPA reachable by satellite phone, ISM Code and company SMS procedures, ship\'s training records and drill reports, SIRE VIQ preparation checklist, crew list with qualifications and language certifications.',
                        'current_conditions'  => 'Dawn, visibility good, traffic moderate in approach channel, vessel speed 12 knots reducing for pilot boarding, port ETB 0800.',
                    ],
                    'tr' => [
                        'situation'           => '20.000 TEU ULCS\'nin kaptanısınız, 35 günlük Asya-Avrupa rotasyonunun 28. günü. 0530\'da Jebel Ali kılavuz yaklaşımı sırasında, kılavuz botu hazır bildiriyor ama varış öncesi brifing için gelen Birinci Zabit, İkinci Zabiti radar konsolu başında uyurken buluyor. Bu seyirde ikinci yorgunluk olayı — iki hafta önce bir güverte eri gözcü nöbetinde uyurken bulunmuştu. Dinlenme saati kayıtlarını incelediğinizde, iki güverte erinin MLC/STCW kapsamındaki azami çalışma saatlerini aştığını (bu hafta üç kez 24 saatlik dönemde 14 saati aştığını) ve kayıtların olası manipülasyon düşündüren sınır değerler gösterdiğini keşfediyorsunuz. Gemi çok uluslu bir mürettebata sahip — Filipinli güverte zabitleri, Ukraynalı mühendisler ve Hintli tayfa. Geçen haftaki yangın tatbikatında kritik iletişim kopuklukları gözlemlendi: Hintli lostromo Ukraynalı Başmühendisin pompa izolasyon talimatlarını anlamadı, Filipinli 3. Zabit çelişkili toplanma talimatları verdi. 3 gün sonra bir sonraki limanda OCIMF tarafından SIRE denetimi planlanmış.',
                        'your_position'       => 'Köprüüstü, 2. Zabiti uyurken bulan Birinci Zabit tarafından acil çağrıldınız. Kılavuz binişi 20 dakika sonra. Liman yaklaşımı devam ediyor.',
                        'available_resources' => 'Tam köprü ekibi (Birinci Zabit, 3. Zabit, dümenci, gözcü), kılavuz alma hazırlıkları, elektronik dinlenme saati kayıt sistemi, uydu telefonu ile şirket DPA, ISM Kodu ve SMS prosedürleri, eğitim kayıtları ve tatbikat raporları, SIRE VIQ hazırlık kontrol listesi, yeterlilik ve dil sertifikalı mürettebat listesi.',
                        'current_conditions'  => 'Şafak, görüş iyi, yaklaşım kanalında orta trafik, kılavuz alımı için hız 12 knota düşürülüyor, ETB 0800.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан ULCS 20 000 TEU, 28-й день 35-дневной ротации Азия-Европа. В 05:30, при подходе к Джебель-Али, старпом находит 2-го помощника спящим у радара. Это второй инцидент усталости. Два матроса превысили нормы отдыха MLC/STCW (3 раза на этой неделе > 14 часов в сутки), записи подозрительны. Многонациональный экипаж (филиппинцы, украинцы, индийцы) — на прошлой неделе критические сбои связи на пожарной тревоге. SIRE инспекция через 3 дня.',
                        'your_position'       => 'Мостик, вызван старпомом. Приём лоцмана через 20 минут. Подход к порту продолжается.',
                        'available_resources' => 'Вахтенная команда, электронная система учёта отдыха, ДОБ по спутнику, ISM/SMS, учебные записи, чек-лист SIRE VIQ, судовая роль.',
                        'current_conditions'  => 'Рассвет, видимость хорошая, трафик умеренный, 12 узлов, ETB 0800.',
                    ],
                    'az' => [
                        'situation'           => '20.000 TEU ULCS-nin kapitanısınız, Asiya-Avropa rotasiyasının 28-ci günü. 05:30-da Jəbəl Əliyə yaxınlaşma zamanı birinci stürman 2-ci stürmanı radar başında yuxuda tapır. Bu, ikinci yorğunluq hadisəsidir. İki matros MLC/STCW istirahət normalarını aşıb (həftədə 3 dəfə 24 saatda >14 saat). Çoxmillətli heyət (filippinli, ukraynalı, hindli) — keçən həftə yanğın məşqində kritik ünsiyyət problemləri. SIRE inspeksiyası 3 gün sonra.',
                        'your_position'       => 'Körpüüstü, birinci stürman tərəfindən çağırılmısınız. Losman 20 dəqiqədən sonra. Liman yaxınlaşması davam edir.',
                        'available_resources' => 'Tam körpü komandası, elektron istirahət qeyd sistemi, peyk telefonu ilə DPA, ISM/SMS, təlim qeydləri, SIRE VIQ çeklistı, heyət siyahısı.',
                        'current_conditions'  => 'Şəfəq, görmə yaxşı, orta trafik, 12 düyün, ETB 0800.',
                    ],
                ],
                'decision_prompt'      => 'How do you handle this situation? Address: (1) the immediate safety concern of OOW sleeping on watch during port approach, (2) the systemic fatigue and rest hour compliance issues, (3) the crew communication and language barriers affecting safety operations, and (4) how you prepare for the SIRE inspection while addressing these safety deficiencies. What are your immediate, short-term, and medium-term actions?',
                'decision_prompt_i18n' => [
                    'tr' => 'Bu durumu nasıl yönetiyorsunuz? Ele alın: (1) liman yaklaşımında VZ\'nin nöbette uyumasının acil güvenlik endişesi, (2) sistemik yorgunluk ve dinlenme saati uyum sorunları, (3) güvenlik operasyonlarını etkileyen mürettebat iletişimi ve dil engelleri, (4) bu güvenlik eksikliklerini giderirken SIRE denetimine nasıl hazırlanırsınız.',
                    'ru' => 'Как вы справляетесь с ситуацией? Рассмотрите: (1) немедленная безопасность — ВП спит на вахте при подходе, (2) системные проблемы усталости и соответствия нормам отдыха, (3) языковые барьеры экипажа, (4) подготовка к SIRE с учётом выявленных дефицитов.',
                    'az' => 'Bu vəziyyəti necə idarə edirsiniz? Nəzərə alın: (1) liman yaxınlaşmasında ВП-nin növbədə yuxulaması, (2) sistemik yorğunluq və istirahət normaları problemi, (3) heyət ünsiyyəti və dil maneələri, (4) SIRE inspeksiyasına hazırlıq.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'fatigue_and_rest_hour_management',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Ignores the 2/O sleeping on watch or treats it as a minor issue. No review of rest hour records. No assessment of crew fatigue levels. Continues the port approach without addressing the watch-keeping failure.',
                            '2' => 'Wakes the 2/O and reprimands him but takes no systemic action. Does not review rest hour records. Does not assess whether the current watch arrangement is sustainable or safe.',
                            '3' => 'Immediately relieves the 2/O from watch and assigns C/O or 3/O for the port approach. Reviews rest hour records and identifies the non-compliance. Adjusts the watch schedule to ensure adequate rest. Reports the incident to the company DPA. Documents the incident in the ship\'s log.',
                            '4' => 'Comprehensive fatigue management: immediately relieves 2/O and ensures a competent OOW for pilot boarding, conducts a thorough review of all crew rest hour records (not just the 2/O), identifies the pattern of non-compliance and its root causes (undermanning, excessive port work, poor planning), adjusts the watch schedule with immediate effect, considers whether the vessel is adequately manned per SOLAS V/14, reports to DPA with full details and requests additional manning if needed, implements fatigue risk management assessment per STCW guidelines, and takes formal disciplinary action against the 2/O with documentation.',
                            '5' => 'Expert fatigue and compliance management: immediate watch relief with documented handover, comprehensive audit of all rest hour records for the entire voyage identifying patterns (not just individual violations), root cause analysis addressing systemic factors (voyage schedule demands, port turnaround time, overtime distribution), comparison against MLC minimum rest requirements and STCW fitness for duty standards, investigation of whether records have been falsified (comparing actual work observations with recorded hours), formal report to company DPA requesting a fatigue risk assessment review, consideration of reducing vessel operations tempo to allow recovery, revised watch bill ensuring compliance going forward, formal incident report with corrective actions that satisfy both ISM Code requirements and SIRE VIQ expectations, and consideration of whether the vessel should delay port entry until adequate rest levels are restored if safety cannot be assured.',
                        ],
                    ],
                    [
                        'axis'   => 'bridge_resource_management',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No BRM response. Continues the port approach with the fatigued 2/O or without proper watch relief. No reassessment of bridge team capability for the pilotage.',
                            '2' => 'Relieves the 2/O but does not reassess the overall bridge team capability. Does not conduct a pre-arrival briefing addressing the changed circumstances. Minimal communication about the incident.',
                            '3' => 'Proper BRM: relieves 2/O, assigns competent replacement for pilotage, conducts abbreviated pre-arrival briefing, ensures all bridge team members are alert and briefed on their roles, communicates clearly with the pilot about the vessel\'s current manning situation.',
                            '4' => 'Strong BRM: full reassessment of bridge team for the pilotage approach — verifies each team member\'s alertness and fitness for duty, conducts comprehensive pre-arrival briefing covering the approach, berthing plan, and contingencies, assigns specific monitoring responsibilities, establishes clear communication protocol with standard phraseology, pre-briefs the pilot on any vessel-specific handling characteristics, and plans for enhanced monitoring during the critical phase.',
                            '5' => 'Exemplary BRM for a compromised crew situation: conducts a formal fitness-for-duty assessment of all bridge team members before proceeding with the approach, considers delaying pilot boarding if team capability is inadequate, comprehensive pre-arrival briefing with explicit acknowledgment of the fatigue risks, assigns redundant monitoring roles, establishes challenge-and-response protocol for all critical actions, plans for additional bridge manning during berthing, debriefs the team on the fatigue incident to raise awareness, documents the BRM measures taken as evidence of safety management, and ensures the pilot is fully briefed on any limitations.',
                        ],
                    ],
                    [
                        'axis'   => 'crew_communication_and_leadership',
                        'weight' => 0.25,
                        'rubric_levels' => [
                            '1' => 'Ignores the communication breakdown issues identified in the drill. No action on language barriers. Blames individual crew members without addressing systemic issues.',
                            '2' => 'Acknowledges the communication problems but takes no concrete action. Plans to "deal with it after the SIRE" rather than addressing it now. No assessment of language proficiency.',
                            '3' => 'Addresses communication issues: conducts language proficiency assessment for key safety roles, implements standard maritime English for all safety communications, schedules additional drills with focus on communication, and assigns bilingual crew members as communication bridges during emergencies.',
                            '4' => 'Proactive leadership: formal language proficiency review against STCW requirements, implements a shipboard communication plan with standardised commands and responses for emergencies, conducts focused drills addressing the specific communication failures observed, assigns roles based on communication capability (not just rank), creates visual aids and checklists in multiple languages for critical procedures, reports language deficiency concerns to company for recruitment/training review.',
                            '5' => 'Comprehensive crew leadership addressing the systemic issues: formal English language assessment for all crew in safety-critical roles with documented results, multilingual emergency procedure cards developed and distributed, communication-focused drills scheduled before SIRE with specific scenarios addressing the observed failures, crew feedback sessions to identify other communication barriers, leadership approach that builds trust across nationalities rather than creating blame, considers requesting crew replacement for positions where language barrier poses an unacceptable safety risk, implements a mentoring system pairing stronger English speakers with weaker ones, documents all corrective actions as ISM Code continuous improvement evidence, and demonstrates to the SIRE inspector that the vessel has identified and is actively managing the communication risk.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No immediate action for OOW sleeping on watch during port approach',   'severity' => 'critical'],
                    ['flag' => 'No fatigue risk assessment or rest hour record review',                'severity' => 'critical'],
                    ['flag' => 'No reporting of rest hour non-compliance to company DPA',              'severity' => 'major'],
                    ['flag' => 'No language proficiency assessment for safety-critical crew',           'severity' => 'major'],
                    ['flag' => 'No BRM corrective measures for the compromised bridge team',           'severity' => 'major'],
                    ['flag' => 'No formal incident documentation and corrective action plan',           'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'STCW Code A-VIII/1 — Watchkeeping fitness for duty',
                    'MLC 2006 — Hours of rest requirements',
                    'ISM Code Section 6 — Resources and personnel',
                    'SOLAS Chapter V Regulation 14 — Manning requirements',
                    'Company SMS — BRM procedures and fatigue management',
                    'OCIMF SIRE/CDI VIQ — Vessel inspection questionnaire',
                    'TMSA — Tanker Management and Self Assessment (Element 8: Reliability and Maintenance Standards)',
                ],
                'red_flags_json' => [
                    'Ignoring OOW sleeping on watch and continuing port approach without watch relief',
                    'Falsifying or tolerating falsification of rest hour records',
                    'No fatigue risk mitigation despite multiple incidents',
                    'Continuing undermanned watches that violate STCW/MLC requirements',
                    'No corrective action before SIRE inspection — hoping to pass without addressing real issues',
                    'Blaming individual crew members without addressing systemic fatigue and communication issues',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 6 — AUTO_DEP — Autopilot/track control + alarm fatigue
            // ══════════════════════════════════════════════════════════════
            'CONTAINER_ULCS_S06_AUTO_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 20,000 TEU ULCS on passage through the Bay of Biscay in track control mode — the autopilot is automatically executing course changes at planned waypoints according to the approved ECDIS passage plan. During the 0400-0800 watch, the OOW notices that the vessel did not alter course at the planned waypoint 12 minutes ago. Upon checking the autopilot panel, the track control has reverted from "Track" mode to "Heading" mode — the vessel is maintaining the previous heading instead of following the planned route. The mode change occurred after a brief GPS signal interruption (logged at 0347), which triggered a track control alarm. However, the alarm was acknowledged by the previous watch (3/O) without investigation or action — the alarm acknowledgement is logged at 0349. The vessel is now 1.2 NM off the planned track and heading toward a precautionary area surrounding offshore oil/gas installations. CPA to the nearest platform is 2.5 NM and closing at current heading. Wind is 35 knots from the west, sea state 5, and the vessel is experiencing a 5-degree list to starboard from wind pressure on the container stacks.',
                        'your_position'       => 'Master, called to the bridge by the OOW who has just discovered the off-track situation.',
                        'available_resources' => 'ECDIS with passage plan and safety zones displayed, two ARPA radars, AIS, autopilot system (track control and heading modes), VHF for communication with offshore installations and MRCC, GPS (now restored and functioning), alarm management system with event log, 3/O available for questioning about the alarm acknowledgement.',
                        'current_conditions'  => 'Night, visibility moderate (5 NM in rain showers), wind W 35 knots, sea state 5, vessel speed 18 knots, 1.2 NM off planned track, CPA to nearest platform 2.5 NM closing, 5° starboard list.',
                    ],
                    'tr' => [
                        'situation'           => '20.000 TEU ULCS\'nin kaptanısınız, Biskay Körfezi\'nden track control modunda geçiş yapıyorsunuz — otopilot, onaylı ECDIS seyir planına göre planlı rota değişikliklerini otomatik gerçekleştiriyor. 0400-0800 vardiyasında VZ, geminin planlı waypoint\'te 12 dakika önce rota değiştirmediğini fark ediyor. Otopilot panelini kontrol ettiğinde track control "Track" modundan "Heading" moduna dönmüş — gemi planlı rotayı takip etmek yerine önceki rotayı sürdürüyor. Mod değişikliği kısa bir GPS sinyal kesilmesinden sonra (0347\'de kayıtlı) meydana gelmiş ve track control alarmı tetiklemiş. Ancak alarm, önceki vardiya (3. Zabit) tarafından araştırma veya eylem olmadan onaylanmış (0349\'da kayıtlı). Gemi artık planlı rotadan 1,2 mil saparak açık deniz petrol/gaz tesislerini çevreleyen bir önlem bölgesine doğru ilerliyor. En yakın platforma CPA 2,5 mil ve mevcut rotada yaklaşıyor. Rüzgar batıdan 35 knot, deniz durumu 5, konteyner yığınlarına rüzgar basıncından gemi sancağa 5 derece meyilli.',
                        'your_position'       => 'Kaptan, rotadan sapma durumunu keşfeden VZ tarafından köprüye çağrıldınız.',
                        'available_resources' => 'Seyir planı ve güvenlik bölgeleri gösterilen ECDIS, iki ARPA radar, AIS, otopilot sistemi (track ve heading modları), açık deniz tesisleri ve MRCC ile VHF, GPS (şimdi çalışıyor), olay kayıtlı alarm yönetim sistemi, alarm onayı hakkında sorgulamaya hazır 3. Zabit.',
                        'current_conditions'  => 'Gece, görüş orta (yağmurda 5 mil), rüzgar B 35 knot, deniz 5, hız 18 knot, planlı rotadan 1,2 mil sapma, en yakın platforma CPA 2,5 mil yaklaşıyor, 5° sancak meyil.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан ULCS 20 000 TEU, проход Бискайского залива в режиме следования по маршруту (track control). ВП обнаружил: судно не изменило курс в запланированной точке 12 минут назад. Автопилот переключился с режима «Track» на «Heading» после кратковременного сбоя GPS (03:47). Аларм был квитирован 3-м помощником (03:49) без расследования. Судно отклонилось на 1,2 мили к зоне нефтегазовых платформ. ДКРС до ближайшей — 2,5 мили, уменьшается. Ветер З 35 узлов, волнение 5 баллов, крен 5° на правый борт.',
                        'your_position'       => 'Капитан, вызван на мостик. ВП только что обнаружил отклонение.',
                        'available_resources' => 'ЭКНИС с маршрутом, два радара САРП, АИС, автопилот, УКВ (платформы и МСКЦ), GPS восстановлен, журнал тревог, 3-й помощник доступен.',
                        'current_conditions'  => 'Ночь, видимость 5 миль (дождь), ветер З 35 узлов, 18 узлов, отклонение 1,2 мили, ДКРС 2,5 мили — уменьш., крен 5° пр. борт.',
                    ],
                    'az' => [
                        'situation'           => '20.000 TEU ULCS-nin kapitanısınız, Biskay körfəzindən track control rejimində keçid edirsiniz. ВП gəminin 12 dəqiqə əvvəl planlı nöqtədə kurs dəyişmədiyini aşkar edib. Avtopilot GPS kəsilməsindən sonra (03:47) «Track»dan «Heading» rejiminə keçib. Alarm 3-cü stürman tərəfindən araşdırılmadan təsdiqlənib (03:49). Gəmi 1,2 mil sapmışdır, neft/qaz platformaları zonasına doğru irəliləyir. Ən yaxın platformaya ДКРС 2,5 mil, azalır.',
                        'your_position'       => 'Kapitan, ВП tərəfindən çağırılmısınız. Sapma yenicə aşkar edilib.',
                        'available_resources' => 'ECDIS, iki ARPA radar, AIS, avtopilot, VHF, GPS bərpa olunub, alarm jurnalı, 3-cü stürman mövcud.',
                        'current_conditions'  => 'Gecə, görmə 5 mil (yağış), Q külək 35 knot, 18 düyün, 1,2 mil sapma, ДКРС 2,5 mil azalır, 5° sancaq əyilmə.',
                    ],
                ],
                'decision_prompt'      => 'Describe your immediate actions and subsequent management of this situation. Address: (1) how you regain safe navigation and avoid the offshore installations, (2) how you investigate and address the alarm acknowledgement failure, (3) what changes you implement to prevent automation complacency, and (4) how you address the watch handover quality that allowed this to happen.',
                'decision_prompt_i18n' => [
                    'tr' => 'Acil eylemlerinizi ve durumun yönetimini açıklayın: (1) güvenli navigasyonu nasıl sağlarsınız ve açık deniz tesislerinden nasıl kaçınırsınız, (2) alarm onay hatasını nasıl araştırır ve ele alırsınız, (3) otomasyon kayıtsızlığını önlemek için hangi değişiklikleri uygularsınız, (4) buna izin veren vardiya devir kalitesini nasıl ele alırsınız.',
                    'ru' => 'Опишите немедленные действия: (1) как восстанавливаете безопасное плавание и избегаете платформ, (2) расследование квитирования аларма без действия, (3) меры против автоматизационного благодушия, (4) качество передачи вахты.',
                    'az' => 'Dərhal hərəkətlərinizi və vəziyyətin idarəsini təsvir edin: (1) təhlükəsiz naviqasiyanı necə bərpa edirsiniz, (2) alarm təsdiqi səhvini necə araşdırırsınız, (3) avtomatlaşdırma laqeydliyinin qarşısını necə alırsınız, (4) növbə təhvil keyfiyyətini necə həll edirsiniz.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'automation_awareness_and_override',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Does not immediately take manual control. Attempts to re-engage track control without first assessing the proximity to offshore installations. No urgency in the response despite closing CPA.',
                            '2' => 'Takes manual control but is slow to assess the situation. Does not immediately check the CPA to installations. No systematic approach to returning to the planned track safely.',
                            '3' => 'Immediately takes manual steering control, assesses CPA to the nearest platform (2.5 NM closing), alters course to open the CPA and return to the planned track. Checks ECDIS for safety zones and exclusion areas. Verifies GPS is now reliable before considering track control re-engagement.',
                            '4' => 'Swift and systematic response: immediately switches to manual steering, assesses CPA to all nearby platforms (not just the nearest), plots a safe return track to the passage plan avoiding all exclusion zones, reduces speed to allow more time for assessment, communicates with nearby installations on VHF if CPA is concerning, verifies all navigation sensors are functioning correctly before any automation re-engagement, and conducts a thorough check of the passage plan for the remainder of the Bay of Biscay transit.',
                            '5' => 'Expert automation override and recovery: immediate manual steering with specific course order to maximise CPA to installations, comprehensive assessment of all platforms and exclusion zones in the area using ECDIS overlay, speed reduction for safety margin, VHF communication with installation(s) and MRCC if entering any precautionary area, systematic verification of all navigation sensors (GPS, gyro, log, wind) before any automation re-engagement, development of criteria for track control re-engagement (minimum GPS signal quality, manual verification of position, officer confirmation of mode), implementation of mandatory automation mode verification checks at each waypoint, and documentation of the incident for fleet learning.',
                        ],
                    ],
                    [
                        'axis'   => 'alarm_management_and_culture',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Does not investigate the alarm acknowledgement failure. Treats it as a non-issue. No review of alarm management practices or the 3/O\'s actions.',
                            '2' => 'Questions the 3/O about the alarm but accepts vague explanations. No systematic review of the alarm culture or management system on the vessel.',
                            '3' => 'Investigates the 3/O\'s alarm acknowledgement: reviews the alarm log, questions the 3/O about why the alarm was acknowledged without action, identifies that acknowledging alarms without investigation is a dangerous practice, and establishes a clear policy that critical navigation alarms require investigation and action.',
                            '4' => 'Thorough alarm management review: formal investigation of the 3/O\'s failure to act on the alarm, review of the alarm log for patterns of acknowledge-without-action behaviour, assessment of whether alarm fatigue is a systemic problem (excessive false alarms reducing response quality), implements a categorised alarm response protocol (critical alarms require immediate investigation, log entry, and Master notification), provides training on automation mode awareness, and documents the investigation per ISM Code.',
                            '5' => 'Comprehensive alarm culture transformation: formal investigation with documented statement from 3/O, root cause analysis determining if this is individual failure or systemic alarm fatigue, review of alarm settings to reduce nuisance alarms (proper threshold settings), implementation of a tiered alarm response protocol aligned with bridge procedures (critical: immediate action + Master notification; important: investigation within defined time; advisory: acknowledge and monitor), training programme on the risks of alarm acknowledgement without action using real incidents (including this one), revision of the vessel\'s SMS bridge procedures to include specific guidance on automation mode change alarms, formal report to company for fleet-wide learning, and consideration of disciplinary action for the 3/O proportionate to the severity.',
                        ],
                    ],
                    [
                        'axis'   => 'passage_monitoring_discipline',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No assessment of how the vessel went 12 minutes and 1.2 NM off track without detection. No review of watch handover quality or OOW monitoring practices.',
                            '2' => 'Acknowledges the monitoring failure but attributes it to a single individual. Does not assess the systemic factors (watch handover, monitoring frequency, cross-track alarm settings).',
                            '3' => 'Assesses the monitoring failure: reviews the watch handover process between 3/O and current OOW, checks whether the cross-track distance alarm was set on ECDIS, implements a requirement for OOW to verify autopilot mode and track at each position check, and establishes a minimum position monitoring frequency.',
                            '4' => 'Comprehensive monitoring review: investigates watch handover quality (was the mode change mentioned in handover?), checks ECDIS alarm settings (cross-track distance limit, waypoint approach alarm), implements mandatory automation mode verification at watch handover and at defined intervals, establishes a position monitoring checklist that includes autopilot mode as a required check item, reviews passage plan for areas requiring enhanced monitoring (near installations, in TSS, approaching waypoints), and updates bridge standing orders.',
                            '5' => 'Expert passage monitoring overhaul: complete review of the failure chain — GPS interruption → mode change → alarm acknowledge without action → missed handover → 12-minute undetected deviation — identifying every breakpoint where the error could have been caught. Implements multiple layers of defence: ECDIS cross-track alarm set to appropriate limit, mandatory autopilot mode verification at handover (recorded in log), 6-minute position check cycle including autopilot status, ECDIS waypoint advance alarm verified operational, bridge standing orders updated with specific track control monitoring requirements, additional physical check of autopilot panel during hourly rounds, pre-passage planning to identify waypoints near hazards requiring enhanced monitoring, and a requirement that any automation mode change triggers a mandatory Master notification during the current transit.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No immediate switch to manual steering upon discovering off-track situation', 'severity' => 'critical'],
                    ['flag' => 'No assessment of CPA to offshore installations',                            'severity' => 'critical'],
                    ['flag' => 'No verification of track control mode at watch handover',                   'severity' => 'major'],
                    ['flag' => 'No alarm management review or investigation of 3/O actions',                'severity' => 'major'],
                    ['flag' => 'No notification to Master for off-track situation',                         'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter V Regulation 19 — Navigation systems and autopilot requirements',
                    'COLREG Rule 5 — Lookout (includes systematic observation of navigation equipment)',
                    'IMO MSC.1/Circ.1503 — ECDIS guidance for good practice',
                    'IMO Resolution A.694(17) — Autopilot performance standards',
                    'Company SMS — Automation procedures and bridge watchkeeping',
                    'STCW Code A-VIII/2 — Watchkeeping arrangements and principles',
                ],
                'red_flags_json' => [
                    'Not immediately taking manual control when vessel is heading toward offshore installations',
                    'Continuing on wrong heading toward platforms without assessing CPA',
                    'Acknowledging critical navigation alarms without investigation or action',
                    'No track monitoring despite track control mode engaged — over-reliance on automation',
                    'No Master notification for significant off-track deviation near hazards',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 7 — CRISIS_RSP — Container fire (lithium batteries / DG cargo)
            // ══════════════════════════════════════════════════════════════
            'CONTAINER_ULCS_S07_CRIS_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 14,000 TEU ULCS in the mid-Indian Ocean, approximately 800 NM from the nearest port. At 1430, the duty AB reports smoke rising from the forward cargo area around hold No. 3, Bay 26. The cargo manifest lists containers in Bay 26 as "household electronics and appliances," but the volume of smoke and the speed of temperature rise (hold temperature sensors show an increase from 35°C to 70°C in just 20 minutes) suggest a much more energetic fire — consistent with misdeclared lithium batteries undergoing thermal runaway. The fixed CO2 fire suppression system is available for hold No. 3. However, you are aware that CO2 may not extinguish lithium battery thermal runaway (which generates its own oxygen), though it will suppress secondary fires and reduce oxygen for other combustibles. Wind is 20 knots from the northwest, currently carrying smoke aft toward the accommodation block. Two containers of Class 1.4S (fireworks/pyrotechnics) are stowed in Bay 30, four bays aft of the fire origin. The nearest port with firefighting capability is Colombo (3 days steaming). No maritime firefighting vessels are available in the area. Your vessel carries standard SOLAS firefighting equipment but no external firefighting monitors rated for container fires.',
                        'your_position'       => 'Bridge, alerted by duty AB. C/O proceeding to investigate from a safe position on the accommodation deck.',
                        'available_resources' => 'Fixed CO2 system for cargo holds (manual release from CO2 room), fire detection and temperature monitoring system for all holds, portable fire extinguishers and fire hoses, breathing apparatus (12 sets), fire team (trained crew per muster list), DG manifest and stowage plan, IMDG Code with Emergency Schedules (EmS), GMDSS equipment (Inmarsat-C, MF/HF DSC, EPIRB, SARTs), company emergency response team (shore-based, 24/7), P&I club emergency line, AMVER system for vessel assistance.',
                        'current_conditions'  => 'Indian Ocean, position 05°N 075°E, wind NW 20 knots carrying smoke toward accommodation, seas 2m, visibility good except in smoke plume, vessel speed 18 knots, nearest port Colombo 800 NM (3 days), no rescue vessels available.',
                    ],
                    'tr' => [
                        'situation'           => '14.000 TEU ULCS\'nin kaptanısınız, Hint Okyanusu ortası, en yakın limana yaklaşık 800 deniz mili. 1430\'da nöbetçi güverte eri, 3 No\'lu ambar Bay 26 civarındaki ön kargo alanından yükselen duman bildiriyor. Kargo manifestinde Bay 26 konteynerleri "ev elektroniği ve aletleri" olarak listelenmiş, ancak dumanın hacmi ve sıcaklık artış hızı (ambar sensörleri 20 dakikada 35°C\'den 70°C\'ye çıkış gösteriyor) termal kaçışa uğrayan yanlış beyan edilmiş lityum pilleri düşündüren çok daha enerjik bir yangın işaret ediyor. 3 No\'lu ambar için sabit CO2 söndürme sistemi mevcut. Ancak CO2\'nin lityum pil termal kaçışını söndüremeyebileceğini biliyorsunuz (kendi oksijenini üretir), fakat ikincil yangınları bastırıp diğer yanıcılar için oksijeni azaltır. Rüzgar kuzeybatıdan 20 knot, dumanı yaşam mahalline doğru taşıyor. Bay 30\'da (yangın kaynağından 4 bay kıç) Sınıf 1.4S (havai fişek) iki konteyner istifleniş. En yakın itfaiye kabiliyetli liman Kolombo (3 gün mesafe). Bölgede deniz söndürme gemisi yok.',
                        'your_position'       => 'Köprüüstü, nöbetçi güverte eri tarafından uyarıldınız. Birinci Zabit yaşam mahalli güvertesinden güvenli konumdan araştırmaya gidiyor.',
                        'available_resources' => 'Kargo ambarları için sabit CO2 sistemi, yangın algılama ve sıcaklık izleme, portatif söndürücüler ve yangın hortumları, solunum cihazları (12 set), yangın ekibi, DG manifesti ve istifleme planı, EmS\'li IMDG Kodu, GMDSS ekipmanı, şirket acil müdahale ekibi (kara bazlı 7/24), P&I kulüp acil hattı, AMVER sistemi.',
                        'current_conditions'  => 'Hint Okyanusu, 05°K 075°D, rüzgar KB 20 knot dumanı yaşam mahalline taşıyor, deniz 2m, görüş duman dışında iyi, hız 18 knot, en yakın liman Kolombo 800 mil (3 gün), kurtarma gemisi yok.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан контейнеровоза 14 000 TEU, Индийский океан, 800 миль от ближайшего порта. Дежурный матрос обнаружил дым из района трюма №3, бэй 26. Манифест: «бытовая электроника», но скорость нагрева (35°→70°C за 20 минут) указывает на незадекларированные литиевые батареи. CO2 для трюма доступен, но может не потушить тепловой разгон лития. Ветер СЗ 20 узлов несёт дым на надстройку. В бэе 30 (4 бэя от огня) — 2 контейнера класса 1.4S (пиротехника). Ближайший порт с возможностями — Коломбо (3 дня).',
                        'your_position'       => 'Мостик, доклад дежурного. Старпом идёт оценить обстановку с безопасной позиции.',
                        'available_resources' => 'CO2 система, температурный мониторинг, пожарное оборудование, SCBA (12 комплектов), DG манифест, IMDG/EmS, ГМССБ, аварийная команда компании, P&I клуб, AMVER.',
                        'current_conditions'  => '05°N 075°E, ветер СЗ 20 узлов, волна 2 м, 18 узлов, Коломбо 800 миль, спасательных судов нет.',
                    ],
                    'az' => [
                        'situation'           => '14.000 TEU konteyner gəmisinin kapitanısınız, Hind okeanı, ən yaxın limandan 800 mil. Növbətçi matros 3 saylı ambar, bey 26-dan tüstü bildirir. Manifest: «məişət elektronikası», lakin istilik artımı sürəti (20 dəqiqədə 35°→70°C) bəyan edilməmiş litium batareyaları göstərir. CO2 mövcuddur, lakin litium termal qaçışını söndürə bilməyə bilər. Külək ŞQ 20 knot tüstünü yaşayış blokuna aparır. Bey 30-da (4 bey geridə) 2 Sinif 1.4S (pirotexnika) konteyner var. Ən yaxın port Kolombo (3 gün).',
                        'your_position'       => 'Körpüüstü, növbətçi tərəfindən xəbərdar edilmisiniz. Birinci stürman təhlükəsiz mövqedən araşdırmağa gedir.',
                        'available_resources' => 'CO2 sistemi, temperatur monitorinqi, yanğınsöndürmə avadanlığı, nəfəs aparatları (12), DG manifesti, IMDG/EmS, GMDSS, şirkət qəza komandası, P&I klubu, AMVER.',
                        'current_conditions'  => '05°N 075°E, ŞQ külək 20 knot, dalğa 2 m, 18 düyün, Kolombo 800 mil, xilasetmə gəmisi yoxdur.',
                    ],
                ],
                'decision_prompt'      => 'Describe your complete emergency response to this container fire. Address: (1) your immediate fire response and CO2 deployment decision, (2) how you assess and manage the risk from adjacent DG cargo (Class 1.4S), (3) your emergency communication plan (GMDSS, company, MRCC), (4) crew safety measures including smoke management, (5) your plan for sustained firefighting over potentially 3 days at sea, and (6) your criteria for escalating to distress/evacuation.',
                'decision_prompt_i18n' => [
                    'tr' => 'Bu konteyner yangınına eksiksiz acil müdahalenizi açıklayın: (1) acil yangın müdahalesi ve CO2 dağıtım kararı, (2) bitişik DG yükünden (Sınıf 1.4S) riski değerlendirme ve yönetme, (3) acil iletişim planı (GMDSS, şirket, MRCC), (4) duman yönetimi dahil mürettebat güvenliği, (5) denizde 3 güne kadar sürdürülebilir söndürme planı, (6) tehlike/tahliye yükseltme kriterleriniz.',
                    'ru' => 'Опишите полный план реагирования: (1) немедленные действия и решение о CO2, (2) оценка риска от соседнего DG груза (1.4S), (3) план аварийной связи (ГМССБ, компания, МСКЦ), (4) безопасность экипажа и управление дымом, (5) план длительного тушения (до 3 дней), (6) критерии эскалации до бедствия/эвакуации.',
                    'az' => 'Bu konteyner yanğınına tam təcili cavabınızı təsvir edin: (1) dərhal yanğın cavabı və CO2 qərarı, (2) bitişik DG yükündən (1.4S) risk qiymətləndirməsi, (3) təcili rabitə planı (GMDSS, şirkət, MRCC), (4) tüstü idarəsi daxil heyət təhlükəsizliyi, (5) dənizdə 3 günədək söndürmə planı, (6) təhlükə/evakuasiya eskalasiya meyarları.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'fire_response_and_containment',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'No activation of fire emergency plan. Delays CO2 deployment. Considers opening hold to investigate, feeding oxygen to the fire. No boundary cooling.',
                            '2' => 'Activates general alarm and musters fire teams but response is disorganized. CO2 deployment delayed or poorly justified. Boundary cooling mentioned but not systematically planned. Does not adjust heading for smoke management.',
                            '3' => 'Activates fire emergency plan promptly. Deploys CO2 to hold No. 3 with reasonable timing. Initiates boundary cooling on adjacent bulkheads and deck. Alters course to move smoke away from accommodation. May lack detailed monitoring plan or CO2 effectiveness assessment criteria.',
                            '4' => 'Swift and structured response: immediate fire alarm, fire teams mustered with BA, CO2 deployed after confirming hold sealed, boundary cooling on all adjacent surfaces including deck and bulkheads to Bays 24 and 28, course altered for smoke management, temperature monitoring established with escalation criteria. Acknowledges CO2 limitations with lithium battery fires (thermal runaway continues despite oxygen depletion).',
                            '5' => 'Exemplary fire response: immediate SMS fire plan activation, fire teams mustered and briefed (explicit instruction NOT to open hold), CO2 deployed after confirming all hold ventilation dampers and openings sealed, comprehensive boundary cooling plan for all adjacent holds and deck, course/speed adjusted for crew safety, continuous multi-point temperature monitoring with clear decision criteria (if temperature continues rising post-CO2 = containment failure), acknowledges CO2 may not stop lithium thermal runaway but suppresses secondary fires, plans for sustained firefighting over days including watch rotation, considers structural integrity under prolonged heat.',
                        ],
                    ],
                    [
                        'axis'   => 'dangerous_goods_awareness',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Does not consult DG manifest or IMDG Code. No awareness of Class 1.4S cargo proximity or misdeclaration implications. No EmS reference.',
                            '2' => 'Mentions DG manifest but does not systematically assess adjacent cargo risks. May reference IMDG Code but does not apply EmS specifically. Does not address the misdeclaration issue.',
                            '3' => 'Consults DG manifest, identifies Class 1.4S in Bay 30, references appropriate EmS for battery/electronics fire, recognizes misdeclaration as complicating factor, initiates boundary cooling toward Bay 30. May not fully assess heat propagation risk.',
                            '4' => 'Thorough DG assessment: reviews full manifest for all DG in holds 2, 3, and 4 and adjacent bays, identifies Class 1.4S and assesses heat propagation risk, applies correct EmS, establishes enhanced Bay 30 temperature monitoring, considers misdeclaration implications (actual contents unknown), reports to company for shipper notification.',
                            '5' => 'Comprehensive DG management: complete manifest review for entire cargo block, specific 1.4S compatibility assessment with fire scenario (requires fire stimulus — assess likelihood of heat reaching Bay 30), EmS for both suspected lithium and adjacent DG, enhanced monitoring with pre-set evacuation criteria, treats fire as potentially involving undeclared Class 9 lithium, applies most conservative EmS, reports to company and flag state, considers implications for other containers from same shipper.',
                        ],
                    ],
                    [
                        'axis'   => 'emergency_coordination_and_communication',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No GMDSS communication. No notification to company, MRCC, or flag state. No crew muster or evacuation readiness consideration.',
                            '2' => 'Notifies company by email but does not initiate GMDSS urgency. Crew muster mentioned but not structured. No MRCC communication plan.',
                            '3' => 'Initiates PAN PAN to nearest MRCC with position, nature of emergency, and assistance needed. Notifies company. Musters crew per emergency plan. May not establish structured communication schedule or address evacuation contingency.',
                            '4' => 'Comprehensive communications: PAN PAN with full SITREP to MRCC, scheduled updates, flag state notification, crew mustered with fire and standby teams, lifeboats prepared for contingency, VHF monitoring for nearby vessels.',
                            '5' => 'Full emergency coordination: PAN PAN (escalating to MAYDAY if needed) with complete SITREP including DG information, structured communication plan with MRCC/company/flag/P&I, crew fully mustered with clear roles, contingency evacuation prepared (lifeboats swung out, abandon criteria established), DSC alert configured, EPIRB/SARTs ready, request nearby vessels to stand by, coordination with shore emergency response team for technical firefighting advice and port of refuge negotiations.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No activation of ship\'s fire emergency plan per SMS',                          'severity' => 'critical'],
                    ['flag' => 'No consultation of IMDG Code or DG manifest for adjacent cargo',                'severity' => 'critical'],
                    ['flag' => 'No boundary cooling on bulkheads and deck adjacent to hold No. 3',              'severity' => 'critical'],
                    ['flag' => 'No assessment of risk to adjacent Class 1.4S cargo in Bay 30',                  'severity' => 'critical'],
                    ['flag' => 'No GMDSS distress or urgency communication to MRCC',                           'severity' => 'critical'],
                    ['flag' => 'No consideration of crew muster or contingency evacuation',                     'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter II-2 — Fire safety, detection and extinction',
                    'IMDG Code — Emergency Schedules (EmS) for fire and spillage',
                    'FSS Code — Fixed gas fire-extinguishing systems (CO2)',
                    'MSC.1/Circ.1515 — Guidance on containership fire safety',
                    'SOLAS Chapter IV — GMDSS radiocommunication',
                    'Company SMS — Fire emergency response procedures',
                    'STCW Code — Advanced fire fighting proficiency',
                ],
                'red_flags_json' => [
                    'Delaying fire response to investigate source or determine cargo contents',
                    'Not consulting DG manifest for adjacent cargo',
                    'Sending crew into smoke-filled hold without BA or proper authorization',
                    'No boundary cooling on adjacent holds especially toward Class 1.4 cargo',
                    'No emergency communication to MRCC',
                    'Opening hold for investigation — feeding oxygen to the fire',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 8 — TRADEOFF — Heavy weather vs schedule vs CII + hull stress
            // ══════════════════════════════════════════════════════════════
            'CONTAINER_ULCS_S08_TRADE_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of an 18,000 TEU ULCS on a westbound North Atlantic crossing. Your weather routing service recommends a 200 NM southern diversion to avoid a developing storm system (hurricane remnant undergoing extratropical transition), adding approximately 18 hours to the voyage. The charterer insists on the direct route — the vessel is already 12 hours behind schedule due to port delay, and the destination terminal berth window closes in 72 hours. The direct route forecast indicates sustained winds of 50-55 knots and seas of 8-10 metres, with significant risk of parametric rolling and bow slamming. The southern route would encounter winds of 25-30 knots and seas of 3-4 metres, adding fuel consumption but keeping the vessel within its annual CII boundary rating (C). Taking the direct route at reduced speed saves fuel overall but exposes the vessel to structural loading concerns. Critically, the hull stress monitoring system has flagged existing fatigue cracks at the hatch coaming of cargo hold No. 2, identified during the last intermediate survey, which the classification society is monitoring. The class requirement stipulates that if hull stress exceeds a defined threshold during the voyage, the vessel must report immediately and may need to proceed to the nearest port for inspection.',
                        'your_position'       => 'Master, reviewing the weather routing recommendation, charterer\'s message, hull stress data, and CII projections. You must communicate your routing decision to all parties.',
                        'available_resources' => 'Weather routing service (24/7 meteorological support), hull stress monitoring system (real-time data including hold No. 2), ECDIS with multiple route options, stability computer, CII tracking software, classification society surveyor (by phone), company fleet operations (24/7), charterer operations desk, P&I club, Chief Officer and Chief Engineer for consultation.',
                        'current_conditions'  => 'Position 45°N 035°W, wind W 30 knots, seas 4m, swell WNW 3m. Storm forecast to cross direct route in 18-24 hours. Speed 19 knots. CII rating: C (borderline B/C). Hull stress at hold No. 2: 65% of reporting threshold in current conditions. Cargo: 16,200 TEU including 1,800 reefers. GM 1.8m — elevated parametric rolling susceptibility.',
                    ],
                    'tr' => [
                        'situation'           => '18.000 TEU ULCS\'nin kaptanısınız, Kuzey Atlantik\'te batıya geçiş yapıyorsunuz. Hava rotalama servisiniz gelişen fırtınadan kaçınmak için 200 mil güneye sapma öneriyor (+18 saat). Kiracı doğrudan rotada ısrar ediyor — gemi 12 saat geride, varış terminali rıhtım penceresi 72 saatte kapanıyor. Doğrudan rota: rüzgar 50-55 knot, dalga 8-10m, parametrik yalpa ve baş vurma riski. Güney rota: 25-30 knot, 3-4m dalga, CII C derecesi korunur. Kritik: gövde gerilim izleme sistemi, son ara sörveyde tespit edilen ve sınıflandırma kuruluşunca izlenen No. 2 ambar kapak kenarındaki yorgunluk çatlaklarını işaretlemiş. Sınıf şartı: gerilim eşiği aşılırsa derhal rapor ve muhtemelen en yakın limana denetim.',
                        'your_position'       => 'Kaptan, hava rotalama önerisi, kiracı mesajı, gövde gerilim verileri ve CII projeksiyonlarını inceliyorsunuz. Rotalama kararınızı tüm taraflara bildirmelisiniz.',
                        'available_resources' => 'Hava rotalama (7/24), gövde gerilim sistemi (No. 2 ambar dahil), çoklu rota seçenekli ECDIS, stabilite bilgisayarı, CII yazılımı, sınıflandırma sörveyörü (telefonda), şirket filo ops (7/24), kiracı, P&I kulüp, Birinci Zabit ve Başmühendis.',
                        'current_conditions'  => '45°K 035°B, rüzgar B 30 knot, dalga 4m. Fırtına 18-24 saatte doğrudan rotadan geçecek. Hız 19 knot. CII: C (B/C sınırı). No. 2 ambar gerilimi: eşiğin %65\'i. Yük: 16.200 TEU (1.800 reefer). GM 1,8m — yüksek parametrik yalpa hassasiyeti.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан ULCS 18 000 TEU, западный переход Северной Атлантики. Метеослужба рекомендует 200-мильный обход штормовой системы к югу (+18 часов). Фрахтователь настаивает на прямом маршруте — судно отстаёт на 12 часов, причальное окно через 72 часа. Прямой маршрут: ветер 50-55 узлов, волна 8-10 м, риск параметрической качки. Южный: 25-30 узлов, 3-4 м, CII сохраняется. Система мониторинга корпуса: усталостные трещины на комингсе трюма №2, контролируемые классом. При превышении порога — обязательный доклад и возможный заход.',
                        'your_position'       => 'Капитан, анализируете рекомендации метеослужбы, позицию фрахтователя, данные о напряжениях корпуса и CII. Нужно сообщить решение всем сторонам.',
                        'available_resources' => 'Метеослужба 24/7, мониторинг корпуса (трюм №2), ЭКНИС, компьютер остойчивости, CII-трекинг, сюрвейер класса (телефон), компания 24/7, фрахтователь, P&I клуб, старпом и стармех.',
                        'current_conditions'  => '45°N 035°W, ветер З 30, волна 4 м. Шторм через 18-24 часа. 19 узлов. CII: C (граница B/C). Напряжения трюма №2: 65% порога. Груз: 16 200 TEU (1 800 рефр.). GM 1,8 м.',
                    ],
                    'az' => [
                        'situation'           => '18.000 TEU ULCS-nin kapitanısınız, Şimali Atlantikada qərbə keçid. Hava xidməti fırtınadan qaçmaq üçün 200 mil cənuba sapmanı tövsiyə edir (+18 saat). Fraxtçı birbaşa marşrutda israr edir — gəmi 12 saat geridədir, rıhtım pəncərəsi 72 saatda bağlanır. Birbaşa: külək 50-55 knot, dalğa 8-10 m, parametrik yırğalanma riski. Cənub: 25-30 knot, 3-4 m, CII C saxlanır. Gövdə gərginlik sistemi: ambar №2 lüku kənarında yorğunluq çatları, sinif tərəfindən izlənir. Hədd aşılarsa dərhal hesabat və ən yaxın limana mümkün yönləndirmə.',
                        'your_position'       => 'Kapitan, hava tövsiyəsi, fraxtçı mesajı, gövdə gərginliyi və CII proqnozlarını nəzərdən keçirirsiniz.',
                        'available_resources' => 'Hava xidməti 24/7, gövdə monitorinqi (ambar №2), ECDIS, stabillik kompüteri, CII proqramı, sinif sörveçisi (telefon), şirkət 24/7, fraxtçı, P&I, birinci stürman, baş mühəndis.',
                        'current_conditions'  => '45°N 035°W, Q külək 30, dalğa 4 m. Fırtına 18-24 saatda. 19 düyün. CII: C. Ambar №2 gərginliyi: 65%. Yük: 16.200 TEU (1.800 soyuducu). GM 1,8 m.',
                    ],
                ],
                'decision_prompt'      => 'Present your routing decision with comprehensive justification. Address: (1) weather routing analysis for both route options, (2) hull structural integrity given existing fatigue cracks and projected stress, (3) parametric rolling risk evaluation, (4) CII compliance implications, (5) communication plan with charterer, company, and classification society, and (6) how you balance commercial pressure against safety under ISM Code Master\'s overriding authority.',
                'decision_prompt_i18n' => [
                    'tr' => 'Rotalama kararınızı kapsamlı gerekçesiyle sunun: (1) her iki rota için hava analizi, (2) mevcut çatlaklar ve öngörülen gerilim düzeyleri ile gövde yapısal bütünlüğü, (3) parametrik yalpa riski, (4) CII uyumluluk etkileri, (5) kiracı/şirket/sınıflandırma kuruluşu ile iletişim planı, (6) ISM Kodu Kaptan yetkisi kapsamında ticari baskı ile güvenlik dengesi.',
                    'ru' => 'Представьте решение по маршруту: (1) метеоанализ обоих вариантов, (2) прочность корпуса с учётом трещин, (3) параметрическая качка, (4) CII, (5) коммуникации с фрахтователем/компанией/классом, (6) баланс коммерческого давления и безопасности по ISM Code.',
                    'az' => 'Marşrut qərarınızı əsaslandırma ilə təqdim edin: (1) hər iki marşrut üçün hava analizi, (2) mövcud çatlar və proqnozlaşdırılan gərginliklə gövdə bütövlüyü, (3) parametrik yırğalanma riski, (4) CII uyğunluğu, (5) fraxtçı/şirkət/sinif ilə ünsiyyət planı, (6) ISM üzrə kommersiya ilə təhlükəsizlik balansı.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'weather_routing_and_safety_decision',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Takes direct route through storm without risk analysis. Dismisses weather routing advice. No parametric rolling or heavy weather consideration. Prioritizes schedule over safety.',
                            '2' => 'Acknowledges risk but proposes a compromise route without proper analysis. Vague heavy weather preparations. Does not assess parametric rolling risk given GM 1.8m.',
                            '3' => 'Decides to divert south based on weather routing advice. Reasonable justification referencing forecast conditions. Considers parametric rolling risk. Prepares heavy weather plans. May lack detailed forecast uncertainty analysis.',
                            '4' => 'Well-reasoned diversion with thorough analysis: compares both routes in detail, specifically assesses parametric rolling (GM 1.8m increases susceptibility), considers forecast uncertainty and storm evolution, plans optimal diversion track, prepares detailed heavy weather procedures even for diversion route.',
                            '5' => 'Exemplary decision: comprehensive meteorological analysis including storm track probability, specific parametric rolling assessment (GM 1.8m with 18,000 TEU creates significant risk in 8-10m seas), speed/heading optimization for diversion, contingency routes if storm track shifts, detailed standing orders, coordination with routing service for continued monitoring, all documented in Master\'s voyage report.',
                        ],
                    ],
                    [
                        'axis'   => 'structural_risk_assessment',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No consideration of hull stress or existing fatigue cracks. Ignores classification society reporting requirements. Disregards structural risk entirely.',
                            '2' => 'Mentions cracks but does not integrate into routing decision. No class consultation. No stress projection for either route.',
                            '3' => 'Considers fatigue cracks at hold No. 2 as a routing factor. Notes current stress at 65% of threshold. Recognizes heavy weather would increase stress. Plans to monitor. May not project stress for each route or consult class.',
                            '4' => 'Full structural integration: current 65% at 4m seas means 8-10m would very likely exceed threshold, triggering mandatory class notification and possible diversion anyway. Proactive class consultation. Enhanced monitoring regardless of route. Considers stress implications for cargo securing.',
                            '5' => 'Comprehensive assessment: detailed stress projections for both routes (65% at 4m, scaling for 8-10m shows probable exceedance), proactive class consultation with full disclosure, enhanced monitoring protocol with speed reduction criteria, fatigue crack propagation risk assessment under cyclic loading, cargo securing inspection near compromised area, documentation in Master\'s report, understanding that threshold exceedance would mandate a class-required port call causing greater delay than the planned diversion.',
                        ],
                    ],
                    [
                        'axis'   => 'commercial_vs_safety_tradeoff_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Yields to charterer and takes direct route without documenting safety concerns. No formal communication. No CII assessment.',
                            '2' => 'Acknowledges pressure but decision is ambiguous. Communication incomplete. CII mentioned but not analyzed.',
                            '3' => 'Safety-based routing decision communicated to charterer. Invokes Master\'s authority if needed. Calculates CII impact. Notifies company. Documentation may be incomplete.',
                            '4' => 'Clear safety-first with professional commercial management: formal written notification to charterer with safety justification, company informed with full analysis, CII calculations showing southern route maintains C rating, Master\'s authority documented in log, all communications recorded, laytime implications communicated.',
                            '5' => 'Exemplary: formal deviation notification with detailed safety justification (weather, structural, parametric rolling), company notified with full analysis, CII calculated for multiple scenarios, Master\'s authority formally invoked and documented per ISM Code, all communications in writing and logged, P&I proactively notified, alternative arrival windows assessed, speed optimization on diversion to recover time safely, sea protest prepared if needed, understanding that a hull-stress-triggered class diversion would cost far more than the planned weather diversion.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No weather routing analysis or comparison of route options',                     'severity' => 'critical'],
                    ['flag' => 'No hull stress assessment considering existing fatigue cracks at hold No. 2',    'severity' => 'critical'],
                    ['flag' => 'No parametric rolling risk evaluation given GM 1.8m and forecast conditions',    'severity' => 'major'],
                    ['flag' => 'No consultation with classification society regarding existing cracks',           'severity' => 'major'],
                    ['flag' => 'No formal notification to charterer documenting deviation and safety basis',     'severity' => 'major'],
                    ['flag' => 'No CII impact calculation for either route option',                             'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter V Regulation 34 — Safe navigation and voyage planning',
                    'ISM Code Section 5 — Master\'s responsibility and overriding authority',
                    'MARPOL Annex VI — Carbon Intensity Indicator (CII) regulations',
                    'IACS Unified Requirement S11 — Hull monitoring systems',
                    'Classification Society rules — Hull structural integrity and reporting thresholds',
                    'Company SMS — Heavy weather procedures and voyage planning',
                    'WMO Guide to Marine Meteorological Services — Weather routing guidance',
                ],
                'red_flags_json' => [
                    'Taking direct route through storm with known hull fatigue cracks at hold No. 2',
                    'Ignoring weather routing recommendations without documented justification',
                    'No parametric rolling assessment despite high GM in 8-10m beam/quartering seas',
                    'Not reporting hull stress exceedance to classification society as required',
                    'No documentation of deviation decision or Master\'s authority invocation',
                    'Prioritizing berth window schedule over vessel structural safety and crew welfare',
                ],
            ],

        ];
    }
}
