<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\JobPosition;
use App\Models\JobSubdomain;
use App\Models\PositionQuestion;
use App\Models\RoleArchetype;
use Illuminate\Database\Seeder;

class HospitalityPositionsSeeder extends Seeder
{
    private array $archetypes = [];
    private array $competencies = [];

    public function run(): void
    {
        $this->archetypes = RoleArchetype::pluck('id', 'code')->toArray();
        $this->competencies = Competency::pluck('id', 'code')->toArray();

        $this->seedFrontOffice();
        $this->seedHousekeeping();
        $this->seedConcierge();
        $this->seedEvents();
        $this->seedTravel();
    }

    private function seedFrontOffice(): void
    {
        $subdomain = JobSubdomain::where('code', 'HOSP_FRONT')->first();
        if (!$subdomain) return;

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_FRONT_RECEPTIONIST',
            'name_tr' => 'Resepsiyonist',
            'name_en' => 'Receptionist',
            'archetype' => 'ENTRY',
            'experience_min' => 0, 'experience_max' => 2,
            'competencies' => ['CUSTOMER_FOCUS', 'COMMUNICATION', 'RELIABILITY', 'ADAPTABILITY'],
        ], [
            ['q_tr' => 'Misafir karşılama ve check-in sürecini nasıl yönetirsiniz?', 'q_en' => 'How do you manage guest greeting and check-in process?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Oda bulunamadığında misafire nasıl çözüm sunarsınız?', 'q_en' => 'How do you offer solutions when no room is available?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'situational'],
            ['q_tr' => 'Telefon ve yüz yüze iletişimde nelere dikkat edersiniz?', 'q_en' => 'What do you pay attention to in phone and face-to-face communication?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Yoğun check-in/check-out saatlerinde nasıl organize olursunuz?', 'q_en' => 'How do you organize during busy check-in/check-out hours?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'situational'],
            ['q_tr' => 'Misafir şikayetlerini nasıl ele alırsınız?', 'q_en' => 'How do you handle guest complaints?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'behavioral'],
            ['q_tr' => 'Otel yönetim sistemleri (PMS) hakkında deneyiminiz var mı?', 'q_en' => 'Do you have experience with hotel management systems (PMS)?', 'comp' => 'LEARNING_AGILITY', 'type' => 'experience'],
            ['q_tr' => 'Gece vardiyasında çalışma konusundaki tutumunuz nedir?', 'q_en' => 'What is your attitude about working night shifts?', 'comp' => 'ADAPTABILITY', 'type' => 'experience'],
            ['q_tr' => 'Misafir bilgi gizliliğini nasıl korursunuz?', 'q_en' => 'How do you protect guest information privacy?', 'comp' => 'RELIABILITY', 'type' => 'experience'],
        ]);

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_FRONT_SUPERVISOR',
            'name_tr' => 'Ön Büro Şefi',
            'name_en' => 'Front Office Supervisor',
            'archetype' => 'COORDINATOR',
            'experience_min' => 3, 'experience_max' => 6,
            'competencies' => ['LEADERSHIP', 'CUSTOMER_FOCUS', 'PROBLEM_SOLVING', 'COMMUNICATION'],
        ], [
            ['q_tr' => 'Ön büro ekibini nasıl yönetir ve motive edersiniz?', 'q_en' => 'How do you manage and motivate the front office team?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'VIP misafir hizmetlerini nasıl organize edersiniz?', 'q_en' => 'How do you organize VIP guest services?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Overbooking durumunda nasıl çözüm üretirsiniz?', 'q_en' => 'How do you find solutions in overbooking situations?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'situational'],
            ['q_tr' => 'Vardiya devirlerini nasıl yönetirsiniz?', 'q_en' => 'How do you manage shift handovers?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Gelir yönetimi (Revenue Management) hakkında bilginiz var mı?', 'q_en' => 'Do you have knowledge about Revenue Management?', 'comp' => 'LEARNING_AGILITY', 'type' => 'experience'],
            ['q_tr' => 'Yeni personel eğitimini nasıl organize edersiniz?', 'q_en' => 'How do you organize new staff training?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Misafir memnuniyet skorlarını nasıl iyileştirirsiniz?', 'q_en' => 'How do you improve guest satisfaction scores?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Diğer departmanlarla koordinasyonu nasıl sağlarsınız?', 'q_en' => 'How do you ensure coordination with other departments?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
        ]);

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_FRONT_MANAGER',
            'name_tr' => 'Ön Büro Müdürü',
            'name_en' => 'Front Office Manager',
            'archetype' => 'MANAGER',
            'experience_min' => 5, 'experience_max' => 10,
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'CUSTOMER_FOCUS', 'TIME_MANAGEMENT'],
        ], [
            ['q_tr' => 'Ön büro departmanının yıllık hedeflerini nasıl belirlersiniz?', 'q_en' => 'How do you set annual goals for the front office department?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Doluluk oranları ve gelir hedeflerini nasıl yönetirsiniz?', 'q_en' => 'How do you manage occupancy rates and revenue targets?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'experience'],
            ['q_tr' => 'Personel bütçesini ve vardiya planlamasını nasıl optimize edersiniz?', 'q_en' => 'How do you optimize staff budget and shift planning?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'experience'],
            ['q_tr' => 'Online Travel Agency (OTA) ilişkilerini nasıl yönetirsiniz?', 'q_en' => 'How do you manage Online Travel Agency (OTA) relationships?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Ciddi bir misafir şikayetini nasıl çözersiniz?', 'q_en' => 'How do you resolve a serious guest complaint?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'situational'],
            ['q_tr' => 'Teknoloji yatırımlarını nasıl değerlendirirsiniz?', 'q_en' => 'How do you evaluate technology investments?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'experience'],
            ['q_tr' => 'Ekibinizin kariyer gelişimini nasıl desteklersiniz?', 'q_en' => 'How do you support your team\'s career development?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Genel Müdür ile raporlama süreciniz nasıl işler?', 'q_en' => 'How does your reporting process with the General Manager work?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
        ]);
    }

    private function seedHousekeeping(): void
    {
        $subdomain = JobSubdomain::where('code', 'HOSP_HOUSEKEEP')->first();
        if (!$subdomain) return;

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_HOUSEKEEP_ATTENDANT',
            'name_tr' => 'Kat Görevlisi',
            'name_en' => 'Room Attendant',
            'archetype' => 'ENTRY',
            'experience_min' => 0, 'experience_max' => 2,
            'competencies' => ['ATTENTION_TO_DETAIL', 'RELIABILITY', 'TIME_MANAGEMENT', 'TEAMWORK'],
        ], [
            ['q_tr' => 'Bir odayı temizlerken hangi adımları takip edersiniz?', 'q_en' => 'What steps do you follow when cleaning a room?', 'comp' => 'ATTENTION_TO_DETAIL', 'type' => 'experience'],
            ['q_tr' => 'Günde kaç oda temizleyebilirsiniz?', 'q_en' => 'How many rooms can you clean per day?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'experience'],
            ['q_tr' => 'Misafir odasında şüpheli bir durum fark ettiğinizde ne yaparsınız?', 'q_en' => 'What do you do when you notice a suspicious situation in a guest room?', 'comp' => 'RELIABILITY', 'type' => 'situational'],
            ['q_tr' => 'Temizlik kimyasallarını güvenli kullanma konusunda bilginiz var mı?', 'q_en' => 'Do you have knowledge about safe use of cleaning chemicals?', 'comp' => 'ATTENTION_TO_DETAIL', 'type' => 'experience'],
            ['q_tr' => 'DND (Rahatsız Etmeyin) odasına nasıl yaklaşırsınız?', 'q_en' => 'How do you approach a DND (Do Not Disturb) room?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'situational'],
            ['q_tr' => 'Fiziksel olarak yorucu bir işte nasıl motive kalırsınız?', 'q_en' => 'How do you stay motivated in a physically demanding job?', 'comp' => 'ADAPTABILITY', 'type' => 'experience'],
            ['q_tr' => 'Misafir şikayeti alındığında nasıl tepki verirsiniz?', 'q_en' => 'How do you react when a guest complaint is received?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'situational'],
            ['q_tr' => 'Ekip arkadaşlarınızla iş paylaşımını nasıl yaparsınız?', 'q_en' => 'How do you share work with your team members?', 'comp' => 'TEAMWORK', 'type' => 'experience'],
        ]);

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_HOUSEKEEP_SUPERVISOR',
            'name_tr' => 'Kat Hizmetleri Şefi',
            'name_en' => 'Housekeeping Supervisor',
            'archetype' => 'COORDINATOR',
            'experience_min' => 2, 'experience_max' => 5,
            'competencies' => ['LEADERSHIP', 'ATTENTION_TO_DETAIL', 'TIME_MANAGEMENT', 'COMMUNICATION'],
        ], [
            ['q_tr' => 'Günlük oda atamalarını nasıl planlar ve dağıtırsınız?', 'q_en' => 'How do you plan and distribute daily room assignments?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'experience'],
            ['q_tr' => 'Oda kalite kontrollerini nasıl yaparsınız?', 'q_en' => 'How do you perform room quality checks?', 'comp' => 'ATTENTION_TO_DETAIL', 'type' => 'experience'],
            ['q_tr' => 'Ekibinizi nasıl eğitir ve gelişimlerini takip edersiniz?', 'q_en' => 'How do you train your team and track their development?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Ön büro ile koordinasyonu nasıl sağlarsınız?', 'q_en' => 'How do you ensure coordination with front office?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Stok ve malzeme yönetimini nasıl yaparsınız?', 'q_en' => 'How do you manage stock and supplies?', 'comp' => 'ATTENTION_TO_DETAIL', 'type' => 'experience'],
            ['q_tr' => 'VIP veya özel istek odalarını nasıl hazırlatırsınız?', 'q_en' => 'How do you prepare VIP or special request rooms?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Personel devamsızlığı durumunda nasıl hareket edersiniz?', 'q_en' => 'How do you act in case of staff absence?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'situational'],
            ['q_tr' => 'Kayıp eşya prosedürlerini nasıl yönetirsiniz?', 'q_en' => 'How do you manage lost and found procedures?', 'comp' => 'RELIABILITY', 'type' => 'experience'],
        ]);

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_HOUSEKEEP_MANAGER',
            'name_tr' => 'Kat Hizmetleri Müdürü',
            'name_en' => 'Housekeeping Manager',
            'archetype' => 'MANAGER',
            'experience_min' => 5, 'experience_max' => 10,
            'competencies' => ['LEADERSHIP', 'TIME_MANAGEMENT', 'PROBLEM_SOLVING', 'ATTENTION_TO_DETAIL'],
        ], [
            ['q_tr' => 'Departman bütçesini nasıl oluşturur ve yönetirsiniz?', 'q_en' => 'How do you create and manage the department budget?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'experience'],
            ['q_tr' => 'Temizlik standartlarını nasıl belirler ve uygularsınız?', 'q_en' => 'How do you set and implement cleaning standards?', 'comp' => 'ATTENTION_TO_DETAIL', 'type' => 'experience'],
            ['q_tr' => 'Personel işe alım ve performans değerlendirme süreçleriniz nelerdir?', 'q_en' => 'What are your staff recruitment and performance evaluation processes?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Sürdürülebilirlik uygulamalarını nasıl entegre edersiniz?', 'q_en' => 'How do you integrate sustainability practices?', 'comp' => 'ADAPTABILITY', 'type' => 'experience'],
            ['q_tr' => 'Dış kaynak kullanımı (outsourcing) konusundaki deneyiminiz nedir?', 'q_en' => 'What is your experience with outsourcing?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'experience'],
            ['q_tr' => 'Yoğun dönemlerde (festival, kongre vb.) nasıl hazırlanırsınız?', 'q_en' => 'How do you prepare for busy periods (festivals, congresses, etc.)?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'situational'],
            ['q_tr' => 'İş sağlığı ve güvenliği standartlarını nasıl uygularsınız?', 'q_en' => 'How do you implement occupational health and safety standards?', 'comp' => 'RELIABILITY', 'type' => 'experience'],
            ['q_tr' => 'Diğer departman müdürleriyle nasıl işbirliği yaparsınız?', 'q_en' => 'How do you collaborate with other department managers?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
        ]);
    }

    private function seedConcierge(): void
    {
        $subdomain = JobSubdomain::where('code', 'HOSP_CONCIERGE')->first();
        if (!$subdomain) return;

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_CONCIERGE_AGENT',
            'name_tr' => 'Concierge',
            'name_en' => 'Concierge',
            'archetype' => 'SPECIALIST',
            'experience_min' => 1, 'experience_max' => 4,
            'competencies' => ['CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING', 'ADAPTABILITY'],
        ], [
            ['q_tr' => 'Misafirlere şehir hakkında nasıl öneriler sunarsınız?', 'q_en' => 'How do you offer recommendations about the city to guests?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Zor bir rezervasyon talebiyle nasıl başa çıkarsınız?', 'q_en' => 'How do you handle a difficult reservation request?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'situational'],
            ['q_tr' => 'Yerel işletmelerle ilişkilerinizi nasıl kurarsınız?', 'q_en' => 'How do you build relationships with local businesses?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Farklı kültürlerden misafirlere nasıl hizmet verirsiniz?', 'q_en' => 'How do you serve guests from different cultures?', 'comp' => 'ADAPTABILITY', 'type' => 'experience'],
            ['q_tr' => 'Yabancı dil bilginiz hakkında bilgi verir misiniz?', 'q_en' => 'Can you tell us about your foreign language skills?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'VIP misafir taleplerini nasıl önceliklendirirsiniz?', 'q_en' => 'How do you prioritize VIP guest requests?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'situational'],
            ['q_tr' => 'Son dakika taleplerine nasıl çözüm üretirsiniz?', 'q_en' => 'How do you find solutions for last-minute requests?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'situational'],
            ['q_tr' => 'Kişiselleştirilmiş hizmet için misafir bilgilerini nasıl kullanırsınız?', 'q_en' => 'How do you use guest information for personalized service?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
        ]);

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_CONCIERGE_CHIEF',
            'name_tr' => 'Baş Concierge',
            'name_en' => 'Chief Concierge',
            'archetype' => 'COORDINATOR',
            'experience_min' => 4, 'experience_max' => 8,
            'competencies' => ['LEADERSHIP', 'CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING'],
        ], [
            ['q_tr' => 'Concierge ekibini nasıl yönetir ve eğitirsiniz?', 'q_en' => 'How do you manage and train the concierge team?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Les Clefs d\'Or üyeliği hakkında ne düşünüyorsunuz?', 'q_en' => 'What do you think about Les Clefs d\'Or membership?', 'comp' => 'LEARNING_AGILITY', 'type' => 'experience'],
            ['q_tr' => 'Concierge hizmetlerinin kalitesini nasıl ölçersiniz?', 'q_en' => 'How do you measure the quality of concierge services?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Özel etkinlik organizasyonlarında rolünüz nedir?', 'q_en' => 'What is your role in special event organizations?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'experience'],
            ['q_tr' => 'Tedarikçi ve partner ağınızı nasıl genişletirsiniz?', 'q_en' => 'How do you expand your supplier and partner network?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Misafir gizliliğini korurken kişiselleştirilmiş hizmet nasıl sunarsınız?', 'q_en' => 'How do you provide personalized service while protecting guest privacy?', 'comp' => 'RELIABILITY', 'type' => 'experience'],
            ['q_tr' => 'Dijital concierge hizmetlerini nasıl değerlendirirsiniz?', 'q_en' => 'How do you evaluate digital concierge services?', 'comp' => 'ADAPTABILITY', 'type' => 'experience'],
            ['q_tr' => 'Ekibinizin motivasyonunu nasıl yüksek tutarsınız?', 'q_en' => 'How do you keep your team motivated?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
        ]);
    }

    private function seedEvents(): void
    {
        $subdomain = JobSubdomain::where('code', 'HOSP_EVENTS')->first();
        if (!$subdomain) return;

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_EVENTS_COORDINATOR',
            'name_tr' => 'Etkinlik Koordinatörü',
            'name_en' => 'Event Coordinator',
            'archetype' => 'COORDINATOR',
            'experience_min' => 2, 'experience_max' => 5,
            'competencies' => ['TIME_MANAGEMENT', 'COMMUNICATION', 'PROBLEM_SOLVING', 'CUSTOMER_FOCUS'],
        ], [
            ['q_tr' => 'Bir etkinliği baştan sona nasıl planlarsınız?', 'q_en' => 'How do you plan an event from start to finish?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'experience'],
            ['q_tr' => 'Müşteri beklentilerini nasıl yönetirsiniz?', 'q_en' => 'How do you manage customer expectations?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Etkinlik günü beklenmedik bir sorunla nasıl başa çıkarsınız?', 'q_en' => 'How do you handle an unexpected problem on event day?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'situational'],
            ['q_tr' => 'Tedarikçilerle nasıl müzakere edersiniz?', 'q_en' => 'How do you negotiate with suppliers?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Bütçe yönetimi konusundaki deneyiminiz nedir?', 'q_en' => 'What is your experience in budget management?', 'comp' => 'ATTENTION_TO_DETAIL', 'type' => 'experience'],
            ['q_tr' => 'Farklı departmanları nasıl koordine edersiniz?', 'q_en' => 'How do you coordinate different departments?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Etkinlik sonrası değerlendirme süreciniz nasıl işler?', 'q_en' => 'How does your post-event evaluation process work?', 'comp' => 'LEARNING_AGILITY', 'type' => 'experience'],
            ['q_tr' => 'Aynı anda birden fazla etkinliği nasıl yönetirsiniz?', 'q_en' => 'How do you manage multiple events simultaneously?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'situational'],
        ]);

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_EVENTS_MANAGER',
            'name_tr' => 'Etkinlik ve Organizasyon Müdürü',
            'name_en' => 'Events and Banquet Manager',
            'archetype' => 'MANAGER',
            'experience_min' => 5, 'experience_max' => 10,
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'CUSTOMER_FOCUS', 'TIME_MANAGEMENT'],
        ], [
            ['q_tr' => 'Etkinlik departmanının gelir hedeflerini nasıl belirlersiniz?', 'q_en' => 'How do you set revenue targets for the events department?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Büyük ölçekli etkinliklerde risk yönetimini nasıl yaparsınız?', 'q_en' => 'How do you manage risks in large-scale events?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'experience'],
            ['q_tr' => 'Kurumsal müşterilerle uzun vadeli ilişkileri nasıl kurarsınız?', 'q_en' => 'How do you build long-term relationships with corporate clients?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Ekibinizin performansını nasıl değerlendirirsiniz?', 'q_en' => 'How do you evaluate your team\'s performance?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Etkinlik trendlerini nasıl takip edersiniz?', 'q_en' => 'How do you follow event trends?', 'comp' => 'ADAPTABILITY', 'type' => 'experience'],
            ['q_tr' => 'Satış ve pazarlama ile nasıl işbirliği yaparsınız?', 'q_en' => 'How do you collaborate with sales and marketing?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Yoğun sezonlarda personel planlamasını nasıl yaparsınız?', 'q_en' => 'How do you plan staffing during busy seasons?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'experience'],
            ['q_tr' => 'Rekabet analizi ve fiyatlandırma stratejiniz nedir?', 'q_en' => 'What is your competition analysis and pricing strategy?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'experience'],
        ]);
    }

    private function seedTravel(): void
    {
        $subdomain = JobSubdomain::where('code', 'HOSP_TRAVEL')->first();
        if (!$subdomain) return;

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_TRAVEL_AGENT',
            'name_tr' => 'Seyahat Danışmanı',
            'name_en' => 'Travel Agent',
            'archetype' => 'SPECIALIST',
            'experience_min' => 1, 'experience_max' => 4,
            'competencies' => ['CUSTOMER_FOCUS', 'COMMUNICATION', 'ATTENTION_TO_DETAIL', 'PROBLEM_SOLVING'],
        ], [
            ['q_tr' => 'Müşteriye uygun seyahat paketini nasıl belirlersiniz?', 'q_en' => 'How do you determine the right travel package for a customer?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Rezervasyon sistemleri hakkında deneyiminiz var mı?', 'q_en' => 'Do you have experience with reservation systems?', 'comp' => 'ATTENTION_TO_DETAIL', 'type' => 'experience'],
            ['q_tr' => 'İptal veya değişiklik taleplerini nasıl yönetirsiniz?', 'q_en' => 'How do you manage cancellation or change requests?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'situational'],
            ['q_tr' => 'Destinasyon bilginizi nasıl güncel tutarsınız?', 'q_en' => 'How do you keep your destination knowledge up to date?', 'comp' => 'LEARNING_AGILITY', 'type' => 'experience'],
            ['q_tr' => 'Şikayet eden bir müşteriyle nasıl ilgilenirsiniz?', 'q_en' => 'How do you deal with a complaining customer?', 'comp' => 'COMMUNICATION', 'type' => 'behavioral'],
            ['q_tr' => 'Satış hedeflerine ulaşmak için neler yaparsınız?', 'q_en' => 'What do you do to achieve sales targets?', 'comp' => 'CUSTOMER_FOCUS', 'type' => 'experience'],
            ['q_tr' => 'Acil durumlarda (uçuş iptali vb.) nasıl hareket edersiniz?', 'q_en' => 'How do you act in emergencies (flight cancellation, etc.)?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'situational'],
            ['q_tr' => 'Grup turlarını nasıl organize edersiniz?', 'q_en' => 'How do you organize group tours?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'experience'],
        ]);

        $this->createPositionWithQuestions($subdomain, [
            'code' => 'HOSP_TRAVEL_MANAGER',
            'name_tr' => 'Tur Operasyonları Müdürü',
            'name_en' => 'Tour Operations Manager',
            'archetype' => 'MANAGER',
            'experience_min' => 5, 'experience_max' => 10,
            'competencies' => ['LEADERSHIP', 'PROBLEM_SOLVING', 'COMMUNICATION', 'TIME_MANAGEMENT'],
        ], [
            ['q_tr' => 'Tur programlarını nasıl geliştirir ve optimize edersiniz?', 'q_en' => 'How do you develop and optimize tour programs?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'experience'],
            ['q_tr' => 'Tedarikçi ilişkilerini nasıl yönetirsiniz?', 'q_en' => 'How do you manage supplier relationships?', 'comp' => 'COMMUNICATION', 'type' => 'experience'],
            ['q_tr' => 'Ekibinizi nasıl motive eder ve geliştirirsiniz?', 'q_en' => 'How do you motivate and develop your team?', 'comp' => 'LEADERSHIP', 'type' => 'experience'],
            ['q_tr' => 'Sezon planlamasını nasıl yaparsınız?', 'q_en' => 'How do you do seasonal planning?', 'comp' => 'TIME_MANAGEMENT', 'type' => 'experience'],
            ['q_tr' => 'Kriz yönetimi deneyiminizi paylaşır mısınız?', 'q_en' => 'Can you share your crisis management experience?', 'comp' => 'PROBLEM_SOLVING', 'type' => 'behavioral'],
            ['q_tr' => 'Dijital dönüşümü nasıl entegre ediyorsunuz?', 'q_en' => 'How do you integrate digital transformation?', 'comp' => 'ADAPTABILITY', 'type' => 'experience'],
            ['q_tr' => 'Kalite standartlarını nasıl belirler ve takip edersiniz?', 'q_en' => 'How do you set and monitor quality standards?', 'comp' => 'ATTENTION_TO_DETAIL', 'type' => 'experience'],
            ['q_tr' => 'Rekabet analizi ve pazar araştırması yapıyor musunuz?', 'q_en' => 'Do you conduct competition analysis and market research?', 'comp' => 'LEARNING_AGILITY', 'type' => 'experience'],
        ]);
    }

    private function createPositionWithQuestions(JobSubdomain $subdomain, array $posData, array $questions): void
    {
        $competencyCodes = $posData['competencies'] ?? [];

        $position = JobPosition::updateOrCreate(
            ['code' => $posData['code']],
            [
                'subdomain_id' => $subdomain->id,
                'archetype_id' => isset($posData['archetype']) ? ($this->archetypes[$posData['archetype']] ?? null) : null,
                'code' => $posData['code'],
                'name_tr' => $posData['name_tr'],
                'name_en' => $posData['name_en'],
                'experience_min_years' => $posData['experience_min'] ?? 0,
                'experience_max_years' => $posData['experience_max'] ?? null,
                'is_active' => true,
            ]
        );

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

        foreach ($questions as $index => $q) {
            PositionQuestion::updateOrCreate(
                ['position_id' => $position->id, 'question_tr' => $q['q_tr']],
                [
                    'position_id' => $position->id,
                    'competency_id' => isset($q['comp']) ? ($this->competencies[$q['comp']] ?? null) : null,
                    'question_type' => $q['type'] ?? 'behavioral',
                    'question_tr' => $q['q_tr'],
                    'question_en' => $q['q_en'],
                    'sort_order' => $index + 1,
                    'is_mandatory' => $index < 3,
                    'is_active' => true,
                ]
            );
        }
    }
}
