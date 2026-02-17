<?php

namespace Database\Seeders;

use App\Models\PositionTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ========================================
            // PERAKENDE / RETAIL
            // ========================================
            [
                'name' => 'Kasiyer',
                'slug' => 'kasiyer',
                'description' => 'Market, magaza ve AVM kasiyer pozisyonu',
                'category' => 'retail',
                'competencies' => [
                    ['name' => 'Dikkat ve Dogruluk', 'weight' => 30],
                    ['name' => 'Musteri Iliskileri', 'weight' => 25],
                    ['name' => 'Hiz ve Verimlilik', 'weight' => 20],
                    ['name' => 'Nakit Yonetimi', 'weight' => 15],
                    ['name' => 'Stres Yonetimi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Para sayiminda hata gecmisi',
                    'Kasa acigi kaydi',
                    'Musteri sikayetleri',
                ],
            ],
            [
                'name' => 'Magaza Gorevlisi',
                'slug' => 'magaza-gorevlisi',
                'description' => 'Genel magaza personeli pozisyonu',
                'category' => 'retail',
                'competencies' => [
                    ['name' => 'Musteri Hizmeti', 'weight' => 30],
                    ['name' => 'Urun Bilgisi', 'weight' => 25],
                    ['name' => 'Takim Calismasi', 'weight' => 20],
                    ['name' => 'Duzen ve Organizasyon', 'weight' => 15],
                    ['name' => 'Iletisim', 'weight' => 10],
                ],
                'red_flags' => [
                    'Devamsizlik sorunu',
                    'Calisma arkadaslariyla uyumsuzluk',
                ],
            ],
            [
                'name' => 'Satis Danismani',
                'slug' => 'satis-danismani',
                'description' => 'Magaza satis danismani pozisyonu',
                'category' => 'retail',
                'competencies' => [
                    ['name' => 'Ikna Kabiliyeti', 'weight' => 30],
                    ['name' => 'Urun Bilgisi', 'weight' => 25],
                    ['name' => 'Musteri Iliskileri', 'weight' => 20],
                    ['name' => 'Hedef Odaklilik', 'weight' => 15],
                    ['name' => 'Iletisim', 'weight' => 10],
                ],
                'red_flags' => [
                    'Satis hedeflerini karsilayamama',
                    'Agresif satis teknikleri',
                ],
            ],
            [
                'name' => 'Depo Gorevlisi',
                'slug' => 'depo-gorevlisi',
                'description' => 'Depo ve stok yonetimi personeli',
                'category' => 'retail',
                'competencies' => [
                    ['name' => 'Fiziksel Dayaniklilik', 'weight' => 25],
                    ['name' => 'Dikkat ve Dogruluk', 'weight' => 25],
                    ['name' => 'Organizasyon', 'weight' => 20],
                    ['name' => 'Takim Calismasi', 'weight' => 15],
                    ['name' => 'Guvenlik Bilinci', 'weight' => 15],
                ],
                'red_flags' => [
                    'Is guvenligi ihlalleri',
                    'Stok sayim hatalari',
                ],
            ],
            [
                'name' => 'Tezgahtar',
                'slug' => 'tezgahtar',
                'description' => 'Pastane, kafe ve magaza tezgahtar pozisyonu',
                'category' => 'retail',
                'competencies' => [
                    ['name' => 'Musteri Iliskileri', 'weight' => 30],
                    ['name' => 'Urun Bilgisi', 'weight' => 25],
                    ['name' => 'Hiz ve Verimlilik', 'weight' => 20],
                    ['name' => 'Nakit Islemleri', 'weight' => 15],
                    ['name' => 'Iletisim', 'weight' => 10],
                ],
                'red_flags' => [
                    'Kasa acigi',
                    'Musteri sikayetleri',
                    'Urun bilgisi eksikligi',
                ],
            ],

            // ========================================
            // YIYECEK & ICECEK / FOOD & BEVERAGE
            // ========================================
            [
                'name' => 'Garson',
                'slug' => 'garson',
                'description' => 'Restoran ve kafe garson pozisyonu',
                'category' => 'food_beverage',
                'competencies' => [
                    ['name' => 'Musteri Hizmeti', 'weight' => 30],
                    ['name' => 'Hiz ve Verimlilik', 'weight' => 25],
                    ['name' => 'Menu Bilgisi', 'weight' => 20],
                    ['name' => 'Takim Calismasi', 'weight' => 15],
                    ['name' => 'Stres Yonetimi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Musteri sikayetleri',
                    'Hijyen kurallarina uymama',
                    'Siparis hatalari',
                ],
            ],
            [
                'name' => 'Barista',
                'slug' => 'barista',
                'description' => 'Kahve hazirlama ve servis pozisyonu',
                'category' => 'food_beverage',
                'competencies' => [
                    ['name' => 'Kahve Bilgisi', 'weight' => 30],
                    ['name' => 'Musteri Iliskileri', 'weight' => 25],
                    ['name' => 'Hiz ve Kalite', 'weight' => 20],
                    ['name' => 'Hijyen', 'weight' => 15],
                    ['name' => 'Yaraticilik', 'weight' => 10],
                ],
                'red_flags' => [
                    'Urun kalitesinde tutarsizlik',
                    'Hijyen ihmali',
                ],
            ],
            [
                'name' => 'Asci',
                'slug' => 'asci',
                'description' => 'Mutfak asci pozisyonu',
                'category' => 'food_beverage',
                'competencies' => [
                    ['name' => 'Yemek Hazirlama', 'weight' => 30],
                    ['name' => 'Hijyen ve Guvenlik', 'weight' => 25],
                    ['name' => 'Zaman Yonetimi', 'weight' => 20],
                    ['name' => 'Takim Calismasi', 'weight' => 15],
                    ['name' => 'Maliyet Bilinci', 'weight' => 10],
                ],
                'red_flags' => [
                    'Hijyen ihlalleri',
                    'Tutarsiz yemek kalitesi',
                    'Fire oranlari yuksek',
                ],
            ],
            [
                'name' => 'Pasta Ustasi',
                'slug' => 'pasta-ustasi',
                'description' => 'Pastane pasta ve tatli hazirlama pozisyonu',
                'category' => 'food_beverage',
                'competencies' => [
                    ['name' => 'Pasta Yapim Teknikleri', 'weight' => 35],
                    ['name' => 'Yaraticilik', 'weight' => 20],
                    ['name' => 'Hijyen', 'weight' => 20],
                    ['name' => 'Zaman Yonetimi', 'weight' => 15],
                    ['name' => 'Detay Odaklilik', 'weight' => 10],
                ],
                'red_flags' => [
                    'Recete uyumsuzlugu',
                    'Hijyen ihmali',
                ],
            ],
            [
                'name' => 'Mutfak Yardimcisi',
                'slug' => 'mutfak-yardimcisi',
                'description' => 'Mutfak hazirlik ve destek personeli',
                'category' => 'food_beverage',
                'competencies' => [
                    ['name' => 'Caliskanlik', 'weight' => 30],
                    ['name' => 'Hijyen Bilinci', 'weight' => 25],
                    ['name' => 'Talimat Takibi', 'weight' => 20],
                    ['name' => 'Hiz', 'weight' => 15],
                    ['name' => 'Takim Calismasi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Yavaslik',
                    'Hijyen ihmali',
                ],
            ],

            // ========================================
            // KONAKLAMA / HOSPITALITY
            // ========================================
            [
                'name' => 'Resepsiyonist',
                'slug' => 'resepsiyonist',
                'description' => 'Otel ve isletme resepsiyon gorevlisi',
                'category' => 'hospitality',
                'competencies' => [
                    ['name' => 'Misafir Iliskileri', 'weight' => 30],
                    ['name' => 'Iletisim', 'weight' => 25],
                    ['name' => 'Problem Cozme', 'weight' => 20],
                    ['name' => 'Organizasyon', 'weight' => 15],
                    ['name' => 'Yabanci Dil', 'weight' => 10],
                ],
                'red_flags' => [
                    'Kaba davranis',
                    'Rezervasyon hatalari',
                ],
            ],
            [
                'name' => 'Kat Gorevlisi',
                'slug' => 'kat-gorevlisi',
                'description' => 'Otel kat temizlik ve duzenleme personeli',
                'category' => 'hospitality',
                'competencies' => [
                    ['name' => 'Detay Odaklilik', 'weight' => 30],
                    ['name' => 'Hiz ve Verimlilik', 'weight' => 25],
                    ['name' => 'Hijyen Bilinci', 'weight' => 25],
                    ['name' => 'Fiziksel Dayaniklilik', 'weight' => 10],
                    ['name' => 'Duzgun Calisma', 'weight' => 10],
                ],
                'red_flags' => [
                    'Temizlik standartlarini karsilayamama',
                    'Misafir esyalarina zarar',
                ],
            ],

            // ========================================
            // GUVENLIK / SECURITY
            // ========================================
            [
                'name' => 'Guvenlik Gorevlisi',
                'slug' => 'guvenlik-gorevlisi',
                'description' => 'Site, plaza ve isletme guvenlik personeli',
                'category' => 'security',
                'competencies' => [
                    ['name' => 'Dikkat ve Gozlem', 'weight' => 30],
                    ['name' => 'Fiziksel Dayaniklilik', 'weight' => 20],
                    ['name' => 'Iletisim', 'weight' => 20],
                    ['name' => 'Kriz Yonetimi', 'weight' => 20],
                    ['name' => 'Kural Uyumu', 'weight' => 10],
                ],
                'red_flags' => [
                    'Gorev basinda uyuma',
                    'Yetkisiz alanlara erisim',
                    'Catisma gecmisi',
                ],
            ],

            // ========================================
            // TEMIZLIK / CLEANING
            // ========================================
            [
                'name' => 'Temizlik Gorevlisi',
                'slug' => 'temizlik-gorevlisi',
                'description' => 'Ofis, site ve isletme temizlik personeli',
                'category' => 'cleaning',
                'competencies' => [
                    ['name' => 'Detay Odaklilik', 'weight' => 30],
                    ['name' => 'Fiziksel Dayaniklilik', 'weight' => 25],
                    ['name' => 'Zaman Yonetimi', 'weight' => 20],
                    ['name' => 'Kimyasal Bilgisi', 'weight' => 15],
                    ['name' => 'Guvenilirlik', 'weight' => 10],
                ],
                'red_flags' => [
                    'Temizlik standartlarinda dusus',
                    'Ekipman hasari',
                ],
            ],

            // ========================================
            // MUSTERI HIZMETLERI / CUSTOMER SERVICE
            // ========================================
            [
                'name' => 'Musteri Temsilcisi',
                'slug' => 'musteri-temsilcisi',
                'description' => 'Cagri merkezi ve musteri hizmetleri personeli',
                'category' => 'customer_service',
                'competencies' => [
                    ['name' => 'Iletisim', 'weight' => 30],
                    ['name' => 'Sabir', 'weight' => 25],
                    ['name' => 'Problem Cozme', 'weight' => 20],
                    ['name' => 'Urun/Hizmet Bilgisi', 'weight' => 15],
                    ['name' => 'Stres Yonetimi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Musteri ile tartisma',
                    'Cozum orani dusuk',
                    'Cagri suresi asimi',
                ],
            ],

            // ========================================
            // LOJISTIK / LOGISTICS
            // ========================================
            [
                'name' => 'Kurye',
                'slug' => 'kurye',
                'description' => 'Paket ve kargo teslimat personeli',
                'category' => 'logistics',
                'competencies' => [
                    ['name' => 'Zaman Yonetimi', 'weight' => 30],
                    ['name' => 'Trafik Bilgisi', 'weight' => 25],
                    ['name' => 'Musteri Iliskileri', 'weight' => 20],
                    ['name' => 'Sorumluluk', 'weight' => 15],
                    ['name' => 'Fiziksel Dayaniklilik', 'weight' => 10],
                ],
                'red_flags' => [
                    'Gec teslimat oranlari yuksek',
                    'Hasar raporlari',
                    'Trafik cezalari',
                ],
            ],
            [
                'name' => 'Forklift Operatoru',
                'slug' => 'forklift-operatoru',
                'description' => 'Depo forklift ve is makinesi operatoru',
                'category' => 'logistics',
                'competencies' => [
                    ['name' => 'Makine Kullanimi', 'weight' => 30],
                    ['name' => 'Guvenlik Bilinci', 'weight' => 30],
                    ['name' => 'Dikkat', 'weight' => 20],
                    ['name' => 'Fiziksel Koordinasyon', 'weight' => 10],
                    ['name' => 'Takim Calismasi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Is kazasi gecmisi',
                    'Lisans/sertifika eksikligi',
                ],
            ],

            // ========================================
            // SAGLIK / HEALTHCARE
            // ========================================
            [
                'name' => 'Hasta Bakici',
                'slug' => 'hasta-bakici',
                'description' => 'Hastane ve evde hasta bakim personeli',
                'category' => 'healthcare',
                'competencies' => [
                    ['name' => 'Empati', 'weight' => 30],
                    ['name' => 'Sabir', 'weight' => 25],
                    ['name' => 'Fiziksel Dayaniklilik', 'weight' => 20],
                    ['name' => 'Temel Saglik Bilgisi', 'weight' => 15],
                    ['name' => 'Iletisim', 'weight' => 10],
                ],
                'red_flags' => [
                    'Hasta ihmal sikayetleri',
                    'Ilac hatalari',
                ],
            ],

            // ========================================
            // COCUK BAKIMI / CHILDCARE
            // ========================================
            [
                'name' => 'Cocuk Bakicisi',
                'slug' => 'cocuk-bakicisi',
                'description' => 'Evde ve kurumlarda cocuk bakim personeli',
                'category' => 'childcare',
                'competencies' => [
                    ['name' => 'Cocuk Gelisimi Bilgisi', 'weight' => 25],
                    ['name' => 'Sabir', 'weight' => 25],
                    ['name' => 'Guvenlik Bilinci', 'weight' => 25],
                    ['name' => 'Yaraticilik', 'weight' => 15],
                    ['name' => 'Iletisim', 'weight' => 10],
                ],
                'red_flags' => [
                    'Cocuk guvenligi ihmali',
                    'Sabir eksikligi',
                    'Referans sorunlari',
                ],
            ],

            // ========================================
            // GUZELLIK / BEAUTY
            // ========================================
            [
                'name' => 'Kuafor',
                'slug' => 'kuafor',
                'description' => 'Sac kesim ve bakim uzmani',
                'category' => 'beauty',
                'competencies' => [
                    ['name' => 'Teknik Beceri', 'weight' => 35],
                    ['name' => 'Musteri Iliskileri', 'weight' => 25],
                    ['name' => 'Yaraticilik', 'weight' => 20],
                    ['name' => 'Hijyen', 'weight' => 10],
                    ['name' => 'Trend Takibi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Musteri memnuniyetsizligi',
                    'Hijyen ihmali',
                ],
            ],
            [
                'name' => 'Guzellik Uzmani',
                'slug' => 'guzellik-uzmani',
                'description' => 'Cilt bakimi ve guzellik uzmani',
                'category' => 'beauty',
                'competencies' => [
                    ['name' => 'Cilt Bilgisi', 'weight' => 30],
                    ['name' => 'Teknik Beceri', 'weight' => 25],
                    ['name' => 'Musteri Iliskileri', 'weight' => 20],
                    ['name' => 'Hijyen', 'weight' => 15],
                    ['name' => 'Urun Bilgisi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Cilt reaksiyonu sikayetleri',
                    'Hijyen ihmali',
                ],
            ],

            // ========================================
            // TEKNOLOJI / TECHNOLOGY
            // ========================================
            [
                'name' => 'Yazilim Gelistirici',
                'slug' => 'yazilim-gelistirici',
                'description' => 'Backend, Frontend veya Full-Stack developer',
                'category' => 'technology',
                'competencies' => [
                    ['name' => 'Teknik Bilgi', 'weight' => 35],
                    ['name' => 'Problem Cozme', 'weight' => 25],
                    ['name' => 'Takim Calismasi', 'weight' => 15],
                    ['name' => 'Ogrenme Istegi', 'weight' => 15],
                    ['name' => 'Iletisim', 'weight' => 10],
                ],
                'red_flags' => [
                    'Kod kalitesi dusuk',
                    'Deadline kacirma',
                    'Takimla uyumsuzluk',
                ],
            ],
            [
                'name' => 'IT Destek Uzmani',
                'slug' => 'it-destek-uzmani',
                'description' => 'Teknik destek ve IT helpdesk personeli',
                'category' => 'technology',
                'competencies' => [
                    ['name' => 'Teknik Bilgi', 'weight' => 30],
                    ['name' => 'Problem Cozme', 'weight' => 25],
                    ['name' => 'Iletisim', 'weight' => 20],
                    ['name' => 'Sabir', 'weight' => 15],
                    ['name' => 'Dokumantasyon', 'weight' => 10],
                ],
                'red_flags' => [
                    'Cozum orani dusuk',
                    'Iletisim sorunlari',
                ],
            ],

            // ========================================
            // EGITIM / EDUCATION
            // ========================================
            [
                'name' => 'Ogretmen',
                'slug' => 'ogretmen',
                'description' => 'Okul ve kurs ogretmeni',
                'category' => 'education',
                'competencies' => [
                    ['name' => 'Alan Bilgisi', 'weight' => 30],
                    ['name' => 'Iletisim', 'weight' => 25],
                    ['name' => 'Sabir', 'weight' => 20],
                    ['name' => 'Yaraticilik', 'weight' => 15],
                    ['name' => 'Sinif Yonetimi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Ogrenci sikayetleri',
                    'Disiplin sorunlari',
                ],
            ],

            // ========================================
            // FINANS / FINANCE
            // ========================================
            [
                'name' => 'Muhasebe Elemani',
                'slug' => 'muhasebe-elemani',
                'description' => 'Sirket muhasebe ve finans personeli',
                'category' => 'finance',
                'competencies' => [
                    ['name' => 'Muhasebe Bilgisi', 'weight' => 35],
                    ['name' => 'Dikkat ve Dogruluk', 'weight' => 25],
                    ['name' => 'Excel/Yazilim', 'weight' => 20],
                    ['name' => 'Zaman Yonetimi', 'weight' => 10],
                    ['name' => 'Mevzuat Bilgisi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Hesap hatalari',
                    'Geciken beyannameler',
                ],
            ],

            // ========================================
            // INSAN KAYNAKLARI / HR
            // ========================================
            [
                'name' => 'IK Uzmani',
                'slug' => 'ik-uzmani',
                'description' => 'Insan kaynaklari departmani personeli',
                'category' => 'hr',
                'competencies' => [
                    ['name' => 'Iletisim', 'weight' => 30],
                    ['name' => 'Organizasyon', 'weight' => 25],
                    ['name' => 'Is Hukuku Bilgisi', 'weight' => 20],
                    ['name' => 'Empati', 'weight' => 15],
                    ['name' => 'Gizlilik', 'weight' => 10],
                ],
                'red_flags' => [
                    'Gizlilik ihlali',
                    'Yasalara aykiri uygulamalar',
                ],
            ],

            // ========================================
            // PAZARLAMA / MARKETING
            // ========================================
            [
                'name' => 'Dijital Pazarlama Uzmani',
                'slug' => 'dijital-pazarlama-uzmani',
                'description' => 'Sosyal medya ve dijital pazarlama personeli',
                'category' => 'marketing',
                'competencies' => [
                    ['name' => 'Dijital Platformlar', 'weight' => 30],
                    ['name' => 'Icerik Uretimi', 'weight' => 25],
                    ['name' => 'Analitik Dusunme', 'weight' => 20],
                    ['name' => 'Yaraticilik', 'weight' => 15],
                    ['name' => 'Trend Takibi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Dusuk kampanya performansi',
                    'Marka uyumsuzlugu',
                ],
            ],
        ];

        $defaultQuestionRules = [
            'max_questions' => 5,
            'time_per_question' => 120,
            'required_competencies' => [],
        ];

        $defaultScoringRubric = [
            'excellent' => ['min' => 80, 'label' => 'Mukemmel'],
            'good' => ['min' => 60, 'label' => 'Iyi'],
            'average' => ['min' => 40, 'label' => 'Orta'],
            'poor' => ['min' => 0, 'label' => 'Zayif'],
        ];

        foreach ($templates as $template) {
            PositionTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'category' => $template['category'],
                    'competencies' => $template['competencies'],
                    'red_flags' => $template['red_flags'],
                    'question_rules' => $defaultQuestionRules,
                    'scoring_rubric' => $defaultScoringRubric,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Added/updated ' . count($templates) . ' position templates!');
    }
}
