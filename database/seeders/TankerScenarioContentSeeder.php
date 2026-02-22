<?php

namespace Database\Seeders;

use App\Models\MaritimeScenario;
use Illuminate\Database\Seeder;

/**
 * Populate TANKER scenarios with production-quality content.
 *
 * Idempotent: updates existing rows by scenario_code.
 * Run: php82 artisan db:seed --class=TankerScenarioContentSeeder --force
 */
class TankerScenarioContentSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = $this->getScenarios();

        foreach ($scenarios as $code => $data) {
            MaritimeScenario::where('scenario_code', $code)->update($data);
            $this->command->info("Updated: {$code}");
        }

        // Activate all 8 TANKER scenarios
        $activated = MaritimeScenario::where('command_class', 'TANKER')
            ->where('version', 'v2')
            ->update(['is_active' => true]);

        $this->command->info("TANKER scenario content seeded and activated ({$activated} scenarios).");
    }

    private function getScenarios(): array
    {
        return [

            // ══════════════════════════════════════════════════════════════
            // SLOT 1 — NAV_COMPLEX — TSS approach restricted visibility + pilotage
            // ══════════════════════════════════════════════════════════════
            'TANKER_S01_NAV_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 115,000 DWT Aframax crude oil tanker, fully laden, approaching the Strait of Dover TSS westbound. Visibility has dropped to 0.5 NM in dense fog. The pilot boarding station is 20 minutes ahead, but the pilot launch reports it cannot locate your vessel. VTS Dover has advised you of three outbound vessels in the opposite lane, two of which are ULCS container ships. Your AIS shows a small craft with erratic movement near the pilot boarding area. The helmsman reports the rudder feels sluggish due to shallow water effect as depth reduces to 1.5 × draft.',
                        'your_position'       => 'Bridge, command. C/O as OOW, helmsman on manual steering, lookout posted on forecastle.',
                        'available_resources' => 'ECDIS with real-time AIS overlay, two ARPA radars (S-band and X-band), VHF Ch 16 and port operations channel, fog horn operating, UKC monitoring system, engine on standby.',
                        'current_conditions'  => 'Visibility 0.5 NM (dense fog), wind calm, tidal stream 2.5 knots setting NE, depth 25m (draft 16m), traffic density high.',
                    ],
                    'tr' => [
                        'situation'           => '115.000 DWT Aframax ham petrol tankerinin kaptanısınız, tam yüklü, Dover Boğazı TSS\'ye batıya doğru yaklaşıyorsunuz. Yoğun siste görüş 0,5 deniz miline düşmüş. Kılavuz alma noktası 20 dakika ileride ama kılavuz botu geminizi bulamadığını bildiriyor. VTS Dover, karşı şeritte üç çıkış yapan gemi olduğunu bildirdi — ikisi ULCS konteyner gemisi. AIS\'inizde kılavuz alma bölgesi yakınında düzensiz hareket eden küçük bir tekne görünüyor. Dümenci, derinlik draft\'ın 1,5 katına düştüğünde sığ su etkisiyle dümenin ağırlaştığını bildiriyor.',
                        'your_position'       => 'Köprüüstü, komuta sizde. Birinci zabit vardiya zabiti, dümenci manuel dümen, baş tarafa gözcü konuşlandırılmış.',
                        'available_resources' => 'Gerçek zamanlı AIS\'li ECDIS, iki ARPA radar (S-band ve X-band), VHF Kanal 16 ve liman operasyon kanalı, sis düdüğü çalışıyor, UKC izleme sistemi, makine hazırda.',
                        'current_conditions'  => 'Görüş 0,5 mil (yoğun sis), rüzgar sakin, gelgit akıntısı 2,5 knot KD yönlü, derinlik 25m (draft 16m), yoğun trafik.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан танкера Афрамакс 115 000 DWT, полная загрузка, подход к СРД Дуврского пролива. Видимость 0,5 мили в густом тумане. Лоцманский катер не может найти судно. VTS сообщает о трёх встречных судах, два из которых ULCS.',
                        'your_position'       => 'Мостик, командование. Старпом — ВП, рулевой на ручном, впередсмотрящий на баке.',
                        'available_resources' => 'ЭКНИС с АИС, два САРП радара, УКВ, туманный горн, система контроля УКГ, двигатель в готовности.',
                        'current_conditions'  => 'Видимость 0,5 мили, штиль, приливное течение 2,5 узла на СВ, глубина 25м (осадка 16м), плотный трафик.',
                    ],
                    'az' => [
                        'situation'           => 'Tam yüklü 115.000 DWT Aframax neft tankerinin kapitanısınız, Dover boğazı TSS-ə yaxınlaşırsınız. Sıx dumanda görmə 0,5 milə düşüb. Losman qayığı gəminizi tapa bilmir. VTS 3 qarşı gəmi, 2-si ULCS konteyner gəmisi bildirir.',
                        'your_position'       => 'Körpüüstü, komanda sizdə. Birinci stürman ВП, sükanşı əl rejimində, baxıcı bakda.',
                        'available_resources' => 'ECDIS/AIS, iki ARPA radar, VHF, duman fitəsi, UKC monitorinqi, mühərrik hazır.',
                        'current_conditions'  => 'Görmə 0,5 mil, sakit, gelgit axını 2,5 düyün ŞQ, dərinlik 25m (çəki 16m), sıx trafik.',
                    ],
                ],
                'decision_prompt'      => 'Describe your navigation strategy for transiting the TSS in dense fog with a fully laden tanker, including your approach to the pilot boarding, traffic management, and UKC monitoring.',
                'decision_prompt_i18n' => [
                    'tr' => 'Yoğun siste tam yüklü tankerle TSS geçişi için navigasyon stratejinizi, kılavuz alımına yaklaşımınızı, trafik yönetimi ve UKC izlemenizi açıklayın.',
                    'ru' => 'Опишите навигационную стратегию прохода через СРД в густом тумане на груженом танкере, включая подход к лоцманской станции, управление трафиком и контроль УКГ.',
                    'az' => 'Sıx dumanda tam yüklü tankerlə TSS keçidi üçün naviqasiya strategiyanızı, losman götürmə, trafik idarəsi və UKC monitorinqini təsvir edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'tss_navigation_competence',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Enters TSS without reducing speed or enhancing bridge watch; no systematic traffic plot; ignores COLREG Rule 10 obligations for TSS transit.',
                            '2' => 'Reduces speed but does not systematically track targets in both lanes; relies solely on AIS without radar verification; minimal awareness of TSS routing obligations.',
                            '3' => 'Applies COLREG Rule 10 correctly, reduces speed per Rule 6, monitors traffic on both radars and AIS, maintains proper course through the designated lane, and coordinates with VTS Dover.',
                            '4' => 'Systematic TSS transit plan: speed reduced to safe speed considering fog and traffic density, both radars on different ranges for short/long-range picture, VTS communication maintained with regular position updates, tidal stream effect calculated and compensated in heading, all crossing/joining traffic identified and tracked.',
                            '5' => 'Expert TSS navigation: comprehensive risk assessment before entry, speed optimised for stopping distance within visibility range, dual radar watch with systematic target acquisition and CPA/TCPA logging, tidal set continuously monitored and corrected, VTS proactively updated, contingency plan for each identified traffic conflict, COLREG rules 5/6/7/8/10/19 all demonstrably applied, and clear decision criteria for aborting transit if conditions deteriorate further.',
                        ],
                    ],
                    [
                        'axis'   => 'restricted_visibility_procedures',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No fog signal activated; no additional bridge manning; continues at service speed in dense fog.',
                            '2' => 'Fog signal sounding but speed not reduced to safe speed; bridge team not augmented; no systematic radar plotting.',
                            '3' => 'Correct restricted visibility actions: fog signal per COLREG Rule 35, safe speed per Rule 6, lookout enhanced, engines on standby, radar plotting systematic.',
                            '4' => 'Thorough restricted visibility management: all Rule 19 actions implemented, two-officer bridge watch, systematic ARPA plotting on both S-band and X-band, UKC continuously monitored with tidal predictions, pilot boarding contingency plan prepared if pilot cannot board.',
                            '5' => 'Exemplary restricted visibility response: full compliance with COLREG Part B Section III, bridge fully manned with designated roles (radar/lookout/helm/communications), safe speed calculated considering vessel stopping distance at laden displacement, pilot boarding alternatives explored (delay, alternative station, VTS coordination), all radar targets classified and tracked, standing orders and night orders updated, watertight doors confirmed closed, and clear abort criteria established.',
                        ],
                    ],
                    [
                        'axis'   => 'ukc_and_shallow_water_management',
                        'weight' => 0.20,
                        'rubric_levels' => [
                            '1' => 'No awareness of UKC; does not monitor depth or consider squat effect on a laden tanker.',
                            '2' => 'Aware of reduced depth but makes no squat calculation or speed adjustment for shallow water.',
                            '3' => 'Calculates squat at current speed, monitors UKC continuously, adjusts speed to maintain minimum required UKC as per company policy.',
                            '4' => 'Systematic UKC management: squat calculated for various speeds using vessel-specific data, tidal height verified against predictions, ECDIS safety contour and depth alarms set correctly, speed reduced to limit squat, and contingency if UKC falls below threshold.',
                            '5' => 'Expert UKC management: dynamic squat calculation integrating speed, block coefficient, and channel width ratio; tidal stream lateral set compensated; ECDIS grounding avoidance alarms verified; engine manoeuvring characteristics at shallow water documented; considers bank effect in confined channel; has clear abort plan if UKC becomes critical.',
                        ],
                    ],
                    [
                        'axis'   => 'bridge_team_management',
                        'weight' => 0.15,
                        'rubric_levels' => [
                            '1' => 'Master handles all tasks alone; no delegation or communication with bridge team.',
                            '2' => 'Some delegation but unclear roles; bridge team not briefed on the restricted visibility plan.',
                            '3' => 'Clear role assignments (radar watch, lookout, helm, VHF); team briefed on the passage plan and fog procedures.',
                            '4' => 'Structured BRM: pre-transit briefing conducted, each team member has defined responsibility, closed-loop communication protocol in use, challenge-and-response for critical actions, Master maintains oversight while delegating effectively.',
                            '5' => 'Exemplary BRM: full pre-transit briefing including abort criteria, team roles documented, communication protocol active with mandatory reporting triggers, additional manning arranged (call extra officers), pilot boarding procedure rehearsed, debrief planned, all decisions and communications logged.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No speed reduction in restricted visibility',              'severity' => 'critical'],
                    ['flag' => 'No COLREG Rule 19 application',                           'severity' => 'critical'],
                    ['flag' => 'No UKC monitoring or squat consideration for laden tanker', 'severity' => 'critical'],
                    ['flag' => 'No VTS coordination',                                      'severity' => 'major'],
                    ['flag' => 'No pilot boarding contingency plan',                       'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'COLREG Rules 5, 6, 7, 8, 10, 19, 35',
                    'SOLAS Chapter V (voyage planning, VTS)',
                    'Company SMS restricted visibility procedures',
                    'OCIMF — Tanker navigation best practices',
                    'ISGOTT Chapter 4 (tanker navigation)',
                ],
                'red_flags_json' => [
                    'Maintaining full speed in dense fog with 0.5 NM visibility',
                    'No radar cross-check of AIS targets',
                    'Ignoring shallow water effect on laden tanker',
                    'No fog signal sounding',
                    'Entering TSS without VTS coordination',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 2 — CMD_SCALE — Multi-party ops: terminal + charterer + superintendent
            // ══════════════════════════════════════════════════════════════
            'TANKER_S02_CMD_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 50,000 DWT product tanker discharging gasoline at a major refinery terminal. Midway through discharge, the terminal operator demands you increase the discharge rate from 3,000 m³/hr to 5,000 m³/hr to meet their schedule. The charterer\'s superintendent on board supports this demand and is pressuring you, stating "the charter party allows maximum pump rate." Simultaneously, the Chief Officer reports that the cargo manifold pressure is approaching the maximum working pressure of the hose, and the terminal\'s vapour return line is showing intermittent high-pressure alarms. Your cargo officer notes that the faster rate will require starting the third cargo pump, which had a bearing temperature alarm yesterday and was cleared after cooling.',
                        'your_position'       => 'Cargo control room, monitoring discharge. Superintendent standing behind you.',
                        'available_resources' => 'Three cargo pumps (one with recent alarm history), cargo monitoring system, manifold pressure gauges, terminal operations contact, Ship/Shore Safety Checklist, company operations department.',
                        'current_conditions'  => 'Night operations, berth alongside, calm weather, 60% discharged.',
                    ],
                    'tr' => [
                        'situation'           => '50.000 DWT ürün tankerinin kaptanısınız, büyük bir rafineri terminalinde benzin boşaltıyorsunuz. Boşaltmanın ortasında terminal operatörü, programlarını tutturmak için boşaltma hızını 3.000 m³/saatten 5.000 m³/saate çıkarmanızı talep ediyor. Gemideki kiracı süperintendanı bu talebi destekliyor ve "çarter parti azami pompa hızına izin veriyor" diyerek baskı yapıyor. Aynı anda Birinci Zabit, kargo manifold basıncının hortumun azami çalışma basıncına yaklaştığını ve terminalin buhar geri dönüş hattının aralıklı yüksek basınç alarmları verdiğini bildiriyor. Kargo zabitiniz, daha yüksek hızın dün yatak sıcaklığı alarmı veren ve soğutma sonrası temizlenen üçüncü kargo pompasının çalıştırılmasını gerektireceğini belirtiyor.',
                        'your_position'       => 'Kargo kontrol odası, boşaltmayı izliyorsunuz. Süperintendant arkanızda duruyor.',
                        'available_resources' => 'Üç kargo pompası (biri yakın zamanda alarm geçmişli), kargo izleme sistemi, manifold basınç göstergeleri, terminal operasyonları irtibatı, Gemi/Kıyı Güvenlik Kontrol Listesi, şirket operasyon departmanı.',
                        'current_conditions'  => 'Gece operasyonları, rıhtımda yanaşık, sakin hava, %60 boşaltılmış.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан танкера 50 000 DWT, выгрузка бензина на НПЗ. Терминал требует увеличить скорость с 3 000 до 5 000 м³/ч. Суперинтендант фрахтователя поддерживает. Старпом сообщает: давление на манифолде приближается к пределу шланга, линия возврата паров даёт аварийные сигналы. Третий грузовой насос (нужен для повышенной скорости) вчера давал аларм по температуре подшипника.',
                        'your_position'       => 'Грузовая рубка, контроль выгрузки. Суперинтендант рядом.',
                        'available_resources' => 'Три грузовых насоса, система мониторинга, манометры, контакт терминала, чек-лист безопасности, отдел эксплуатации компании.',
                        'current_conditions'  => 'Ночная операция, у причала, штиль, 60% выгружено.',
                    ],
                    'az' => [
                        'situation'           => '50.000 DWT məhsul tankerinin kapitanısınız, NEZ terminalında benzin boşaldırsınız. Terminal sürəti 3.000-dən 5.000 m³/saata artırmağı tələb edir. Fraxtçı superintendantı dəstəkləyir. Birinci stürman manifold təzyiqinin hortum limitinə yaxınlaşdığını, buhar xəttinin aralıqlı alarm verdiyini bildirir. Üçüncü yük nasosu dünən yataq temperaturu alarmı vermişdi.',
                        'your_position'       => 'Yük nəzarət otağı, boşaltmanı izləyirsiniz. Superintendant yanınızdadır.',
                        'available_resources' => 'Üç yük nasosu, monitorinq sistemi, manometrlər, terminal əlaqəsi, təhlükəsizlik çeklistı, şirkət əməliyyat şöbəsi.',
                        'current_conditions'  => 'Gecə əməliyyatı, rıhtımda, sakit hava, 60% boşaldılıb.',
                    ],
                ],
                'decision_prompt'      => 'How do you respond to the pressure to increase the discharge rate? Explain your decision-making process, how you manage the multiple stakeholders, and what safety boundaries you set.',
                'decision_prompt_i18n' => [
                    'tr' => 'Boşaltma hızını artırma baskısına nasıl yanıt veriyorsunuz? Karar verme sürecinizi, çoklu paydaşları nasıl yönettiğinizi ve hangi güvenlik sınırlarını belirlediğinizi açıklayın.',
                    'ru' => 'Как вы реагируете на давление увеличить скорость выгрузки? Объясните процесс принятия решения, управление заинтересованными сторонами и установленные границы безопасности.',
                    'az' => 'Boşaltma sürətini artırma təzyiqinə necə cavab verirsiniz? Qərar qəbul prosesini, maraqlı tərəfləri necə idarə etdiyinizi və hansı təhlükəsizlik sərhədlərini qoyduğunuzu izah edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'safety_boundary_management',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Immediately complies with the rate increase demand without checking manifold limits, hose ratings, or pump condition.',
                            '2' => 'Hesitates but eventually agrees to the increase under pressure, without verifying the cargo system can safely handle the higher rate.',
                            '3' => 'Checks manifold pressure limits and hose maximum working pressure before deciding; refuses to exceed equipment ratings; addresses the pump bearing alarm history before starting the third pump.',
                            '4' => 'Systematic safety assessment: reviews Ship/Shore Safety Checklist limits, verifies hose burst pressure vs. current manifold pressure, evaluates third pump bearing condition with C/E consultation, checks vapour return capacity, sets a firm maximum rate based on the weakest link in the system.',
                            '5' => 'Expert safety management: comprehensive review of all cargo system limits (hose MWP, manifold flange rating, vapour line capacity, pump NPSH at higher rate), refuses to start third pump until bearing inspected and cleared by C/E, calculates safe maximum rate considering all constraints, documents the rate limitation in the deck log, and formally advises terminal of the maximum safe rate with technical justification.',
                        ],
                    ],
                    [
                        'axis'   => 'stakeholder_management',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Capitulates to whoever shouts loudest; no assertive communication with terminal or superintendent.',
                            '2' => 'Disagrees but cannot articulate the safety basis clearly; allows superintendent to override safety decisions.',
                            '3' => 'Communicates clearly with terminal and superintendent that the rate increase is limited by equipment constraints; maintains professional but firm position; explains the safety reasoning.',
                            '4' => 'Effective multi-party management: addresses terminal operator with specific technical limits, explains to superintendent that master\'s authority on safety cannot be overridden by charter party commercial terms, informs company operations of the situation, documents all communications.',
                            '5' => 'Exemplary stakeholder management: calm and professional under pressure, clearly invokes master\'s overriding authority per ISM Code, provides terminal with written confirmation of maximum safe rate, addresses superintendent diplomatically but firmly citing ISGOTT and Ship/Shore Checklist, notifies company with full situation report, prepares for potential commercial claim by documenting everything, and offers a compromise where possible (e.g., slightly increased rate within safe limits).',
                        ],
                    ],
                    [
                        'axis'   => 'technical_cargo_knowledge',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No understanding of cargo system limitations, hose ratings, or pump characteristics for the discharge operation.',
                            '2' => 'Basic awareness of pump rates but does not understand the relationship between flow rate, manifold pressure, and hose stress, or the significance of the vapour return alarm.',
                            '3' => 'Understands the cargo system holistically: knows that increased rate raises manifold pressure, checks hose certificate for MWP, recognises vapour return alarm as a potential overpressure risk, and evaluates pump bearing alarm as a mechanical risk.',
                            '4' => 'Strong technical knowledge: analyses the discharge system as a whole — pump capacity, pipeline friction losses at higher rate, manifold pressure increase, hose stress, vapour return line sizing, tank pressure management; identifies that the vapour alarm may indicate the terminal cannot handle higher vapour generation at increased rate.',
                            '5' => 'Expert technical analysis: comprehensive understanding of the entire discharge chain including NPSH requirements at higher rates, considers gasoline static accumulation risk at increased flow velocity, evaluates gas freeing implications for tank atmosphere, understands vapour return line capacity limitations indicating terminal-side constraint, considers thermal stress on cargo hose at higher rate, and identifies that charter party "maximum rate" is always subject to safe operational limits per ISGOTT.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No check of manifold pressure vs hose MWP before rate increase',  'severity' => 'critical'],
                    ['flag' => 'No assessment of third pump bearing condition',                    'severity' => 'critical'],
                    ['flag' => 'No investigation of vapour return line alarms',                    'severity' => 'critical'],
                    ['flag' => 'No assertion of master authority against unsafe demands',           'severity' => 'major'],
                    ['flag' => 'No documentation of rate limitation decision',                     'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'ISGOTT Chapter 11 (cargo transfer operations)',
                    'Ship/Shore Safety Checklist (ISGOTT Appendix)',
                    'OCIMF — Mooring Equipment Guidelines',
                    'ISM Code Section 5 (master\'s overriding authority)',
                    'SOLAS Chapter II-2 (fire safety — cargo operations)',
                    'Company SMS cargo operations procedures',
                ],
                'red_flags_json' => [
                    'Increasing rate without checking equipment limits',
                    'Starting pump with known bearing alarm history without inspection',
                    'Allowing superintendent to override safety decisions',
                    'Ignoring vapour return line high-pressure alarms',
                    'No documentation of pressure readings and rate decisions',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 3 — TECH_DEPTH — Cargo pump failure + IGS malfunction during discharge
            // ══════════════════════════════════════════════════════════════
            'TANKER_S03_TECH_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 74,000 DWT Panamax crude oil tanker discharging at a single-point mooring (SPM). During the final stages of discharge, the No. 1 cargo pump trips on overload and cannot be restarted. Simultaneously, the Inert Gas System (IGS) shows a drop in IG pressure and the oxygen content in the IG main is rising — currently reading 6.5% O₂ (maximum allowable is 5%). The deck seal water level is low. Without adequate IG pressure, tank atmospheres will become unsafe as cargo levels drop. The terminal is demanding you maintain the discharge schedule. You still have approximately 8,000 m³ remaining across four centre tanks.',
                        'your_position'       => 'Cargo control room. C/O on deck monitoring manifold, C/E investigating pump fault from engine room.',
                        'available_resources' => 'Two remaining operational cargo pumps, IGS (flue gas type) with deck seal unit, portable gas detector, fixed gas monitoring system, terminal SPM control room contact, company technical superintendent by phone.',
                        'current_conditions'  => 'Night, calm seas, wind Force 2, SPM operations, no other vessel in vicinity.',
                    ],
                    'tr' => [
                        'situation'           => '74.000 DWT Panamax ham petrol tankerinin kaptanısınız, tek nokta bağlama (SPM) sisteminde boşaltma yapıyorsunuz. Boşaltmanın son aşamalarında 1 No\'lu kargo pompası aşırı yük nedeniyle devre dışı kalıyor ve yeniden çalıştırılamıyor. Aynı anda İnert Gaz Sistemi (IGS) IG basıncında düşüş gösteriyor ve IG ana hattındaki oksijen içeriği yükseliyor — şu an %6,5 O₂ okuyor (izin verilen maksimum %5). Güverte mühür suyu seviyesi düşük. Yeterli IG basıncı olmadan, kargo seviyeleri düştükçe tank atmosferleri güvensiz hale gelecek. Terminal boşaltma programını sürdürmenizi talep ediyor. Dört merkez tankta yaklaşık 8.000 m³ kargo kalmış durumda.',
                        'your_position'       => 'Kargo kontrol odası. Birinci zabit güvertede manifoldu izliyor, Başmühendis makine dairesinden pompa arızasını araştırıyor.',
                        'available_resources' => 'Çalışır durumda iki kargo pompası, IGS (baca gazı tipi) güverte mühür ünitesiyle, portatif gaz dedektörü, sabit gaz izleme sistemi, terminal SPM kontrol odası irtibatı, telefonla şirket teknik süperintendanı.',
                        'current_conditions'  => 'Gece, sakin deniz, rüzgar Kuvvet 2, SPM operasyonları, civarda başka gemi yok.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан танкера 74 000 DWT, выгрузка на ВТМ. Грузовой насос №1 отключился по перегрузке, перезапуск невозможен. Одновременно давление инертного газа падает, содержание O₂ в магистрали ИГ — 6,5% (допустимо 5%). Уровень воды в палубном затворе низкий. Терминал требует продолжения по графику. Осталось ~8 000 м³ в четырёх центральных танках.',
                        'your_position'       => 'Грузовая рубка. Старпом на палубе, стармех исследует насос.',
                        'available_resources' => 'Два рабочих насоса, ИГС (дымовой тип), газоанализатор, фиксированный газовый мониторинг, контакт ВТМ, тех. суперинтендант по телефону.',
                        'current_conditions'  => 'Ночь, штиль, ветер 2 балла, операция на ВТМ.',
                    ],
                    'az' => [
                        'situation'           => '74.000 DWT tankerinin kapitanısınız, tək nöqtəli bağlama (SPM) sistemində boşaltma aparırsınız. №1 yük nasosu həddindən artıq yüklənmə ilə dayanıb. Eyni anda İnert Qaz Sistemində (İQS) təzyiq düşüb, O₂ səviyyəsi 6,5%-ə yüksəlib (norma 5%). Göyərtə möhür suyu aşağıdır. Terminaldan cədvəl tələbi gəlir. 4 mərkəzi tankda ~8.000 m³ yük qalıb.',
                        'your_position'       => 'Yük nəzarət otağı. Birinci stürman göyərtədə, baş mühəndis nasosu araşdırır.',
                        'available_resources' => 'İki işlək nasос, İQS, qaz detektoru, sabit qaz monitorinqi, SPM kontakt, texniki superintendant telefonda.',
                        'current_conditions'  => 'Gecə, sakit dəniz, külək 2 bal, SPM əməliyyatı.',
                    ],
                ],
                'decision_prompt'      => 'How do you manage the dual emergency of a cargo pump failure and rising O₂ in the IG system? What is your priority sequence and what actions do you take regarding cargo operations, IGS restoration, and tank atmosphere safety?',
                'decision_prompt_i18n' => [
                    'tr' => 'Kargo pompası arızası ve IG sistemindeki yükselen O₂ ikili acil durumunu nasıl yönetiyorsunuz? Öncelik sıralamanız nedir ve kargo operasyonları, IGS restorasyonu ve tank atmosfer güvenliği konusunda hangi eylemleri gerçekleştiriyorsunuz?',
                    'ru' => 'Как вы управляете двойной аварией — отказ насоса и рост O₂ в ИГС? Каков порядок приоритетов и действия по грузовым операциям, восстановлению ИГС и безопасности атмосферы танков?',
                    'az' => 'Yük nasosu nasazlığı və İQS-də artan O₂ ikili təcili vəziyyətini necə idarə edirsiniz? Prioritet ardıcıllığınız nədir və yük əməliyyatları, İQS bərpası və tank atmosferi təhlükəsizliyi üzrə hansı addımları atırsınız?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'igs_emergency_response',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Ignores rising O₂ content; continues discharge without addressing the IG system failure, creating an explosive atmosphere risk.',
                            '2' => 'Notices the O₂ alarm but continues discharge hoping the IGS will self-correct; does not investigate deck seal water level.',
                            '3' => 'Stops discharge immediately upon confirming O₂ exceeds 5%, orders IGS investigation (deck seal top-up, scrubber check), and monitors tank atmospheres with portable instruments.',
                            '4' => 'Systematic IGS response: immediately reduces discharge rate or stops, prioritises deck seal water restoration, investigates root cause (scrubber efficiency, blower performance, deck seal integrity), monitors all tank ullage spaces with fixed and portable instruments, does not resume discharge until O₂ confirmed below 5% with stable IG supply.',
                            '5' => 'Expert IGS management: immediately stops discharge, closes tank Butterworth plates and openings, tops up deck seal, systematically diagnoses IGS fault (checks flue gas quality, scrubber water flow, blower capacity, deck seal for leaks), verifies each tank atmosphere individually, calculates time to reach dangerous atmosphere based on ullage increase rate, establishes clear restart criteria (O₂ < 5% for minimum 30 minutes stable), documents all readings, and notifies terminal with technical explanation for the stop.',
                        ],
                    ],
                    [
                        'axis'   => 'cargo_pump_troubleshooting',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Makes no attempt to diagnose the pump overload; either ignores it or waits passively for the C/E.',
                            '2' => 'Asks C/E to fix the pump but provides no direction on the urgency or how remaining pumps should be managed during the outage.',
                            '3' => 'Coordinates with C/E on diagnosis (overload trip: checks motor, breaker, pump suction conditions), redistributes discharge load to two remaining pumps within safe limits, calculates revised discharge timeline.',
                            '4' => 'Structured troubleshooting: works with C/E to identify overload cause (stripping suction — pump cavitation, motor thermal overload, breaker fault), checks pump suction pressure and cargo level in the tank being stripped, adjusts valve lineup to optimise remaining pumps, communicates revised ETA to terminal.',
                            '5' => 'Expert pump management: systematic fault isolation with C/E (checks if pump ran dry due to tank level, inspects motor insulation, verifies breaker setting, considers pump alignment), optimises two-pump discharge by adjusting valve sequencing and tank rotation, calculates NPSH for remaining pumps at reducing cargo levels, arranges technical support for potential pump repair, and develops a plan to strip final residues using the eductors if pump cannot be restored.',
                        ],
                    ],
                    [
                        'axis'   => 'risk_prioritisation_and_communication',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Does not recognise that IG failure is a higher priority than pump failure; focuses only on the commercial impact of discharge delay.',
                            '2' => 'Understands both issues are serious but addresses them in the wrong order — tries to fix the pump first while O₂ continues rising.',
                            '3' => 'Correctly prioritises: IG safety first (stop discharge → restore IG), then pump troubleshooting; communicates situation to terminal and company.',
                            '4' => 'Clear priority framework: (1) stop discharge to protect tank atmospheres, (2) restore IG system, (3) diagnose pump fault in parallel, (4) resume discharge only when IG stable and at least two pumps confirmed safe; proactive communication with terminal, company, and all on-board personnel.',
                            '5' => 'Expert prioritisation and communication: immediately invokes emergency cargo stop procedure, clearly explains the safety basis to terminal (ISGOTT tank atmosphere requirements), notifies company technical superintendent with detailed situation report, coordinates C/O and C/E on parallel work streams (IG restoration and pump diagnosis), sets clear safety gates for resumption, documents the entire event chronologically, and conducts a toolbox meeting with cargo team before resuming operations.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'Continuing discharge with O₂ above 5% in IG main',           'severity' => 'critical'],
                    ['flag' => 'No investigation of deck seal water level',                   'severity' => 'critical'],
                    ['flag' => 'No individual tank atmosphere monitoring',                    'severity' => 'major'],
                    ['flag' => 'No communication with terminal about discharge stop',         'severity' => 'major'],
                    ['flag' => 'No assessment of pump overload root cause',                   'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'ISGOTT Chapter 7 (inerting, purging, gas-freeing)',
                    'ISGOTT Chapter 11 (cargo transfer operations)',
                    'SOLAS Chapter II-2 Reg 4 (inert gas systems)',
                    'Company SMS IGS operation procedures',
                    'Cargo pump manufacturer manual',
                    'OCIMF — Ship-to-Ship Transfer Guide',
                ],
                'red_flags_json' => [
                    'Continuing cargo operations with O₂ above 5% in IG supply',
                    'Not stopping discharge when tank atmospheres are at risk',
                    'Attempting to restart tripped pump without diagnosis',
                    'Ignoring deck seal water level alarm',
                    'No tank atmosphere monitoring during IG failure',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 4 — RISK_MGMT — Static electricity + tank cleaning + enclosed space
            // ══════════════════════════════════════════════════════════════
            'TANKER_S04_RISK_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 45,000 DWT chemical/product tanker in ballast, proceeding to a load port. Tank cleaning is underway on six cargo tanks in preparation for the next cargo (clean petroleum product — jet fuel). During tank cleaning, the Chief Officer discovers that the portable gas detector readings in No. 3 centre tank show 18% LEL hydrocarbon despite two complete washing cycles. The C/O wants to send a crew member into the tank to check the suction well and pipeline for residues. The tank has not yet been gas-freed — only inerted and washed. Additionally, the vessel\'s fixed hydrocarbon analyser has been reading intermittently due to a faulty sensor, and you have only one calibrated portable instrument available.',
                        'your_position'       => 'Bridge office, reviewing tank cleaning progress reports. C/O on cargo deck.',
                        'available_resources' => 'Tank washing machines, IGS, one calibrated portable multi-gas detector (O₂/HC/H₂S/CO), fixed gas monitoring system (intermittent), Permit to Work system, enclosed space entry equipment (BA sets, rescue harness, communications), company SMS.',
                        'current_conditions'  => 'Daylight, calm seas, ambient temperature 32°C, 2 days from load port.',
                    ],
                    'tr' => [
                        'situation'           => '45.000 DWT kimyasal/ürün tankerinin kaptanısınız, balastta yükleme limanına gidiyorsunuz. Bir sonraki kargo (temiz petrol ürünü — jet yakıtı) için altı kargo tankında temizlik yapılıyor. Tank temizliği sırasında Birinci Zabit, 3 No\'lu merkez tankta iki tam yıkama döngüsüne rağmen portatif gaz dedektörünün %18 LEL hidrokarbon gösterdiğini tespit ediyor. Birinci zabit, emme kuyusu ve boru hattındaki kalıntıları kontrol etmek için bir mürettebat göndermek istiyor. Tank henüz gazdan arındırılmamış — yalnızca inertlenmiş ve yıkanmış. Ayrıca geminin sabit hidrokarbon analizörü arızalı sensör nedeniyle aralıklı okuyor ve elinizde yalnızca bir kalibre edilmiş portatif cihaz var.',
                        'your_position'       => 'Köprüüstü ofis, tank temizleme ilerleme raporlarını inceliyorsunuz. Birinci zabit kargo güvertesinde.',
                        'available_resources' => 'Tank yıkama makineleri, IGS, bir kalibre portatif çoklu gaz dedektörü (O₂/HC/H₂S/CO), sabit gaz izleme sistemi (aralıklı), İş İzin sistemi, kapalı alan giriş ekipmanı (BA setleri, kurtarma kemeri, iletişim), şirket SMS.',
                        'current_conditions'  => 'Gündüz, sakin deniz, ortam sıcaklığı 32°C, yükleme limanına 2 gün.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан танкера 45 000 DWT в балласте, мойка 6 грузовых танков для следующего груза (реактивное топливо). В танке №3 после двух моечных циклов — 18% НПВ углеводородов. Старпом хочет послать человека в танк для проверки приёмного колодца. Танк не дегазирован — только инертирован и промыт. Стационарный газоанализатор работает с перебоями. Один калиброванный портативный прибор.',
                        'your_position'       => 'Штурманская, обзор прогресса мойки. Старпом на грузовой палубе.',
                        'available_resources' => 'Моечные машины, ИГС, один портативный мультигаз (O₂/УВ/H₂S/CO), стационарный мониторинг (перебои), система ПТР, снаряжение для входа в ЗП (ДА, страховочная привязь, связь), СУБ.',
                        'current_conditions'  => 'День, штиль, 32°C, 2 дня до порта погрузки.',
                    ],
                    'az' => [
                        'situation'           => '45.000 DWT tankerinin kapitanısınız, ballastda yükleme limanına gedirsiniz. 6 tankda təmizlik aparılır (növbəti yük: reaktiv yanacaq). №3 merkez tankda iki yuma dövrəsinə baxmayaraq 18% AYH karbohidrogen göstəricisi. Birinci stürman tankа adam göndərmək istəyir. Tank qazdan təmizlənməyib — yalnız inertlənib və yuyulub. Stasionar analizator fasilələrlə işləyir, bir kalibr olunmuş portativ cihaz var.',
                        'your_position'       => 'Körpüüstü ofis, tank təmizlik hesabatlarını nəzərdən keçirirsiniz. Birinci stürman yük göyərtəsindədir.',
                        'available_resources' => 'Tank yuma maşınları, İQS, bir portativ multi-qaz detektoru, stasionar monitorinq (fasiləli), İş İcazə sistemi, qapalı sahə avadanlığı (nəfəs aparatları, xilasetmə qurşağı, rabitə), SİS.',
                        'current_conditions'  => 'Gündüz, sakit dəniz, 32°C, yükleme limanına 2 gün.',
                    ],
                ],
                'decision_prompt'      => 'What is your decision regarding the C/O\'s proposal to send crew into the tank? How do you manage the tank cleaning challenges, gas monitoring limitations, and enclosed space entry safety?',
                'decision_prompt_i18n' => [
                    'tr' => 'Birinci zabitin tanka mürettebat gönderme teklifine ilişkin kararınız nedir? Tank temizleme zorluklarını, gaz izleme sınırlamalarını ve kapalı alan giriş güvenliğini nasıl yönetiyorsunuz?',
                    'ru' => 'Каково ваше решение по предложению старпома отправить человека в танк? Как вы управляете трудностями мойки, ограничениями газового мониторинга и безопасностью входа в закрытое пространство?',
                    'az' => 'Birinci stürmanın tanka ekipaj göndərmə təklifinə münasibətiniz nədir? Tank təmizlik çətinliklərini, qaz monitorinq məhdudiyyətlərini və qapalı sahə giriş təhlükəsizliyini necə idarə edirsiniz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'enclosed_space_entry_safety',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'Allows entry into an inerted, unwashed tank with 18% LEL without any risk assessment or permit — this is immediately dangerous to life.',
                            '2' => 'Recognises some risk but agrees to entry after "extra ventilation" without following the full enclosed space entry procedure or achieving safe atmosphere.',
                            '3' => 'Refuses entry until the tank is properly gas-freed (O₂ 21%, HC < 1% LEL, no H₂S/CO), requires a full enclosed space entry permit, and orders additional washing cycles first.',
                            '4' => 'Comprehensive approach: categorically refuses entry into a non-gas-freed tank, orders continued tank washing with additional cycles, plans gas-freeing after washing completes, requires full Permit to Work with risk assessment before any entry, ensures rescue team and equipment are ready before entry is even considered.',
                            '5' => 'Expert enclosed space management: absolutely prohibits entry in current conditions, orders systematic additional washing with monitoring between cycles, plans gas-freeing procedure with ventilation, establishes clear atmospheric criteria (O₂ 20.8-21%, HC < 1% LFL, H₂S < 5 ppm, CO < 25 ppm), requires atmosphere testing at multiple levels in the tank, full Permit to Work with master\'s personal approval, rescue team stood up with BA and communications, considers static electricity risk at 32°C ambient, and briefs crew on the fatal consequences of enclosed space entry violations.',
                        ],
                    ],
                    [
                        'axis'   => 'gas_monitoring_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Relies on the intermittent fixed analyser; does not address the single portable instrument limitation.',
                            '2' => 'Uses the portable instrument but does not recognise the risk of having only one calibrated device for six tanks simultaneously undergoing cleaning.',
                            '3' => 'Addresses the fixed analyser fault by requesting shore repair at next port; uses the portable instrument systematically for all tanks; implements a structured monitoring schedule.',
                            '4' => 'Proactive gas monitoring: attempts to repair or recalibrate the fixed analyser, implements a strict rotation schedule for portable instrument across all six tanks, records all readings, prioritises monitoring of the problematic No. 3 tank, ensures crew understand the instrument\'s limitations and reading accuracy.',
                            '5' => 'Expert monitoring management: addresses both instruments — arranges emergency parts/calibration gas for fixed analyser, establishes 2-hourly portable monitoring schedule for all tanks with documented readings, cross-references fixed and portable readings where both available, ensures calibration of portable instrument is current and documented, considers requesting an additional instrument from the load port agent, and trains designated crew member on proper sampling technique (top/middle/bottom of tank).',
                        ],
                    ],
                    [
                        'axis'   => 'tank_cleaning_process_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No analysis of why No. 3 tank still shows high LEL after two wash cycles; accepts it and moves on.',
                            '2' => 'Orders "more washing" without investigating why the tank is not cleaning properly or reviewing the washing programme.',
                            '3' => 'Investigates possible causes for residual HC (wax deposits, pipeline residue, insufficient washing temperature or duration), reviews the tank washing programme, adjusts nozzle angles and cycle duration for the next wash.',
                            '4' => 'Systematic troubleshooting: reviews previous cargo history for No. 3 tank (heavy crudes leave more residue), checks washing machine nozzle rotation, verifies wash water temperature (critical for waxy crudes), examines the pipeline and suction well design for dead spots, adjusts the washing programme based on findings.',
                            '5' => 'Expert process management: full investigation including cargo history review, wash water temperature monitoring, washing machine performance verification, consideration of chemical cleaning agents for stubborn residues, review of suction well and pipeline configuration for dead legs, consultation with company technical superintendent on cleaning procedure, establishes acceptance criteria before gas-freeing, and develops a timeline that balances thorough cleaning with the 2-day arrival deadline.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'Allowing enclosed space entry into non-gas-freed tank',             'severity' => 'critical'],
                    ['flag' => 'No Permit to Work before any tank entry',                           'severity' => 'critical'],
                    ['flag' => 'No investigation of persistent HC readings after washing',          'severity' => 'major'],
                    ['flag' => 'No plan to address fixed gas analyser malfunction',                 'severity' => 'major'],
                    ['flag' => 'No static electricity awareness at 32°C ambient temperature',       'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'ISGOTT Chapter 7 (inerting, purging, gas-freeing)',
                    'ISGOTT Chapter 10 (tank cleaning)',
                    'ISGOTT Chapter 3 (static electricity)',
                    'IMO Resolution A.1050(27) — Enclosed space entry',
                    'Company SMS Permit to Work procedures',
                    'ICS Tanker Safety Guide (Chemicals)',
                    'SOLAS Chapter II-2 Reg 4 (inert gas)',
                ],
                'red_flags_json' => [
                    'Permitting entry into inerted/non-gas-freed tank',
                    'No Permit to Work system applied',
                    'Ignoring 18% LEL reading as "acceptable"',
                    'No rescue team standby for enclosed space entry',
                    'Relying on single intermittent gas analyser for safety-critical decisions',
                    'No awareness of static electricity risk during tank cleaning',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 5 — CREW_LEAD — Fatigue + permit-to-work discipline failures
            // ══════════════════════════════════════════════════════════════
            'TANKER_S05_CREW_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 62,000 DWT product tanker that has completed three consecutive port calls in 10 days (load–discharge–load) with tank cleaning between each. You are now alongside loading for the fourth voyage. The Safety Officer reports to you that during his morning round he found: (1) two ABs performing hot work on the forecastle deck without a valid Hot Work Permit — they claim the Bosun told them "it\'s just a small job, no permit needed"; (2) the enclosed space entry log shows three entries into cargo tanks in the past 48 hours where the permit was signed by the C/O but the atmosphere test column is blank; (3) rest hour records reveal that the C/O has had only 4 hours rest in the last 24 hours, and the pumpman has worked 18 hours in the past 24. A SIRE inspection is expected at this port within the next 12 hours.',
                        'your_position'       => 'Master\'s cabin, morning meeting with Safety Officer.',
                        'available_resources' => 'Permit to Work system (hot work, enclosed space, working aloft), rest hour recording software, ISM SMS documentation, Safety Committee provisions, DPA contact, 22 crew total.',
                        'current_conditions'  => 'Alongside at terminal, loading operations underway, good weather, SIRE inspection expected within 12 hours.',
                    ],
                    'tr' => [
                        'situation'           => '62.000 DWT ürün tankerinin kaptanısınız, 10 günde üç ardışık liman uğraması (yükleme-boşaltma-yükleme) tamamladınız ve her birinin arasında tank temizliği yapıldı. Şimdi dördüncü sefer için yükleme yapıyorsunuz. Güvenlik Zabiti sabah turunda şunları tespit ettiğini bildiriyor: (1) baş tarafa güvertesinde iki Usta Gemici geçerli Sıcak Çalışma İzni olmadan sıcak çalışma yapıyor — lostromo "küçük bir iş, izin gerekmez" demiş; (2) kapalı alan giriş kaydında son 48 saatte Birinci zabit tarafından imzalanmış ama atmosfer testi sütunu boş olan üç giriş var; (3) dinlenme saati kayıtları Birinci zabitin son 24 saatte yalnızca 4 saat dinlendiğini ve pompa operatörünün son 24 saatte 18 saat çalıştığını gösteriyor. Bu limanda 12 saat içinde SIRE denetimi bekleniyor.',
                        'your_position'       => 'Kaptan kamarası, Güvenlik Zabiti ile sabah toplantısı.',
                        'available_resources' => 'İş İzin sistemi (sıcak çalışma, kapalı alan, yüksekte çalışma), dinlenme saati kayıt yazılımı, ISM SMS belgeleri, Güvenlik Komitesi hükümleri, DPA irtibatı, toplam 22 mürettebat.',
                        'current_conditions'  => 'Terminalde yanaşık, yükleme operasyonları devam ediyor, iyi hava, 12 saat içinde SIRE denetimi bekleniyor.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан танкера 62 000 DWT, за 10 дней три порт-захода (погрузка-выгрузка-погрузка) с мойками танков. Сейчас на погрузке. Офицер по безопасности обнаружил: (1) два матроса ведут огневые работы без наряда — боцман сказал «разрешение не нужно»; (2) в журнале входа в ЗП — три записи за 48 часов с пустой графой замеров атмосферы; (3) старпом отдохнул 4 часа за сутки, помповый — отработал 18 часов. SIRE инспекция ожидается через 12 часов.',
                        'your_position'       => 'Каюта капитана, утреннее совещание с офицером по безопасности.',
                        'available_resources' => 'Система нарядов-допусков, ПО учёта часов отдыха, документация ISM, Комитет безопасности, DPA, 22 человека экипажа.',
                        'current_conditions'  => 'У причала, погрузка, хорошая погода, SIRE через 12 часов.',
                    ],
                    'az' => [
                        'situation'           => '62.000 DWT tankerinin kapitanısınız, 10 gündə 3 ardıcıl liman (yükləmə-boşaltma-yükləmə) tanklar arası yuma ilə. İndi 4-cü səfər üçün yükləyirsiniz. Təhlükəsizlik zabiti aşkar edib: (1) 2 matros icazəsiz isti iş aparır — lostromo «lazım deyil» deyib; (2) qapalı sahə jurnalında 48 saatda 3 giriş atmosfer ölçmə qrafası boş; (3) birinci stürman 24 saatda cəmi 4 saat dincəlib, nasosçu 18 saat işləyib. 12 saat ərzində SIRE yoxlaması gözlənilir.',
                        'your_position'       => 'Kapitan kamarası, təhlükəsizlik zabiti ilə səhər görüşü.',
                        'available_resources' => 'İş İcazə sistemi, istirahət saatı proqramı, ISM sənədləri, Təhlükəsizlik Komitəsi, DPA kontaktı, 22 nəfər.',
                        'current_conditions'  => 'Terminalda, yükləmə davam edir, yaxşı hava, 12 saata SIRE yoxlaması.',
                    ],
                ],
                'decision_prompt'      => 'How do you address the multiple safety failures: unpermitted hot work, incomplete enclosed space permits, and rest hour violations? What immediate, short-term, and systemic actions do you take, especially with a SIRE inspection imminent?',
                'decision_prompt_i18n' => [
                    'tr' => 'Birden fazla güvenlik ihlalini nasıl ele alıyorsunuz: izinsiz sıcak çalışma, eksik kapalı alan izinleri ve dinlenme saati ihlalleri? SIRE denetimi yaklaşırken hangi acil, kısa vadeli ve sistemik önlemleri alıyorsunuz?',
                    'ru' => 'Как вы решаете множественные нарушения безопасности: несанкционированные огневые работы, неполные наряды на вход в ЗП и нарушения часов отдыха? Какие немедленные, краткосрочные и системные меры принимаете, учитывая предстоящую инспекцию SIRE?',
                    'az' => 'Çoxsaylı təhlükəsizlik pozuntularını necə həll edirsiniz: icazəsiz isti iş, natamam qapalı sahə icazələri və istirahət saatı pozuntuları? SIRE yoxlaması yaxınlaşarkən hansı təcili, qısa müddətli və sistemli tədbirlər görürsünüz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'immediate_safety_response',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Takes no immediate action on any of the three findings; delays until after SIRE inspection.',
                            '2' => 'Stops the hot work but does not address the enclosed space permit failures or rest hour violations.',
                            '3' => 'Immediately stops all unpermitted hot work, reviews enclosed space entry permits for completeness, and adjusts watch schedules to address the worst rest hour violations (C/O and pumpman).',
                            '4' => 'Comprehensive immediate response: stops hot work and secures the area, suspends all enclosed space entries pending permit review, relieves C/O from duties for mandatory rest, reassigns pumpman work to others, conducts emergency audit of all active permits, and addresses the Bosun directly about permit circumvention.',
                            '5' => 'Expert immediate response: hot work stopped and area confirmed safe (fire watch maintained for 30 minutes after cessation), all enclosed space permits reviewed and non-compliant entries retroactively documented with corrective actions, C/O ordered to rest immediately with temporary delegation of duties to 2/O, pumpman relieved and work redistributed, Bosun formally counselled and documented, emergency safety meeting called for all crew, and loading operations reviewed to ensure they can continue safely with adjusted manning.',
                        ],
                    ],
                    [
                        'axis'   => 'systemic_root_cause_analysis',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Treats each issue as isolated misconduct; no recognition that the root cause is systemic fatigue from three back-to-back port calls.',
                            '2' => 'Mentions fatigue as a factor but does not analyse the connection between the operational tempo, rest hour violations, and safety shortcut culture.',
                            '3' => 'Identifies the root cause: excessive operational tempo over 10 days has led to crew fatigue, which has degraded safety discipline (permit shortcuts are a symptom, not the cause). Communicates this to the company.',
                            '4' => 'Structured root cause analysis: connects the three consecutive port calls and tank cleaning periods to cumulative fatigue, identifies that the permit failures result from crew cutting corners due to exhaustion, recognises that the C/O\'s fatigue is both a symptom and a contributing cause, documents the analysis and recommends adjusted trading patterns to the company.',
                            '5' => 'Expert systemic analysis: comprehensive root cause investigation linking operational tempo to fatigue to safety culture degradation, identifies organisational factors (company scheduling pressure, insufficient crew for tanker operations at this intensity), documents findings as an ISM non-conformity, formally invokes master\'s overriding authority to request crew rest before next voyage, proposes systemic fixes (additional crew for intensive operations, mandatory rest periods between tank cleaning cycles), and schedules a full Safety Committee meeting.',
                        ],
                    ],
                    [
                        'axis'   => 'sire_preparation_and_transparency',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Panics about SIRE and attempts to falsify records — backdates permits, adjusts rest hour logs.',
                            '2' => 'Does not falsify records but tries to hide the issues from the SIRE inspector by cleaning up documentation superficially.',
                            '3' => 'Accepts the situation transparently: corrects the permit deficiencies with honest documentation, prepares factual records for SIRE inspector, and focuses on demonstrating corrective actions already taken.',
                            '4' => 'Proactive SIRE approach: documents all findings honestly, implements corrective actions before the inspection, prepares a brief for the SIRE inspector showing the safety culture in action (finding issues → taking corrective action), briefs crew on honest answers during inspection.',
                            '5' => 'Exemplary transparency: fully documents all findings as non-conformities in the ISM system, implements immediate corrective actions with timestamps, prepares a comprehensive corrective action report showing: finding → root cause → immediate action → systemic fix, briefs SIRE inspector on the situation proactively (demonstrating a learning safety culture rather than hiding problems), contacts DPA and company with full disclosure, and uses the situation as evidence that the vessel\'s safety management system works (finds problems and fixes them).',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No immediate stop of unpermitted hot work',                        'severity' => 'critical'],
                    ['flag' => 'No review of enclosed space entry permits with missing gas tests',  'severity' => 'critical'],
                    ['flag' => 'No rest hour corrective action for C/O and pumpman',               'severity' => 'critical'],
                    ['flag' => 'No root cause analysis linking operational tempo to safety failures','severity' => 'major'],
                    ['flag' => 'Falsifying records for SIRE inspection',                           'severity' => 'critical'],
                    ['flag' => 'No DPA notification of systemic safety concerns',                  'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'ISGOTT Chapter 2 (permit to work systems)',
                    'ISGOTT Chapter 9 (hot work on tankers)',
                    'IMO Resolution A.1050(27) — Enclosed space entry',
                    'STCW Code Section A-VIII/1 (rest hours)',
                    'MLC 2006 Regulation 2.3 (hours of work)',
                    'ISM Code Sections 5, 6, 9, 12',
                    'OCIMF SIRE/VIQ requirements',
                ],
                'red_flags_json' => [
                    'Falsifying permits or rest hour records',
                    'Allowing unpermitted hot work to continue on a tanker',
                    'No action on enclosed space entries without gas testing',
                    'Ignoring C/O fatigue as a contributing factor to safety failures',
                    'Prioritising SIRE appearance over actual safety correction',
                    'No communication with company about systemic fatigue issue',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 6 — AUTO_DEP — ECDIS/GNSS anomaly + radar cross-check
            // ══════════════════════════════════════════════════════════════
            'TANKER_S06_AUTO_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 105,000 DWT Suezmax crude oil tanker, laden, transiting a coastal zone with multiple offshore platforms 30 NM off the West African coast. The OOW reports that the vessel\'s GNSS position has jumped 0.8 NM northeast in the last 10 minutes and then returned to the previous track line. Both GPS receivers show the same anomaly. ECDIS is displaying an integrity alarm. The radar overlay on ECDIS no longer matches the charted positions of three nearby oil platforms — the radar targets are consistent but the ECDIS chart position appears shifted. A coastal current of 1.5 knots is setting the vessel toward the platform exclusion zone. The nearest platform is 4 NM to the east.',
                        'your_position'       => 'Bridge, called by OOW. Night watch, 2/O on watch.',
                        'available_resources' => 'Two independent GNSS receivers, two ARPA radars (S-band and X-band), ECDIS (2 units, same GNSS feed), gyro compass, magnetic compass, AIS showing platform positions, VHF for platform operations, Navtex, company IT/navigation support.',
                        'current_conditions'  => 'Night, visibility good (8 NM), wind Force 3, current 1.5 knots setting east, multiple oil platforms in vicinity.',
                    ],
                    'tr' => [
                        'situation'           => '105.000 DWT Suezmax ham petrol tankerinin kaptanısınız, yüklü, Batı Afrika kıyısının 30 mil açığında çoklu açık deniz platformlarının bulunduğu kıyı bölgesinden geçiyorsunuz. Vardiya zabiti, geminin GNSS pozisyonunun son 10 dakikada 0,8 mil kuzeydoğuya sıçradığını ve ardından önceki rota hattına döndüğünü bildiriyor. Her iki GPS alıcısı da aynı anomaliyi gösteriyor. ECDIS bütünlük alarmı veriyor. ECDIS üzerindeki radar örtüşmesi artık yakındaki üç petrol platformunun harita pozisyonlarıyla uyuşmuyor — radar hedefleri tutarlı ama ECDIS harita pozisyonu kaymış görünüyor. 1,5 knotluk kıyı akıntısı gemiyi platform yasak bölgesine doğru sürüklüyor. En yakın platform doğuda 4 mil mesafede.',
                        'your_position'       => 'Köprüüstü, vardiya zabitinin çağrısıyla. Gece vardiyası, 2. zabit nöbette.',
                        'available_resources' => 'İki bağımsız GNSS alıcısı, iki ARPA radar (S-band ve X-band), ECDIS (2 ünite, aynı GNSS beslemesi), cayro pusula, manyetik pusula, platform pozisyonlarını gösteren AIS, platform operasyonları için VHF, Navtex, şirket IT/navigasyon desteği.',
                        'current_conditions'  => 'Gece, iyi görüş (8 mil), rüzgar Kuvvet 3, 1,5 knot doğuya sürükleyen akıntı, civarda çoklu petrol platformu.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан груженого танкера Суэцмакс 105 000 DWT, прибрежная зона Западной Африки с нефтяными платформами. GNSS позиция скакнула на 0,8 мили за 10 минут. Оба приёмника показывают аномалию. ЭКНИС — тревога целостности. Наложение радара на ЭКНИС не совпадает с позициями платформ на карте. Течение 1,5 узла сносит к зоне отчуждения платформ. Ближайшая — 4 мили к востоку.',
                        'your_position'       => 'Мостик, вызван ВП. Ночь, 2-й помощник на вахте.',
                        'available_resources' => 'Два GNSS приёмника, два САРП радара, 2 ЭКНИС (общий GNSS), гирокомпас, магнитный компас, АИС, УКВ, Навтекс, техподдержка компании.',
                        'current_conditions'  => 'Ночь, видимость 8 миль, ветер 3 балла, течение 1,5 узла на восток, нефтяные платформы рядом.',
                    ],
                    'az' => [
                        'situation'           => 'Yüklü 105.000 DWT Suezmax tankerinin kapitanısınız, Qərbi Afrika sahilində neft platformları olan bölgədə keçid edirsiniz. GNSS mövqeyi 10 dəqiqədə 0,8 mil sıçrayıb. Hər iki GPS eyni anomaliyanı göstərir. ECDIS bütövlük alarmı verir. Radar-ECDIS platformaların mövqelərini tutdurmur. 1,5 düyünlük axın gəmini platforma zonasına aparır. Ən yaxın platforma 4 mil şərqdə.',
                        'your_position'       => 'Körpüüstü, ВП tərəfindən çağırılıb. Gecə, 2-ci stürman növbədə.',
                        'available_resources' => 'İki GNSS qəbuledici, iki ARPA radar, 2 ECDIS, giro və maqnit kompas, AIS, VHF, Navtex, şirkət dəstəyi.',
                        'current_conditions'  => 'Gecə, görmə 8 mil, külək 3 bal, axın 1,5 düyün şərqə, neft platformaları yaxınlıqda.',
                    ],
                ],
                'decision_prompt'      => 'How do you verify your true position and ensure safe navigation when GNSS integrity is compromised near offshore platforms? Describe your diagnostic approach and navigation strategy.',
                'decision_prompt_i18n' => [
                    'tr' => 'Açık deniz platformları yakınında GNSS bütünlüğü bozulduğunda gerçek pozisyonunuzu nasıl doğruluyor ve güvenli navigasyonu nasıl sağlıyorsunuz? Tanı yaklaşımınızı ve navigasyon stratejinizi açıklayın.',
                    'ru' => 'Как вы проверяете истинное местоположение и обеспечиваете безопасную навигацию при нарушении целостности GNSS вблизи платформ? Опишите диагностику и навигационную стратегию.',
                    'az' => 'Neft platformaları yaxınlığında GNSS bütövlüyü pozulduqda həqiqi mövqeyinizi necə yoxlayırsınız və təhlükəsiz naviqasiyanı necə təmin edirsiniz? Diaqnostik yanaşmanızı və naviqasiya strategiyanızı təsvir edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'gnss_anomaly_diagnosis',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'Ignores the GNSS anomaly and ECDIS integrity alarm; continues to rely on ECDIS position display without question.',
                            '2' => 'Notes the alarm but assumes it is a temporary glitch; does not investigate or cross-check with independent sources.',
                            '3' => 'Recognises the GNSS anomaly as potentially serious (spoofing or atmospheric interference), cross-checks with radar ranges and bearings to fixed objects (platforms), and determines the radar position is more reliable.',
                            '4' => 'Systematic diagnosis: compares both GNSS receivers, checks for common-mode failure (jamming/spoofing vs. atmospheric), uses radar bearings and ranges to platforms as independent position fix, compares AIS-reported platform positions with radar-observed positions, checks Navtex for any GNSS warnings, considers potential GPS spoofing in the region.',
                            '5' => 'Expert GNSS diagnosis: systematic evaluation of both receivers for common-mode anomaly (indicating external interference rather than equipment fault), checks RAIM/integrity monitoring on each receiver, uses parallel index on radar with known platform positions to determine true position, plots visual/radar position on paper chart as backup, investigates whether the anomaly correlates with known spoofing patterns (West Africa), checks company and IMO circulars on GNSS interference in the area, and documents the event for subsequent analysis and reporting.',
                        ],
                    ],
                    [
                        'axis'   => 'navigation_safety_actions',
                        'weight' => 0.40,
                        'rubric_levels' => [
                            '1' => 'No course or speed adjustment despite GNSS uncertainty and proximity to platform exclusion zone.',
                            '2' => 'Reduces speed but does not adjust course away from the platform exclusion zone or establish a verified position.',
                            '3' => 'Immediately adjusts course to increase CPA to the nearest platform, reduces speed, establishes radar-based navigation, and monitors drift from the 1.5-knot current.',
                            '4' => 'Comprehensive safety actions: immediate course alteration to open distance from platforms, speed reduced, navigation switched to radar-primary mode with parallel indexing, current set and drift calculated and compensated, ECDIS position offset applied if offset is deterministic, lookout enhanced, and platform operations centre contacted.',
                            '5' => 'Expert navigation response: immediate bold course change to increase sea room from platform exclusion zone, speed reduced to allow more reaction time, radar parallel indexing established on multiple platforms for continuous position monitoring, current compensation applied, ECDIS used for chart data only (position from radar), manual position plotting on paper chart as verification, bridge team briefed on degraded GNSS mode, company notified, and clear criteria established for when to revert to GNSS (e.g., integrity alarm cleared for 30+ minutes with consistent readings).',
                        ],
                    ],
                    [
                        'axis'   => 'reporting_and_communication',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No communication with platforms, VTS, or company about the GNSS anomaly.',
                            '2' => 'Contacts company but does not warn nearby platforms or report the anomaly to maritime authorities.',
                            '3' => 'Notifies the nearest platform operations centre of the GNSS anomaly, contacts company, and logs the event in the deck log.',
                            '4' => 'Thorough communication: platform operations contacted about the GNSS issue and vessel\'s radar-based navigation, company and fleet informed (other vessels in the area may be affected), NAVAREA coordinator informed, event documented with timestamps and position data.',
                            '5' => 'Expert reporting: immediate notification to platform operations with vessel position and intended track, company fleet alert for potential regional GNSS interference, NAVAREA warning requested through company, flag state administration notified if suspected deliberate interference (spoofing), comprehensive deck log entries with GNSS data recordings for post-event analysis, and considers filing an IMO GNSS anomaly report.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No cross-check of GNSS position with radar',                       'severity' => 'critical'],
                    ['flag' => 'No course adjustment away from platform exclusion zone',            'severity' => 'critical'],
                    ['flag' => 'No recognition of current setting vessel toward platforms',         'severity' => 'major'],
                    ['flag' => 'No notification to platform operations',                           'severity' => 'major'],
                    ['flag' => 'Continued reliance on GNSS position after integrity alarm',        'severity' => 'critical'],
                ],
                'expected_references_json' => [
                    'SOLAS Chapter V Reg 19 (navigation equipment)',
                    'IMO MSC.1/Circ.1575 (GNSS vulnerabilities)',
                    'ECDIS — Navigation in GNSS-denied environment',
                    'Radar navigation and position fixing techniques',
                    'OCIMF — Navigation in offshore areas',
                    'Company SMS navigation procedures',
                ],
                'red_flags_json' => [
                    'Continuing to trust GNSS position after integrity alarm near platforms',
                    'No speed reduction in GNSS-compromised waters near fixed structures',
                    'Not using radar as primary position source',
                    'Ignoring 1.5-knot current setting toward platforms',
                    'No communication with platform operations about position uncertainty',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 7 — CRISIS_RSP — Cargo spill at manifold + fire risk
            // ══════════════════════════════════════════════════════════════
            'TANKER_S07_CRIS_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 48,000 DWT product tanker loading unleaded gasoline at a refinery terminal. During the final stages of loading, a cargo hose burst occurs at the ship/shore manifold connection, resulting in a significant release of gasoline onto the main deck and into the water between the vessel and the jetty. The released product is pooling on deck around the manifold area and cascading over the side. You can see a visible sheen spreading on the water surface. The terminal has not yet shut down their pumps. The wind is blowing from the jetty toward the vessel at 10 knots. There is a strong smell of gasoline vapour on deck. The fixed gas detection system is showing >100% LEL at multiple deck locations.',
                        'your_position'       => 'Cargo control room, alerted by cargo watch AB who witnessed the burst. C/O running to the manifold area.',
                        'available_resources' => 'Emergency shutdown system (ESD ship and shore link), Ship/Shore Safety Checklist, fire main system, foam monitors on deck, SOPEP equipment (absorbent booms, dispersant), IGS, portable fire extinguishers, terminal fire brigade on standby, 20 crew total.',
                        'current_conditions'  => 'Daylight, wind 10 knots from jetty toward vessel, no ignition sources identified yet, product: unleaded gasoline (flash point -43°C), terminal berth with two other vessels in vicinity.',
                    ],
                    'tr' => [
                        'situation'           => '48.000 DWT ürün tankerinin kaptanısınız, rafineri terminalinde kurşunsuz benzin yüklüyorsunuz. Yüklemenin son aşamalarında gemi/kıyı manifold bağlantısında kargo hortum patlaması meydana geliyor ve ana güverteye ve gemi ile iskele arasındaki suya önemli miktarda benzin salınıyor. Ürün manifold alanında güvertede birikmiş ve kenardan aşağı akıyor. Su yüzeyinde görünür bir film yayılıyor. Terminal henüz pompalarını kapatmadı. Rüzgar iskeleden gemiye doğru 10 knot esiyor. Güvertede güçlü benzin buharı kokusu var. Sabit gaz algılama sistemi birden fazla güverte noktasında >%100 LEL gösteriyor.',
                        'your_position'       => 'Kargo kontrol odası, patlamaya tanık olan kargo vardiyası Usta Gemici tarafından uyarıldınız. Birinci zabit manifold alanına koşuyor.',
                        'available_resources' => 'Acil kapatma sistemi (ESD gemi ve kıyı bağlantısı), Gemi/Kıyı Güvenlik Kontrol Listesi, yangın ana sistemi, güverte köpük monitörleri, SOPEP ekipmanı (emici barajlar, dağıtıcı), IGS, portatif yangın söndürücüler, beklemede terminal itfaiyesi, toplam 20 mürettebat.',
                        'current_conditions'  => 'Gündüz, iskeleden gemiye 10 knot rüzgar, henüz ateşleme kaynağı tespit edilmemiş, ürün: kurşunsuz benzin (parlama noktası -43°C), civarda iki gemi daha bulunan terminal rıhtımı.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан танкера 48 000 DWT, погрузка бензина на НПЗ. Разрыв грузового шланга на манифолде — значительный разлив бензина на палубу и в воду между судном и причалом. Терминал ещё не остановил насосы. Ветер 10 узлов от причала к судну. Запах паров бензина на палубе. Газоанализатор показывает >100% НПВ в нескольких точках.',
                        'your_position'       => 'Грузовая рубка, предупреждён матросом. Старпом бежит к манифолду.',
                        'available_resources' => 'Аварийная остановка (ESD), система пожаротушения, пенные мониторы, оборудование SOPEP, ИГС, переносные огнетушители, пожарная бригада терминала, 20 человек.',
                        'current_conditions'  => 'День, ветер 10 узлов от причала к судну, бензин (т. вспышки -43°C), два других судна у терминала.',
                    ],
                    'az' => [
                        'situation'           => '48.000 DWT tankerinin kapitanısınız, NEZ terminalında benzin yükləyirsiniz. Yükləmənin sonunda manifold bağlantısında hortum partlayışı — göyərtəyə və suya benzin axıdı. Terminal nasoslarını hələ dayandırmayıb. Külək rıhtımdan gəmiyə 10 düyün. Güclü benzin buharı qoxusu. Qaz detektoru >100% AYH göstərir.',
                        'your_position'       => 'Yük nəzarət otağı, matros tərəfindən xəbərdar edilib. Birinci stürman manifolda qaçır.',
                        'available_resources' => 'Təcili dayandırma sistemi (ESD), yanğın sistemi, köpük monitorları, SOPEP avadanlığı, İQS, portativ söndürücülər, terminal yanğın briqadası, 20 nəfər.',
                        'current_conditions'  => 'Gündüz, rıhtımdan gəmiyə 10 düyün külək, benzin (alovlanma -43°C), terminalda daha iki gəmi.',
                    ],
                ],
                'decision_prompt'      => 'What is your emergency sequence of actions for the gasoline spill? How do you manage the immediate fire/explosion risk, stop the spill, protect crew, and handle the environmental response?',
                'decision_prompt_i18n' => [
                    'tr' => 'Benzin dökülmesi için acil eylem sıranız nedir? Anlık yangın/patlama riskini, dökülmenin durdurulmasını, mürettebat korumasını ve çevresel müdahaleyi nasıl yönetiyorsunuz?',
                    'ru' => 'Какова ваша последовательность аварийных действий при разливе бензина? Как управляете риском пожара/взрыва, остановкой разлива, защитой экипажа и экологическим реагированием?',
                    'az' => 'Benzin tökülməsi üçün təcili hərəkət ardıcıllığınız nədir? Yanğın/partlayış riskini, tökülmənin dayandırılmasını, ekipaj qorunmasını və ekoloji cavab tədbirlərini necə idarə edirsiniz?',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'emergency_shutdown_and_spill_control',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Does not activate emergency shutdown; attempts to manually close valves in a gasoline vapour atmosphere without stopping the source.',
                            '2' => 'Activates ship-side ESD but does not verify that the terminal has stopped pumping; no manifold valve closure confirmation.',
                            '3' => 'Immediately activates ship ESD and contacts terminal to stop pumps, confirms manifold valves closed, orders deck drainage to be checked to prevent further overboard discharge.',
                            '4' => 'Rapid emergency response: activates ship ESD immediately, contacts terminal via dedicated emergency channel to confirm shore-side shutdown, C/O verifies manifold valve closure (from upwind side), deck scuppers plugged to contain pooled product, IGS verified to be maintaining tank pressure.',
                            '5' => 'Expert emergency shutdown: instantaneous ESD activation (both ship and shore link), simultaneous radio communication to terminal on emergency channel, C/O directed to verify manifold closure from safe position (upwind, wearing BA), deck drainage controlled (scuppers plugged, save-all confirmed), remaining cargo in hose drained to slop tank, IGS positive pressure confirmed in all cargo tanks, and terminal fire brigade placed on immediate standby.',
                        ],
                    ],
                    [
                        'axis'   => 'fire_and_explosion_prevention',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'No awareness of the extreme fire/explosion risk from gasoline vapour on deck; allows crew to work in the area without precautions.',
                            '2' => 'Recognises the fire risk but only posts a fire watch; does not eliminate ignition sources or establish exclusion zones.',
                            '3' => 'Orders all non-essential crew away from the manifold area, eliminates all potential ignition sources (galley fires, electrical equipment), ensures foam monitors are trained on the spill area, and contacts terminal to eliminate their ignition sources.',
                            '4' => 'Comprehensive fire prevention: all ignition sources eliminated vessel-wide (galley, incinerator, electrical switches), exclusion zone established on deck, foam monitors pre-positioned and ready, fire parties mustered in BA with hoses charged, terminal notified to eliminate ignition sources on jetty, neighbouring vessels warned via VHF.',
                            '5' => 'Expert fire/explosion prevention: immediate elimination of all ignition sources on vessel (galley secured, no electrical switching, mobile phones prohibited on deck), wind direction assessed — gasoline vapour drifting over vessel from jetty confirms extreme danger, exclusion zone expanded, foam monitors activated on continuous spray over the spill area to suppress vapour, fire parties in full BA at standby positions, terminal and all neighbouring vessels warned, considers requesting tug standby for emergency departure if fire risk escalates, and establishes clear criteria for evacuation of non-essential crew.',
                        ],
                    ],
                    [
                        'axis'   => 'environmental_response_and_reporting',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No environmental response; ignores the visible sheen on the water and makes no reports.',
                            '2' => 'Notes the spill into the water but takes no containment action; delayed reporting.',
                            '3' => 'Deploys SOPEP equipment (absorbent booms around the vessel), activates SOPEP plan, notifies port authority and local maritime authority of the spill.',
                            '4' => 'Structured SOPEP response: absorbent booms deployed, deck spill contained with sand/absorbent, port authority and maritime authority notified immediately, MARPOL reporting initiated, estimates spill quantity for reporting, coordinates with terminal on shore-side containment.',
                            '5' => 'Expert environmental response: immediate SOPEP activation with designated team, booms deployed to contain waterborne spill, deck pooling managed with absorbent materials, port state notified per MARPOL 73/78 Annex I, flag state notified, P&I Club contacted for pollution response coordination, spill quantity estimated and documented with photographs, coordinates with terminal and port on joint containment/clean-up, and preserves evidence (samples, photographs, cargo records, hose inspection certificate) for subsequent investigation.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No immediate ESD activation',                                     'severity' => 'critical'],
                    ['flag' => 'No ignition source elimination with gasoline vapour on deck',      'severity' => 'critical'],
                    ['flag' => 'No notification to terminal to stop pumps',                       'severity' => 'critical'],
                    ['flag' => 'No SOPEP activation for waterborne spill',                        'severity' => 'major'],
                    ['flag' => 'No port authority notification of pollution',                      'severity' => 'major'],
                    ['flag' => 'No warning to neighbouring vessels',                              'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'ISGOTT Chapter 6 (hazards of petroleum)',
                    'ISGOTT Chapter 11 (cargo transfer — emergency procedures)',
                    'MARPOL 73/78 Annex I (oil pollution prevention)',
                    'SOPEP (Shipboard Oil Pollution Emergency Plan)',
                    'SOLAS Chapter II-2 (fire protection)',
                    'Ship/Shore Safety Checklist — Emergency procedures',
                    'ISM Code Section 8 (emergency preparedness)',
                ],
                'red_flags_json' => [
                    'Not activating ESD immediately upon hose burst',
                    'Allowing crew into gasoline vapour zone without BA',
                    'No ignition source control with >100% LEL on deck',
                    'Ignoring waterborne spill — no SOPEP response',
                    'Not warning neighbouring vessels of fire/explosion risk',
                    'Attempting to continue loading after manifold spill',
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // SLOT 8 — TRADEOFF — ETA pressure vs safety + CII/fuel + terminal slot
            // ══════════════════════════════════════════════════════════════
            'TANKER_S08_TRADE_001' => [
                'briefing_json' => [
                    'en' => [
                        'situation'           => 'You are Master of a 80,000 DWT Aframax crude oil tanker, laden, en route from West Africa to a European refinery terminal. You are 4 days from the discharge port. The charterer has just notified you that the terminal slot has been brought forward by 36 hours — if you miss the new slot, the next available window is 5 days later, costing the charterer approximately USD 200,000 in demurrage and storage. To make the new slot, you would need to increase speed from 12 knots (economical) to 14.5 knots. Your calculations show: (1) fuel consumption increases from 45 MT/day to 72 MT/day; (2) this will push your CII rating from a projected "C" to "D" for the year; (3) your remaining bunkers are sufficient but will leave only 15% reserve instead of company minimum 20%. The weather forecast shows Beaufort 6-7 in the Bay of Biscay (your route), which at 14.5 knots will cause significant bow slamming on the laden vessel.',
                        'your_position'       => 'Bridge, reviewing the charterer\'s request with C/O and C/E.',
                        'available_resources' => 'Weather routing service, CII calculator, fuel consumption tables, charter party terms, company operations department, engine performance data.',
                        'current_conditions'  => 'Currently fair weather, Beaufort 6-7 expected in Bay of Biscay (24 hours ahead), vessel laden and trimmed by stern.',
                    ],
                    'tr' => [
                        'situation'           => '80.000 DWT Aframax ham petrol tankerinin kaptanısınız, yüklü, Batı Afrika\'dan Avrupa rafineri terminaline gidiyorsunuz. Boşaltma limanına 4 gün mesafedesiniz. Kiracı, terminal slotunun 36 saat öne alındığını bildirdi — yeni slotu kaçırırsanız bir sonraki pencere 5 gün sonra ve bu kiracıya yaklaşık 200.000 USD demuraj ve depolama maliyeti çıkaracak. Yeni slotu yakalamak için hızı 12 knottan (ekonomik) 14,5 knota çıkarmanız gerekiyor. Hesaplarınız gösteriyor: (1) yakıt tüketimi günde 45 MT\'den 72 MT\'ye çıkar; (2) bu, yıllık CII derecelendirmenizi öngörülen "C"den "D"ye düşürür; (3) kalan yakıtınız yeterli ama şirket minimum %20 yerine yalnızca %15 rezerv bırakır. Hava tahmini rotanızdaki Biskay Körfezi\'nde Beaufort 6-7 gösteriyor ve 14,5 knotla yüklü gemide ciddi baş vuruş olacak.',
                        'your_position'       => 'Köprüüstü, kiracının talebini Birinci Zabit ve Başmühendisle değerlendiriyorsunuz.',
                        'available_resources' => 'Hava rotalama servisi, CII hesaplayıcı, yakıt tüketim tabloları, çarter parti şartları, şirket operasyon departmanı, motor performans verileri.',
                        'current_conditions'  => 'Şu an güzel hava, 24 saat sonra Biskay Körfezi\'nde Beaufort 6-7 bekleniyor, gemi yüklü ve kıçtan trimli.',
                    ],
                    'ru' => [
                        'situation'           => 'Вы капитан груженого Афрамакса 80 000 DWT из Западной Африки в Европу. До порта выгрузки 4 дня. Фрахтователь сдвинул терминальный слот на 36 часов раньше — пропуск означает 5 дней ожидания и ~200 000 USD убытков. Для нового слота нужно увеличить скорость с 12 до 14,5 узлов. Расход вырастет с 45 до 72 т/сут, CII ухудшится с «C» до «D», резерв бункера составит 15% вместо нормативных 20%. В Бискайском заливе — Бофорт 6-7, при 14,5 узлах на груженом судне будет сильный слеминг.',
                        'your_position'       => 'Мостик, обсуждение со старпомом и стармехом.',
                        'available_resources' => 'Метеомаршрутизация, CII калькулятор, таблицы расхода, чартер-партия, компания, данные двигателя.',
                        'current_conditions'  => 'Хорошая погода, Бофорт 6-7 ожидается через 24 часа (Бискайский залив), судно в грузу.',
                    ],
                    'az' => [
                        'situation'           => 'Yüklü 80.000 DWT Aframax tankerinin kapitanısınız, Qərbi Afrikadan Avropaya gedirsiniz. Limana 4 gün. Fraxtçı terminal slotunu 36 saat irəli çəkib — qaçırsanız növbəti pəncərə 5 gün sonra, ~200.000 USD itkisi. Yeni slot üçün sürəti 12-dən 14,5 düyünə artırmalısınız. Yanacaq sərfiyyatı 45-dən 72 MT/günə artır, CII «C»-dən «D»-yə düşür, yanacaq ehtiyatı 20% əvəzinə 15% qalır. Biskay körfəzində Bofort 6-7, yüklü gəmidə ciddi baş vurma olacaq.',
                        'your_position'       => 'Körpüüstü, birinci stürman və baş mühəndislə müzakirə.',
                        'available_resources' => 'Hava marşrutlaşdırma, CII hesablayıcı, yanacaq cədvəlləri, çarter şərtləri, şirkət, mühərrik məlumatları.',
                        'current_conditions'  => 'Hazırda yaxşı hava, 24 saata Biskay körfəzində Bofort 6-7, gəmi yüklü.',
                    ],
                ],
                'decision_prompt'      => 'How do you respond to the charterer\'s request to increase speed? Explain your analysis of the commercial pressure against the safety, structural, fuel, and CII considerations, and your communication strategy with all parties.',
                'decision_prompt_i18n' => [
                    'tr' => 'Kiracının hız artırma talebine nasıl yanıt veriyorsunuz? Ticari baskıyı güvenlik, yapısal, yakıt ve CII değerlendirmelerine karşı analizinizi ve tüm taraflarla iletişim stratejinizi açıklayın.',
                    'ru' => 'Как вы реагируете на запрос фрахтователя увеличить скорость? Объясните анализ коммерческого давления против безопасности, конструкционных, топливных и CII соображений, а также стратегию коммуникации.',
                    'az' => 'Fraxtçının sürəti artırma tələbinə necə cavab verirsiniz? Kommersiya təzyiqini təhlükəsizlik, struktur, yanacaq və CII mülahizələrinə qarşı analizinizi və bütün tərəflərlə əlaqə strategiyanızı izah edin.',
                ],
                'evaluation_axes_json' => [
                    [
                        'axis'   => 'safety_and_structural_assessment',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Agrees to full speed increase without considering structural loading in heavy weather or bow slamming risk on a laden tanker.',
                            '2' => 'Acknowledges weather concern but does not analyse the specific structural risk of driving a laden tanker at 14.5 knots into Beaufort 6-7.',
                            '3' => 'Assesses the bow slamming risk at 14.5 knots in Beaufort 6-7: recognises that a laden Aframax at this speed in head seas risks structural damage to the forepart, adjusts the speed profile to reduce speed through the weather zone.',
                            '4' => 'Thorough structural assessment: evaluates hull stress at 14.5 knots in forecast sea conditions using hull stress monitoring (if available) or vessel experience, considers fatigue loading on a laden tanker in head seas, plans a speed profile that maximises speed in fair weather and reduces appropriately in heavy weather, calculates whether a phased approach can still meet the slot.',
                            '5' => 'Expert structural and safety analysis: detailed assessment of bow slamming risk based on vessel speed, wave period, and laden draft forward; calculates forward draft emergence risk; considers whipping and springing loads on hull girder; develops an optimised speed profile (maximum speed in current fair weather, reduced to safe speed in Bay of Biscay, resume after clearing); runs multiple ETA scenarios to determine if the slot is achievable with a weather-adapted profile; documents the assessment and communicates technical limitations to all parties.',
                        ],
                    ],
                    [
                        'axis'   => 'fuel_and_cii_management',
                        'weight' => 0.30,
                        'rubric_levels' => [
                            '1' => 'No fuel sufficiency calculation; ignores the CII impact entirely.',
                            '2' => 'Notes the increased consumption and CII impact but treats them as "someone else\'s problem" without analysis or communication.',
                            '3' => 'Calculates fuel sufficiency for the higher speed (15% vs. 20% reserve), recognises the CII downgrade to "D" rating, and raises both concerns with the company.',
                            '4' => 'Comprehensive fuel and CII analysis: calculates fuel for multiple speed scenarios (12, 13, 14.5 knots) with corresponding reserves, quantifies the CII impact for each scenario, identifies that falling below 20% reserve violates company policy, presents the trade-off to company operations with clear recommendations.',
                            '5' => 'Expert fuel and CII management: detailed multi-scenario analysis showing fuel consumption, reserve percentages, and CII impact for each speed option; identifies intermediate speed options (e.g., 13.5 knots) that may partially meet the slot with acceptable fuel reserve; quantifies the CII correction plan if "D" rating is unavoidable; considers slow steaming on future voyages to recover CII; presents a comprehensive options paper to company with full cost-benefit for each scenario including bunker cost, CII penalty risk, demurrage savings, and structural risk.',
                        ],
                    ],
                    [
                        'axis'   => 'commercial_and_stakeholder_communication',
                        'weight' => 0.35,
                        'rubric_levels' => [
                            '1' => 'Either blindly agrees to the charterer or refuses without explanation; no communication with company.',
                            '2' => 'Communicates the decision to one party but does not coordinate between charterer, company, and terminal.',
                            '3' => 'Informs company of the request and safety concerns, communicates the vessel\'s limitations to the charterer through the company, and proposes a realistic ETA based on a safe speed profile.',
                            '4' => 'Effective multi-party coordination: advises company with detailed analysis (safety, fuel, CII), recommends a compromise speed profile through company operations, charterer informed of the technical limitations and best achievable ETA, terminal notified of the revised ETA, all communications documented.',
                            '5' => 'Expert stakeholder management: transparent communication with company including full written analysis of all options and their consequences; recommends the optimal commercial-safety balance (e.g., increase speed where safe, reduce in weather, best achievable ETA); company can make an informed commercial decision with full safety picture; charterer advised through proper channels with professional technical explanation; terminal kept informed; charter party deviation clause and weather clauses referenced; all communications documented for potential disputes; master\'s overriding authority reserved for safety-critical decisions.',
                        ],
                    ],
                ],
                'critical_omission_flags_json' => [
                    ['flag' => 'No structural risk assessment for high speed in heavy weather',       'severity' => 'critical'],
                    ['flag' => 'No fuel reserve calculation',                                        'severity' => 'critical'],
                    ['flag' => 'No CII impact assessment',                                           'severity' => 'major'],
                    ['flag' => 'No company consultation before agreeing to speed increase',           'severity' => 'major'],
                    ['flag' => 'No consideration of bow slamming risk on laden tanker',              'severity' => 'critical'],
                    ['flag' => 'No weather routing analysis for optimised speed profile',            'severity' => 'major'],
                ],
                'expected_references_json' => [
                    'MARPOL Annex VI — CII/EEXI requirements',
                    'IACS Rec. 34 — Standard wave data for ship structural assessment',
                    'Charter party terms (speed/consumption, weather deviation)',
                    'Company SMS voyage planning and fuel management',
                    'SOLAS Chapter V (voyage planning)',
                    'OCIMF — Tanker structural safety guidelines',
                    'ISM Code Section 5 (master\'s overriding authority)',
                ],
                'red_flags_json' => [
                    'Agreeing to unsafe speed in forecast heavy weather for commercial reasons',
                    'Reducing fuel reserve below company minimum without authorisation',
                    'Ignoring CII implications of speed increase',
                    'No structural assessment for bow slamming on laden tanker at high speed',
                    'Making commercial decision without company involvement',
                    'Prioritising charterer demands over vessel and crew safety',
                ],
            ],

        ];
    }
}
