<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\JobDomain;
use App\Models\JobPosition;
use App\Models\JobSubdomain;
use App\Models\PositionQuestion;
use App\Models\RoleArchetype;
use Illuminate\Database\Seeder;

class AllDomainsPositionsSeeder extends Seeder
{
    private array $archetypes = [];
    private array $competencies = [];

    public function run(): void
    {
        $this->archetypes = RoleArchetype::pluck('id', 'code')->toArray();
        $this->competencies = Competency::pluck('id', 'code')->toArray();

        // IT & Technology
        $this->seedITPositions();
        // Healthcare
        $this->seedHealthcarePositions();
        // Finance
        $this->seedFinancePositions();
        // Education
        $this->seedEducationPositions();
        // Manufacturing
        $this->seedManufacturingPositions();
        // Logistics
        $this->seedLogisticsPositions();
        // Construction
        $this->seedConstructionPositions();
        // Marketing
        $this->seedMarketingPositions();
        // HR
        $this->seedHRPositions();
        // Legal
        $this->seedLegalPositions();
        // Customer Service
        $this->seedCustomerServicePositions();
        // Security
        $this->seedSecurityPositions();
        // Cleaning
        $this->seedCleaningPositions();
        // Automotive
        $this->seedAutomotivePositions();
        // Agriculture
        $this->seedAgriculturePositions();
        // Beauty
        $this->seedBeautyPositions();
        // Childcare
        $this->seedChildcarePositions();
    }

    private function seedITPositions(): void
    {
        // Software Development
        $sd = JobSubdomain::where('code', 'IT_DEV')->first();
        if ($sd) {
            $this->createPos($sd, 'IT_DEV_JUNIOR', 'Junior Yazılım Geliştirici', 'Junior Software Developer', 'ENTRY', 0, 2, ['LEARNING_AGILITY', 'PROBLEM_SOLVING', 'TEAMWORK', 'COMMUNICATION'], [
                ['Yazılım geliştirme süreçleri hakkında neler biliyorsunuz?', 'What do you know about software development processes?', 'LEARNING_AGILITY'],
                ['Bir bug ile karşılaştığınızda nasıl debug edersiniz?', 'How do you debug when you encounter a bug?', 'PROBLEM_SOLVING'],
                ['Hangi programlama dillerinde deneyiminiz var?', 'What programming languages do you have experience with?', 'LEARNING_AGILITY'],
                ['Ekip içinde kod review sürecine nasıl katılırsınız?', 'How do you participate in the code review process within the team?', 'TEAMWORK'],
                ['Version control sistemleri hakkında bilginiz var mı?', 'Do you have knowledge about version control systems?', 'ATTENTION_TO_DETAIL'],
                ['Yeni bir teknoloji öğrenirken nasıl bir yol izlersiniz?', 'What approach do you take when learning a new technology?', 'LEARNING_AGILITY'],
                ['Deadline baskısı altında nasıl çalışırsınız?', 'How do you work under deadline pressure?', 'ADAPTABILITY'],
                ['Teknik bir konuyu teknik olmayan birine nasıl anlatırsınız?', 'How do you explain a technical topic to a non-technical person?', 'COMMUNICATION'],
            ]);

            $this->createPos($sd, 'IT_DEV_SENIOR', 'Kıdemli Yazılım Geliştirici', 'Senior Software Developer', 'SPECIALIST', 4, 8, ['PROBLEM_SOLVING', 'LEADERSHIP', 'COMMUNICATION', 'ATTENTION_TO_DETAIL'], [
                ['Karmaşık bir teknik problemi nasıl çözersiniz?', 'How do you solve a complex technical problem?', 'PROBLEM_SOLVING'],
                ['Mimari kararları nasıl alırsınız?', 'How do you make architectural decisions?', 'PROBLEM_SOLVING'],
                ['Junior geliştiricilere nasıl mentorluk yaparsınız?', 'How do you mentor junior developers?', 'LEADERSHIP'],
                ['Teknik borç yönetimi konusundaki yaklaşımınız nedir?', 'What is your approach to technical debt management?', 'ATTENTION_TO_DETAIL'],
                ['Performans optimizasyonu konusundaki deneyiminiz nedir?', 'What is your experience with performance optimization?', 'PROBLEM_SOLVING'],
                ['Code review sürecinde nelere dikkat edersiniz?', 'What do you pay attention to in the code review process?', 'ATTENTION_TO_DETAIL'],
                ['Paydaşlarla teknik iletişimi nasıl yönetirsiniz?', 'How do you manage technical communication with stakeholders?', 'COMMUNICATION'],
                ['Teknoloji seçimlerinde hangi kriterleri kullanırsınız?', 'What criteria do you use in technology choices?', 'PROBLEM_SOLVING'],
            ]);

            $this->createPos($sd, 'IT_DEV_LEAD', 'Teknik Lider', 'Tech Lead', 'COORDINATOR', 6, 10, ['LEADERSHIP', 'PROBLEM_SOLVING', 'COMMUNICATION', 'TIME_MANAGEMENT'], [
                ['Teknik ekibi nasıl yönlendirirsiniz?', 'How do you lead the technical team?', 'LEADERSHIP'],
                ['Sprint planlamasını nasıl yaparsınız?', 'How do you do sprint planning?', 'TIME_MANAGEMENT'],
                ['Teknik standartları nasıl belirler ve uygularsınız?', 'How do you set and implement technical standards?', 'LEADERSHIP'],
                ['Ekip içi çatışmaları nasıl çözersiniz?', 'How do you resolve conflicts within the team?', 'COMMUNICATION'],
                ['Teknik ve iş gereksinimleri arasında nasıl denge kurarsınız?', 'How do you balance technical and business requirements?', 'PROBLEM_SOLVING'],
                ['Ekibinizin gelişimini nasıl desteklersiniz?', 'How do you support your team\'s development?', 'LEADERSHIP'],
                ['Proje risklerini nasıl yönetirsiniz?', 'How do you manage project risks?', 'PROBLEM_SOLVING'],
                ['Ürün yönetimiyle nasıl işbirliği yaparsınız?', 'How do you collaborate with product management?', 'COMMUNICATION'],
            ]);
        }

        // IT Support
        $sd = JobSubdomain::where('code', 'IT_SUPPORT')->first();
        if ($sd) {
            $this->createPos($sd, 'IT_SUPPORT_TECH', 'IT Destek Uzmanı', 'IT Support Technician', 'ENTRY', 0, 2, ['CUSTOMER_FOCUS', 'PROBLEM_SOLVING', 'COMMUNICATION', 'RELIABILITY'], [
                ['Kullanıcılardan gelen sorunları nasıl önceliklendirirsiniz?', 'How do you prioritize issues from users?', 'TIME_MANAGEMENT'],
                ['Teknik bir sorunu teşhis ederken hangi adımları izlersiniz?', 'What steps do you follow when diagnosing a technical issue?', 'PROBLEM_SOLVING'],
                ['Sinirli bir kullanıcıyla nasıl iletişim kurarsınız?', 'How do you communicate with an angry user?', 'CUSTOMER_FOCUS'],
                ['Hangi işletim sistemleri ve yazılımlarla deneyiminiz var?', 'What operating systems and software do you have experience with?', 'LEARNING_AGILITY'],
                ['Uzaktan destek verirken nelere dikkat edersiniz?', 'What do you pay attention to when providing remote support?', 'COMMUNICATION'],
                ['Çözemediğiniz bir sorunla karşılaştığınızda ne yaparsınız?', 'What do you do when you encounter a problem you cannot solve?', 'PROBLEM_SOLVING'],
                ['Ticket sistemi kullanım deneyiminiz var mı?', 'Do you have experience using ticket systems?', 'RELIABILITY'],
                ['IT güvenliği konusunda kullanıcıları nasıl eğitirsiniz?', 'How do you train users on IT security?', 'COMMUNICATION'],
            ]);

            $this->createPos($sd, 'IT_SUPPORT_MANAGER', 'IT Destek Müdürü', 'IT Support Manager', 'MANAGER', 5, 10, ['LEADERSHIP', 'PROBLEM_SOLVING', 'CUSTOMER_FOCUS', 'TIME_MANAGEMENT'], [
                ['Destek ekibini nasıl yönetir ve motive edersiniz?', 'How do you manage and motivate the support team?', 'LEADERSHIP'],
                ['SLA hedeflerini nasıl takip eder ve iyileştirirsiniz?', 'How do you track and improve SLA targets?', 'TIME_MANAGEMENT'],
                ['Kullanıcı memnuniyetini nasıl ölçersiniz?', 'How do you measure user satisfaction?', 'CUSTOMER_FOCUS'],
                ['IT altyapı değişikliklerini nasıl planlarsınız?', 'How do you plan IT infrastructure changes?', 'PROBLEM_SOLVING'],
                ['Bütçe yönetimi konusundaki deneyiminiz nedir?', 'What is your experience with budget management?', 'TIME_MANAGEMENT'],
                ['Kritik sistem arızalarında nasıl hareket edersiniz?', 'How do you act in critical system failures?', 'PROBLEM_SOLVING'],
                ['Ekibinizin eğitim ihtiyaçlarını nasıl belirlersiniz?', 'How do you identify your team\'s training needs?', 'LEADERSHIP'],
                ['Tedarikçi ilişkilerini nasıl yönetirsiniz?', 'How do you manage supplier relationships?', 'COMMUNICATION'],
            ]);
        }

        // Data & Analytics
        $sd = JobSubdomain::where('code', 'IT_DATA')->first();
        if ($sd) {
            $this->createPos($sd, 'IT_DATA_ANALYST', 'Veri Analisti', 'Data Analyst', 'SPECIALIST', 1, 4, ['ATTENTION_TO_DETAIL', 'PROBLEM_SOLVING', 'COMMUNICATION', 'LEARNING_AGILITY'], [
                ['Veri analizi sürecinizi anlatır mısınız?', 'Can you describe your data analysis process?', 'PROBLEM_SOLVING'],
                ['Hangi analitik araçları kullanıyorsunuz?', 'What analytical tools do you use?', 'LEARNING_AGILITY'],
                ['Karmaşık verileri nasıl görselleştirirsiniz?', 'How do you visualize complex data?', 'COMMUNICATION'],
                ['Veri kalitesi sorunlarıyla nasıl başa çıkarsınız?', 'How do you deal with data quality issues?', 'ATTENTION_TO_DETAIL'],
                ['SQL bilginiz hakkında bilgi verir misiniz?', 'Can you tell us about your SQL knowledge?', 'ATTENTION_TO_DETAIL'],
                ['İş birimlerine insight sunma deneyiminiz nedir?', 'What is your experience in presenting insights to business units?', 'COMMUNICATION'],
                ['A/B testleri konusundaki deneyiminiz var mı?', 'Do you have experience with A/B testing?', 'PROBLEM_SOLVING'],
                ['Büyük veri setleriyle nasıl çalışırsınız?', 'How do you work with large data sets?', 'PROBLEM_SOLVING'],
            ]);
        }
    }

    private function seedHealthcarePositions(): void
    {
        // Nursing
        $sd = JobSubdomain::where('code', 'HC_NURSING')->first();
        if ($sd) {
            $this->createPos($sd, 'HC_NURSING_NURSE', 'Hemşire', 'Nurse', 'SPECIALIST', 0, 5, ['CUSTOMER_FOCUS', 'RELIABILITY', 'COMMUNICATION', 'ADAPTABILITY'], [
                ['Hasta bakımında öncelikleriniz nelerdir?', 'What are your priorities in patient care?', 'CUSTOMER_FOCUS'],
                ['Acil bir durumda nasıl hareket edersiniz?', 'How do you act in an emergency?', 'PROBLEM_SOLVING'],
                ['Hasta ve aileleriyle iletişiminizi nasıl yönetirsiniz?', 'How do you manage communication with patients and families?', 'COMMUNICATION'],
                ['Yoğun vardiyalarda nasıl organize olursunuz?', 'How do you organize during busy shifts?', 'TIME_MANAGEMENT'],
                ['İlaç uygulama sürecinde nelere dikkat edersiniz?', 'What do you pay attention to in the medication administration process?', 'ATTENTION_TO_DETAIL'],
                ['Ekip içinde nasıl iletişim kurarsınız?', 'How do you communicate within the team?', 'TEAMWORK'],
                ['Zor bir hastayla nasıl başa çıkarsınız?', 'How do you deal with a difficult patient?', 'CUSTOMER_FOCUS'],
                ['Mesleğinizde güncel kalma konusunda ne yaparsınız?', 'What do you do to stay current in your profession?', 'LEARNING_AGILITY'],
            ]);

            $this->createPos($sd, 'HC_NURSING_HEAD', 'Sorumlu Hemşire', 'Head Nurse', 'COORDINATOR', 5, 10, ['LEADERSHIP', 'PROBLEM_SOLVING', 'COMMUNICATION', 'TIME_MANAGEMENT'], [
                ['Hemşirelik ekibini nasıl yönetirsiniz?', 'How do you manage the nursing team?', 'LEADERSHIP'],
                ['Vardiya planlamasını nasıl yaparsınız?', 'How do you do shift planning?', 'TIME_MANAGEMENT'],
                ['Hasta bakım kalitesini nasıl takip edersiniz?', 'How do you monitor patient care quality?', 'ATTENTION_TO_DETAIL'],
                ['Kritik durumlarda ekibi nasıl koordine edersiniz?', 'How do you coordinate the team in critical situations?', 'LEADERSHIP'],
                ['Doktorlarla nasıl işbirliği yaparsınız?', 'How do you collaborate with doctors?', 'COMMUNICATION'],
                ['Yeni personel eğitimini nasıl organize edersiniz?', 'How do you organize new staff training?', 'LEADERSHIP'],
                ['Kaynak yönetimini nasıl yaparsınız?', 'How do you manage resources?', 'TIME_MANAGEMENT'],
                ['Hasta şikayetlerini nasıl ele alırsınız?', 'How do you handle patient complaints?', 'CUSTOMER_FOCUS'],
            ]);
        }

        // Pharmacy
        $sd = JobSubdomain::where('code', 'HC_PHARMACY')->first();
        if ($sd) {
            $this->createPos($sd, 'HC_PHARMACY_TECH', 'Eczane Teknisyeni', 'Pharmacy Technician', 'ENTRY', 0, 3, ['ATTENTION_TO_DETAIL', 'CUSTOMER_FOCUS', 'RELIABILITY', 'COMMUNICATION'], [
                ['Reçete okuma ve ilaç hazırlama sürecinizi anlatın.', 'Describe your prescription reading and medication preparation process.', 'ATTENTION_TO_DETAIL'],
                ['Stok yönetimini nasıl yaparsınız?', 'How do you manage stock?', 'RELIABILITY'],
                ['Hasta sorularını nasıl yanıtlarsınız?', 'How do you answer patient questions?', 'CUSTOMER_FOCUS'],
                ['İlaç etkileşimleri konusunda nelere dikkat edersiniz?', 'What do you pay attention to regarding drug interactions?', 'ATTENTION_TO_DETAIL'],
                ['Yoğun saatlerde nasıl organize olursunuz?', 'How do you organize during busy hours?', 'TIME_MANAGEMENT'],
                ['Hata önleme konusunda neler yaparsınız?', 'What do you do to prevent errors?', 'RELIABILITY'],
                ['Eczacıyla nasıl koordine çalışırsınız?', 'How do you coordinate with the pharmacist?', 'TEAMWORK'],
                ['Soğuk zincir ilaçları nasıl yönetirsiniz?', 'How do you manage cold chain medications?', 'ATTENTION_TO_DETAIL'],
            ]);

            $this->createPos($sd, 'HC_PHARMACY_PHARMACIST', 'Eczacı', 'Pharmacist', 'SPECIALIST', 0, 5, ['ATTENTION_TO_DETAIL', 'CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING'], [
                ['Reçete değerlendirme sürecinizi anlatın.', 'Describe your prescription evaluation process.', 'ATTENTION_TO_DETAIL'],
                ['Hasta danışmanlığını nasıl yaparsınız?', 'How do you provide patient counseling?', 'CUSTOMER_FOCUS'],
                ['İlaç etkileşimlerini nasıl değerlendirirsiniz?', 'How do you evaluate drug interactions?', 'PROBLEM_SOLVING'],
                ['Mesleki gelişiminizi nasıl sürdürürsünüz?', 'How do you maintain your professional development?', 'LEARNING_AGILITY'],
                ['Eczane yönetimi deneyiminiz var mı?', 'Do you have pharmacy management experience?', 'LEADERSHIP'],
                ['Sağlık sigortası süreçlerini nasıl yönetirsiniz?', 'How do you manage health insurance processes?', 'ATTENTION_TO_DETAIL'],
                ['Acil ilaç ihtiyaçlarını nasıl karşılarsınız?', 'How do you meet urgent medication needs?', 'PROBLEM_SOLVING'],
                ['Ekibi nasıl yönlendirirsiniz?', 'How do you guide the team?', 'LEADERSHIP'],
            ]);
        }
    }

    private function seedFinancePositions(): void
    {
        // Banking
        $sd = JobSubdomain::where('code', 'FIN_BANKING')->first();
        if ($sd) {
            $this->createPos($sd, 'FIN_BANKING_TELLER', 'Gişe Yetkilisi', 'Bank Teller', 'ENTRY', 0, 2, ['ATTENTION_TO_DETAIL', 'CUSTOMER_FOCUS', 'RELIABILITY', 'COMMUNICATION'], [
                ['Para işlemlerinde nelere dikkat edersiniz?', 'What do you pay attention to in cash transactions?', 'ATTENTION_TO_DETAIL'],
                ['Müşteri ilişkilerini nasıl yönetirsiniz?', 'How do you manage customer relationships?', 'CUSTOMER_FOCUS'],
                ['Şüpheli işlemleri nasıl tespit edersiniz?', 'How do you detect suspicious transactions?', 'RELIABILITY'],
                ['Yoğun saatlerde nasıl organize olursunuz?', 'How do you organize during busy hours?', 'TIME_MANAGEMENT'],
                ['Kasa farkı durumunda ne yaparsınız?', 'What do you do in case of cash difference?', 'RELIABILITY'],
                ['Ürün ve hizmet bilginizi nasıl güncel tutarsınız?', 'How do you keep your product and service knowledge up to date?', 'LEARNING_AGILITY'],
                ['Zor bir müşteriyle nasıl başa çıkarsınız?', 'How do you deal with a difficult customer?', 'CUSTOMER_FOCUS'],
                ['Bankacılık yazılımları konusundaki deneyiminiz nedir?', 'What is your experience with banking software?', 'LEARNING_AGILITY'],
            ]);

            $this->createPos($sd, 'FIN_BANKING_RELATIONSHIP', 'Bireysel Portföy Yöneticisi', 'Personal Banking Relationship Manager', 'SPECIALIST', 2, 6, ['CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING', 'RELIABILITY'], [
                ['Müşteri portföyünüzü nasıl yönetirsiniz?', 'How do you manage your customer portfolio?', 'CUSTOMER_FOCUS'],
                ['Satış hedeflerine nasıl ulaşırsınız?', 'How do you achieve sales targets?', 'PROBLEM_SOLVING'],
                ['Müşteri ihtiyaç analizi nasıl yaparsınız?', 'How do you conduct customer needs analysis?', 'CUSTOMER_FOCUS'],
                ['Ürün önerisi yaparken neleri göz önünde bulundurursunuz?', 'What do you consider when making product recommendations?', 'COMMUNICATION'],
                ['Müşteri şikayetlerini nasıl çözersiniz?', 'How do you resolve customer complaints?', 'PROBLEM_SOLVING'],
                ['Yeni müşteri kazanımı stratejileriniz nelerdir?', 'What are your new customer acquisition strategies?', 'CUSTOMER_FOCUS'],
                ['Finansal okuryazarlık konusunda müşterileri nasıl bilgilendirirsiniz?', 'How do you inform customers about financial literacy?', 'COMMUNICATION'],
                ['Risk değerlendirmesini nasıl yaparsınız?', 'How do you conduct risk assessment?', 'ATTENTION_TO_DETAIL'],
            ]);

            $this->createPos($sd, 'FIN_BANKING_BRANCH_MGR', 'Şube Müdürü', 'Branch Manager', 'MANAGER', 7, 12, ['LEADERSHIP', 'PROBLEM_SOLVING', 'CUSTOMER_FOCUS', 'TIME_MANAGEMENT'], [
                ['Şube hedeflerini nasıl belirler ve takip edersiniz?', 'How do you set and track branch targets?', 'LEADERSHIP'],
                ['Ekibinizi nasıl motive eder ve geliştirirsiniz?', 'How do you motivate and develop your team?', 'LEADERSHIP'],
                ['Operasyonel riskleri nasıl yönetirsiniz?', 'How do you manage operational risks?', 'PROBLEM_SOLVING'],
                ['Müşteri deneyimini nasıl iyileştirirsiniz?', 'How do you improve customer experience?', 'CUSTOMER_FOCUS'],
                ['Bölge müdürlüğü ile nasıl iletişim kurarsınız?', 'How do you communicate with regional management?', 'COMMUNICATION'],
                ['Personel performansını nasıl değerlendirirsiniz?', 'How do you evaluate staff performance?', 'LEADERSHIP'],
                ['Uyum ve denetim konularını nasıl yönetirsiniz?', 'How do you manage compliance and audit issues?', 'RELIABILITY'],
                ['Şube bütçesini nasıl yönetirsiniz?', 'How do you manage the branch budget?', 'TIME_MANAGEMENT'],
            ]);
        }

        // Accounting
        $sd = JobSubdomain::where('code', 'FIN_ACCOUNTING')->first();
        if ($sd) {
            $this->createPos($sd, 'FIN_ACCOUNTING_ACCOUNTANT', 'Muhasebeci', 'Accountant', 'SPECIALIST', 1, 5, ['ATTENTION_TO_DETAIL', 'RELIABILITY', 'TIME_MANAGEMENT', 'PROBLEM_SOLVING'], [
                ['Ay sonu kapanış süreçlerinizi anlatın.', 'Describe your month-end closing processes.', 'ATTENTION_TO_DETAIL'],
                ['Hangi muhasebe yazılımlarını kullanıyorsunuz?', 'What accounting software do you use?', 'LEARNING_AGILITY'],
                ['Vergi mevzuatını nasıl takip edersiniz?', 'How do you follow tax legislation?', 'LEARNING_AGILITY'],
                ['Muhasebe hatalarını nasıl tespit edersiniz?', 'How do you detect accounting errors?', 'ATTENTION_TO_DETAIL'],
                ['Banka mutabakatlarını nasıl yaparsınız?', 'How do you perform bank reconciliations?', 'RELIABILITY'],
                ['Mali tablolar hazırlama deneyiminiz nedir?', 'What is your experience in preparing financial statements?', 'ATTENTION_TO_DETAIL'],
                ['Deadline baskısı altında nasıl çalışırsınız?', 'How do you work under deadline pressure?', 'TIME_MANAGEMENT'],
                ['Denetim süreçlerine nasıl hazırlanırsınız?', 'How do you prepare for audit processes?', 'RELIABILITY'],
            ]);

            $this->createPos($sd, 'FIN_ACCOUNTING_MANAGER', 'Muhasebe Müdürü', 'Accounting Manager', 'MANAGER', 6, 12, ['LEADERSHIP', 'ATTENTION_TO_DETAIL', 'TIME_MANAGEMENT', 'PROBLEM_SOLVING'], [
                ['Muhasebe ekibini nasıl yönetirsiniz?', 'How do you manage the accounting team?', 'LEADERSHIP'],
                ['Mali raporlama süreçlerini nasıl yönetirsiniz?', 'How do you manage financial reporting processes?', 'ATTENTION_TO_DETAIL'],
                ['İç kontrol sistemlerini nasıl tasarlarsınız?', 'How do you design internal control systems?', 'RELIABILITY'],
                ['Bütçe hazırlama sürecinizi anlatın.', 'Describe your budget preparation process.', 'TIME_MANAGEMENT'],
                ['Dış denetçilerle nasıl çalışırsınız?', 'How do you work with external auditors?', 'COMMUNICATION'],
                ['Mevzuat değişikliklerine nasıl uyum sağlarsınız?', 'How do you adapt to regulatory changes?', 'ADAPTABILITY'],
                ['Maliyetleri nasıl optimize edersiniz?', 'How do you optimize costs?', 'PROBLEM_SOLVING'],
                ['Üst yönetime nasıl raporlama yaparsınız?', 'How do you report to upper management?', 'COMMUNICATION'],
            ]);
        }
    }

    private function seedEducationPositions(): void
    {
        // K-12
        $sd = JobSubdomain::where('code', 'EDU_K12')->first();
        if ($sd) {
            $this->createPos($sd, 'EDU_K12_TEACHER', 'Öğretmen', 'Teacher', 'SPECIALIST', 0, 5, ['COMMUNICATION', 'CUSTOMER_FOCUS', 'ADAPTABILITY', 'LEADERSHIP'], [
                ['Ders planlamasını nasıl yaparsınız?', 'How do you do lesson planning?', 'TIME_MANAGEMENT'],
                ['Farklı öğrenme stillerine nasıl uyum sağlarsınız?', 'How do you adapt to different learning styles?', 'ADAPTABILITY'],
                ['Sınıf yönetimi konusundaki yaklaşımınız nedir?', 'What is your approach to classroom management?', 'LEADERSHIP'],
                ['Velilerle iletişiminizi nasıl yönetirsiniz?', 'How do you manage communication with parents?', 'COMMUNICATION'],
                ['Öğrenci değerlendirmesini nasıl yaparsınız?', 'How do you assess students?', 'ATTENTION_TO_DETAIL'],
                ['Teknolojiden eğitimde nasıl yararlanırsınız?', 'How do you utilize technology in education?', 'LEARNING_AGILITY'],
                ['Zorlu öğrencilerle nasıl başa çıkarsınız?', 'How do you deal with challenging students?', 'PROBLEM_SOLVING'],
                ['Mesleki gelişiminizi nasıl sürdürürsünüz?', 'How do you maintain your professional development?', 'LEARNING_AGILITY'],
            ]);

            $this->createPos($sd, 'EDU_K12_COORDINATOR', 'Bölüm Koordinatörü', 'Department Coordinator', 'COORDINATOR', 5, 10, ['LEADERSHIP', 'COMMUNICATION', 'TIME_MANAGEMENT', 'PROBLEM_SOLVING'], [
                ['Öğretmen ekibini nasıl yönetirsiniz?', 'How do you manage the teacher team?', 'LEADERSHIP'],
                ['Müfredat geliştirme sürecine nasıl katkı sağlarsınız?', 'How do you contribute to the curriculum development process?', 'PROBLEM_SOLVING'],
                ['Eğitim kalitesini nasıl takip edersiniz?', 'How do you monitor educational quality?', 'ATTENTION_TO_DETAIL'],
                ['Okul yönetimiyle nasıl iletişim kurarsınız?', 'How do you communicate with school administration?', 'COMMUNICATION'],
                ['Yeni öğretmenlerin oryantasyonunu nasıl yaparsınız?', 'How do you orient new teachers?', 'LEADERSHIP'],
                ['Sınav ve değerlendirme sistemlerini nasıl yönetirsiniz?', 'How do you manage exam and assessment systems?', 'TIME_MANAGEMENT'],
                ['Öğretmenler arası işbirliğini nasıl teşvik edersiniz?', 'How do you encourage collaboration among teachers?', 'TEAMWORK'],
                ['Eğitim trendlerini nasıl takip edersiniz?', 'How do you follow educational trends?', 'LEARNING_AGILITY'],
            ]);
        }

        // Preschool
        $sd = JobSubdomain::where('code', 'EDU_PRESCHOOL')->first();
        if ($sd) {
            $this->createPos($sd, 'EDU_PRESCHOOL_TEACHER', 'Okul Öncesi Öğretmeni', 'Preschool Teacher', 'SPECIALIST', 0, 5, ['CUSTOMER_FOCUS', 'COMMUNICATION', 'ADAPTABILITY', 'RELIABILITY'], [
                ['Çocukların gelişimini nasıl desteklersiniz?', 'How do you support children\'s development?', 'CUSTOMER_FOCUS'],
                ['Oyun temelli öğrenmeyi nasıl uygularsınız?', 'How do you implement play-based learning?', 'ADAPTABILITY'],
                ['Velilerle iletişiminizi nasıl yönetirsiniz?', 'How do you manage communication with parents?', 'COMMUNICATION'],
                ['Günlük rutinleri nasıl organize edersiniz?', 'How do you organize daily routines?', 'TIME_MANAGEMENT'],
                ['Çocuklar arası çatışmaları nasıl çözersiniz?', 'How do you resolve conflicts among children?', 'PROBLEM_SOLVING'],
                ['Güvenlik önlemlerine nasıl dikkat edersiniz?', 'How do you pay attention to safety measures?', 'RELIABILITY'],
                ['Özel gereksinimli çocuklarla nasıl çalışırsınız?', 'How do you work with children with special needs?', 'ADAPTABILITY'],
                ['Yaratıcı aktiviteleri nasıl planlarsınız?', 'How do you plan creative activities?', 'PROBLEM_SOLVING'],
            ]);
        }
    }

    private function seedManufacturingPositions(): void
    {
        // Production
        $sd = JobSubdomain::where('code', 'MFG_PRODUCTION')->first();
        if ($sd) {
            $this->createPos($sd, 'MFG_PRODUCTION_OPERATOR', 'Üretim Operatörü', 'Production Operator', 'ENTRY', 0, 2, ['RELIABILITY', 'ATTENTION_TO_DETAIL', 'TEAMWORK', 'ADAPTABILITY'], [
                ['Makine kullanım deneyiminizi anlatın.', 'Describe your machine operation experience.', 'LEARNING_AGILITY'],
                ['Üretim hedeflerine ulaşmak için neler yaparsınız?', 'What do you do to achieve production targets?', 'RELIABILITY'],
                ['İş güvenliği kurallarına nasıl uyarsınız?', 'How do you comply with occupational safety rules?', 'RELIABILITY'],
                ['Kalite kontrolde nelere dikkat edersiniz?', 'What do you pay attention to in quality control?', 'ATTENTION_TO_DETAIL'],
                ['Vardiya çalışması konusundaki tutumunuz nedir?', 'What is your attitude about shift work?', 'ADAPTABILITY'],
                ['Ekip arkadaşlarınızla nasıl koordineli çalışırsınız?', 'How do you work in coordination with your teammates?', 'TEAMWORK'],
                ['Makine arızasında ne yaparsınız?', 'What do you do in case of machine failure?', 'PROBLEM_SOLVING'],
                ['Üretim verilerini nasıl raporlarsınız?', 'How do you report production data?', 'ATTENTION_TO_DETAIL'],
            ]);

            $this->createPos($sd, 'MFG_PRODUCTION_SUPERVISOR', 'Üretim Şefi', 'Production Supervisor', 'COORDINATOR', 3, 7, ['LEADERSHIP', 'TIME_MANAGEMENT', 'PROBLEM_SOLVING', 'COMMUNICATION'], [
                ['Üretim ekibini nasıl yönetirsiniz?', 'How do you manage the production team?', 'LEADERSHIP'],
                ['Üretim planlamasını nasıl yaparsınız?', 'How do you do production planning?', 'TIME_MANAGEMENT'],
                ['Kalite sorunlarını nasıl çözersiniz?', 'How do you solve quality issues?', 'PROBLEM_SOLVING'],
                ['Vardiya devirlerini nasıl yönetirsiniz?', 'How do you manage shift handovers?', 'COMMUNICATION'],
                ['İş güvenliğini nasıl sağlarsınız?', 'How do you ensure occupational safety?', 'RELIABILITY'],
                ['Performans takibini nasıl yaparsınız?', 'How do you track performance?', 'ATTENTION_TO_DETAIL'],
                ['Bakım ekibiyle nasıl koordine olursunuz?', 'How do you coordinate with the maintenance team?', 'COMMUNICATION'],
                ['Verimlilik artışı için neler yaparsınız?', 'What do you do to increase efficiency?', 'PROBLEM_SOLVING'],
            ]);

            $this->createPos($sd, 'MFG_PRODUCTION_MANAGER', 'Üretim Müdürü', 'Production Manager', 'MANAGER', 7, 12, ['LEADERSHIP', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT', 'ADAPTABILITY'], [
                ['Üretim stratejisini nasıl belirlersiniz?', 'How do you determine the production strategy?', 'LEADERSHIP'],
                ['Kapasite planlamasını nasıl yaparsınız?', 'How do you do capacity planning?', 'TIME_MANAGEMENT'],
                ['Maliyetleri nasıl optimize edersiniz?', 'How do you optimize costs?', 'PROBLEM_SOLVING'],
                ['Yalın üretim uygulamalarınız nelerdir?', 'What are your lean manufacturing practices?', 'LEARNING_AGILITY'],
                ['Kalite yönetim sistemlerini nasıl uygularsınız?', 'How do you implement quality management systems?', 'ATTENTION_TO_DETAIL'],
                ['Ekibinizin gelişimini nasıl desteklersiniz?', 'How do you support your team\'s development?', 'LEADERSHIP'],
                ['Üst yönetime nasıl raporlama yaparsınız?', 'How do you report to upper management?', 'COMMUNICATION'],
                ['Teknoloji yatırımlarını nasıl değerlendirirsiniz?', 'How do you evaluate technology investments?', 'PROBLEM_SOLVING'],
            ]);
        }

        // Quality
        $sd = JobSubdomain::where('code', 'MFG_QUALITY')->first();
        if ($sd) {
            $this->createPos($sd, 'MFG_QUALITY_INSPECTOR', 'Kalite Kontrol Uzmanı', 'Quality Control Inspector', 'SPECIALIST', 1, 4, ['ATTENTION_TO_DETAIL', 'RELIABILITY', 'PROBLEM_SOLVING', 'COMMUNICATION'], [
                ['Kalite kontrol sürecinizi anlatın.', 'Describe your quality control process.', 'ATTENTION_TO_DETAIL'],
                ['Uygunsuzluk tespit ettiğinizde ne yaparsınız?', 'What do you do when you detect non-conformity?', 'RELIABILITY'],
                ['Ölçüm aletlerini nasıl kullanırsınız?', 'How do you use measuring instruments?', 'ATTENTION_TO_DETAIL'],
                ['Kalite raporlarını nasıl hazırlarsınız?', 'How do you prepare quality reports?', 'COMMUNICATION'],
                ['İstatistiksel proses kontrol hakkında bilginiz var mı?', 'Do you have knowledge about statistical process control?', 'LEARNING_AGILITY'],
                ['Üretimle nasıl koordine çalışırsınız?', 'How do you coordinate with production?', 'TEAMWORK'],
                ['Kalite standartlarını nasıl takip edersiniz?', 'How do you follow quality standards?', 'LEARNING_AGILITY'],
                ['Müşteri şikayetlerini nasıl değerlendirirsiniz?', 'How do you evaluate customer complaints?', 'PROBLEM_SOLVING'],
            ]);
        }
    }

    private function seedLogisticsPositions(): void
    {
        // Warehouse
        $sd = JobSubdomain::where('code', 'LOG_WAREHOUSE')->first();
        if ($sd) {
            $this->createPos($sd, 'LOG_WAREHOUSE_WORKER', 'Depo İşçisi', 'Warehouse Worker', 'ENTRY', 0, 2, ['RELIABILITY', 'ATTENTION_TO_DETAIL', 'TEAMWORK', 'ADAPTABILITY'], [
                ['Mal kabul sürecini nasıl yönetirsiniz?', 'How do you manage the goods receiving process?', 'ATTENTION_TO_DETAIL'],
                ['Forklift kullanım deneyiminiz var mı?', 'Do you have forklift operation experience?', 'LEARNING_AGILITY'],
                ['Stok yerleştirmede nelere dikkat edersiniz?', 'What do you pay attention to in stock placement?', 'ATTENTION_TO_DETAIL'],
                ['Fiziksel iş ortamında nasıl çalışırsınız?', 'How do you work in a physical work environment?', 'ADAPTABILITY'],
                ['Depo güvenliği konusunda neler biliyorsunuz?', 'What do you know about warehouse safety?', 'RELIABILITY'],
                ['Ekip arkadaşlarınızla nasıl koordineli çalışırsınız?', 'How do you work in coordination with teammates?', 'TEAMWORK'],
                ['WMS sistemleri hakkında bilginiz var mı?', 'Do you have knowledge about WMS systems?', 'LEARNING_AGILITY'],
                ['Yoğun dönemlerde nasıl çalışırsınız?', 'How do you work during busy periods?', 'ADAPTABILITY'],
            ]);

            $this->createPos($sd, 'LOG_WAREHOUSE_SUPERVISOR', 'Depo Şefi', 'Warehouse Supervisor', 'COORDINATOR', 3, 6, ['LEADERSHIP', 'TIME_MANAGEMENT', 'ATTENTION_TO_DETAIL', 'PROBLEM_SOLVING'], [
                ['Depo operasyonlarını nasıl planlarsınız?', 'How do you plan warehouse operations?', 'TIME_MANAGEMENT'],
                ['Ekibinizi nasıl yönetirsiniz?', 'How do you manage your team?', 'LEADERSHIP'],
                ['Stok doğruluğunu nasıl sağlarsınız?', 'How do you ensure stock accuracy?', 'ATTENTION_TO_DETAIL'],
                ['Sevkiyat süreçlerini nasıl optimize edersiniz?', 'How do you optimize shipment processes?', 'PROBLEM_SOLVING'],
                ['Depo güvenliğini nasıl sağlarsınız?', 'How do you ensure warehouse safety?', 'RELIABILITY'],
                ['Performans takibini nasıl yaparsınız?', 'How do you track performance?', 'ATTENTION_TO_DETAIL'],
                ['Tedarik zinciriyle nasıl koordine olursunuz?', 'How do you coordinate with the supply chain?', 'COMMUNICATION'],
                ['Kapasite yönetimini nasıl yaparsınız?', 'How do you manage capacity?', 'TIME_MANAGEMENT'],
            ]);
        }

        // Transport
        $sd = JobSubdomain::where('code', 'LOG_TRANSPORT')->first();
        if ($sd) {
            $this->createPos($sd, 'LOG_TRANSPORT_DRIVER', 'Sürücü / Şoför', 'Driver', 'ENTRY', 0, 5, ['RELIABILITY', 'TIME_MANAGEMENT', 'CUSTOMER_FOCUS', 'ADAPTABILITY'], [
                ['Güvenli sürüş konusundaki yaklaşımınız nedir?', 'What is your approach to safe driving?', 'RELIABILITY'],
                ['Rota planlamasını nasıl yaparsınız?', 'How do you do route planning?', 'TIME_MANAGEMENT'],
                ['Teslimat sırasında müşterilerle nasıl iletişim kurarsınız?', 'How do you communicate with customers during delivery?', 'CUSTOMER_FOCUS'],
                ['Araç bakımını nasıl takip edersiniz?', 'How do you track vehicle maintenance?', 'RELIABILITY'],
                ['Beklenmedik durumlarla nasıl başa çıkarsınız?', 'How do you deal with unexpected situations?', 'ADAPTABILITY'],
                ['Yük güvenliğini nasıl sağlarsınız?', 'How do you ensure load safety?', 'ATTENTION_TO_DETAIL'],
                ['Uzun mesafe sürüşlerinde yorgunluğu nasıl yönetirsiniz?', 'How do you manage fatigue on long-distance drives?', 'RELIABILITY'],
                ['Takograf ve yasal düzenlemelere uyumu nasıl sağlarsınız?', 'How do you ensure compliance with tachograph and legal regulations?', 'RELIABILITY'],
            ]);

            $this->createPos($sd, 'LOG_TRANSPORT_COORDINATOR', 'Nakliye Koordinatörü', 'Transport Coordinator', 'COORDINATOR', 2, 5, ['TIME_MANAGEMENT', 'PROBLEM_SOLVING', 'COMMUNICATION', 'ATTENTION_TO_DETAIL'], [
                ['Nakliye planlamasını nasıl yaparsınız?', 'How do you do transport planning?', 'TIME_MANAGEMENT'],
                ['Sürücü ekibini nasıl koordine edersiniz?', 'How do you coordinate the driver team?', 'COMMUNICATION'],
                ['Maliyet optimizasyonunu nasıl yaparsınız?', 'How do you do cost optimization?', 'PROBLEM_SOLVING'],
                ['Müşteri şikayetlerini nasıl yönetirsiniz?', 'How do you manage customer complaints?', 'CUSTOMER_FOCUS'],
                ['Araç takip sistemlerini nasıl kullanırsınız?', 'How do you use vehicle tracking systems?', 'ATTENTION_TO_DETAIL'],
                ['Acil durumları nasıl yönetirsiniz?', 'How do you manage emergencies?', 'PROBLEM_SOLVING'],
                ['Tedarikçi ilişkilerini nasıl yönetirsiniz?', 'How do you manage supplier relationships?', 'COMMUNICATION'],
                ['Performans raporlarını nasıl hazırlarsınız?', 'How do you prepare performance reports?', 'ATTENTION_TO_DETAIL'],
            ]);
        }
    }

    private function seedConstructionPositions(): void
    {
        // Site Operations
        $sd = JobSubdomain::where('code', 'CON_SITE')->first();
        if ($sd) {
            $this->createPos($sd, 'CON_SITE_WORKER', 'İnşaat İşçisi', 'Construction Worker', 'ENTRY', 0, 3, ['RELIABILITY', 'TEAMWORK', 'ADAPTABILITY', 'ATTENTION_TO_DETAIL'], [
                ['İnşaat deneyiminizi anlatın.', 'Describe your construction experience.', 'LEARNING_AGILITY'],
                ['İş güvenliği kurallarına nasıl uyarsınız?', 'How do you comply with safety rules?', 'RELIABILITY'],
                ['Hangi alet ve ekipmanları kullanabilirsiniz?', 'What tools and equipment can you use?', 'LEARNING_AGILITY'],
                ['Fiziksel iş ortamında nasıl çalışırsınız?', 'How do you work in a physical environment?', 'ADAPTABILITY'],
                ['Ekip arkadaşlarınızla nasıl koordineli çalışırsınız?', 'How do you coordinate with teammates?', 'TEAMWORK'],
                ['Kalite standartlarına nasıl uyarsınız?', 'How do you comply with quality standards?', 'ATTENTION_TO_DETAIL'],
                ['Farklı hava koşullarında çalışma konusundaki tutumunuz nedir?', 'What is your attitude about working in different weather conditions?', 'ADAPTABILITY'],
                ['Talimatları nasıl takip edersiniz?', 'How do you follow instructions?', 'RELIABILITY'],
            ]);

            $this->createPos($sd, 'CON_SITE_FOREMAN', 'Şantiye Şefi', 'Site Foreman', 'COORDINATOR', 5, 10, ['LEADERSHIP', 'TIME_MANAGEMENT', 'PROBLEM_SOLVING', 'COMMUNICATION'], [
                ['Şantiye ekibini nasıl yönetirsiniz?', 'How do you manage the site team?', 'LEADERSHIP'],
                ['İş programını nasıl takip edersiniz?', 'How do you track the work schedule?', 'TIME_MANAGEMENT'],
                ['Kalite kontrolü nasıl sağlarsınız?', 'How do you ensure quality control?', 'ATTENTION_TO_DETAIL'],
                ['İş güvenliğini nasıl yönetirsiniz?', 'How do you manage occupational safety?', 'RELIABILITY'],
                ['Taşeronlarla nasıl çalışırsınız?', 'How do you work with subcontractors?', 'COMMUNICATION'],
                ['Malzeme yönetimini nasıl yaparsınız?', 'How do you manage materials?', 'TIME_MANAGEMENT'],
                ['Teknik sorunları nasıl çözersiniz?', 'How do you solve technical problems?', 'PROBLEM_SOLVING'],
                ['Proje müdürüyle nasıl raporlama yaparsınız?', 'How do you report to the project manager?', 'COMMUNICATION'],
            ]);

            $this->createPos($sd, 'CON_SITE_PROJECT_MGR', 'Proje Müdürü', 'Project Manager', 'MANAGER', 8, 15, ['LEADERSHIP', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT', 'COMMUNICATION'], [
                ['Projeyi baştan sona nasıl yönetirsiniz?', 'How do you manage a project from start to finish?', 'LEADERSHIP'],
                ['Bütçe yönetimini nasıl yaparsınız?', 'How do you manage the budget?', 'ATTENTION_TO_DETAIL'],
                ['Proje risklerini nasıl yönetirsiniz?', 'How do you manage project risks?', 'PROBLEM_SOLVING'],
                ['Müşteri ilişkilerini nasıl yönetirsiniz?', 'How do you manage client relationships?', 'CUSTOMER_FOCUS'],
                ['Tedarikçi ve taşeron seçimini nasıl yaparsınız?', 'How do you select suppliers and subcontractors?', 'PROBLEM_SOLVING'],
                ['Proje ekibini nasıl motive edersiniz?', 'How do you motivate the project team?', 'LEADERSHIP'],
                ['Yasal ve mevzuat gerekliliklerini nasıl takip edersiniz?', 'How do you follow legal and regulatory requirements?', 'RELIABILITY'],
                ['Proje raporlamasını nasıl yaparsınız?', 'How do you do project reporting?', 'COMMUNICATION'],
            ]);
        }

        // Real Estate
        $sd = JobSubdomain::where('code', 'CON_REALESTATE')->first();
        if ($sd) {
            $this->createPos($sd, 'CON_REALESTATE_AGENT', 'Emlak Danışmanı', 'Real Estate Agent', 'SPECIALIST', 0, 5, ['CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING', 'RELIABILITY'], [
                ['Müşteri ihtiyaç analizini nasıl yaparsınız?', 'How do you analyze customer needs?', 'CUSTOMER_FOCUS'],
                ['Portföy yönetimini nasıl yaparsınız?', 'How do you manage your portfolio?', 'TIME_MANAGEMENT'],
                ['Pazarlık sürecini nasıl yönetirsiniz?', 'How do you manage the negotiation process?', 'COMMUNICATION'],
                ['Pazar araştırmasını nasıl yaparsınız?', 'How do you do market research?', 'LEARNING_AGILITY'],
                ['Satış kapatma teknikleriniz nelerdir?', 'What are your closing techniques?', 'CUSTOMER_FOCUS'],
                ['Müşteri ilişkilerini nasıl sürdürürsünüz?', 'How do you maintain customer relationships?', 'CUSTOMER_FOCUS'],
                ['Yasal süreçler hakkında bilginiz var mı?', 'Do you have knowledge about legal processes?', 'RELIABILITY'],
                ['Dijital pazarlamayı nasıl kullanırsınız?', 'How do you use digital marketing?', 'ADAPTABILITY'],
            ]);
        }
    }

    private function seedMarketingPositions(): void
    {
        // Digital Marketing
        $sd = JobSubdomain::where('code', 'MKT_DIGITAL')->first();
        if ($sd) {
            $this->createPos($sd, 'MKT_DIGITAL_SPECIALIST', 'Dijital Pazarlama Uzmanı', 'Digital Marketing Specialist', 'SPECIALIST', 1, 4, ['PROBLEM_SOLVING', 'LEARNING_AGILITY', 'COMMUNICATION', 'ATTENTION_TO_DETAIL'], [
                ['Dijital pazarlama stratejisi nasıl oluşturursunuz?', 'How do you create a digital marketing strategy?', 'PROBLEM_SOLVING'],
                ['Hangi dijital kanalları kullanıyorsunuz?', 'What digital channels do you use?', 'LEARNING_AGILITY'],
                ['ROI ölçümünü nasıl yaparsınız?', 'How do you measure ROI?', 'ATTENTION_TO_DETAIL'],
                ['A/B testleri konusundaki deneyiminiz nedir?', 'What is your experience with A/B testing?', 'PROBLEM_SOLVING'],
                ['SEO optimizasyonu nasıl yaparsınız?', 'How do you do SEO optimization?', 'ATTENTION_TO_DETAIL'],
                ['Sosyal medya yönetimi deneyiminiz nedir?', 'What is your social media management experience?', 'COMMUNICATION'],
                ['Analitik araçlarla nasıl çalışırsınız?', 'How do you work with analytics tools?', 'LEARNING_AGILITY'],
                ['İçerik stratejisi nasıl oluşturursunuz?', 'How do you create content strategy?', 'PROBLEM_SOLVING'],
            ]);

            $this->createPos($sd, 'MKT_DIGITAL_MANAGER', 'Dijital Pazarlama Müdürü', 'Digital Marketing Manager', 'MANAGER', 5, 10, ['LEADERSHIP', 'PROBLEM_SOLVING', 'COMMUNICATION', 'ADAPTABILITY'], [
                ['Dijital pazarlama ekibini nasıl yönetirsiniz?', 'How do you manage the digital marketing team?', 'LEADERSHIP'],
                ['Bütçe yönetimini nasıl yaparsınız?', 'How do you manage the budget?', 'ATTENTION_TO_DETAIL'],
                ['Marka bilinirliğini nasıl artırırsınız?', 'How do you increase brand awareness?', 'PROBLEM_SOLVING'],
                ['Ajanslarla nasıl çalışırsınız?', 'How do you work with agencies?', 'COMMUNICATION'],
                ['Dijital trendleri nasıl takip edersiniz?', 'How do you follow digital trends?', 'ADAPTABILITY'],
                ['Performans raporlamasını nasıl yaparsınız?', 'How do you do performance reporting?', 'COMMUNICATION'],
                ['Kriz iletişimini nasıl yönetirsiniz?', 'How do you manage crisis communication?', 'PROBLEM_SOLVING'],
                ['Satış ekibiyle nasıl koordine çalışırsınız?', 'How do you coordinate with the sales team?', 'TEAMWORK'],
            ]);
        }

        // Content
        $sd = JobSubdomain::where('code', 'MKT_CONTENT')->first();
        if ($sd) {
            $this->createPos($sd, 'MKT_CONTENT_WRITER', 'İçerik Yazarı', 'Content Writer', 'SPECIALIST', 0, 4, ['COMMUNICATION', 'ATTENTION_TO_DETAIL', 'LEARNING_AGILITY', 'ADAPTABILITY'], [
                ['Yazım sürecinizi anlatın.', 'Describe your writing process.', 'COMMUNICATION'],
                ['Farklı hedef kitlelere nasıl yazarsınız?', 'How do you write for different target audiences?', 'ADAPTABILITY'],
                ['SEO uyumlu içerik nasıl oluşturursunuz?', 'How do you create SEO-friendly content?', 'LEARNING_AGILITY'],
                ['Araştırma sürecinizi anlatın.', 'Describe your research process.', 'ATTENTION_TO_DETAIL'],
                ['Deadline baskısı altında nasıl çalışırsınız?', 'How do you work under deadline pressure?', 'TIME_MANAGEMENT'],
                ['Geri bildirimi nasıl değerlendirirsiniz?', 'How do you evaluate feedback?', 'ADAPTABILITY'],
                ['Hangi konularda yazma deneyiminiz var?', 'What topics do you have writing experience in?', 'LEARNING_AGILITY'],
                ['İçerik performansını nasıl ölçersiniz?', 'How do you measure content performance?', 'ATTENTION_TO_DETAIL'],
            ]);
        }
    }

    private function seedHRPositions(): void
    {
        // Recruitment
        $sd = JobSubdomain::where('code', 'HR_RECRUIT')->first();
        if ($sd) {
            $this->createPos($sd, 'HR_RECRUIT_SPECIALIST', 'İşe Alım Uzmanı', 'Recruitment Specialist', 'SPECIALIST', 1, 4, ['COMMUNICATION', 'CUSTOMER_FOCUS', 'PROBLEM_SOLVING', 'ATTENTION_TO_DETAIL'], [
                ['Aday bulma stratejileriniz nelerdir?', 'What are your candidate sourcing strategies?', 'PROBLEM_SOLVING'],
                ['Mülakat sürecini nasıl yönetirsiniz?', 'How do you manage the interview process?', 'COMMUNICATION'],
                ['Aday değerlendirmesini nasıl yaparsınız?', 'How do you evaluate candidates?', 'ATTENTION_TO_DETAIL'],
                ['İşe alım metrikleri hakkında bilginiz var mı?', 'Do you have knowledge about recruitment metrics?', 'LEARNING_AGILITY'],
                ['İşveren markasını nasıl güçlendirirsiniz?', 'How do you strengthen the employer brand?', 'CUSTOMER_FOCUS'],
                ['Zor pozisyonları nasıl doldurursunuz?', 'How do you fill difficult positions?', 'PROBLEM_SOLVING'],
                ['Yöneticilerle nasıl çalışırsınız?', 'How do you work with managers?', 'COMMUNICATION'],
                ['Aday deneyimini nasıl iyileştirirsiniz?', 'How do you improve candidate experience?', 'CUSTOMER_FOCUS'],
            ]);

            $this->createPos($sd, 'HR_RECRUIT_MANAGER', 'İşe Alım Müdürü', 'Recruitment Manager', 'MANAGER', 5, 10, ['LEADERSHIP', 'PROBLEM_SOLVING', 'COMMUNICATION', 'TIME_MANAGEMENT'], [
                ['İşe alım stratejisini nasıl belirlersiniz?', 'How do you determine the recruitment strategy?', 'LEADERSHIP'],
                ['Ekibinizi nasıl yönetirsiniz?', 'How do you manage your team?', 'LEADERSHIP'],
                ['Bütçe yönetimini nasıl yaparsınız?', 'How do you manage the budget?', 'TIME_MANAGEMENT'],
                ['Tedarikçi ilişkilerini nasıl yönetirsiniz?', 'How do you manage supplier relationships?', 'COMMUNICATION'],
                ['Performans takibini nasıl yaparsınız?', 'How do you track performance?', 'ATTENTION_TO_DETAIL'],
                ['Çeşitlilik ve kapsayıcılık konusundaki yaklaşımınız nedir?', 'What is your approach to diversity and inclusion?', 'CUSTOMER_FOCUS'],
                ['İşe alım teknolojilerini nasıl kullanırsınız?', 'How do you use recruitment technologies?', 'LEARNING_AGILITY'],
                ['Üst yönetime nasıl raporlama yaparsınız?', 'How do you report to upper management?', 'COMMUNICATION'],
            ]);
        }

        // Training
        $sd = JobSubdomain::where('code', 'HR_TRAINING')->first();
        if ($sd) {
            $this->createPos($sd, 'HR_TRAINING_SPECIALIST', 'Eğitim Uzmanı', 'Training Specialist', 'SPECIALIST', 2, 5, ['COMMUNICATION', 'LEARNING_AGILITY', 'PROBLEM_SOLVING', 'CUSTOMER_FOCUS'], [
                ['Eğitim ihtiyaç analizi nasıl yaparsınız?', 'How do you conduct training needs analysis?', 'PROBLEM_SOLVING'],
                ['Eğitim programları nasıl tasarlarsınız?', 'How do you design training programs?', 'LEARNING_AGILITY'],
                ['Farklı öğrenme stillerine nasıl uyum sağlarsınız?', 'How do you adapt to different learning styles?', 'ADAPTABILITY'],
                ['Eğitim etkinliğini nasıl ölçersiniz?', 'How do you measure training effectiveness?', 'ATTENTION_TO_DETAIL'],
                ['E-öğrenme deneyiminiz var mı?', 'Do you have e-learning experience?', 'LEARNING_AGILITY'],
                ['Sunum becerilerinizi nasıl geliştirirsiniz?', 'How do you develop your presentation skills?', 'COMMUNICATION'],
                ['Eğitim bütçesini nasıl yönetirsiniz?', 'How do you manage the training budget?', 'TIME_MANAGEMENT'],
                ['Yöneticilerle nasıl işbirliği yaparsınız?', 'How do you collaborate with managers?', 'COMMUNICATION'],
            ]);
        }
    }

    private function seedLegalPositions(): void
    {
        // Corporate Law
        $sd = JobSubdomain::where('code', 'LEG_CORPORATE')->first();
        if ($sd) {
            $this->createPos($sd, 'LEG_CORPORATE_LAWYER', 'Şirket Avukatı', 'Corporate Lawyer', 'SPECIALIST', 2, 7, ['ATTENTION_TO_DETAIL', 'PROBLEM_SOLVING', 'COMMUNICATION', 'RELIABILITY'], [
                ['Sözleşme inceleme sürecinizi anlatın.', 'Describe your contract review process.', 'ATTENTION_TO_DETAIL'],
                ['Risk değerlendirmesini nasıl yaparsınız?', 'How do you conduct risk assessment?', 'PROBLEM_SOLVING'],
                ['Müzakere süreçlerini nasıl yönetirsiniz?', 'How do you manage negotiation processes?', 'COMMUNICATION'],
                ['Mevzuat değişikliklerini nasıl takip edersiniz?', 'How do you follow regulatory changes?', 'LEARNING_AGILITY'],
                ['Şirket yönetimine nasıl danışmanlık yaparsınız?', 'How do you advise company management?', 'COMMUNICATION'],
                ['Dış avukatlarla nasıl çalışırsınız?', 'How do you work with external lawyers?', 'TEAMWORK'],
                ['Uyuşmazlık çözümü konusundaki deneyiminiz nedir?', 'What is your experience in dispute resolution?', 'PROBLEM_SOLVING'],
                ['Gizlilik ve veri koruma konusundaki bilginiz nedir?', 'What is your knowledge on confidentiality and data protection?', 'RELIABILITY'],
            ]);

            $this->createPos($sd, 'LEG_CORPORATE_COUNSEL', 'Hukuk Müşaviri', 'Legal Counsel', 'MANAGER', 7, 15, ['LEADERSHIP', 'PROBLEM_SOLVING', 'COMMUNICATION', 'ATTENTION_TO_DETAIL'], [
                ['Hukuk departmanını nasıl yönetirsiniz?', 'How do you manage the legal department?', 'LEADERSHIP'],
                ['Şirket stratejisine hukuki katkınız nedir?', 'What is your legal contribution to company strategy?', 'PROBLEM_SOLVING'],
                ['Uyum programlarını nasıl tasarlarsınız?', 'How do you design compliance programs?', 'ATTENTION_TO_DETAIL'],
                ['Yönetim kuruluna nasıl raporlama yaparsınız?', 'How do you report to the board?', 'COMMUNICATION'],
                ['Hukuki bütçeyi nasıl yönetirsiniz?', 'How do you manage the legal budget?', 'TIME_MANAGEMENT'],
                ['Dış hukuk danışmanlarını nasıl yönetirsiniz?', 'How do you manage external legal advisors?', 'LEADERSHIP'],
                ['Kriz yönetiminde rolünüz nedir?', 'What is your role in crisis management?', 'PROBLEM_SOLVING'],
                ['Uluslararası hukuki konularda deneyiminiz var mı?', 'Do you have experience in international legal matters?', 'LEARNING_AGILITY'],
            ]);
        }
    }

    private function seedCustomerServicePositions(): void
    {
        // Call Center
        $sd = JobSubdomain::where('code', 'CS_CALLCENTER')->first();
        if ($sd) {
            $this->createPos($sd, 'CS_CALLCENTER_AGENT', 'Çağrı Merkezi Temsilcisi', 'Call Center Agent', 'ENTRY', 0, 2, ['CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING', 'ADAPTABILITY'], [
                ['Müşteri şikayetlerini nasıl ele alırsınız?', 'How do you handle customer complaints?', 'CUSTOMER_FOCUS'],
                ['Stresli aramalarla nasıl başa çıkarsınız?', 'How do you deal with stressful calls?', 'ADAPTABILITY'],
                ['Çözüm sunarken hangi adımları izlersiniz?', 'What steps do you follow when offering solutions?', 'PROBLEM_SOLVING'],
                ['Çağrı kalitesini nasıl yüksek tutarsınız?', 'How do you maintain high call quality?', 'COMMUNICATION'],
                ['CRM sistemleri hakkında deneyiminiz var mı?', 'Do you have experience with CRM systems?', 'LEARNING_AGILITY'],
                ['Satış odaklı çağrılarda nasıl çalışırsınız?', 'How do you work on sales-oriented calls?', 'CUSTOMER_FOCUS'],
                ['Hedeflere ulaşmak için neler yaparsınız?', 'What do you do to achieve targets?', 'RELIABILITY'],
                ['Ekip içinde nasıl iletişim kurarsınız?', 'How do you communicate within the team?', 'TEAMWORK'],
            ]);

            $this->createPos($sd, 'CS_CALLCENTER_SUPERVISOR', 'Çağrı Merkezi Şefi', 'Call Center Supervisor', 'COORDINATOR', 2, 5, ['LEADERSHIP', 'CUSTOMER_FOCUS', 'TIME_MANAGEMENT', 'PROBLEM_SOLVING'], [
                ['Ekibinizi nasıl yönetirsiniz?', 'How do you manage your team?', 'LEADERSHIP'],
                ['Performans takibini nasıl yaparsınız?', 'How do you track performance?', 'ATTENTION_TO_DETAIL'],
                ['Eskalasyon süreçlerini nasıl yönetirsiniz?', 'How do you manage escalation processes?', 'PROBLEM_SOLVING'],
                ['Eğitim ihtiyaçlarını nasıl belirlersiniz?', 'How do you identify training needs?', 'LEADERSHIP'],
                ['Kalite güvencesini nasıl sağlarsınız?', 'How do you ensure quality assurance?', 'CUSTOMER_FOCUS'],
                ['Vardiya planlamasını nasıl yaparsınız?', 'How do you do shift planning?', 'TIME_MANAGEMENT'],
                ['Motivasyonu nasıl yüksek tutarsınız?', 'How do you keep motivation high?', 'LEADERSHIP'],
                ['Raporlamayı nasıl yaparsınız?', 'How do you do reporting?', 'COMMUNICATION'],
            ]);
        }
    }

    private function seedSecurityPositions(): void
    {
        // Physical Security
        $sd = JobSubdomain::where('code', 'SEC_PHYSICAL')->first();
        if ($sd) {
            $this->createPos($sd, 'SEC_PHYSICAL_GUARD', 'Güvenlik Görevlisi', 'Security Guard', 'ENTRY', 0, 3, ['RELIABILITY', 'ATTENTION_TO_DETAIL', 'COMMUNICATION', 'ADAPTABILITY'], [
                ['Güvenlik prosedürlerini nasıl uygularsınız?', 'How do you implement security procedures?', 'RELIABILITY'],
                ['Şüpheli bir durumla karşılaştığınızda ne yaparsınız?', 'What do you do when you encounter a suspicious situation?', 'PROBLEM_SOLVING'],
                ['Ziyaretçi kontrolünü nasıl yaparsınız?', 'How do you do visitor control?', 'ATTENTION_TO_DETAIL'],
                ['Acil durumlarda nasıl hareket edersiniz?', 'How do you act in emergencies?', 'ADAPTABILITY'],
                ['Devriye görevlerini nasıl yerine getirirsiniz?', 'How do you perform patrol duties?', 'RELIABILITY'],
                ['Güvenlik ekipmanlarını nasıl kullanırsınız?', 'How do you use security equipment?', 'LEARNING_AGILITY'],
                ['Çatışma çözümünde nasıl yaklaşım sergilersiniz?', 'How do you approach conflict resolution?', 'COMMUNICATION'],
                ['Raporlama süreciniz nasıl işler?', 'How does your reporting process work?', 'ATTENTION_TO_DETAIL'],
            ]);

            $this->createPos($sd, 'SEC_PHYSICAL_SUPERVISOR', 'Güvenlik Amiri', 'Security Supervisor', 'COORDINATOR', 3, 7, ['LEADERSHIP', 'PROBLEM_SOLVING', 'COMMUNICATION', 'RELIABILITY'], [
                ['Güvenlik ekibini nasıl yönetirsiniz?', 'How do you manage the security team?', 'LEADERSHIP'],
                ['Vardiya planlamasını nasıl yaparsınız?', 'How do you do shift planning?', 'TIME_MANAGEMENT'],
                ['Güvenlik risklerini nasıl değerlendirirsiniz?', 'How do you assess security risks?', 'PROBLEM_SOLVING'],
                ['Eğitim programlarını nasıl organize edersiniz?', 'How do you organize training programs?', 'LEADERSHIP'],
                ['Olay raporlamasını nasıl yönetirsiniz?', 'How do you manage incident reporting?', 'ATTENTION_TO_DETAIL'],
                ['Yetkili makamlarla nasıl koordine çalışırsınız?', 'How do you coordinate with authorities?', 'COMMUNICATION'],
                ['Güvenlik sistemlerini nasıl denetlersiniz?', 'How do you supervise security systems?', 'ATTENTION_TO_DETAIL'],
                ['Performans değerlendirmesini nasıl yaparsınız?', 'How do you conduct performance evaluation?', 'LEADERSHIP'],
            ]);
        }
    }

    private function seedCleaningPositions(): void
    {
        // General Cleaning
        $sd = JobSubdomain::where('code', 'CLN_GENERAL')->first();
        if ($sd) {
            $this->createPos($sd, 'CLN_GENERAL_CLEANER', 'Temizlik Görevlisi', 'Cleaner', 'ENTRY', 0, 2, ['RELIABILITY', 'ATTENTION_TO_DETAIL', 'TIME_MANAGEMENT', 'TEAMWORK'], [
                ['Temizlik sürecinizi anlatın.', 'Describe your cleaning process.', 'ATTENTION_TO_DETAIL'],
                ['Temizlik kimyasallarını nasıl kullanırsınız?', 'How do you use cleaning chemicals?', 'RELIABILITY'],
                ['İş güvenliğine nasıl dikkat edersiniz?', 'How do you pay attention to work safety?', 'RELIABILITY'],
                ['Yoğun alanlarda nasıl çalışırsınız?', 'How do you work in busy areas?', 'ADAPTABILITY'],
                ['Ekipman bakımını nasıl yaparsınız?', 'How do you maintain equipment?', 'ATTENTION_TO_DETAIL'],
                ['Zaman yönetimini nasıl yaparsınız?', 'How do you manage time?', 'TIME_MANAGEMENT'],
                ['Özel istekleri nasıl karşılarsınız?', 'How do you meet special requests?', 'CUSTOMER_FOCUS'],
                ['Ekip arkadaşlarınızla nasıl çalışırsınız?', 'How do you work with teammates?', 'TEAMWORK'],
            ]);

            $this->createPos($sd, 'CLN_GENERAL_SUPERVISOR', 'Temizlik Şefi', 'Cleaning Supervisor', 'COORDINATOR', 2, 5, ['LEADERSHIP', 'TIME_MANAGEMENT', 'ATTENTION_TO_DETAIL', 'COMMUNICATION'], [
                ['Temizlik ekibini nasıl yönetirsiniz?', 'How do you manage the cleaning team?', 'LEADERSHIP'],
                ['İş planlamasını nasıl yaparsınız?', 'How do you do work planning?', 'TIME_MANAGEMENT'],
                ['Kalite kontrolünü nasıl sağlarsınız?', 'How do you ensure quality control?', 'ATTENTION_TO_DETAIL'],
                ['Malzeme yönetimini nasıl yaparsınız?', 'How do you manage supplies?', 'TIME_MANAGEMENT'],
                ['Müşteri geri bildirimlerini nasıl değerlendirirsiniz?', 'How do you evaluate customer feedback?', 'CUSTOMER_FOCUS'],
                ['Personel eğitimini nasıl organize edersiniz?', 'How do you organize staff training?', 'LEADERSHIP'],
                ['Şikayetleri nasıl yönetirsiniz?', 'How do you manage complaints?', 'PROBLEM_SOLVING'],
                ['Raporlamayı nasıl yaparsınız?', 'How do you do reporting?', 'COMMUNICATION'],
            ]);
        }
    }

    private function seedAutomotivePositions(): void
    {
        // Sales
        $sd = JobSubdomain::where('code', 'AUTO_SALES')->first();
        if ($sd) {
            $this->createPos($sd, 'AUTO_SALES_CONSULTANT', 'Satış Danışmanı', 'Sales Consultant', 'SPECIALIST', 1, 5, ['CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING', 'RELIABILITY'], [
                ['Müşteri ihtiyaç analizi nasıl yaparsınız?', 'How do you analyze customer needs?', 'CUSTOMER_FOCUS'],
                ['Araç sunumunu nasıl yaparsınız?', 'How do you present vehicles?', 'COMMUNICATION'],
                ['Fiyat müzakeresini nasıl yönetirsiniz?', 'How do you manage price negotiation?', 'PROBLEM_SOLVING'],
                ['Test sürüşü sürecini nasıl yönetirsiniz?', 'How do you manage test drive process?', 'CUSTOMER_FOCUS'],
                ['Finansman seçeneklerini nasıl sunarsınız?', 'How do you present financing options?', 'COMMUNICATION'],
                ['Satış sonrası takibi nasıl yaparsınız?', 'How do you do after-sales follow-up?', 'CUSTOMER_FOCUS'],
                ['Hedeflere ulaşmak için neler yaparsınız?', 'What do you do to achieve targets?', 'PROBLEM_SOLVING'],
                ['Rakip ürünleri nasıl değerlendirirsiniz?', 'How do you evaluate competitor products?', 'LEARNING_AGILITY'],
            ]);
        }

        // Service
        $sd = JobSubdomain::where('code', 'AUTO_SERVICE')->first();
        if ($sd) {
            $this->createPos($sd, 'AUTO_SERVICE_TECHNICIAN', 'Oto Servis Teknisyeni', 'Auto Service Technician', 'SPECIALIST', 1, 5, ['ATTENTION_TO_DETAIL', 'PROBLEM_SOLVING', 'RELIABILITY', 'LEARNING_AGILITY'], [
                ['Arıza teşhis sürecinizi anlatın.', 'Describe your fault diagnosis process.', 'PROBLEM_SOLVING'],
                ['Hangi marka ve modellerde deneyiminiz var?', 'What brands and models do you have experience with?', 'LEARNING_AGILITY'],
                ['Güvenlik prosedürlerine nasıl uyarsınız?', 'How do you comply with safety procedures?', 'RELIABILITY'],
                ['Yeni teknolojileri nasıl takip edersiniz?', 'How do you follow new technologies?', 'LEARNING_AGILITY'],
                ['Kalite kontrolünü nasıl yaparsınız?', 'How do you do quality control?', 'ATTENTION_TO_DETAIL'],
                ['Müşteri ile iletişiminiz nasıl?', 'How is your communication with customers?', 'COMMUNICATION'],
                ['Zor arızalarla nasıl başa çıkarsınız?', 'How do you deal with difficult faults?', 'PROBLEM_SOLVING'],
                ['Ekip arkadaşlarınızla nasıl çalışırsınız?', 'How do you work with teammates?', 'TEAMWORK'],
            ]);

            $this->createPos($sd, 'AUTO_SERVICE_ADVISOR', 'Servis Danışmanı', 'Service Advisor', 'SPECIALIST', 2, 5, ['CUSTOMER_FOCUS', 'COMMUNICATION', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT'], [
                ['Müşteri karşılama sürecinizi anlatın.', 'Describe your customer greeting process.', 'CUSTOMER_FOCUS'],
                ['İş emirlerini nasıl hazırlarsınız?', 'How do you prepare work orders?', 'ATTENTION_TO_DETAIL'],
                ['Müşteri şikayetlerini nasıl yönetirsiniz?', 'How do you manage customer complaints?', 'PROBLEM_SOLVING'],
                ['Fiyat açıklamasını nasıl yaparsınız?', 'How do you explain pricing?', 'COMMUNICATION'],
                ['Randevu planlamasını nasıl yaparsınız?', 'How do you do appointment scheduling?', 'TIME_MANAGEMENT'],
                ['Ek satış önerilerini nasıl sunarsınız?', 'How do you present upselling suggestions?', 'CUSTOMER_FOCUS'],
                ['Teknisyenlerle nasıl koordine çalışırsınız?', 'How do you coordinate with technicians?', 'TEAMWORK'],
                ['Teslimat sürecini nasıl yönetirsiniz?', 'How do you manage the delivery process?', 'CUSTOMER_FOCUS'],
            ]);
        }
    }

    private function seedAgriculturePositions(): void
    {
        // Farming
        $sd = JobSubdomain::where('code', 'AGR_FARMING')->first();
        if ($sd) {
            $this->createPos($sd, 'AGR_FARMING_WORKER', 'Tarım İşçisi', 'Farm Worker', 'ENTRY', 0, 3, ['RELIABILITY', 'ADAPTABILITY', 'TEAMWORK', 'ATTENTION_TO_DETAIL'], [
                ['Tarımsal çalışma deneyiminizi anlatın.', 'Describe your agricultural work experience.', 'LEARNING_AGILITY'],
                ['Hangi tarım makinelerini kullanabilirsiniz?', 'What agricultural machines can you use?', 'LEARNING_AGILITY'],
                ['Farklı hava koşullarında nasıl çalışırsınız?', 'How do you work in different weather conditions?', 'ADAPTABILITY'],
                ['Hasat süreçlerini nasıl yönetirsiniz?', 'How do you manage harvest processes?', 'TIME_MANAGEMENT'],
                ['Sulama sistemleri hakkında bilginiz var mı?', 'Do you have knowledge about irrigation systems?', 'LEARNING_AGILITY'],
                ['İş güvenliğine nasıl dikkat edersiniz?', 'How do you pay attention to work safety?', 'RELIABILITY'],
                ['Ekip çalışmasına nasıl katkı sağlarsınız?', 'How do you contribute to teamwork?', 'TEAMWORK'],
                ['Bitki sağlığını nasıl takip edersiniz?', 'How do you monitor plant health?', 'ATTENTION_TO_DETAIL'],
            ]);

            $this->createPos($sd, 'AGR_FARMING_MANAGER', 'Çiftlik Müdürü', 'Farm Manager', 'MANAGER', 5, 10, ['LEADERSHIP', 'PROBLEM_SOLVING', 'TIME_MANAGEMENT', 'ATTENTION_TO_DETAIL'], [
                ['Çiftlik operasyonlarını nasıl yönetirsiniz?', 'How do you manage farm operations?', 'LEADERSHIP'],
                ['Üretim planlamasını nasıl yaparsınız?', 'How do you do production planning?', 'TIME_MANAGEMENT'],
                ['Bütçe yönetimini nasıl yaparsınız?', 'How do you manage the budget?', 'ATTENTION_TO_DETAIL'],
                ['Personel yönetimini nasıl yaparsınız?', 'How do you manage staff?', 'LEADERSHIP'],
                ['Mahsul verimini nasıl optimize edersiniz?', 'How do you optimize crop yield?', 'PROBLEM_SOLVING'],
                ['Sürdürülebilir tarım uygulamalarınız nelerdir?', 'What are your sustainable farming practices?', 'LEARNING_AGILITY'],
                ['Tedarikçi ve alıcı ilişkilerini nasıl yönetirsiniz?', 'How do you manage supplier and buyer relationships?', 'COMMUNICATION'],
                ['Risk yönetimini nasıl yaparsınız?', 'How do you manage risks?', 'PROBLEM_SOLVING'],
            ]);
        }
    }

    private function seedBeautyPositions(): void
    {
        // Hairdressing
        $sd = JobSubdomain::where('code', 'BEAUTY_HAIR')->first();
        if ($sd) {
            $this->createPos($sd, 'BEAUTY_HAIR_STYLIST', 'Kuaför', 'Hairstylist', 'SPECIALIST', 0, 5, ['CUSTOMER_FOCUS', 'COMMUNICATION', 'ATTENTION_TO_DETAIL', 'LEARNING_AGILITY'], [
                ['Müşteri danışmanlığını nasıl yaparsınız?', 'How do you do customer consultation?', 'CUSTOMER_FOCUS'],
                ['Farklı saç tiplerine nasıl yaklaşırsınız?', 'How do you approach different hair types?', 'ATTENTION_TO_DETAIL'],
                ['Renk uygulamalarındaki uzmanlığınız nedir?', 'What is your expertise in color applications?', 'LEARNING_AGILITY'],
                ['Trendleri nasıl takip edersiniz?', 'How do you follow trends?', 'LEARNING_AGILITY'],
                ['Müşteri memnuniyetsizliğiyle nasıl başa çıkarsınız?', 'How do you deal with customer dissatisfaction?', 'PROBLEM_SOLVING'],
                ['Salon hijyenine nasıl dikkat edersiniz?', 'How do you pay attention to salon hygiene?', 'RELIABILITY'],
                ['Ürün önerilerini nasıl yaparsınız?', 'How do you make product recommendations?', 'CUSTOMER_FOCUS'],
                ['Randevu yönetimini nasıl yaparsınız?', 'How do you manage appointments?', 'TIME_MANAGEMENT'],
            ]);
        }

        // Spa & Massage
        $sd = JobSubdomain::where('code', 'BEAUTY_SPA')->first();
        if ($sd) {
            $this->createPos($sd, 'BEAUTY_SPA_THERAPIST', 'Spa Terapisti', 'Spa Therapist', 'SPECIALIST', 0, 5, ['CUSTOMER_FOCUS', 'ATTENTION_TO_DETAIL', 'COMMUNICATION', 'RELIABILITY'], [
                ['Masaj tekniklerinizi anlatın.', 'Describe your massage techniques.', 'ATTENTION_TO_DETAIL'],
                ['Müşteri ihtiyaçlarını nasıl belirlersiniz?', 'How do you determine customer needs?', 'CUSTOMER_FOCUS'],
                ['Hijyen standartlarına nasıl uyarsınız?', 'How do you comply with hygiene standards?', 'RELIABILITY'],
                ['Farklı cilt tiplerine nasıl yaklaşırsınız?', 'How do you approach different skin types?', 'ATTENTION_TO_DETAIL'],
                ['Rahatlatıcı atmosfer nasıl oluşturursunuz?', 'How do you create a relaxing atmosphere?', 'CUSTOMER_FOCUS'],
                ['Ürün bilginizi nasıl güncel tutarsınız?', 'How do you keep your product knowledge up to date?', 'LEARNING_AGILITY'],
                ['Fiziksel dayanıklılığınızı nasıl korursunuz?', 'How do you maintain your physical endurance?', 'ADAPTABILITY'],
                ['Müşteri gizliliğine nasıl dikkat edersiniz?', 'How do you pay attention to customer privacy?', 'RELIABILITY'],
            ]);
        }
    }

    private function seedChildcarePositions(): void
    {
        // Nannying
        $sd = JobSubdomain::where('code', 'CHILD_NANNY')->first();
        if ($sd) {
            $this->createPos($sd, 'CHILD_NANNY', 'Çocuk Bakıcısı / Dadı', 'Nanny', 'SPECIALIST', 0, 5, ['CUSTOMER_FOCUS', 'RELIABILITY', 'COMMUNICATION', 'ADAPTABILITY'], [
                ['Çocuk bakımı deneyiminizi anlatın.', 'Describe your childcare experience.', 'CUSTOMER_FOCUS'],
                ['Günlük rutinleri nasıl oluşturursunuz?', 'How do you create daily routines?', 'TIME_MANAGEMENT'],
                ['Acil durumlara nasıl hazırlıklı olursunuz?', 'How do you prepare for emergencies?', 'RELIABILITY'],
                ['Ebeveynlerle iletişiminizi nasıl yönetirsiniz?', 'How do you manage communication with parents?', 'COMMUNICATION'],
                ['Çocukların gelişimini nasıl desteklersiniz?', 'How do you support children\'s development?', 'CUSTOMER_FOCUS'],
                ['Disiplin konusundaki yaklaşımınız nedir?', 'What is your approach to discipline?', 'COMMUNICATION'],
                ['Yaratıcı aktiviteleri nasıl planlarsınız?', 'How do you plan creative activities?', 'ADAPTABILITY'],
                ['Farklı yaş gruplarıyla nasıl çalışırsınız?', 'How do you work with different age groups?', 'ADAPTABILITY'],
            ]);
        }

        // Daycare
        $sd = JobSubdomain::where('code', 'CHILD_DAYCARE')->first();
        if ($sd) {
            $this->createPos($sd, 'CHILD_DAYCARE_TEACHER', 'Kreş Öğretmeni', 'Daycare Teacher', 'SPECIALIST', 0, 5, ['CUSTOMER_FOCUS', 'COMMUNICATION', 'RELIABILITY', 'ADAPTABILITY'], [
                ['Günlük program planlamasını nasıl yaparsınız?', 'How do you plan daily programs?', 'TIME_MANAGEMENT'],
                ['Çocukların sosyal gelişimini nasıl desteklersiniz?', 'How do you support children\'s social development?', 'CUSTOMER_FOCUS'],
                ['Ebeveyn iletişimini nasıl yönetirsiniz?', 'How do you manage parent communication?', 'COMMUNICATION'],
                ['Güvenlik önlemlerine nasıl dikkat edersiniz?', 'How do you pay attention to safety measures?', 'RELIABILITY'],
                ['Grup yönetimini nasıl yaparsınız?', 'How do you manage groups?', 'LEADERSHIP'],
                ['Özel ihtiyaçları olan çocuklarla nasıl çalışırsınız?', 'How do you work with children with special needs?', 'ADAPTABILITY'],
                ['Hijyen kurallarına nasıl uyarsınız?', 'How do you comply with hygiene rules?', 'RELIABILITY'],
                ['Çatışma çözümünde nasıl yaklaşım sergilersiniz?', 'How do you approach conflict resolution?', 'PROBLEM_SOLVING'],
            ]);
        }
    }

    private function createPos(JobSubdomain $sd, string $code, string $nameTr, string $nameEn, string $archetype, int $expMin, int $expMax, array $comps, array $questions): void
    {
        $position = JobPosition::updateOrCreate(
            ['code' => $code],
            [
                'subdomain_id' => $sd->id,
                'archetype_id' => $this->archetypes[$archetype] ?? null,
                'code' => $code,
                'name_tr' => $nameTr,
                'name_en' => $nameEn,
                'experience_min_years' => $expMin,
                'experience_max_years' => $expMax,
                'is_active' => true,
            ]
        );

        $compData = [];
        foreach ($comps as $i => $c) {
            if (isset($this->competencies[$c])) {
                $compData[$this->competencies[$c]] = ['weight' => 10 - $i, 'is_critical' => $i === 0, 'min_score' => 3, 'sort_order' => $i + 1];
            }
        }
        if (!empty($compData)) $position->competencies()->sync($compData);

        foreach ($questions as $i => $q) {
            PositionQuestion::updateOrCreate(
                ['position_id' => $position->id, 'question_tr' => $q[0]],
                [
                    'position_id' => $position->id,
                    'competency_id' => isset($q[2]) ? ($this->competencies[$q[2]] ?? null) : null,
                    'question_type' => 'behavioral',
                    'question_tr' => $q[0],
                    'question_en' => $q[1],
                    'sort_order' => $i + 1,
                    'is_mandatory' => $i < 3,
                    'is_active' => true,
                ]
            );
        }
    }
}
