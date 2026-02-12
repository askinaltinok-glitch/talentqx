<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder for new position templates:
 * - sales_associate (Mağaza Satış Temsilcisi)
 * - customer_support (Müşteri Hizmetleri)
 * - warehouse_picker (Depo Toplama Elemanı)
 */
class NewPositionTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            $this->getSalesAssociateTemplate(),
            $this->getCustomerSupportTemplate(),
            $this->getWarehousePickerTemplate(),
        ];

        foreach ($positions as $position) {
            $this->upsertPosition($position);
        }

        echo "\n========================================\n";
        echo "NEW POSITION TEMPLATES SEEDED\n";
        echo "========================================\n";

        $rows = DB::table('interview_templates')
            ->select('position_code', 'title', 'is_active')
            ->where('is_active', true)
            ->orderBy('position_code')
            ->get();

        foreach ($rows as $row) {
            echo sprintf("  %-20s | %s\n", $row->position_code, $row->title);
        }

        echo "\nTotal active templates: " . count($rows) . "\n";
    }

    private function upsertPosition(array $data): void
    {
        $existing = DB::table('interview_templates')
            ->where('version', 'v1')
            ->where('language', 'tr')
            ->where('position_code', $data['position_code'])
            ->first();

        $templateJson = json_encode($data['template'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if (!$existing) {
            DB::table('interview_templates')->insert([
                'id' => (string) Str::uuid(),
                'version' => 'v1',
                'language' => 'tr',
                'position_code' => $data['position_code'],
                'title' => $data['title'],
                'template_json' => $templateJson,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  [INSERT] {$data['position_code']}\n";
        } else {
            DB::table('interview_templates')
                ->where('id', $existing->id)
                ->update([
                    'title' => $data['title'],
                    'template_json' => $templateJson,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            echo "  [UPDATE] {$data['position_code']}\n";
        }
    }

    private function getSalesAssociateTemplate(): array
    {
        return [
            'position_code' => 'sales_associate',
            'title' => 'Magaza Satis Temsilcisi Interview Template',
            'template' => [
                'version' => 'v1',
                'language' => 'tr',
                'position' => [
                    'position_code' => 'sales_associate',
                    'title_tr' => 'Magaza Satis Temsilcisi',
                    'title_en' => 'Sales Associate',
                    'category' => 'Perakende',
                    'skill_gate' => ['gate' => 50, 'action' => 'HOLD', 'safety_critical' => false],
                ],
                'questions' => [
                    [
                        'slot' => 1,
                        'competency' => 'communication',
                        'question' => 'Bir musteriye urun onerirken nasil bir iletisim kurarsinis? Basarili bir satis deneyiminizi anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Musteriyle iletisim kuramadi, tek tarafli anlatti, dinlemedi',
                            '2' => 'Temel iletisim kurdu ama musteri ihtiyacini anlamadi',
                            '3' => 'Musteriyi dinledi, standart oneriler sundu',
                            '4' => 'Aktif dinleme, ihtiyac analizi, kisisellestirilmis oneri',
                            '5' => 'Mukemmel iletisim, guvene dayali iliski, tekrar satin alma sagladi',
                        ],
                        'positive_signals' => [
                            'Acik uclu sorular sordu',
                            'Musteriyi aktif dinledi',
                            'Ihtiyaca gore oneri sundu',
                            'Geri bildirim aldi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AGGRESSION', 'trigger_guidance' => 'Musteriye baskici veya asagilayici yaklasim', 'severity' => 'critical'],
                        ],
                    ],
                    [
                        'slot' => 2,
                        'competency' => 'accountability',
                        'question' => 'Bir musteriye yanlis urun veya bilgi verdiginiz bir durumda ne yaptiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Hatayi kabul etmedi veya musteriyi sucladi',
                            '2' => 'Hatayi kabul etti ama duzeltmedi',
                            '3' => 'Hatayi kabul etti ve standart cozum sundu',
                            '4' => 'Hemen duzeltici adim atti, musteri memnuniyetini sagladi',
                            '5' => 'Proaktif cozum, telafi onerisi, guven yeniden kuruldu',
                        ],
                        'positive_signals' => [
                            'Hatayi hemen kabul etti',
                            'Ozur diledi',
                            'Cozum odakli yaklasti',
                            'Takip yapti',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_BLAME', 'trigger_guidance' => 'Hatayi sisteme, urune veya musteriye yukleme', 'severity' => 'high'],
                        ],
                    ],
                    [
                        'slot' => 3,
                        'competency' => 'teamwork',
                        'question' => 'Magaza ekibinizle birlikte bir hedefi basarmak icin nasil calistiginizi anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Bireysel calismayi tercih etti, ekiple uyumsuz',
                            '2' => 'Ekiple calisti ama pasif katilim',
                            '3' => 'Ekip hedeflerine katki sagladi',
                            '4' => 'Aktif isbirligi, bilgi paylasimi, destekleyici',
                            '5' => 'Ekibi motive etti, koordinasyon sagladi, ortak basari vurguladi',
                        ],
                        'positive_signals' => [
                            'Ekip basarisini vurguladi',
                            'Bilgi ve deneyim paylasti',
                            'Esneklik gosterdi',
                            'Arkadaslarina destek oldu',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_EGO', 'trigger_guidance' => 'Bireysel basariyi one cikarma, ekip katkilarini kucumseme', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 4,
                        'competency' => 'stress_resilience',
                        'question' => 'Yogun kampanya doneminde veya indirim gunlerinde nasil calistiniz? Stresle nasil basa ciktiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Stres altinda performans dustu, hatalar artti',
                            '2' => 'Zorlanarak tamamladi, stres belli oldu',
                            '3' => 'Makul performans, temel stres yonetimi',
                            '4' => 'Sakin kaldi, verimli calisti, musterilere olumlu yaklasti',
                            '5' => 'Baski altinda ustun performans, ekibi de motive etti',
                        ],
                        'positive_signals' => [
                            'Onceliklendirme yapti',
                            'Sakin kaldi',
                            'Pozitif tutum korudu',
                            'Enerjisini yonetti',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_UNSTABLE', 'trigger_guidance' => 'Stres altinda kontrolsuz tepkiler', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 5,
                        'competency' => 'adaptability',
                        'question' => 'Magazada yeni bir urun grubu veya satis teknigi geldiginde nasil uyum sagladiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Degisiklige direndi, eski yontemi kullanmaya devam etti',
                            '2' => 'Zorunlu olarak uyum sagladi, isteksiz',
                            '3' => 'Degisikligi kabul etti, makul surede ogrendi',
                            '4' => 'Hizla adapte oldu, yeni yontemi benimsedi',
                            '5' => 'Degisimi firsata cevirdi, baskalarina da ogretti',
                        ],
                        'positive_signals' => [
                            'Ogrenmeye acik',
                            'Yeni yontemleri denedi',
                            'Geri bildirim aldi',
                            'Baskalarina yardim etti',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Degisimden kacinma, ogrenme sorumlulugundan kacma', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 6,
                        'competency' => 'learning_agility',
                        'question' => 'Yeni bir urun veya marka hakkinda hizla bilgi edinmeniz gereken bir durumu anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Ogrenme cabasi gostermedi, bilgisiz kaldi',
                            '2' => 'Temel bilgi edindi ama yetersiz',
                            '3' => 'Gerekli bilgiyi ogrendi',
                            '4' => 'Hizla derinlemesine ogrendi, musterilere aktardi',
                            '5' => 'Uzman seviyesine ulasti, ekibi de egitti',
                        ],
                        'positive_signals' => [
                            'Proaktif arastirma yapti',
                            'Birden fazla kaynak kullandi',
                            'Pratik yapti',
                            'Bilgisini paylasti',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Ogrenme sorumlulugundan kacinma', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 7,
                        'competency' => 'integrity',
                        'question' => 'Bir musteri veya is arkadasi sizden uygun olmayan bir sey istediginde nasil davrandiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Etik disi istegi kabul etti veya goz yumdu',
                            '2' => 'Kararsiz kaldi, net tutum almadi',
                            '3' => 'Kibarca reddetti ama aciklama yapmadi',
                            '4' => 'Nazikce reddetti, sebebini acikladi, alternatif sundu',
                            '5' => 'Guclu etik durusu, gerektiginde ust kademeye bildirdi',
                        ],
                        'positive_signals' => [
                            'Net etik cerceve',
                            'Kurallara bagli',
                            'Nazik ama kararli',
                            'Alternatif sundu',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Duruma gore degisen etik standartlar', 'severity' => 'high'],
                        ],
                    ],
                    [
                        'slot' => 8,
                        'competency' => 'role_competence',
                        'question' => 'Gunluk satis sureclerinizi anlatir misiniz? Musteri karsilama, ihtiyac analizi, satis kapatma adimlarinda neler yapiyorsunuz?',
                        'method' => 'Direct',
                        'scoring_rubric' => [
                            '1' => 'Temel satis bilgisi yok, deneyim cok sinirli',
                            '2' => 'Bazi adimlari biliyor ama eksik',
                            '3' => 'Standart satis surecini uygulayabilir',
                            '4' => 'Tum adimlari biliyor, musteri odakli yaklasim',
                            '5' => 'Uzman: ikna teknikleri, objection handling, upselling',
                        ],
                        'positive_signals' => [
                            'Musteri odakli yaklasim',
                            'Surec adimlarini bildi',
                            'Satis kapatma teknigi anlatti',
                            'Somut ornekler verdi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Deneyim abartisi, detaylarda tutarsizlik', 'severity' => 'high'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getCustomerSupportTemplate(): array
    {
        return [
            'position_code' => 'customer_support',
            'title' => 'Musteri Hizmetleri Temsilcisi Interview Template',
            'template' => [
                'version' => 'v1',
                'language' => 'tr',
                'position' => [
                    'position_code' => 'customer_support',
                    'title_tr' => 'Musteri Hizmetleri Temsilcisi',
                    'title_en' => 'Customer Support Representative',
                    'category' => 'Musteri Hizmetleri',
                    'skill_gate' => ['gate' => 55, 'action' => 'HOLD', 'safety_critical' => false],
                ],
                'questions' => [
                    [
                        'slot' => 1,
                        'competency' => 'communication',
                        'question' => 'Kizgin veya sikayetci bir musteri ile nasil iletisim kurarsinis? Zor bir musteri deneyiminizi anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Musteriye karsi savunmaci veya sert davranis',
                            '2' => 'Dinledi ama empati kuramadi',
                            '3' => 'Sabirli dinledi, standart cozum sundu',
                            '4' => 'Empati ile yaklasti, memnuniyet sagladi',
                            '5' => 'Zor durumu firsata cevirdi, musteri sadakati yaratti',
                        ],
                        'positive_signals' => [
                            'Aktif dinleme',
                            'Empati ifadeleri',
                            'Sakin ve profesyonel',
                            'Cozum odakli',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AGGRESSION', 'trigger_guidance' => 'Musteriye kaba veya asagilayici ifadeler', 'severity' => 'critical'],
                        ],
                    ],
                    [
                        'slot' => 2,
                        'competency' => 'accountability',
                        'question' => 'Musteriye yanlis bilgi verdiginiz veya bir sikayet yanlis yonettiginiz bir durumda ne yaptiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Hatayi kabul etmedi, sistemi veya musteriyi sucladi',
                            '2' => 'Hatayi kabul etti ama cozum uretmedi',
                            '3' => 'Hatayi kabul etti ve prosedure uydu',
                            '4' => 'Proaktif duzeltme, musteri memnuniyeti sagladi',
                            '5' => 'Telafi onerisi, takip yapti, surecte iyilestirme onerdi',
                        ],
                        'positive_signals' => [
                            'Sorumluluk aldi',
                            'Hemen harekete gecti',
                            'Musteri ile takip yapti',
                            'Ders cikardi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_BLAME', 'trigger_guidance' => 'Surekli dissal faktorlere atif', 'severity' => 'high'],
                        ],
                    ],
                    [
                        'slot' => 3,
                        'competency' => 'teamwork',
                        'question' => 'Karmasik bir musteri sorununu cozmek icin diger departmanlarla nasil calistiginizi anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Isbirligi yapmadi, sorunu baskasina atti',
                            '2' => 'Temel iletisim kurdu ama koordinasyon zayif',
                            '3' => 'Ilgili kisilarla iletisim kurdu, cozum bulundu',
                            '4' => 'Proaktif koordinasyon, sureci yonetti',
                            '5' => 'Departmanlar arasi kopru kurdu, sistem iyilestirmesi onerdi',
                        ],
                        'positive_signals' => [
                            'Iletisimi baslatti',
                            'Takip yapti',
                            'Bilgi paylasti',
                            'Sonuca odaklandi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Sorumluluktan kacinma, sorunu baskasina yikmak', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 4,
                        'competency' => 'stress_resilience',
                        'question' => 'Yogun cagri/talep donemlerinde ust uste zor musterilerle karsilastiginizda nasil basa ciktiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Tukenmislik yasadi, performans ciddi dustu',
                            '2' => 'Zorlanarak devam etti, stres belli oldu',
                            '3' => 'Makul performans korudu',
                            '4' => 'Sakin kaldi, her musteriye profesyonel yaklasti',
                            '5' => 'Baski altinda bile pozitif, ekibi de destekledi',
                        ],
                        'positive_signals' => [
                            'Stres yonetim teknigi',
                            'Ara verme stratejisi',
                            'Pozitif tutum',
                            'Enerji yonetimi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_UNSTABLE', 'trigger_guidance' => 'Stres altinda kontrolunu kaybetme', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 5,
                        'competency' => 'adaptability',
                        'question' => 'Yeni bir CRM sistemi veya musteri hizmetleri proseduru geldiginde nasil uyum sagladiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Degisiklige direndi, eski usulde israr etti',
                            '2' => 'Zoraki uyum sagladi',
                            '3' => 'Degisikligi kabul etti, ogrendi',
                            '4' => 'Hizla adapte oldu, yeni sistemin avantajlarini gordu',
                            '5' => 'Degisimi liderlik etti, baskalarina ogretti',
                        ],
                        'positive_signals' => [
                            'Ogrenmeye istekli',
                            'Yeni sistemi benimsedi',
                            'Sorular sordu',
                            'Geri bildirim verdi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Degisimden sistemik kacinma', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 6,
                        'competency' => 'learning_agility',
                        'question' => 'Yeni bir urun veya hizmet hakkinda hizla bilgi edinmeniz gereken bir durumu anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Bilgisiz kaldi, musterilere yanlis bilgi verdi',
                            '2' => 'Temel bilgi edindi',
                            '3' => 'Yeterli bilgi ogrendi',
                            '4' => 'Hizla derinlemesine ogrendi',
                            '5' => 'Uzman oldu, ekibi egitti',
                        ],
                        'positive_signals' => [
                            'Proaktif ogrenme',
                            'Kaynaklari etkin kullandi',
                            'Notlar aldi',
                            'Bilgi paylasti',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Ogrenme sorumlulugundan kacinma', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 7,
                        'competency' => 'integrity',
                        'question' => 'Bir musteri sizden sirket politikasina aykiri bir istekte bulundugunda nasil davrandiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Politikaya aykiri istegi kabul etti',
                            '2' => 'Kararsiz kaldi',
                            '3' => 'Kibarca reddetti',
                            '4' => 'Aciklama yapti, alternatif sundu',
                            '5' => 'Net etik durusu, eskalasyon yapti',
                        ],
                        'positive_signals' => [
                            'Politikalara hakim',
                            'Nazik ama net',
                            'Alternatif sundu',
                            'Gerektiginde eskalasyon',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Kurallari duruma gore esnetme', 'severity' => 'high'],
                        ],
                    ],
                    [
                        'slot' => 8,
                        'competency' => 'role_competence',
                        'question' => 'Tipik bir musteri hizmeti talebini bastan sona nasil yonetirsiniz? Sikayet alma, arastirma, cozum ve kapatis adimlarini anlatir misiniz?',
                        'method' => 'Direct',
                        'scoring_rubric' => [
                            '1' => 'Temel sikayet yonetimi bilgisi yok',
                            '2' => 'Bazi adimlari biliyor',
                            '3' => 'Standart sureci uygulayabilir',
                            '4' => 'Tum adimlari biliyor, empati ile yonetiyor',
                            '5' => 'Uzman: de-eskalasyon, problem solving, memnuniyet olcumu',
                        ],
                        'positive_signals' => [
                            'Surec adimlarini dogru siraladi',
                            'Empati vurguladi',
                            'Takip/kapatis adimlarini anlatti',
                            'Dokumantasyon onemi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Deneyim abartisi', 'severity' => 'high'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getWarehousePickerTemplate(): array
    {
        return [
            'position_code' => 'warehouse_picker',
            'title' => 'Depo Toplama Elemani Interview Template',
            'template' => [
                'version' => 'v1',
                'language' => 'tr',
                'position' => [
                    'position_code' => 'warehouse_picker',
                    'title_tr' => 'Depo Toplama Elemani',
                    'title_en' => 'Warehouse Picker',
                    'category' => 'Lojistik',
                    'skill_gate' => ['gate' => 45, 'action' => 'HOLD', 'safety_critical' => true],
                ],
                'questions' => [
                    [
                        'slot' => 1,
                        'competency' => 'communication',
                        'question' => 'Depoda ekip arkadaslariniz veya supervisor ile iletisim kurmaniz gereken bir durumu anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Iletisim kurmadi, bilgi paylasmadi',
                            '2' => 'Temel iletisim, yetersiz bilgi aktarimi',
                            '3' => 'Gerekli bilgiyi paylasti',
                            '4' => 'Net ve zamaninda iletisim, koordinasyon sagladi',
                            '5' => 'Proaktif bilgi paylasimi, ekip koordinasyonunu iyilestirdi',
                        ],
                        'positive_signals' => [
                            'Zamaninda bilgi paylasti',
                            'Net ve kisa iletisim',
                            'Sorulari cevapladi',
                            'Geri bildirim aldi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Iletisimden kacinma', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 2,
                        'competency' => 'accountability',
                        'question' => 'Yanlis urun topladığınız veya bir siparis hatasinda ne yaptiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Hatayi sakladi veya baskasina yukledi',
                            '2' => 'Hatayi kabul etti ama duzeltmedi',
                            '3' => 'Hatayi bildirdi ve duzeltme yapildi',
                            '4' => 'Hemen bildirdi, nedenini arastirdi, duzeltici adim atti',
                            '5' => 'Kok neden analizi, surec iyilestirmesi onerdi',
                        ],
                        'positive_signals' => [
                            'Hatayi hemen bildirdi',
                            'Sorumluluk aldi',
                            'Duzeltme icin calisli',
                            'Ders cikardi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_BLAME', 'trigger_guidance' => 'Hatayi sisteme veya baskasina yukleme', 'severity' => 'high'],
                        ],
                    ],
                    [
                        'slot' => 3,
                        'competency' => 'teamwork',
                        'question' => 'Depoda yogun bir gunde ekiple birlikte nasil calistiginizi anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Bireysel calisti, ekibe katki saglamadi',
                            '2' => 'Ekiple calisti ama pasif',
                            '3' => 'Ekibe katki sagladi',
                            '4' => 'Aktif isbirligi, is paylasimi',
                            '5' => 'Ekip koordinasyonu sagladi, herkesin yukunu dengeledi',
                        ],
                        'positive_signals' => [
                            'Yardim teklif etti',
                            'Is paylasimina acik',
                            'Ekip basarisini vurguladi',
                            'Esneklik gosterdi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_EGO', 'trigger_guidance' => 'Bireysel basariyi one cikarma', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 4,
                        'competency' => 'stress_resilience',
                        'question' => 'Siparis yogunluğu arttığında ve zaman baskisi oldugunda nasil calistiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Stres altinda hatalar artti, performans dustu',
                            '2' => 'Zorlanarak tamamladi',
                            '3' => 'Makul performans korudu',
                            '4' => 'Sakin kaldi, hiz ve dogrulugu dengeledi',
                            '5' => 'Baski altinda ustun performans, ekibi de motive etti',
                        ],
                        'positive_signals' => [
                            'Onceliklendirme yapti',
                            'Sakin kaldi',
                            'Hizini korudu',
                            'Hatalardan kacindi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_UNSTABLE', 'trigger_guidance' => 'Stres altinda kontrolsuz tepkiler', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 5,
                        'competency' => 'adaptability',
                        'question' => 'Depoda yeni bir sistem, rota veya prosedur geldiginde nasil uyum sagladiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Degisiklige direndi',
                            '2' => 'Zorunlu olarak uyum sagladi',
                            '3' => 'Degisikligi kabul etti, ogrendi',
                            '4' => 'Hizla adapte oldu',
                            '5' => 'Degisimi benimsedi, iyilestirme onerdi',
                        ],
                        'positive_signals' => [
                            'Ogrenmeye acik',
                            'Yeni sistemi denedi',
                            'Soru sordu',
                            'Geri bildirim verdi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Degisimden kacinma', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 6,
                        'competency' => 'learning_agility',
                        'question' => 'Yeni bir depo ekipmani veya yazilimi ogrenmeniz gereken bir durumu anlatir misiniz?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Ogrenme cabasi gostermedi',
                            '2' => 'Temel duzeyde ogrendi',
                            '3' => 'Yeterli seviyede ogrendi',
                            '4' => 'Hizla ogrendi ve uygulamaya gecti',
                            '5' => 'Uzman oldu, baskalarini egitti',
                        ],
                        'positive_signals' => [
                            'Egitimi dikkatle takip etti',
                            'Pratik yapti',
                            'Sorular sordu',
                            'Ogrendiklerini uyguladi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Ogrenme sorumlulugundan kacinma', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 7,
                        'competency' => 'integrity',
                        'question' => 'Depoda guvenlik kurallarina uymayan bir durum veya davranis gordugunde ne yaptiniz?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Gormezden geldi veya kendisi de kurallari esnetti',
                            '2' => 'Fark etti ama harekete gecmedi',
                            '3' => 'Uyardi veya bildirdi',
                            '4' => 'Hemen mudahale etti, gerektiginde raporladi',
                            '5' => 'Guvenlik kulturu savunucusu, onleyici adimlar atti',
                        ],
                        'positive_signals' => [
                            'Guvenlik onceligi',
                            'Kurallara bagli',
                            'Bildirme cesurlubu',
                            'Onleyici yaklasim',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Guvenlik kurallarini onemsemedigi ima', 'severity' => 'high'],
                        ],
                    ],
                    [
                        'slot' => 8,
                        'competency' => 'role_competence',
                        'question' => 'Gunluk depo toplama sureclerinizi anlatir misiniz? Siparis alma, rota planlama, toplama, kontrol ve paketleme adimlarinda neler yapiyorsunuz?',
                        'method' => 'Direct',
                        'scoring_rubric' => [
                            '1' => 'Temel depo bilgisi yok',
                            '2' => 'Bazi adimlari biliyor',
                            '3' => 'Standart sureci uygulayabilir',
                            '4' => 'Tum adimlari biliyor, verimlilik odakli',
                            '5' => 'Uzman: optimizasyon, forklift/transpalet kullanimi, WMS',
                        ],
                        'positive_signals' => [
                            'Surec adimlarini bildi',
                            'Verimlilik vurguladi',
                            'Guvenlik protokollerini bildi',
                            'Ekipman kullanimi',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Deneyim abartisi', 'severity' => 'high'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
