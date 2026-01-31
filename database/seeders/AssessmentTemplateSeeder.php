<?php

namespace Database\Seeders;

use App\Models\AssessmentTemplate;
use Illuminate\Database\Seeder;

class AssessmentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            $this->getSoforTemplate(),
            $this->getDepoTemplate(),
            $this->getTezgahtarTemplate(),
            $this->getUretimTemplate(),
            $this->getTemizlikTemplate(),
            $this->getMagazaMuduruTemplate(),
            $this->getKoordinatorTemplate(),
        ];

        foreach ($templates as $template) {
            AssessmentTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }

    private function getSoforTemplate(): array
    {
        return [
            'name' => 'Sofor Degerlendirmesi',
            'slug' => 'sofor-assessment',
            'role_category' => 'sofor',
            'description' => 'Dagitim ve sevkiyat soforleri icin kapsamli yetkinlik degerlendirmesi',
            'competencies' => [
                ['code' => 'TRAFFIC_RULES', 'name' => 'Trafik Kurallari Bilgisi', 'description' => 'Trafik isaretleri, kurallar ve yasal duzenlemeler', 'weight' => 1.5],
                ['code' => 'SAFE_DRIVING', 'name' => 'Guvenli Surus', 'description' => 'Defansif surus, risk yonetimi', 'weight' => 2.0],
                ['code' => 'ROUTE_PLANNING', 'name' => 'Rota Planlama', 'description' => 'Verimli rota olusturma ve navigasyon', 'weight' => 1.0],
                ['code' => 'VEHICLE_CARE', 'name' => 'Arac Bakimi', 'description' => 'Temel arac kontrolu ve bakim bilinci', 'weight' => 1.0],
                ['code' => 'TIME_MANAGEMENT', 'name' => 'Zaman Yonetimi', 'description' => 'Teslimat surelerine uyum', 'weight' => 1.0],
                ['code' => 'CUSTOMER_COMM', 'name' => 'Musteri Iletisimi', 'description' => 'Teslimat sirasinda iletisim', 'weight' => 0.8],
                ['code' => 'STRESS_MGMT', 'name' => 'Stres Yonetimi', 'description' => 'Trafik ve zaman baskisi altinda sakinlik', 'weight' => 1.2],
                ['code' => 'PROBLEM_SOLVING', 'name' => 'Problem Cozme', 'description' => 'Beklenmedik durumlarda cozum uretme', 'weight' => 1.0],
            ],
            'red_flags' => [
                ['code' => 'TRAFFIC_VIOLATION', 'name' => 'Trafik Ihlali Egilimi', 'description' => 'Kurallari ihlal etme egilimi', 'severity' => 'critical'],
                ['code' => 'AGGRESSIVE_DRIVING', 'name' => 'Agresif Surus', 'description' => 'Ofkeli veya tehlikeli surus davranisi', 'severity' => 'critical'],
                ['code' => 'ALCOHOL_TOLERANCE', 'name' => 'Alkol Toleransi', 'description' => 'Alkol kullanimina toleransli tutum', 'severity' => 'critical'],
                ['code' => 'FATIGUE_IGNORANCE', 'name' => 'Yorgunluk Gormezden Gelme', 'description' => 'Yorgun surusu normal gorme', 'severity' => 'high'],
                ['code' => 'PHONE_WHILE_DRIVING', 'name' => 'Suruste Telefon', 'description' => 'Surus sirasinda telefon kullanimi', 'severity' => 'high'],
            ],
            'questions' => [
                [
                    'order' => 1,
                    'type' => 'scenario',
                    'text' => 'Yogun trafik sirasinda bir arac aniden oniinuze kirildi. Ne yaparsiniz?',
                    'competency_code' => 'SAFE_DRIVING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Korna calar ve el hareketi yaparim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Yavaslar, mesafe birakir ve sakin kalirim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Hizla serit degistiririm', 'score' => 20],
                        ['value' => 'd', 'text' => 'Ayni sekilde ona yaklasirim', 'score' => 0],
                    ],
                ],
                [
                    'order' => 2,
                    'type' => 'scenario',
                    'text' => 'Teslimat rotanizda kaza nedeniyle yol kapali. Gecikmeyeceksiniz ama alternatif rota 20 dk uzun. Ne yaparsiniz?',
                    'competency_code' => 'ROUTE_PLANNING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Alternatif rotayi kullanirim ve merkezi bilgilendiririm', 'score' => 100],
                        ['value' => 'b', 'text' => 'Yolun acilmasini beklerim', 'score' => 30],
                        ['value' => 'c', 'text' => 'Yasak olsa da yan yollardan gecmeye calisirim', 'score' => 0],
                        ['value' => 'd', 'text' => 'Teslimatı ertelerim', 'score' => 20],
                    ],
                ],
                [
                    'order' => 3,
                    'type' => 'knowledge',
                    'text' => 'Sari yanip sonen trafik isigi ne anlama gelir?',
                    'competency_code' => 'TRAFFIC_RULES',
                    'options' => [
                        ['value' => 'a', 'text' => 'Durmadan gecebilirsiniz', 'score' => 0],
                        ['value' => 'b', 'text' => 'Dikkatli olun, yavası geçin', 'score' => 100],
                        ['value' => 'c', 'text' => 'Durmaniz gerekir', 'score' => 20],
                        ['value' => 'd', 'text' => 'Sadece gece gecerlidir', 'score' => 0],
                    ],
                ],
                [
                    'order' => 4,
                    'type' => 'behavior',
                    'text' => 'Uzun bir surus sonrasi kendinizi yorgun hissediyorsunuz ama 2 teslimat daha var. Ne yaparsiniz?',
                    'competency_code' => 'STRESS_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Mola veririm ve merkezi bilgilendiririm', 'score' => 100],
                        ['value' => 'b', 'text' => 'Kahve icip devam ederim', 'score' => 40],
                        ['value' => 'c', 'text' => 'Biraz daha hizli gidip bitiririm', 'score' => 0],
                        ['value' => 'd', 'text' => 'Pencereyi acar devam ederim', 'score' => 30],
                    ],
                ],
                [
                    'order' => 5,
                    'type' => 'knowledge',
                    'text' => 'Arac lastik basinci kontrolu ne siklikla yapilmalidir?',
                    'competency_code' => 'VEHICLE_CARE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Yilda bir', 'score' => 0],
                        ['value' => 'b', 'text' => 'Ayda bir veya uzun yol oncesi', 'score' => 100],
                        ['value' => 'c', 'text' => 'Sadece lastik inince', 'score' => 20],
                        ['value' => 'd', 'text' => 'Servis yaptiginda', 'score' => 30],
                    ],
                ],
                [
                    'order' => 6,
                    'type' => 'scenario',
                    'text' => 'Musteri teslimat sirasinda urun hasarli diyor ama siz hasarli gormuyorsunuz. Ne yaparsiniz?',
                    'competency_code' => 'CUSTOMER_COMM',
                    'options' => [
                        ['value' => 'a', 'text' => 'Musteri ile tartisirim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Dinler, fotograf ceker, merkeze bildiririm', 'score' => 100],
                        ['value' => 'c', 'text' => 'Hasar yok derim ve cikaririm', 'score' => 10],
                        ['value' => 'd', 'text' => 'Musteriyi aramasi icin merkeze yonlendiririm', 'score' => 50],
                    ],
                ],
                [
                    'order' => 7,
                    'type' => 'behavior',
                    'text' => 'Cep telefonunuz caliyor, arama muhim olabilir. Surerken ne yaparsiniz?',
                    'competency_code' => 'SAFE_DRIVING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Hemen bakarim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Uygun bir yerde durur bakarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Handsfree ile cevap veririm', 'score' => 60],
                        ['value' => 'd', 'text' => 'Mesaji okur cevap yazarim', 'score' => 0],
                    ],
                ],
                [
                    'order' => 8,
                    'type' => 'scenario',
                    'text' => 'Arac motorundan duman cikiyor. Ne yaparsiniz?',
                    'competency_code' => 'PROBLEM_SOLVING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Yol kenarinda durur, motoru kapatir, yardim isterim', 'score' => 100],
                        ['value' => 'b', 'text' => 'Yakin servise kadar devam ederim', 'score' => 10],
                        ['value' => 'c', 'text' => 'Kaputu acar bakarim', 'score' => 30],
                        ['value' => 'd', 'text' => 'Su dokerim', 'score' => 0],
                    ],
                ],
                [
                    'order' => 9,
                    'type' => 'behavior',
                    'text' => 'Is arkadasiniz icki ictigini ama az oldugunu ve surebilecegini soyluyor. Ne yaparsiniz?',
                    'competency_code' => 'SAFE_DRIVING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Az ictiyse sorun olmaz derim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Kesinlikle surmemesini soyler, gerekirse mudahale ederim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Kendi kararini vermesini beklerim', 'score' => 10],
                        ['value' => 'd', 'text' => 'Dikkatli surmesini onerim', 'score' => 0],
                    ],
                ],
                [
                    'order' => 10,
                    'type' => 'knowledge',
                    'text' => 'Sis lambalari ne zaman kullanilmalidir?',
                    'competency_code' => 'TRAFFIC_RULES',
                    'options' => [
                        ['value' => 'a', 'text' => 'Sadece gece', 'score' => 0],
                        ['value' => 'b', 'text' => 'Gorusun 100m altina dustugunde', 'score' => 100],
                        ['value' => 'c', 'text' => 'Yagmurda her zaman', 'score' => 20],
                        ['value' => 'd', 'text' => 'Sehir disinda her zaman', 'score' => 0],
                    ],
                ],
                [
                    'order' => 11,
                    'type' => 'scenario',
                    'text' => 'Teslimat adresini bulamiyorsunuz ve musteri telefonu acmiyor. Ne yaparsiniz?',
                    'competency_code' => 'PROBLEM_SOLVING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Depoya donerim', 'score' => 20],
                        ['value' => 'b', 'text' => 'Komsulardan sorar, merkezi bilgilendirir, tekrar ararim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Teslimati iptal ederim', 'score' => 10],
                        ['value' => 'd', 'text' => 'Kapiya birakirim', 'score' => 30],
                    ],
                ],
                [
                    'order' => 12,
                    'type' => 'behavior',
                    'text' => 'Gecikeceginizi anliyorsunuz. Ne yaparsiniz?',
                    'competency_code' => 'TIME_MANAGEMENT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Hiz limitlerini asarak yetismeye calisirim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Onceden merkezi ve musteriyi bilgilendirir, guvenlí surume devam ederim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Vardígimda ozur dilerim', 'score' => 40],
                        ['value' => 'd', 'text' => 'Trafigi bahane ederim', 'score' => 20],
                    ],
                ],
            ],
            'scoring_config' => [
                'passing_score' => 60,
                'level_thresholds' => [
                    'basarisiz' => 40,
                    'gelisime_acik' => 55,
                    'yeterli' => 70,
                    'iyi' => 85,
                    'mukemmel' => 100,
                ],
            ],
            'time_limit_minutes' => 25,
            'is_active' => true,
        ];
    }

    private function getDepoTemplate(): array
    {
        return [
            'name' => 'Depo Personeli Degerlendirmesi',
            'slug' => 'depo-assessment',
            'role_category' => 'depo',
            'description' => 'Depo ve stok yonetimi personeli icin yetkinlik degerlendirmesi',
            'competencies' => [
                ['code' => 'INVENTORY_MGMT', 'name' => 'Stok Yonetimi', 'description' => 'Envanter takibi ve FIFO prensipleri', 'weight' => 1.5],
                ['code' => 'PHYSICAL_ORG', 'name' => 'Fiziksel Organizasyon', 'description' => 'Depo duzeni ve alan kullanimi', 'weight' => 1.2],
                ['code' => 'SAFETY', 'name' => 'Is Guvenligi', 'description' => 'Depo guvenligi ve ekipman kullanimi', 'weight' => 2.0],
                ['code' => 'ACCURACY', 'name' => 'Dogruluk', 'description' => 'Sayim ve kayit dogrulugu', 'weight' => 1.5],
                ['code' => 'SPEED', 'name' => 'Hiz ve Verimlilik', 'description' => 'Siparis hazirlama ve yukleme hizi', 'weight' => 1.0],
                ['code' => 'TEAMWORK', 'name' => 'Takim Calismasi', 'description' => 'Is arkadaslari ile koordinasyon', 'weight' => 0.8],
                ['code' => 'EQUIPMENT', 'name' => 'Ekipman Kullanimi', 'description' => 'Forklift, transpalet kullanimi', 'weight' => 1.0],
                ['code' => 'PROBLEM_SOLVING', 'name' => 'Problem Cozme', 'description' => 'Stok ve teslimat sorunlarini cozme', 'weight' => 1.0],
            ],
            'red_flags' => [
                ['code' => 'SAFETY_VIOLATION', 'name' => 'Guvenlik Ihlali', 'description' => 'Is guvenligi kurallarini gormezden gelme', 'severity' => 'critical'],
                ['code' => 'INVENTORY_FRAUD', 'name' => 'Stok Manipulasyonu', 'description' => 'Kayitlari yanlis tutma egilimi', 'severity' => 'critical'],
                ['code' => 'CARELESS_HANDLING', 'name' => 'Dikkatsiz Tasima', 'description' => 'Urunlere zarar verme', 'severity' => 'high'],
                ['code' => 'NO_TEAM_SPIRIT', 'name' => 'Bireyselcilik', 'description' => 'Takim calismasini reddetme', 'severity' => 'medium'],
            ],
            'questions' => [
                [
                    'order' => 1,
                    'type' => 'knowledge',
                    'text' => 'FIFO prensibi ne anlama gelir?',
                    'competency_code' => 'INVENTORY_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Ilk giren ilk cikar', 'score' => 100],
                        ['value' => 'b', 'text' => 'Son giren ilk cikar', 'score' => 0],
                        ['value' => 'c', 'text' => 'En buyuk ilk cikar', 'score' => 0],
                        ['value' => 'd', 'text' => 'Rastgele cikarilir', 'score' => 0],
                    ],
                ],
                [
                    'order' => 2,
                    'type' => 'scenario',
                    'text' => 'Forklift kullanirken yukun gorusu kapattigini fark ettiniz. Ne yaparsiniz?',
                    'competency_code' => 'SAFETY',
                    'options' => [
                        ['value' => 'a', 'text' => 'Yavas gider, dikkatli olurum', 'score' => 30],
                        ['value' => 'b', 'text' => 'Geri geri giderim veya yardim alirim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Baska birini yone bakmasi icin cagiririm', 'score' => 60],
                        ['value' => 'd', 'text' => 'Korna calarak devam ederim', 'score' => 20],
                    ],
                ],
                [
                    'order' => 3,
                    'type' => 'scenario',
                    'text' => 'Sayimda sistemde 100 adet gorunuyor ama siz 95 adet saydınız. Ne yaparsiniz?',
                    'competency_code' => 'ACCURACY',
                    'options' => [
                        ['value' => 'a', 'text' => '100 olarak girerim, kucuk fark onemli degil', 'score' => 0],
                        ['value' => 'b', 'text' => 'Tekrar sayar, farki raporlarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Amirime sorarim ne yapayim diye', 'score' => 60],
                        ['value' => 'd', 'text' => '95 girerim aciklama yapmam', 'score' => 20],
                    ],
                ],
                [
                    'order' => 4,
                    'type' => 'behavior',
                    'text' => 'Is arkadasiniz agir bir yukü tek basina kaldirmaya calisiyor. Ne yaparsiniz?',
                    'competency_code' => 'TEAMWORK',
                    'options' => [
                        ['value' => 'a', 'text' => 'Kendi isime bakarim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Hemen yardima giderim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Dikkatli ol derim', 'score' => 20],
                        ['value' => 'd', 'text' => 'Transpalet kullanmasini soylarim', 'score' => 60],
                    ],
                ],
                [
                    'order' => 5,
                    'type' => 'knowledge',
                    'text' => 'Depo raflarinda agir urunler nereye yerlestirilmelidir?',
                    'competency_code' => 'PHYSICAL_ORG',
                    'options' => [
                        ['value' => 'a', 'text' => 'En ust raflara', 'score' => 0],
                        ['value' => 'b', 'text' => 'Alt raflara, zemine yakin', 'score' => 100],
                        ['value' => 'c', 'text' => 'Orta raflara', 'score' => 30],
                        ['value' => 'd', 'text' => 'Fark etmez', 'score' => 0],
                    ],
                ],
                [
                    'order' => 6,
                    'type' => 'scenario',
                    'text' => 'Acil bir siparis geldi ama size atanan baska bir görev var. Ne yaparsiniz?',
                    'competency_code' => 'SPEED',
                    'options' => [
                        ['value' => 'a', 'text' => 'Once kendi isimi bitiririm', 'score' => 20],
                        ['value' => 'b', 'text' => 'Amirime duruşu bildirir, acil isçi once yaparim', 'score' => 100],
                        ['value' => 'c', 'text' => 'İkisini birden yapmaya calisirim', 'score' => 40],
                        ['value' => 'd', 'text' => 'Baskasina havale ederim', 'score' => 30],
                    ],
                ],
                [
                    'order' => 7,
                    'type' => 'behavior',
                    'text' => 'Hasar gormus bir urun fark ettiniz ama nasil hasarlandigini bilmiyorsunuz. Ne yaparsiniz?',
                    'competency_code' => 'PROBLEM_SOLVING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Kenara koyar, raporlarim', 'score' => 100],
                        ['value' => 'b', 'text' => 'Ses cikarmam', 'score' => 0],
                        ['value' => 'c', 'text' => 'Diger urunlerin arasina koyarim', 'score' => 0],
                        ['value' => 'd', 'text' => 'Hasar gorunmuyor gibi yerlestiririm', 'score' => 0],
                    ],
                ],
                [
                    'order' => 8,
                    'type' => 'knowledge',
                    'text' => 'Koruyucu ekipman (baret, celik burunlu ayakkabi) ne zaman kullanilmalidir?',
                    'competency_code' => 'SAFETY',
                    'options' => [
                        ['value' => 'a', 'text' => 'Sadece denetim varken', 'score' => 0],
                        ['value' => 'b', 'text' => 'Her zaman calisma alaninda', 'score' => 100],
                        ['value' => 'c', 'text' => 'Sadece agir yuklerle calisirken', 'score' => 30],
                        ['value' => 'd', 'text' => 'Istege bagli', 'score' => 0],
                    ],
                ],
                [
                    'order' => 9,
                    'type' => 'scenario',
                    'text' => 'Bir urunun son kullanim tarihi yaklasıyor ama raf arkasinda kalmis. Ne yaparsiniz?',
                    'competency_code' => 'INVENTORY_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'One alirim ki ilk o ciksın', 'score' => 100],
                        ['value' => 'b', 'text' => 'Oldugu yerde birakirim', 'score' => 0],
                        ['value' => 'c', 'text' => 'Atim daha kolay', 'score' => 10],
                        ['value' => 'd', 'text' => 'Amire bildiririm sadece', 'score' => 50],
                    ],
                ],
                [
                    'order' => 10,
                    'type' => 'behavior',
                    'text' => 'Transpalet arizalandi ama baska transpalet yok. Yukler bekliyor. Ne yaparsiniz?',
                    'competency_code' => 'EQUIPMENT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Elle tasirim hepsini', 'score' => 20],
                        ['value' => 'b', 'text' => 'Arizayi bildiri, gecici cozum icin amirimle konusurum', 'score' => 100],
                        ['value' => 'c', 'text' => 'Tamír olana kadar beklerim', 'score' => 40],
                        ['value' => 'd', 'text' => 'Tamir etmeye calisirim kendim', 'score' => 30],
                    ],
                ],
            ],
            'scoring_config' => [
                'passing_score' => 60,
                'level_thresholds' => [
                    'basarisiz' => 40,
                    'gelisime_acik' => 55,
                    'yeterli' => 70,
                    'iyi' => 85,
                    'mukemmel' => 100,
                ],
            ],
            'time_limit_minutes' => 20,
            'is_active' => true,
        ];
    }

    private function getTezgahtarTemplate(): array
    {
        return [
            'name' => 'Tezgahtar/Kasiyer Degerlendirmesi',
            'slug' => 'tezgahtar-kasiyer-assessment',
            'role_category' => 'tezgahtar',
            'description' => 'Magaza satis ve kasa personeli icin yetkinlik degerlendirmesi',
            'competencies' => [
                ['code' => 'CUSTOMER_SERVICE', 'name' => 'Musteri Hizmeti', 'description' => 'Guler yuzlu ve yardimci hizmet', 'weight' => 2.0],
                ['code' => 'PRODUCT_KNOWLEDGE', 'name' => 'Urun Bilgisi', 'description' => 'Satis yapilan urunler hakkinda bilgi', 'weight' => 1.2],
                ['code' => 'CASH_HANDLING', 'name' => 'Kasa Islemleri', 'description' => 'Para ve odeme islemleri', 'weight' => 1.5],
                ['code' => 'SALES_SKILLS', 'name' => 'Satis Becerileri', 'description' => 'Carpraz satis ve upselling', 'weight' => 1.0],
                ['code' => 'PROBLEM_RESOLUTION', 'name' => 'Sorun Cozme', 'description' => 'Musteri sikayetlerini yonetme', 'weight' => 1.2],
                ['code' => 'SPEED_ACCURACY', 'name' => 'Hiz ve Dogruluk', 'description' => 'Hizli ve hatasiz islem', 'weight' => 1.0],
                ['code' => 'HYGIENE', 'name' => 'Hijyen', 'description' => 'Kisisel ve alan temizligi', 'weight' => 1.0],
                ['code' => 'TEAMWORK', 'name' => 'Takim Calismasi', 'description' => 'Ekiple uyum', 'weight' => 0.8],
            ],
            'red_flags' => [
                ['code' => 'CASH_DISCREPANCY', 'name' => 'Kasa Acigi', 'description' => 'Para islemlerinde dikkatsizlik/suistimal', 'severity' => 'critical'],
                ['code' => 'RUDE_BEHAVIOR', 'name' => 'Kaba Davranis', 'description' => 'Musteriye kaba veya ilgisiz davranis', 'severity' => 'high'],
                ['code' => 'HYGIENE_NEGLECT', 'name' => 'Hijyen Ihmali', 'description' => 'Temizlik kurallarini gormezden gelme', 'severity' => 'high'],
                ['code' => 'NO_INITIATIVE', 'name' => 'Inisiyatif Eksikligi', 'description' => 'Pasif, talimatsiz hareket edememe', 'severity' => 'medium'],
            ],
            'questions' => [
                [
                    'order' => 1,
                    'type' => 'scenario',
                    'text' => 'Musteri urun hakkinda soru soruyor ama siz cevabi bilmiyorsunuz. Ne yaparsiniz?',
                    'competency_code' => 'CUSTOMER_SERVICE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Bilmiyorum derim', 'score' => 20],
                        ['value' => 'b', 'text' => 'Uydururum bir cevap', 'score' => 0],
                        ['value' => 'c', 'text' => 'Ogrenir donerim veya bilen birini bulurum', 'score' => 100],
                        ['value' => 'd', 'text' => 'Internetten bakmalarini onerim', 'score' => 10],
                    ],
                ],
                [
                    'order' => 2,
                    'type' => 'scenario',
                    'text' => 'Kasada para ustu verirken yanlis verdiginizi fark ettiniz, musteri ayriliyor. Ne yaparsiniz?',
                    'competency_code' => 'CASH_HANDLING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Seslenip duzeltirím', 'score' => 100],
                        ['value' => 'b', 'text' => 'Fark kucukse birak gidsin', 'score' => 0],
                        ['value' => 'c', 'text' => 'Kendi cebimden tamamlarim', 'score' => 30],
                        ['value' => 'd', 'text' => 'Not alir vardiya sonunda soylerim', 'score' => 50],
                    ],
                ],
                [
                    'order' => 3,
                    'type' => 'behavior',
                    'text' => 'Musteri bir urun aliyor, yanina yakisan baska bir urun de var. Ne yaparsiniz?',
                    'competency_code' => 'SALES_SKILLS',
                    'options' => [
                        ['value' => 'a', 'text' => 'Sadece istedigi urunu verir im', 'score' => 30],
                        ['value' => 'b', 'text' => 'Nazikce ilgili urunleri onerir im', 'score' => 100],
                        ['value' => 'c', 'text' => 'Baskilı satis yaparim', 'score' => 10],
                        ['value' => 'd', 'text' => 'Musteriye bagli, sormam', 'score' => 40],
                    ],
                ],
                [
                    'order' => 4,
                    'type' => 'scenario',
                    'text' => 'Musteri aldigi urunun bozuk cikmasi nedeniyle kizgin sekilde geliyor. Ne yaparsiniz?',
                    'competency_code' => 'PROBLEM_RESOLUTION',
                    'options' => [
                        ['value' => 'a', 'text' => 'Ozur diler, hemen cozum onerileri sunarim', 'score' => 100],
                        ['value' => 'b', 'text' => 'Bize geldiginde iyiydi derim', 'score' => 0],
                        ['value' => 'c', 'text' => 'Muduru cagiririm', 'score' => 50],
                        ['value' => 'd', 'text' => 'Kurallari anlatirim', 'score' => 30],
                    ],
                ],
                [
                    'order' => 5,
                    'type' => 'knowledge',
                    'text' => 'Gida satis noktasinda el yikama ne siklikla yapilmalidir?',
                    'competency_code' => 'HYGIENE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Sadece ise baslarken', 'score' => 20],
                        ['value' => 'b', 'text' => 'Para dokunduktan, wc sonrasi, her urun degisiminde', 'score' => 100],
                        ['value' => 'c', 'text' => 'Sadece wc sonrasi', 'score' => 40],
                        ['value' => 'd', 'text' => 'Gunde 2-3 kez yeterli', 'score' => 30],
                    ],
                ],
                [
                    'order' => 6,
                    'type' => 'behavior',
                    'text' => 'Kasada uzun kuyruk var, tek basinizsiniz. Ne yaparsiniz?',
                    'competency_code' => 'SPEED_ACCURACY',
                    'options' => [
                        ['value' => 'a', 'text' => 'Normal hizimda devam ederim', 'score' => 40],
                        ['value' => 'b', 'text' => 'Hizli ama dikkatli çalışır, destek isterim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Hata pahasina cok hizlanrim', 'score' => 20],
                        ['value' => 'd', 'text' => 'Musterilerin beklemesi normal', 'score' => 0],
                    ],
                ],
                [
                    'order' => 7,
                    'type' => 'scenario',
                    'text' => 'Is arkadasiniz mola istiyor ama siz de cok yorgunsunuz ve kalabalik var. Ne yaparsiniz?',
                    'competency_code' => 'TEAMWORK',
                    'options' => [
                        ['value' => 'a', 'text' => 'Ben de moladayim derim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Kalabalik azalinca sirayla mola veririz', 'score' => 100],
                        ['value' => 'c', 'text' => 'O gitsin ben beklerim', 'score' => 60],
                        ['value' => 'd', 'text' => 'Mudure sikayet ederim', 'score' => 10],
                    ],
                ],
                [
                    'order' => 8,
                    'type' => 'knowledge',
                    'text' => 'Kredi karti reddedildiginde musteriye ne soylenir?',
                    'competency_code' => 'CASH_HANDLING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Kartinizda para yok', 'score' => 0],
                        ['value' => 'b', 'text' => 'Islem onaylanmadi, baska odeme yontemi deneyelim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Kartiniz calismiyor', 'score' => 30],
                        ['value' => 'd', 'text' => 'Bankanizt arayin', 'score' => 40],
                    ],
                ],
                [
                    'order' => 9,
                    'type' => 'behavior',
                    'text' => 'Tezgahta bos zaman var, musteri yok. Ne yaparsiniz?',
                    'competency_code' => 'PRODUCT_KNOWLEDGE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Telefona bakarim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Rafları düzenler, urunleri inceler, temizlik yaparim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Arkadaslarla sohbet ederim', 'score' => 10],
                        ['value' => 'd', 'text' => 'Beklerim', 'score' => 20],
                    ],
                ],
                [
                    'order' => 10,
                    'type' => 'scenario',
                    'text' => 'Musteri indirim istiyor ama yetkiniŸ yok. Ne yaparsiniz?',
                    'competency_code' => 'CUSTOMER_SERVICE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Yapamam, kurallar boyle', 'score' => 40],
                        ['value' => 'b', 'text' => 'Yetkiliyi cagirir, alternatif kampanyalari anlatírim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Kucuk bir indirim yaparim', 'score' => 0],
                        ['value' => 'd', 'text' => 'Baska magazayi deneyin', 'score' => 0],
                    ],
                ],
            ],
            'scoring_config' => [
                'passing_score' => 60,
                'level_thresholds' => [
                    'basarisiz' => 40,
                    'gelisime_acik' => 55,
                    'yeterli' => 70,
                    'iyi' => 85,
                    'mukemmel' => 100,
                ],
            ],
            'time_limit_minutes' => 20,
            'is_active' => true,
        ];
    }

    private function getUretimTemplate(): array
    {
        return [
            'name' => 'Uretim Personeli Degerlendirmesi',
            'slug' => 'uretim-personeli-assessment',
            'role_category' => 'uretim',
            'description' => 'Pastahane ve uretim hatti personeli icin yetkinlik degerlendirmesi',
            'competencies' => [
                ['code' => 'FOOD_SAFETY', 'name' => 'Gida Guvenligi', 'description' => 'HACCP ve hijyen kurallari', 'weight' => 2.0],
                ['code' => 'RECIPE_FOLLOW', 'name' => 'Recete Takibi', 'description' => 'Tariflere uygun uretim', 'weight' => 1.5],
                ['code' => 'QUALITY_CHECK', 'name' => 'Kalite Kontrol', 'description' => 'Urun kalitesini denetleme', 'weight' => 1.2],
                ['code' => 'EQUIPMENT_USE', 'name' => 'Ekipman Kullanimi', 'description' => 'Uretim makinelerini kullanma', 'weight' => 1.0],
                ['code' => 'TIME_MGMT', 'name' => 'Zaman Yonetimi', 'description' => 'Uretim surelerine uyum', 'weight' => 1.0],
                ['code' => 'WASTE_MGMT', 'name' => 'Fire Yonetimi', 'description' => 'Israfi minimize etme', 'weight' => 0.8],
                ['code' => 'TEAMWORK', 'name' => 'Takim Calismasi', 'description' => 'Uretim hattinda koordinasyon', 'weight' => 0.8],
                ['code' => 'PROBLEM_SOLVING', 'name' => 'Problem Cozme', 'description' => 'Uretim sorunlarini cozme', 'weight' => 1.0],
            ],
            'red_flags' => [
                ['code' => 'HYGIENE_VIOLATION', 'name' => 'Hijyen Ihlali', 'description' => 'Gida guvenligi kurallarini ihlal', 'severity' => 'critical'],
                ['code' => 'RECIPE_DEVIATION', 'name' => 'Recete Sapma', 'description' => 'Izinsiz recete degisikligi', 'severity' => 'high'],
                ['code' => 'QUALITY_IGNORE', 'name' => 'Kalite Gormezlik', 'description' => 'Dusuk kaliteyi gormezden gelme', 'severity' => 'high'],
                ['code' => 'UNSAFE_EQUIPMENT', 'name' => 'Ekipman Ihmali', 'description' => 'Ekipmani guvenli kullanmama', 'severity' => 'high'],
            ],
            'questions' => [
                [
                    'order' => 1,
                    'type' => 'knowledge',
                    'text' => 'HACCP sisteminde kritik kontrol noktasi (CCP) ne demektir?',
                    'competency_code' => 'FOOD_SAFETY',
                    'options' => [
                        ['value' => 'a', 'text' => 'Tehlikenin onlenebilecegi veya kabul edilebilir seviyeye indirilebilecegi asama', 'score' => 100],
                        ['value' => 'b', 'text' => 'Uretimin yapildigi yer', 'score' => 0],
                        ['value' => 'c', 'text' => 'Kalite kontrol noktasi', 'score' => 30],
                        ['value' => 'd', 'text' => 'Hammadde girisi', 'score' => 20],
                    ],
                ],
                [
                    'order' => 2,
                    'type' => 'scenario',
                    'text' => 'Recetede 500gr un yazıyor ama elde 480gr var. Ne yaparsiniz?',
                    'competency_code' => 'RECIPE_FOLLOW',
                    'options' => [
                        ['value' => 'a', 'text' => '480 ile devam ederim, fark az', 'score' => 0],
                        ['value' => 'b', 'text' => 'Eksigi tamamlar veya amiri bilgilendiririm', 'score' => 100],
                        ['value' => 'c', 'text' => 'Baska malzeme eklerim', 'score' => 0],
                        ['value' => 'd', 'text' => 'Porsiyon sayisini azaltirim', 'score' => 50],
                    ],
                ],
                [
                    'order' => 3,
                    'type' => 'behavior',
                    'text' => 'Hazirlanan urunde renk veya koku normaldeki gibi degil. Ne yaparsiniz?',
                    'competency_code' => 'QUALITY_CHECK',
                    'options' => [
                        ['value' => 'a', 'text' => 'Sevkiyata gonderirim, musteri bakar', 'score' => 0],
                        ['value' => 'b', 'text' => 'Kalite kontrol/amire bildiririm', 'score' => 100],
                        ['value' => 'c', 'text' => 'Ustune sos/kaplama yaparim', 'score' => 0],
                        ['value' => 'd', 'text' => 'Personel yemegine ayiririm', 'score' => 20],
                    ],
                ],
                [
                    'order' => 4,
                    'type' => 'knowledge',
                    'text' => 'Soguk zincir gerektiren urunler hangi sicaklikta saklanmalidir?',
                    'competency_code' => 'FOOD_SAFETY',
                    'options' => [
                        ['value' => 'a', 'text' => '0-4°C arasi', 'score' => 100],
                        ['value' => 'b', 'text' => '10-15°C arasi', 'score' => 20],
                        ['value' => 'c', 'text' => 'Oda sicakligi', 'score' => 0],
                        ['value' => 'd', 'text' => '-10°C alti', 'score' => 30],
                    ],
                ],
                [
                    'order' => 5,
                    'type' => 'scenario',
                    'text' => 'Mikser ariza verdi, uretim duracak. Ne yaparsiniz?',
                    'competency_code' => 'PROBLEM_SOLVING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Tamir olana kadar beklerim', 'score' => 20],
                        ['value' => 'b', 'text' => 'Arizayi bildiri, alternatif ekipman/yontem ararim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Kendim tamir etmeye calisirim', 'score' => 10],
                        ['value' => 'd', 'text' => 'O urunu atlayip digerine gecerim', 'score' => 40],
                    ],
                ],
                [
                    'order' => 6,
                    'type' => 'behavior',
                    'text' => 'Hazirlanan hamurdan fazla var, ne yaparsiniz?',
                    'competency_code' => 'WASTE_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Cope atarim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Dondurarak saklarim veya baska urun icin kullanirim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Eve gotururum', 'score' => 0],
                        ['value' => 'd', 'text' => 'Miktari kaydetmem', 'score' => 10],
                    ],
                ],
                [
                    'order' => 7,
                    'type' => 'knowledge',
                    'text' => 'El yikama suresi en az ne kadar olmalidir?',
                    'competency_code' => 'FOOD_SAFETY',
                    'options' => [
                        ['value' => 'a', 'text' => '5 saniye', 'score' => 0],
                        ['value' => 'b', 'text' => '20-30 saniye', 'score' => 100],
                        ['value' => 'c', 'text' => '1 dakika', 'score' => 50],
                        ['value' => 'd', 'text' => 'Sure onemli degil', 'score' => 0],
                    ],
                ],
                [
                    'order' => 8,
                    'type' => 'scenario',
                    'text' => 'Firin sicakligi normalden dusuk ama uretim baskisi var. Ne yaparsiniz?',
                    'competency_code' => 'EQUIPMENT_USE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Dusuk sicaklikta devam ederim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Firinin isitmesini bekler, amiri bilgilendiririm', 'score' => 100],
                        ['value' => 'c', 'text' => 'Sure uzatarak pisiririm', 'score' => 40],
                        ['value' => 'd', 'text' => 'Farkí musteri anlamaz', 'score' => 0],
                    ],
                ],
                [
                    'order' => 9,
                    'type' => 'behavior',
                    'text' => 'Uretim hattinda arkadasiniz geride kaldi, hattaki akisi yavaslatıyor. Ne yaparsiniz?',
                    'competency_code' => 'TEAMWORK',
                    'options' => [
                        ['value' => 'a', 'text' => 'Kendi isime bakarim', 'score' => 10],
                        ['value' => 'b', 'text' => 'Yardim eder, birlikte ritmini yakalariz', 'score' => 100],
                        ['value' => 'c', 'text' => 'Amire sikayet ederim', 'score' => 20],
                        ['value' => 'd', 'text' => 'Daha hizli olmasini soylarim', 'score' => 30],
                    ],
                ],
                [
                    'order' => 10,
                    'type' => 'scenario',
                    'text' => 'Bone/eldiven takmadan alana giren is arkadasi gordunuz. Ne yaparsiniz?',
                    'competency_code' => 'FOOD_SAFETY',
                    'options' => [
                        ['value' => 'a', 'text' => 'Beni ilgilendirmez', 'score' => 0],
                        ['value' => 'b', 'text' => 'Nazikce uyarır, kurallari hatirlatirim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Mudure bildiririm', 'score' => 50],
                        ['value' => 'd', 'text' => 'Herkes yapiyor zaten', 'score' => 0],
                    ],
                ],
            ],
            'scoring_config' => [
                'passing_score' => 60,
                'level_thresholds' => [
                    'basarisiz' => 40,
                    'gelisime_acik' => 55,
                    'yeterli' => 70,
                    'iyi' => 85,
                    'mukemmel' => 100,
                ],
            ],
            'time_limit_minutes' => 20,
            'is_active' => true,
        ];
    }

    private function getTemizlikTemplate(): array
    {
        return [
            'name' => 'Temizlik/Yardimci Personel Degerlendirmesi',
            'slug' => 'temizlik-yardimci-assessment',
            'role_category' => 'temizlik',
            'description' => 'Temizlik, bulasik ve yardimci personel icin yetkinlik degerlendirmesi',
            'competencies' => [
                ['code' => 'CLEANING_TECH', 'name' => 'Temizlik Teknikleri', 'description' => 'Dogru temizlik yontemleri', 'weight' => 1.5],
                ['code' => 'HYGIENE_AWARE', 'name' => 'Hijyen Farkindaligi', 'description' => 'Hijyen kurallarini bilme ve uygulama', 'weight' => 2.0],
                ['code' => 'CHEMICAL_SAFETY', 'name' => 'Kimyasal Guvenlik', 'description' => 'Temizlik maddelerini guvenli kullanma', 'weight' => 1.5],
                ['code' => 'TIME_EFFICIENCY', 'name' => 'Zaman Verimliligi', 'description' => 'Gorevleri zamaninda tamamlama', 'weight' => 1.0],
                ['code' => 'ATTENTION_DETAIL', 'name' => 'Detaya Dikkat', 'description' => 'Gozden kacanlari fark etme', 'weight' => 1.0],
                ['code' => 'PHYSICAL_ENDUR', 'name' => 'Fiziksel Dayaniklilik', 'description' => 'Agir isle bas etme', 'weight' => 0.8],
                ['code' => 'TEAMWORK', 'name' => 'Takim Calismasi', 'description' => 'Ekiple uyum', 'weight' => 0.8],
                ['code' => 'INITIATIVE', 'name' => 'Inisiyatif', 'description' => 'Talimat beklemeden is gorme', 'weight' => 0.8],
            ],
            'red_flags' => [
                ['code' => 'CROSS_CONTAM', 'name' => 'Capraz Bulasma', 'description' => 'Hijyen kurallarini ihlal ederek capraz bulasma riski', 'severity' => 'critical'],
                ['code' => 'CHEMICAL_MISUSE', 'name' => 'Kimyasal Hata', 'description' => 'Temizlik maddelerini yanlis kullanma', 'severity' => 'high'],
                ['code' => 'LAZY_ATTITUDE', 'name' => 'Is Kacirma', 'description' => 'Gorevlerden kacma egilimi', 'severity' => 'medium'],
                ['code' => 'NO_DETAIL', 'name' => 'Detay Korlugu', 'description' => 'Oz-ensiz temizlik yapma', 'severity' => 'medium'],
            ],
            'questions' => [
                [
                    'order' => 1,
                    'type' => 'knowledge',
                    'text' => 'Temizlik bezleri neden renk kodlarina gore ayrilmalidir?',
                    'competency_code' => 'HYGIENE_AWARE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Estetik gorunum icin', 'score' => 0],
                        ['value' => 'b', 'text' => 'Capraz bulasmayi onlemek icin', 'score' => 100],
                        ['value' => 'c', 'text' => 'Kolay bulmak icin', 'score' => 20],
                        ['value' => 'd', 'text' => 'Zorunlu degil', 'score' => 0],
                    ],
                ],
                [
                    'order' => 2,
                    'type' => 'scenario',
                    'text' => 'Yogun is saatinde mutfakta dokulme oldu. Ne yaparsiniz?',
                    'competency_code' => 'INITIATIVE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Siramiz gelince temizlerim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Hemen guvenligi saglar, temizlerim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Baskasina haber veririm', 'score' => 40],
                        ['value' => 'd', 'text' => 'Benim gorevim degil', 'score' => 0],
                    ],
                ],
                [
                    'order' => 3,
                    'type' => 'knowledge',
                    'text' => 'Camas suyu (klor) baska temizlik maddesiyle karistirilirsa ne olur?',
                    'competency_code' => 'CHEMICAL_SAFETY',
                    'options' => [
                        ['value' => 'a', 'text' => 'Daha etkili olur', 'score' => 0],
                        ['value' => 'b', 'text' => 'Zehirli gaz cikabilir, tehlikelidir', 'score' => 100],
                        ['value' => 'c', 'text' => 'Rengi degisir sadece', 'score' => 0],
                        ['value' => 'd', 'text' => 'Bir sey olmaz', 'score' => 0],
                    ],
                ],
                [
                    'order' => 4,
                    'type' => 'behavior',
                    'text' => 'Temizlemeniz gereken alan cok kirli ve zor. Ne yaparsiniz?',
                    'competency_code' => 'PHYSICAL_ENDUR',
                    'options' => [
                        ['value' => 'a', 'text' => 'Ustten gecistiririm', 'score' => 0],
                        ['value' => 'b', 'text' => 'Sabırla detayli temizlerim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Baskasina devrederim', 'score' => 10],
                        ['value' => 'd', 'text' => 'Gorunmeyen yerleri atlarim', 'score' => 0],
                    ],
                ],
                [
                    'order' => 5,
                    'type' => 'scenario',
                    'text' => 'WC temizliginden sonra ayni bezle mutfaga gecmek istiyorsunuz. Ne yaparsiniz?',
                    'competency_code' => 'HYGIENE_AWARE',
                    'options' => [
                        ['value' => 'a', 'text' => 'Bezi yikar kullanirim', 'score' => 10],
                        ['value' => 'b', 'text' => 'Asla! Farkli bez ve eldiven kullanirim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Dezenfektan sikar devam ederim', 'score' => 20],
                        ['value' => 'd', 'text' => 'Bez bez sonucta', 'score' => 0],
                    ],
                ],
                [
                    'order' => 6,
                    'type' => 'knowledge',
                    'text' => 'Temizlik sirasi nasil olmalidir?',
                    'competency_code' => 'CLEANING_TECH',
                    'options' => [
                        ['value' => 'a', 'text' => 'Once zemin sonra yuzeyler', 'score' => 0],
                        ['value' => 'b', 'text' => 'Yukaridan asagiya, temizden kirliye', 'score' => 100],
                        ['value' => 'c', 'text' => 'Fark etmez', 'score' => 0],
                        ['value' => 'd', 'text' => 'Once en kirli yer', 'score' => 20],
                    ],
                ],
                [
                    'order' => 7,
                    'type' => 'behavior',
                    'text' => 'Temizlik programinda olmayan ama kirli bir alan gorduniiz. Ne yaparsiniz?',
                    'competency_code' => 'ATTENTION_DETAIL',
                    'options' => [
                        ['value' => 'a', 'text' => 'Programda yok, dokunmam', 'score' => 10],
                        ['value' => 'b', 'text' => 'Firsatim varsa hemen temizlerim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Bir dahaki sefere', 'score' => 20],
                        ['value' => 'd', 'text' => 'Amirime bildiririm', 'score' => 60],
                    ],
                ],
                [
                    'order' => 8,
                    'type' => 'scenario',
                    'text' => 'Bulasikhane cok dolu ama mola saatiniz geldi. Ne yaparsiniz?',
                    'competency_code' => 'TEAMWORK',
                    'options' => [
                        ['value' => 'a', 'text' => 'Hakkim, molaya cikarim', 'score' => 20],
                        ['value' => 'b', 'text' => 'Kritik kisimlari bitir, ekiple koordine olur molay i ertelerim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Diger arkadas gelsin', 'score' => 40],
                        ['value' => 'd', 'text' => 'Hızlı yapar cikarim', 'score' => 50],
                    ],
                ],
                [
                    'order' => 9,
                    'type' => 'knowledge',
                    'text' => 'Temizlik maddesi kutusu/sisesi uzerinde "asla karistirmayin" yaziyorsa ne yapmalsiniz?',
                    'competency_code' => 'CHEMICAL_SAFETY',
                    'options' => [
                        ['value' => 'a', 'text' => 'Az miktar olursa olur', 'score' => 0],
                        ['value' => 'b', 'text' => 'Kesinlikle karistirmam, talimata uyarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Su ile karistiririm', 'score' => 30],
                        ['value' => 'd', 'text' => 'Denemek lazim', 'score' => 0],
                    ],
                ],
                [
                    'order' => 10,
                    'type' => 'behavior',
                    'text' => 'Gun sonunda cok yorgunsunuz ama son kontroller yapilmadi. Ne yaparsiniz?',
                    'competency_code' => 'TIME_EFFICIENCY',
                    'options' => [
                        ['value' => 'a', 'text' => 'Yarin yaparim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Kontrolleri yapar, eksik varsa tamamlarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Ustten gecerim', 'score' => 20],
                        ['value' => 'd', 'text' => 'Arkadasa birakir giderim', 'score' => 10],
                    ],
                ],
            ],
            'scoring_config' => [
                'passing_score' => 55,
                'level_thresholds' => [
                    'basarisiz' => 35,
                    'gelisime_acik' => 50,
                    'yeterli' => 65,
                    'iyi' => 80,
                    'mukemmel' => 100,
                ],
            ],
            'time_limit_minutes' => 18,
            'is_active' => true,
        ];
    }

    private function getMagazaMuduruTemplate(): array
    {
        return [
            'name' => 'Magaza Muduru Degerlendirmesi',
            'slug' => 'magaza-muduru-assessment',
            'role_category' => 'magaza_muduru',
            'description' => 'Magaza yoneticileri icin liderlik ve operasyon yetkinlik degerlendirmesi',
            'competencies' => [
                ['code' => 'LEADERSHIP', 'name' => 'Liderlik', 'description' => 'Ekibi yonlendirme ve motive etme', 'weight' => 2.0],
                ['code' => 'BUSINESS_ACUMEN', 'name' => 'Is Zekasi', 'description' => 'Finansal ve operasyonel anlayis', 'weight' => 1.5],
                ['code' => 'PEOPLE_MGMT', 'name' => 'Insan Yonetimi', 'description' => 'Personel yonetimi ve gelisimi', 'weight' => 1.5],
                ['code' => 'CUSTOMER_EXP', 'name' => 'Musteri Deneyimi', 'description' => 'Musteri memnuniyeti yonetimi', 'weight' => 1.2],
                ['code' => 'OPERATIONS', 'name' => 'Operasyon Yonetimi', 'description' => 'Gunluk islemleri yonetme', 'weight' => 1.0],
                ['code' => 'PROBLEM_SOLVING', 'name' => 'Problem Cozme', 'description' => 'Karmasik sorunlara cozum uretme', 'weight' => 1.2],
                ['code' => 'COMMUNICATION', 'name' => 'Iletisim', 'description' => 'Etkili iletisim kurma', 'weight' => 1.0],
                ['code' => 'DECISION_MAKING', 'name' => 'Karar Verme', 'description' => 'Hizli ve dogru kararlar alma', 'weight' => 1.2],
            ],
            'red_flags' => [
                ['code' => 'POOR_LEADERSHIP', 'name' => 'Zayif Liderlik', 'description' => 'Ekibi yonetememe, otorite kuramama', 'severity' => 'critical'],
                ['code' => 'FINANCIAL_NEGLECT', 'name' => 'Mali Dikkatsizlik', 'description' => 'Butce ve mali konularda ihmalkarlık', 'severity' => 'high'],
                ['code' => 'CUSTOMER_IGNORE', 'name' => 'Musteri Ihmali', 'description' => 'Musteri sikayetlerini gormezden gelme', 'severity' => 'high'],
                ['code' => 'MICROMANAGEMENT', 'name' => 'Asiri Kontrol', 'description' => 'Her seye mudahale, yetki devredememe', 'severity' => 'medium'],
                ['code' => 'CONFLICT_AVOID', 'name' => 'Catismadan Kacis', 'description' => 'Zor konulari ele almama', 'severity' => 'medium'],
            ],
            'questions' => [
                [
                    'order' => 1,
                    'type' => 'scenario',
                    'text' => 'Iki calisan arasinda surekli gerginlik var ve ekibi etkiliyor. Ne yaparsiniz?',
                    'competency_code' => 'PEOPLE_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Kendi hallerine birakirim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Ayri ayri gorusu, birlikte toplantí yapar, cozum bulurum', 'score' => 100],
                        ['value' => 'c', 'text' => 'Birini baska subeve gondermeye calisirim', 'score' => 30],
                        ['value' => 'd', 'text' => 'Ikisine de uyari veririm', 'score' => 40],
                    ],
                ],
                [
                    'order' => 2,
                    'type' => 'scenario',
                    'text' => 'Bu ay satis hedefinin %70\'inde kaldiniz. Ay sonuna 1 hafta var. Ne yaparsiniz?',
                    'competency_code' => 'BUSINESS_ACUMEN',
                    'options' => [
                        ['value' => 'a', 'text' => 'Olmadi der bekleria', 'score' => 0],
                        ['value' => 'b', 'text' => 'Analiz yapar, aksiyon plani cikarir, ekibi motive ederim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Ekibe baski yaparim', 'score' => 20],
                        ['value' => 'd', 'text' => 'Merkezden indirim/kampanya isterim', 'score' => 50],
                    ],
                ],
                [
                    'order' => 3,
                    'type' => 'behavior',
                    'text' => 'Calisan size ozel bir konuda danismak istiyor ama cok mesgulseniz. Ne yaparsiniz?',
                    'competency_code' => 'LEADERSHIP',
                    'options' => [
                        ['value' => 'a', 'text' => 'Simdi olmaz der gecerim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Kisa dinler, gerekirse ozel zaman ayiririm', 'score' => 100],
                        ['value' => 'c', 'text' => 'IK\'ya yonlendiririm', 'score' => 40],
                        ['value' => 'd', 'text' => 'Mail atmasini isterim', 'score' => 20],
                    ],
                ],
                [
                    'order' => 4,
                    'type' => 'scenario',
                    'text' => 'Musteri sosyal medyada magazanizi elestirir bir paylasim yapti. Ne yaparsiniz?',
                    'competency_code' => 'CUSTOMER_EXP',
                    'options' => [
                        ['value' => 'a', 'text' => 'Gormezden gelirim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Hizla iletisime gecer, ozur ve cozum sunartm', 'score' => 100],
                        ['value' => 'c', 'text' => 'Savunma yaniti yazarim', 'score' => 10],
                        ['value' => 'd', 'text' => 'Merkeze bildiririm sadece', 'score' => 40],
                    ],
                ],
                [
                    'order' => 5,
                    'type' => 'knowledge',
                    'text' => 'Personel maliyet optimizasyonu icin oncelikle neye bakmalisiniz?',
                    'competency_code' => 'OPERATIONS',
                    'options' => [
                        ['value' => 'a', 'text' => 'En yuksek maasli kisiye', 'score' => 10],
                        ['value' => 'b', 'text' => 'Satis/trafik verilerine gore mesai planlamasi', 'score' => 100],
                        ['value' => 'c', 'text' => 'Herkesin mesaisini kesmeye', 'score' => 20],
                        ['value' => 'd', 'text' => 'Part-time calisana gecise', 'score' => 40],
                    ],
                ],
                [
                    'order' => 6,
                    'type' => 'scenario',
                    'text' => 'Yeni gelen bir calisaniniz cok hatali is yapiyor. 2 hafta oldu. Ne yaparsiniz?',
                    'competency_code' => 'PEOPLE_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Deneme suresi bitince gonderiririm', 'score' => 20],
                        ['value' => 'b', 'text' => 'Birebir gorusur, egitim ve destek saglarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Baska birine devretim', 'score' => 10],
                        ['value' => 'd', 'text' => 'Yazili uyari veririm', 'score' => 30],
                    ],
                ],
                [
                    'order' => 7,
                    'type' => 'behavior',
                    'text' => 'Önemli bir karar almaniz gerekiyor ama tum bilgiler elinizde yok. Ne yaparsiniz?',
                    'competency_code' => 'DECISION_MAKING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Bilgi gelene kadar beklerim', 'score' => 30],
                        ['value' => 'b', 'text' => 'Mevcut bilgiyle risk analizi yapar, karar alirim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Baskasina devretim', 'score' => 10],
                        ['value' => 'd', 'text' => 'Sezgilerimle karar veririm', 'score' => 40],
                    ],
                ],
                [
                    'order' => 8,
                    'type' => 'scenario',
                    'text' => 'Merkez yeni bir prosedur gondordi, ekip isyankar. Ne yaparsiniz?',
                    'competency_code' => 'COMMUNICATION',
                    'options' => [
                        ['value' => 'a', 'text' => 'Emir buyuk, yapacaksiniz derim', 'score' => 20],
                        ['value' => 'b', 'text' => 'Nedenini açıklar, faydalarini anlatirim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Merkeze ekibin itirazini ileterim', 'score' => 50],
                        ['value' => 'd', 'text' => 'Sessizce uygulamaya gecerim', 'score' => 30],
                    ],
                ],
                [
                    'order' => 9,
                    'type' => 'scenario',
                    'text' => 'Raflar bos, tedarik gecikmeli, musteri sikayetleri artiyor. Ne yaparsiniz?',
                    'competency_code' => 'PROBLEM_SOLVING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Tedarik departmanina sikayet ederim', 'score' => 30],
                        ['value' => 'b', 'text' => 'Alternatif tedarik arar, musterileri bilgilendirir, stok yanetimi yaparim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Beklerim, olan olmus', 'score' => 0],
                        ['value' => 'd', 'text' => 'Kampanyayla musteri cekerim baska urunlere', 'score' => 50],
                    ],
                ],
                [
                    'order' => 10,
                    'type' => 'behavior',
                    'text' => 'En iyi satisciniZ terfı istedi ama uygun pozisyon yok. Ne yaparsiniz?',
                    'competency_code' => 'LEADERSHIP',
                    'options' => [
                        ['value' => 'a', 'text' => 'Malesef yok, beklemek lazim derim', 'score' => 30],
                        ['value' => 'b', 'text' => 'Gelisim plani yapar, yeni sorumluluklar verir, firsat cikarsa desteklerim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Zam oneririm', 'score' => 50],
                        ['value' => 'd', 'text' => 'Baska firmaya bakabilecegini soylarim', 'score' => 0],
                    ],
                ],
                [
                    'order' => 11,
                    'type' => 'knowledge',
                    'text' => 'Magaza karlilik analizi yapilirken hangi metrik on plandadir?',
                    'competency_code' => 'BUSINESS_ACUMEN',
                    'options' => [
                        ['value' => 'a', 'text' => 'Sadece satis cirasi', 'score' => 30],
                        ['value' => 'b', 'text' => 'Satis, kar marji, operasyon giderleri birlikte', 'score' => 100],
                        ['value' => 'c', 'text' => 'Musteri sayisi', 'score' => 20],
                        ['value' => 'd', 'text' => 'Personel sayisi', 'score' => 10],
                    ],
                ],
                [
                    'order' => 12,
                    'type' => 'scenario',
                    'text' => 'Bir calisaniniz surekli hastalik raporu aliyor, ekip iş yukunden sikayet ediyor. Ne yaparsiniz?',
                    'competency_code' => 'PEOPLE_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'IK\'nin islemi, ben karismam', 'score' => 20],
                        ['value' => 'b', 'text' => 'Calisanla özel gorusur, durumu anlar, ekip dengesini saglarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Disiplin sureci baslatirim', 'score' => 30],
                        ['value' => 'd', 'text' => 'Ekibe daha fazla mesai yaptiririm', 'score' => 0],
                    ],
                ],
            ],
            'scoring_config' => [
                'passing_score' => 65,
                'level_thresholds' => [
                    'basarisiz' => 45,
                    'gelisime_acik' => 60,
                    'yeterli' => 75,
                    'iyi' => 87,
                    'mukemmel' => 100,
                ],
            ],
            'time_limit_minutes' => 30,
            'is_active' => true,
        ];
    }

    private function getKoordinatorTemplate(): array
    {
        return [
            'name' => 'Koordinator Degerlendirmesi',
            'slug' => 'koordinator-assessment',
            'role_category' => 'koordinator',
            'description' => 'Bolge/alan koordinatorleri icin yonetim ve koordinasyon yetkinlik degerlendirmesi',
            'competencies' => [
                ['code' => 'STRATEGIC_THINK', 'name' => 'Stratejik Dusunme', 'description' => 'Buyuk resmi gorme ve planlama', 'weight' => 1.8],
                ['code' => 'MULTI_UNIT_MGMT', 'name' => 'Coklu Birim Yonetimi', 'description' => 'Birden fazla lokasyonu yonetme', 'weight' => 1.5],
                ['code' => 'STAKEHOLDER_MGMT', 'name' => 'Paydas Yonetimi', 'description' => 'Farkli taraflarla iliski yonetimi', 'weight' => 1.2],
                ['code' => 'DATA_ANALYSIS', 'name' => 'Veri Analizi', 'description' => 'Verileri yorumlama ve aksiyona dönüstürme', 'weight' => 1.3],
                ['code' => 'CHANGE_MGMT', 'name' => 'Degisim Yonetimi', 'description' => 'Degisim sureclerini yonetme', 'weight' => 1.2],
                ['code' => 'COACHING', 'name' => 'Koçluk', 'description' => 'Mudur ve ekipleri gelistirme', 'weight' => 1.2],
                ['code' => 'CRISIS_MGMT', 'name' => 'Kriz Yonetimi', 'description' => 'Acil durumlarda karar verme', 'weight' => 1.3],
                ['code' => 'REPORTING', 'name' => 'Raporlama', 'description' => 'Ust yonetime etkili raporlama', 'weight' => 1.0],
            ],
            'red_flags' => [
                ['code' => 'NO_DELEGATION', 'name' => 'Yetki Devredememe', 'description' => 'Her isi kendisi yapmaya calisma', 'severity' => 'high'],
                ['code' => 'DATA_BLIND', 'name' => 'Veri Korlugu', 'description' => 'Verileri gormezden gelme veya yanlis yorumlama', 'severity' => 'high'],
                ['code' => 'INCONSISTENT', 'name' => 'Tutarsizlik', 'description' => 'Farkli lokasyonlara farkli standart uygulama', 'severity' => 'medium'],
                ['code' => 'CONFLICT_ESCALATE', 'name' => 'Catisma Tirmanmasi', 'description' => 'Sorunlari buyutme egilimi', 'severity' => 'medium'],
            ],
            'questions' => [
                [
                    'order' => 1,
                    'type' => 'scenario',
                    'text' => '5 magazanizdan 2\'si surekli hedef tutturmuyor, 3\'u basarili. Ne yaparsiniz?',
                    'competency_code' => 'MULTI_UNIT_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Basarisiz mudurleri degistiririm', 'score' => 20],
                        ['value' => 'b', 'text' => 'Kok neden analizi yapar, ozel aksiyon plani olusturusum', 'score' => 100],
                        ['value' => 'c', 'text' => 'Hedefleri dusururum', 'score' => 10],
                        ['value' => 'd', 'text' => 'Basarililari ornek gosteririm', 'score' => 50],
                    ],
                ],
                [
                    'order' => 2,
                    'type' => 'scenario',
                    'text' => 'Bolge bazinda yeni bir operasyonel degisiklik uygulanacak. Direnç bekliyorsunuz. Ne yaparsiniz?',
                    'competency_code' => 'CHANGE_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Direktif olarak gonderir bekleria', 'score' => 10],
                        ['value' => 'b', 'text' => 'Onceden hazirlık yapar, egitim verir, pilot uygularim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Mudurlere birakir im uygulama sekli ni', 'score' => 30],
                        ['value' => 'd', 'text' => 'Ust yonetimden baski isterim', 'score' => 20],
                    ],
                ],
                [
                    'order' => 3,
                    'type' => 'knowledge',
                    'text' => 'Performans takibinde trailing vs. leading gosterge farki nedir?',
                    'competency_code' => 'DATA_ANALYSIS',
                    'options' => [
                        ['value' => 'a', 'text' => 'Ikisi de ayni, farkli isim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Trailing gecmis sonuc, leading gelecek tahminł gostergesi', 'score' => 100],
                        ['value' => 'c', 'text' => 'Trailing haftalik, leading aylik', 'score' => 10],
                        ['value' => 'd', 'text' => 'Trailing satis, leading maliyet', 'score' => 20],
                    ],
                ],
                [
                    'order' => 4,
                    'type' => 'scenario',
                    'text' => 'Bir magaza muduru surekli kaynak istiyor ama performansi dusuk. Ne yaparsiniz?',
                    'competency_code' => 'COACHING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Kaynak vermem, once performans', 'score' => 30],
                        ['value' => 'b', 'text' => 'Birlikte analiz yapar, onde kaynak problemý mi performans mi anlarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'IK\'ya gönderirimi degerlendirmeye', 'score' => 20],
                        ['value' => 'd', 'text' => 'Denesin diye kaynak veririm', 'score' => 40],
                    ],
                ],
                [
                    'order' => 5,
                    'type' => 'scenario',
                    'text' => 'Gece yarisi bir magazanizda hirsizlik yasandi. Ne yaparsiniz?',
                    'competency_code' => 'CRISIS_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Sabah bakarim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Derhal haberdar olur, gerekli aksiyonlari koordine ederim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Güvenlik firmasini ararím', 'score' => 50],
                        ['value' => 'd', 'text' => 'Mudure birakir im', 'score' => 30],
                    ],
                ],
                [
                    'order' => 6,
                    'type' => 'behavior',
                    'text' => 'Ust yonetim detayli bolge raporu istiyor, zamaniniz kisitli. Ne yaparsiniz?',
                    'competency_code' => 'REPORTING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Kisaltilmis versiyon gonderirim', 'score' => 40],
                        ['value' => 'b', 'text' => 'Oncelikleri belirler, kritik verilere odaklanirim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Ek zaman isterim', 'score' => 50],
                        ['value' => 'd', 'text' => 'Asistana yaptiririm', 'score' => 30],
                    ],
                ],
                [
                    'order' => 7,
                    'type' => 'scenario',
                    'text' => 'Iki magaza muduru birbirine rakip gibi davranip isbirligi yapmiyor. Ne yaparsiniz?',
                    'competency_code' => 'STAKEHOLDER_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Rekabet iyi, birakir im', 'score' => 10],
                        ['value' => 'b', 'text' => 'Ortàk hedefler koyar, isbirligi gerektiren projeler atarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Birini baska bolgeye gonderirim', 'score' => 30],
                        ['value' => 'd', 'text' => 'Ikisine de uyari veririm', 'score' => 40],
                    ],
                ],
                [
                    'order' => 8,
                    'type' => 'knowledge',
                    'text' => 'Bolge butcesi hazirlarken once neye bakmalisiniz?',
                    'competency_code' => 'STRATEGIC_THINK',
                    'options' => [
                        ['value' => 'a', 'text' => 'Gecen yil ne harcadik', 'score' => 40],
                        ['value' => 'b', 'text' => 'Strateji hedefleri, pazar trendleri, lokasyon ihtiyaclari', 'score' => 100],
                        ['value' => 'c', 'text' => 'Merkezin verdigi limit', 'score' => 30],
                        ['value' => 'd', 'text' => 'Magaza mudurlerinin talepleri', 'score' => 50],
                    ],
                ],
                [
                    'order' => 9,
                    'type' => 'scenario',
                    'text' => 'Bir mudur cok basarili ama kurallara uymuyor. Diger mudurlar adiletsizlik hissediyor. Ne yaparsiniz?',
                    'competency_code' => 'COACHING',
                    'options' => [
                        ['value' => 'a', 'text' => 'Basarili oldugu icin goz yumarim', 'score' => 0],
                        ['value' => 'b', 'text' => 'Net konusur, kurallara uyumu saglarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Diger mudurlere açiklarim ozel durumu', 'score' => 20],
                        ['value' => 'd', 'text' => 'IK\'ya raporlarim', 'score' => 40],
                    ],
                ],
                [
                    'order' => 10,
                    'type' => 'scenario',
                    'text' => 'Bolgenizde yeni magaza acilacak. Planlamayi nasil yaparsiniz?',
                    'competency_code' => 'STRATEGIC_THINK',
                    'options' => [
                        ['value' => 'a', 'text' => 'Merkez planlasın, ben uygularım', 'score' => 20],
                        ['value' => 'b', 'text' => 'Lokasyon analizi, rakip haritalama, ekip plani, zaman cizelgesi hazirlarim', 'score' => 100],
                        ['value' => 'c', 'text' => 'Tecrubeli bir mudure devrederim', 'score' => 30],
                        ['value' => 'd', 'text' => 'Diger magazalardan takviye yaparim', 'score' => 50],
                    ],
                ],
                [
                    'order' => 11,
                    'type' => 'behavior',
                    'text' => 'Veri raporlarinizda bir tutarsizlik fark ettiniz. Hata sizin ekibinizden. Ne yaparsiniz?',
                    'competency_code' => 'DATA_ANALYSIS',
                    'options' => [
                        ['value' => 'a', 'text' => 'Sessizce duzeltir geceririm', 'score' => 30],
                        ['value' => 'b', 'text' => 'Seffaf sekilde bildiri, duzeltir, onlem alírím', 'score' => 100],
                        ['value' => 'c', 'text' => 'Hatanin kaynagini bulmam', 'score' => 10],
                        ['value' => 'd', 'text' => 'Sorumluyu uyarir im', 'score' => 50],
                    ],
                ],
                [
                    'order' => 12,
                    'type' => 'scenario',
                    'text' => 'Ust yonetim hizli sonuc istiyor ama sahadaki gercekler farkli. Ne yaparsiniz?',
                    'competency_code' => 'STAKEHOLDER_MGMT',
                    'options' => [
                        ['value' => 'a', 'text' => 'Saha ne derse onu yaparim', 'score' => 30],
                        ['value' => 'b', 'text' => 'Ust yonetime gercekleri sunar, uygulanabilir plan onerir im', 'score' => 100],
                        ['value' => 'c', 'text' => 'Ust yonetimin dedigi olur', 'score' => 20],
                        ['value' => 'd', 'text' => 'Ortala bir yol bulurum kimse bilmeden', 'score' => 40],
                    ],
                ],
            ],
            'scoring_config' => [
                'passing_score' => 70,
                'level_thresholds' => [
                    'basarisiz' => 50,
                    'gelisime_acik' => 65,
                    'yeterli' => 78,
                    'iyi' => 90,
                    'mukemmel' => 100,
                ],
            ],
            'time_limit_minutes' => 35,
            'is_active' => true,
        ];
    }
}
