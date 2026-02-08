<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\JobDomain;
use App\Models\JobPosition;
use App\Models\JobSubdomain;
use App\Models\PositionQuestion;
use App\Models\RoleArchetype;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RetailPositionsSeeder extends Seeder
{
    private array $archetypes = [];
    private array $competencies = [];

    public function run(): void
    {
        $this->loadArchetypes();
        $this->loadCompetencies();

        $retailDomain = JobDomain::where('code', 'RETAIL')->first();
        if (!$retailDomain) {
            $this->command->error('Retail domain not found. Run TaxonomySeeder first.');
            return;
        }

        $this->seedStoreOperationsPositions();
        $this->seedSalesPositions();
        $this->seedCashierPositions();
        $this->seedVisualMerchandisingPositions();
        $this->seedWarehousePositions();
    }

    private function loadArchetypes(): void
    {
        $this->archetypes = RoleArchetype::pluck('id', 'code')->toArray();
    }

    private function loadCompetencies(): void
    {
        $this->competencies = Competency::pluck('id', 'code')->toArray();
    }

    private function seedStoreOperationsPositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'RETAIL_STORE')->first();
        if (!$subdomain) return;

        // Mağaza Elemanı (Entry Level)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_STORE_ASSOCIATE',
            'name_tr' => 'Mağaza Elemanı',
            'name_en' => 'Store Associate',
            'archetype' => 'ENTRY',
            'description_tr' => 'Mağaza içi müşteri hizmetleri, ürün yerleşimi ve satış desteği sağlayan giriş seviye pozisyon',
            'description_en' => 'Entry-level position providing in-store customer service, product placement and sales support',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['CUSTOMER_FOCUS', 'COMMUNICATION', 'TEAMWORK', 'RELIABILITY'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Bir müşteri aradığı ürünü bulamadığında nasıl yardımcı olursunuz?',
                'question_en' => 'How do you help a customer who cannot find the product they are looking for?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Aktif dinleme', 'Alternatif önerme', 'Müşteriyle birlikte ürüne yönlendirme', 'Stok kontrolü'],
                'red_flag_indicators' => ['Sadece rafa yönlendirme', 'İlgisizlik', 'Ürün bilgisi eksikliği'],
            ],
            [
                'question_tr' => 'Yoğun bir günde birden fazla müşteri aynı anda yardım istediğinde ne yaparsınız?',
                'question_en' => 'What do you do when multiple customers ask for help at the same time on a busy day?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'situational',
                'expected_indicators' => ['Önceliklendirme', 'Kısa sürede çözüm', 'Ekip desteği isteme', 'Tüm müşterileri görmezden gelmeme'],
                'red_flag_indicators' => ['Panik', 'Müşteri görmezden gelme', 'Tek müşteriye takılıp kalma'],
            ],
            [
                'question_tr' => 'Daha önce çalıştığınız bir yerde zor bir müşteriyle nasıl başa çıktınız?',
                'question_en' => 'How did you handle a difficult customer at a previous job?',
                'competency' => 'COMMUNICATION',
                'type' => 'behavioral',
                'expected_indicators' => ['Sakin kalma', 'Empati kurma', 'Çözüm odaklılık', 'Profesyonellik'],
                'red_flag_indicators' => ['Müşteriyi suçlama', 'Tartışma', 'Duygusal tepki'],
            ],
            [
                'question_tr' => 'Ekip arkadaşlarınızla birlikte çalışma deneyiminizi anlatır mısınız?',
                'question_en' => 'Can you describe your experience working with team members?',
                'competency' => 'TEAMWORK',
                'type' => 'behavioral',
                'expected_indicators' => ['İşbirliği örnekleri', 'Yardımlaşma', 'Ortak hedef bilinci'],
                'red_flag_indicators' => ['Sadece bireysel başarı', 'Ekiple sorun yaşama', 'Başkalarını suçlama'],
            ],
            [
                'question_tr' => 'Mağazada ürün düzeni ve temizlik konusunda nelere dikkat edersiniz?',
                'question_en' => 'What do you pay attention to regarding product arrangement and cleanliness in the store?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Detay odaklılık', 'Standartlara uyum', 'Proaktif düzenleme'],
                'red_flag_indicators' => ['Detaylara önem vermeme', 'Sadece söyleneni yapma'],
            ],
            [
                'question_tr' => 'Vardiya başlangıcında ve bitiminde hangi görevleri yerine getirirsiniz?',
                'question_en' => 'What tasks do you perform at the beginning and end of your shift?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sistematik yaklaşım', 'Sorumluluk bilinci', 'Prosedürlere uyum'],
                'red_flag_indicators' => ['Belirsiz cevap', 'Görev bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Neden perakende sektöründe çalışmak istiyorsunuz?',
                'question_en' => 'Why do you want to work in the retail sector?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sektöre ilgi', 'Müşteri hizmetine yatkınlık', 'Kariyer hedefleri'],
                'red_flag_indicators' => ['Sadece maaş odaklı', 'Sektör hakkında bilgisizlik'],
            ],
            [
                'question_tr' => 'Bir ürün hakkında bilginiz olmadığında müşteriye nasıl yanıt verirsiniz?',
                'question_en' => 'How do you respond to a customer when you don\'t know about a product?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Dürüstlük', 'Araştırma yapma', 'Yardım isteme', 'Takip etme'],
                'red_flag_indicators' => ['Yanlış bilgi verme', 'Umursamazlık', 'Müşteriyi savma'],
            ],
        ]);

        // Kıdemli Mağaza Elemanı (Specialist)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_STORE_SENIOR_ASSOCIATE',
            'name_tr' => 'Kıdemli Mağaza Elemanı',
            'name_en' => 'Senior Store Associate',
            'archetype' => 'SPECIALIST',
            'description_tr' => 'Deneyimli mağaza elemanı, yeni çalışanlara rehberlik eder ve karmaşık müşteri taleplerini yönetir',
            'description_en' => 'Experienced store associate who guides new employees and manages complex customer requests',
            'experience_min_years' => 2,
            'experience_max_years' => 4,
            'education_level' => 'high_school',
            'competencies' => ['CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING', 'LEADERSHIP', 'ATTENTION_TO_DETAIL'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Yeni başlayan bir çalışana işi öğretirken hangi yöntemleri kullanırsınız?',
                'question_en' => 'What methods do you use when training a new employee?',
                'competency' => 'LEADERSHIP',
                'type' => 'behavioral',
                'expected_indicators' => ['Sabırlı yaklaşım', 'Adım adım öğretme', 'Pratik uygulama', 'Geri bildirim'],
                'red_flag_indicators' => ['Sabırsızlık', 'Sadece sözel anlatım', 'Takipsizlik'],
            ],
            [
                'question_tr' => 'Mağazada bir sorun fark ettiğinizde (stok eksikliği, fiyat hatası vb.) nasıl hareket edersiniz?',
                'question_en' => 'How do you act when you notice a problem in the store (stock shortage, price error, etc.)?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Proaktif müdahale', 'Raporlama', 'Çözüm üretme', 'Yöneticiyi bilgilendirme'],
                'red_flag_indicators' => ['Görmezden gelme', 'Sadece şikayet etme', 'Sorumluluk almama'],
            ],
            [
                'question_tr' => 'Müşteri şikayetlerini çözme konusundaki deneyiminizi paylaşır mısınız?',
                'question_en' => 'Can you share your experience in resolving customer complaints?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'behavioral',
                'expected_indicators' => ['Empati', 'Hızlı çözüm', 'Takip', 'Memnuniyet odaklılık'],
                'red_flag_indicators' => ['Müşteriyi suçlama', 'Çözümsüzlük', 'İlgisizlik'],
            ],
            [
                'question_tr' => 'Yoğun dönemlerde ekibin motivasyonunu nasıl yüksek tutarsınız?',
                'question_en' => 'How do you keep the team motivated during busy periods?',
                'competency' => 'TEAMWORK',
                'type' => 'behavioral',
                'expected_indicators' => ['Pozitif tutum', 'Destek sağlama', 'Görev dağılımı', 'Takdir etme'],
                'red_flag_indicators' => ['Negatiflik', 'Bireysel çalışma', 'Stres yansıtma'],
            ],
            [
                'question_tr' => 'Satış hedeflerine ulaşmak için ne tür stratejiler uygularsınız?',
                'question_en' => 'What strategies do you apply to achieve sales targets?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Müşteri ihtiyaç analizi', 'Ek satış teknikleri', 'Ürün bilgisi', 'Takip'],
                'red_flag_indicators' => ['Agresif satış', 'Hedef bilinci eksikliği', 'Pasiflik'],
            ],
            [
                'question_tr' => 'Mağaza standartlarının korunmasında hangi detaylara önem verirsiniz?',
                'question_en' => 'What details do you pay attention to in maintaining store standards?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Görsel düzen', 'Temizlik', 'Fiyat etiketleri', 'Stok düzeni'],
                'red_flag_indicators' => ['Genel cevaplar', 'Detay eksikliği'],
            ],
            [
                'question_tr' => 'Zor bir müşteriyle yaşadığınız ve başarıyla çözdüğünüz bir durumu anlatın.',
                'question_en' => 'Tell us about a difficult customer situation you successfully resolved.',
                'competency' => 'COMMUNICATION',
                'type' => 'behavioral',
                'expected_indicators' => ['STAR yöntemi', 'Sakinlik', 'Profesyonellik', 'Sonuç odaklılık'],
                'red_flag_indicators' => ['Suçlama', 'Duygusallık', 'Çözümsüzlük'],
            ],
            [
                'question_tr' => 'Mağaza envanterini yönetme konusundaki tecrübeniz nedir?',
                'question_en' => 'What is your experience in managing store inventory?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Stok takibi', 'Sayım deneyimi', 'Sistem kullanımı', 'Fire kontrolü'],
                'red_flag_indicators' => ['Envanter bilgisi eksikliği', 'İlgisizlik'],
            ],
        ]);

        // Mağaza Müdür Yardımcısı (Coordinator)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_STORE_ASST_MANAGER',
            'name_tr' => 'Mağaza Müdür Yardımcısı',
            'name_en' => 'Assistant Store Manager',
            'archetype' => 'COORDINATOR',
            'description_tr' => 'Mağaza operasyonlarını koordine eden, ekibi yöneten ve müdüre destek sağlayan pozisyon',
            'description_en' => 'Position that coordinates store operations, manages the team and supports the manager',
            'experience_min_years' => 3,
            'experience_max_years' => 5,
            'education_level' => 'associate',
            'competencies' => ['LEADERSHIP', 'COMMUNICATION', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT', 'CUSTOMER_FOCUS'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Bir ekip üyesi performans düşüklüğü yaşadığında nasıl yaklaşırsınız?',
                'question_en' => 'How do you approach when a team member experiences performance decline?',
                'competency' => 'LEADERSHIP',
                'type' => 'situational',
                'expected_indicators' => ['Bireysel görüşme', 'Nedenleri anlama', 'Destek sunma', 'Takip planı'],
                'red_flag_indicators' => ['Hemen ceza', 'Görmezden gelme', 'Başkalarının önünde eleştiri'],
            ],
            [
                'question_tr' => 'Mağaza hedeflerini ekibe nasıl iletir ve takip edersiniz?',
                'question_en' => 'How do you communicate and track store goals with the team?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Düzenli toplantılar', 'Görsel takip', 'Bireysel hedefler', 'Geri bildirim'],
                'red_flag_indicators' => ['Tek yönlü iletişim', 'Takipsizlik', 'Belirsiz hedefler'],
            ],
            [
                'question_tr' => 'Vardiya planlaması yaparken neleri göz önünde bulundurursunuz?',
                'question_en' => 'What do you consider when planning shifts?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Yoğunluk analizi', 'Yetkinlik dağılımı', 'Çalışan tercihleri', 'Maliyet dengesi'],
                'red_flag_indicators' => ['Plansızlık', 'Adaletsiz dağılım', 'İş yükü dengesizliği'],
            ],
            [
                'question_tr' => 'Mağazada acil bir durum (hırsızlık, kaza vb.) yaşandığında nasıl müdahale edersiniz?',
                'question_en' => 'How do you respond to an emergency (theft, accident, etc.) in the store?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Soğukkanlılık', 'Prosedürlere uyum', 'İletişim', 'Raporlama'],
                'red_flag_indicators' => ['Panik', 'Prosedür bilgisizliği', 'Yanlış müdahale'],
            ],
            [
                'question_tr' => 'Müdürün yokluğunda mağazayı nasıl yönetirsiniz?',
                'question_en' => 'How do you manage the store in the absence of the manager?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Özgüven', 'Karar alma', 'Sorumluluk', 'Raporlama'],
                'red_flag_indicators' => ['Belirsizlik', 'Karar alamama', 'Sürekli onay bekleme'],
            ],
            [
                'question_tr' => 'Ekip içi çatışmaları nasıl çözersiniz?',
                'question_en' => 'How do you resolve conflicts within the team?',
                'competency' => 'COMMUNICATION',
                'type' => 'behavioral',
                'expected_indicators' => ['Tarafsızlık', 'Dinleme', 'Arabuluculuk', 'Çözüm odaklılık'],
                'red_flag_indicators' => ['Taraf tutma', 'Görmezden gelme', 'Otoriter yaklaşım'],
            ],
            [
                'question_tr' => 'Satış performansını artırmak için hangi aksiyonları alırsınız?',
                'question_en' => 'What actions do you take to increase sales performance?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Veri analizi', 'Eğitim', 'Motivasyon', 'Stratejik planlama'],
                'red_flag_indicators' => ['Belirsiz stratejiler', 'Reaktif yaklaşım'],
            ],
            [
                'question_tr' => 'Maliyetleri kontrol altında tutmak için neler yaparsınız?',
                'question_en' => 'What do you do to keep costs under control?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Fire kontrolü', 'Vardiya optimizasyonu', 'Enerji tasarrufu', 'Stok yönetimi'],
                'red_flag_indicators' => ['Maliyet bilinci eksikliği', 'Genel cevaplar'],
            ],
        ]);

        // Mağaza Müdürü (Manager)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_STORE_MANAGER',
            'name_tr' => 'Mağaza Müdürü',
            'name_en' => 'Store Manager',
            'archetype' => 'MANAGER',
            'description_tr' => 'Mağazanın tüm operasyonlarından, ekipten ve finansal performanstan sorumlu pozisyon',
            'description_en' => 'Position responsible for all store operations, team and financial performance',
            'experience_min_years' => 5,
            'experience_max_years' => 8,
            'education_level' => 'bachelor',
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT', 'CUSTOMER_FOCUS', 'ADAPTABILITY'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Mağazanızın yıllık hedeflerini nasıl belirler ve bu hedeflere ulaşmak için ekibinizi nasıl yönlendirirsiniz?',
                'question_en' => 'How do you set annual goals for your store and how do you guide your team to achieve these goals?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Stratejik planlama', 'SMART hedefler', 'Ekip katılımı', 'Performans takibi'],
                'red_flag_indicators' => ['Hedef belirsizliği', 'Tek başına karar', 'Takipsizlik'],
            ],
            [
                'question_tr' => 'Satışların beklenenden düşük olduğu bir dönemde hangi aksiyonları alırsınız?',
                'question_en' => 'What actions do you take during a period when sales are lower than expected?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Analiz', 'Aksiyon planı', 'Hızlı uygulama', 'Sonuç takibi'],
                'red_flag_indicators' => ['Panik', 'Suçlama', 'Pasiflik'],
            ],
            [
                'question_tr' => 'Bir çalışanı işten çıkarmak zorunda kaldığınız bir durumu anlatır mısınız?',
                'question_en' => 'Can you describe a situation where you had to terminate an employee?',
                'competency' => 'LEADERSHIP',
                'type' => 'behavioral',
                'expected_indicators' => ['Adil süreç', 'Dokümantasyon', 'Profesyonellik', 'Empati'],
                'red_flag_indicators' => ['Ani kararlar', 'Prosedür eksikliği', 'Duygusal yaklaşım'],
            ],
            [
                'question_tr' => 'Müşteri deneyimini iyileştirmek için uyguladığınız başarılı bir projeyi anlatın.',
                'question_en' => 'Tell us about a successful project you implemented to improve customer experience.',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'behavioral',
                'expected_indicators' => ['İnovasyon', 'Ölçülebilir sonuçlar', 'Ekip katılımı', 'Sürdürülebilirlik'],
                'red_flag_indicators' => ['Somut örnek eksikliği', 'Başkasının projesi'],
            ],
            [
                'question_tr' => 'Bütçe yönetimi ve karlılık konusundaki yaklaşımınız nedir?',
                'question_en' => 'What is your approach to budget management and profitability?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Finansal okur yazarlık', 'KPI takibi', 'Maliyet kontrolü', 'Kar optimizasyonu'],
                'red_flag_indicators' => ['Finansal bilgi eksikliği', 'Sadece satış odaklılık'],
            ],
            [
                'question_tr' => 'Perakende sektöründeki değişimlere (online satış, teknoloji vb.) nasıl uyum sağlıyorsunuz?',
                'question_en' => 'How do you adapt to changes in the retail sector (online sales, technology, etc.)?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Değişim liderliği', 'Teknoloji kabulü', 'Sürekli öğrenme', 'Proaktif yaklaşım'],
                'red_flag_indicators' => ['Değişime direnç', 'Geleneksel yaklaşım'],
            ],
            [
                'question_tr' => 'Yüksek performanslı bir ekip oluşturmak için neler yaparsınız?',
                'question_en' => 'What do you do to build a high-performing team?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['İşe alım stratejisi', 'Eğitim', 'Motivasyon', 'Kariyer gelişimi'],
                'red_flag_indicators' => ['Ekip geliştirme vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Üst yönetimle iletişiminizi ve raporlama süreçlerinizi nasıl yönetirsiniz?',
                'question_en' => 'How do you manage your communication with upper management and reporting processes?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Düzenli raporlama', 'Proaktif iletişim', 'Veri odaklılık', 'Çözüm önerileri'],
                'red_flag_indicators' => ['Reaktif iletişim', 'Sadece sorun bildirme'],
            ],
        ]);

        // Bölge Müdürü (Leader)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_STORE_REGIONAL_MANAGER',
            'name_tr' => 'Bölge Müdürü',
            'name_en' => 'Regional Manager',
            'archetype' => 'LEADER',
            'description_tr' => 'Birden fazla mağazanın performansından sorumlu, strateji belirleyen ve mağaza müdürlerini yöneten lider pozisyon',
            'description_en' => 'Leader position responsible for performance of multiple stores, setting strategy and managing store managers',
            'experience_min_years' => 8,
            'experience_max_years' => 12,
            'education_level' => 'bachelor',
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'ADAPTABILITY', 'COMMUNICATION', 'TIME_MANAGEMENT'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Farklı performans seviyelerindeki mağazaları nasıl yönetir ve dengelersiniz?',
                'question_en' => 'How do you manage and balance stores at different performance levels?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Veri odaklı analiz', 'Bireysel strateji', 'Kaynak tahsisi', 'Best practice paylaşımı'],
                'red_flag_indicators' => ['Tek tip yaklaşım', 'Zayıf mağazaları görmezden gelme'],
            ],
            [
                'question_tr' => 'Bölgeniz için 3 yıllık büyüme stratejinizi nasıl oluşturursunuz?',
                'question_en' => 'How do you create a 3-year growth strategy for your region?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Pazar analizi', 'Rekabet değerlendirmesi', 'Kaynak planlaması', 'Risk yönetimi'],
                'red_flag_indicators' => ['Kısa vadeli düşünce', 'Veri eksikliği'],
            ],
            [
                'question_tr' => 'Mağaza müdürlerinizin gelişimini nasıl desteklersiniz?',
                'question_en' => 'How do you support the development of your store managers?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Mentorluk', 'Eğitim programları', 'Yetkilendirme', 'Kariyer planlama'],
                'red_flag_indicators' => ['Mikro yönetim', 'Gelişim vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Büyük bir organizasyonel değişikliği ekibinize nasıl iletir ve uygularsınız?',
                'question_en' => 'How do you communicate and implement a major organizational change to your team?',
                'competency' => 'COMMUNICATION',
                'type' => 'situational',
                'expected_indicators' => ['Değişim yönetimi', 'Net iletişim', 'Direnç yönetimi', 'Takip'],
                'red_flag_indicators' => ['Tek yönlü iletişim', 'Geri bildirim almama'],
            ],
            [
                'question_tr' => 'Bütçe kısıtlamaları altında performansı nasıl artırırsınız?',
                'question_en' => 'How do you increase performance under budget constraints?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Yaratıcı çözümler', 'Verimlilik odağı', 'Önceliklendirme', 'ROI analizi'],
                'red_flag_indicators' => ['Sadece kesinti', 'İnovasyon eksikliği'],
            ],
            [
                'question_tr' => 'Sektördeki trendleri nasıl takip eder ve bölgenize nasıl uygularsınız?',
                'question_en' => 'How do you follow industry trends and apply them to your region?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sürekli öğrenme', 'Pilot uygulamalar', 'Hızlı adaptasyon', 'İnovasyon kültürü'],
                'red_flag_indicators' => ['Reaktif yaklaşım', 'Değişime direnç'],
            ],
            [
                'question_tr' => 'Kritik bir pozisyondaki mağaza müdürünüz aniden ayrıldığında nasıl hareket edersiniz?',
                'question_en' => 'How do you act when a store manager in a critical position suddenly leaves?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'situational',
                'expected_indicators' => ['Hızlı çözüm', 'Yedekleme planı', 'Geçici yönetim', 'Uzun vadeli çözüm'],
                'red_flag_indicators' => ['Panik', 'Plansızlık', 'Yavaş hareket'],
            ],
            [
                'question_tr' => 'Üst yönetimle farklı görüşte olduğunuz bir stratejik kararı nasıl ele alırsınız?',
                'question_en' => 'How do you handle a strategic decision where you disagree with upper management?',
                'competency' => 'COMMUNICATION',
                'type' => 'behavioral',
                'expected_indicators' => ['Profesyonellik', 'Veri ile destekleme', 'Yapıcı muhalefet', 'Uyum'],
                'red_flag_indicators' => ['Çatışmacı yaklaşım', 'Pasif kabul'],
            ],
        ]);
    }

    private function seedSalesPositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'RETAIL_SALES')->first();
        if (!$subdomain) return;

        // Satış Danışmanı (Entry)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_SALES_CONSULTANT',
            'name_tr' => 'Satış Danışmanı',
            'name_en' => 'Sales Consultant',
            'archetype' => 'ENTRY',
            'description_tr' => 'Müşterilere ürün ve hizmet satışı yapan, ihtiyaç analizi gerçekleştiren giriş seviye satış pozisyonu',
            'description_en' => 'Entry-level sales position selling products and services to customers and performing needs analysis',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['CUSTOMER_FOCUS', 'COMMUNICATION', 'ADAPTABILITY', 'RELIABILITY'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Bir müşteriye ürün satarken hangi adımları izlersiniz?',
                'question_en' => 'What steps do you follow when selling a product to a customer?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['İhtiyaç analizi', 'Ürün sunumu', 'İtiraz yönetimi', 'Kapanış'],
                'red_flag_indicators' => ['Sistematik yaklaşım eksikliği', 'Sadece ürün anlatma'],
            ],
            [
                'question_tr' => '"Sadece bakıyorum" diyen bir müşteriye nasıl yaklaşırsınız?',
                'question_en' => 'How do you approach a customer who says "I\'m just looking"?',
                'competency' => 'COMMUNICATION',
                'type' => 'situational',
                'expected_indicators' => ['Saygılı mesafe', 'Açık uçlu sorular', 'Değer sunumu', 'Takip'],
                'red_flag_indicators' => ['Israr', 'Görmezden gelme', 'Agresif satış'],
            ],
            [
                'question_tr' => 'Müşteri itirazlarıyla nasıl başa çıkarsınız?',
                'question_en' => 'How do you handle customer objections?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Dinleme', 'Anlayış gösterme', 'Çözüm sunma', 'Alternatifler'],
                'red_flag_indicators' => ['Savunmacılık', 'Tartışma', 'Vazgeçme'],
            ],
            [
                'question_tr' => 'Satış hedeflerinize ulaşamadığınız bir dönemde ne yaptınız?',
                'question_en' => 'What did you do during a period when you couldn\'t reach your sales targets?',
                'competency' => 'ADAPTABILITY',
                'type' => 'behavioral',
                'expected_indicators' => ['Analiz', 'Strateji değişikliği', 'Ekstra çaba', 'Öğrenme'],
                'red_flag_indicators' => ['Bahane üretme', 'Pes etme', 'Başkalarını suçlama'],
            ],
            [
                'question_tr' => 'Çapraz satış (cross-selling) ve üst satış (up-selling) konusundaki deneyiminiz nedir?',
                'question_en' => 'What is your experience with cross-selling and up-selling?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Teknik bilgisi', 'Başarılı örnekler', 'Müşteri odaklı yaklaşım'],
                'red_flag_indicators' => ['Kavram bilgisizliği', 'Agresif satış eğilimi'],
            ],
            [
                'question_tr' => 'Ürün bilginizi nasıl güncel tutarsınız?',
                'question_en' => 'How do you keep your product knowledge up to date?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Proaktif öğrenme', 'Eğitimlere katılım', 'Araştırma', 'Deneyimleme'],
                'red_flag_indicators' => ['Pasif yaklaşım', 'Sadece zorunlu eğitimler'],
            ],
            [
                'question_tr' => 'Bir gününüzü satış açısından değerlendirin - en çok neye zaman ayırırsınız?',
                'question_en' => 'Evaluate your day from a sales perspective - what do you spend most time on?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Müşteri odaklı zaman kullanımı', 'Önceliklendirme', 'Verimlilik'],
                'red_flag_indicators' => ['Müşteriden kaçınma', 'Zaman yönetimi eksikliği'],
            ],
            [
                'question_tr' => 'Satış yaparken etik sınırlarınız nelerdir?',
                'question_en' => 'What are your ethical boundaries when selling?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Dürüstlük', 'Müşteri yararı', 'Şeffaflık', 'Uzun vadeli ilişki'],
                'red_flag_indicators' => ['Her şeyi satma eğilimi', 'Etik farkındalık eksikliği'],
            ],
        ]);

        // Kıdemli Satış Danışmanı (Specialist)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_SALES_SENIOR_CONSULTANT',
            'name_tr' => 'Kıdemli Satış Danışmanı',
            'name_en' => 'Senior Sales Consultant',
            'archetype' => 'SPECIALIST',
            'description_tr' => 'Yüksek değerli satışlar gerçekleştiren, VIP müşterilerle ilgilenen deneyimli satış uzmanı',
            'description_en' => 'Experienced sales specialist handling high-value sales and VIP customers',
            'experience_min_years' => 2,
            'experience_max_years' => 5,
            'education_level' => 'high_school',
            'competencies' => ['CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING', 'LEADERSHIP'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'VIP müşteri ilişkilerini nasıl yönetirsiniz?',
                'question_en' => 'How do you manage VIP customer relationships?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Kişiselleştirilmiş hizmet', 'Proaktif iletişim', 'Özel teklifler', 'Uzun vadeli ilişki'],
                'red_flag_indicators' => ['Standart yaklaşım', 'Reaktif hizmet'],
            ],
            [
                'question_tr' => 'Karmaşık bir satış sürecini başından sonuna anlatır mısınız?',
                'question_en' => 'Can you describe a complex sales process from start to finish?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'behavioral',
                'expected_indicators' => ['Sistematik yaklaşım', 'Paydaş yönetimi', 'İtiraz yönetimi', 'Kapanış stratejisi'],
                'red_flag_indicators' => ['Basit satışlar', 'Karmaşıklık yönetimi eksikliği'],
            ],
            [
                'question_tr' => 'Yeni satış danışmanlarına nasıl koçluk yaparsınız?',
                'question_en' => 'How do you coach new sales consultants?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Gözlem', 'Geri bildirim', 'Role play', 'Takip'],
                'red_flag_indicators' => ['Koçluk deneyimi eksikliği', 'Sabırsızlık'],
            ],
            [
                'question_tr' => 'Rakip ürünleri tercih eden bir müşteriyi nasıl ikna edersiniz?',
                'question_en' => 'How do you convince a customer who prefers competitor products?',
                'competency' => 'COMMUNICATION',
                'type' => 'situational',
                'expected_indicators' => ['Dinleme', 'Farklılaştırma', 'Değer sunumu', 'Saygı'],
                'red_flag_indicators' => ['Rakibi kötüleme', 'Agresif ikna'],
            ],
            [
                'question_tr' => 'Satış performansınızı nasıl analiz eder ve geliştirirsiniz?',
                'question_en' => 'How do you analyze and improve your sales performance?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Veri analizi', 'KPI takibi', 'Sürekli gelişim', 'Öz değerlendirme'],
                'red_flag_indicators' => ['Analiz yapmama', 'Reaktif yaklaşım'],
            ],
            [
                'question_tr' => 'Stresli satış dönemlerinde performansınızı nasıl korursunuz?',
                'question_en' => 'How do you maintain your performance during stressful sales periods?',
                'competency' => 'ADAPTABILITY',
                'type' => 'behavioral',
                'expected_indicators' => ['Stres yönetimi', 'Odaklanma', 'Enerji yönetimi', 'Pozitiflik'],
                'red_flag_indicators' => ['Stres altında çökme', 'Performans düşüşü'],
            ],
            [
                'question_tr' => 'Müşteri sadakatini artırmak için neler yaparsınız?',
                'question_en' => 'What do you do to increase customer loyalty?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Takip', 'Kişiselleştirme', 'Değer ekleme', 'İlişki kurma'],
                'red_flag_indicators' => ['Tek seferlik satış odağı', 'Takipsizlik'],
            ],
            [
                'question_tr' => 'En zorlu satış deneyiminizi ve sonucunu anlatın.',
                'question_en' => 'Describe your most challenging sales experience and its outcome.',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'behavioral',
                'expected_indicators' => ['Karmaşıklık', 'Çözüm stratejisi', 'Azim', 'Öğrenme'],
                'red_flag_indicators' => ['Basit örnekler', 'Başarısızlık kabullenememe'],
            ],
        ]);

        // Satış Ekip Lideri (Coordinator)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_SALES_TEAM_LEAD',
            'name_tr' => 'Satış Ekip Lideri',
            'name_en' => 'Sales Team Lead',
            'archetype' => 'COORDINATOR',
            'description_tr' => 'Satış ekibini koordine eden, hedefleri takip eden ve performansı yöneten pozisyon',
            'description_en' => 'Position coordinating the sales team, tracking goals and managing performance',
            'experience_min_years' => 3,
            'experience_max_years' => 6,
            'education_level' => 'associate',
            'competencies' => ['LEADERSHIP', 'COMMUNICATION', 'CUSTOMER_FOCUS', 'TIME_MANAGEMENT'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Satış ekibinizin günlük performansını nasıl takip eder ve yönetirsiniz?',
                'question_en' => 'How do you track and manage your sales team\'s daily performance?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['KPI takibi', 'Günlük check-in', 'Koçluk', 'Hızlı müdahale'],
                'red_flag_indicators' => ['Takipsizlik', 'Reaktif yönetim'],
            ],
            [
                'question_tr' => 'Satış hedeflerini ekibinize nasıl dağıtır ve motive edersiniz?',
                'question_en' => 'How do you distribute sales targets to your team and motivate them?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Adil dağılım', 'Bireysel hedefler', 'Motivasyon teknikleri', 'Takdir'],
                'red_flag_indicators' => ['Eşit dağılım', 'Motivasyon eksikliği'],
            ],
            [
                'question_tr' => 'Düşük performanslı bir satış danışmanıyla nasıl çalışırsınız?',
                'question_en' => 'How do you work with an underperforming sales consultant?',
                'competency' => 'LEADERSHIP',
                'type' => 'situational',
                'expected_indicators' => ['Neden analizi', 'Koçluk planı', 'Takip', 'Destek'],
                'red_flag_indicators' => ['Hemen ceza', 'Görmezden gelme', 'Kişisel yaklaşım'],
            ],
            [
                'question_tr' => 'Ekip içinde sağlıklı rekabeti nasıl teşvik edersiniz?',
                'question_en' => 'How do you encourage healthy competition within the team?',
                'competency' => 'TEAMWORK',
                'type' => 'experience',
                'expected_indicators' => ['Takım ruhu', 'Bireysel takdir', 'Yapıcı rekabet', 'Paylaşım kültürü'],
                'red_flag_indicators' => ['Yıkıcı rekabet', 'Ayrımcılık'],
            ],
            [
                'question_tr' => 'Satış verilerini analiz ederek hangi aksiyonları alırsınız?',
                'question_en' => 'What actions do you take by analyzing sales data?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Veri okuryazarlığı', 'Trend analizi', 'Aksiyon planlama', 'Sonuç takibi'],
                'red_flag_indicators' => ['Veri kullanmama', 'İçgüdüsel kararlar'],
            ],
            [
                'question_tr' => 'Yoğun kampanya dönemlerinde ekibi nasıl organize edersiniz?',
                'question_en' => 'How do you organize the team during busy campaign periods?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'situational',
                'expected_indicators' => ['Planlama', 'Vardiya yönetimi', 'Kaynak tahsisi', 'Esneklik'],
                'red_flag_indicators' => ['Plansızlık', 'Stres yansıtma'],
            ],
            [
                'question_tr' => 'Müşteri şikayetlerinin satış ekibine etkisini nasıl yönetirsiniz?',
                'question_en' => 'How do you manage the impact of customer complaints on the sales team?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Öğrenme fırsatı', 'Destek', 'Süreç iyileştirme', 'Moral yönetimi'],
                'red_flag_indicators' => ['Suçlama', 'Görmezden gelme'],
            ],
            [
                'question_tr' => 'Satış ekibinizin eğitim ihtiyaçlarını nasıl belirler ve karşılarsınız?',
                'question_en' => 'How do you identify and address your sales team\'s training needs?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['İhtiyaç analizi', 'Bireysel gelişim', 'Eğitim planlaması', 'Takip'],
                'red_flag_indicators' => ['Eğitim vizyonu eksikliği', 'Standart eğitimler'],
            ],
        ]);
    }

    private function seedCashierPositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'RETAIL_CASHIER')->first();
        if (!$subdomain) return;

        // Kasiyer (Entry)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_CASHIER',
            'name_tr' => 'Kasiyer',
            'name_en' => 'Cashier',
            'archetype' => 'ENTRY',
            'description_tr' => 'Kasa işlemlerini gerçekleştiren, ödeme alan ve müşteri hizmeti sunan giriş seviye pozisyon',
            'description_en' => 'Entry-level position performing cash register operations, receiving payments and providing customer service',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['ATTENTION_TO_DETAIL', 'CUSTOMER_FOCUS', 'RELIABILITY', 'COMMUNICATION'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Kasa işlemlerinde dikkat etmeniz gereken en önemli noktalar nelerdir?',
                'question_en' => 'What are the most important points to pay attention to in cash register operations?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Para sayma', 'Fiyat kontrolü', 'Ürün doğrulama', 'Güvenlik'],
                'red_flag_indicators' => ['Dikkat eksikliği', 'Yüzeysel cevap'],
            ],
            [
                'question_tr' => 'Kasa farkı çıktığında nasıl hareket edersiniz?',
                'question_en' => 'How do you act when there is a cash difference?',
                'competency' => 'RELIABILITY',
                'type' => 'situational',
                'expected_indicators' => ['Dürüstlük', 'Raporlama', 'Neden araştırma', 'Prosedüre uyum'],
                'red_flag_indicators' => ['Gizleme eğilimi', 'Panik', 'Sorumluluk almama'],
            ],
            [
                'question_tr' => 'Uzun kuyruklar oluştuğunda müşterileri nasıl yönetirsiniz?',
                'question_en' => 'How do you manage customers when long queues form?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Hızlı işlem', 'İletişim', 'Destek isteme', 'Sakinlik'],
                'red_flag_indicators' => ['Stres', 'Müşteriyle tartışma', 'Yavaşlama'],
            ],
            [
                'question_tr' => 'Ödeme yöntemleri konusunda müşterilere nasıl yardımcı olursunuz?',
                'question_en' => 'How do you help customers with payment methods?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Bilgi aktarımı', 'Sabır', 'Teknik yardım', 'Alternatif sunma'],
                'red_flag_indicators' => ['Sabırsızlık', 'Bilgi eksikliği'],
            ],
            [
                'question_tr' => 'Şüpheli bir ödeme işlemiyle karşılaştığınızda ne yaparsınız?',
                'question_en' => 'What do you do when you encounter a suspicious payment transaction?',
                'competency' => 'RELIABILITY',
                'type' => 'situational',
                'expected_indicators' => ['Prosedür bilgisi', 'Yönetici bilgilendirme', 'Sakinlik', 'Güvenlik'],
                'red_flag_indicators' => ['Görmezden gelme', 'Panik', 'Yanlış müdahale'],
            ],
            [
                'question_tr' => 'Müşteri iade veya değişim istediğinde hangi adımları izlersiniz?',
                'question_en' => 'What steps do you follow when a customer requests a return or exchange?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Prosedür bilgisi', 'Belge kontrolü', 'Sistem kullanımı', 'Müşteri memnuniyeti'],
                'red_flag_indicators' => ['Prosedür bilmeme', 'Müşteriyi reddetme'],
            ],
            [
                'question_tr' => 'Vardiya sonunda kasa kapanış işlemlerini nasıl yaparsınız?',
                'question_en' => 'How do you perform cash closing operations at the end of your shift?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Sistematik yaklaşım', 'Para sayma', 'Raporlama', 'Kontrol'],
                'red_flag_indicators' => ['Acelecilik', 'Detay eksikliği'],
            ],
            [
                'question_tr' => 'Zor veya sinirli bir müşteriyle kasada nasıl başa çıkarsınız?',
                'question_en' => 'How do you handle a difficult or angry customer at the cash register?',
                'competency' => 'COMMUNICATION',
                'type' => 'behavioral',
                'expected_indicators' => ['Sakinlik', 'Empati', 'Çözüm odaklılık', 'Profesyonellik'],
                'red_flag_indicators' => ['Tartışma', 'Savunmacılık', 'Duygusal tepki'],
            ],
        ]);

        // Kıdemli Kasiyer (Specialist)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_CASHIER_SENIOR',
            'name_tr' => 'Kıdemli Kasiyer',
            'name_en' => 'Senior Cashier',
            'archetype' => 'SPECIALIST',
            'description_tr' => 'Karmaşık işlemleri yöneten, yeni kasiyerlere eğitim veren deneyimli kasiyer',
            'description_en' => 'Experienced cashier managing complex transactions and training new cashiers',
            'experience_min_years' => 2,
            'experience_max_years' => 4,
            'education_level' => 'high_school',
            'competencies' => ['ATTENTION_TO_DETAIL', 'LEADERSHIP', 'PROBLEM_SOLVING', 'CUSTOMER_FOCUS'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Yeni kasiyerlere eğitim verirken hangi konulara öncelik verirsiniz?',
                'question_en' => 'What topics do you prioritize when training new cashiers?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Temel işlemler', 'Müşteri hizmeti', 'Güvenlik', 'Pratik uygulama'],
                'red_flag_indicators' => ['Sistematik olmayan eğitim', 'Sabırsızlık'],
            ],
            [
                'question_tr' => 'Karmaşık bir iade veya iptal işlemini nasıl yönetirsiniz?',
                'question_en' => 'How do you manage a complex return or cancellation transaction?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Prosedür bilgisi', 'Sistem kullanımı', 'Yönetici koordinasyonu', 'Müşteri memnuniyeti'],
                'red_flag_indicators' => ['Belirsizlik', 'Prosedür bilgisizliği'],
            ],
            [
                'question_tr' => 'Kasa operasyonlarında sık yapılan hataları önlemek için neler yaparsınız?',
                'question_en' => 'What do you do to prevent common mistakes in cash operations?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Kontrol listeleri', 'Eğitim', 'Geri bildirim', 'Süreç iyileştirme'],
                'red_flag_indicators' => ['Reaktif yaklaşım', 'Önlem almama'],
            ],
            [
                'question_tr' => 'Yoğun saatlerde kasa operasyonlarını nasıl optimize edersiniz?',
                'question_en' => 'How do you optimize cash operations during busy hours?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Hız', 'Organizasyon', 'Ekip koordinasyonu', 'Verimlilik'],
                'red_flag_indicators' => ['Stres altında bozulma', 'Organizasyon eksikliği'],
            ],
            [
                'question_tr' => 'Kasa güvenliği konusunda hangi önlemleri alırsınız?',
                'question_en' => 'What measures do you take for cash security?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Prosedür bilgisi', 'Dikkat', 'Raporlama', 'Önleyici tedbirler'],
                'red_flag_indicators' => ['Güvenlik bilinci eksikliği', 'Gevşeklik'],
            ],
            [
                'question_tr' => 'Müşteri şikayetlerini kasada nasıl çözersiniz?',
                'question_en' => 'How do you resolve customer complaints at the cash register?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'behavioral',
                'expected_indicators' => ['Dinleme', 'Empati', 'Hızlı çözüm', 'Takip'],
                'red_flag_indicators' => ['Savunmacılık', 'Çözümsüzlük'],
            ],
            [
                'question_tr' => 'Kasa sisteminde teknik bir sorun yaşandığında ne yaparsınız?',
                'question_en' => 'What do you do when there is a technical problem with the cash system?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Soğukkanlılık', 'Alternatif çözümler', 'Teknik destek', 'Müşteri yönetimi'],
                'red_flag_indicators' => ['Panik', 'Çözüm üretememe'],
            ],
            [
                'question_tr' => 'Kasiyerler arasında tutarlılığı nasıl sağlarsınız?',
                'question_en' => 'How do you ensure consistency among cashiers?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Standartlar', 'Eğitim', 'Gözlem', 'Geri bildirim'],
                'red_flag_indicators' => ['Standart eksikliği', 'Takipsizlik'],
            ],
        ]);
    }

    private function seedVisualMerchandisingPositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'RETAIL_VISUAL')->first();
        if (!$subdomain) return;

        // Görsel Mağazacılık Elemanı (Entry)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_VISUAL_ASSOCIATE',
            'name_tr' => 'Görsel Mağazacılık Elemanı',
            'name_en' => 'Visual Merchandising Associate',
            'archetype' => 'ENTRY',
            'description_tr' => 'Mağaza içi ürün sergileme, vitrin düzenleme ve görsel standartları uygulayan pozisyon',
            'description_en' => 'Position implementing in-store product display, window dressing and visual standards',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['ATTENTION_TO_DETAIL', 'ADAPTABILITY', 'TEAMWORK', 'LEARNING_AGILITY'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Görsel mağazacılık sizin için ne anlama geliyor?',
                'question_en' => 'What does visual merchandising mean to you?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Kavram bilgisi', 'Tutkulu yaklaşım', 'Detay bilinci', 'Müşteri deneyimi'],
                'red_flag_indicators' => ['Yüzeysel bilgi', 'İlgisizlik'],
            ],
            [
                'question_tr' => 'Bir vitrin düzenlemesinde nelere dikkat edersiniz?',
                'question_en' => 'What do you pay attention to when arranging a display window?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Renk uyumu', 'Tema', 'Işıklandırma', 'Ürün yerleşimi', 'Dikkat çekicilik'],
                'red_flag_indicators' => ['Teknik bilgi eksikliği', 'Rastgele yerleştirme'],
            ],
            [
                'question_tr' => 'Marka standartlarına uygun çalışmak sizin için ne demek?',
                'question_en' => 'What does it mean to you to work according to brand standards?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Standart bilinci', 'Tutarlılık', 'Detay takibi', 'Marka değerleri'],
                'red_flag_indicators' => ['Standart önemsememe', 'Bireysel tercihler'],
            ],
            [
                'question_tr' => 'Yeni bir kampanya için mağazayı hazırlarken ekiple nasıl çalışırsınız?',
                'question_en' => 'How do you work with the team when preparing the store for a new campaign?',
                'competency' => 'TEAMWORK',
                'type' => 'situational',
                'expected_indicators' => ['İşbirliği', 'İletişim', 'Planlama', 'Esneklik'],
                'red_flag_indicators' => ['Bireysel çalışma tercihi', 'İletişimsizlik'],
            ],
            [
                'question_tr' => 'Görsel düzenleme yaparken yaratıcılığınızı nasıl kullanırsınız?',
                'question_en' => 'How do you use your creativity when doing visual arrangements?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Yaratıcı örnekler', 'Trend takibi', 'Deneysellik', 'Standart içinde yenilik'],
                'red_flag_indicators' => ['Yaratıcılık eksikliği', 'Sadece talimat takibi'],
            ],
            [
                'question_tr' => 'Ürün sergilemede yapılan yaygın hatalar nelerdir ve nasıl önlersiniz?',
                'question_en' => 'What are common mistakes in product display and how do you prevent them?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Hata farkındalığı', 'Önleyici tedbirler', 'Kalite kontrolü'],
                'red_flag_indicators' => ['Hata bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Sınırlı zaman ve kaynaklarla en iyi sonucu nasıl alırsınız?',
                'question_en' => 'How do you achieve the best results with limited time and resources?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Önceliklendirme', 'Verimlilik', 'Yaratıcı çözümler', 'Pratiklik'],
                'red_flag_indicators' => ['Kaynak şikayeti', 'Çözüm üretememe'],
            ],
            [
                'question_tr' => 'Görsel mağazacılık trendlerini nasıl takip edersiniz?',
                'question_en' => 'How do you follow visual merchandising trends?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sosyal medya', 'Rakip analizi', 'Endüstri kaynakları', 'Mağaza ziyaretleri'],
                'red_flag_indicators' => ['Trend takipsizliği', 'Statik yaklaşım'],
            ],
        ]);

        // Görsel Mağazacılık Uzmanı (Specialist)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_VISUAL_SPECIALIST',
            'name_tr' => 'Görsel Mağazacılık Uzmanı',
            'name_en' => 'Visual Merchandising Specialist',
            'archetype' => 'SPECIALIST',
            'description_tr' => 'Mağaza görsel stratejisini uygulayan, ekibi yönlendiren ve standartları belirleyen uzman',
            'description_en' => 'Specialist implementing store visual strategy, guiding the team and setting standards',
            'experience_min_years' => 2,
            'experience_max_years' => 5,
            'education_level' => 'associate',
            'competencies' => ['ATTENTION_TO_DETAIL', 'LEADERSHIP', 'PROBLEM_SOLVING', 'COMMUNICATION'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Bir mağazanın görsel stratejisini nasıl oluşturursunuz?',
                'question_en' => 'How do you create the visual strategy for a store?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Marka analizi', 'Hedef kitle', 'Mekansal planlama', 'Sezonsal adaptasyon'],
                'red_flag_indicators' => ['Stratejik düşünce eksikliği', 'Reaktif yaklaşım'],
            ],
            [
                'question_tr' => 'Görsel standartları ekibe nasıl aktarır ve uygulatırsınız?',
                'question_en' => 'How do you communicate visual standards to the team and ensure implementation?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Eğitim', 'Görsel rehberler', 'Takip', 'Geri bildirim'],
                'red_flag_indicators' => ['İletişim eksikliği', 'Takipsizlik'],
            ],
            [
                'question_tr' => 'Satış performansını artırmak için görsel mağazacılığı nasıl kullanırsınız?',
                'question_en' => 'How do you use visual merchandising to increase sales performance?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Veri analizi', 'Müşteri davranışı', 'Stratejik yerleşim', 'A/B testleri'],
                'red_flag_indicators' => ['Sadece estetik odak', 'Satış bağlantısı kuramama'],
            ],
            [
                'question_tr' => 'Birden fazla mağazada tutarlı görsel standartları nasıl sağlarsınız?',
                'question_en' => 'How do you ensure consistent visual standards across multiple stores?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Standart dökümanlar', 'Eğitim programları', 'Denetim', 'İletişim'],
                'red_flag_indicators' => ['Tutarsızlık toleransı', 'Kontrol eksikliği'],
            ],
            [
                'question_tr' => 'Bütçe kısıtlaması altında etkileyici bir görsel sunum nasıl yaparsınız?',
                'question_en' => 'How do you create an impressive visual presentation under budget constraints?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Yaratıcılık', 'Maliyet bilinci', 'Kaynak optimizasyonu', 'Önceliklendirme'],
                'red_flag_indicators' => ['Sadece bütçe şikayeti', 'Yaratıcılık eksikliği'],
            ],
            [
                'question_tr' => 'Görsel mağazacılığın ROI\'sini nasıl ölçersiniz?',
                'question_en' => 'How do you measure the ROI of visual merchandising?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['KPI belirleme', 'Satış korelasyonu', 'Müşteri geri bildirimi', 'Karşılaştırma'],
                'red_flag_indicators' => ['Ölçüm yapmama', 'Sadece sezgisel değerlendirme'],
            ],
            [
                'question_tr' => 'Marka yönetimiyle görsel uygulamalar konusunda nasıl iletişim kurarsınız?',
                'question_en' => 'How do you communicate with brand management about visual applications?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Proaktif iletişim', 'Raporlama', 'Öneri sunma', 'Geri bildirim alma'],
                'red_flag_indicators' => ['Tek yönlü iletişim', 'İletişimsizlik'],
            ],
            [
                'question_tr' => 'Yeni açılan bir mağazanın görsel kurulumunu baştan sona nasıl yönetirsiniz?',
                'question_en' => 'How do you manage the visual setup of a newly opened store from start to finish?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Proje yönetimi', 'Zaman planlaması', 'Ekip koordinasyonu', 'Kalite kontrolü'],
                'red_flag_indicators' => ['Planlama eksikliği', 'Deadline kaçırma'],
            ],
        ]);
    }

    private function seedWarehousePositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'RETAIL_WAREHOUSE')->first();
        if (!$subdomain) return;

        // Depo Elemanı (Entry)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_WAREHOUSE_ASSOCIATE',
            'name_tr' => 'Depo Elemanı',
            'name_en' => 'Warehouse Associate',
            'archetype' => 'ENTRY',
            'description_tr' => 'Mal kabul, stok yerleştirme ve depo düzeni sağlayan giriş seviye pozisyon',
            'description_en' => 'Entry-level position handling goods receiving, stock placement and warehouse organization',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['RELIABILITY', 'ATTENTION_TO_DETAIL', 'TEAMWORK', 'ADAPTABILITY'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Mal kabul işlemlerinde nelere dikkat edersiniz?',
                'question_en' => 'What do you pay attention to during goods receiving?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Miktar kontrolü', 'Hasar kontrolü', 'Belge eşleştirme', 'Sistem kaydı'],
                'red_flag_indicators' => ['Kontrolsüz kabul', 'Detay eksikliği'],
            ],
            [
                'question_tr' => 'Depo düzenini nasıl korursunuz?',
                'question_en' => 'How do you maintain warehouse organization?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Yerleşim sistemi', 'Etiketleme', 'FIFO', 'Düzenli kontrol'],
                'red_flag_indicators' => ['Düzensizlik toleransı', 'Sistem bilmeme'],
            ],
            [
                'question_tr' => 'Stok sayımı yaparken hangi adımları izlersiniz?',
                'question_en' => 'What steps do you follow when doing stock counting?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Sistematik sayım', 'Çift kontrol', 'Fark raporlama', 'Doğruluk'],
                'red_flag_indicators' => ['Özensizlik', 'Hızlı geçiştirme'],
            ],
            [
                'question_tr' => 'Fiziksel olarak zorlu bir iş ortamında nasıl çalışırsınız?',
                'question_en' => 'How do you work in a physically demanding work environment?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Fiziksel dayanıklılık', 'İş güvenliği', 'Enerji yönetimi', 'Pozitif tutum'],
                'red_flag_indicators' => ['Fiziksel iş reddi', 'Şikayet'],
            ],
            [
                'question_tr' => 'Yoğun sevkiyat dönemlerinde ekiple nasıl çalışırsınız?',
                'question_en' => 'How do you work with the team during busy shipment periods?',
                'competency' => 'TEAMWORK',
                'type' => 'situational',
                'expected_indicators' => ['İşbirliği', 'Hız', 'İletişim', 'Esneklik'],
                'red_flag_indicators' => ['Bireysel çalışma', 'Stres altında çökme'],
            ],
            [
                'question_tr' => 'Hasarlı veya eksik mal tespit ettiğinizde ne yaparsınız?',
                'question_en' => 'What do you do when you detect damaged or missing goods?',
                'competency' => 'RELIABILITY',
                'type' => 'situational',
                'expected_indicators' => ['Raporlama', 'Belgeleme', 'Yönetici bilgilendirme', 'Prosedür takibi'],
                'red_flag_indicators' => ['Görmezden gelme', 'Raporlama yapmama'],
            ],
            [
                'question_tr' => 'Depo güvenliği konusunda nelere dikkat edersiniz?',
                'question_en' => 'What do you pay attention to regarding warehouse safety?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['İş güvenliği kuralları', 'Ekipman kullanımı', 'Tehlike farkındalığı', 'Raporlama'],
                'red_flag_indicators' => ['Güvenlik bilinci eksikliği', 'Kural ihlali'],
            ],
            [
                'question_tr' => 'Depo yönetim sistemleri (WMS) hakkında bilginiz var mı?',
                'question_en' => 'Do you have knowledge about Warehouse Management Systems (WMS)?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sistem bilgisi', 'Öğrenme isteği', 'Teknoloji kabulü'],
                'red_flag_indicators' => ['Teknoloji reddi', 'Öğrenme isteksizliği'],
            ],
        ]);

        // Depo Sorumlusu (Coordinator)
        $position = $this->createPosition($subdomain, [
            'code' => 'RETAIL_WAREHOUSE_SUPERVISOR',
            'name_tr' => 'Depo Sorumlusu',
            'name_en' => 'Warehouse Supervisor',
            'archetype' => 'COORDINATOR',
            'description_tr' => 'Depo operasyonlarını yöneten, ekibi koordine eden ve stok yönetiminden sorumlu pozisyon',
            'description_en' => 'Position managing warehouse operations, coordinating the team and responsible for inventory management',
            'experience_min_years' => 3,
            'experience_max_years' => 5,
            'education_level' => 'high_school',
            'competencies' => ['LEADERSHIP', 'TIME_MANAGEMENT', 'ATTENTION_TO_DETAIL', 'PROBLEM_SOLVING'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Depo operasyonlarını nasıl planlayıp organize edersiniz?',
                'question_en' => 'How do you plan and organize warehouse operations?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Günlük planlama', 'Kaynak tahsisi', 'Önceliklendirme', 'Verimlilik'],
                'red_flag_indicators' => ['Plansızlık', 'Reaktif yönetim'],
            ],
            [
                'question_tr' => 'Stok doğruluğunu nasıl sağlarsınız?',
                'question_en' => 'How do you ensure stock accuracy?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Döngüsel sayım', 'Sistem kontrolü', 'Fark analizi', 'Süreç iyileştirme'],
                'red_flag_indicators' => ['Stok farklarını kabul', 'Kontrol eksikliği'],
            ],
            [
                'question_tr' => 'Depo ekibini nasıl yönetir ve motive edersiniz?',
                'question_en' => 'How do you manage and motivate the warehouse team?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Görev dağılımı', 'Performans takibi', 'Geri bildirim', 'Takdir'],
                'red_flag_indicators' => ['Otoriter yaklaşım', 'Motivasyon eksikliği'],
            ],
            [
                'question_tr' => 'Yoğun sezonda depo kapasitesini nasıl yönetirsiniz?',
                'question_en' => 'How do you manage warehouse capacity during busy season?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Önceden planlama', 'Alan optimizasyonu', 'Ekstra kaynak', 'Esneklik'],
                'red_flag_indicators' => ['Reaktif yaklaşım', 'Kapasite sorunları'],
            ],
            [
                'question_tr' => 'Fire ve kayıpları nasıl minimize edersiniz?',
                'question_en' => 'How do you minimize waste and losses?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Neden analizi', 'Önleyici tedbirler', 'Takip', 'Raporlama'],
                'red_flag_indicators' => ['Fire toleransı', 'Önlem almama'],
            ],
            [
                'question_tr' => 'Tedarik zinciri sorunlarını nasıl çözersiniz?',
                'question_en' => 'How do you solve supply chain issues?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Hızlı müdahale', 'Alternatif çözümler', 'İletişim', 'Takip'],
                'red_flag_indicators' => ['Pasiflik', 'Çözüm üretememe'],
            ],
            [
                'question_tr' => 'Depo güvenliği ve iş sağlığı standartlarını nasıl uygularsınız?',
                'question_en' => 'How do you implement warehouse safety and occupational health standards?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Eğitim', 'Denetim', 'Raporlama', 'Sürekli iyileştirme'],
                'red_flag_indicators' => ['Güvenlik ihmal', 'Standart bilmeme'],
            ],
            [
                'question_tr' => 'Mağaza ile depo koordinasyonunu nasıl sağlarsınız?',
                'question_en' => 'How do you ensure coordination between the store and warehouse?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Düzenli iletişim', 'Talep yönetimi', 'Proaktif bilgilendirme', 'İşbirliği'],
                'red_flag_indicators' => ['İletişimsizlik', 'Reaktif yaklaşım'],
            ],
        ]);
    }

    private function createPosition(JobSubdomain $subdomain, array $data): JobPosition
    {
        $competencyCodes = $data['competencies'] ?? [];
        unset($data['competencies']);
        $data['archetype'] = $data['archetype'] ?? null;

        $position = JobPosition::updateOrCreate(
            ['code' => $data['code']],
            [
                'subdomain_id' => $subdomain->id,
                'archetype_id' => $data['archetype'] ? ($this->archetypes[$data['archetype']] ?? null) : null,
                'code' => $data['code'],
                'name_tr' => $data['name_tr'],
                'name_en' => $data['name_en'],
                'description_tr' => $data['description_tr'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'experience_min_years' => $data['experience_min_years'] ?? 0,
                'experience_max_years' => $data['experience_max_years'] ?? null,
                'education_level' => $data['education_level'] ?? null,
                'is_active' => true,
            ]
        );

        // Attach competencies
        $competencyData = [];
        foreach ($competencyCodes as $index => $code) {
            if (isset($this->competencies[$code])) {
                $competencyData[$this->competencies[$code]] = [
                    'weight' => 10 - $index, // First competency has highest weight
                    'is_critical' => $index === 0, // First competency is critical
                    'min_score' => 3,
                    'sort_order' => $index + 1,
                ];
            }
        }

        if (!empty($competencyData)) {
            $position->competencies()->sync($competencyData);
        }

        return $position;
    }

    private function addQuestions(JobPosition $position, array $questions): void
    {
        foreach ($questions as $index => $q) {
            PositionQuestion::updateOrCreate(
                [
                    'position_id' => $position->id,
                    'question_tr' => $q['question_tr'],
                ],
                [
                    'position_id' => $position->id,
                    'competency_id' => isset($q['competency']) && isset($this->competencies[$q['competency']])
                        ? $this->competencies[$q['competency']]
                        : null,
                    'question_type' => $q['type'] ?? 'behavioral',
                    'question_tr' => $q['question_tr'],
                    'question_en' => $q['question_en'],
                    'expected_indicators' => $q['expected_indicators'] ?? null,
                    'red_flag_indicators' => $q['red_flag_indicators'] ?? null,
                    'difficulty_level' => $q['difficulty'] ?? 2,
                    'time_limit_seconds' => $q['time_limit'] ?? 120,
                    'sort_order' => $index + 1,
                    'is_mandatory' => $index < 3, // First 3 questions are mandatory
                    'is_active' => true,
                ]
            );
        }
    }
}
