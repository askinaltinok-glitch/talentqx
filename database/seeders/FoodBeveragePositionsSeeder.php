<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\JobDomain;
use App\Models\JobPosition;
use App\Models\JobSubdomain;
use App\Models\PositionQuestion;
use App\Models\RoleArchetype;
use Illuminate\Database\Seeder;

class FoodBeveragePositionsSeeder extends Seeder
{
    private array $archetypes = [];
    private array $competencies = [];

    public function run(): void
    {
        $this->loadArchetypes();
        $this->loadCompetencies();

        $domain = JobDomain::where('code', 'FOOD_BEV')->first();
        if (!$domain) {
            $this->command->error('Food & Beverage domain not found. Run TaxonomySeeder first.');
            return;
        }

        $this->seedKitchenPositions();
        $this->seedServicePositions();
        $this->seedBaristaPositions();
        $this->seedManagementPositions();
        $this->seedDeliveryPositions();
    }

    private function loadArchetypes(): void
    {
        $this->archetypes = RoleArchetype::pluck('id', 'code')->toArray();
    }

    private function loadCompetencies(): void
    {
        $this->competencies = Competency::pluck('id', 'code')->toArray();
    }

    private function seedKitchenPositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'FB_KITCHEN')->first();
        if (!$subdomain) return;

        // Mutfak Elemanı (Entry)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_KITCHEN_HELPER',
            'name_tr' => 'Mutfak Elemanı',
            'name_en' => 'Kitchen Helper',
            'archetype' => 'ENTRY',
            'description_tr' => 'Mutfakta hazırlık, temizlik ve yardımcı görevler yapan giriş seviye pozisyon',
            'description_en' => 'Entry-level position doing prep work, cleaning and support tasks in the kitchen',
            'experience_min_years' => 0,
            'experience_max_years' => 1,
            'education_level' => 'high_school',
            'competencies' => ['RELIABILITY', 'TEAMWORK', 'ADAPTABILITY', 'ATTENTION_TO_DETAIL'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Mutfakta hijyen kurallarına uyum konusunda nelere dikkat edersiniz?',
                'question_en' => 'What do you pay attention to regarding hygiene rules in the kitchen?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['El yıkama', 'Temizlik prosedürleri', 'Gıda güvenliği', 'Kişisel hijyen'],
                'red_flag_indicators' => ['Hijyen bilinci eksikliği', 'Kuralları önemsememe'],
            ],
            [
                'question_tr' => 'Yoğun servis saatlerinde mutfakta nasıl çalışırsınız?',
                'question_en' => 'How do you work in the kitchen during busy service hours?',
                'competency' => 'ADAPTABILITY',
                'type' => 'situational',
                'expected_indicators' => ['Hız', 'Sakinlik', 'Önceliklendirme', 'Ekip uyumu'],
                'red_flag_indicators' => ['Panik', 'Stres altında çökme', 'Yavaşlama'],
            ],
            [
                'question_tr' => 'Şef veya aşçılardan aldığınız talimatları nasıl uygularsınız?',
                'question_en' => 'How do you follow instructions from chefs or cooks?',
                'competency' => 'RELIABILITY',
                'type' => 'behavioral',
                'expected_indicators' => ['Dikkatli dinleme', 'Soru sorma', 'Doğru uygulama', 'Geri bildirim'],
                'red_flag_indicators' => ['Dinlememe', 'Kendi bildiğini yapma', 'Talimat reddi'],
            ],
            [
                'question_tr' => 'Mutfakta ekip çalışması sizin için ne anlama geliyor?',
                'question_en' => 'What does teamwork in the kitchen mean to you?',
                'competency' => 'TEAMWORK',
                'type' => 'experience',
                'expected_indicators' => ['İşbirliği', 'Yardımlaşma', 'İletişim', 'Ortak hedef'],
                'red_flag_indicators' => ['Bireysellik', 'Çatışma eğilimi'],
            ],
            [
                'question_tr' => 'Malzeme hazırlama (doğrama, yıkama vb.) konusundaki deneyiminiz nedir?',
                'question_en' => 'What is your experience with food preparation (chopping, washing, etc.)?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Teknik bilgi', 'Güvenli kullanım', 'Hız ve doğruluk'],
                'red_flag_indicators' => ['Deneyim eksikliği kabul edilebilir ama öğrenme isteği olmalı'],
            ],
            [
                'question_tr' => 'Mutfakta bir hata yaptığınızda (yanlış kesim, dökülen malzeme vb.) nasıl davranırsınız?',
                'question_en' => 'How do you react when you make a mistake in the kitchen?',
                'competency' => 'RELIABILITY',
                'type' => 'situational',
                'expected_indicators' => ['Dürüstlük', 'Hızlı düzeltme', 'Öğrenme', 'Raporlama'],
                'red_flag_indicators' => ['Gizleme', 'Başkalarını suçlama', 'Tekrarlama'],
            ],
            [
                'question_tr' => 'Uzun süreler ayakta çalışmak ve fiziksel iş yapmak konusunda ne düşünüyorsunuz?',
                'question_en' => 'What do you think about working on your feet for long periods and physical work?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Fiziksel dayanıklılık', 'Pozitif tutum', 'Gerçekçi beklenti'],
                'red_flag_indicators' => ['Fiziksel iş reddi', 'Negatif tutum'],
            ],
            [
                'question_tr' => 'Neden yiyecek içecek sektöründe çalışmak istiyorsunuz?',
                'question_en' => 'Why do you want to work in the food and beverage industry?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sektöre ilgi', 'Kariyer hedefi', 'Öğrenme isteği', 'Tutku'],
                'red_flag_indicators' => ['Sadece geçici iş arayışı', 'İlgisizlik'],
            ],
        ]);

        // Aşçı Yardımcısı (Entry-Specialist arası)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_KITCHEN_COMMIS',
            'name_tr' => 'Aşçı Yardımcısı (Commis)',
            'name_en' => 'Commis Chef',
            'archetype' => 'ENTRY',
            'description_tr' => 'Temel pişirme tekniklerini uygulayan, aşçılara yardım eden pozisyon',
            'description_en' => 'Position applying basic cooking techniques and assisting cooks',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['ATTENTION_TO_DETAIL', 'LEARNING_AGILITY', 'RELIABILITY', 'TEAMWORK'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Temel pişirme teknikleri (haşlama, kızartma, ızgara vb.) hakkında bilginizi paylaşır mısınız?',
                'question_en' => 'Can you share your knowledge about basic cooking techniques?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Teknik bilgi', 'Pratik deneyim', 'Doğru terminoloji'],
                'red_flag_indicators' => ['Temel bilgi eksikliği'],
            ],
            [
                'question_tr' => 'Bir yemeğin tarifine uymak mı yoksa yaratıcılık mı daha önemli sizce?',
                'question_en' => 'Do you think following a recipe or creativity is more important?',
                'competency' => 'RELIABILITY',
                'type' => 'situational',
                'expected_indicators' => ['Önce tariflere uyum', 'Zamanla yaratıcılık', 'Standart bilinci'],
                'red_flag_indicators' => ['Kurallara uymama eğilimi', 'Aşırı yaratıcılık iddiası'],
            ],
            [
                'question_tr' => 'Mutfakta zaman yönetimi konusunda nasıl bir yaklaşımınız var?',
                'question_en' => 'What is your approach to time management in the kitchen?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Mise en place', 'Önceliklendirme', 'Paralel iş yapma'],
                'red_flag_indicators' => ['Plansızlık', 'Zaman kavramı eksikliği'],
            ],
            [
                'question_tr' => 'Yeni bir tarif öğrenirken hangi yöntemleri kullanırsınız?',
                'question_en' => 'What methods do you use when learning a new recipe?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Gözlem', 'Not alma', 'Pratik yapma', 'Soru sorma'],
                'red_flag_indicators' => ['Öğrenme isteği eksikliği'],
            ],
            [
                'question_tr' => 'Malzeme israfını önlemek için neler yaparsınız?',
                'question_en' => 'What do you do to prevent ingredient waste?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Porsiyon kontrolü', 'FIFO', 'Yaratıcı kullanım', 'Planlama'],
                'red_flag_indicators' => ['İsraf bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Yoğun servis sırasında birden fazla sipariş geldiğinde nasıl organize olursunuz?',
                'question_en' => 'How do you organize yourself when multiple orders come in during busy service?',
                'competency' => 'ADAPTABILITY',
                'type' => 'situational',
                'expected_indicators' => ['Önceliklendirme', 'Sıralama', 'İletişim', 'Sakinlik'],
                'red_flag_indicators' => ['Panik', 'Organizasyon eksikliği'],
            ],
            [
                'question_tr' => 'Mutfakta güvenlik kuralları hakkında neler biliyorsunuz?',
                'question_en' => 'What do you know about safety rules in the kitchen?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Bıçak güvenliği', 'Yangın önleme', 'Kimyasal kullanımı', 'İlk yardım'],
                'red_flag_indicators' => ['Güvenlik bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Eleştiri aldığınızda nasıl tepki verirsiniz?',
                'question_en' => 'How do you react when you receive criticism?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'behavioral',
                'expected_indicators' => ['Kabul', 'Öğrenme', 'Geliştirme', 'Profesyonellik'],
                'red_flag_indicators' => ['Savunmacılık', 'Kabullenememe', 'Duygusallık'],
            ],
        ]);

        // Aşçı (Specialist)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_KITCHEN_COOK',
            'name_tr' => 'Aşçı',
            'name_en' => 'Cook',
            'archetype' => 'SPECIALIST',
            'description_tr' => 'Belirli bir bölümde (soğuk, sıcak, pastane vb.) uzmanlaşmış, yemekleri hazırlayan pozisyon',
            'description_en' => 'Position specialized in a specific section (cold, hot, pastry, etc.) preparing dishes',
            'experience_min_years' => 2,
            'experience_max_years' => 5,
            'education_level' => 'high_school',
            'competencies' => ['ATTENTION_TO_DETAIL', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT', 'TEAMWORK'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Hangi mutfak bölümünde (parti) deneyiminiz var ve neden bu alanda uzmanlaştınız?',
                'question_en' => 'Which kitchen section (station) do you have experience in and why did you specialize in this area?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Uzmanlık alanı', 'Derinlik', 'Kariyer bilinci'],
                'red_flag_indicators' => ['Yüzeysel bilgi', 'Tutarsızlık'],
            ],
            [
                'question_tr' => 'Servis sırasında bir yemek geri geldiğinde nasıl tepki verirsiniz?',
                'question_en' => 'How do you react when a dish is sent back during service?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Sakinlik', 'Hızlı düzeltme', 'Neden analizi', 'Profesyonellik'],
                'red_flag_indicators' => ['Savunmacılık', 'Öfke', 'Görmezden gelme'],
            ],
            [
                'question_tr' => 'Mise en place (hazırlık) sürecinizi nasıl organize edersiniz?',
                'question_en' => 'How do you organize your mise en place (prep) process?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Sistematik yaklaşım', 'Önceliklendirme', 'Zamanlama', 'Düzen'],
                'red_flag_indicators' => ['Plansızlık', 'Mise en place bilmeme'],
            ],
            [
                'question_tr' => 'Menü değişikliğinde yeni yemekleri nasıl öğrenir ve uygularsınız?',
                'question_en' => 'How do you learn and apply new dishes when the menu changes?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Hızlı adaptasyon', 'Deneme', 'Standartlaştırma'],
                'red_flag_indicators' => ['Değişime direnç', 'Yavaş öğrenme'],
            ],
            [
                'question_tr' => 'Mutfakta diğer bölümlerle koordinasyonu nasıl sağlarsınız?',
                'question_en' => 'How do you ensure coordination with other sections in the kitchen?',
                'competency' => 'TEAMWORK',
                'type' => 'experience',
                'expected_indicators' => ['İletişim', 'Zamanlama', 'Yardımlaşma', 'Uyum'],
                'red_flag_indicators' => ['İzole çalışma', 'İletişimsizlik'],
            ],
            [
                'question_tr' => 'Stok yönetimi ve sipariş konusundaki rolünüz nedir?',
                'question_en' => 'What is your role in inventory management and ordering?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Stok takibi', 'Sipariş bildirimi', 'Fire kontrolü'],
                'red_flag_indicators' => ['Stok bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Beklenmedik bir durumda (malzeme bitmesi, ekipman arızası) nasıl çözüm üretirsiniz?',
                'question_en' => 'How do you find solutions in unexpected situations (running out of ingredients, equipment failure)?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Hızlı düşünme', 'Alternatif bulma', 'İletişim', 'Sakinlik'],
                'red_flag_indicators' => ['Panik', 'Çözüm üretememe'],
            ],
            [
                'question_tr' => 'Yemek sunumunda (plating) dikkat ettiğiniz noktalar nelerdir?',
                'question_en' => 'What points do you pay attention to in food presentation (plating)?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Estetik', 'Tutarlılık', 'Sıcaklık', 'Porsiyon'],
                'red_flag_indicators' => ['Sunuma önem vermeme', 'Tutarsızlık'],
            ],
        ]);

        // Şef (Manager)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_KITCHEN_CHEF',
            'name_tr' => 'Şef',
            'name_en' => 'Chef',
            'archetype' => 'MANAGER',
            'description_tr' => 'Mutfağı yöneten, menü geliştiren ve ekibi koordine eden pozisyon',
            'description_en' => 'Position managing the kitchen, developing menus and coordinating the team',
            'experience_min_years' => 5,
            'experience_max_years' => 10,
            'education_level' => 'associate',
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'ATTENTION_TO_DETAIL', 'TIME_MANAGEMENT'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Mutfak ekibinizi nasıl oluşturur ve yönetirsiniz?',
                'question_en' => 'How do you build and manage your kitchen team?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['İşe alım', 'Eğitim', 'Motivasyon', 'Performans yönetimi'],
                'red_flag_indicators' => ['Ekip yönetimi vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Menü geliştirme sürecinizi anlatır mısınız?',
                'question_en' => 'Can you describe your menu development process?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Yaratıcılık', 'Maliyet analizi', 'Trend takibi', 'Test süreci'],
                'red_flag_indicators' => ['Sistematik olmayan yaklaşım'],
            ],
            [
                'question_tr' => 'Gıda maliyetlerini nasıl kontrol edersiniz?',
                'question_en' => 'How do you control food costs?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Maliyet analizi', 'Porsiyon kontrolü', 'Fire azaltma', 'Tedarikçi yönetimi'],
                'red_flag_indicators' => ['Maliyet bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Yoğun servis saatlerinde mutfak koordinasyonunu nasıl sağlarsınız?',
                'question_en' => 'How do you ensure kitchen coordination during busy service hours?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'situational',
                'expected_indicators' => ['Liderlik', 'İletişim', 'Kriz yönetimi', 'Zamanlama'],
                'red_flag_indicators' => ['Kontrol kaybı', 'Stres yönetimi eksikliği'],
            ],
            [
                'question_tr' => 'Gıda güvenliği ve hijyen standartlarını ekibinize nasıl benimsetirsiniz?',
                'question_en' => 'How do you instill food safety and hygiene standards in your team?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Eğitim', 'Denetim', 'Örnek olma', 'Süreç'],
                'red_flag_indicators' => ['Standart gevşekliği'],
            ],
            [
                'question_tr' => 'Mutfakta bir kriz anında (yangın, kaza vb.) nasıl hareket edersiniz?',
                'question_en' => 'How do you act during a crisis (fire, accident, etc.) in the kitchen?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Soğukkanlılık', 'Prosedür bilgisi', 'Hızlı karar', 'İletişim'],
                'red_flag_indicators' => ['Panik', 'Prosedür bilmeme'],
            ],
            [
                'question_tr' => 'Tedarikçilerle ilişkilerinizi nasıl yönetirsiniz?',
                'question_en' => 'How do you manage your relationships with suppliers?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Müzakere', 'Kalite takibi', 'İlişki yönetimi', 'Alternatif kaynak'],
                'red_flag_indicators' => ['Tedarikçi yönetimi deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Mutfak ekibinizin gelişimi için neler yaparsınız?',
                'question_en' => 'What do you do for the development of your kitchen team?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Eğitim programları', 'Mentorluk', 'Kariyer planlaması', 'Geri bildirim'],
                'red_flag_indicators' => ['Gelişim vizyonu eksikliği'],
            ],
        ]);

        // Baş Aşçı / Executive Chef (Leader)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_KITCHEN_EXEC_CHEF',
            'name_tr' => 'Baş Aşçı / Executive Chef',
            'name_en' => 'Executive Chef',
            'archetype' => 'LEADER',
            'description_tr' => 'Birden fazla mutfağı veya lokasyonu yöneten, stratejik kararlar alan üst düzey şef',
            'description_en' => 'Senior chef managing multiple kitchens or locations and making strategic decisions',
            'experience_min_years' => 8,
            'experience_max_years' => 15,
            'education_level' => 'bachelor',
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'ADAPTABILITY', 'COMMUNICATION'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Birden fazla lokasyonu veya mutfağı nasıl yönetirsiniz?',
                'question_en' => 'How do you manage multiple locations or kitchens?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Standartlaştırma', 'Delegasyon', 'Kontrol mekanizmaları', 'Ekip geliştirme'],
                'red_flag_indicators' => ['Mikro yönetim', 'Ölçeklendirme vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Mutfak stratejinizi işletme hedefleriyle nasıl uyumlu hale getirirsiniz?',
                'question_en' => 'How do you align your kitchen strategy with business goals?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['İş anlayışı', 'Finansal okuryazarlık', 'Strateji', 'KPI belirleme'],
                'red_flag_indicators' => ['Sadece mutfak odaklılık'],
            ],
            [
                'question_tr' => 'Gastronomik trendleri nasıl takip eder ve uygularsınız?',
                'question_en' => 'How do you follow and apply gastronomic trends?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sürekli öğrenme', 'Seyahat', 'Networking', 'İnovasyon'],
                'red_flag_indicators' => ['Gelenekselcilik', 'Değişime direnç'],
            ],
            [
                'question_tr' => 'Üst yönetimle ve diğer departmanlarla nasıl iletişim kurarsınız?',
                'question_en' => 'How do you communicate with upper management and other departments?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Profesyonel iletişim', 'Raporlama', 'İşbirliği', 'Sunum'],
                'red_flag_indicators' => ['İzole çalışma', 'İletişim sorunları'],
            ],
            [
                'question_tr' => 'Genç şefleri nasıl yetiştirirsiniz?',
                'question_en' => 'How do you train young chefs?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Mentorluk', 'Kariyer yolu', 'Fırsat sunma', 'Geri bildirim'],
                'red_flag_indicators' => ['Gelişim vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Marka kimliği ve mutfak konseptini nasıl oluşturursunuz?',
                'question_en' => 'How do you create brand identity and kitchen concept?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Yaratıcılık', 'Pazar anlayışı', 'Tutarlılık', 'Farklılaşma'],
                'red_flag_indicators' => ['Konsept oluşturma deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Bir restoranın açılış sürecini baştan sona nasıl yönetirsiniz?',
                'question_en' => 'How do you manage a restaurant opening process from start to finish?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Proje yönetimi', 'Detay planlama', 'Ekip kurma', 'Zamanlama'],
                'red_flag_indicators' => ['Açılış deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Mutfakta sürdürülebilirlik konusunda ne gibi uygulamalarınız var?',
                'question_en' => 'What sustainability practices do you have in the kitchen?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Atık azaltma', 'Yerel tedarik', 'Mevsimsellik', 'Çevresel bilinç'],
                'red_flag_indicators' => ['Sürdürülebilirlik bilinci eksikliği'],
            ],
        ]);
    }

    private function seedServicePositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'FB_SERVICE')->first();
        if (!$subdomain) return;

        // Garson (Entry)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_SERVICE_WAITER',
            'name_tr' => 'Garson',
            'name_en' => 'Waiter/Waitress',
            'archetype' => 'ENTRY',
            'description_tr' => 'Müşteri siparişlerini alan, servis yapan ve misafir memnuniyetini sağlayan pozisyon',
            'description_en' => 'Position taking customer orders, serving and ensuring guest satisfaction',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['CUSTOMER_FOCUS', 'COMMUNICATION', 'TEAMWORK', 'RELIABILITY'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'İyi bir garson olmanın en önemli özellikleri sizce nelerdir?',
                'question_en' => 'What do you think are the most important qualities of a good waiter?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Müşteri odaklılık', 'Dikkat', 'Hız', 'Güler yüz'],
                'red_flag_indicators' => ['Yüzeysel cevap', 'Müşteri bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Masasında bekleyen bir müşteriye nasıl yaklaşırsınız?',
                'question_en' => 'How do you approach a customer waiting at their table?',
                'competency' => 'COMMUNICATION',
                'type' => 'situational',
                'expected_indicators' => ['Hızlı karşılama', 'Göz teması', 'Güler yüz', 'İlgi'],
                'red_flag_indicators' => ['Görmezden gelme', 'Geç karşılama'],
            ],
            [
                'question_tr' => 'Menüyü müşteriye nasıl tanıtırsınız?',
                'question_en' => 'How do you introduce the menu to a customer?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Menü bilgisi', 'Öneri sunma', 'Alerjen bilgisi', 'Dinleme'],
                'red_flag_indicators' => ['Menü bilgisizliği', 'İlgisizlik'],
            ],
            [
                'question_tr' => 'Yanlış sipariş geldiğinde nasıl tepki verirsiniz?',
                'question_en' => 'How do you react when a wrong order arrives?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Özür', 'Hızlı çözüm', 'Mutfakla iletişim', 'Telafi'],
                'red_flag_indicators' => ['Savunmacılık', 'Müşteriyi suçlama'],
            ],
            [
                'question_tr' => 'Yoğun saatlerde birden fazla masaya nasıl hizmet edersiniz?',
                'question_en' => 'How do you serve multiple tables during busy hours?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'situational',
                'expected_indicators' => ['Önceliklendirme', 'Verimlilik', 'Ekip desteği', 'Sakinlik'],
                'red_flag_indicators' => ['Panik', 'Masaları ihmal'],
            ],
            [
                'question_tr' => 'Şikayetçi bir müşteriyle nasıl başa çıkarsınız?',
                'question_en' => 'How do you handle a complaining customer?',
                'competency' => 'COMMUNICATION',
                'type' => 'behavioral',
                'expected_indicators' => ['Dinleme', 'Empati', 'Çözüm sunma', 'Yönetici desteği'],
                'red_flag_indicators' => ['Tartışma', 'Savunmacılık'],
            ],
            [
                'question_tr' => 'Mutfak ve servis arasındaki koordinasyonu nasıl sağlarsınız?',
                'question_en' => 'How do you ensure coordination between kitchen and service?',
                'competency' => 'TEAMWORK',
                'type' => 'experience',
                'expected_indicators' => ['İletişim', 'Zamanlama', 'Takip', 'İşbirliği'],
                'red_flag_indicators' => ['İzole çalışma'],
            ],
            [
                'question_tr' => 'Kasa işlemleri ve hesap ödemesi konusundaki deneyiminiz nedir?',
                'question_en' => 'What is your experience with cash operations and bill payments?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Kasa bilgisi', 'Dikkat', 'Dürüstlük'],
                'red_flag_indicators' => ['Kasa deneyimi olmasa bile öğrenme isteği olmalı'],
            ],
        ]);

        // Kaptan / Şef Garson (Coordinator)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_SERVICE_CAPTAIN',
            'name_tr' => 'Kaptan / Şef Garson',
            'name_en' => 'Captain / Head Waiter',
            'archetype' => 'COORDINATOR',
            'description_tr' => 'Servis ekibini koordine eden, VIP misafirlere hizmet veren deneyimli servis personeli',
            'description_en' => 'Experienced service staff coordinating the service team and serving VIP guests',
            'experience_min_years' => 3,
            'experience_max_years' => 6,
            'education_level' => 'high_school',
            'competencies' => ['LEADERSHIP', 'CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Servis ekibini bir vardiya boyunca nasıl koordine edersiniz?',
                'question_en' => 'How do you coordinate the service team throughout a shift?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Görev dağılımı', 'İletişim', 'Takip', 'Destek'],
                'red_flag_indicators' => ['Koordinasyon vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'VIP misafirlere nasıl hizmet verirsiniz?',
                'question_en' => 'How do you serve VIP guests?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Kişiselleştirilmiş hizmet', 'Önceden hazırlık', 'Detay', 'Takip'],
                'red_flag_indicators' => ['VIP hizmet anlayışı eksikliği'],
            ],
            [
                'question_tr' => 'Yeni garsonları nasıl eğitirsiniz?',
                'question_en' => 'How do you train new waiters?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Sistematik eğitim', 'Takip', 'Geri bildirim', 'Sabır'],
                'red_flag_indicators' => ['Eğitim vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Servis sırasında bir sorun çıktığında (dökülen yemek, kırılan tabak vb.) nasıl müdahale edersiniz?',
                'question_en' => 'How do you intervene when a problem occurs during service?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Hızlı müdahale', 'Sakinlik', 'Müşteri yönetimi', 'Çözüm'],
                'red_flag_indicators' => ['Panik', 'Suçlama'],
            ],
            [
                'question_tr' => 'Rezervasyon ve masa planlamasını nasıl yönetirsiniz?',
                'question_en' => 'How do you manage reservations and table planning?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Planlama', 'Verimlilik', 'Özel istekler', 'Esneklik'],
                'red_flag_indicators' => ['Planlama deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Mutfakla yaşanan gecikme veya sorunları misafire nasıl iletirsiniz?',
                'question_en' => 'How do you communicate kitchen delays or problems to guests?',
                'competency' => 'COMMUNICATION',
                'type' => 'situational',
                'expected_indicators' => ['Dürüstlük', 'Profesyonellik', 'Telafi önerileri', 'Sakinlik'],
                'red_flag_indicators' => ['Mutfağı suçlama', 'İletişim kopukluğu'],
            ],
            [
                'question_tr' => 'Özel etkinlik veya grup yemeği organizasyonunda göreviniz nedir?',
                'question_en' => 'What is your role in special events or group dinner organization?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Planlama', 'Koordinasyon', 'Detay takibi', 'İletişim'],
                'red_flag_indicators' => ['Etkinlik deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Servis kalitesini nasıl ölçer ve iyileştirirsiniz?',
                'question_en' => 'How do you measure and improve service quality?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Geri bildirim toplama', 'Gözlem', 'Eğitim', 'Standartlar'],
                'red_flag_indicators' => ['Kalite bilinci eksikliği'],
            ],
        ]);

        // Restoran Şefi / F&B Supervisor (Manager)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_SERVICE_SUPERVISOR',
            'name_tr' => 'Restoran Şefi / F&B Supervisor',
            'name_en' => 'Restaurant Supervisor / F&B Supervisor',
            'archetype' => 'MANAGER',
            'description_tr' => 'Tüm servis operasyonlarını yöneten, personeli denetleyen ve misafir deneyiminden sorumlu yönetici',
            'description_en' => 'Manager overseeing all service operations, supervising staff and responsible for guest experience',
            'experience_min_years' => 5,
            'experience_max_years' => 8,
            'education_level' => 'associate',
            'competencies' => ['LEADERSHIP', 'CUSTOMER_FOCUS', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Servis ekibinizin performansını nasıl değerlendirirsiniz?',
                'question_en' => 'How do you evaluate your service team\'s performance?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['KPI belirleme', 'Gözlem', 'Geri bildirim', 'Gelişim planı'],
                'red_flag_indicators' => ['Performans yönetimi vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Misafir memnuniyetini artırmak için hangi stratejileri uygularsınız?',
                'question_en' => 'What strategies do you apply to increase guest satisfaction?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Stratejik düşünce', 'Veri kullanımı', 'İnovasyon', 'Eğitim'],
                'red_flag_indicators' => ['Yüzeysel yaklaşım'],
            ],
            [
                'question_tr' => 'Personel planlaması ve vardiya yönetimini nasıl yaparsınız?',
                'question_en' => 'How do you handle staff planning and shift management?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Yoğunluk analizi', 'Adil dağılım', 'Maliyet bilinci', 'Esneklik'],
                'red_flag_indicators' => ['Planlama deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Servis maliyetlerini nasıl kontrol edersiniz?',
                'question_en' => 'How do you control service costs?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Maliyet analizi', 'Verimlilik', 'Fire kontrolü', 'Personel optimizasyonu'],
                'red_flag_indicators' => ['Maliyet bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Ciddi bir misafir şikayetini nasıl yönetirsiniz?',
                'question_en' => 'How do you manage a serious guest complaint?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Dinleme', 'Empati', 'Hızlı çözüm', 'Takip', 'Telafi'],
                'red_flag_indicators' => ['Savunmacılık', 'Çözümsüzlük'],
            ],
            [
                'question_tr' => 'Mutfak ile servis arasındaki koordinasyonu nasıl sağlarsınız?',
                'question_en' => 'How do you ensure coordination between kitchen and service?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['İletişim kanalları', 'Toplantılar', 'Sorun çözümü', 'İşbirliği'],
                'red_flag_indicators' => ['Departmanlar arası çatışma'],
            ],
            [
                'question_tr' => 'Özel etkinlik ve ziyafet organizasyonlarını nasıl yönetirsiniz?',
                'question_en' => 'How do you manage special events and banquet organizations?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Proje yönetimi', 'Detaylı planlama', 'Koordinasyon', 'Risk yönetimi'],
                'red_flag_indicators' => ['Etkinlik yönetimi deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Servis ekibinizi nasıl motive edersiniz?',
                'question_en' => 'How do you motivate your service team?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Takdir', 'Hedef belirleme', 'Gelişim fırsatları', 'İletişim'],
                'red_flag_indicators' => ['Motivasyon stratejisi eksikliği'],
            ],
        ]);
    }

    private function seedBaristaPositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'FB_BARISTA')->first();
        if (!$subdomain) return;

        // Barista (Entry)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_BARISTA',
            'name_tr' => 'Barista',
            'name_en' => 'Barista',
            'archetype' => 'ENTRY',
            'description_tr' => 'Kahve ve içecek hazırlayan, müşteri hizmeti sunan pozisyon',
            'description_en' => 'Position preparing coffee and beverages and providing customer service',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['CUSTOMER_FOCUS', 'ATTENTION_TO_DETAIL', 'COMMUNICATION', 'RELIABILITY'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Kahve çeşitleri ve hazırlama yöntemleri hakkında neler biliyorsunuz?',
                'question_en' => 'What do you know about coffee types and preparation methods?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Espresso bazlı içecekler', 'Demleme yöntemleri', 'Süt köpürtme'],
                'red_flag_indicators' => ['Temel bilgi eksikliği kabul edilebilir ama öğrenme isteği olmalı'],
            ],
            [
                'question_tr' => 'Müşteriye kahve önerirken neleri göz önünde bulundurursunuz?',
                'question_en' => 'What do you consider when recommending coffee to a customer?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Tercih sorgulama', 'Tat profili', 'Alternatif sunma'],
                'red_flag_indicators' => ['İlgisizlik', 'Standart öneri'],
            ],
            [
                'question_tr' => 'Yoğun saatlerde sipariş sırasını nasıl yönetirsiniz?',
                'question_en' => 'How do you manage order sequence during busy hours?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'situational',
                'expected_indicators' => ['Önceliklendirme', 'Hız', 'Kalite dengesi', 'İletişim'],
                'red_flag_indicators' => ['Panik', 'Kaliteden ödün'],
            ],
            [
                'question_tr' => 'Latte art yapabilir misiniz? Tekniğinizi anlatır mısınız?',
                'question_en' => 'Can you do latte art? Can you describe your technique?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Teknik bilgi', 'Pratik', 'Gelişim isteği'],
                'red_flag_indicators' => ['Yapamasa bile öğrenme isteği olmalı'],
            ],
            [
                'question_tr' => 'Espresso makinesi ve diğer ekipmanların bakımını nasıl yaparsınız?',
                'question_en' => 'How do you maintain the espresso machine and other equipment?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Günlük temizlik', 'Bakım prosedürleri', 'Sorun tespiti'],
                'red_flag_indicators' => ['Ekipman bilgisi eksikliği'],
            ],
            [
                'question_tr' => 'Müşteri siparişinde hata yaptığınızda nasıl davranırsınız?',
                'question_en' => 'How do you act when you make a mistake in a customer order?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Özür', 'Hızlı düzeltme', 'Pozitif tutum'],
                'red_flag_indicators' => ['Savunmacılık', 'Suçlama'],
            ],
            [
                'question_tr' => 'Kahve dışındaki içecekler (çay, smoothie vb.) konusundaki bilginiz nedir?',
                'question_en' => 'What is your knowledge about beverages other than coffee?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Geniş içecek bilgisi', 'Öğrenme isteği'],
                'red_flag_indicators' => ['Sadece kahve odaklılık'],
            ],
            [
                'question_tr' => 'Neden barista olmak istiyorsunuz?',
                'question_en' => 'Why do you want to be a barista?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Kahveye ilgi', 'Müşteri hizmeti', 'Kariyer hedefi'],
                'red_flag_indicators' => ['Geçici iş arayışı', 'İlgisizlik'],
            ],
        ]);

        // Kıdemli Barista (Specialist)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_BARISTA_SENIOR',
            'name_tr' => 'Kıdemli Barista',
            'name_en' => 'Senior Barista',
            'archetype' => 'SPECIALIST',
            'description_tr' => 'Kahve uzmanlığı olan, eğitim veren ve kalite standartlarını belirleyen deneyimli barista',
            'description_en' => 'Experienced barista with coffee expertise, providing training and setting quality standards',
            'experience_min_years' => 2,
            'experience_max_years' => 5,
            'education_level' => 'high_school',
            'competencies' => ['ATTENTION_TO_DETAIL', 'LEADERSHIP', 'CUSTOMER_FOCUS', 'PROBLEM_SOLVING'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Kahve çekirdekleri, kavurma profilleri ve demleme değişkenleri hakkında bilginizi paylaşır mısınız?',
                'question_en' => 'Can you share your knowledge about coffee beans, roasting profiles and brewing variables?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Derinlikli bilgi', 'Teknik terminoloji', 'Pratik uygulama'],
                'red_flag_indicators' => ['Yüzeysel bilgi'],
            ],
            [
                'question_tr' => 'Yeni baristalara nasıl eğitim verirsiniz?',
                'question_en' => 'How do you train new baristas?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Sistematik eğitim', 'Pratik ağırlıklı', 'Geri bildirim', 'Sabır'],
                'red_flag_indicators' => ['Eğitim deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Kahve kalitesini nasıl değerlendirirsiniz?',
                'question_en' => 'How do you evaluate coffee quality?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Tat analizi', 'Görsel değerlendirme', 'Tutarlılık', 'Standartlar'],
                'red_flag_indicators' => ['Kalite bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Espresso makinesi sorun çıkardığında ilk ne yaparsınız?',
                'question_en' => 'What do you do first when the espresso machine has problems?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Tanılama', 'Temel sorun giderme', 'Teknik destek', 'Alternatif çözüm'],
                'red_flag_indicators' => ['Teknik bilgi eksikliği'],
            ],
            [
                'question_tr' => 'Menü geliştirmede rolünüz var mı? Yeni içecek oluşturma sürecinizi anlatın.',
                'question_en' => 'Do you have a role in menu development? Describe your process for creating new beverages.',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Yaratıcılık', 'Test süreci', 'Maliyet bilinci', 'Müşteri odaklılık'],
                'red_flag_indicators' => ['Yaratıcılık eksikliği'],
            ],
            [
                'question_tr' => 'Kahve tedarikçileriyle nasıl çalışırsınız?',
                'question_en' => 'How do you work with coffee suppliers?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['İlişki yönetimi', 'Kalite değerlendirmesi', 'Müzakere'],
                'red_flag_indicators' => ['Tedarikçi deneyimi eksikliği kabul edilebilir'],
            ],
            [
                'question_tr' => 'Kahve trendlerini nasıl takip edersiniz?',
                'question_en' => 'How do you follow coffee trends?',
                'competency' => 'LEARNING_AGILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sürekli öğrenme', 'Networking', 'Deneyimleme'],
                'red_flag_indicators' => ['Trend takipsizliği'],
            ],
            [
                'question_tr' => 'Müşteri deneyimini iyileştirmek için neler önerirsiniz?',
                'question_en' => 'What would you suggest to improve customer experience?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['İnovasyon', 'Gözlem', 'Geri bildirim analizi', 'Pratik öneriler'],
                'red_flag_indicators' => ['Öneri üretememe'],
            ],
        ]);

        // Barmen (Entry-Specialist)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_BAR_BARTENDER',
            'name_tr' => 'Barmen',
            'name_en' => 'Bartender',
            'archetype' => 'SPECIALIST',
            'description_tr' => 'Kokteyl ve içecek hazırlayan, bar operasyonlarını yöneten pozisyon',
            'description_en' => 'Position preparing cocktails and beverages and managing bar operations',
            'experience_min_years' => 1,
            'experience_max_years' => 4,
            'education_level' => 'high_school',
            'competencies' => ['CUSTOMER_FOCUS', 'ATTENTION_TO_DETAIL', 'COMMUNICATION', 'TIME_MANAGEMENT'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Klasik kokteyller ve hazırlama teknikleri hakkında bilginizi paylaşır mısınız?',
                'question_en' => 'Can you share your knowledge about classic cocktails and preparation techniques?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Kokteyl bilgisi', 'Teknikler', 'Ölçüler', 'Sunum'],
                'red_flag_indicators' => ['Temel bilgi eksikliği'],
            ],
            [
                'question_tr' => 'Yoğun bir barda birden fazla siparişi nasıl yönetirsiniz?',
                'question_en' => 'How do you manage multiple orders in a busy bar?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'situational',
                'expected_indicators' => ['Önceliklendirme', 'Hız', 'Organizasyon', 'Sakinlik'],
                'red_flag_indicators' => ['Panik', 'Organizasyon eksikliği'],
            ],
            [
                'question_tr' => 'Alkollü içecek servisi konusunda yasal sorumluluklar hakkında neler biliyorsunuz?',
                'question_en' => 'What do you know about legal responsibilities regarding alcoholic beverage service?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Yaş kontrolü', 'Sarhoş müşteri yönetimi', 'Yasal düzenlemeler'],
                'red_flag_indicators' => ['Yasal bilinç eksikliği'],
            ],
            [
                'question_tr' => 'Müşteriye içecek önerirken nasıl bir yaklaşım sergilersiniz?',
                'question_en' => 'What approach do you take when recommending drinks to customers?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Tercih sorgulama', 'Tat profili', 'Kişiselleştirme'],
                'red_flag_indicators' => ['Standart öneri', 'İlgisizlik'],
            ],
            [
                'question_tr' => 'Bar stok yönetimi ve sipariş konusundaki deneyiminiz nedir?',
                'question_en' => 'What is your experience with bar stock management and ordering?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Stok takibi', 'Par level', 'Fire kontrolü', 'Sipariş planlaması'],
                'red_flag_indicators' => ['Stok yönetimi bilgisi eksikliği'],
            ],
            [
                'question_tr' => 'Zor veya sarhoş bir müşteriyle nasıl başa çıkarsınız?',
                'question_en' => 'How do you deal with a difficult or intoxicated customer?',
                'competency' => 'COMMUNICATION',
                'type' => 'situational',
                'expected_indicators' => ['Sakinlik', 'Profesyonellik', 'Güvenlik', 'Yönetici desteği'],
                'red_flag_indicators' => ['Çatışmacı tutum', 'Güvenlik bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Yeni kokteyl tarifleri oluşturma konusundaki deneyiminiz nedir?',
                'question_en' => 'What is your experience in creating new cocktail recipes?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Yaratıcılık', 'Tat dengesi', 'Maliyet bilinci', 'Sunum'],
                'red_flag_indicators' => ['Yaratıcılık eksikliği kabul edilebilir'],
            ],
            [
                'question_tr' => 'Bar alanı temizliği ve hijyen konusunda nasıl bir rutin izlersiniz?',
                'question_en' => 'What routine do you follow for bar area cleanliness and hygiene?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Günlük temizlik', 'Hijyen standartları', 'Organizasyon'],
                'red_flag_indicators' => ['Hijyen bilinci eksikliği'],
            ],
        ]);
    }

    private function seedManagementPositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'FB_MANAGEMENT')->first();
        if (!$subdomain) return;

        // Vardiya Müdürü (Coordinator)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_MGMT_SHIFT_MANAGER',
            'name_tr' => 'Vardiya Müdürü',
            'name_en' => 'Shift Manager',
            'archetype' => 'COORDINATOR',
            'description_tr' => 'Vardiya süresince restoran operasyonlarını yöneten, personeli koordine eden pozisyon',
            'description_en' => 'Position managing restaurant operations during shift and coordinating staff',
            'experience_min_years' => 2,
            'experience_max_years' => 4,
            'education_level' => 'high_school',
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT', 'CUSTOMER_FOCUS'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Vardiya boyunca ekibi nasıl organize eder ve yönetirsiniz?',
                'question_en' => 'How do you organize and manage the team throughout the shift?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Görev dağılımı', 'İletişim', 'Takip', 'Destek'],
                'red_flag_indicators' => ['Organizasyon eksikliği'],
            ],
            [
                'question_tr' => 'Vardiya sırasında bir personel gelmediğinde nasıl hareket edersiniz?',
                'question_en' => 'How do you act when a staff member doesn\'t show up during your shift?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Hızlı çözüm', 'Yedek arama', 'Görev yeniden dağıtımı', 'İletişim'],
                'red_flag_indicators' => ['Çözümsüzlük', 'Panik'],
            ],
            [
                'question_tr' => 'Açılış ve kapanış prosedürlerini nasıl yönetirsiniz?',
                'question_en' => 'How do you manage opening and closing procedures?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Checklist kullanımı', 'Kontrol', 'Güvenlik', 'Raporlama'],
                'red_flag_indicators' => ['Prosedür bilgisi eksikliği'],
            ],
            [
                'question_tr' => 'Müşteri şikayetlerini vardiya sırasında nasıl yönetirsiniz?',
                'question_en' => 'How do you manage customer complaints during your shift?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Dinleme', 'Empati', 'Hızlı çözüm', 'Yetki kullanımı'],
                'red_flag_indicators' => ['Savunmacılık', 'Yetkisiz davranma'],
            ],
            [
                'question_tr' => 'Vardiya performansını nasıl raporlarsınız?',
                'question_en' => 'How do you report shift performance?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Satış verileri', 'Olaylar', 'Personel durumu', 'Öneriler'],
                'red_flag_indicators' => ['Raporlama deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Kasa açıkları veya güvenlik sorunlarıyla nasıl başa çıkarsınız?',
                'question_en' => 'How do you handle cash discrepancies or security issues?',
                'competency' => 'RELIABILITY',
                'type' => 'situational',
                'expected_indicators' => ['Prosedür bilgisi', 'Soruşturma', 'Raporlama', 'Önlem'],
                'red_flag_indicators' => ['Güvenlik bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Yoğun saatlerde mutfak ve servis koordinasyonunu nasıl sağlarsınız?',
                'question_en' => 'How do you ensure kitchen and service coordination during busy hours?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'situational',
                'expected_indicators' => ['İletişim', 'Sorun çözümü', 'Hızlı karar', 'Dengeleme'],
                'red_flag_indicators' => ['Koordinasyon zorluğu'],
            ],
            [
                'question_tr' => 'Ekip motivasyonunu vardiya boyunca nasıl yüksek tutarsınız?',
                'question_en' => 'How do you keep team motivation high throughout the shift?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Pozitif tutum', 'Takdir', 'Destek', 'Örnek olma'],
                'red_flag_indicators' => ['Motivasyon stratejisi eksikliği'],
            ],
        ]);

        // Restoran Müdürü (Manager)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_MGMT_RESTAURANT_MANAGER',
            'name_tr' => 'Restoran Müdürü',
            'name_en' => 'Restaurant Manager',
            'archetype' => 'MANAGER',
            'description_tr' => 'Restoranın tüm operasyonlarından, personelden ve finansal performanstan sorumlu yönetici',
            'description_en' => 'Manager responsible for all restaurant operations, staff and financial performance',
            'experience_min_years' => 5,
            'experience_max_years' => 8,
            'education_level' => 'bachelor',
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT', 'CUSTOMER_FOCUS'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Restoran karlılığını artırmak için hangi stratejileri uygularsınız?',
                'question_en' => 'What strategies do you apply to increase restaurant profitability?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Gelir artırma', 'Maliyet kontrolü', 'Verimlilik', 'Analiz'],
                'red_flag_indicators' => ['Finansal bilgi eksikliği'],
            ],
            [
                'question_tr' => 'Personel işe alım ve eğitim süreçlerinizi anlatır mısınız?',
                'question_en' => 'Can you describe your staff recruitment and training processes?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Sistematik işe alım', 'Oryantasyon', 'Sürekli eğitim', 'Performans takibi'],
                'red_flag_indicators' => ['İK vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Müşteri memnuniyetini nasıl ölçer ve geliştirirsiniz?',
                'question_en' => 'How do you measure and improve customer satisfaction?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'experience',
                'expected_indicators' => ['Geri bildirim toplama', 'Analiz', 'Aksiyon alma', 'Takip'],
                'red_flag_indicators' => ['Müşteri odaklılık eksikliği'],
            ],
            [
                'question_tr' => 'Gıda ve işçilik maliyetlerini nasıl kontrol edersiniz?',
                'question_en' => 'How do you control food and labor costs?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Maliyet analizi', 'Porsiyon kontrolü', 'Vardiya planlaması', 'Fire azaltma'],
                'red_flag_indicators' => ['Maliyet bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Mutfak ve servis ekipleri arasındaki çatışmaları nasıl çözersiniz?',
                'question_en' => 'How do you resolve conflicts between kitchen and service teams?',
                'competency' => 'COMMUNICATION',
                'type' => 'situational',
                'expected_indicators' => ['Arabuluculuk', 'Tarafsızlık', 'Ortak hedef', 'Süreç iyileştirme'],
                'red_flag_indicators' => ['Çatışma yönetimi eksikliği'],
            ],
            [
                'question_tr' => 'Yeni menü veya konsept lansmanını nasıl yönetirsiniz?',
                'question_en' => 'How do you manage a new menu or concept launch?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Proje yönetimi', 'Eğitim', 'Pazarlama', 'Performans takibi'],
                'red_flag_indicators' => ['Lansman deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Üst yönetimle ve diğer departmanlarla nasıl iletişim kurarsınız?',
                'question_en' => 'How do you communicate with upper management and other departments?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Düzenli raporlama', 'Proaktif iletişim', 'Veri odaklılık'],
                'red_flag_indicators' => ['İletişim kopukluğu'],
            ],
            [
                'question_tr' => 'Restoranın yerel pazarda rekabet gücünü nasıl artırırsınız?',
                'question_en' => 'How do you increase the restaurant\'s competitiveness in the local market?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Rekabet analizi', 'Farklılaşma', 'Pazarlama', 'Müşteri odaklılık'],
                'red_flag_indicators' => ['Stratejik düşünce eksikliği'],
            ],
        ]);

        // F&B Müdürü (Leader)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_MGMT_FB_DIRECTOR',
            'name_tr' => 'F&B Müdürü / Direktörü',
            'name_en' => 'F&B Director',
            'archetype' => 'LEADER',
            'description_tr' => 'Birden fazla F&B operasyonunu yöneten, strateji belirleyen ve bütçeden sorumlu üst düzey yönetici',
            'description_en' => 'Senior executive managing multiple F&B operations, setting strategy and responsible for budget',
            'experience_min_years' => 8,
            'experience_max_years' => 15,
            'education_level' => 'bachelor',
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'ADAPTABILITY', 'COMMUNICATION'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Birden fazla F&B lokasyonunu nasıl yönetir ve standartları nasıl korursunuz?',
                'question_en' => 'How do you manage multiple F&B locations and maintain standards?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Standartlaştırma', 'Kontrol mekanizmaları', 'Ekip geliştirme', 'Delegasyon'],
                'red_flag_indicators' => ['Ölçeklendirme vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'F&B stratejinizi işletme hedefleriyle nasıl uyumlu hale getirirsiniz?',
                'question_en' => 'How do you align your F&B strategy with business objectives?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'experience',
                'expected_indicators' => ['Stratejik planlama', 'Finansal hedefler', 'KPI belirleme', 'Yönetim kurulu raporlaması'],
                'red_flag_indicators' => ['Stratejik düşünce eksikliği'],
            ],
            [
                'question_tr' => 'F&B bütçesini nasıl oluşturur ve yönetirsiniz?',
                'question_en' => 'How do you create and manage the F&B budget?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Bütçe planlama', 'Varyans analizi', 'Maliyet kontrolü', 'Yatırım kararları'],
                'red_flag_indicators' => ['Finansal yönetim eksikliği'],
            ],
            [
                'question_tr' => 'Gastronomik trendleri nasıl takip eder ve işletmeye adapte edersiniz?',
                'question_en' => 'How do you follow gastronomic trends and adapt them to the business?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Trend analizi', 'İnovasyon', 'Test süreçleri', 'Müşteri odaklılık'],
                'red_flag_indicators' => ['Değişime direnç'],
            ],
            [
                'question_tr' => 'F&B ekibinizin gelişimi için ne tür programlar uygularsınız?',
                'question_en' => 'What programs do you implement for the development of your F&B team?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['Eğitim programları', 'Kariyer yolları', 'Mentorluk', 'Performans yönetimi'],
                'red_flag_indicators' => ['İK vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Yeni konsept veya restoran açılışını nasıl planlarsınız?',
                'question_en' => 'How do you plan a new concept or restaurant opening?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Proje yönetimi', 'Bütçeleme', 'Ekip kurma', 'Risk yönetimi'],
                'red_flag_indicators' => ['Açılış deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Tedarikçi ve partner ilişkilerini nasıl yönetirsiniz?',
                'question_en' => 'How do you manage supplier and partner relationships?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['Stratejik ortaklık', 'Müzakere', 'Kalite yönetimi', 'Uzun vadeli ilişki'],
                'red_flag_indicators' => ['İlişki yönetimi eksikliği'],
            ],
            [
                'question_tr' => 'F&B operasyonlarında sürdürülebilirlik konusunda ne gibi uygulamalarınız var?',
                'question_en' => 'What sustainability practices do you have in F&B operations?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Çevresel bilinç', 'Yerel tedarik', 'Atık azaltma', 'Sosyal sorumluluk'],
                'red_flag_indicators' => ['Sürdürülebilirlik bilinci eksikliği'],
            ],
        ]);
    }

    private function seedDeliveryPositions(): void
    {
        $subdomain = JobSubdomain::where('code', 'FB_DELIVERY')->first();
        if (!$subdomain) return;

        // Kurye (Entry)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_DELIVERY_COURIER',
            'name_tr' => 'Kurye',
            'name_en' => 'Delivery Courier',
            'archetype' => 'ENTRY',
            'description_tr' => 'Siparişleri müşterilere teslim eden, müşteri memnuniyetini sağlayan pozisyon',
            'description_en' => 'Position delivering orders to customers and ensuring customer satisfaction',
            'experience_min_years' => 0,
            'experience_max_years' => 2,
            'education_level' => 'high_school',
            'competencies' => ['RELIABILITY', 'CUSTOMER_FOCUS', 'ADAPTABILITY', 'TIME_MANAGEMENT'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Teslimat sırasında rota planlamasını nasıl yaparsınız?',
                'question_en' => 'How do you plan your route during deliveries?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Verimli rota', 'Trafik bilgisi', 'Zaman yönetimi', 'Teknoloji kullanımı'],
                'red_flag_indicators' => ['Plansızlık'],
            ],
            [
                'question_tr' => 'Müşteriye geç kaldığınızda nasıl davranırsınız?',
                'question_en' => 'How do you behave when you are late to a customer?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Özür', 'İletişim', 'Açıklama', 'Profesyonellik'],
                'red_flag_indicators' => ['Mazeret', 'İlgisizlik'],
            ],
            [
                'question_tr' => 'Teslimat sırasında bir sorunla karşılaştığınızda (adres bulamama, müşteri yok vb.) ne yaparsınız?',
                'question_en' => 'What do you do when you encounter a problem during delivery?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['İletişim', 'Çözüm arayışı', 'Raporlama', 'Sakinlik'],
                'red_flag_indicators' => ['Panik', 'İletişimsizlik'],
            ],
            [
                'question_tr' => 'Kötü hava koşullarında teslimat yapma konusundaki tutumunuz nedir?',
                'question_en' => 'What is your attitude about making deliveries in bad weather conditions?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Esneklik', 'Güvenlik bilinci', 'Pozitif tutum'],
                'red_flag_indicators' => ['Direnç', 'Şikayet'],
            ],
            [
                'question_tr' => 'Yiyecek güvenliği ve hijyen konusunda teslimat sırasında nelere dikkat edersiniz?',
                'question_en' => 'What do you pay attention to regarding food safety and hygiene during delivery?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Sıcaklık kontrolü', 'Ambalaj bütünlüğü', 'Temizlik'],
                'red_flag_indicators' => ['Gıda güvenliği bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Ehliyet ve araç durumunuz hakkında bilgi verir misiniz?',
                'question_en' => 'Can you provide information about your driver\'s license and vehicle status?',
                'competency' => 'RELIABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Geçerli ehliyet', 'Araç durumu', 'Sigorta'],
                'red_flag_indicators' => ['Ehliyet/araç sorunu'],
            ],
            [
                'question_tr' => 'Nakit ödeme aldığınızda nasıl bir prosedür izlersiniz?',
                'question_en' => 'What procedure do you follow when receiving cash payment?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Para sayma', 'Para üstü', 'Raporlama', 'Güvenlik'],
                'red_flag_indicators' => ['Prosedür bilgisi eksikliği'],
            ],
            [
                'question_tr' => 'Müşteri memnuniyetsizliği ile nasıl başa çıkarsınız?',
                'question_en' => 'How do you deal with customer dissatisfaction?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Dinleme', 'Empati', 'Yönlendirme', 'Profesyonellik'],
                'red_flag_indicators' => ['Tartışma', 'Savunmacılık'],
            ],
        ]);

        // Teslimat Koordinatörü (Coordinator)
        $position = $this->createPosition($subdomain, [
            'code' => 'FB_DELIVERY_COORDINATOR',
            'name_tr' => 'Teslimat Koordinatörü',
            'name_en' => 'Delivery Coordinator',
            'archetype' => 'COORDINATOR',
            'description_tr' => 'Teslimat operasyonlarını koordine eden, kuryeleri yöneten ve performansı takip eden pozisyon',
            'description_en' => 'Position coordinating delivery operations, managing couriers and tracking performance',
            'experience_min_years' => 2,
            'experience_max_years' => 5,
            'education_level' => 'high_school',
            'competencies' => ['LEADERSHIP', 'TIME_MANAGEMENT', 'PROBLEM_SOLVING', 'COMMUNICATION'],
        ]);

        $this->addQuestions($position, [
            [
                'question_tr' => 'Teslimat operasyonlarını nasıl planlayıp organize edersiniz?',
                'question_en' => 'How do you plan and organize delivery operations?',
                'competency' => 'TIME_MANAGEMENT',
                'type' => 'experience',
                'expected_indicators' => ['Rota optimizasyonu', 'Kaynak planlaması', 'Zaman yönetimi', 'Teknoloji kullanımı'],
                'red_flag_indicators' => ['Planlama deneyimi eksikliği'],
            ],
            [
                'question_tr' => 'Kurye ekibini nasıl yönetir ve motive edersiniz?',
                'question_en' => 'How do you manage and motivate the courier team?',
                'competency' => 'LEADERSHIP',
                'type' => 'experience',
                'expected_indicators' => ['İletişim', 'Performans takibi', 'Motivasyon', 'Destek'],
                'red_flag_indicators' => ['Ekip yönetimi vizyonu eksikliği'],
            ],
            [
                'question_tr' => 'Yoğun saatlerde teslimat taleplerini nasıl dengelirsiniz?',
                'question_en' => 'How do you balance delivery requests during busy hours?',
                'competency' => 'PROBLEM_SOLVING',
                'type' => 'situational',
                'expected_indicators' => ['Önceliklendirme', 'Kaynak yönetimi', 'İletişim', 'Hızlı karar'],
                'red_flag_indicators' => ['Kriz yönetimi eksikliği'],
            ],
            [
                'question_tr' => 'Teslimat performansını nasıl ölçer ve iyileştirirsiniz?',
                'question_en' => 'How do you measure and improve delivery performance?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['KPI takibi', 'Veri analizi', 'Süreç iyileştirme', 'Geri bildirim'],
                'red_flag_indicators' => ['Performans yönetimi eksikliği'],
            ],
            [
                'question_tr' => 'Müşteri şikayetlerini teslimat açısından nasıl yönetirsiniz?',
                'question_en' => 'How do you manage customer complaints regarding delivery?',
                'competency' => 'CUSTOMER_FOCUS',
                'type' => 'situational',
                'expected_indicators' => ['Araştırma', 'Çözüm', 'Önlem', 'İletişim'],
                'red_flag_indicators' => ['Müşteri odaklılık eksikliği'],
            ],
            [
                'question_tr' => 'Restoran ve teslimat ekibi arasındaki koordinasyonu nasıl sağlarsınız?',
                'question_en' => 'How do you ensure coordination between restaurant and delivery team?',
                'competency' => 'COMMUNICATION',
                'type' => 'experience',
                'expected_indicators' => ['İletişim kanalları', 'Zamanlama', 'Sorun çözümü'],
                'red_flag_indicators' => ['Koordinasyon zorluğu'],
            ],
            [
                'question_tr' => 'Teslimat maliyetlerini nasıl kontrol edersiniz?',
                'question_en' => 'How do you control delivery costs?',
                'competency' => 'ATTENTION_TO_DETAIL',
                'type' => 'experience',
                'expected_indicators' => ['Maliyet analizi', 'Verimlilik', 'Rota optimizasyonu'],
                'red_flag_indicators' => ['Maliyet bilinci eksikliği'],
            ],
            [
                'question_tr' => 'Üçüncü parti teslimat platformlarıyla nasıl çalışırsınız?',
                'question_en' => 'How do you work with third-party delivery platforms?',
                'competency' => 'ADAPTABILITY',
                'type' => 'experience',
                'expected_indicators' => ['Platform bilgisi', 'Koordinasyon', 'Performans takibi'],
                'red_flag_indicators' => ['Platform deneyimi eksikliği kabul edilebilir'],
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
                    'weight' => 10 - $index,
                    'is_critical' => $index === 0,
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
                    'is_mandatory' => $index < 3,
                    'is_active' => true,
                ]
            );
        }
    }
}
