<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\ExpectationQuestion;
use App\Models\JobDomain;
use App\Models\JobSubdomain;
use App\Models\RoleArchetype;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoleArchetypes();
        $this->seedUniversalCompetencies();
        $this->seedJobDomains();
        $this->seedExpectationQuestions();
    }

    private function seedRoleArchetypes(): void
    {
        $archetypes = [
            [
                'code' => 'ENTRY',
                'name_tr' => 'Giriş Seviye',
                'name_en' => 'Entry Level',
                'description_tr' => 'Deneyim gerektirmeyen, eğitim ve gelişim odaklı pozisyonlar',
                'description_en' => 'No experience required, training and development focused positions',
                'level' => 1,
                'typical_competencies' => ['COMMUNICATION', 'LEARNING_AGILITY', 'TEAMWORK', 'RELIABILITY'],
                'interview_focus' => ['Motivasyon', 'Öğrenme kapasitesi', 'Temel iletişim', 'Uyum'],
            ],
            [
                'code' => 'SPECIALIST',
                'name_tr' => 'Uzman',
                'name_en' => 'Specialist',
                'description_tr' => 'Belirli bir alanda teknik uzmanlık gerektiren pozisyonlar',
                'description_en' => 'Positions requiring technical expertise in a specific area',
                'level' => 2,
                'typical_competencies' => ['TECHNICAL_EXPERTISE', 'PROBLEM_SOLVING', 'ATTENTION_TO_DETAIL', 'SELF_MANAGEMENT'],
                'interview_focus' => ['Teknik bilgi', 'Problem çözme', 'Deneyim derinliği'],
            ],
            [
                'code' => 'COORDINATOR',
                'name_tr' => 'Koordinatör',
                'name_en' => 'Coordinator',
                'description_tr' => 'Süreçleri ve ekipleri koordine eden, liderlik geliştirme aşamasındaki pozisyonlar',
                'description_en' => 'Positions coordinating processes and teams, in leadership development phase',
                'level' => 3,
                'typical_competencies' => ['COORDINATION', 'COMMUNICATION', 'ORGANIZATION', 'STAKEHOLDER_MANAGEMENT'],
                'interview_focus' => ['Koordinasyon', 'Organizasyon', 'Paydaş yönetimi'],
            ],
            [
                'code' => 'MANAGER',
                'name_tr' => 'Yönetici',
                'name_en' => 'Manager',
                'description_tr' => 'Ekip ve süreç yönetimi sorumluluğu olan pozisyonlar',
                'description_en' => 'Positions with team and process management responsibility',
                'level' => 4,
                'typical_competencies' => ['LEADERSHIP', 'PEOPLE_MANAGEMENT', 'DECISION_MAKING', 'PERFORMANCE_MANAGEMENT'],
                'interview_focus' => ['Liderlik', 'Ekip yönetimi', 'Karar verme', 'Performans yönetimi'],
            ],
            [
                'code' => 'LEADER',
                'name_tr' => 'Lider',
                'name_en' => 'Leader',
                'description_tr' => 'Birden fazla ekip veya departmanı yöneten üst düzey pozisyonlar',
                'description_en' => 'Senior positions managing multiple teams or departments',
                'level' => 5,
                'typical_competencies' => ['STRATEGIC_THINKING', 'CHANGE_MANAGEMENT', 'BUSINESS_ACUMEN', 'INFLUENCE'],
                'interview_focus' => ['Strateji', 'Değişim yönetimi', 'İş anlayışı', 'Etki'],
            ],
            [
                'code' => 'EXECUTIVE',
                'name_tr' => 'Üst Düzey Yönetici',
                'name_en' => 'Executive',
                'description_tr' => 'C-level ve organizasyon genelinde sorumluluk taşıyan pozisyonlar',
                'description_en' => 'C-level and organization-wide responsibility positions',
                'level' => 6,
                'typical_competencies' => ['VISION', 'STRATEGIC_LEADERSHIP', 'TRANSFORMATION', 'STAKEHOLDER_RELATIONS'],
                'interview_focus' => ['Vizyon', 'Stratejik liderlik', 'Dönüşüm', 'Üst düzey ilişkiler'],
            ],
        ];

        foreach ($archetypes as $index => $archetype) {
            RoleArchetype::updateOrCreate(
                ['code' => $archetype['code']],
                array_merge($archetype, ['sort_order' => $index + 1])
            );
        }
    }

    private function seedUniversalCompetencies(): void
    {
        $competencies = [
            // Soft Skills
            [
                'code' => 'COMMUNICATION',
                'name_tr' => 'İletişim',
                'name_en' => 'Communication',
                'description_tr' => 'Sözlü ve yazılı olarak net, etkili ve profesyonel iletişim kurma becerisi',
                'description_en' => 'Ability to communicate clearly, effectively and professionally, both verbally and in writing',
                'category' => 'soft_skill',
                'icon' => '💬',
                'is_universal' => true,
                'indicators' => [
                    'Düşüncelerini açık ve anlaşılır şekilde ifade eder',
                    'Aktif dinleme yapar',
                    'Farklı kitleler için iletişim tarzını uyarlar',
                    'Yazılı iletişimde profesyoneldir',
                ],
                'evaluation_criteria' => [
                    '1' => 'İletişimde ciddi zorluklar, anlaşılması güç',
                    '2' => 'Temel düzeyde iletişim kurar ama netlik eksik',
                    '3' => 'Yeterli düzeyde iletişim, bazı alanlarda geliştirilebilir',
                    '4' => 'Net ve etkili iletişim, farklı durumlara uyum sağlar',
                    '5' => 'Mükemmel iletişim becerileri, ilham verici ve ikna edici',
                ],
                'red_flags' => [
                    'Soruları anlamakta güçlük çeker',
                    'Tutarsız veya karışık yanıtlar verir',
                    'Çok kısa veya çok uzun cevaplar',
                ],
            ],
            [
                'code' => 'TEAMWORK',
                'name_tr' => 'Takım Çalışması',
                'name_en' => 'Teamwork',
                'description_tr' => 'Ekip içinde uyumlu çalışma, işbirliği yapma ve ortak hedeflere katkıda bulunma becerisi',
                'description_en' => 'Ability to work harmoniously in a team, collaborate and contribute to common goals',
                'category' => 'soft_skill',
                'icon' => '🤝',
                'is_universal' => true,
                'indicators' => [
                    'Ekip üyeleriyle işbirliği yapar',
                    'Farklı görüşlere saygı gösterir',
                    'Ortak başarıyı bireysel başarının önüne koyar',
                    'Ekip içi çatışmaları yapıcı şekilde çözer',
                ],
                'evaluation_criteria' => [
                    '1' => 'Ekip çalışmasına uyum sağlayamaz, bireysel çalışmayı tercih eder',
                    '2' => 'Zorunlu olduğunda ekiple çalışır ama işbirliği sınırlı',
                    '3' => 'Ekiple uyumlu çalışır, ortalama düzeyde işbirliği',
                    '4' => 'Etkin ekip oyuncusu, aktif katkı sağlar',
                    '5' => 'Ekibi bir araya getiren, işbirliğini teşvik eden örnek çalışan',
                ],
                'red_flags' => [
                    'Hep "ben" odaklı konuşur',
                    'Ekip başarısızlıklarında başkalarını suçlar',
                    'Diğerlerinin katkısını küçümser',
                ],
            ],
            [
                'code' => 'PROBLEM_SOLVING',
                'name_tr' => 'Problem Çözme',
                'name_en' => 'Problem Solving',
                'description_tr' => 'Karmaşık sorunları analiz etme, çözüm üretme ve uygulama becerisi',
                'description_en' => 'Ability to analyze complex problems, generate solutions and implement them',
                'category' => 'soft_skill',
                'icon' => '🧩',
                'is_universal' => true,
                'indicators' => [
                    'Problemi doğru tanımlar',
                    'Kök nedenleri analiz eder',
                    'Alternatif çözümler üretir',
                    'Sistematik yaklaşım sergiler',
                ],
                'evaluation_criteria' => [
                    '1' => 'Problemleri tanımlamakta zorlanır, çözüm üretemez',
                    '2' => 'Basit problemleri çözer ama karmaşık durumlarda zorlanır',
                    '3' => 'Standart problemleri çözer, ortalama analitik beceri',
                    '4' => 'Karmaşık problemleri başarıyla çözer, sistematik yaklaşım',
                    '5' => 'Olağanüstü problem çözme, yenilikçi çözümler üretir',
                ],
                'red_flags' => [
                    'Her şeyi başkalarının çözmesini bekler',
                    'Problem karşısında donup kalır',
                    'Sistematik düşünemez',
                ],
            ],
            [
                'code' => 'ADAPTABILITY',
                'name_tr' => 'Uyum Sağlama',
                'name_en' => 'Adaptability',
                'description_tr' => 'Değişen koşullara ve beklenmedik durumlara uyum sağlama becerisi',
                'description_en' => 'Ability to adapt to changing conditions and unexpected situations',
                'category' => 'soft_skill',
                'icon' => '🔄',
                'is_universal' => true,
                'indicators' => [
                    'Değişime açık tutum sergiler',
                    'Belirsizlikle baş edebilir',
                    'Yeni durumlara hızla uyum sağlar',
                    'Esnek çalışma anlayışı gösterir',
                ],
                'evaluation_criteria' => [
                    '1' => 'Değişime direnç gösterir, uyum sağlayamaz',
                    '2' => 'Değişime zorla uyum sağlar, zaman alır',
                    '3' => 'Normal değişimlere uyum sağlar',
                    '4' => 'Değişime hızla uyum sağlar, esnek',
                    '5' => 'Değişimi fırsata çevirir, başkalarına yol gösterir',
                ],
                'red_flags' => [
                    'Değişimden çok şikayet eder',
                    'Sadece tek bir yol görür',
                    'Belirsizlikte panik yapar',
                ],
            ],
            [
                'code' => 'RELIABILITY',
                'name_tr' => 'Güvenilirlik',
                'name_en' => 'Reliability',
                'description_tr' => 'Verilen görevleri zamanında ve kaliteli tamamlama, söz verilen şeyleri yerine getirme becerisi',
                'description_en' => 'Ability to complete assigned tasks on time and with quality, fulfilling commitments',
                'category' => 'behavioral',
                'icon' => '✅',
                'is_universal' => true,
                'indicators' => [
                    'Taahhütlerini yerine getirir',
                    'Zamanında teslim eder',
                    'Tutarlı performans gösterir',
                    'Sorumluluklarını sahiplenir',
                ],
                'evaluation_criteria' => [
                    '1' => 'Güvenilmez, taahhütlerini yerine getirmez',
                    '2' => 'Ara sıra güvenilir, tutarsız performans',
                    '3' => 'Genellikle güvenilir, bazı aksaklıklar olabilir',
                    '4' => 'Güvenilir, tutarlı performans',
                    '5' => 'Son derece güvenilir, her zaman üstün performans',
                ],
                'red_flags' => [
                    'Mazeret üretme eğilimi',
                    'Sözünde durmama geçmişi',
                    'Sorumluluktan kaçınma',
                ],
            ],
            [
                'code' => 'LEARNING_AGILITY',
                'name_tr' => 'Öğrenme Çevikliği',
                'name_en' => 'Learning Agility',
                'description_tr' => 'Yeni bilgi ve becerileri hızlı öğrenme ve uygulama kapasitesi',
                'description_en' => 'Capacity to quickly learn and apply new knowledge and skills',
                'category' => 'soft_skill',
                'icon' => '📚',
                'is_universal' => true,
                'indicators' => [
                    'Yeni bilgileri hızla öğrenir',
                    'Öğrendiklerini pratiğe döker',
                    'Geri bildirimi değerlendirir',
                    'Sürekli gelişim arayışında',
                ],
                'evaluation_criteria' => [
                    '1' => 'Öğrenme kapasitesi düşük, yeni becerileri edinmekte zorlanır',
                    '2' => 'Yavaş öğrenir, çok tekrar gerektirir',
                    '3' => 'Ortalama öğrenme hızı',
                    '4' => 'Hızlı öğrenir, yeni becerileri kolayca edinir',
                    '5' => 'Olağanüstü öğrenme kapasitesi, başkalarına da öğretir',
                ],
                'red_flags' => [
                    'Yeni şeyler öğrenmekten kaçınır',
                    'Geri bildirimi kabul etmez',
                    'Eski yöntemlerde ısrar eder',
                ],
            ],
            // Leadership competencies
            [
                'code' => 'LEADERSHIP',
                'name_tr' => 'Liderlik',
                'name_en' => 'Leadership',
                'description_tr' => 'Ekibi yönlendirme, motive etme ve ortak hedeflere ulaştırma becerisi',
                'description_en' => 'Ability to guide, motivate and lead the team to common goals',
                'category' => 'behavioral',
                'icon' => '👑',
                'is_universal' => false,
                'indicators' => [
                    'Vizyon belirler ve paylaşır',
                    'Ekibi motive eder',
                    'Örnek olur',
                    'Zor kararlar alabilir',
                ],
                'evaluation_criteria' => [
                    '1' => 'Liderlik kapasitesi yok, yönlendirme yapamaz',
                    '2' => 'Sınırlı liderlik, sadece talimat verir',
                    '3' => 'Temel liderlik becerileri var',
                    '4' => 'Etkili lider, ekibi motive eder',
                    '5' => 'İlham verici lider, dönüşüm yaratır',
                ],
                'red_flags' => [
                    'Mikro yönetim eğilimi',
                    'Kararlardan kaçınma',
                    'Ekibi suçlama',
                ],
            ],
            [
                'code' => 'CUSTOMER_FOCUS',
                'name_tr' => 'Müşteri Odaklılık',
                'name_en' => 'Customer Focus',
                'description_tr' => 'Müşteri ihtiyaçlarını anlama ve karşılamaya odaklanma becerisi',
                'description_en' => 'Ability to understand and meet customer needs',
                'category' => 'behavioral',
                'icon' => '🎯',
                'is_universal' => false,
                'indicators' => [
                    'Müşteri ihtiyaçlarını anlar',
                    'Proaktif hizmet sunar',
                    'Müşteri memnuniyetini önceliklendirir',
                    'Geri bildirimleri değerlendirir',
                ],
                'evaluation_criteria' => [
                    '1' => 'Müşteri odaklı değil, kendi işine odaklı',
                    '2' => 'Minimum müşteri hizmeti',
                    '3' => 'Standart müşteri hizmeti',
                    '4' => 'Müşteri odaklı, beklentilerin ötesinde hizmet',
                    '5' => 'Olağanüstü müşteri deneyimi yaratır',
                ],
                'red_flags' => [
                    'Müşteriden şikayet eder',
                    'Müşteri sorunlarını önemsemez',
                    'Tepkisel değil reaktif',
                ],
            ],
            [
                'code' => 'ATTENTION_TO_DETAIL',
                'name_tr' => 'Detaylara Dikkat',
                'name_en' => 'Attention to Detail',
                'description_tr' => 'İşleri titizlikle, hatasız ve standartlara uygun yapma becerisi',
                'description_en' => 'Ability to do work meticulously, error-free and according to standards',
                'category' => 'behavioral',
                'icon' => '🔍',
                'is_universal' => false,
                'indicators' => [
                    'Hatasız çalışır',
                    'Standartlara uyar',
                    'Kalite kontrolü yapar',
                    'Detayları gözden kaçırmaz',
                ],
                'evaluation_criteria' => [
                    '1' => 'Dikkatsiz, sürekli hata yapar',
                    '2' => 'Ara sıra hatalar yapar',
                    '3' => 'Kabul edilebilir dikkat seviyesi',
                    '4' => 'Dikkatli ve titiz çalışır',
                    '5' => 'Mükemmeliyetçi, sıfır hata',
                ],
                'red_flags' => [
                    'Aynı hataları tekrarlar',
                    'Kontrolsüz iş teslim eder',
                    'Kaliteyi önemsemez',
                ],
            ],
            [
                'code' => 'TIME_MANAGEMENT',
                'name_tr' => 'Zaman Yönetimi',
                'name_en' => 'Time Management',
                'description_tr' => 'Zamanı verimli kullanma, önceliklendirme ve deadline\'lara uyma becerisi',
                'description_en' => 'Ability to use time efficiently, prioritize and meet deadlines',
                'category' => 'behavioral',
                'icon' => '⏰',
                'is_universal' => true,
                'indicators' => [
                    'Önceliklendirme yapar',
                    'Zamanı verimli kullanır',
                    'Deadline\'lara uyar',
                    'Çoklu görevleri yönetir',
                ],
                'evaluation_criteria' => [
                    '1' => 'Zaman yönetimi yok, sürekli gecikmeler',
                    '2' => 'Zayıf zaman yönetimi',
                    '3' => 'Kabul edilebilir zaman yönetimi',
                    '4' => 'İyi zaman yönetimi',
                    '5' => 'Mükemmel zaman yönetimi',
                ],
                'red_flags' => [
                    'Sürekli geç kalır',
                    'Önceliklendirme yapamaz',
                    'Son dakikacı',
                ],
            ],
        ];

        foreach ($competencies as $competency) {
            Competency::updateOrCreate(
                ['code' => $competency['code']],
                $competency
            );
        }
    }

    private function seedJobDomains(): void
    {
        $domains = [
            [
                'code' => 'RETAIL',
                'name_tr' => 'Perakende',
                'name_en' => 'Retail',
                'icon' => '🛒',
                'description_tr' => 'Mağazacılık, satış noktası yönetimi ve perakende operasyonları',
                'description_en' => 'Store management, point of sale management and retail operations',
                'subdomains' => [
                    ['code' => 'RETAIL_STORE', 'name_tr' => 'Mağaza Operasyonları', 'name_en' => 'Store Operations', 'icon' => '🏪'],
                    ['code' => 'RETAIL_SALES', 'name_tr' => 'Satış', 'name_en' => 'Sales', 'icon' => '💰'],
                    ['code' => 'RETAIL_CASHIER', 'name_tr' => 'Kasa Operasyonları', 'name_en' => 'Cashier Operations', 'icon' => '🧾'],
                    ['code' => 'RETAIL_VISUAL', 'name_tr' => 'Görsel Mağazacılık', 'name_en' => 'Visual Merchandising', 'icon' => '🎨'],
                    ['code' => 'RETAIL_WAREHOUSE', 'name_tr' => 'Depo ve Stok', 'name_en' => 'Warehouse & Stock', 'icon' => '📦'],
                ],
            ],
            [
                'code' => 'FOOD_BEV',
                'name_tr' => 'Yiyecek & İçecek',
                'name_en' => 'Food & Beverage',
                'icon' => '🍽️',
                'description_tr' => 'Restoran, kafe, fast food ve yiyecek servisi operasyonları',
                'description_en' => 'Restaurant, cafe, fast food and food service operations',
                'subdomains' => [
                    ['code' => 'FB_KITCHEN', 'name_tr' => 'Mutfak', 'name_en' => 'Kitchen', 'icon' => '👨‍🍳'],
                    ['code' => 'FB_SERVICE', 'name_tr' => 'Servis', 'name_en' => 'Service', 'icon' => '🍸'],
                    ['code' => 'FB_BARISTA', 'name_tr' => 'Barista & Bar', 'name_en' => 'Barista & Bar', 'icon' => '☕'],
                    ['code' => 'FB_MANAGEMENT', 'name_tr' => 'Restoran Yönetimi', 'name_en' => 'Restaurant Management', 'icon' => '📋'],
                    ['code' => 'FB_DELIVERY', 'name_tr' => 'Teslimat', 'name_en' => 'Delivery', 'icon' => '🚴'],
                ],
            ],
            [
                'code' => 'HOSPITALITY',
                'name_tr' => 'Konaklama & Turizm',
                'name_en' => 'Hospitality & Tourism',
                'icon' => '🏨',
                'description_tr' => 'Otel, tatil köyleri, seyahat ve turizm hizmetleri',
                'description_en' => 'Hotels, resorts, travel and tourism services',
                'subdomains' => [
                    ['code' => 'HOSP_FRONT', 'name_tr' => 'Ön Büro', 'name_en' => 'Front Office', 'icon' => '🛎️'],
                    ['code' => 'HOSP_HOUSEKEEP', 'name_tr' => 'Kat Hizmetleri', 'name_en' => 'Housekeeping', 'icon' => '🧹'],
                    ['code' => 'HOSP_CONCIERGE', 'name_tr' => 'Concierge', 'name_en' => 'Concierge', 'icon' => '🎩'],
                    ['code' => 'HOSP_EVENTS', 'name_tr' => 'Etkinlik & Organizasyon', 'name_en' => 'Events & Organization', 'icon' => '🎪'],
                    ['code' => 'HOSP_TRAVEL', 'name_tr' => 'Seyahat & Tur', 'name_en' => 'Travel & Tour', 'icon' => '✈️'],
                ],
            ],
            [
                'code' => 'IT_TECH',
                'name_tr' => 'Bilişim & Teknoloji',
                'name_en' => 'IT & Technology',
                'icon' => '💻',
                'description_tr' => 'Yazılım geliştirme, sistem yönetimi ve teknoloji hizmetleri',
                'description_en' => 'Software development, system management and technology services',
                'subdomains' => [
                    ['code' => 'IT_DEV', 'name_tr' => 'Yazılım Geliştirme', 'name_en' => 'Software Development', 'icon' => '👨‍💻'],
                    ['code' => 'IT_INFRA', 'name_tr' => 'Altyapı & Sistem', 'name_en' => 'Infrastructure & Systems', 'icon' => '🖥️'],
                    ['code' => 'IT_DATA', 'name_tr' => 'Veri & Analitik', 'name_en' => 'Data & Analytics', 'icon' => '📊'],
                    ['code' => 'IT_SECURITY', 'name_tr' => 'Siber Güvenlik', 'name_en' => 'Cybersecurity', 'icon' => '🔒'],
                    ['code' => 'IT_SUPPORT', 'name_tr' => 'Teknik Destek', 'name_en' => 'Technical Support', 'icon' => '🛠️'],
                    ['code' => 'IT_PRODUCT', 'name_tr' => 'Ürün Yönetimi', 'name_en' => 'Product Management', 'icon' => '📱'],
                ],
            ],
            [
                'code' => 'HEALTHCARE',
                'name_tr' => 'Sağlık',
                'name_en' => 'Healthcare',
                'icon' => '🏥',
                'description_tr' => 'Hastane, klinik, eczane ve sağlık hizmetleri',
                'description_en' => 'Hospital, clinic, pharmacy and healthcare services',
                'subdomains' => [
                    ['code' => 'HC_NURSING', 'name_tr' => 'Hemşirelik', 'name_en' => 'Nursing', 'icon' => '👩‍⚕️'],
                    ['code' => 'HC_MEDICAL', 'name_tr' => 'Tıbbi Hizmetler', 'name_en' => 'Medical Services', 'icon' => '🩺'],
                    ['code' => 'HC_PHARMACY', 'name_tr' => 'Eczane', 'name_en' => 'Pharmacy', 'icon' => '💊'],
                    ['code' => 'HC_ADMIN', 'name_tr' => 'Sağlık İdaresi', 'name_en' => 'Healthcare Administration', 'icon' => '📋'],
                    ['code' => 'HC_LAB', 'name_tr' => 'Laboratuvar', 'name_en' => 'Laboratory', 'icon' => '🔬'],
                ],
            ],
            [
                'code' => 'FINANCE',
                'name_tr' => 'Finans & Bankacılık',
                'name_en' => 'Finance & Banking',
                'icon' => '🏦',
                'description_tr' => 'Bankacılık, sigortacılık, yatırım ve finansal hizmetler',
                'description_en' => 'Banking, insurance, investment and financial services',
                'subdomains' => [
                    ['code' => 'FIN_BANKING', 'name_tr' => 'Bankacılık', 'name_en' => 'Banking', 'icon' => '🏧'],
                    ['code' => 'FIN_INSURANCE', 'name_tr' => 'Sigortacılık', 'name_en' => 'Insurance', 'icon' => '🛡️'],
                    ['code' => 'FIN_INVEST', 'name_tr' => 'Yatırım', 'name_en' => 'Investment', 'icon' => '📈'],
                    ['code' => 'FIN_ACCOUNTING', 'name_tr' => 'Muhasebe', 'name_en' => 'Accounting', 'icon' => '🧮'],
                    ['code' => 'FIN_AUDIT', 'name_tr' => 'Denetim', 'name_en' => 'Audit', 'icon' => '📑'],
                ],
            ],
            [
                'code' => 'EDUCATION',
                'name_tr' => 'Eğitim',
                'name_en' => 'Education',
                'icon' => '🎓',
                'description_tr' => 'Okul öncesi, ilk-orta-lise, yükseköğretim ve özel eğitim',
                'description_en' => 'Preschool, K-12, higher education and special education',
                'subdomains' => [
                    ['code' => 'EDU_PRESCHOOL', 'name_tr' => 'Okul Öncesi', 'name_en' => 'Preschool', 'icon' => '🧒'],
                    ['code' => 'EDU_K12', 'name_tr' => 'İlk ve Ortaöğretim', 'name_en' => 'K-12', 'icon' => '📚'],
                    ['code' => 'EDU_HIGHER', 'name_tr' => 'Yükseköğretim', 'name_en' => 'Higher Education', 'icon' => '🎓'],
                    ['code' => 'EDU_SPECIAL', 'name_tr' => 'Özel Eğitim', 'name_en' => 'Special Education', 'icon' => '🤝'],
                    ['code' => 'EDU_TRAINING', 'name_tr' => 'Kurumsal Eğitim', 'name_en' => 'Corporate Training', 'icon' => '📊'],
                ],
            ],
            [
                'code' => 'MANUFACTURING',
                'name_tr' => 'Üretim & İmalat',
                'name_en' => 'Manufacturing',
                'icon' => '🏭',
                'description_tr' => 'Fabrika, atölye ve endüstriyel üretim operasyonları',
                'description_en' => 'Factory, workshop and industrial production operations',
                'subdomains' => [
                    ['code' => 'MFG_PRODUCTION', 'name_tr' => 'Üretim', 'name_en' => 'Production', 'icon' => '⚙️'],
                    ['code' => 'MFG_QUALITY', 'name_tr' => 'Kalite Kontrol', 'name_en' => 'Quality Control', 'icon' => '✅'],
                    ['code' => 'MFG_MAINTENANCE', 'name_tr' => 'Bakım', 'name_en' => 'Maintenance', 'icon' => '🔧'],
                    ['code' => 'MFG_PLANNING', 'name_tr' => 'Üretim Planlama', 'name_en' => 'Production Planning', 'icon' => '📅'],
                    ['code' => 'MFG_SAFETY', 'name_tr' => 'İş Güvenliği', 'name_en' => 'Safety', 'icon' => '⚠️'],
                ],
            ],
            [
                'code' => 'LOGISTICS',
                'name_tr' => 'Lojistik & Tedarik',
                'name_en' => 'Logistics & Supply Chain',
                'icon' => '🚚',
                'description_tr' => 'Depolama, dağıtım, nakliye ve tedarik zinciri yönetimi',
                'description_en' => 'Warehousing, distribution, transportation and supply chain management',
                'subdomains' => [
                    ['code' => 'LOG_WAREHOUSE', 'name_tr' => 'Depo Yönetimi', 'name_en' => 'Warehouse Management', 'icon' => '📦'],
                    ['code' => 'LOG_TRANSPORT', 'name_tr' => 'Nakliye', 'name_en' => 'Transportation', 'icon' => '🚛'],
                    ['code' => 'LOG_SUPPLY', 'name_tr' => 'Tedarik', 'name_en' => 'Supply Chain', 'icon' => '🔗'],
                    ['code' => 'LOG_CUSTOMS', 'name_tr' => 'Gümrük', 'name_en' => 'Customs', 'icon' => '🛃'],
                    ['code' => 'LOG_FLEET', 'name_tr' => 'Filo Yönetimi', 'name_en' => 'Fleet Management', 'icon' => '🚗'],
                ],
            ],
            [
                'code' => 'CONSTRUCTION',
                'name_tr' => 'İnşaat & Emlak',
                'name_en' => 'Construction & Real Estate',
                'icon' => '🏗️',
                'description_tr' => 'İnşaat projeleri, gayrimenkul ve mülk yönetimi',
                'description_en' => 'Construction projects, real estate and property management',
                'subdomains' => [
                    ['code' => 'CON_SITE', 'name_tr' => 'Şantiye', 'name_en' => 'Site Operations', 'icon' => '👷'],
                    ['code' => 'CON_ARCH', 'name_tr' => 'Mimarlık', 'name_en' => 'Architecture', 'icon' => '🏛️'],
                    ['code' => 'CON_CIVIL', 'name_tr' => 'İnşaat Mühendisliği', 'name_en' => 'Civil Engineering', 'icon' => '🌉'],
                    ['code' => 'CON_REALESTATE', 'name_tr' => 'Gayrimenkul', 'name_en' => 'Real Estate', 'icon' => '🏠'],
                    ['code' => 'CON_PROPERTY', 'name_tr' => 'Mülk Yönetimi', 'name_en' => 'Property Management', 'icon' => '🔑'],
                ],
            ],
            [
                'code' => 'MARKETING',
                'name_tr' => 'Pazarlama & İletişim',
                'name_en' => 'Marketing & Communications',
                'icon' => '📢',
                'description_tr' => 'Marka yönetimi, dijital pazarlama ve kurumsal iletişim',
                'description_en' => 'Brand management, digital marketing and corporate communications',
                'subdomains' => [
                    ['code' => 'MKT_DIGITAL', 'name_tr' => 'Dijital Pazarlama', 'name_en' => 'Digital Marketing', 'icon' => '🌐'],
                    ['code' => 'MKT_BRAND', 'name_tr' => 'Marka Yönetimi', 'name_en' => 'Brand Management', 'icon' => '🏷️'],
                    ['code' => 'MKT_CONTENT', 'name_tr' => 'İçerik', 'name_en' => 'Content', 'icon' => '✍️'],
                    ['code' => 'MKT_PR', 'name_tr' => 'Halkla İlişkiler', 'name_en' => 'Public Relations', 'icon' => '📰'],
                    ['code' => 'MKT_SOCIAL', 'name_tr' => 'Sosyal Medya', 'name_en' => 'Social Media', 'icon' => '📱'],
                ],
            ],
            [
                'code' => 'HR',
                'name_tr' => 'İnsan Kaynakları',
                'name_en' => 'Human Resources',
                'icon' => '👥',
                'description_tr' => 'İşe alım, eğitim, bordro ve çalışan ilişkileri',
                'description_en' => 'Recruitment, training, payroll and employee relations',
                'subdomains' => [
                    ['code' => 'HR_RECRUIT', 'name_tr' => 'İşe Alım', 'name_en' => 'Recruitment', 'icon' => '🎯'],
                    ['code' => 'HR_TRAINING', 'name_tr' => 'Eğitim ve Gelişim', 'name_en' => 'Training & Development', 'icon' => '📈'],
                    ['code' => 'HR_PAYROLL', 'name_tr' => 'Bordro', 'name_en' => 'Payroll', 'icon' => '💵'],
                    ['code' => 'HR_RELATIONS', 'name_tr' => 'Çalışan İlişkileri', 'name_en' => 'Employee Relations', 'icon' => '🤝'],
                    ['code' => 'HR_COMP', 'name_tr' => 'Ücret ve Yan Haklar', 'name_en' => 'Compensation & Benefits', 'icon' => '🎁'],
                ],
            ],
            [
                'code' => 'LEGAL',
                'name_tr' => 'Hukuk',
                'name_en' => 'Legal',
                'icon' => '⚖️',
                'description_tr' => 'Hukuki danışmanlık, sözleşme yönetimi ve uyum',
                'description_en' => 'Legal advisory, contract management and compliance',
                'subdomains' => [
                    ['code' => 'LEG_CORPORATE', 'name_tr' => 'Şirketler Hukuku', 'name_en' => 'Corporate Law', 'icon' => '🏢'],
                    ['code' => 'LEG_CONTRACT', 'name_tr' => 'Sözleşme', 'name_en' => 'Contract', 'icon' => '📝'],
                    ['code' => 'LEG_LITIGATION', 'name_tr' => 'Dava', 'name_en' => 'Litigation', 'icon' => '🔨'],
                    ['code' => 'LEG_COMPLIANCE', 'name_tr' => 'Uyum', 'name_en' => 'Compliance', 'icon' => '✅'],
                    ['code' => 'LEG_IP', 'name_tr' => 'Fikri Mülkiyet', 'name_en' => 'Intellectual Property', 'icon' => '💡'],
                ],
            ],
            [
                'code' => 'CUSTOMER_SERVICE',
                'name_tr' => 'Müşteri Hizmetleri',
                'name_en' => 'Customer Service',
                'icon' => '📞',
                'description_tr' => 'Çağrı merkezi, müşteri destek ve şikayet yönetimi',
                'description_en' => 'Call center, customer support and complaint management',
                'subdomains' => [
                    ['code' => 'CS_CALLCENTER', 'name_tr' => 'Çağrı Merkezi', 'name_en' => 'Call Center', 'icon' => '🎧'],
                    ['code' => 'CS_SUPPORT', 'name_tr' => 'Müşteri Destek', 'name_en' => 'Customer Support', 'icon' => '💬'],
                    ['code' => 'CS_SUCCESS', 'name_tr' => 'Müşteri Başarısı', 'name_en' => 'Customer Success', 'icon' => '🌟'],
                    ['code' => 'CS_COMPLAINT', 'name_tr' => 'Şikayet Yönetimi', 'name_en' => 'Complaint Management', 'icon' => '📋'],
                ],
            ],
            [
                'code' => 'SECURITY',
                'name_tr' => 'Güvenlik',
                'name_en' => 'Security',
                'icon' => '🔐',
                'description_tr' => 'Fiziksel güvenlik, özel güvenlik ve koruma hizmetleri',
                'description_en' => 'Physical security, private security and protection services',
                'subdomains' => [
                    ['code' => 'SEC_PHYSICAL', 'name_tr' => 'Fiziksel Güvenlik', 'name_en' => 'Physical Security', 'icon' => '🚨'],
                    ['code' => 'SEC_EXECUTIVE', 'name_tr' => 'Koruma', 'name_en' => 'Executive Protection', 'icon' => '🕴️'],
                    ['code' => 'SEC_CONTROL', 'name_tr' => 'Kontrol Odası', 'name_en' => 'Control Room', 'icon' => '📹'],
                    ['code' => 'SEC_PATROL', 'name_tr' => 'Devriye', 'name_en' => 'Patrol', 'icon' => '🚔'],
                ],
            ],
            [
                'code' => 'CLEANING',
                'name_tr' => 'Temizlik & Bakım',
                'name_en' => 'Cleaning & Maintenance',
                'icon' => '🧹',
                'description_tr' => 'Temizlik hizmetleri, bina bakımı ve tesis yönetimi',
                'description_en' => 'Cleaning services, building maintenance and facility management',
                'subdomains' => [
                    ['code' => 'CLN_GENERAL', 'name_tr' => 'Genel Temizlik', 'name_en' => 'General Cleaning', 'icon' => '🧽'],
                    ['code' => 'CLN_INDUSTRIAL', 'name_tr' => 'Endüstriyel Temizlik', 'name_en' => 'Industrial Cleaning', 'icon' => '🏭'],
                    ['code' => 'CLN_FACILITY', 'name_tr' => 'Tesis Yönetimi', 'name_en' => 'Facility Management', 'icon' => '🏢'],
                    ['code' => 'CLN_LANDSCAPE', 'name_tr' => 'Peyzaj', 'name_en' => 'Landscape', 'icon' => '🌳'],
                ],
            ],
            [
                'code' => 'AUTOMOTIVE',
                'name_tr' => 'Otomotiv',
                'name_en' => 'Automotive',
                'icon' => '🚗',
                'description_tr' => 'Araç satış, servis, yedek parça ve otomotiv hizmetleri',
                'description_en' => 'Vehicle sales, service, spare parts and automotive services',
                'subdomains' => [
                    ['code' => 'AUTO_SALES', 'name_tr' => 'Araç Satış', 'name_en' => 'Vehicle Sales', 'icon' => '🚙'],
                    ['code' => 'AUTO_SERVICE', 'name_tr' => 'Servis', 'name_en' => 'Service', 'icon' => '🔧'],
                    ['code' => 'AUTO_PARTS', 'name_tr' => 'Yedek Parça', 'name_en' => 'Parts', 'icon' => '⚙️'],
                    ['code' => 'AUTO_BODYSHOP', 'name_tr' => 'Kaporta & Boya', 'name_en' => 'Body Shop', 'icon' => '🎨'],
                ],
            ],
            [
                'code' => 'AGRICULTURE',
                'name_tr' => 'Tarım & Hayvancılık',
                'name_en' => 'Agriculture & Livestock',
                'icon' => '🌾',
                'description_tr' => 'Tarımsal üretim, hayvancılık ve gıda işleme',
                'description_en' => 'Agricultural production, livestock and food processing',
                'subdomains' => [
                    ['code' => 'AGR_FARMING', 'name_tr' => 'Çiftçilik', 'name_en' => 'Farming', 'icon' => '🚜'],
                    ['code' => 'AGR_LIVESTOCK', 'name_tr' => 'Hayvancılık', 'name_en' => 'Livestock', 'icon' => '🐄'],
                    ['code' => 'AGR_PROCESSING', 'name_tr' => 'Gıda İşleme', 'name_en' => 'Food Processing', 'icon' => '🍎'],
                    ['code' => 'AGR_GREENHOUSE', 'name_tr' => 'Sera', 'name_en' => 'Greenhouse', 'icon' => '🌱'],
                ],
            ],
            [
                'code' => 'BEAUTY',
                'name_tr' => 'Güzellik & Kişisel Bakım',
                'name_en' => 'Beauty & Personal Care',
                'icon' => '💅',
                'description_tr' => 'Kuaför, estetik, spa ve kişisel bakım hizmetleri',
                'description_en' => 'Hairdressing, aesthetics, spa and personal care services',
                'subdomains' => [
                    ['code' => 'BEAUTY_HAIR', 'name_tr' => 'Kuaförlük', 'name_en' => 'Hairdressing', 'icon' => '💇'],
                    ['code' => 'BEAUTY_NAILS', 'name_tr' => 'Tırnak Bakımı', 'name_en' => 'Nail Care', 'icon' => '💅'],
                    ['code' => 'BEAUTY_MAKEUP', 'name_tr' => 'Makyaj', 'name_en' => 'Makeup', 'icon' => '💄'],
                    ['code' => 'BEAUTY_SPA', 'name_tr' => 'Spa & Masaj', 'name_en' => 'Spa & Massage', 'icon' => '🧖'],
                ],
            ],
            [
                'code' => 'CHILDCARE',
                'name_tr' => 'Çocuk Bakımı',
                'name_en' => 'Childcare',
                'icon' => '👶',
                'description_tr' => 'Bebek bakımı, dadılık ve çocuk gelişimi',
                'description_en' => 'Baby care, nannying and child development',
                'subdomains' => [
                    ['code' => 'CHILD_NANNY', 'name_tr' => 'Dadılık', 'name_en' => 'Nannying', 'icon' => '👩‍👧'],
                    ['code' => 'CHILD_DAYCARE', 'name_tr' => 'Kreş', 'name_en' => 'Daycare', 'icon' => '🧒'],
                    ['code' => 'CHILD_ACTIVITY', 'name_tr' => 'Çocuk Aktiviteleri', 'name_en' => 'Child Activities', 'icon' => '🎨'],
                    ['code' => 'CHILD_TUTOR', 'name_tr' => 'Özel Ders', 'name_en' => 'Tutoring', 'icon' => '📖'],
                ],
            ],
        ];

        foreach ($domains as $index => $domainData) {
            $subdomains = $domainData['subdomains'] ?? [];
            unset($domainData['subdomains']);

            $domain = JobDomain::updateOrCreate(
                ['code' => $domainData['code']],
                array_merge($domainData, ['sort_order' => $index + 1])
            );

            foreach ($subdomains as $subIndex => $subdomainData) {
                JobSubdomain::updateOrCreate(
                    ['code' => $subdomainData['code']],
                    array_merge($subdomainData, [
                        'domain_id' => $domain->id,
                        'sort_order' => $subIndex + 1,
                    ])
                );
            }
        }
    }

    private function seedExpectationQuestions(): void
    {
        $questions = [
            // Salary
            [
                'code' => 'SALARY_EXPECT',
                'category' => 'salary',
                'question_tr' => 'Bu pozisyon için maaş beklentiniz nedir?',
                'question_en' => 'What is your salary expectation for this position?',
                'answer_type' => 'open',
                'evaluation_note_tr' => 'Piyasa şartlarıyla uyumu değerlendirin',
                'evaluation_note_en' => 'Evaluate alignment with market conditions',
            ],
            [
                'code' => 'SALARY_PRIORITY',
                'category' => 'salary',
                'question_tr' => 'Maaş sizin için ne kadar önemli? Diğer faktörlerle karşılaştırın.',
                'question_en' => 'How important is salary to you? Compare with other factors.',
                'answer_type' => 'open',
                'evaluation_note_tr' => 'Motivasyon kaynaklarını anlayın',
                'evaluation_note_en' => 'Understand motivation sources',
            ],
            // Work Hours
            [
                'code' => 'HOURS_PREFER',
                'category' => 'work_hours',
                'question_tr' => 'Tercih ettiğiniz çalışma saatleri nedir?',
                'question_en' => 'What are your preferred working hours?',
                'answer_type' => 'single_choice',
                'answer_options' => [
                    ['value' => 'standard', 'label_tr' => 'Standart mesai (09:00-18:00)', 'label_en' => 'Standard hours (9-6)'],
                    ['value' => 'flexible', 'label_tr' => 'Esnek çalışma saatleri', 'label_en' => 'Flexible hours'],
                    ['value' => 'shift', 'label_tr' => 'Vardiyalı çalışma', 'label_en' => 'Shift work'],
                    ['value' => 'weekend', 'label_tr' => 'Hafta sonu dahil', 'label_en' => 'Including weekends'],
                ],
            ],
            [
                'code' => 'OVERTIME',
                'category' => 'work_hours',
                'question_tr' => 'Fazla mesai konusunda tutumunuz nedir?',
                'question_en' => 'What is your attitude towards overtime?',
                'answer_type' => 'open',
            ],
            // Location
            [
                'code' => 'COMMUTE',
                'category' => 'location',
                'question_tr' => 'Kabul edebileceğiniz maksimum iş-ev uzaklığı nedir?',
                'question_en' => 'What is the maximum commute distance you can accept?',
                'answer_type' => 'open',
            ],
            [
                'code' => 'REMOTE',
                'category' => 'location',
                'question_tr' => 'Uzaktan çalışma tercihiniz nedir?',
                'question_en' => 'What is your remote work preference?',
                'answer_type' => 'single_choice',
                'answer_options' => [
                    ['value' => 'office', 'label_tr' => 'Tam zamanlı ofis', 'label_en' => 'Full-time office'],
                    ['value' => 'hybrid', 'label_tr' => 'Hibrit model', 'label_en' => 'Hybrid model'],
                    ['value' => 'remote', 'label_tr' => 'Tam uzaktan', 'label_en' => 'Fully remote'],
                    ['value' => 'flexible', 'label_tr' => 'Esnek', 'label_en' => 'Flexible'],
                ],
            ],
            // Growth
            [
                'code' => 'CAREER_GOALS',
                'category' => 'growth',
                'question_tr' => '3-5 yıl içinde kendinizi nerede görüyorsunuz?',
                'question_en' => 'Where do you see yourself in 3-5 years?',
                'answer_type' => 'open',
                'evaluation_note_tr' => 'Kariyer hedeflerini pozisyonla uyumunu değerlendirin',
                'evaluation_note_en' => 'Evaluate alignment of career goals with the position',
            ],
            [
                'code' => 'LEARNING_PREF',
                'category' => 'growth',
                'question_tr' => 'Kendinizi nasıl geliştirmeyi tercih edersiniz?',
                'question_en' => 'How do you prefer to develop yourself?',
                'answer_type' => 'multi_choice',
                'answer_options' => [
                    ['value' => 'courses', 'label_tr' => 'Eğitim ve kurslar', 'label_en' => 'Training and courses'],
                    ['value' => 'mentorship', 'label_tr' => 'Mentorluk', 'label_en' => 'Mentorship'],
                    ['value' => 'learning_by_doing', 'label_tr' => 'Yaparak öğrenme', 'label_en' => 'Learning by doing'],
                    ['value' => 'self_study', 'label_tr' => 'Kendi kendine çalışma', 'label_en' => 'Self-study'],
                ],
            ],
            // Culture
            [
                'code' => 'TEAM_STYLE',
                'category' => 'culture',
                'question_tr' => 'Nasıl bir ekip ortamında en verimli çalışırsınız?',
                'question_en' => 'In what kind of team environment do you work most efficiently?',
                'answer_type' => 'open',
            ],
            [
                'code' => 'WORK_STYLE',
                'category' => 'culture',
                'question_tr' => 'Bireysel mi yoksa ekip çalışmasını mı tercih edersiniz?',
                'question_en' => 'Do you prefer individual or team work?',
                'answer_type' => 'single_choice',
                'answer_options' => [
                    ['value' => 'individual', 'label_tr' => 'Bireysel çalışma', 'label_en' => 'Individual work'],
                    ['value' => 'team', 'label_tr' => 'Ekip çalışması', 'label_en' => 'Team work'],
                    ['value' => 'both', 'label_tr' => 'Her ikisi de', 'label_en' => 'Both'],
                ],
            ],
            // General
            [
                'code' => 'START_DATE',
                'category' => 'general',
                'question_tr' => 'Ne zaman işe başlayabilirsiniz?',
                'question_en' => 'When can you start?',
                'answer_type' => 'open',
            ],
            [
                'code' => 'WHY_INTERESTED',
                'category' => 'general',
                'question_tr' => 'Bu pozisyonla neden ilgileniyorsunuz?',
                'question_en' => 'Why are you interested in this position?',
                'answer_type' => 'open',
                'evaluation_note_tr' => 'Motivasyonu ve şirketi araştırıp araştırmadığını değerlendirin',
                'evaluation_note_en' => 'Evaluate motivation and whether they researched the company',
            ],
        ];

        foreach ($questions as $index => $question) {
            ExpectationQuestion::updateOrCreate(
                ['code' => $question['code']],
                array_merge($question, ['sort_order' => $index + 1])
            );
        }
    }
}
