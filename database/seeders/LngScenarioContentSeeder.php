<?php

namespace Database\Seeders;

use App\Models\MaritimeScenario;
use Illuminate\Database\Seeder;

/**
 * Populate LNG scenarios with production-quality content.
 *
 * Idempotent: updates existing rows by scenario_code.
 * Run: php82 artisan db:seed --class=LngScenarioContentSeeder --force
 */
class LngScenarioContentSeeder extends Seeder
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

        $activated = MaritimeScenario::where('command_class', 'LNG')
            ->where('version', 'v2')
            ->update(['is_active' => true]);

        $this->command->info("LNG scenario content seeded and activated ({$activated} scenarios).");
    }

    private function getScenariosSlot1to4(): array
    {
return [

    // ══════════════════════════════════════════════════════════════
    // SLOT 1 — NAV_COMPLEX — TSS + pilotage + LNGC maneuvering
    // ══════════════════════════════════════════════════════════════
    'LNG_S01_NAV_001' => [
        'briefing_json' => [
            'en' => [
                'situation'           => 'You are Master of a 290m Q-Max LNG carrier (loaded, 174,000 CBM, draft 12.2m) transiting the Malacca Strait Traffic Separation Scheme southbound toward the SLNG terminal via the Johor Strait approach. Your passage plan identifies a critical section at Phillip Channel where the charted depth is 13.5m — giving a static under-keel clearance (UKC) of only 1.3m before accounting for squat, tidal height, and vessel motion allowances. Your ETO has calculated that at 12 knots in the shallow section, squat effect will be approximately 0.8m (block coefficient 0.78), reducing effective UKC to 0.5m plus tidal contribution. The tidal window at Phillip Channel is 45 minutes — you must transit between HW-30 and HW+15 to maintain the minimum company-required UKC of 1.0m. ECDIS shows the closest approach to the 10m contour at 0.3 cable (55m) on the port side. Two loaded VLCCs are northbound in the opposing traffic lane, one overtaking the other. A beam wind of 18 knots from the NE is creating a significant leeway effect on your high-freeboard LNGC (air draft 52m). Singapore VTIS has confirmed your transit slot but reports heavy traffic density with 14 vessels in the Phillip Channel sector. The pilot boarding station is at the eastern approach — weather at the pilot station shows NE swell 1.5m with intermittent rain reducing visibility to 2 NM. The pilot boat has signaled marginal conditions for boarding and requests you reduce to 6 knots for the lee.',
                'your_position'       => 'Bridge, command. C/O as navigating officer on ECDIS, 2/O on radar/AIS plotting, helmsman in manual steering, lookout posted.',
                'available_resources'  => 'ECDIS (dual system) with approved ENC cells and company danger/caution overlays, two X-band ARPA radars, AIS, echo sounder (dual transducer), Doppler speed log, VHF Ch 16/Ch 14 (Singapore VTIS), company passage plan with UKC calculations and squat tables, SIGTTO passage planning guide, tide tables and real-time tide gauge data from MPA Singapore, engine room on standby with full maneuvering capability, bow thruster (limited effectiveness at speed), rudder tested satisfactory.',
                'current_conditions'   => 'Wind NE 18 knots (beam), visibility 2 NM in rain (improving to 6 NM in clear patches), tidal stream setting WNW 1.5 knots (flood), HW Phillip Channel in 40 minutes, loaded VLCC traffic northbound, Singapore VTIS monitoring.',
            ],
            'tr' => [
                'situation'           => 'Malaka Boğazı Trafik Ayırım Düzeni\'nden güneye doğru Johor Boğazı yaklaşımıyla SLNG terminaline transit geçiş yapan 290m Q-Max LNG gemisinin (yüklü, 174.000 CBM, su çekimi 12,2m) kaptanısınız. Seyir planınız Phillip Kanalı\'nda kritik bir kesim belirlemiştir: haritadaki derinlik 13,5m olup, squat, gelgit yüksekliği ve gemi hareket payları hesaba katılmadan statik omurga altı açıklığı (UKC) sadece 1,3m\'dir. ETO\'nuz sığ kesimde 12 knot hızla squat etkisinin yaklaşık 0,8m olacağını hesaplamıştır (blok katsayısı 0,78), bu da efektif UKC\'yi gelgit katkısı öncesi 0,5m\'ye düşürmektedir. Phillip Kanalı\'ndaki gelgit penceresi 45 dakikadır — şirketin minimum 1,0m UKC gereksinimini korumak için YS-30 ile YS+15 arasında transit geçiş yapmalısınız. ECDIS, iskele tarafında 10m eş derinlik çizgisine en yakın yaklaşımı 0,3 kablo (55m) olarak göstermektedir. Karşı trafik şeridinde iki yüklü VLCC kuzey yönlü seyrediyor, biri diğerini sollama yapıyor. KB\'den 18 knotluk bort rüzgarı yüksek freeboard\'lu LNGC\'niz (hava çekimi 52m) üzerinde belirgin bir leeway etkisi yaratmaktadır. Singapur VTIS transit slotunuzu onaylamış ancak Phillip Kanalı sektöründe 14 gemiyle yoğun trafik bildirmiştir. Kılavuz kaptan alma istasyonu doğu yaklaşımında olup, istasyondaki hava koşulları 1,5m KB dalgası ve görüşü 2 mile düşüren aralıklı yağmur göstermektedir. Kılavuz botu marjinal koşullar bildirmiş ve rüzgaraltı oluşturmanız için 6 knota düşmenizi talep etmiştir.',
                'your_position'       => 'Köprüüstü, komuta. Birinci Zabit ECDIS\'te seyrüsefer zabiti, İkinci Zabit radar/AIS plotlama, dümenci manuel dümen, gözcü postaya yerleştirilmiş.',
                'available_resources'  => 'Çift ECDIS (onaylı ENC hücreleri ve şirket tehlike/dikkat katmanları), iki X-band ARPA radar, AIS, iskandil (çift sensör), Doppler hız kütüğü, VHF Kanal 16/14 (Singapur VTIS), UKC hesaplamalı ve squat tablolu şirket seyir planı, SIGTTO seyir planlama kılavuzu, gelgit tabloları ve MPA Singapur gerçek zamanlı gelgit verileri, tam manevra kabiliyetli makine dairesi hazır, baş itici (hızda sınırlı etkinlik), dümen test edilmiş.',
                'current_conditions'   => 'Rüzgar KB 18 knot (bort), görüş yağmurda 2 mil (açık bölgelerde 6 mile iyileşen), 1,5 knot BKB gelgit akıntısı (med), Phillip Kanalı YS 40 dakika içinde, kuzey yönlü yüklü VLCC trafiği, Singapur VTIS izlemede.',
            ],
            'ru' => [
                'situation'           => 'Вы капитан 290-метрового Q-Max СПГ-танкера (загружен, осадка 12,2 м), транзит через ССР Малаккского пролива к терминалу SLNG. Критический участок — Phillip Channel: глубина 13,5 м, статический ЗПК 1,3 м. Squat на 12 узлах ~0,8 м (Cb 0,78). Приливное окно 45 минут. ЭКНИС показывает сближение с 10-м изобатой до 0,3 кбт (55 м). Два гружёных VLCC идут встречным курсом. Боковой ветер NE 18 узлов создаёт значительный дрейф. VTIS Сингапура подтвердил слот, 14 судов в секторе. Лоцманский бот сообщает о маргинальных условиях посадки.',
                'your_position'       => 'Мостик, командование. Старпом на ЭКНИС, 2-й помощник на радаре/АИС, рулевой на ручном, вперёдсмотрящий на посту.',
                'available_resources'  => 'Двойная ЭКНИС, два ARPA, АИС, двойной эхолот, доплеровский лаг, УКВ (VTIS), план перехода с расчётами ЗПК и squat, таблицы приливов, машинное на standby, носовое подруливающее.',
                'current_conditions'   => 'Ветер NE 18, видимость 2 мили (дождь), приливное течение 1,5 узла, ПВ через 40 мин, VLCC встречные, VTIS активен.',
            ],
            'az' => [
                'situation'           => 'Malakka boğazı TSS-dən cənuba SLNG terminalına keçən 290 m Q-Max LNG gəmisinin (yüklü, çəki 12,2 m) kapitanısınız. Phillip Channel-da dərinlik 13,5 m, statik UKC cəmi 1,3 m. 12 knot sürətdə squat ~0,8 m. Gelgit pəncərəsi 45 dəqiqə. ECDIS 10 m izobata 0,3 kabel yaxınlaşma göstərir. Qarşı zolaqda 2 yüklü VLCC. NE-dən 18 knot bort küləyi yüksək fribordlu gəmidə əhəmiyyətli sürüşmə yaradır. Sinqapur VTIS sektorda 14 gəmi bildirb. Losman qayığı marjinal şərait bildirmişdir.',
                'your_position'       => 'Körpüüstü, komanda. Birinci stürman ECDIS-də, ikinci stürman radar/AIS-də, sükandar əl idarəsində.',
                'available_resources'  => 'İkili ECDIS, iki ARPA, AIS, ikili exolot, Doppler laq, VHF (VTIS), keçid planı, gelgit cədvəlləri, maşın hazır.',
                'current_conditions'   => 'Külək NE 18, görmə 2 mil, gelgit axını 1,5 knot, ÜS 40 dəq-yə, VLCC trafiki, VTIS aktiv.',
            ],
        ],
        'decision_prompt'      => 'Describe your passage plan execution for transiting Phillip Channel with critical UKC constraints. Address your speed/squat management strategy, tidal window compliance, traffic management with VLCCs in the opposing lane, wind/leeway compensation on the LNGC, pilot boarding arrangements in marginal weather, and your contingency plans including abort criteria.',
        'decision_prompt_i18n' => [
            'tr' => 'Kritik UKC kısıtlamalarıyla Phillip Kanalı geçişi için seyir planı uygulamanızı açıklayın. Hız/squat yönetim stratejinizi, gelgit penceresi uyumunu, karşı şeritteki VLCC\'lerle trafik yönetimini, LNGC üzerindeki rüzgar/leeway telafisini, marjinal havada kılavuz alma düzenlemelerini ve iptal kriterleri dahil acil durum planlarınızı ele alın.',
            'ru' => 'Опишите выполнение плана перехода через Phillip Channel с критическими ограничениями ЗПК. Рассмотрите: управление скоростью/squat, соблюдение приливного окна, управление трафиком с VLCC, компенсация дрейфа от ветра, посадка лоцмана в маргинальных условиях и план действий на случай непредвиденных ситуаций.',
            'az' => 'Kritik UKC məhdudiyyətləri ilə Phillip Channel keçidini təsvir edin: sürət/squat idarəsi, gelgit pəncərəsi, VLCC trafik idarəsi, külək/sürüşmə kompensasiyası, marjinal havada losman qəbulu və ehtiyat planları.',
        ],
        'evaluation_axes_json' => [
            [
                'axis'   => 'passage_planning_ukc',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'No UKC calculation or awareness of squat effect. Proceeds at full speed through Phillip Channel without considering depth constraints. No reference to tidal window or company UKC policy.',
                    '2' => 'Basic awareness of shallow water but UKC calculation incomplete. Does not account for squat at planned speed, or uses incorrect block coefficient. Tidal window acknowledged but no precise timing planned.',
                    '3' => 'Correct UKC calculation: identifies 13.5m charted depth minus 12.2m draft = 1.3m static UKC, calculates squat at 12 knots (~0.8m for Cb 0.78), recognises residual UKC insufficient without tidal contribution, plans transit within the 45-minute tidal window to maintain company minimum 1.0m UKC, considers reducing speed in the critical section to reduce squat.',
                    '4' => 'Thorough UKC management: comprehensive squat calculation using Barrass or Huuska formula for confined channel (accounting for channel width-to-beam ratio), calculates dynamic UKC including squat, vessel motion (heave/pitch in swell), tidal height with safety margin, and water density. Plans speed reduction to 8-10 knots in the critical section to reduce squat to 0.4-0.5m. Monitors real-time tide gauge data from MPA to confirm tidal predictions. Identifies the specific waypoints where UKC is most critical and plans echo sounder monitoring at increased frequency. Confirms dual echo sounder operational with alarm set at minimum safe depth.',
                    '5' => 'Expert UKC and passage planning: uses multiple squat formulae (Barrass for open water sections, Huuska/Eryuzlu for canal-effect sections) with block coefficient 0.78, calculates UKC budget with all allowances (squat, vessel motions in 1.5m swell, heel due to beam wind on LNG carrier, water density, chart datum accuracy, echo sounder transducer depth). Plans graduated speed reduction approaching Phillip Channel to minimise squat while maintaining steerage. Cross-references real-time MPA tide data against predicted tides with documented margin. Identifies that 0.3 cable proximity to 10m contour with beam wind leeway creates risk of grounding on port side — plans track offset to starboard within TSS lane. Establishes abort point before entering the critical section with defined turnaround or anchor options. Considers interaction effects when passing VLCCs in adjacent lane (drawdown effect on UKC). Documents entire UKC analysis as part of the Master/Pilot exchange.',
                ],
            ],
            [
                'axis'   => 'traffic_management_tss',
                'weight' => 0.25,
                'rubric_levels' => [
                    '1' => 'No awareness of TSS rules or traffic management. Does not plot VLCCs or assess CPA/TCPA. No contact with Singapore VTIS regarding traffic density.',
                    '2' => 'Monitors VLCCs on radar but does not assess interaction risks. Relies solely on VTIS for traffic management without own assessment. Does not consider the vessel interaction effects in confined waters.',
                    '3' => 'Plots VLCCs on ARPA and AIS, assesses CPA/TCPA, communicates with VTIS regarding traffic coordination in Phillip Channel sector. Recognises that passing loaded VLCCs in confined channel creates hydrodynamic interaction risk (suction/repulsion). Maintains proper track within TSS lane.',
                    '4' => 'Active traffic management: continuous ARPA/AIS plotting of all 14 vessels in sector, identifies the two VLCCs (one overtaking) as primary concern for the Phillip Channel transit, coordinates timing with VTIS to avoid meeting VLCCs at the narrowest/shallowest section, assesses bank effect and vessel interaction forces for LNG carrier passing VLCCs in confined channel, plans to reduce speed during passing to minimise interaction effects, maintains enhanced bridge team lookout during the critical section.',
                    '5' => 'Expert traffic management in TSS: comprehensive traffic assessment integrating ARPA, AIS, and VTIS information, identifies optimal transit timing to avoid VLCC meeting at Phillip Channel critical section, requests VTIS assistance with traffic sequencing if necessary, calculates vessel interaction effects (passing distance, speed differential, bank suction in shallow water) for Q-Max meeting VLCCs, plans specific helm orders and speed adjustments for each anticipated passing situation, considers that beam wind on high-freeboard LNGC makes maintaining track in TSS more demanding during vessel interactions, establishes a traffic-based abort criterion (if meeting cannot be avoided at critical section, will request VTIS permission to delay transit), ensures all bridge team members have clear assignments for monitoring during the high-traffic transit.',
                ],
            ],
            [
                'axis'   => 'pilotage_bridge_team',
                'weight' => 0.25,
                'rubric_levels' => [
                    '1' => 'No preparation for pilot boarding in marginal conditions. No Master/Pilot exchange planned. Treats pilot arrival as routine without considering weather limitations.',
                    '2' => 'Prepares for pilot boarding but does not address marginal weather conditions. Master/Pilot exchange cursory without discussing UKC, squat, or wind effects specific to the LNGC.',
                    '3' => 'Plans pilot boarding with speed reduction to 6 knots to create lee for pilot boat. Prepares comprehensive Master/Pilot exchange covering UKC calculations, squat management, tidal window, traffic situation, and wind effects on the LNGC. Confirms pilot is experienced with LNG carriers in this waterway.',
                    '4' => 'Thorough pilot boarding and exchange: plans approach to pilot station with weather contingency (if boarding not possible, identifies safe waiting area), reduces to 6 knots with heading adjusted to provide maximum lee, ensures pilot hoist and deck crew ready with safety briefing, prepares detailed Master/Pilot exchange card with vessel-specific data (290m LOA, 12.2m draft, squat characteristics, UKC calculations, wind sensitivity, stopping distance at various speeds), discusses critical waypoints and abort criteria with pilot, confirms bridge team roles with pilot integrated into the team.',
                    '5' => 'Expert pilotage management: comprehensive pilot boarding plan with primary and alternate boarding positions, speed and heading adjusted for optimal lee considering NE wind and swell, boarding equipment inspected and safety net rigged, Master/Pilot exchange includes vessel-specific LNGC characteristics (high windage profile, restricted maneuverability due to deep draft and length, engine response times, bow thruster limitations at speed), shares passage plan with pilot including UKC analysis and tidal window constraints, discusses abort scenarios and emergency anchoring procedures specific to the Strait, establishes clear bridge team protocol with pilot (who gives helm orders, communication of wheel-over points, monitoring assignments), confirms pilot\'s familiarity with Q-Max dimensions and handling, documents the Master/Pilot exchange as per SOLAS V requirements.',
                ],
            ],
            [
                'axis'   => 'contingency_planning',
                'weight' => 0.20,
                'rubric_levels' => [
                    '1' => 'No contingency plans for the transit. No abort criteria defined. No consideration of emergency anchoring, engine failure, or steering failure scenarios.',
                    '2' => 'Vague contingency planning — "we will anchor if needed" without identifying suitable anchorage areas for a 290m vessel or considering the specific risks.',
                    '3' => 'Identifies key contingency scenarios: engine failure in Phillip Channel (emergency anchoring plan), missed tidal window (waiting area identified), pilot unable to board (alternative plan), and defines abort criteria before entering the critical section.',
                    '4' => 'Comprehensive contingency planning: abort point defined before Phillip Channel commitment point with specific criteria (UKC margin, traffic density, visibility, engine status), emergency anchorage positions identified on ECDIS suitable for 290m LNGC draft, steering failure procedure reviewed (emergency steering tested), engine failure plan includes drift assessment and emergency anchor deployment timing, missed tidal window plan with safe waiting area clear of TSS, communication plan with VTIS for each contingency.',
                    '5' => 'Expert contingency management: layered contingency plan covering all credible failure modes — propulsion loss (drift assessment including wind/current on LNGC windage area, emergency anchor deployment at specific waypoints with adequate depth and holding ground), steering failure (transfer to emergency steering with time estimate, pre-planned emergency heading), missed tidal window (safe anchorage or drift-off area, revised ETA to terminal), pilot boarding failure (safe holding pattern, alternate pilot station), traffic conflict at critical section (speed adjustment or delay with VTIS coordination), UKC alarm during transit (immediate speed reduction protocol, abort to designated safe water). Each contingency has a defined trigger point, pre-planned actions, and communication protocol. Crew briefed on contingency actions. Emergency equipment pre-positioned (anchors ready for immediate deployment through the critical section). Terminal notified of potential delay scenarios.',
                ],
            ],
        ],
        'critical_omission_flags_json' => [
            ['flag' => 'No squat calculation or dynamic UKC assessment for the shallow section',       'severity' => 'critical'],
            ['flag' => 'No tidal window planning for Phillip Channel transit',                         'severity' => 'critical'],
            ['flag' => 'No traffic management plan for VLCCs in opposing TSS lane',                    'severity' => 'major'],
            ['flag' => 'No consideration of beam wind leeway on LNGC near shoal water',               'severity' => 'major'],
            ['flag' => 'No abort criteria or contingency plan before entering critical section',        'severity' => 'critical'],
            ['flag' => 'No Master/Pilot exchange addressing LNGC-specific handling characteristics',   'severity' => 'major'],
        ],
        'expected_references_json' => [
            'SOLAS Chapter V — Safety of navigation, passage planning requirements',
            'Singapore Port Marine Circular — Regulations for vessel traffic in the Strait',
            'SIGTTO "Passage Planning for Large LNG Carriers"',
            'IMO Resolution A.857(20) — Guidelines for vessel traffic services',
            'Company passage plan standards and UKC policy',
            'OCIMF "Marine Terminal Management and Self Assessment" (MTMSA)',
            'IMO Resolution A.601(15) — Provision and display of manoeuvring information',
        ],
        'red_flags_json' => [
            'Transiting Phillip Channel without calculating squat effect on UKC',
            'Proceeding outside the tidal window with insufficient UKC',
            'No speed reduction in shallow section to manage squat',
            'Ignoring beam wind effect on high-freeboard LNGC near shoal water',
            'No contingency or abort plan for the critical shallow-water transit',
            'Failing to coordinate with Singapore VTIS in heavy traffic conditions',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SLOT 2 — CMD_SCALE — Terminal/charterer pressure + no-go decision
    // ══════════════════════════════════════════════════════════════
    'LNG_S02_CMD_001' => [
        'briefing_json' => [
            'en' => [
                'situation'           => 'You are Master of a 174,000 CBM membrane LNGC arriving at an exposed LNG terminal in West Africa under significant charterer pressure to berth within a 2-hour window or face $350,000/day demurrage. The terminal jetty is oriented NW-SE, creating direct crosswind and cross-current exposure at berth from the prevailing SW weather. The latest forecast shows wind at 20 knots sustained gusting to 28 knots from the SW, with swell building from the SW at 1.8m and expected to reach 2.5m within 6 hours. The terminal\'s operational limit for LNG cargo operations is 25 knots sustained wind and 2.0m significant wave height at berth. Your company SMS berthing criteria state maximum 25 knots crosswind for LNGC berthing operations and require a positive safety assessment before approach. Four harbor tugs are available (2 x 70t BP, 2 x 50t BP), but the tug master of the lead 70t tug has radioed you directly expressing concern about the SW swell effect on tug line safety during the berthing maneuver, noting that beam swell creates snap-loading risk on tow lines at the bow. The terminal pilot has boarded and insists conditions are acceptable, stating "we berth in these conditions regularly." During the pre-berth safety checklist, your C/O reports that the #2 port liquid manifold ESD valve failed its self-test — the valve operated but response time was 32 seconds versus the required 30-second maximum, and visual inspection shows the actuator seal is weeping hydraulic fluid. Terminal control is now on VHF demanding you commence your approach immediately as they have a departure scheduled for the opposite berth in 3 hours.',
                'your_position'       => 'Bridge, approaching the terminal fairway. Pilot on board, C/O at cargo control room verifying pre-arrival checklist, 2/O on bridge assisting with navigation.',
                'available_resources'  => '4 harbor tugs (2x70t BP, 2x50t BP), ship\'s mooring winches (auto-tension capable), terminal mooring boats, comprehensive weather forecast service, company SMS with defined berthing limits, SIGTTO berthing guidelines, cargo control room with ESD panel, shore ESD link system, spare ESD valve actuator in ship\'s stores (installation requires 4-6 hours), terminal operations manual, VHF to terminal control/tugs/pilot.',
                'current_conditions'   => 'Wind SW 20 kt sustained gusting 28 kt, SW swell 1.8m building to 2.5m, cross-current 0.8 kt at berth, 4 tugs (lead tug master concerned about swell), terminal limit 25 kt sustained, ESD valve #2 port failed self-test (32 sec vs 30 sec max, hydraulic weep on actuator), charterer demurrage $350k/day, terminal demanding immediate approach.',
            ],
            'tr' => [
                'situation'           => '174.000 CBM membran LNG gemisinin kaptanı olarak, Batı Afrika\'daki açık bir LNG terminaline 2 saatlik pencere içinde yanaşma baskısı altındasınız, aksi halde günlük 350.000 $ sürastarya doğacaktır. Terminal iskele yönlendirmesi KB-GD olup, hakim GB hava koşullarından doğrudan çapraz rüzgar ve akıntıya maruz kalmaktadır. Tahmin: GB\'den 20 knot sürekli rüzgar, 28 knot hamle, GB\'den 1,8m kabarma 6 saat içinde 2,5m\'ye yükselecek. Terminalin LNG kargo operasyonları limiti 25 knot sürekli rüzgar ve 2,0m dalga yüksekliğidir. Şirket SMS yanaşma kriteri: LNGC için maksimum 25 knot çapraz rüzgar ve yaklaşım öncesi olumlu güvenlik değerlendirmesi gerekli. Dört römorkör mevcut (2x70t, 2x50t), ancak baş 70t römorkör kaptanı GB dalgasının römorkör halatlarında ani yüklenme riski yarattığı endişesini doğrudan telsizle bildirmiştir. Terminal kılavuzu "bu koşullarda düzenli olarak yanaşıyoruz" diyerek koşulları kabul edilebilir buluyor. Yanaşma öncesi güvenlik kontrol listesinde Birinci Zabitiniz #2 iskele sıvı manifold ESD valfinin kendi testinde başarısız olduğunu bildiriyor — valf çalıştı ancak yanıt süresi gereken 30 saniye maksimuma karşı 32 saniye, görsel inceleme aktüatör contasından hidrolik sızıntı gösteriyor. Terminal kontrol derhal yaklaşmanızı başlatmanızı talep ediyor.',
                'your_position'       => 'Köprüüstü, terminal farvaterine yaklaşma. Kılavuz gemide, Birinci Zabit kargo kontrol odasında yanaşma öncesi kontrol listesi doğrulaması yapıyor, İkinci Zabit seyir yardımında.',
                'available_resources'  => '4 römorkör (2x70t, 2x50t), gemi palamar vinçleri (otomatik gerginlik), mooring botları, hava tahmini, şirket SMS yanaşma limitleri, SIGTTO yanaşma kılavuzu, ESD panelli kargo kontrol odası, kıyı ESD bağlantı sistemi, yedek ESD valf aktüatörü (kurulum 4-6 saat), terminal operasyon kılavuzu, VHF.',
                'current_conditions'   => 'Rüzgar GB 20 knot sürekli hamle 28, GB kabarma 1,8m (yükselen), çapraz akıntı 0,8 knot, 4 römorkör (baş römorkör kaptanı endişeli), terminal limiti 25 knot, #2 ESD valf testi başarısız (32 sn / 30 sn maks, hidrolik sızıntı), sürastarya 350k$/gün.',
            ],
            'ru' => [
                'situation'           => 'Вы капитан мембранного СПГ-танкера 174 000 м³, подходите к открытому СПГ-терминалу в Западной Африке. Фрахтователь требует швартовку в 2-часовом окне, иначе демередж $350 000/сутки. Ветер SW 20 узлов (порывы 28), зыбь SW 1,8 м (растёт до 2,5 м). Лимит терминала — 25 узлов устойчивый. Капитан ведущего буксира обеспокоен рывковыми нагрузками от зыби. Лоцман настаивает: условия приемлемы. ESD-клапан #2 по левому борту не прошёл самотест (32 сек при норме 30, течь гидравлики). Терминал требует немедленный подход.',
                'your_position'       => 'Мостик, подход к фарватеру. Лоцман на борту, старпом в грузовом, 2-й помощник на мостике.',
                'available_resources'  => '4 буксира (2×70т, 2×50т), швартовные лебёдки, метеосервис, SMS компании, SIGTTO, ESD-панель, запасной привод ESD (установка 4-6 ч), руководство терминала, УКВ.',
                'current_conditions'   => 'Ветер SW 20 (порывы 28), зыбь 1,8 м (растёт), течение 0,8 узла, буксир обеспокоен, ESD не прошёл тест, демередж $350k/день.',
            ],
            'az' => [
                'situation'           => '174 000 CBM membran LNG gəmisinin kapitanısınız, Qərbi Afrikada açıq LNG terminalına yaxınlaşırsınız. Çarter 2 saatda liman etməyi tələb edir, əks halda $350 000/gün demüraj. Külək SW 20 knot (şiddətli 28), dalğalanma 1,8 m (2,5 m-ə artır). Terminal limiti 25 knot. Baş yedəkçi kapitanı dalğanın halat təhlükəsindən narahatdır. Losman "şərait məqbuldur" deyir. #2 sol manifold ESD klapanı testi keçməyib (32 san/30 san norma, hidravlik sızıntı). Terminal dərhal yaxınlaşma tələb edir.',
                'your_position'       => 'Körpüüstü, farvaterə yaxınlaşma. Losman göyərtədə, birinci stürman yük idarəsində.',
                'available_resources'  => '4 yedəkçi, bağlama bucurğadları, meteoroloji xidmət, SMS, SIGTTO, ESD panel, ehtiyat ESD aktuator (4-6 saat quraşdırma), VHF.',
                'current_conditions'   => 'Külək SW 20 (28 şiddətli), dalğa 1,8 m, axın 0,8 knot, yedəkçi narahat, ESD nasaz, demüraj $350k/gün.',
            ],
        ],
        'decision_prompt'      => 'What is your decision regarding the berthing approach? Address the weather assessment against terminal and company limits, your response to the charterer/terminal pressure, the tug master\'s safety concern, the ESD valve deficiency and its implications for cargo operations, and how you communicate your decision to all stakeholders.',
        'decision_prompt_i18n' => [
            'tr' => 'Yanaşma yaklaşımı konusundaki kararınız nedir? Terminal ve şirket limitlerine karşı hava değerlendirmesini, kiracı/terminal baskısına yanıtınızı, römorkör kaptanının güvenlik endişesini, ESD valf eksikliğini ve kargo operasyonlarına etkisini, ve kararınızı tüm paydaşlara nasıl ilettiğinizi ele alın.',
            'ru' => 'Каково ваше решение о подходе к причалу? Рассмотрите: оценку погоды относительно лимитов, реакцию на давление, обеспокоенность буксира, неисправность ESD-клапана и коммуникацию со всеми сторонами.',
            'az' => 'Yanaşma barədə qərarınız nədir? Hava qiymətləndirməsi, təzyiqə cavab, yedəkçi narahatlığı, ESD klapan nasazlığı və qərarın bütün tərəflərə çatdırılmasını nəzərə alın.',
        ],
        'evaluation_axes_json' => [
            [
                'axis'   => 'command_decision_authority',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Capitulates to terminal and charterer pressure. Proceeds with berthing despite marginal weather, tug master\'s concern, and ESD deficiency. No independent safety assessment. Allows commercial pressure to override safety judgment.',
                    '2' => 'Hesitates and attempts to negotiate a compromise — "we will try and see." Does not make a firm decision. Allows the pilot\'s reassurance to override own assessment. Does not address the ESD valve issue as a separate no-go criterion.',
                    '3' => 'Makes a clear decision based on safety assessment: recognises that gusting to 28 knots exceeds the 25-knot terminal limit, the building swell will exceed 2.0m during cargo operations, and the ESD valve deficiency creates a cargo safety risk. Decides to delay berthing until conditions improve and the ESD issue is resolved. Communicates decision to terminal and company.',
                    '4' => 'Strong command authority: conducts systematic risk assessment addressing each factor independently — weather (gusts above terminal limit, swell building beyond safe threshold), tug safety (tug master\'s concern is a valid professional assessment that must be respected), ESD valve (even if marginal, a failed self-test means the safety system cannot be verified as reliable for cargo operations). Decides no-go on multiple independent grounds. Informs company before communicating to terminal. Provides terminal with clear technical justification. Proposes a revised berthing window based on weather forecast and ESD repair timeline.',
                    '5' => 'Expert command decision-making: comprehensive and documented safety assessment that addresses each risk factor with reference to specific standards — weather: gusts of 28 knots exceed terminal operational limit of 25 knots sustained (SIGTTO guidance that gusts must be considered, not just sustained wind); swell: 1.8m building to 2.5m will exceed terminal 2.0m limit during cargo operations (must consider conditions for the entire cargo operation duration, not just berthing); tugs: tug master\'s concern about snap-loading in beam swell is a professional safety judgment that cannot be overridden (OCIMF Mooring Equipment Guidelines on dynamic tow line loads); ESD valve: IGC Code requires all ESD systems to be tested and operational before cargo transfer — a failed self-test with hydraulic leak is a definitive no-go regardless of weather. Documents the decision with timestamps, weather data, and specific regulatory references. Proposes to the company a plan: repair ESD valve (4-6 hours), await weather improvement, request revised terminal slot. Makes clear that the $350k/day demurrage is irrelevant to a safety decision under ISM Code Master\'s overriding authority. Maintains professional relationship with the pilot while firmly declining.',
                ],
            ],
            [
                'axis'   => 'risk_communication',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'No structured communication. Either silently complies or refuses without explanation. Does not inform company or provide technical reasoning to the terminal.',
                    '2' => 'Communicates the decision but without structured technical justification. Allows discussion with terminal to become adversarial. Does not manage the pilot relationship effectively.',
                    '3' => 'Clear communication to terminal and company: states the specific reasons for delay (weather limits exceeded, ESD deficiency), provides the regulatory basis, maintains professional tone with pilot and terminal.',
                    '4' => 'Effective multi-stakeholder communication: formal notification to terminal with specific limit exceedances cited (gusts 28 kt vs 25 kt limit, swell forecast exceeding 2.0m, ESD valve failed self-test per IGC Code requirements), company operations informed with comprehensive risk assessment before the terminal is notified, pilot briefed on the decision with respect for the pilot\'s experience but clarity that the Master\'s assessment governs, tug master\'s concern documented and acknowledged, charterer informed through the company with factual basis to mitigate demurrage dispute.',
                    '5' => 'Expert risk communication: layered communication strategy — company briefed first with full data package (weather observations, forecasts, terminal limits, ESD test results, tug master\'s statement) and Master\'s recommendation, ensuring company support before external communication; terminal notified formally with technical letter/email documenting specific limit exceedances and regulatory requirements, proposed revised timeline, and positive language ("we will berth at the earliest safe opportunity"); pilot thanked for professional assessment but Master\'s decision documented in Master/Pilot exchange; tug master\'s concern formally acknowledged and documented as supporting evidence; charterer communication handled by company with Master\'s factual report attached to establish clear record against demurrage claims. All communications timestamped and logged for potential dispute resolution.',
                ],
            ],
            [
                'axis'   => 'terminal_interface_management',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'No awareness of terminal interface safety requirements. Does not address the ESD valve deficiency as a terminal safety issue. No consideration of the neighboring berth operations.',
                    '2' => 'Acknowledges the ESD issue but treats it as minor because the valve operated (just slow). Does not consider the implications for the ship/shore ESD link or cargo operations safety.',
                    '3' => 'Recognises the ESD valve failure as a terminal interface safety issue: the ship/shore ESD link requires all ESD valves to function within specification, a valve with a 32-second response time and hydraulic leak cannot be relied upon for emergency cargo shutdown, and this must be resolved before any cargo transfer. Plans repair and retest before cargo operations.',
                    '4' => 'Thorough terminal interface management: identifies the ESD valve deficiency as a no-go item independent of weather — IGC Code and SIGTTO ESD guidance require all emergency systems tested and verified before cargo transfer. Plans: isolate the affected manifold, commence repair using spare actuator (4-6 hours), conduct full ESD test including ship/shore link test before cargo operations. Reviews the pre-berth safety checklist to confirm no other deficiencies. Coordinates with terminal on revised cargo plan that may use alternative manifold configuration during repair. Considers the neighboring berth departure schedule in the approach planning.',
                    '5' => 'Expert terminal interface management: comprehensive approach to the ESD issue — recognises that per SIGTTO "ESD Arrangements and Linked Ship/Shore Systems", the ESD system is a single integrated safety barrier and any component failure compromises the entire system. Plans a full ESD system review (not just the failed valve) before cargo operations. Coordinates with terminal safety officer on the deficiency, agreeing on repair plan and retest protocol. Reviews the ship/shore compatibility checklist (ISGOTT/SIGTTO) in full, not just the failed item. Assesses whether the hydraulic leak indicates a systemic maintenance issue requiring broader inspection. Plans cargo operations to avoid using the affected manifold until repair is verified. Considers terminal emergency response readiness (fire monitors not pressurized is unacceptable — requests terminal confirm fire safety readiness). Addresses the neighboring berth tanker operation as a SIMOPS consideration — departure of vessel from opposite berth during own approach creates additional risk. Documents all terminal interface issues for company and classification society notification if required.',
                ],
            ],
        ],
        'critical_omission_flags_json' => [
            ['flag' => 'No independent safety assessment — proceeds under commercial pressure',            'severity' => 'critical'],
            ['flag' => 'No recognition of ESD valve failure as a no-go for cargo operations',             'severity' => 'critical'],
            ['flag' => 'No consideration of weather trend — swell building beyond terminal limits',        'severity' => 'critical'],
            ['flag' => 'Disregards tug master\'s professional safety concern about swell',                 'severity' => 'major'],
            ['flag' => 'No communication to company before engaging with terminal on the decision',        'severity' => 'major'],
            ['flag' => 'No reference to ISM Code Master\'s overriding authority',                          'severity' => 'major'],
        ],
        'expected_references_json' => [
            'SIGTTO "ESD Arrangements and Linked Ship/Shore Systems"',
            'OCIMF "Mooring Equipment Guidelines" (MEG4)',
            'IGC Code Chapter 5 (cargo containment) and Chapter 18 (operating requirements)',
            'Company SMS berthing limits and Master\'s overriding authority policy',
            'ISGOTT Chapter 26 — Ship/shore safety checklist',
            'Terminal-specific operations manual and berthing criteria',
            'ISM Code Section 5 — Master\'s responsibility and authority',
        ],
        'red_flags_json' => [
            'Proceeding to berth under commercial pressure when weather exceeds terminal limits',
            'Ignoring ESD valve self-test failure and proceeding with cargo operations',
            'Overriding tug master\'s professional safety assessment of swell conditions',
            'No independent risk assessment — relying solely on pilot\'s or terminal\'s judgment',
            'Allowing charterer demurrage pressure to influence safety-critical decision',
            'No documentation of the safety assessment and decision rationale',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SLOT 3 — TECH_DEPTH — Boil-off / reliquefaction / containment alarm
    // ══════════════════════════════════════════════════════════════
    'LNG_S03_TECH_001' => [
        'briefing_json' => [
            'en' => [
                'situation'           => 'You are Master of a 174,000 CBM membrane LNGC (GTT Mark III containment system) on a loaded voyage, Day 5 of an 18-day passage. The cargo monitoring system has triggered an alarm on Tank #2: the primary barrier inter-barrier space (IBS) pressure has risen from the normal operating range of 130-160 mbar to 280 mbar over the past 12 hours, and is trending upward toward the 300 mbar alarm threshold. The IBS is normally maintained with nitrogen at positive pressure to detect any primary barrier leakage — a rising pressure indicates either a temperature change in the IBS (thermal effect) or gas ingress through the primary membrane. Your cargo engineer has taken gas samples from the Tank #2 IBS and detected methane concentration at 0.8% — below the 1.0% investigation threshold per GTT guidelines but significantly higher than the normal trace levels (<0.1%) in the other tanks. Simultaneously, the vessel\'s overall boil-off rate (BOR) has increased from the charter party guaranteed 0.10%/day to 0.14%/day across all four tanks. This could indicate increased ambient heat ingress through insulation degradation or higher sea/air temperatures. The reliquefaction plant tripped 6 hours ago on high discharge pressure at the compressor — the ETO has attempted two restarts, both failing on the same alarm. Without reliquefaction, the excess boil-off gas (BOG) beyond main boiler consumption must be managed. The main propulsion boiler can burn approximately 0.12%/day equivalent of BOG at full speed; with BOR at 0.14%, there is a 0.02%/day excess. Options: reduce speed to lower BOG demand (extending voyage, potentially missing the 3-day terminal slot and incurring waiting time), burn HFO supplement to maintain speed (CII rating impact — vessel is currently at C rating, HFO use will push toward D), or use the gas combustion unit (GCU) to burn excess BOG (wasteful but maintains schedule). GTT technical support has been contacted via satellite but intermittent communications (high-latitude satellite coverage issues) mean responses are delayed by 4-8 hours.',
                'your_position'       => 'Bridge/cargo control room. C/O managing cargo monitoring, cargo engineer investigating Tank #2, ETO troubleshooting reliquefaction plant, Chief Engineer in engine room.',
                'available_resources'  => 'Cargo monitoring system (tank pressures, temperatures, IBS pressures, gas detection for all 4 tanks), BOR tracking system, GTT Mark III technical manual and emergency procedures, company cargo operations manual, reliquefaction plant with ETO troubleshooting, GCU (gas combustion unit) available, main boiler dual-fuel (LNG/HFO), satellite communication (intermittent), GTT 24-hour technical support (delayed response), cargo log with historical IBS data for comparison, classification society emergency contact.',
                'current_conditions'   => 'Day 5 of 18, sea temp 28°C (tropical), air temp 32°C, Tank #2 IBS pressure 280 mbar (rising from 150 mbar), IBS methane 0.8%, BOR 0.14%/day (vs 0.10% guaranteed), reliquefaction plant tripped (two restart failures), main boiler BOG capacity 0.12%/day, 3-day terminal window starting Day 18, CII rating currently C, satellite comms intermittent.',
            ],
            'tr' => [
                'situation'           => '174.000 CBM membran LNGC\'nin (GTT Mark III) kaptanısınız, 18 günlük seferin 5. gününde yüklü seyir halinde. Tank #2 birincil bariyer arası boşluk (IBS) basıncı 12 saat içinde normal 130-160 mbar aralığından 280 mbar\'a yükselmiş, 300 mbar alarm eşiğine doğru trend gösteriyor. IBS normalde azot ile pozitif basınçta tutulur — yükselen basınç sıcaklık değişimini veya birincil membrandan gaz girişini gösterir. Kargo mühendisi Tank #2 IBS\'den gaz örneği almış ve %0,8 metan konsantrasyonu tespit etmiş — GTT kılavuzuna göre %1,0 soruşturma eşiğinin altında ancak diğer tanklardaki normal eser seviyesinden (<%0,1) önemli ölçüde yüksek. Aynı zamanda, genel buharlaşma oranı (BOR) tüm dört tankta charter parti garantili %0,10/gün\'den %0,14/gün\'e yükselmiş. Reliquefaction tesisi 6 saat önce kompresörde yüksek basınçla devre dışı kalmış — ETO iki yeniden başlatma denemiş, her ikisi de aynı alarmda başarısız. Reliquefaction olmadan, ana kazan BOG tüketimini aşan fazla buharlaşma gazı yönetilmelidir. Ana kazan tam hızda yaklaşık %0,12/gün eşdeğeri BOG yakabilir; BOR %0,14 ile günde %0,02 fazlalık var. Seçenekler: hız düşürme (seferi uzatma, terminal slotunu kaçırma riski), HFO takviyesi (CII etkisi — C puanından D\'ye düşme), veya GCU ile fazla BOG yakma (israf ama program korunur). GTT teknik destek iletişimi uydu sorunları nedeniyle 4-8 saat gecikmeli.',
                'your_position'       => 'Köprüüstü/kargo kontrol odası. Birinci Zabit kargo izleme, kargo mühendisi Tank #2 soruşturma, ETO reliquefaction sorun giderme, Başmühendis makine dairesinde.',
                'available_resources'  => 'Kargo izleme sistemi, BOR takibi, GTT Mark III teknik kılavuz, şirket kargo operasyonları kılavuzu, reliquefaction tesisi (ETO müdahalede), GCU, çift yakıtlı ana kazan, uydu iletişimi (kesintili), GTT teknik destek (gecikmeli), geçmiş IBS verileri, klas kuruluşu acil irtibat.',
                'current_conditions'   => '18 günün 5. günü, deniz 28°C, Tank #2 IBS 280 mbar (yükselen), IBS metan %0,8, BOR %0,14, reliquefaction devre dışı, kazan BOG kapasitesi %0,12/gün, terminal slotu 18. gün, CII C puanı, uydu kesintili.',
            ],
            'ru' => [
                'situation'           => 'Вы капитан мембранного СПГ-танкера 174 000 м³ (GTT Mark III), день 5 из 18. Давление в межбарьерном пространстве (МБП) Танка #2 выросло с 150 до 280 мбар за 12 часов (аларм при 300). Концентрация метана в МБП 0,8% (норма <0,1%). Суммарный BOR вырос с 0,10% до 0,14%/день. Реликвефакционная установка отключилась 6 часов назад — два перезапуска неудачны. Котёл потребляет ~0,12%/день BOG. Избыток 0,02%/день: либо снизить ход (опоздание к терминалу), либо HFO (CII ухудшение), либо GCU (сжигание излишков). Связь с GTT через спутник с задержкой 4-8 часов.',
                'your_position'       => 'Мостик/грузовой пост. Старпом — мониторинг, грузовой инженер — Танк #2, ETO — реликвефакция, стармех — МО.',
                'available_resources'  => 'Система контроля груза, BOR-трекинг, техруководство GTT Mark III, руководство компании, GCU, двухтопливный котёл, спутниковая связь (нестабильная), GTT-поддержка (задержка), история IBS, контакт класса.',
                'current_conditions'   => 'День 5/18, море 28°C, Танк #2 МБП 280 мбар, CH₄ 0,8%, BOR 0,14%, реликвефакция отключена, слот терминала день 18, CII — C.',
            ],
            'az' => [
                'situation'           => '174 000 CBM membran LNG gəmisinin (GTT Mark III) kapitanısınız, 18 günlük səfərin 5-ci günü. Tank #2 IBS təzyiqi 12 saatda 150-dən 280 mbar-a artıb (alarm 300-də). IBS metanı 0,8% (norma <0,1%). BOR 0,10%-dən 0,14%-ə artıb. Reliquefaction 6 saat əvvəl sönüb — 2 yenidən başlatma uğursuz. Qazan ~0,12%/gün BOG yandırır. Artıq 0,02%: sürəti azaltmaq, HFO yandırmaq (CII pisləşmə), və ya GCU istifadə etmək. GTT əlaqəsi 4-8 saat gecikmə ilə.',
                'your_position'       => 'Körpüüstü/yük idarəsi. Birinci stürman monitorinq, yük mühəndisi Tank #2, ETO reliquefaction, baş mühəndis MO-da.',
                'available_resources'  => 'Yük monitorinq sistemi, BOR izləmə, GTT təlimatı, şirkət təlimatı, GCU, ikiyanlı qazan, peyk rabitəsi (fasilələrlə), GTT dəstək, IBS tarixi, klas əlaqəsi.',
                'current_conditions'   => 'Gün 5/18, dəniz 28°C, Tank #2 IBS 280 mbar, CH₄ 0,8%, BOR 0,14%, reliquefaction sönük, terminal slotu gün 18, CII — C.',
            ],
        ],
        'decision_prompt'      => 'Describe your assessment and management of the Tank #2 IBS anomaly. Explain your diagnosis of the potential primary barrier issue, your decision on boil-off gas management without the reliquefaction plant, and how you balance the cargo containment concern against the commercial and CII implications. Include your communication strategy with GTT, the company, and the terminal.',
        'decision_prompt_i18n' => [
            'tr' => 'Tank #2 IBS anomalisinin değerlendirme ve yönetimini açıklayın. Olası birincil bariyer sorununun tanısını, reliquefaction tesisi olmadan buharlaşma gazı yönetimi kararınızı, ve kargo muhafaza endişesini ticari ve CII etkileriyle nasıl dengelediğinizi açıklayın. GTT, şirket ve terminal ile iletişim stratejinizi ekleyin.',
            'ru' => 'Опишите оценку и управление аномалией МБП Танка #2: диагностика барьера, решение по управлению BOG без реликвефакции, баланс между безопасностью груза, коммерческими и CII-последствиями. Включите стратегию связи с GTT, компанией и терминалом.',
            'az' => 'Tank #2 IBS anomaliyasının qiymətləndirilməsi və idarəsini təsvir edin: baryerin diaqnozu, reliquefaction olmadan BOG idarəsi, yük təhlükəsizliyi ilə kommersiya/CII arasında balans, GTT, şirkət və terminalla əlaqə strategiyası.',
        ],
        'evaluation_axes_json' => [
            [
                'axis'   => 'cargo_containment_diagnosis',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Ignores the IBS pressure rise or dismisses it as a sensor error without investigation. No gas sampling, no trend analysis, no reference to GTT procedures. Continues voyage without addressing the containment concern.',
                    '2' => 'Acknowledges the IBS alarm but does not perform systematic diagnosis. Does not compare Tank #2 IBS data with other tanks or historical trends. Does not understand the significance of methane detection in the IBS.',
                    '3' => 'Systematic diagnosis: recognises that rising IBS pressure with methane detection (0.8%) indicates possible primary barrier micro-leak in Tank #2. Compares with other tanks (normal IBS pressures and trace levels). Reviews historical IBS data for Tank #2 to identify when the anomaly started. References GTT Mark III technical manual for diagnostic procedures. Increases monitoring frequency on Tank #2 IBS. Plans enhanced gas sampling at regular intervals to determine if methane concentration is stable or rising.',
                    '4' => 'Thorough containment assessment: differential diagnosis — considers thermal effects (tropical waters 28°C may cause IBS pressure rise through insulation thermal lag) versus primary barrier micro-leak (methane presence is the distinguishing factor). Notes that 0.8% methane is below the 1.0% GTT investigation threshold but the trend from <0.1% is significant. Cross-references Tank #2 IBS temperature data with tank liquid level and sloshing history. Reviews the GTT Mark III primary barrier structure (stainless steel corrugated membrane) for known failure modes at this tank location. Establishes an enhanced monitoring regime: IBS pressure logged every 30 minutes, gas sampling every 2 hours, and methane concentration plotted against time. Prepares detailed data package for GTT technical support. Considers whether the increased BOR across all tanks is related or coincidental.',
                    '5' => 'Expert cargo containment diagnosis: comprehensive analysis integrating all available data — IBS pressure trend rate (150 to 280 mbar in 12 hours = ~10.8 mbar/hour, linear trend will reach 300 mbar alarm in ~2 hours), methane concentration trending (requests cargo engineer to establish a sampling protocol every hour to determine rate of change), temperature differentials between Tank #2 IBS and other tanks to isolate thermal vs leak effect, tank liquid level and sloshing assessment (recent heavy weather may have caused membrane fatigue at tank corner areas — a known GTT Mark III consideration), review of vessel\'s drydock records for any previous Tank #2 membrane repairs. Distinguishes between scenarios: (a) thermal-only (no methane increase over time = benign), (b) micro-leak stable (methane stable at 0.8% = very small defect, manageable), (c) micro-leak progressing (methane rising = potential for secondary barrier loading). Plans for each scenario including the worst case: if methane reaches 1.0% or IBS pressure hits 300 mbar, GTT procedures require specific actions including potential speed reduction and cargo temperature management. Prepares comprehensive data transmission to GTT including IBS trend graphs, methane sampling results, tank loading conditions, recent weather history, and vessel trading route temperatures. Contacts classification society as a precaution for containment system anomaly reporting.',
                ],
            ],
            [
                'axis'   => 'boiloff_management_decision',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'No plan for managing excess BOG without reliquefaction. Ignores the BOR increase. Does not consider the balance between schedule, CII, and operational safety.',
                    '2' => 'Recognises the BOG surplus but proposes only one solution without analysing alternatives. Does not consider CII implications or terminal schedule impact.',
                    '3' => 'Analyses the BOG management options: speed reduction to reduce BOG generation, HFO supplementation to maintain speed, or GCU to burn excess. Evaluates each against schedule (Day 18 terminal slot), CII impact (currently C rating), and commercial implications. Makes a reasoned decision considering all factors.',
                    '4' => 'Systematic BOG management: quantifies the problem (0.14% BOR - 0.12% boiler capacity = 0.02% excess = approximately 35 m³/day excess BOG). Evaluates options: (1) Speed reduction — calculates the speed at which boiler demand matches 0.14% BOR, determines revised ETA vs terminal window, reports to company; (2) GCU — burns 0.02% excess, maintains speed and schedule, but represents cargo loss of ~35 m³/day (commercial impact to charterer); (3) HFO supplement — calculates fuel cost and CII impact for remaining 13 days, determines if this pushes CII from C to D. Also prioritises reliquefaction plant repair — works with ETO and Chief Engineer to diagnose the high discharge pressure fault (possible condenser issue, refrigerant charge, or compressor valve failure). Recommends company on the optimal combination and requests guidance on CII vs schedule trade-off.',
                    '5' => 'Expert BOG and energy management: comprehensive analysis with numerical data — calculates exact BOG surplus (174,000 m³ × 0.02% = 34.8 m³ LNG/day equivalent), converts to energy terms for each option comparison. Speed reduction analysis: plots BOG generation curve against speed, identifies the equilibrium speed (approximately 16-17 knots vs 19 knots full speed), calculates revised ETA and determines if terminal window can still be met with margin. GCU analysis: quantifies cargo loss value to charterer (34.8 m³/day × 13 days × LNG price = commercial exposure), determines this requires charterer notification per charter party terms. HFO analysis: calculates HFO consumption for supplementary power, cost, and precise CII impact using IMO DCS methodology — determines whether the CII impact is acceptable versus the alternative costs. Reliquefaction priority: establishes this as the preferred solution, directs ETO and Chief Engineer to conduct systematic fault-finding on the compressor (high discharge pressure suggests condenser fouling in tropical waters, loss of coolant flow, or non-condensable gas in refrigerant circuit). Sets repair timeline target. Recommends to company a combined strategy: GCU for immediate BOG management while prioritising reliquefaction repair, with fallback to slight speed reduction if repair fails within 24 hours and terminal window is at risk. Addresses the relationship between the BOR increase and Tank #2 — if the containment issue is contributing to elevated BOR, cargo management decisions may change.',
                ],
            ],
            [
                'axis'   => 'technical_communication',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'No communication with GTT, company, or terminal about the containment or BOG issues. Attempts to handle everything independently without seeking technical support.',
                    '2' => 'Contacts company but provides incomplete information. Does not prioritise GTT communication despite the containment concern. Does not consider terminal notification for potential schedule impact.',
                    '3' => 'Communicates with company about both the containment anomaly and BOG management issue. Contacts GTT via satellite (despite intermittent comms) with Tank #2 data. Advises terminal of potential schedule delay. Maintains cargo log with enhanced monitoring data.',
                    '4' => 'Effective technical communication: prepares structured data package for GTT (IBS pressure trend, gas sampling results, tank conditions, vessel route and environmental data) and transmits via satellite at the next communication window. Briefs company with a comprehensive situation report covering: containment status (Tank #2 IBS anomaly, monitoring protocol, GTT consulted), BOG management (options analysis with recommendation), and schedule impact assessment. Advises terminal of potential delay with estimated revised ETA range. Ensures all cargo monitoring data is logged for class and flag state reporting if containment issue escalates. Coordinates between Chief Engineer and ETO on reliquefaction troubleshooting with clear priorities.',
                    '5' => 'Expert technical communication and coordination: establishes a structured reporting regime — GTT: comprehensive technical data package prepared (IBS trends, gas chromatography results, tank pressures/temperatures/levels, hull stress data, weather and sea temperature history, previous inspection records) with specific questions for GTT engineers, transmitted in prioritised format for intermittent satellite windows, backup via HF email if satellite fails; Company: formal situation report with risk assessment matrix covering containment, BOG management, commercial, and regulatory dimensions, clear recommendation with alternatives, requests company to pre-coordinate with terminal and classification society; Terminal: early notification of potential delay with honest assessment and proposed revised timeline; Classification society: precautionary notification of IBS anomaly per class rules for gas carrier containment systems (early engagement demonstrates due diligence). Internally: conducts a structured briefing with all senior officers (C/O, Chief Engineer, cargo engineer, ETO) to ensure coordinated response, assigns clear responsibilities and monitoring duties, establishes escalation criteria (IBS reaches 300 mbar, methane exceeds 1.0%, BOR exceeds 0.15%), documents all actions and decisions in the cargo operations log with timestamps for potential incident investigation.',
                ],
            ],
        ],
        'critical_omission_flags_json' => [
            ['flag' => 'No investigation of Tank #2 IBS pressure anomaly with gas sampling and trend analysis',        'severity' => 'critical'],
            ['flag' => 'No recognition that methane in IBS may indicate primary barrier micro-leak',                    'severity' => 'critical'],
            ['flag' => 'No plan for BOG management without reliquefaction plant',                                       'severity' => 'major'],
            ['flag' => 'No communication with GTT technical support regarding containment anomaly',                     'severity' => 'critical'],
            ['flag' => 'No enhanced monitoring regime established for Tank #2',                                         'severity' => 'major'],
            ['flag' => 'No assessment of whether BOR increase is related to containment system degradation',            'severity' => 'major'],
        ],
        'expected_references_json' => [
            'IGC Code Chapter 5 — Cargo containment systems, design and testing requirements',
            'GTT Mark III Containment System Technical Manual — IBS monitoring and emergency procedures',
            'SIGTTO "Crew Competence and Training in LNG Cargo Operations"',
            'Company cargo operations manual — containment anomaly procedures',
            'IMO MSC.1/Circ.1599 — Interim guidelines for gas carrier cargo operations',
            'Classification society rules for gas carrier containment system integrity',
        ],
        'red_flags_json' => [
            'Ignoring IBS pressure rise and methane detection without investigation',
            'No gas sampling from Tank #2 IBS to assess barrier integrity',
            'Failing to contact GTT technical support for containment system anomaly',
            'No contingency plan for BOG management without reliquefaction',
            'Prioritising schedule over containment system investigation',
            'No enhanced monitoring regime for the anomalous tank',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SLOT 4 — RISK_MGMT — ESD/ESD link, gas detection, ignition sources
    // ══════════════════════════════════════════════════════════════
    'LNG_S04_RISK_001' => [
        'briefing_json' => [
            'en' => [
                'situation'           => 'You are Master of an LNGC alongside an LNG terminal, cargo discharge in progress — 85% complete with 4 tanks discharging via 2 liquid manifolds and 1 vapor return line. Operations have been running smoothly for 14 hours. During a routine cargo area round at 0230, the duty officer reports that gas detection alarm ML-03 at the manifold area has activated showing 15% LEL (Lower Explosive Limit). The wind is light and variable at 5 knots, currently from the NE, which means any gas cloud from the manifold area would drift toward the accommodation block and galley exhaust fans on the port quarter. Simultaneously, you receive a report from the bosun on his rounds that he has discovered two members of a shore maintenance crew performing unauthorized welding on a deck store bracket located approximately 30 meters aft of the manifold connection area. They have a ship\'s welding set that was left unsecured in the deck store — no Permit to Work has been issued, no gas testing was conducted, and the terminal was not notified. The fire monitors on the terminal jetty are in their standby cradles but are not pressurized — a condition you noted but did not formally challenge during the shift-change safety check 2 hours ago. Your ESD (Emergency Shutdown) panel on the bridge shows "ESD Link Active," but during the shift-change testing, the terminal-side ESD push-button showed a sluggish response of 3.2 seconds compared to the required maximum of 1 second, which the terminal operator dismissed as "the cold weather slowing the actuator." The neighboring berth, 150 meters away, has a product tanker conducting an STS (Ship-to-Ship) transfer operation with a barge alongside.',
                'your_position'       => 'Bridge, monitoring cargo operations. Duty officer at manifold area, bosun on deck rounds, cargo engineer in cargo control room, terminal operator in terminal control room.',
                'available_resources'  => 'Ship\'s ESD system with bridge-activated emergency shutdown, ship/shore ESD link to terminal, fixed gas detection system (manifold area sensors ML-01 through ML-06, deck area sensors, accommodation sensors), portable gas detectors, ship\'s fire-fighting equipment (water spray, dry powder, foam), terminal fire monitors (not pressurized), ship\'s fire pumps (running), PA/GA system, VHF to terminal control and neighboring berth, cargo control room with remote valve operation, company emergency response procedure, SOPEP equipment.',
                'current_conditions'   => 'Night (0230), wind NE 5 knots (light/variable), cargo discharge 85% complete, gas alarm 15% LEL at ML-03, unauthorized hot work 30m from manifold, terminal fire monitors not pressurized, ESD link active but terminal-side button slow (3.2 sec vs 1 sec required), neighboring berth STS operation 150m away, wind pushing potential gas cloud toward accommodation.',
            ],
            'tr' => [
                'situation'           => 'Bir LNG terminalinde yükleme iskelesi yanında LNGC\'nin kaptanısınız, kargo boşaltma devam ediyor — %85 tamamlanmış, 4 tank 2 sıvı manifold ve 1 buhar dönüş hattıyla boşaltılıyor. 0230\'da manifold bölgesinde ML-03 gaz dedektörü alarmı tetikleniyor — %15 LEL gösteriyor. Rüzgar hafif ve değişken 5 knot, şu anda KB\'den; manifold bölgesinden çıkacak gaz bulutu iskele kıç tarafındaki yaşam mahalli ve mutfak egzoz fanlarına doğru sürüklenecektir. Aynı anda, bostromo tur sırasında manifold bağlantı alanından yaklaşık 30 metre kıç tarafta güverte deposu braketine izinsiz kaynak yapan iki kıyı bakım personeli keşfettiğini bildiriyor. Çalışma İzni verilmemiş, gaz testi yapılmamış, terminal bilgilendirilmemiş. Terminal iskele yangın monitörleri bekleme pozisyonunda ancak basınçlı değil. Köprüdeki ESD paneli "ESD Link Aktif" gösteriyor, ancak vardiya değişiminde terminal tarafı ESD butonu 3,2 saniyelik yavaş yanıt vermiş (gereken maksimum 1 saniye). Terminal operatörü "soğuk hava aktüatörü yavaşlatıyor" diye geçiştirmiş. Komşu iskele, 150 metre uzaklıkta, gemiden gemiye (STS) transfer yapan bir ürün tankeri barınıyor.',
                'your_position'       => 'Köprüüstü, kargo izleme. Vardiya zabiti manifold bölgesinde, bostromo güverte turunda, kargo mühendisi kargo kontrol odasında, terminal operatörü terminal kontrol odasında.',
                'available_resources'  => 'Gemi ESD sistemi, gemi/kıyı ESD bağlantısı, sabit gaz tespit sistemi (ML-01 ile ML-06 arası manifold sensörleri, güverte ve yaşam mahalli sensörleri), portatif gaz dedektörleri, gemi yangın söndürme ekipmanları (su spreyi, kuru toz, köpük), terminal yangın monitörleri (basınçsız), gemi yangın pompaları (çalışır), PA/GA sistemi, VHF (terminal ve komşu iskele), uzaktan valf operasyonlu kargo kontrol odası, şirket acil müdahale prosedürü, SOPEP.',
                'current_conditions'   => 'Gece (0230), rüzgar KB 5 knot, kargo %85, gaz alarmı %15 LEL ML-03, izinsiz sıcak çalışma manifolddan 30m, terminal monitörleri basınçsız, ESD bağlantısı aktif ama terminal butonu yavaş (3,2 sn), komşu iskelede STS, rüzgar gaz bulutunu yaşam mahalline doğru itiyor.',
            ],
            'ru' => [
                'situation'           => 'Вы капитан СПГ-танкера у терминала, выгрузка 85%. В 02:30 сработал датчик газа ML-03 у манифолда — 15% НКПР. Ветер NE 5 узлов, сносит возможное облако к надстройке. Боцман обнаружил двух береговых рабочих, выполняющих несанкционированную сварку в 30 м от манифолда — без наряд-допуска, без газовых замеров. Терминальные пожарные мониторы не под давлением. Панель ESD показывает "Link Active", но терминальная кнопка ESD при тесте дала задержку 3,2 сек (норма <1 сек). Соседний причал — продуктовый танкер на STS-операции в 150 м.',
                'your_position'       => 'Мостик. Вахтенный помощник — у манифолда, боцман — на обходе, грузовой инженер — в грузовом посту.',
                'available_resources'  => 'ESD (судовая + судно-берег), стационарная газодетекция (ML-01–06), переносные детекторы, водяная завеса/порошок/пена, терминальные мониторы (не под давлением), пожарные насосы (работают), ГГС, УКВ, грузовой пост с дистанционным управлением клапанами, аварийная процедура, SOPEP.',
                'current_conditions'   => 'Ночь 02:30, ветер NE 5, выгрузка 85%, газ 15% НКПР (ML-03), несанкционированная сварка 30 м от манифолда, мониторы не под давлением, ESD-кнопка 3,2 сек, STS у соседнего причала.',
            ],
            'az' => [
                'situation'           => 'LNG terminalında LNGC-nin kapitanısınız, boşaltma 85% tamamlanıb. 02:30-da manifold zonasında ML-03 qaz detektoru 15% AKAH göstərir. Külək NE 5 knot — qaz buludu yaşayış blokuna sürüklənə bilər. Bosman manifolddan 30 m-də icazəsiz qaynaq işi aparan 2 sahil işçisi aşkar edib — iş icazəsi yox, qaz testi yox. Terminal yanğın monitorları təzyiqsiz. ESD paneli "Link Active" göstərir, amma terminal ESD düyməsi 3,2 san cavab verir (norma <1 san). Qonşu rıhtımda 150 m-də STS əməliyyatı.',
                'your_position'       => 'Körpüüstü. Növbətçi zabit manifoldda, bosman göyərtədə, yük mühəndisi yük postunda.',
                'available_resources'  => 'ESD (gəmi + gəmi-sahil), stasionar qaz deteksiyası (ML-01–06), portativ detektorlar, yanğınsöndürmə, terminal monitorları (təzyiqsiz), yanğın nasosları (işləyir), səsucaldan, VHF, yük postu, təcili prosedur.',
                'current_conditions'   => 'Gecə 02:30, NE 5 knot, boşaltma 85%, qaz 15% AKAH, icazəsiz qaynaq 30 m, monitorlar təzyiqsiz, ESD düyməsi 3,2 san, qonşu STS 150 m.',
            ],
        ],
        'decision_prompt'      => 'Describe your immediate response to this situation. Address: your decision on activating the ESD given the gas detection alarm and its reliability concerns, how you deal with the unauthorized hot work as an immediate ignition source, your gas hazard management strategy considering the wind direction toward accommodation, and your coordination with the terminal and neighboring berth regarding the emergency.',
        'decision_prompt_i18n' => [
            'tr' => 'Bu duruma acil müdahalenizi açıklayın. Ele alın: gaz tespit alarmı ve güvenilirlik endişeleri göz önünde ESD aktivasyonu kararınız, acil tutuşma kaynağı olarak izinsiz sıcak çalışmayla nasıl başa çıktığınız, rüzgarın yaşam mahalline yöneldiği göz önünde gaz tehlikesi yönetim stratejiniz, terminal ve komşu iskele ile acil durum koordinasyonunuz.',
            'ru' => 'Опишите немедленные действия: решение об активации ESD с учётом тревоги и проблем надёжности, действия по несанкционированной сварке как источнику зажигания, стратегия управления газовой опасностью с учётом ветра к надстройке, координация с терминалом и соседним причалом.',
            'az' => 'Dərhal reaksiyanızı təsvir edin: qaz alarmı və etibarlılıq narahatlıqları ilə ESD aktivasiya qərarı, dərhal alovlanma mənbəyi kimi icazəsiz qaynaqla mübarizə, küləyin yaşayış blokuna yönəldiyi qaz təhlükəsi strategiyası, terminal və qonşu rıhtımla koordinasiya.',
        ],
        'evaluation_axes_json' => [
            [
                'axis'   => 'immediate_response_esd',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Does not activate ESD despite gas detection alarm. Attempts to investigate the gas alarm first before taking protective action. Does not recognise the combination of gas detection + active ignition source as requiring immediate emergency shutdown.',
                    '2' => 'Recognises the seriousness but delays ESD activation to "confirm the reading" or "check with the terminal." Addresses the hot work and gas alarm as separate issues rather than recognising the combined catastrophic risk.',
                    '3' => 'Activates ship\'s ESD immediately upon confirmation of 15% LEL gas detection combined with active ignition source nearby. Initiates emergency cargo shutdown via ship\'s ESD system. Sounds general alarm. Directs crew to emergency stations. Contacts terminal control to confirm ESD link has activated on their side.',
                    '4' => 'Decisive ESD response: immediately activates ship\'s ESD upon receiving confirmed gas alarm + hot work report, recognising this as a simultaneous gas release + ignition source emergency requiring zero delay. Does not rely solely on the ship/shore ESD link (which showed sluggish response) — activates ship-side ESD and simultaneously orders terminal control to activate their ESD independently. Sounds general alarm, musters crew, establishes fire watch with dry powder and water spray standby at manifold. Verifies via cargo control room that all cargo valves are closing and cargo pumps stopping. Monitors all gas detectors for spread of gas cloud.',
                    '5' => 'Expert emergency response: instant ESD activation without hesitation — recognises that 15% LEL is already in the flammable range context (gas detectors read % of LEL, so 15% LEL means 0.75% methane by volume — below LEL but indicating a significant release, and localized concentrations near the leak source could be at or above LEL). Activates ship\'s ESD and simultaneously radios terminal control with the emergency protocol "Emergency shutdown activated, gas detection at manifold, request you activate terminal ESD independently and pressurize fire monitors immediately." Addresses the known ESD link sluggishness by not relying on automatic link activation — ensures both ship and shore ESD operate independently. Sounds general alarm and PA announcement with specific muster instructions (crew to muster away from gas cloud drift direction — i.e., forward/starboard, not aft port quarter). Directs cargo engineer to verify all manifold ESD valves closing on the cargo control room display (cross-checking each valve status). Immediately contacts the neighboring berth STS operation to alert them to the emergency and request they prepare for potential gas cloud migration. Considers whether to activate the emergency fire pump to pressurize ship\'s water spray system in the manifold area for gas dispersion, while being mindful that water spray near an LNG spill can increase vaporization rate.',
                ],
            ],
            [
                'axis'   => 'gas_hazard_management',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'No awareness of gas cloud behavior or wind effects. Does not monitor gas spread. Does not consider the accommodation block ventilation or the neighboring STS operation.',
                    '2' => 'Recognises the wind direction issue but does not take specific actions to manage gas spread. Does not shut down accommodation ventilation or address the neighboring vessel.',
                    '3' => 'Manages gas hazard: monitors all gas detectors for cloud migration, orders accommodation ventilation shut to prevent gas ingress (closes fire dampers), deploys crew with portable gas detectors to track the cloud boundary, notifies the neighboring berth of the gas release, establishes an exclusion zone around the manifold area.',
                    '4' => 'Comprehensive gas hazard management: immediately orders accommodation ventilation shut down and fire dampers closed (wind pushing gas toward accommodation — this is the primary personnel safety risk), deploys portable gas detectors to establish the gas cloud boundary and movement, orders all non-essential personnel to remain indoors in sealed accommodation, establishes exclusion zone around manifold area (minimum 25m radius per hazardous area classification), monitors gas detectors ML-01 through ML-06 and deck sensors for cloud tracking. Contacts neighboring berth STS operation to warn of gas release and recommend they cease transfer operations and prepare for emergency. Requests terminal to pressurize fire monitors immediately. Assesses whether the gas detection indicates a manifold leak, hose failure, or other source.',
                    '5' => 'Expert gas hazard management: layered response to the gas cloud threat — immediate accommodation protection (all ventilation stopped, fire dampers sealed, galley exhaust fans secured — these are ignition sources), all non-essential personnel mustered in sealed forward spaces away from drift direction, portable gas detectors deployed in a systematic grid pattern to map the cloud boundary and track movement. Analyses the gas detection reading: 15% LEL at ML-03 — reviews ML-03 location relative to manifold connections to identify probable leak source (flange, hose connection, drip tray). Considers that methane is lighter than air and will rise and disperse — but in light wind conditions (5 knots) horizontal drift toward accommodation is the primary concern. Requests terminal to immediately pressurize fire monitors and establish water curtain between manifold area and accommodation to knock down the gas cloud. Contacts neighboring berth with specific warning: gas release at own berth, wind direction, and recommendation to cease STS transfer immediately (product tanker cargo is also flammable — a gas cloud reaching their operation creates a secondary ignition risk). Considers IEC 60079 hazardous area classification: the manifold area is Zone 1, but a gas release extends the hazardous area beyond normal boundaries — all ignition sources within the expanded zone must be identified and eliminated. Documents all gas detector readings with timestamps for incident investigation.',
                ],
            ],
            [
                'axis'   => 'permit_to_work_enforcement',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'Does not address the unauthorized hot work as an immediate emergency priority. Treats it as an administrative issue to be dealt with later. Does not recognise welding near manifold during cargo operations as a catastrophic risk.',
                    '2' => 'Directs the bosun to stop the welding but does not escalate it as an emergency. Does not recognise that the welding set itself is an ongoing ignition source even after work stops (hot metal, welding leads energized).',
                    '3' => 'Immediately orders the bosun to stop all welding and remove the personnel from the area. Secures the welding equipment (de-energize). Recognises this as a critical PTW failure. Reports to terminal as a serious safety breach. Initiates investigation into how unauthorized hot work was possible during cargo operations.',
                    '4' => 'Decisive hot work response: orders immediate cessation of welding via the most rapid communication available (radio to bosun: "Stop welding immediately, gas detection alarm active, emergency"), directs bosun to de-energize the welding set and remove it from the cargo area, ensures the shore maintenance crew is escorted to a safe location. Recognises multiple PTW failures: no permit issued, no gas testing, unsecured welding equipment, shore crew access to cargo area without authorization, no terminal notification. Escalates to terminal as a critical safety breach requiring formal investigation. Secures the area as a potential crime scene for investigation. Reviews ship\'s hot work/PTW procedures to determine how the failure occurred — was the welding set not locked out? Was the deck store not secured? Were shore personnel not escorted?',
                    '5' => 'Expert PTW enforcement and investigation: treats the unauthorized hot work as the most critical immediate threat (active ignition source 30m from a gas leak on an LNG carrier is a potential catastrophic event). Orders immediate cessation with maximum urgency, then conducts a systematic assessment: (1) welding set de-energized and isolated at the switch, not just turned off, (2) welding area inspected for residual hot spots — metal that was being welded may still be above auto-ignition temperature of methane (537°C), direct the bosun to cool the area with water, (3) shore crew identified, removed from cargo area, and their access documented, (4) formal notification to terminal safety officer that a critical PTW violation has occurred during cargo operations — this is a SIGTTO and ISGOTT reportable event. Conducts root cause analysis of the PTW failure: the ship\'s welding set should be locked out during cargo operations per company procedures and IGC Code — how was it accessed? Were ship\'s lock-out/tag-out procedures followed? Were shore personnel given unescorted access to the cargo area? This represents a systemic safety management failure requiring a formal investigation report to the company, terminal, and potentially the flag state. Documents the incident comprehensively including photographs, personnel statements, and timeline. Following the emergency, conducts a formal review with all crew to reinforce the absolute prohibition of hot work during LNG cargo operations (zero tolerance) and the importance of the PTW system as a life-critical barrier.',
                ],
            ],
        ],
        'critical_omission_flags_json' => [
            ['flag' => 'No immediate ESD activation despite gas detection and active ignition source',         'severity' => 'critical'],
            ['flag' => 'No immediate action to stop unauthorized hot work near manifold',                      'severity' => 'critical'],
            ['flag' => 'No accommodation ventilation shutdown with gas cloud drifting toward living spaces',    'severity' => 'critical'],
            ['flag' => 'No notification to neighboring berth STS operation about gas release',                  'severity' => 'major'],
            ['flag' => 'No recognition that terminal ESD button sluggishness is a safety-critical deficiency',  'severity' => 'major'],
            ['flag' => 'No formal reporting of unauthorized hot work as a critical PTW/safety breach',          'severity' => 'major'],
        ],
        'expected_references_json' => [
            'IGC Code Chapter 11 — Fire protection and fire extinction',
            'SIGTTO "ESD Arrangements and Linked Ship/Shore Systems"',
            'ISGOTT Chapter 26 — Ship/shore safety checklist and cargo operations safety',
            'OCIMF "Ship/Shore Interface" guidelines',
            'Company Permit to Work procedure and hot work policy',
            'IEC 60079 — Hazardous area classification for explosive gas atmospheres',
            'SOLAS Chapter II-2 — Fire protection, fire detection and fire extinction',
        ],
        'red_flags_json' => [
            'Failing to activate ESD when gas detection alarm and ignition source coexist',
            'Not immediately stopping unauthorized welding during LNG cargo operations',
            'Ignoring gas cloud drift toward accommodation without securing ventilation',
            'Accepting terminal explanation for sluggish ESD response without formal challenge',
            'Not alerting neighboring berth STS operation to the gas emergency',
            'Treating unauthorized hot work during LNG operations as a minor administrative issue',
        ],
    ],

];

    }

    private function getScenariosSlot5to8(): array
    {
return [
    'LNG_S05_CREW_001' => [
        'briefing_json' => [
            'en' => [
                'situation' => 'Your LNGC is alongside the terminal in the final stages of cargo loading. Tank levels are at 98% and the topping-off phase is about to commence, requiring precise monitoring with auto-shutdown configured at 98.5%. The CCR (Cargo Control Room) operator, your 3rd Officer, has been on continuous watch for 11 hours because the relief officer fell ill and no qualified replacement is available. You have observed the 3/O showing clear signs of fatigue — slow responses to routine communications, a missed log entry at the last half-hour mark, and slight disorientation when reading the cargo monitoring display. The deck team preparing for the manifold line-up change from liquid to vapor return consists of a multinational crew: a Filipino bosun, an Indian AB, and a Ukrainian pumpman. UHF radio communication between CCR and deck has become intermittent over the past hour, with the bosun resorting to hand signals across the 45-metre deck distance during the last two valve operations. A review of the previous port\'s loading incident report reveals a near-miss where the same 3/O missed a critical tank level alarm during topping-off operations due to alarm fatigue — 47 alarms had sounded in a two-hour period. The terminal is loading at 12,000 m³/hr and the terminal supervisor has requested maintaining this rate to meet the port schedule, with the pilot boat booked for departure in 6 hours.',
                'your_position' => 'You are the Chief Officer (C/O) responsible for cargo operations, currently in the CCR overseeing the topping-off preparation. The Master is in his cabin resting after handling a port state inspection earlier today.',
                'available_resources' => 'One additional qualified officer (2/O) currently off-watch and resting in cabin. Backup handheld VHF radios in the bridge locker. Cargo operations manual with topping-off checklists. Ship-to-shore telephone link to terminal control room. Alarm management system with ability to suppress non-critical alarms. CCTV coverage of manifold area and deck. One additional AB available from the engine department (dual-certified).',
                'current_conditions' => 'Nighttime, 22:30 local. Calm weather, wind SW 8 knots. Terminal berth with shore gangway connected. Ambient temperature 14°C. Cargo: LNG (methane), loading temperature -162°C. Tank pressures stable at 120 mbar. Vapor return line operating normally. No cargo alarms currently active but alarm system is generating routine advisory notifications at a rate of approximately 15 per hour.'
            ],
            'tr' => [
                'situation' => 'LNG tankeriniz terminal yanaşık durumda ve yüklemenin son aşamasındadır. Tank seviyeleri %98\'e ulaşmış olup, otomatik kapanmanın %98,5\'te ayarlandığı hassas izleme gerektiren topping-off aşaması başlamak üzeredir. CCR (Kargo Kontrol Odası) operatörü olan 3. Zabitiniz, yedek zabitin hastalanması ve kalifiye bir yedek bulunamaması nedeniyle 11 saattir kesintisiz vardiya tutmaktadır. 3. Zabitte belirgin yorgunluk belirtileri gözlemlediniz — rutin iletişimlere geç yanıt verme, son yarım saatlik kontrol noktasında kaçırılmış bir seyir defteri kaydı ve kargo izleme ekranını okurken hafif dikkatsizlik. Manifold hattı değişikliği için güvertede hazırlık yapan tim çok uluslu bir mürettebattan oluşmaktadır: Filipinli bir lostromo, Hintli bir gemici ve Ukraynalı bir pompacı. CCR ile güverte arasındaki UHF telsiz iletişimi son bir saattir kesintili hale gelmiş, lostromo son iki valf operasyonunda 45 metrelik güverte mesafesinde el işaretlerine başvurmuştur. Önceki limandaki yükleme olay raporunun incelenmesi, aynı 3. Zabitin topping-off operasyonları sırasında alarm yorgunluğu nedeniyle kritik bir tank seviye alarmını kaçırdığı bir ramak kala olayını ortaya koymaktadır — iki saatlik sürede 47 alarm çalmıştı. Terminal 12.000 m³/saat hızla yükleme yapıyor ve terminal sorumlusu liman programını karşılamak için bu hızın korunmasını talep etmiştir; kılavuz botu 6 saat sonra kalkış için ayarlanmıştır.',
                'your_position' => 'Kargo operasyonlarından sorumlu Birinci Zabit (C/O) olarak CCR\'de topping-off hazırlığını denetlemektesiniz. Kaptan, bugün daha erken saatlerde liman devleti denetimini yönettikten sonra kamarasında dinlenmektedir.',
                'available_resources' => 'Vardiya dışında kamarasında dinlenen bir ek kalifiye zabit (2. Zabit). Köprü dolabında yedek el tipi VHF telsizler. Topping-off kontrol listelerini içeren kargo operasyonları el kitabı. Terminal kontrol odasına gemi-kıyı telefon bağlantısı. Kritik olmayan alarmları bastırma özellikli alarm yönetim sistemi. Manifold alanı ve güvertenin CCTV kapsamı. Makine departmanından ek bir gemici (çift sertifikalı).',
                'current_conditions' => 'Gece vakti, yerel saat 22:30. Sakin hava, rüzgar GB 8 knot. Terminal iskelesi, kıyı bağlantısı kurulu. Ortam sıcaklığı 14°C. Kargo: LNG (metan), yükleme sıcaklığı -162°C. Tank basınçları 120 mbar\'da stabil. Buhar dönüş hattı normal çalışıyor. Şu anda aktif kargo alarmı yok ancak alarm sistemi saatte yaklaşık 15 rutin bilgi bildirimi üretmektedir.'
            ],
            'ru' => [
                'situation' => 'Ваш СПГ-танкер у терминала на завершающем этапе погрузки. Уровень танков — 98%, начинается фаза доливки с автоматическим отключением при 98,5%. Оператор ЦУГ (3-й помощник) на вахте уже 11 часов из-за болезни сменщика, демонстрирует явные признаки усталости. Палубная команда — многонациональная (боцман-филиппинец, матрос-индиец, моторист-украинец), УВЧ-связь ЦУГ-палуба работает с перебоями, боцман прибегает к жестам на расстоянии 45 м. Предыдущий рапорт о происшествии показал, что 3-й помощник пропустил аварийную сигнализацию уровня танка из-за усталости от алармов — 47 срабатываний за 2 часа.',
                'your_position' => 'Вы — старший помощник капитана, отвечающий за грузовые операции, находитесь в ЦУГ. Капитан отдыхает в каюте.',
                'available_resources' => 'Один свободный вахтенный офицер (2-й помощник), запасные портативные УКВ-рации, контрольные листы по доливке, судно-береговая связь с терминалом, система управления алармами, камеры видеонаблюдения.',
                'current_conditions' => 'Ночь, 22:30 по местному времени. Штиль, ветер ЮЗ 8 узлов. Температура 14°C. Груз: СПГ (метан), -162°C. Давление в танках 120 мбар, стабильно.'
            ],
            'az' => [
                'situation' => 'LNG tankeriniz terminalda yüklənmənin son mərhələsindədir. Tank səviyyələri 98%-ə çatıb, 98,5%-də avtomatik bağlanma ilə topping-off mərhələsi başlamaq üzrədir. ÜKO (Yük Nəzarət Otağı) operatoru (3-cü zabit) əvəzedici zabitin xəstəliyinə görə 11 saatdır növbədədir və yorğunluq əlamətləri göstərir. Göyərtə komandası çoxmillətli tərkibdədir, UHF radio rabitəsi kəsintilidir, lostromo 45 m məsafədə əl işarələrinə keçib. Əvvəlki limanda 3-cü zabit alarm yorğunluğu səbəbindən tank səviyyə xəbərdarlığını qaçırmışdı.',
                'your_position' => 'Siz yük əməliyyatlarına cavabdeh olan Baş Zabitsiniz (C/O), hazırda ÜKO-da topping-off hazırlığına nəzarət edirsiniz. Kapitan kamarasında istirahət edir.',
                'available_resources' => 'Növbədən kənar bir zabit (2-ci zabit), ehtiyat VHF radioları, yüklənmə prosedur kitabçası, terminal ilə telefon əlaqəsi, alarm idarəetmə sistemi, CCTV kameraları.',
                'current_conditions' => 'Gecə, yerli vaxt 22:30. Sakit hava, külək CQ 8 düyün. Temperatur 14°C. Yük: LNG (metan), -162°C. Tank təzyiqləri 120 mbar, stabil.'
            ]
        ],
        'decision_prompt' => 'As Chief Officer overseeing topping-off operations, what immediate actions do you take to address the fatigued CCR operator, the communication breakdown with the deck team, and the terminal\'s request to maintain loading rate? Detail your crew resource management decisions, communication recovery plan, and how you will ensure safe completion of the topping-off phase.',
        'decision_prompt_i18n' => [
            'tr' => 'Topping-off operasyonlarını denetleyen Birinci Zabit olarak, yorgun CCR operatörü, güverte ekibiyle iletişim kopukluğu ve terminalin yükleme hızını koruma talebi konularında hangi acil önlemleri alırsınız? Mürettebat kaynak yönetimi kararlarınızı, iletişim kurtarma planınızı ve topping-off aşamasının güvenli tamamlanmasını nasıl sağlayacağınızı ayrıntılı olarak açıklayın.',
            'ru' => 'Как старший помощник, руководящий операцией доливки, какие немедленные действия вы предпримете в отношении уставшего оператора ЦУГ, нарушения связи с палубной командой и запроса терминала на поддержание скорости погрузки? Опишите ваши решения по управлению ресурсами экипажа, план восстановления связи и обеспечение безопасного завершения доливки.',
            'az' => 'Topping-off əməliyyatlarına nəzarət edən Baş Zabit olaraq, yorğun ÜKO operatoru, göyərtə komandası ilə rabitə kəsilməsi və terminalın yüklənmə sürətini saxlamaq tələbi ilə bağlı hansı təcili tədbirləri görürsünüz? Ekipaj resurs idarəetmə qərarlarınızı, rabitə bərpa planınızı və topping-off mərhələsinin təhlükəsiz başa çatdırılmasını necə təmin edəcəyinizi ətraflı izah edin.'
        ],
        'evaluation_axes_json' => [
            [
                'axis' => 'crew_resource_management',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Fails to recognize fatigue as a risk factor; leaves 3/O in position without intervention; does not consider calling additional personnel or informing the Master.',
                    '2' => 'Recognizes 3/O fatigue but only provides verbal encouragement; does not relieve or supplement the watch; minimal consideration of STCW rest hour implications.',
                    '3' => 'Relieves or supplements the 3/O by calling the 2/O to CCR; informs the Master of the situation; assigns clear roles for topping-off but does not fully address multinational crew coordination challenges.',
                    '4' => 'Immediately relieves the 3/O, calls 2/O to CCR with proper handover briefing; informs Master; assigns specific topping-off duties considering individual crew competencies; establishes clear chain of command for the operation.',
                    '5' => 'Comprehensive CRM approach: relieves 3/O with documented handover, calls 2/O with structured briefing on tank levels and alarm status, wakes Master with full situation report, assigns roles based on crew competency assessment, conducts toolbox talk with deck team addressing language barriers, establishes dedicated lookout for tank overflow indicators, and documents STCW rest hour violation for reporting.'
                ]
            ],
            [
                'axis' => 'fatigue_management_decision',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'Ignores fatigue indicators; allows 3/O to continue as sole CCR operator during the most critical phase; prioritizes terminal schedule over crew fitness.',
                    '2' => 'Acknowledges fatigue but delays action; suggests 3/O take a short break after topping-off completes; does not address systemic fatigue risk or alarm fatigue from previous near-miss.',
                    '3' => 'Removes 3/O from primary CCR role; arranges relief; considers reducing loading rate to extend time margin but does not address the alarm fatigue issue from the previous near-miss report.',
                    '4' => 'Immediately removes 3/O from watch, arranges qualified relief, requests terminal to reduce loading rate for safety margin; addresses alarm management by prioritizing critical alarms for topping-off phase; logs the fatigue event.',
                    '5' => 'Removes 3/O with immediate effect and ensures rest; arranges qualified relief with competency verification; requests loading rate reduction and communicates clear safety rationale to terminal; implements alarm rationalization — suppresses non-critical alarms and configures staged high-level warnings; references previous near-miss as justification; initiates fatigue incident documentation per ISM Code and company SMS; plans post-operation review of watch relief procedures.'
                ]
            ],
            [
                'axis' => 'communication_protocols',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Ignores the communication breakdown; continues operations with intermittent radio and hand signals; no attempt to establish reliable CCR-deck communication before topping-off.',
                    '2' => 'Recognizes communication problem but only switches to a single backup channel; does not verify understanding with multinational deck team; no closed-loop communication protocol established.',
                    '3' => 'Distributes backup VHF radios to deck team; establishes a primary and secondary communication channel; conducts radio check before topping-off but does not address language barrier challenges specifically.',
                    '4' => 'Deploys backup radios, establishes primary and backup channels with dedicated topping-off frequency; conducts pre-operation communication check with each deck team member; uses simplified and standardized commands addressing language differences; establishes phone backup via ship-shore line.',
                    '5' => 'Full communication recovery: deploys tested backup radios to all deck positions, establishes dedicated topping-off channel with no cross-traffic, conducts individual radio checks with each multinational crew member using IMO Standard Marine Communication Phrases, implements closed-loop repeat-back protocol for all valve operations, positions CCTV as visual backup, establishes pre-agreed hand signal protocol as last resort with documented meanings, briefs terminal control room on communication plan, and halts operation if two-way communication cannot be confirmed at any point.'
                ]
            ]
        ],
        'critical_omission_flags_json' => [
            'Failed to relieve the fatigued 3/O before commencing topping-off phase',
            'Did not establish reliable two-way communication between CCR and deck before starting topping-off',
            'Did not inform the Master of the combined fatigue and communication risks',
            'Failed to address or reference the previous near-miss incident and alarm fatigue concern',
            'Did not request or consider reducing the loading rate to provide additional safety margin',
            'Failed to verify STCW rest hour compliance and document the violation'
        ],
        'expected_references_json' => [
            'STCW Convention — rest hour requirements (Section A-VIII/1)',
            'MLC 2006 — hours of rest provisions (Regulation 2.3)',
            'ISM Code Section 6 — Resources and Personnel',
            'SIGTTO "LNG Ship to Shore Interface" — topping-off procedures',
            'OCIMF TMSA Element 3 — Manning and Competence',
            'Company fatigue management policy and SMS procedures',
            'SIGTTO "Crew Competence and Training in LNG Cargo Operations"'
        ],
        'red_flags_json' => [
            'Decides to let 3/O continue as sole CCR operator during topping-off because "we are almost done"',
            'Agrees to maintain 12,000 m³/hr loading rate without addressing fatigue and communication deficiencies',
            'Proceeds with topping-off using hand signals as the primary communication method across 45m deck distance',
            'Fails to call any additional personnel and attempts to manage CCR and deck supervision single-handedly',
            'Dismisses the previous near-miss report as irrelevant to current operations',
            'Prioritizes terminal departure schedule over crew safety and proper watch relief'
        ]
    ],

    'LNG_S06_AUTO_001' => [
        'briefing_json' => [
            'en' => [
                'situation' => 'Your LNGC is alongside a discharge terminal preparing for departure. The vessel is equipped with an Integrated Automation System (IAS) that controls cargo, ballast, and inert gas operations simultaneously through a centralized control platform. During routine ballast discharge in preparation for sailing, the IAS has unexpectedly switched the cargo tank #3 and #4 spray pumps to "AUTO COOLDOWN" mode without any operator command. Investigation reveals this is a mode conflict triggered by a software flag error introduced during a recent system update carried out by the IAS vendor at the last dry-dock. The spray pumps are now circulating LNG through the spray headers while the main cargo pumps are still running in discharge mode, causing a conflict in tank pressure management. Cargo tank pressures are rising — currently at 230 mbar and increasing at approximately 8 mbar per minute, with the relief valve setting at 250 mbar, giving you less than 3 minutes before potential relief valve lifting. The CCR alarm system has flooded with over 60 alarms in 90 seconds, making it extremely difficult to identify critical versus advisory alerts. The 2nd Officer on cargo watch joined the vessel only 3 weeks ago and, while trained in manual valve operations, has limited experience with IAS troubleshooting and has never encountered a mode conflict scenario. The IAS vendor engineer is ashore but can be reached by phone. The terminal has noticed an increase in vapor return line pressure and is querying the vessel about the anomaly. Manual override of the spray pump system requires physical valve operations at deck level — specifically 8 cryogenic valves across 4 tanks that must be operated in a specific sequence.',
                'your_position' => 'You are the Chief Officer (C/O) and have just arrived in the CCR after being alerted by the 2/O about the rising tank pressures. The Master is on the bridge preparing for departure.',
                'available_resources' => 'One additional officer (3/O) available in accommodation. Bosun and two ABs on standby for mooring operations. IAS vendor engineer contactable by phone. Cargo operations manual with manual override procedures. Emergency cargo shutdown (ESD) system independent of IAS. Tank pressure gauges (independent analog backup) at each tank dome. CCTV on deck. Ship-to-shore communication with terminal.',
                'current_conditions' => 'Daytime, 14:00 local. Clear weather, light winds. Alongside terminal, gangway connected. Cargo tanks #1 and #2 discharged to heel quantity (2%), tanks #3 and #4 at 35% and discharging. Tank pressures: #1 — 80 mbar, #2 — 85 mbar, #3 — 230 mbar (rising), #4 — 228 mbar (rising). Spray pump status: #3 and #4 running in AUTO COOLDOWN (unauthorized). Main cargo pump #3 and #4 running in discharge mode. Relief valve setting: 250 mbar.'
            ],
            'tr' => [
                'situation' => 'LNG tankeriniz tahliye terminalinde yanaşık durumda ve kalkışa hazırlanmaktadır. Gemi, kargo, balast ve inert gaz operasyonlarını merkezi bir kontrol platformu üzerinden eş zamanlı olarak kontrol eden Entegre Otomasyon Sistemi (IAS) ile donatılmıştır. Kalkış hazırlığı kapsamında rutin balast tahliyesi sırasında, IAS herhangi bir operatör komutu olmaksızın #3 ve #4 kargo tankı sprey pompalarını beklenmedik şekilde "OTOMATİK SOĞUTMA" moduna geçirmiştir. Araştırma, bunun son havuz bakımında IAS sağlayıcısı tarafından yapılan sistem güncellemesinden sonra ortaya çıkan bir yazılım bayrak hatası nedeniyle oluşan bir mod çakışması olduğunu ortaya koymaktadır. Sprey pompaları şu anda ana kargo pompaları tahliye modunda çalışmaya devam ederken LNG\'yi sprey başlıkları üzerinden dolaştırmakta ve tank basınç yönetiminde çakışma yaratmaktadır. Kargo tank basınçları yükselmektedir — şu anda 230 mbar, dakikada yaklaşık 8 mbar artışla, emniyet valfi ayarı 250 mbar\'da olup, olası emniyet valfi açılmasına 3 dakikadan az süre kalmıştır. CCR alarm sistemi 90 saniyede 60\'tan fazla alarmla dolmuş, kritik ve bilgilendirici uyarıların ayırt edilmesini son derece zorlaştırmıştır. Kargo vardiyasındaki 2. Zabit gemiye sadece 3 hafta önce katılmış olup, manuel valf operasyonlarında eğitimli olmakla birlikte IAS sorun giderme deneyimi sınırlıdır ve daha önce hiç mod çakışması senaryosuyla karşılaşmamıştır. IAS sağlayıcı mühendis karada ancak telefonla ulaşılabilir durumdadır. Terminal, buhar dönüş hattı basıncında artış fark etmiş ve gemiyi sorgulamaktadır. Manuel müdahale, güverte seviyesinde fiziksel valf operasyonları gerektirmektedir — 4 tank boyunca belirli bir sırayla çalıştırılması gereken 8 kriyojenik valf.',
                'your_position' => 'Birinci Zabit (C/O) olarak, yükselen tank basınçları hakkında 2. Zabit tarafından uyarıldıktan sonra CCR\'ye geldiniz. Kaptan köprüde kalkış hazırlıkları yapmaktadır.',
                'available_resources' => 'Kamarasında mevcut bir ek zabit (3. Zabit). Palamar operasyonları için hazırda bekleyen lostromo ve iki gemici. Telefonla ulaşılabilir IAS sağlayıcı mühendis. Manuel müdahale prosedürlerini içeren kargo operasyonları el kitabı. IAS\'tan bağımsız acil kargo kapatma (ESD) sistemi. Her tank kubbesinde bağımsız analog yedek tank basınç göstergeleri. Güvertede CCTV. Terminal ile gemi-kıyı iletişimi.',
                'current_conditions' => 'Gündüz, yerel saat 14:00. Açık hava, hafif rüzgar. Terminal yanaşık, iskele bağlı. Kargo tankları #1 ve #2 dip miktarına tahliye edilmiş (%2), #3 ve #4 %35\'te ve tahliye devam ediyor. Tank basınçları: #1 — 80 mbar, #2 — 85 mbar, #3 — 230 mbar (yükseliyor), #4 — 228 mbar (yükseliyor). Sprey pompa durumu: #3 ve #4 OTOMATİK SOĞUTMA\'da çalışıyor (yetkisiz). Ana kargo pompa #3 ve #4 tahliye modunda çalışıyor. Emniyet valfi ayarı: 250 mbar.'
            ],
            'ru' => [
                'situation' => 'Ваш СПГ-танкер у терминала, готовится к отходу. Интегрированная система автоматизации (ИСА) неожиданно переключила спрей-насосы танков №3 и №4 в режим «АВТООХЛАЖДЕНИЕ» без команды оператора — конфликт режимов из-за программной ошибки после недавнего обновления. Давление в танках растёт: 230 мбар при уставке предохранительного клапана 250 мбар, скорость роста ~8 мбар/мин. Система аварийной сигнализации выдала более 60 алармов за 90 секунд. 2-й помощник на грузовой вахте имеет ограниченный опыт работы с ИСА. Ручное управление требует операций с 8 криогенными клапанами на палубе.',
                'your_position' => 'Вы — старший помощник, прибыли в ЦУГ по вызову 2-го помощника. Капитан на мостике.',
                'available_resources' => 'Дополнительный офицер (3-й помощник), боцман и два матроса, инженер ИСА на берегу (доступен по телефону), руководство по ручному управлению, независимая система ESD, аналоговые манометры на каждом танке, CCTV, связь с терминалом.',
                'current_conditions' => 'День, 14:00 местного. Ясно, слабый ветер. Танки №3 и №4 — 35%, давление 230/228 мбар (растёт). Спрей-насосы №3 и №4 работают в несанкционированном режиме. Уставка предохранительного клапана: 250 мбар.'
            ],
            'az' => [
                'situation' => 'LNG tankeriniz terminal yanında boşaltma əməliyyatını tamamlayıb, yola çıxmağa hazırlaşır. İnteqrasiya Olunmuş Avtomatlaşdırma Sistemi (İAS) operator komandasız №3 və №4 tankların sprey nasoslarını "AVTOMATİK SOYUTMA" rejiminə keçirib — bu, son yenilənmədən sonra proqram xətası nəticəsində yaranmış rejim konfliktdir. Tank təzyiqləri artır: 230 mbar, dəqiqədə 8 mbar artımla, təhlükəsizlik klapanı 250 mbar-da qurulub. Alarm sistemi 90 saniyədə 60-dan çox alarm verib. 2-ci zabitin İAS ilə təcrübəsi məhduddur.',
                'your_position' => 'Siz Baş Zabitsiniz, 2-ci zabitin xəbərdarlığından sonra ÜKO-ya gəlmisiniz. Kapitan göyərtədə yola çıxma hazırlığındadır.',
                'available_resources' => 'Əlavə zabit (3-cü zabit), lostromo və iki matros, İAS mühəndisi telefonla əlçatan, əl ilə idarəetmə prosedurları, müstəqil ESD sistemi, analoq manometrlər, CCTV, terminal ilə rabitə.',
                'current_conditions' => 'Gündüz, 14:00. Aydın hava. №3 və №4 tanklar 35%-də, təzyiq 230/228 mbar (artır). Sprey nasosları icazəsiz rejimdə işləyir. Təhlükəsizlik klapanı: 250 mbar.'
            ]
        ],
        'decision_prompt' => 'As Chief Officer arriving in the CCR with less than 3 minutes before potential relief valve lifting on tanks #3 and #4, what are your immediate actions to stabilize tank pressures, manage the alarm flood, and resolve the IAS mode conflict? Explain your decision-making process regarding use of ESD versus manual intervention, how you will deploy available personnel, and your communication plan with the terminal and IAS vendor.',
        'decision_prompt_i18n' => [
            'tr' => '#3 ve #4 tanklarda olası emniyet valfi açılmasına 3 dakikadan az süre kala CCR\'ye ulaşan Birinci Zabit olarak, tank basınçlarını stabilize etmek, alarm yoğunluğunu yönetmek ve IAS mod çakışmasını çözmek için acil eylemleriniz nelerdir? ESD kullanımı ile manuel müdahale arasındaki karar verme sürecinizi, mevcut personeli nasıl konuşlandıracağınızı ve terminal ile IAS sağlayıcıyla iletişim planınızı açıklayın.',
            'ru' => 'Как старший помощник, прибывший в ЦУГ менее чем за 3 минуты до возможного срабатывания предохранительных клапанов на танках №3 и №4, какие немедленные действия вы предпримете для стабилизации давления, управления потоком алармов и устранения конфликта режимов ИСА? Объясните процесс принятия решения об использовании ESD или ручного вмешательства, распределение персонала и план связи с терминалом и поставщиком ИСА.',
            'az' => '№3 və №4 tanklarda təhlükəsizlik klapanının açılmasına 3 dəqiqədən az vaxt qalmışkən ÜKO-ya çatan Baş Zabit olaraq, tank təzyiqlərini sabitləşdirmək, alarm axınını idarə etmək və İAS rejim konfliktini həll etmək üçün təcili hərəkətləriniz nələrdir? ESD istifadəsi ilə əl ilə müdaxilə arasında qərar vermə prosesinizi, mövcud heyəti necə yerləşdirəcəyinizi və terminal və İAS mühəndisi ilə rabitə planınızı izah edin.'
        ],
        'evaluation_axes_json' => [
            [
                'axis' => 'automation_mode_awareness',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Does not identify the IAS mode conflict as the root cause; attempts to troubleshoot individual alarms without understanding the systemic software issue; no awareness of automation mode states.',
                    '2' => 'Identifies that spray pumps are running unexpectedly but does not understand the AUTO COOLDOWN mode conflict; attempts to stop pumps via IAS interface without considering that software error may prevent normal shutdown.',
                    '3' => 'Correctly identifies the IAS mode conflict between cargo discharge and auto cooldown; recognizes that IAS commands may not be reliable; decides to bypass IAS but does not systematically verify all automation mode states across remaining tanks.',
                    '4' => 'Rapidly identifies mode conflict, recognizes IAS unreliability due to software error, immediately switches critical systems to manual control; verifies mode states on all tanks not just affected ones; contacts IAS vendor for root cause analysis while managing immediate crisis.',
                    '5' => 'Immediately identifies IAS mode conflict and its root cause (software flag error from update); takes all cargo control to manual override; systematically verifies all automation mode states across all 4 tanks and associated systems (IG, ballast); contacts IAS vendor with precise error description; documents the software failure for classification society notification; recognizes this as a potential systemic vulnerability affecting all IAS-controlled operations; prevents IAS from being returned to auto until full verification complete.'
                ]
            ],
            [
                'axis' => 'manual_fallback_competence',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'Unable to transition to manual operations; relies entirely on IAS to resolve the issue; does not know or reference manual valve operation sequences; fails to use ESD as emergency option.',
                    '2' => 'Recognizes need for manual intervention but is uncertain about valve operation sequence; sends crew to deck without clear instructions; delays critical pressure relief actions while searching for procedures.',
                    '3' => 'Initiates manual fallback by deploying crew to deck with cargo operations manual; follows correct valve sequence to isolate spray pumps; uses ESD as backup option but may not optimize the sequence to minimize thermal shock.',
                    '4' => 'Efficiently transitions to manual control: correctly prioritizes stopping spray pumps on #3 and #4 via manual isolation valves; deploys 3/O and deck crew with specific valve assignments and sequence; uses analog tank gauges for pressure monitoring independent of IAS; holds ESD as last resort with clear trigger criteria (e.g., pressure reaches 245 mbar).',
                    '5' => 'Expert manual fallback execution: immediately assigns specific cryogenic valve operations to qualified crew with PPE requirements for cryogenic conditions; provides precise sequence to avoid thermal shock and pressure transients; establishes manual pressure monitoring via independent analog gauges with phone relay to CCR; sets clear ESD trigger threshold with pre-positioned personnel; simultaneously manages ongoing cargo discharge on #3/#4 main pumps to continue pressure reduction through normal discharge; considers temporarily increasing vapor return to terminal; maintains manual control barrier until IAS software error is fully diagnosed and corrected.'
                ]
            ],
            [
                'axis' => 'alarm_management',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Overwhelmed by alarm flood; unable to prioritize or filter alarms; responds to alarms sequentially rather than by criticality; alarm paralysis leads to delayed action on rising tank pressures.',
                    '2' => 'Attempts to acknowledge alarms to clear the screen but does not effectively prioritize; focuses on individual alarm responses rather than identifying the pattern indicating the mode conflict; critical tank pressure alarms lost in noise.',
                    '3' => 'Recognizes the alarm flood and suppresses non-critical advisory alarms; focuses on tank pressure and cargo pump status alarms; assigns 2/O to monitor remaining alarms while addressing the primary issue.',
                    '4' => 'Immediately implements alarm triage: silences advisory and maintenance alarms; isolates critical safety alarms (tank pressure, gas detection, relief valve proximity); assigns dedicated alarm monitoring to 2/O with clear escalation criteria; uses independent analog instruments to verify critical parameters rather than relying on alarming IAS displays.',
                    '5' => 'Systematic alarm management under crisis: immediately suppresses all non-safety alarms; establishes priority alarm list (tank pressure, relief valve status, gas detection, pump status, ESD status); assigns 2/O exclusively to alarm monitoring with specific escalation triggers documented; cross-references IAS alarm data with independent analog instruments; recognizes alarm flood as a known hazard in automated systems per IMO MSC.1/Circ.1389; documents alarm flood for post-incident analysis; establishes verbal reporting cadence for pressure readings every 30 seconds from independent gauges.'
                ]
            ]
        ],
        'critical_omission_flags_json' => [
            'Failed to take immediate action to stop the spray pumps on tanks #3 and #4 within the available time window',
            'Did not consider or deploy the independent ESD system as a backup to the malfunctioning IAS',
            'Failed to transition critical cargo monitoring to independent analog instruments',
            'Did not inform the Master about the automation system failure and rising tank pressures',
            'Failed to communicate with the terminal regarding the pressure anomaly and potential safety risk',
            'Did not restrict IAS from returning to automatic mode until the software error was diagnosed and corrected'
        ],
        'expected_references_json' => [
            'IGC Code Chapter 13 — Instrumentation, automation, and safety systems',
            'IMO MSC.1/Circ.1389 — Guidance on procedures for operational limitations of automation',
            'SIGTTO "LNG Operations in Port Areas"',
            'Company IAS operating manual and emergency procedures',
            'Classification society requirements for software management and cyber safety',
            'ISM Code — maintenance of critical systems and equipment'
        ],
        'red_flags_json' => [
            'Continues to attempt IAS troubleshooting via software while tank pressures approach relief valve setting without manual backup action',
            'Ignores the alarm flood and attempts to respond to each alarm individually, losing critical time',
            'Sends inexperienced 2/O alone to deck for cryogenic valve operations without supervision or clear instructions',
            'Does not use independent analog pressure gauges and relies solely on the malfunctioning IAS for tank pressure data',
            'Decides to wait for IAS vendor engineer phone guidance before taking any stabilization action',
            'Triggers full ESD as first response without attempting targeted spray pump isolation, causing unnecessary thermal and pressure transients across all tanks'
        ]
    ],

    'LNG_S07_CRIS_001' => [
        'briefing_json' => [
            'en' => [
                'situation' => 'Your LNGC is discharging cargo at a terminal during the night watch at 02:00 local time. A catastrophic failure has occurred at the liquid manifold — the #2 loading arm emergency release coupler has activated spuriously, causing a partial disconnection of the loading arm and resulting in an LNG spray leak at the manifold area. The estimated release rate is approximately 50 m³/hr of LNG, which is forming a rapidly expanding cryogenic vapor cloud. The vapor cloud is drifting aft toward the accommodation block, driven by the light night breeze. Multiple gas detectors are alarming across the vessel: the manifold area shows LEL readings exceeding 60%, the midship area shows 30% LEL, and detectors approaching the accommodation are reading 10% LEL and rising. Deck area temperature sensors at the leak point show -45°C, indicating severe cryogenic contact hazard to any personnel or deck structure in the vicinity. The terminal\'s Emergency Shutdown link has activated ESD-1, which has stopped the terminal-side pumps, but the ship-side cargo pumps will continue running for approximately 15 seconds until the ESD-2 sequence completes — during which time LNG continues to be pumped through the partially disconnected loading arm. The accommodation HVAC system is currently set to "recirculation" mode, but the expanding gas cloud is now reaching the ventilation intake positions on the accommodation front. One crew member who was conducting deck rounds has been reported at or near the manifold area at the time of the incident — radio contact with this person has been lost. The fire detection system shows no ignition has occurred yet, but conditions are rapidly approaching the flammable range as the methane-air mixture dilutes from its initially too-rich concentration. The terminal fire brigade has been mobilized with an estimated arrival time of 8 minutes.',
                'your_position' => 'You are the Master, awakened by the general alarm and cargo emergency alarm in your cabin. You have arrived on the bridge within 90 seconds of the alarm activation. The 2/O (OOW) is in the CCR and the duty AB is on the bridge.',
                'available_resources' => 'Chief Officer en route to CCR (ETA 2 minutes). Chief Engineer in engine control room. Full crew complement of 25 aboard. Ship fire-fighting equipment: water spray/deluge system, dry powder units at manifold, foam monitors. Fixed water curtain at accommodation front. Boundary cooling system (water spray). Emergency fire pump (diesel-driven, independent). Ship-shore ESD system. CCTV covering deck and manifold. Sound-powered telephone system (backup communication). Terminal fire brigade (ETA 8 minutes). Terminal tugboat on standby.',
                'current_conditions' => 'Night, 02:00 local. Dark, deck lighting operational. Wind: NNE 6 knots (breeze pushing vapor cloud aft toward accommodation). Temperature: 8°C ambient. Sea state calm, vessel alongside starboard side to. Cargo: LNG (methane), discharge temperature -160°C. Cargo discharge was at 10,000 m³/hr prior to incident. Ship-side ESD-2 completing in 15 seconds. Gas detection readings rising. No ignition detected. HVAC set to recirculation. One crew member unaccounted for near manifold.'
            ],
            'tr' => [
                'situation' => 'LNG tankeriniz gece vardiyasında, yerel saat 02:00\'de terminalde kargo tahliyesi yapmaktadır. Sıvı manifoldda ciddi bir arıza meydana gelmiştir — #2 yükleme kolunun acil bırakma kuplajı beklenmedik şekilde aktive olmuş, yükleme kolunun kısmi ayrılmasına ve manifold bölgesinde LNG sprey sızıntısına neden olmuştur. Tahmini sızıntı hızı yaklaşık 50 m³/saat LNG olup, hızla genişleyen bir kriyojenik buhar bulutu oluşturmaktadır. Buhar bulutu hafif gece esintisiyle kıç tarafa, yaşam mahalline doğru sürüklenmektedir. Gemi genelinde birden fazla gaz dedektörü alarm vermektedir: manifold bölgesinde LEL okumaları %60\'ı aşmış, orta gemi bölgesinde %30 LEL, yaşam mahalline yaklaşan dedektörler %10 LEL ve yükselmektedir. Sızıntı noktasındaki güverte sıcaklık sensörleri -45°C göstermekte olup, civardaki herhangi bir personel veya güverte yapısı için ciddi kriyojenik temas tehlikesi bulunmaktadır. Terminalin Acil Durdurma (ESD) bağlantısı ESD-1\'i aktive etmiş ve terminal tarafı pompaları durdurmuştur, ancak gemi tarafı kargo pompaları ESD-2 sekansı tamamlanana kadar yaklaşık 15 saniye daha çalışmaya devam edecektir. Yaşam mahalli HVAC sistemi şu anda "resirkülasyon" modundadır, ancak genişleyen gaz bulutu yaşam mahalli ön cephesindeki havalandırma giriş noktalarına ulaşmaktadır. Güverte turu yapan bir mürettebat üyesinin olay anında manifold bölgesinde veya yakınında olduğu bildirilmiştir — bu kişiyle telsiz teması kesilmiştir. Yangın algılama sistemi henüz bir tutuşma meydana gelmediğini göstermektedir, ancak metan-hava karışımı başlangıçtaki çok zengin konsantrasyonundan seyrelerek yanıcı aralığa hızla yaklaşmaktadır. Terminal itfaiye ekibi harekete geçirilmiş olup tahmini varış süresi 8 dakikadır.',
                'your_position' => 'Kamaranızda genel alarm ve kargo acil durum alarmıyla uyandırılan Kaptansınız. Alarm aktivasyonunun 90 saniye içinde köprüye ulaştınız. 2. Zabit (vardiya zabiti) CCR\'de ve nöbetçi gemici köprüdedir.',
                'available_resources' => 'Birinci Zabit CCR\'ye gelmekte (TVS 2 dakika). Başmühendis makine kontrol odasında. 25 kişilik tam mürettebat gemide. Gemi yangın söndürme ekipmanı: su sprey/yağmurlama sistemi, manifoldda kuru toz üniteleri, köpük monitörleri. Yaşam mahalli önünde sabit su perdesi. Sınır soğutma sistemi (su spreyi). Acil yangın pompası (dizel tahrikli, bağımsız). Gemi-kıyı ESD sistemi. Güverte ve manifoldu kapsayan CCTV. Sesli telefon sistemi (yedek iletişim). Terminal itfaiye ekibi (TVS 8 dakika). Terminalde hazır römorkör.',
                'current_conditions' => 'Gece, yerel saat 02:00. Karanlık, güverte aydınlatması çalışıyor. Rüzgar: KKD 6 knot (buhar bulutunu kıça doğru itiyor). Sıcaklık: 8°C. Sakin deniz, gemi sancak tarafı yanaşık. Kargo: LNG (metan), tahliye sıcaklığı -160°C. Gemi tarafı ESD-2 15 saniye içinde tamamlanacak. Gaz algılama okumaları yükseliyor. Tutuşma tespit edilmedi. HVAC resirkülasyonda. Manifold yakınında bir mürettebat üyesi kayıp.'
            ],
            'ru' => [
                'situation' => 'Ваш СПГ-танкер выгружает груз у терминала, ночная вахта, 02:00. Произошёл аварийный отказ грузового манифолда — ложное срабатывание аварийного разъединителя стендера №2 привело к частичному отсоединению и утечке СПГ в виде спрея. Скорость утечки ~50 м³/ч, образуется быстро расширяющееся криогенное облако, дрейфующее к надстройке. Газовые детекторы: >60% НКПР у манифолда, 30% на миделе, 10% у надстройки. Температура на палубе у точки утечки -45°C. ESD-1 терминала активирован, но судовые грузовые насосы работают ещё 15 секунд до завершения ESD-2. Один член экипажа находился у манифолда — связь потеряна. Воспламенения нет, но условия приближаются к пределам воспламеняемости.',
                'your_position' => 'Вы — капитан, поднятый по тревоге. Вы на мостике через 90 секунд. 2-й помощник в ЦУГ, вахтенный матрос на мостике.',
                'available_resources' => 'Старший помощник в пути к ЦУГ (2 мин). Старший механик в ЦПУ. Полный экипаж 25 человек. Противопожарное оборудование: водяное орошение, порошковые установки, пенные мониторы. Водяная завеса у надстройки. Аварийный пожарный насос. ESD система. CCTV. Пожарная бригада терминала (8 мин).',
                'current_conditions' => 'Ночь, 02:00. Ветер ССВ 6 узлов (облако дрейфует к надстройке). 8°C. Груз: СПГ, -160°C. ESD-2 завершится через 15 секунд. Показания газа растут. Воспламенения нет. HVAC в режиме рециркуляции. Один человек не на связи у манифолда.'
            ],
            'az' => [
                'situation' => 'LNG tankeriniz gecə növbəsində (02:00) terminalda yük boşaldır. №2 yükləmə qolunun təcili ayırma birləşdiricisi səhvən işə düşüb, manifold sahəsində LNG sprey sızıntısına səbəb olub. Sızıntı sürəti ~50 m³/saat, sürətlə genişlənən kriogen buxar buludu yaşayış blokuna doğru hərəkət edir. Qaz detektorları: manifoldda >60% AYH, orta hissədə 30%, yaşayış blokunun yaxınlığında 10%. Sızıntı nöqtəsində -45°C. Terminal ESD-1 aktivləşib, lakin gəmi tərəfi nasosları hələ 15 saniyə işləyəcək. Manifold yaxınlığında bir heyət üzvü ilə əlaqə kəsilib. Alovlanma yoxdur, amma şərait yanıcı həddə yaxınlaşır.',
                'your_position' => 'Siz Kapitansınız, ümumi həyəcan siqnalı ilə oyandınız və 90 saniyədə körpüyə çatdınız. 2-ci zabit ÜKO-da, növbətçi matros körpüdədir.',
                'available_resources' => 'Baş zabit ÜKO-ya gəlir (2 dəq). Baş mühəndis maşın idarəetmə otağında. 25 nəfər tam heyət. Yanğınsöndürmə avadanlığı: su sprey sistemi, quru toz, köpük monitorları. Yaşayış bloku önündə su pərdəsi. Təcili yanğın nasosu. ESD sistemi. CCTV. Terminal yanğın briqadası (8 dəq).',
                'current_conditions' => 'Gecə, 02:00. Külək ŞŞQ 6 düyün (buxar buludu yaşayış blokuna doğru). 8°C. Yük: LNG, -160°C. ESD-2 15 saniyəyə tamamlanacaq. Qaz göstəriciləri artır. Alovlanma yoxdur. HVAC resirkulyasiyada. Manifold yaxınlığında bir nəfər əlaqəsiz.'
            ]
        ],
        'decision_prompt' => 'As Master arriving on the bridge during an active LNG release with a vapor cloud drifting toward accommodation, one crew member unaccounted for near the manifold, and no ignition yet but conditions approaching flammable range, what are your immediate priorities and ordered actions? Address emergency shutdown completion, crew safety and muster, accommodation protection, search for the missing crew member, boundary cooling, and coordination with the terminal. Explain your decision sequence and reasoning.',
        'decision_prompt_i18n' => [
            'tr' => 'Aktif bir LNG sızıntısı sırasında köprüye ulaşan Kaptan olarak — buhar bulutu yaşam mahalline doğru sürükleniyor, manifold yakınında bir mürettebat üyesi kayıp ve henüz tutuşma yok ama koşullar yanıcı aralığa yaklaşıyor — acil öncelikleriniz ve sıralı eylemleriniz nelerdir? Acil durdurma tamamlanması, mürettebat güvenliği ve toplanma, yaşam mahalli koruması, kayıp mürettebat araması, sınır soğutması ve terminal koordinasyonunu ele alın.',
            'ru' => 'Как капитан, прибывший на мостик во время активной утечки СПГ с облаком пара, дрейфующим к надстройке, одним членом экипажа без связи у манифолда и отсутствием воспламенения при приближении условий к пределам воспламеняемости — каковы ваши немедленные приоритеты и последовательность действий? Рассмотрите завершение аварийной остановки, безопасность экипажа и сбор, защиту надстройки, поиск пропавшего, охлаждение границ и координацию с терминалом.',
            'az' => 'Aktiv LNG sızıntısı zamanı körpüyə çatan Kapitan olaraq — buxar buludu yaşayış blokuna doğru hərəkət edir, manifold yaxınlığında bir heyət üzvü əlaqəsizdir, alovlanma yoxdur amma şərait yanıcı həddə yaxınlaşır — təcili prioritetləriniz və ardıcıl hərəkətləriniz nələrdir? Təcili dayandırmanın tamamlanması, heyət təhlükəsizliyi və toplanma, yaşayış bloku mühafizəsi, itkin heyət üzvünün axtarışı, sərhəd soyutması və terminal koordinasiyasını əhatə edin.'
        ],
        'evaluation_axes_json' => [
            [
                'axis' => 'emergency_shutdown_execution',
                'weight' => 0.40,
                'rubric_levels' => [
                    '1' => 'Does not verify ESD-2 completion; does not initiate any additional shutdown actions; unaware of the 15-second gap during which ship-side pumps continue; fails to confirm cargo flow has stopped.',
                    '2' => 'Waits passively for ESD-2 to complete; does not consider manual backup actions if ESD-2 fails; does not verify terminal ESD-1 status independently; slow to confirm all cargo transfer operations have ceased.',
                    '3' => 'Monitors ESD-2 completion and verifies ship-side pumps have stopped; confirms with terminal that ESD-1 is active; orders closure of manifold valves as backup; but does not address isolation of the leaking loading arm specifically or consider additional measures to reduce release rate.',
                    '4' => 'Immediately verifies ESD-2 countdown and prepares manual pump trip as backup; confirms terminal ESD-1 status; orders manifold emergency valve closure on all lines; specifically addresses #2 loading arm isolation; instructs CCR to close ship-side manifold valves to minimize residual drainage; confirms all cargo and vapor lines isolated.',
                    '5' => 'Comprehensive shutdown execution: immediately confirms ESD-2 status with CCR, has manual pump trip ready as backup with 2/O\'s hand on button; orders immediate closure of all ship-side manifold valves (liquid and vapor); specifically isolates the #2 loading arm at the ship manifold emergency valve; confirms ESD-1 status with terminal directly; verifies all cargo pumps stopped with zero-flow confirmation; instructs stripping of liquid from the manifold header to minimize continued leakage from residual head pressure; confirms vapor return valve closed to prevent backflow; coordinates with terminal on loading arm status and any terminal-side isolation actions; documents exact time of each shutdown step.'
                ]
            ],
            [
                'axis' => 'crew_safety_and_muster',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'Does not order muster; does not address the missing crew member; does not switch HVAC to emergency mode; does not restrict crew movement toward the gas cloud; accommodation protection not considered.',
                    '2' => 'Orders general alarm and muster but does not specifically address HVAC ventilation risk; delayed response to missing crew member; does not establish gas-free safe routes for crew movement; does not designate specific muster point away from gas cloud drift path.',
                    '3' => 'Orders muster at a location upwind of the gas cloud; switches HVAC to emergency shutdown/gas-tight mode; initiates search for missing crew member but does not establish rescue team with proper PPE; restricts crew from manifold area.',
                    '4' => 'Immediately orders emergency muster at designated point upwind; shuts down all accommodation ventilation and seals gas-tight closures; establishes dedicated rescue team with BA sets and cold-protective gear for missing crew member; designates safe approach routes avoiding gas cloud; establishes headcount protocol; restricts all non-essential crew to accommodation interior.',
                    '5' => 'Immediate comprehensive crew safety response: sounds emergency alarm and orders muster at designated upwind point; immediately orders HVAC to full shutdown with all fire dampers and gas-tight closures activated; establishes two-person rescue team with full BA sets, cold-weather protective gear, gas detectors, and communication on dedicated channel for missing crew member search via pre-planned safe route using CCTV to assess gas cloud extent first; initiates headcount with department heads reporting directly to bridge; designates secondary muster point if primary becomes unsafe due to gas cloud shift; establishes continuous gas monitoring at muster point; prepares for potential medical response for cryogenic burns; all non-essential crew sealed in accommodation with instructions to close all portholes and external doors.'
                ]
            ],
            [
                'axis' => 'boundary_cooling_containment',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'Does not activate water spray or boundary cooling; does not address the expanding vapor cloud; no fire-fighting preparation; does not consider the risk of delayed ignition.',
                    '2' => 'Activates water spray at manifold area only; does not start boundary cooling at accommodation; does not deploy foam or dry powder monitors pre-emptively; fire-fighting preparation reactive rather than anticipatory.',
                    '3' => 'Activates accommodation front water curtain and manifold water spray; starts emergency fire pump; deploys fire party to standby positions but does not establish comprehensive boundary cooling coverage between the leak and accommodation.',
                    '4' => 'Activates full water curtain at accommodation front, manifold water deluge, and intermediate deck water spray to create vapor dispersal barriers; starts emergency fire pump; positions fire party with dry powder and foam at strategic boundaries; coordinates with terminal fire brigade on approach route and equipment staging; prepares for potential delayed ignition scenario.',
                    '5' => 'Maximum boundary protection: immediately activates accommodation front water curtain, all available deck water spray between manifold and accommodation to create multiple vapor knockdown barriers, manifold deluge system; starts diesel-driven emergency fire pump independent of main power; positions fire teams at strategic boundaries with dry powder (for immediate fire knockdown) and foam monitors (for pool fire containment); activates deck foam system at manifold area pre-emptively for cryogenic pool containment; coordinates with terminal on their fire water and foam capability; establishes contingency for wind shift scenarios; prepares for delayed ignition by pre-wetting all exposed deck structures; coordinates terminal fire brigade approach to avoid gas cloud path; considers requesting terminal tug for additional fire-fighting capacity and potential emergency departure readiness.'
                ]
            ]
        ],
        'critical_omission_flags_json' => [
            'Failed to verify ESD-2 completion and confirm all cargo pumps stopped and manifold valves closed',
            'Did not immediately shut down or seal accommodation HVAC system against gas cloud ingress',
            'Failed to initiate search and rescue for the missing crew member near the manifold',
            'Did not activate boundary cooling water curtain at accommodation front to disperse/knockdown vapor cloud',
            'Failed to prepare for delayed ignition scenario despite gas readings in approaching flammable range',
            'Did not coordinate emergency response actions with the terminal control room'
        ],
        'expected_references_json' => [
            'IGC Code Chapter 11 — Fire protection and fire extinction',
            'IGC Code Chapter 14 — Personnel protection',
            'SIGTTO "ESD Arrangements and Linked Ship/Shore Systems"',
            'SOLAS Chapter II-2 — Fire safety measures',
            'Company emergency response plan for gas release scenarios',
            'Terminal emergency procedures and ship-shore emergency coordination protocol',
            'SIGTTO "Liquefied Gas Fire Hazard Management"'
        ],
        'red_flags_json' => [
            'Sends crew to the manifold area to investigate the leak without BA sets and without assessing gas cloud extent',
            'Does not shut down accommodation HVAC despite gas cloud approaching ventilation intakes',
            'Attempts to reconnect or secure the loading arm while LNG is still flowing',
            'Delays emergency actions to wait for terminal fire brigade rather than initiating ship-side response immediately',
            'Fails to account for the missing crew member or delays rescue efforts due to other priorities',
            'Orders crew to attempt fire-fighting at the manifold in a methane-rich atmosphere exceeding upper flammable limit without recognizing delayed ignition risk'
        ]
    ],

    'LNG_S08_TRADE_001' => [
        'briefing_json' => [
            'en' => [
                'situation' => 'Your 174,000 CBM LNGC is on a laden voyage from the Persian Gulf to Northwest Europe, currently 14 days into a 21-day passage. The vessel is proceeding at 17.5 knots, consuming natural boil-off gas (BOG) as primary fuel supplemented by HFO to meet the propulsion demand at this speed. The fleet operations center has issued an instruction to reduce speed to 14.5 knots in order to improve the vessel\'s CII (Carbon Intensity Indicator) rating — the current projected annual CII is rated D, borderline C, and the company is under pressure from charterers and investors to demonstrate environmental compliance. However, this speed reduction creates a cascade of operational problems. First, arrival will be delayed by approximately 2.5 days, causing the vessel to miss its assigned terminal slot at Gate LNG terminal in Rotterdam. The next available slot is 5 days later, meaning a total delay of 7.5 days from the original schedule. Second, at the reduced speed of 14.5 knots, the natural boil-off rate (approximately 0.15% per day of cargo volume) will exceed propulsion fuel demand, creating excess BOG that must be managed. The options for excess BOG are: (a) venting to atmosphere via the mast riser, which is now effectively prohibited under EU MRV regulations and would constitute a significant methane emission event; (b) burning in the Gas Combustion Unit (GCU), which consumes the gas at zero propulsive benefit but still counts as fuel consumption under CII calculations, partially negating the speed reduction benefit; or (c) reliquefaction — but the reliquefaction plant is already operating at 85% capacity and the Chief Engineer has reported that the reliquefaction compressor is showing elevated vibration levels trending toward the alarm threshold. The charter party contains a laycan clause stipulating that the vessel must arrive within the agreed laydays or the charterer may claim delay damages at $280,000 per day. Weather routing shows a developing low-pressure system in the Bay of Biscay — maintaining the direct route risks encountering Force 7-8 conditions, while diverting north around the system adds approximately 1 day to the voyage but significantly reduces heavy weather risk. The company DPA has advised "safety first, comply with CII guidance" but has not provided specific speed or routing instructions.',
                'your_position' => 'You are the Master. It is your decision to determine the vessel\'s speed, route, and how to manage the conflicting commercial, regulatory, and safety requirements. The Chief Officer, Chief Engineer, and fleet operations are available for consultation.',
                'available_resources' => 'Weather routing service with updated forecasts every 6 hours. Fleet operations center (available 24/7 by satellite phone and email). Charterer\'s operations desk (available during business hours). Ship\'s cargo management system with BOG calculations. Reliquefaction plant (operating at 85%, compressor vibration trending up). GCU available and operational. Engine room can switch between gas, dual-fuel, and HFO modes. Charter party documents aboard. CII calculation tool with voyage modeling capability. P&I Club emergency contact for legal guidance.',
                'current_conditions' => 'Day 14 of 21-day passage. Current position: Eastern Mediterranean, approaching Gibraltar. Speed: 17.5 knots. Weather at current position: favorable, Beaufort 3-4. Bay of Biscay forecast: low-pressure system developing, Beaufort 7-8 expected in 3 days on direct route. Cargo: LNG, 174,000 CBM, temperatures normal at -162°C. Natural BOR: 0.15%/day. Reliquefaction plant: 85% capacity, compressor vibration 4.2 mm/s (alarm at 5.0 mm/s). Tank pressures stable. Current projected annual CII: D rating (borderline C). Laycan window: arriving +2.5 days late at 14.5 knots (or on time at 17.5 knots). Terminal slot at Gate LNG: will be missed if speed reduced; next slot +5 days. Charter party delay damages: $280,000/day. Fuel: adequate HFO and LNG BOG for all speed options.'
            ],
            'tr' => [
                'situation' => '174.000 CBM kapasiteli LNG tankeriniz Basra Körfezi\'nden Kuzeybatı Avrupa\'ya yüklü sefer halinde olup, 21 günlük yolculuğun 14. günündesiniz. Gemi 17,5 knot hızla seyretmekte, doğal kaynama gazını (BOG) birincil yakıt olarak ve bu hızdaki tahrik talebini karşılamak için HFO takviyesiyle kullanmaktadır. Filo operasyon merkezi, geminin CII (Karbon Yoğunluğu Göstergesi) derecesini iyileştirmek amacıyla hızın 14,5 knota düşürülmesi talimatını vermiştir — mevcut tahmini yıllık CII değerlendirmesi D, sınırda C\'dir ve şirket, kiracılar ve yatırımcılardan çevresel uyumluluk gösterme baskısı altındadır. Ancak bu hız düşüşü bir dizi operasyonel sorun yaratmaktadır. Birincisi, varış yaklaşık 2,5 gün gecikecek ve Rotterdam\'daki Gate LNG terminalinde ayrılan terminal slotunun kaçırılmasına neden olacaktır — sonraki müsait slot 5 gün sonradır, yani orijinal programdan toplam 7,5 gün gecikme. İkincisi, 14,5 knot\'a düşürülen hızda doğal kaynama oranı (günlük kargo hacminin yaklaşık %0,15\'i) tahrik yakıt talebini aşacak ve fazla BOG\'un yönetilmesi gerekecektir. Fazla BOG seçenekleri: (a) baca yükselticisinden atmosfere havalandırma — AB MRV düzenlemeleri altında fiilen yasak ve önemli bir metan emisyonu olayı; (b) GCU\'da yakma — gazı sıfır tahrik faydasıyla tüketir ancak CII hesaplamalarında yakıt tüketimi olarak sayılır; (c) yeniden sıvılaştırma — ancak tesis zaten %85 kapasitede çalışıyor ve Başmühendis kompresör titreşimlerinin alarm eşiğine doğru yükseldiğini bildirmiştir. Kiralama sözleşmesi, geminin anlaşılan laydays içinde varması gerektiğini veya kiracının günlük 280.000 $ gecikme tazminatı talep edebileceğini şart koşmaktadır. Hava yönlendirmesi, Biscay Körfezi\'nde gelişen bir alçak basınç sistemi göstermektedir — doğrudan rota Kuvvet 7-8 koşullarıyla karşılaşma riski taşırken, kuzeyden sapma yaklaşık 1 gün ekler ancak ağır hava riskini önemli ölçüde azaltır. Şirket DPA\'sı "önce güvenlik, CII rehberliğine uyun" tavsiyesinde bulunmuş ancak belirli hız veya rota talimatı vermemiştir.',
                'your_position' => 'Kaptansınız. Geminin hızını, rotasını ve çelişen ticari, düzenleyici ve güvenlik gereksinimlerinin nasıl yönetileceğini belirlemek sizin kararınızdır. Birinci Zabit, Başmühendis ve filo operasyonları danışma için mevcuttur.',
                'available_resources' => 'Her 6 saatte güncellenen hava yönlendirme servisi. Filo operasyon merkezi (uydu telefon ve e-posta ile 7/24 ulaşılabilir). Kiracının operasyon masası (mesai saatlerinde ulaşılabilir). BOG hesaplamaları içeren gemi kargo yönetim sistemi. Yeniden sıvılaştırma tesisi (%85 kapasitede, kompresör titreşimi yükseliyor). GCU mevcut ve operasyonel. Makine dairesi gaz, çift yakıtlı ve HFO modları arasında geçiş yapabilir. Gemide kiralama sözleşmesi belgeleri. Sefer modelleme kapasiteli CII hesaplama aracı. Hukuki rehberlik için P&I Kulübü acil iletişim.',
                'current_conditions' => '21 günlük seferin 14. günü. Mevcut konum: Doğu Akdeniz, Cebelitarık\'a yaklaşıyor. Hız: 17,5 knot. Mevcut konumda hava: uygun, Beaufort 3-4. Biscay Körfezi tahmini: alçak basınç sistemi gelişiyor, 3 gün içinde doğrudan rotada Beaufort 7-8 bekleniyor. Kargo: LNG, 174.000 CBM, sıcaklıklar -162°C\'de normal. Doğal BOR: günde %0,15. Yeniden sıvılaştırma tesisi: %85 kapasite, kompresör titreşimi 4,2 mm/s (alarm 5,0 mm/s\'de). Tank basınçları stabil. Mevcut tahmini yıllık CII: D derecesi (sınırda C). Laycan penceresi: 14,5 knotta +2,5 gün geç varış (17,5 knotta zamanında). Gate LNG terminal slotu: hız düşürülürse kaçırılacak; sonraki slot +5 gün. Kiralama sözleşmesi gecikme tazminatı: günlük 280.000 $. Yakıt: tüm hız seçenekleri için yeterli HFO ve LNG BOG.'
            ],
            'ru' => [
                'situation' => 'Ваш СПГ-танкер вместимостью 174 000 м³ совершает гружёный рейс из Персидского залива в Северо-Западную Европу, 14-й день из 21-дневного перехода. Скорость 17,5 узла, топливо — естественный боил-офф газ (БОГ) плюс мазут. Флотская операционная поручает снизить скорость до 14,5 узла для улучшения рейтинга CII (текущий прогноз — D, на границе с C). Снижение скорости ведёт к опозданию на 2,5 дня и потере слота на терминале Gate LNG (следующий — через 5 дней). Избыточный БОГ при меньшей скорости нужно утилизировать: сброс в атмосферу запрещён (EU MRV), сжигание в ГСУ учитывается в CII, а релик-установка на 85% мощности с вибрацией компрессора у аварийного порога. Чартер предусматривает $280 000/день за опоздание. В Бискайском заливе формируется циклон — обход добавляет 1 день, но снижает штормовой риск.',
                'your_position' => 'Вы — капитан. Решение о скорости, маршруте и управлении конфликтом между коммерческими, регуляторными и безопасностными требованиями за вами.',
                'available_resources' => 'Метеосервис (обновления каждые 6 часов), флотская операционная 24/7, оператор фрахтователя, система управления грузом с расчётами БОГ, релик-установка (85%, вибрация компрессора растёт), ГСУ, двигатель с переключением газ/мазут, документы чартера, CII-калькулятор, P&I Клуб.',
                'current_conditions' => '14-й день. Восточное Средиземноморье, подход к Гибралтару. 17,5 узла. Погода: Бфт 3-4. Бискайский залив: Бфт 7-8 через 3 дня. Груз: 174 000 м³ СПГ, -162°C. БОР: 0,15%/день. Релик: 85%, вибрация 4,2 мм/с (аларм 5,0). CII: D. Опоздание на 2,5 дня при 14,5 уз. Следующий слот: +5 дней. Штраф: $280 000/день.'
            ],
            'az' => [
                'situation' => '174 000 CBM tutumlu LNG tankeriniz Fars körfəzindən Şimal-Qərbi Avropaya yüklü səfərdədir, 21 günlük səfərin 14-cü günü. Sürət 17,5 düyün, yanacaq — təbii qaynama qazı (BOG) + mazut. Filo əməliyyat mərkəzi CII reytinqini yaxşılaşdırmaq üçün sürəti 14,5 düyünə endirməyi tapşırıb (cari CII proqnozu: D, C sərhədində). Sürət azaldılması 2,5 gün gecikmə yaradır, Gate LNG terminalında slot itirilir (növbəti slot +5 gün). Artıq BOG-un idarə edilməsi lazımdır: atmosferə atılması qadağandır (EU MRV), GCU-da yandırılması CII-də hesablanır, reliquefaction qurğusu 85% gücündə kompressor titrəməsi ilə. Çarter müqaviləsi gecikmə üçün $280 000/gün cərimə nəzərdə tutur. Biskay körfəzində alçaq təzyiq sistemi formalaşır.',
                'your_position' => 'Siz Kapitansınız. Gəminin sürəti, marşrutu və ziddiyyətli kommersiya, tənzimləmə və təhlükəsizlik tələblərinin idarə edilməsi barədə qərar sizindir.',
                'available_resources' => 'Hava marşrutu xidməti (6 saatda yenilənmə), filo əməliyyat mərkəzi 24/7, fraxtçının əməliyyat masası, BOG hesablamaları ilə yük idarəetmə sistemi, reliquefaction qurğusu (85%, kompressor titrəməsi artır), GCU, mühərrik qaz/mazut rejimləri, çarter sənədləri, CII kalkulyatoru, P&I Klubu.',
                'current_conditions' => '14-cü gün. Şərqi Aralıq dənizi, Cəbəllütariqə yaxınlaşır. 17,5 düyün. Hava: Bft 3-4. Biskay: 3 gündə Bft 7-8. Yük: 174 000 CBM LNG, -162°C. BOR: 0,15%/gün. Reliquefaction: 85%, titrəmə 4,2 mm/s (alarm 5,0). CII: D. 14,5 düyündə +2,5 gün gecikmə. Növbəti slot: +5 gün. Cərimə: $280 000/gün.'
            ]
        ],
        'decision_prompt' => 'As Master, you must decide on the vessel\'s speed, routing, and boil-off management strategy while balancing CII compliance, charter party obligations, weather avoidance, and equipment reliability concerns. What is your decision and how do you communicate it to all stakeholders? Provide your analysis of the trade-offs, your chosen course of action, and the reasoning behind each element of your decision.',
        'decision_prompt_i18n' => [
            'tr' => 'Kaptan olarak, CII uyumluluğu, kiralama sözleşmesi yükümlülükleri, hava koşullarından kaçınma ve ekipman güvenilirliği endişelerini dengeleyerek geminin hızı, rotası ve kaynama gazı yönetim stratejisi hakkında karar vermelisiniz. Kararınız nedir ve bunu tüm paydaşlara nasıl iletirsiniz? Ödünleşimlerin analizinizi, seçtiğiniz eylem planını ve kararınızın her bir unsurundaki gerekçeyi açıklayın.',
            'ru' => 'Как капитан, вы должны принять решение о скорости судна, маршруте и стратегии управления боил-оффом, балансируя между соответствием CII, обязательствами по чартеру, уклонением от непогоды и надёжностью оборудования. Каково ваше решение и как вы доносите его до всех заинтересованных сторон? Представьте анализ компромиссов, выбранный курс действий и обоснование каждого элемента вашего решения.',
            'az' => 'Kapitan olaraq, CII uyğunluğu, çarter müqaviləsi öhdəlikləri, hava şəraitindən qaçınma və avadanlıq etibarlılığı narahatlıqlarını tarazlayaraq gəminin sürəti, marşrutu və qaynama qazı idarəetmə strategiyası barədə qərar verməlisiniz. Qərarınız nədir və bunu bütün maraqlı tərəflərə necə çatdırırsınız? Güzəştlərin analizinizi, seçdiyiniz fəaliyyət planını və qərarınızın hər elementinin əsaslandırmasını təqdim edin.'
        ],
        'evaluation_axes_json' => [
            [
                'axis' => 'voyage_optimization_decision',
                'weight' => 0.30,
                'rubric_levels' => [
                    '1' => 'Makes a binary choice (full speed or slow down) without analyzing the trade-offs; ignores weather routing considerations; does not calculate the financial and operational impacts of different speed options; no consideration of intermediate speed alternatives.',
                    '2' => 'Considers speed reduction but does not calculate the BOG surplus or its management implications; addresses weather routing superficially; does not model different speed/route combinations to find an optimal solution.',
                    '3' => 'Analyzes the speed reduction impact on arrival, terminal slot, and BOG management; considers the weather routing diversion; selects a reasonable speed/route combination but does not optimize for the overall best outcome considering all variables simultaneously.',
                    '4' => 'Conducts thorough analysis of multiple speed/route scenarios; calculates BOG surplus at different speeds and reliquefaction/GCU capacity to handle it; factors weather diversion time into arrival calculations; identifies an optimized speed that balances CII improvement with commercial obligations; considers phased speed changes (e.g., slower in good weather, faster to meet laycan).',
                    '5' => 'Comprehensive voyage optimization: models multiple speed profiles including variable speed strategy (e.g., 15.5 knots to Gibraltar, adjust based on weather update, potentially increase to 16.5 through Biscay if routing north); calculates BOG management at each speed segment considering reliquefaction capacity limitations; evaluates total fuel consumption and CII impact of each scenario including GCU operation; identifies that a moderate speed reduction (e.g., 16.0-16.5 knots) may achieve meaningful CII improvement while still making the terminal slot or arriving within laycan; factors compressor vibration trend into reliquefaction availability planning; presents decision matrix to fleet operations with recommended option and alternatives.'
                ]
            ],
            [
                'axis' => 'regulatory_compliance_balance',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Ignores CII implications entirely and maintains full speed; or blindly follows CII instruction without considering charter party, BOG management, or safety implications; does not understand the interaction between speed, BOG, GCU, and CII.',
                    '2' => 'Acknowledges CII concern but treats it as secondary to commercial schedule without analysis; does not recognize that GCU consumption partially offsets CII benefit of speed reduction; does not address EU MRV compliance for potential venting.',
                    '3' => 'Understands the CII-speed-BOG interaction; recognizes that venting is prohibited under EU MRV; plans GCU use for excess BOG but does not calculate the net CII impact; addresses charter party implications but does not develop a clear position for commercial negotiations.',
                    '4' => 'Demonstrates clear understanding of regulatory framework: CII calculation methodology, EU MRV requirements, and their interaction with operational decisions; calculates net CII impact considering GCU consumption; develops a reasoned position balancing CII improvement against commercial exposure; addresses venting prohibition clearly; considers annual CII picture not just this voyage.',
                    '5' => 'Expert regulatory navigation: calculates precise CII impact of each speed scenario including GCU fuel consumption offset; recognizes that CII is annual and models the impact of this voyage on full-year rating; identifies that moderate speed reduction may achieve C rating without the extreme commercial penalty of 14.5 knots; firmly rules out atmospheric venting with EU MRV and IMO GHG strategy references; develops documented decision rationale showing compliance effort for CII while protecting against charter party exposure; considers SEEMP Part III documentation requirements; proactively communicates CII compliance strategy to flag state if needed; recognizes that the D/C borderline means marginal improvement may be sufficient.'
                ]
            ],
            [
                'axis' => 'stakeholder_communication',
                'weight' => 0.35,
                'rubric_levels' => [
                    '1' => 'Does not communicate decision rationale to any stakeholder; makes unilateral decision without consulting fleet operations, charterer, or informing Chief Engineer about reliquefaction concerns; no documentation of decision-making process.',
                    '2' => 'Communicates decision to fleet operations but does not engage charterer\'s operations desk; does not discuss reliquefaction compressor concern with Chief Engineer in the context of voyage planning; limited documentation.',
                    '3' => 'Communicates with fleet operations and charterer separately; discusses compressor issue with Chief Engineer; provides decision rationale but does not proactively manage expectations or negotiate alternatives (e.g., alternative terminal slot arrangements).',
                    '4' => 'Structured communication plan: briefs fleet operations with analysis and recommendation before deciding; contacts charterer proactively about potential arrival variance and explores mitigation options (alternative discharge port, partial speed reduction compromise); works with Chief Engineer on compressor management plan including contingency if vibration reaches alarm; documents all communications and decision rationale in ship\'s log.',
                    '5' => 'Comprehensive stakeholder management: sends detailed voyage analysis to fleet operations with speed/route/BOG scenarios and CII calculations, requesting written guidance to clarify the verbal "comply with CII" instruction; proactively contacts charterer with transparent situation briefing and proposes solutions (partial speed reduction maintaining laycan, alternative terminal coordination, request for laycan extension with CII compliance justification); consults Chief Engineer on reliquefaction reliability and agrees on compressor monitoring protocol with defined abort criteria; contacts P&I Club for preliminary guidance on charter party exposure under force majeure/regulatory compliance arguments; briefs all ship officers on the situation and decision rationale; documents entire decision-making process including analysis, communications, and rationale in master\'s standing orders and ship\'s log; requests weather routing update specifically addressing Bay of Biscay timing windows.'
                ]
            ]
        ],
        'critical_omission_flags_json' => [
            'Failed to calculate the BOG surplus at reduced speed and plan for its management (reliquefaction, GCU, or other)',
            'Did not address the reliquefaction compressor vibration issue and its impact on BOG management options',
            'Failed to communicate proactively with the charterer about potential arrival delay and explore mitigation options',
            'Did not consider the interaction between GCU fuel consumption and CII calculation when assessing net benefit of speed reduction',
            'Failed to factor weather routing (Bay of Biscay low-pressure system) into the speed and arrival time analysis',
            'Did not document the decision-making process and rationale for regulatory and commercial audit trail'
        ],
        'expected_references_json' => [
            'IMO MEPC.364(79) — CII regulations and calculation methodology',
            'EU MRV Regulation — monitoring, reporting, verification of CO2 and methane emissions',
            'IGC Code — cargo management and boil-off handling requirements',
            'SIGTTO "LNG Shipping Suggested Competency Standards"',
            'Charter party terms — GIIGNL Voyage Charter laycan provisions',
            'Company voyage efficiency and CII compliance policy',
            'IMO Initial GHG Strategy and MEPC guidelines on CII corrective actions'
        ],
        'red_flags_json' => [
            'Decides to vent excess boil-off gas to atmosphere via mast riser as a routine solution',
            'Blindly reduces speed to 14.5 knots without analyzing BOG surplus, charter party exposure, or terminal slot consequences',
            'Ignores the reliquefaction compressor vibration trend and assumes full reliquefaction capacity will remain available',
            'Makes no attempt to communicate with the charterer about the developing delay situation',
            'Treats the CII instruction as absolute and does not exercise Master\'s professional judgment on voyage optimization',
            'Fails to consider weather routing and proceeds on direct route through forecasted heavy weather in Bay of Biscay'
        ]
    ]
];

    }
}
