<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\AssessmentTemplate;
use App\Models\AssessmentSession;
use App\Models\AssessmentResult;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo employees with assessment results
        $this->createTezgahtarDemo();
        $this->createUretimDemo();
        $this->createMudurDemo();
    }

    private function createTezgahtarDemo(): void
    {
        // Employee 1: Good performer - Hire
        $this->createDemoEmployee([
            'first_name' => 'Elif',
            'last_name' => 'Yılmaz',
            'employee_code' => 'TZG-001',
            'current_role' => 'Tezgahtar',
            'department' => 'Satış',
            'branch' => 'Kadıköy Şubesi',
            'hire_date' => now()->subMonths(8),
        ], 'kasiyer', [
            'overall_score' => 78,
            'level_label' => 'iyi',
            'level_numeric' => 4,
            'risk_level' => 'low',
            'promotion_suitable' => true,
            'promotion_readiness' => 'developing',
            'promotion_notes' => 'Müşteri ilişkileri güçlü. 6 ay sonra kasa sorumlusu pozisyonuna hazır olabilir.',
            'competency_scores' => [
                'musteri_iliskisi' => [
                    'score' => 85,
                    'weight' => 0.25,
                    'weighted_score' => 21.25,
                    'feedback' => 'Müşterilerle sıcak ve profesyonel iletişim kuruyor. Şikayetleri sakinlikle çözüyor.',
                    'evidence' => [
                        'Zor müşteri senaryosunda empati kurarak çözüm önerdi',
                        'Beden dili ve göz teması konusunda bilinçli yaklaşım gösterdi',
                    ],
                ],
                'urun_bilgisi' => [
                    'score' => 72,
                    'weight' => 0.20,
                    'weighted_score' => 14.4,
                    'feedback' => 'Temel ürün bilgisine sahip ancak yeni ürünlerde gelişime açık.',
                    'evidence' => [
                        'Klasik ürünleri doğru tanımlıyor',
                        'Sezonluk ürün bilgisinde eksiklik var',
                    ],
                ],
                'kasa_islemleri' => [
                    'score' => 80,
                    'weight' => 0.20,
                    'weighted_score' => 16,
                    'feedback' => 'Kasa işlemlerini hızlı ve hatasız yapıyor. Para üstü hesabı doğru.',
                    'evidence' => [
                        'İade senaryosunda prosedürü doğru uyguladı',
                        'POS cihazı kullanımında deneyimli',
                    ],
                ],
                'hijyen_standartlari' => [
                    'score' => 75,
                    'weight' => 0.20,
                    'weighted_score' => 15,
                    'feedback' => 'Hijyen kurallarına uyuyor ancak tezgah düzeni konusunda daha dikkatli olmalı.',
                    'evidence' => [
                        'El hijyeni protokolünü biliyor',
                        'Ürün sergileme düzeni iyileştirilebilir',
                    ],
                ],
                'takim_uyumu' => [
                    'score' => 82,
                    'weight' => 0.15,
                    'weighted_score' => 12.3,
                    'feedback' => 'Ekip arkadaşlarıyla uyumlu çalışıyor, yardımlaşma konusunda istekli.',
                    'evidence' => [
                        'Yoğun saatlerde destek vermeye gönüllü',
                        'İletişimde açık ve yapıcı',
                    ],
                ],
            ],
            'risk_flags' => [],
            'strengths' => [
                ['competency' => 'Müşteri İlişkileri', 'description' => 'Empati kurma ve sorun çözme becerisi yüksek'],
                ['competency' => 'Takım Uyumu', 'description' => 'İşbirliğine açık, destekleyici tutum'],
            ],
            'improvement_areas' => [
                ['competency' => 'Ürün Bilgisi', 'description' => 'Yeni ve sezonluk ürünler hakkında eğitim almalı', 'priority' => 'medium'],
                ['competency' => 'Hijyen Standartları', 'description' => 'Tezgah düzeni ve görsel standartlar konusunda gelişmeli', 'priority' => 'low'],
            ],
            'development_plan' => [
                ['area' => 'Ürün Eğitimi', 'actions' => ['Haftalık ürün tanıtım toplantılarına katılım', 'Yeni ürün katalogunu inceleme'], 'priority' => 'medium', 'timeline' => '1 ay'],
                ['area' => 'Liderlik Gelişimi', 'actions' => ['Kasa sorumlusu gölgeleme programı', 'Vardiya koordinasyonu deneyimi'], 'priority' => 'low', 'timeline' => '3 ay'],
            ],
            'question_analyses' => [
                ['question_order' => 1, 'competency_code' => 'musteri_iliskisi', 'score' => 85, 'max_score' => 100, 'analysis' => 'Müşteri şikayeti senaryosunda profesyonel yaklaşım sergiledi.', 'positive_points' => ['Aktif dinleme', 'Çözüm odaklı'], 'negative_points' => []],
                ['question_order' => 2, 'competency_code' => 'urun_bilgisi', 'score' => 70, 'max_score' => 100, 'analysis' => 'Temel bilgi yeterli ancak detaylarda eksik.', 'positive_points' => ['Ana ürünleri tanıyor'], 'negative_points' => ['Yeni ürünleri bilmiyor']],
                ['question_order' => 3, 'competency_code' => 'kasa_islemleri', 'score' => 80, 'max_score' => 100, 'analysis' => 'İade prosedürünü doğru uyguladı.', 'positive_points' => ['Prosedür bilgisi', 'Hız'], 'negative_points' => []],
            ],
            'manager_summary' => 'Elif Yılmaz, müşteri ilişkileri konusunda güçlü bir performans sergiliyor. Empati kurma becerisi ve problem çözme yaklaşımı olumlu. Kasa işlemlerinde hızlı ve güvenilir. Ürün bilgisi ve hijyen standartları konusunda gelişime açık alanlar mevcut. Takım içinde uyumlu ve destekleyici bir profil çiziyor. 6 aylık gelişim planı sonrası kasa sorumlusu pozisyonuna aday olabilir.',
            'hiring_recommendation' => 'hire',
        ]);

        // Employee 2: Needs training
        $this->createDemoEmployee([
            'first_name' => 'Mehmet',
            'last_name' => 'Kaya',
            'employee_code' => 'TZG-002',
            'current_role' => 'Tezgahtar',
            'department' => 'Satış',
            'branch' => 'Beşiktaş Şubesi',
            'hire_date' => now()->subMonths(2),
        ], 'kasiyer', [
            'overall_score' => 58,
            'level_label' => 'yeterli',
            'level_numeric' => 3,
            'risk_level' => 'medium',
            'promotion_suitable' => false,
            'promotion_readiness' => 'not_ready',
            'promotion_notes' => 'Öncelikle mevcut pozisyondaki eksiklikler giderilmeli.',
            'competency_scores' => [
                'musteri_iliskisi' => [
                    'score' => 55,
                    'weight' => 0.25,
                    'weighted_score' => 13.75,
                    'feedback' => 'Müşteri iletişiminde mesafeli kalıyor. Şikayet durumlarında savunmacı tavır alıyor.',
                    'evidence' => [
                        'Zor müşteri senaryosunda empati kurmakta zorlandı',
                        'Ses tonu ve beden dilinde iyileştirme gerekli',
                    ],
                ],
                'urun_bilgisi' => [
                    'score' => 62,
                    'weight' => 0.20,
                    'weighted_score' => 12.4,
                    'feedback' => 'Ürün bilgisi ortalama düzeyde. Fiyatlandırma konusunda tereddütlü.',
                    'evidence' => [
                        'Bazı ürün özelliklerini karıştırıyor',
                        'Kampanya bilgisinde eksiklik var',
                    ],
                ],
                'kasa_islemleri' => [
                    'score' => 68,
                    'weight' => 0.20,
                    'weighted_score' => 13.6,
                    'feedback' => 'Kasa işlemlerinde yavaş. Yoğun saatlerde stres altında hata yapma riski var.',
                    'evidence' => [
                        'İade işleminde prosedür hatası yaptı',
                        'Para sayımında dikkatli ancak yavaş',
                    ],
                ],
                'hijyen_standartlari' => [
                    'score' => 52,
                    'weight' => 0.20,
                    'weighted_score' => 10.4,
                    'feedback' => 'Hijyen protokollerinde eksiklik var. Ürün teması konusunda dikkatli değil.',
                    'evidence' => [
                        'Eldiven kullanımını atladı',
                        'Tezgah temizliğini ihmal etti',
                    ],
                ],
                'takim_uyumu' => [
                    'score' => 55,
                    'weight' => 0.15,
                    'weighted_score' => 8.25,
                    'feedback' => 'Ekip içinde sessiz kalıyor. Yardım istemekten çekiniyor.',
                    'evidence' => [
                        'Bireysel çalışmayı tercih ediyor',
                        'İletişimde pasif',
                    ],
                ],
            ],
            'risk_flags' => [
                ['code' => 'hijyen_riski', 'label' => 'Hijyen Protokol İhlali', 'severity' => 'medium', 'detected_in_question' => 4, 'evidence' => 'Ürün teması öncesi hijyen protokolünü uygulamadı'],
                ['code' => 'stres_toleransi', 'label' => 'Düşük Stres Toleransı', 'severity' => 'low', 'detected_in_question' => 3, 'evidence' => 'Yoğunluk senaryosunda panik belirtileri gösterdi'],
            ],
            'strengths' => [
                ['competency' => 'Dürüstlük', 'description' => 'Para konusunda güvenilir, hata yaptığında bildiriyor'],
            ],
            'improvement_areas' => [
                ['competency' => 'Müşteri İlişkileri', 'description' => 'İletişim ve empati eğitimi gerekli', 'priority' => 'high'],
                ['competency' => 'Hijyen Standartları', 'description' => 'Hijyen protokolleri acil eğitim gerektiriyor', 'priority' => 'high'],
                ['competency' => 'Stres Yönetimi', 'description' => 'Yoğun saatlerde destek ve rehberlik sağlanmalı', 'priority' => 'medium'],
            ],
            'development_plan' => [
                ['area' => 'Hijyen Eğitimi', 'actions' => ['Zorunlu hijyen sertifikası yenileme', 'Günlük hijyen checklist takibi'], 'priority' => 'high', 'timeline' => '2 hafta'],
                ['area' => 'İletişim Becerileri', 'actions' => ['Müşteri iletişimi workshop', 'Rol yapma egzersizleri'], 'priority' => 'high', 'timeline' => '1 ay'],
                ['area' => 'Mentorluk', 'actions' => ['Deneyimli personel eşliğinde çalışma', 'Haftalık performans görüşmeleri'], 'priority' => 'medium', 'timeline' => '2 ay'],
            ],
            'question_analyses' => [
                ['question_order' => 1, 'competency_code' => 'musteri_iliskisi', 'score' => 55, 'max_score' => 100, 'analysis' => 'Müşteri şikayetinde savunmacı tutum aldı.', 'positive_points' => ['Sonunda özür diledi'], 'negative_points' => ['Empati kuramadı', 'Savunmacı']],
                ['question_order' => 2, 'competency_code' => 'hijyen_standartlari', 'score' => 50, 'max_score' => 100, 'analysis' => 'Hijyen protokolünü atladı.', 'positive_points' => [], 'negative_points' => ['Eldiven kullanmadı', 'Protokol bilgisi eksik']],
            ],
            'manager_summary' => 'Mehmet Kaya, mevcut performansında kritik gelişim alanları bulunuyor. Hijyen protokollerine uyum konusunda acil eğitim gerekiyor - bu alan gıda sektöründe kritik önem taşıyor. Müşteri iletişiminde empati eksikliği ve savunmacı tutum gözlemlendi. Stres altında performansı düşüyor. Dürüstlük ve güvenilirlik konusunda olumlu izlenim var. 2 aylık yoğun eğitim ve mentorluk programı sonrası yeniden değerlendirme önerilir.',
            'hiring_recommendation' => 'train',
        ]);

        // Employee 3: Risky - Red flags
        $this->createDemoEmployee([
            'first_name' => 'Ali',
            'last_name' => 'Demir',
            'employee_code' => 'TZG-003',
            'current_role' => 'Tezgahtar',
            'department' => 'Satış',
            'branch' => 'Bakırköy Şubesi',
            'hire_date' => now()->subMonths(1),
        ], 'kasiyer', [
            'overall_score' => 42,
            'level_label' => 'gelisime_acik',
            'level_numeric' => 2,
            'risk_level' => 'high',
            'promotion_suitable' => false,
            'promotion_readiness' => 'not_ready',
            'promotion_notes' => 'Mevcut pozisyonda devam riski yüksek.',
            'competency_scores' => [
                'musteri_iliskisi' => [
                    'score' => 38,
                    'weight' => 0.25,
                    'weighted_score' => 9.5,
                    'feedback' => 'Müşterilere karşı ilgisiz ve sabırsız. Şikayet durumlarında agresif tepki verdi.',
                    'evidence' => [
                        'Müşteriyle tartıştı',
                        'Göz temasından kaçındı',
                        'Kaba üslup kullandı',
                    ],
                ],
                'urun_bilgisi' => [
                    'score' => 45,
                    'weight' => 0.20,
                    'weighted_score' => 9,
                    'feedback' => 'Ürün bilgisi yetersiz. Yanlış bilgi verme eğilimi var.',
                    'evidence' => [
                        'Ürün fiyatını yanlış söyledi',
                        'İçerik bilgisinde ciddi hatalar yaptı',
                    ],
                ],
                'kasa_islemleri' => [
                    'score' => 50,
                    'weight' => 0.20,
                    'weighted_score' => 10,
                    'feedback' => 'Kasa işlemlerinde dikkatsiz. Para üstü hatası yapma riski yüksek.',
                    'evidence' => [
                        'Fiş vermeyi unuttu',
                        'Kasa kapanış prosedürünü bilmiyor',
                    ],
                ],
                'hijyen_standartlari' => [
                    'score' => 35,
                    'weight' => 0.20,
                    'weighted_score' => 7,
                    'feedback' => 'Hijyen kurallarını ciddiye almıyor. Gıda güvenliği riski oluşturuyor.',
                    'evidence' => [
                        'Yiyeceklere çıplak elle temas etti',
                        'Hijyen uyarısına olumsuz tepki verdi',
                        'Kişisel hijyen standardı düşük',
                    ],
                ],
                'takim_uyumu' => [
                    'score' => 40,
                    'weight' => 0.15,
                    'weighted_score' => 6,
                    'feedback' => 'Ekiple çatışma yaşıyor. Otorite sorunları var.',
                    'evidence' => [
                        'Vardiya arkadaşıyla tartıştı',
                        'Yönetici talimatını sorguladı',
                    ],
                ],
            ],
            'risk_flags' => [
                ['code' => 'gida_guvenligi', 'label' => 'Gıda Güvenliği Riski', 'severity' => 'critical', 'detected_in_question' => 4, 'evidence' => 'Gıdaya çıplak elle temas, hijyen protokolünü reddetme'],
                ['code' => 'musteri_catismasi', 'label' => 'Müşteri Çatışması Riski', 'severity' => 'high', 'detected_in_question' => 1, 'evidence' => 'Müşteriyle tartışma, agresif üslup'],
                ['code' => 'otorite_sorunu', 'label' => 'Otorite Sorunu', 'severity' => 'medium', 'detected_in_question' => 5, 'evidence' => 'Yönetici talimatlarını sorgulama, ekiple çatışma'],
            ],
            'strengths' => [],
            'improvement_areas' => [
                ['competency' => 'Hijyen Standartları', 'description' => 'Kritik seviyede - gıda güvenliği eğitimi zorunlu', 'priority' => 'high'],
                ['competency' => 'Müşteri İlişkileri', 'description' => 'Davranış ve iletişim eğitimi gerekli', 'priority' => 'high'],
                ['competency' => 'Profesyonel Tutum', 'description' => 'Kurumsal kültür ve beklentiler netleştirilmeli', 'priority' => 'high'],
            ],
            'development_plan' => [
                ['area' => 'Performans İyileştirme Planı', 'actions' => ['Zorunlu hijyen sertifikası', 'Davranış koçluğu', '2 haftalık gözlem süreci'], 'priority' => 'high', 'timeline' => '2 hafta'],
            ],
            'question_analyses' => [
                ['question_order' => 1, 'competency_code' => 'musteri_iliskisi', 'score' => 35, 'max_score' => 100, 'analysis' => 'Müşteriyle tartıştı, agresif tutum sergiledi.', 'positive_points' => [], 'negative_points' => ['Agresif', 'Sabırsız', 'Özür dilemedi']],
                ['question_order' => 4, 'competency_code' => 'hijyen_standartlari', 'score' => 30, 'max_score' => 100, 'analysis' => 'Hijyen kurallarını ihlal etti ve uyarıyı kabul etmedi.', 'positive_points' => [], 'negative_points' => ['Çıplak elle temas', 'Uyarıyı reddetti']],
            ],
            'manager_summary' => 'Ali Demir, birden fazla kritik alanda ciddi risk oluşturuyor. Hijyen standartlarına uyumsuzluk gıda güvenliği açısından kabul edilemez seviyede. Müşterilerle yaşanan çatışma ve agresif tutum marka itibarını tehdit ediyor. Otorite sorunları ekip dinamiğini olumsuz etkiliyor. Güçlü yön tespit edilemedi. 2 haftalık sıkı performans iyileştirme planı uygulanmalı; gelişim göstermezse pozisyondan ayrılması değerlendirilmeli.',
            'hiring_recommendation' => 'risky',
        ]);
    }

    private function createUretimDemo(): void
    {
        // Employee 1: Good performer
        $this->createDemoEmployee([
            'first_name' => 'Fatma',
            'last_name' => 'Özkan',
            'employee_code' => 'URT-001',
            'current_role' => 'Üretim Personeli',
            'department' => 'Üretim',
            'branch' => 'Merkez Fabrika',
            'hire_date' => now()->subYears(2),
        ], 'depo_sorumlusu', [
            'overall_score' => 82,
            'level_label' => 'iyi',
            'level_numeric' => 4,
            'risk_level' => 'low',
            'promotion_suitable' => true,
            'promotion_readiness' => 'ready',
            'promotion_notes' => 'Üretim şefi pozisyonuna hazır. Teknik bilgi ve liderlik potansiyeli yüksek.',
            'competency_scores' => [
                'uretim_teknikleri' => [
                    'score' => 88,
                    'weight' => 0.25,
                    'weighted_score' => 22,
                    'feedback' => 'Üretim tekniklerinde uzman seviyede. Makine kullanımı ve bakımı konusunda yetkin.',
                    'evidence' => [
                        'Tüm üretim makinelerini kullanabiliyor',
                        'Arıza tespiti ve basit bakım yapabiliyor',
                    ],
                ],
                'hijyen_kalite' => [
                    'score' => 90,
                    'weight' => 0.25,
                    'weighted_score' => 22.5,
                    'feedback' => 'Hijyen ve kalite standartlarına tam uyum. Örnek personel.',
                    'evidence' => [
                        'HACCP kurallarını eksiksiz uyguluyor',
                        'Kalite kontrol süreçlerini biliyor',
                    ],
                ],
                'is_guvenligi' => [
                    'score' => 78,
                    'weight' => 0.20,
                    'weighted_score' => 15.6,
                    'feedback' => 'İş güvenliği kurallarına uyuyor. KKD kullanımında dikkatli.',
                    'evidence' => [
                        'Güvenlik ekipmanlarını düzenli kullanıyor',
                        'Tehlike bildirimlerini zamanında yapıyor',
                    ],
                ],
                'takim_calisma' => [
                    'score' => 75,
                    'weight' => 0.15,
                    'weighted_score' => 11.25,
                    'feedback' => 'Takım çalışmasına yatkın. Yeni personele rehberlik yapıyor.',
                    'evidence' => [
                        'Vardiya değişimlerinde koordinasyon sağlıyor',
                        'Bilgi paylaşımında istekli',
                    ],
                ],
                'verimlilik' => [
                    'score' => 80,
                    'weight' => 0.15,
                    'weighted_score' => 12,
                    'feedback' => 'Hedeflerin üzerinde verimlilik. Fire oranı düşük.',
                    'evidence' => [
                        'Günlük üretim hedeflerini aşıyor',
                        'Hammadde kullanımında tasarruflu',
                    ],
                ],
            ],
            'risk_flags' => [],
            'strengths' => [
                ['competency' => 'Hijyen & Kalite', 'description' => 'HACCP standartlarında örnek uygulama'],
                ['competency' => 'Üretim Teknikleri', 'description' => 'Teknik bilgi ve makine kullanımında uzman'],
                ['competency' => 'Verimlilik', 'description' => 'Yüksek üretkenlik ve düşük fire oranı'],
            ],
            'improvement_areas' => [
                ['competency' => 'Liderlik', 'description' => 'Şeflik pozisyonu için yönetim becerileri geliştirilebilir', 'priority' => 'medium'],
            ],
            'development_plan' => [
                ['area' => 'Liderlik Eğitimi', 'actions' => ['Şef adayı programına katılım', 'Vardiya koordinasyonu deneyimi'], 'priority' => 'medium', 'timeline' => '2 ay'],
                ['area' => 'Teknik Sertifikasyon', 'actions' => ['İleri düzey makine operatörlüğü sertifikası'], 'priority' => 'low', 'timeline' => '3 ay'],
            ],
            'question_analyses' => [
                ['question_order' => 1, 'competency_code' => 'hijyen_kalite', 'score' => 90, 'max_score' => 100, 'analysis' => 'HACCP prosedürlerini mükemmel uyguladı.', 'positive_points' => ['Protokol bilgisi tam', 'Detaylara dikkat'], 'negative_points' => []],
                ['question_order' => 2, 'competency_code' => 'uretim_teknikleri', 'score' => 88, 'max_score' => 100, 'analysis' => 'Makine arıza senaryosunu başarıyla çözdü.', 'positive_points' => ['Hızlı teşhis', 'Doğru müdahale'], 'negative_points' => []],
            ],
            'manager_summary' => 'Fatma Özkan, üretim departmanının en deneyimli ve güvenilir personellerinden biri. Hijyen ve kalite standartlarında örnek uygulama sergiliyor. Teknik bilgisi ve makine kullanım becerisi üst düzeyde. Verimlilik metrikleri sürekli hedeflerin üzerinde. Takım içinde doğal liderlik özellikleri gösteriyor. Üretim şefi pozisyonuna terfi için güçlü aday. 2 aylık liderlik eğitimi ile hazırlık tamamlanabilir.',
            'hiring_recommendation' => 'hire',
        ]);

        // Employee 2: Medium performer
        $this->createDemoEmployee([
            'first_name' => 'Hasan',
            'last_name' => 'Çelik',
            'employee_code' => 'URT-002',
            'current_role' => 'Üretim Personeli',
            'department' => 'Üretim',
            'branch' => 'Merkez Fabrika',
            'hire_date' => now()->subMonths(6),
        ], 'depo_sorumlusu', [
            'overall_score' => 61,
            'level_label' => 'yeterli',
            'level_numeric' => 3,
            'risk_level' => 'medium',
            'promotion_suitable' => false,
            'promotion_readiness' => 'developing',
            'promotion_notes' => 'Mevcut pozisyonda gelişim sağlaması gerekiyor.',
            'competency_scores' => [
                'uretim_teknikleri' => [
                    'score' => 65,
                    'weight' => 0.25,
                    'weighted_score' => 16.25,
                    'feedback' => 'Temel üretim tekniklerini biliyor ancak karmaşık işlemlerde desteğe ihtiyaç duyuyor.',
                    'evidence' => [
                        'Standart işlemleri yapabiliyor',
                        'Makine ayarlarında tereddütlü',
                    ],
                ],
                'hijyen_kalite' => [
                    'score' => 58,
                    'weight' => 0.25,
                    'weighted_score' => 14.5,
                    'feedback' => 'Hijyen kurallarını genellikle uyguluyor ancak bazen atlıyor.',
                    'evidence' => [
                        'Temel hijyen bilgisi var',
                        'Yoğun dönemlerde protokolü ihmal ediyor',
                    ],
                ],
                'is_guvenligi' => [
                    'score' => 70,
                    'weight' => 0.20,
                    'weighted_score' => 14,
                    'feedback' => 'İş güvenliği kurallarına uyuyor. KKD kullanımı yeterli.',
                    'evidence' => [
                        'Güvenlik ekipmanlarını kullanıyor',
                        'Tehlike farkındalığı orta düzeyde',
                    ],
                ],
                'takim_calisma' => [
                    'score' => 55,
                    'weight' => 0.15,
                    'weighted_score' => 8.25,
                    'feedback' => 'Takım içinde pasif. İletişimde çekingen.',
                    'evidence' => [
                        'Yardım istemekte zorlanıyor',
                        'Grup toplantılarında sessiz',
                    ],
                ],
                'verimlilik' => [
                    'score' => 58,
                    'weight' => 0.15,
                    'weighted_score' => 8.7,
                    'feedback' => 'Hedeflere yakın ancak tutarsız performans.',
                    'evidence' => [
                        'Bazı günler hedefin altında kalıyor',
                        'Fire oranı ortalamanın üstünde',
                    ],
                ],
            ],
            'risk_flags' => [
                ['code' => 'hijyen_tutarsizlik', 'label' => 'Hijyen Tutarsızlığı', 'severity' => 'medium', 'detected_in_question' => 2, 'evidence' => 'Yoğun dönemlerde hijyen protokolünü atladığını belirtti'],
            ],
            'strengths' => [
                ['competency' => 'İş Güvenliği', 'description' => 'KKD kullanımında disiplinli'],
            ],
            'improvement_areas' => [
                ['competency' => 'Hijyen & Kalite', 'description' => 'Tutarlı hijyen uygulaması için takip gerekli', 'priority' => 'high'],
                ['competency' => 'Verimlilik', 'description' => 'Fire oranı düşürülmeli', 'priority' => 'medium'],
                ['competency' => 'İletişim', 'description' => 'Takım içi iletişimi güçlendirmeli', 'priority' => 'low'],
            ],
            'development_plan' => [
                ['area' => 'Hijyen Takibi', 'actions' => ['Günlük hijyen checklist', 'Aylık hijyen denetimi'], 'priority' => 'high', 'timeline' => '1 ay'],
                ['area' => 'Teknik Gelişim', 'actions' => ['Makine operatörü eğitimi', 'Fire azaltma workshop'], 'priority' => 'medium', 'timeline' => '2 ay'],
            ],
            'question_analyses' => [
                ['question_order' => 1, 'competency_code' => 'hijyen_kalite', 'score' => 58, 'max_score' => 100, 'analysis' => 'Hijyen protokolünü bildiğini ancak her zaman uygulamadığını belirtti.', 'positive_points' => ['Dürüst yanıt'], 'negative_points' => ['Tutarsız uygulama']],
            ],
            'manager_summary' => 'Hasan Çelik, orta düzeyde bir performans sergiliyor. Temel üretim becerilerine sahip ancak karmaşık işlemlerde gelişime ihtiyacı var. Hijyen protokollerinde tutarsızlık gözlemlendi - özellikle yoğun dönemlerde. İş güvenliği konusunda disiplinli. Verimlilik dalgalı ve fire oranı yüksek. Takım içinde daha aktif olması gerekiyor. 2 aylık yapılandırılmış gelişim programı ile potansiyelini ortaya koyabilir.',
            'hiring_recommendation' => 'train',
        ]);
    }

    private function createMudurDemo(): void
    {
        // Employee 1: Strong manager
        $this->createDemoEmployee([
            'first_name' => 'Ayşe',
            'last_name' => 'Arslan',
            'employee_code' => 'MGR-001',
            'current_role' => 'Mağaza Müdürü',
            'department' => 'Yönetim',
            'branch' => 'Şişli Şubesi',
            'hire_date' => now()->subYears(4),
        ], 'sube_muduru', [
            'overall_score' => 87,
            'level_label' => 'mukemmel',
            'level_numeric' => 5,
            'risk_level' => 'low',
            'promotion_suitable' => true,
            'promotion_readiness' => 'highly_ready',
            'promotion_notes' => 'Bölge müdürlüğü pozisyonuna güçlü aday. Stratejik düşünce ve liderlik becerileri olgun.',
            'competency_scores' => [
                'liderlik' => [
                    'score' => 92,
                    'weight' => 0.25,
                    'weighted_score' => 23,
                    'feedback' => 'Ekibini motive ediyor, performans yönetiminde başarılı. Doğal lider.',
                    'evidence' => [
                        'Ekip devir hızı sektör ortalamasının altında',
                        'Çalışan memnuniyeti yüksek',
                        'Performans görüşmelerini düzenli yapıyor',
                    ],
                ],
                'operasyonel_yonetim' => [
                    'score' => 88,
                    'weight' => 0.25,
                    'weighted_score' => 22,
                    'feedback' => 'Mağaza operasyonlarını verimli yönetiyor. Süreç iyileştirmelerinde proaktif.',
                    'evidence' => [
                        'Stok yönetimi optimum seviyede',
                        'Operasyonel maliyetler bütçe dahilinde',
                        'Vardiya planlaması etkin',
                    ],
                ],
                'satis_performansi' => [
                    'score' => 85,
                    'weight' => 0.20,
                    'weighted_score' => 17,
                    'feedback' => 'Satış hedeflerini sürekli aşıyor. Müşteri memnuniyeti yüksek.',
                    'evidence' => [
                        'Son 12 ayın 10unda hedef üstü',
                        'NPS skoru bölge ortalamasının üstünde',
                    ],
                ],
                'finansal_yonetim' => [
                    'score' => 82,
                    'weight' => 0.15,
                    'weighted_score' => 12.3,
                    'feedback' => 'Bütçe kontrolü iyi. Maliyet bilinci yüksek.',
                    'evidence' => [
                        'Bütçe sapması minimal',
                        'Karlılık hedeflerini tutturuyor',
                    ],
                ],
                'problem_cozme' => [
                    'score' => 88,
                    'weight' => 0.15,
                    'weighted_score' => 13.2,
                    'feedback' => 'Kriz yönetimi güçlü. Hızlı ve etkili karar alıyor.',
                    'evidence' => [
                        'Acil durumları başarıyla yönetti',
                        'Müşteri şikayetlerini çözüme kavuşturuyor',
                    ],
                ],
            ],
            'risk_flags' => [],
            'strengths' => [
                ['competency' => 'Liderlik', 'description' => 'Ekip motivasyonu ve performans yönetiminde mükemmel'],
                ['competency' => 'Operasyonel Yönetim', 'description' => 'Süreç verimliliği ve maliyet kontrolü üst düzeyde'],
                ['competency' => 'Problem Çözme', 'description' => 'Kriz anlarında soğukkanlı ve etkili'],
            ],
            'improvement_areas' => [
                ['competency' => 'Stratejik Planlama', 'description' => 'Bölge düzeyinde stratejik düşünme becerisi geliştirilebilir', 'priority' => 'low'],
            ],
            'development_plan' => [
                ['area' => 'Bölge Müdürlüğü Hazırlık', 'actions' => ['Çoklu mağaza yönetimi gölgeleme', 'Stratejik planlama workshop', 'Bölge toplantılarına katılım'], 'priority' => 'medium', 'timeline' => '3 ay'],
            ],
            'question_analyses' => [
                ['question_order' => 1, 'competency_code' => 'liderlik', 'score' => 92, 'max_score' => 100, 'analysis' => 'Düşük performanslı çalışan senaryosunu mükemmel yönetti.', 'positive_points' => ['Empati', 'Net geri bildirim', 'Gelişim odaklı'], 'negative_points' => []],
                ['question_order' => 2, 'competency_code' => 'problem_cozme', 'score' => 88, 'max_score' => 100, 'analysis' => 'Kriz senaryosunda hızlı ve doğru kararlar aldı.', 'positive_points' => ['Soğukkanlılık', 'Önceliklendirme', 'İletişim'], 'negative_points' => []],
            ],
            'manager_summary' => 'Ayşe Arslan, örnek bir mağaza müdürü profili çiziyor. Liderlik becerileri olgun; ekibini motive etme ve performans yönetiminde başarılı. Operasyonel verimlilikte sektör standartlarının üzerinde. Satış hedeflerini tutarlı şekilde aşıyor. Kriz yönetimi ve problem çözme kapasitesi yüksek. Finansal disiplin sağlam. Bölge müdürlüğü pozisyonuna hazır; 3 aylık geçiş programı ile terfi edilebilir.',
            'hiring_recommendation' => 'hire',
        ]);

        // Employee 2: Developing manager
        $this->createDemoEmployee([
            'first_name' => 'Burak',
            'last_name' => 'Şahin',
            'employee_code' => 'MGR-002',
            'current_role' => 'Mağaza Müdürü',
            'department' => 'Yönetim',
            'branch' => 'Ümraniye Şubesi',
            'hire_date' => now()->subMonths(10),
        ], 'sube_muduru', [
            'overall_score' => 65,
            'level_label' => 'yeterli',
            'level_numeric' => 3,
            'risk_level' => 'medium',
            'promotion_suitable' => false,
            'promotion_readiness' => 'developing',
            'promotion_notes' => 'Mevcut pozisyonda deneyim kazanması gerekiyor. Liderlik becerilerinde gelişim alanları var.',
            'competency_scores' => [
                'liderlik' => [
                    'score' => 58,
                    'weight' => 0.25,
                    'weighted_score' => 14.5,
                    'feedback' => 'Ekip yönetiminde zorluk yaşıyor. Geri bildirim vermekte çekingen.',
                    'evidence' => [
                        'Ekip içi çatışmaları çözmekte zorlanıyor',
                        'Performans görüşmelerini erteliyor',
                        'Delegasyon yetersiz',
                    ],
                ],
                'operasyonel_yonetim' => [
                    'score' => 72,
                    'weight' => 0.25,
                    'weighted_score' => 18,
                    'feedback' => 'Temel operasyonları yönetiyor ancak süreç iyileştirmede pasif.',
                    'evidence' => [
                        'Günlük operasyonlar akıyor',
                        'Stok fazlası problemi var',
                    ],
                ],
                'satis_performansi' => [
                    'score' => 68,
                    'weight' => 0.20,
                    'weighted_score' => 13.6,
                    'feedback' => 'Satış hedeflerine yakın ancak tutarlılık sorunu var.',
                    'evidence' => [
                        'Aydan aya dalgalanma',
                        'Kampanya yönetimi zayıf',
                    ],
                ],
                'finansal_yonetim' => [
                    'score' => 62,
                    'weight' => 0.15,
                    'weighted_score' => 9.3,
                    'feedback' => 'Bütçe kontrolünde zorluk. Maliyet farkındalığı düşük.',
                    'evidence' => [
                        'Operasyonel giderler bütçeyi aşıyor',
                        'Fire oranı yüksek',
                    ],
                ],
                'problem_cozme' => [
                    'score' => 60,
                    'weight' => 0.15,
                    'weighted_score' => 9,
                    'feedback' => 'Sorunları üst yönetime aktarma eğilimi. Karar almada tereddütlü.',
                    'evidence' => [
                        'Basit sorunları bile yöneticiye danışıyor',
                        'Kriz anında panik yapabiliyor',
                    ],
                ],
            ],
            'risk_flags' => [
                ['code' => 'liderlik_eksikligi', 'label' => 'Liderlik Eksikliği', 'severity' => 'medium', 'detected_in_question' => 1, 'evidence' => 'Ekip çatışmalarını çözmekte zorlandığını ve geri bildirim vermekten kaçındığını belirtti'],
                ['code' => 'butce_asimi', 'label' => 'Bütçe Aşımı Riski', 'severity' => 'low', 'detected_in_question' => 4, 'evidence' => 'Operasyonel giderlerin kontrol edilmesinde zorluk yaşadığını ifade etti'],
            ],
            'strengths' => [
                ['competency' => 'Operasyonel Yönetim', 'description' => 'Günlük operasyonları sürdürme kapasitesi var'],
                ['competency' => 'Teknik Bilgi', 'description' => 'Mağaza sistemleri ve süreçleri hakkında bilgili'],
            ],
            'improvement_areas' => [
                ['competency' => 'Liderlik', 'description' => 'Ekip yönetimi ve geri bildirim becerileri geliştirilmeli', 'priority' => 'high'],
                ['competency' => 'Karar Alma', 'description' => 'Bağımsız karar alma kapasitesi artırılmalı', 'priority' => 'high'],
                ['competency' => 'Finansal Yönetim', 'description' => 'Maliyet kontrolü ve bütçe disiplini güçlendirilmeli', 'priority' => 'medium'],
            ],
            'development_plan' => [
                ['area' => 'Liderlik Koçluğu', 'actions' => ['Haftalık koçluk seansları', 'Geri bildirim teknikleri workshop', 'Çatışma yönetimi eğitimi'], 'priority' => 'high', 'timeline' => '2 ay'],
                ['area' => 'Yönetsel Gelişim', 'actions' => ['Deneyimli müdür gölgeleme', 'Karar alma simülasyonları'], 'priority' => 'high', 'timeline' => '3 ay'],
                ['area' => 'Finansal Eğitim', 'actions' => ['Bütçe yönetimi kursu', 'Maliyet analizi workshop'], 'priority' => 'medium', 'timeline' => '1 ay'],
            ],
            'question_analyses' => [
                ['question_order' => 1, 'competency_code' => 'liderlik', 'score' => 58, 'max_score' => 100, 'analysis' => 'Ekip çatışması senaryosunda kararsız kaldı.', 'positive_points' => ['Durumu analiz etti'], 'negative_points' => ['Karar almaktan kaçındı', 'Çözüm öneremedi']],
                ['question_order' => 4, 'competency_code' => 'finansal_yonetim', 'score' => 60, 'max_score' => 100, 'analysis' => 'Bütçe aşımı sorusunda maliyet kontrolü eksikliği ortaya çıktı.', 'positive_points' => ['Sorunu fark ediyor'], 'negative_points' => ['Çözüm stratejisi yok']],
            ],
            'manager_summary' => 'Burak Şahin, müdürlük pozisyonunda gelişim aşamasında. Temel operasyonel yetkinliklere sahip ancak liderlik becerilerinde ciddi eksiklikler var. Ekip yönetimi, geri bildirim verme ve karar alma konularında desteklenmesi gerekiyor. Finansal disiplin zayıf; bütçe aşımları yaşanıyor. Problem çözmede üst yönetime bağımlı kalıyor. 3 aylık yoğun gelişim programı ve koçluk desteği ile potansiyelini ortaya koyabilir.',
            'hiring_recommendation' => 'train',
        ]);
    }

    private function createDemoEmployee(array $employeeData, string $roleCategory, array $resultData): void
    {
        // Get or create template
        $template = AssessmentTemplate::where('role_category', $roleCategory)->first();

        if (!$template) {
            $template = AssessmentTemplate::create([
                'name' => ucfirst(str_replace('_', ' ', $roleCategory)) . ' Değerlendirme',
                'slug' => $roleCategory . '-assessment',
                'role_category' => $roleCategory,
                'description' => ucfirst(str_replace('_', ' ', $roleCategory)) . ' pozisyonu için yetkinlik değerlendirme şablonu',
                'competencies' => [],
                'red_flags' => [],
                'questions' => [],
                'scoring_config' => [
                    'levels' => [
                        ['min' => 0, 'max' => 39, 'label' => 'basarisiz', 'numeric' => 1],
                        ['min' => 40, 'max' => 54, 'label' => 'gelisime_acik', 'numeric' => 2],
                        ['min' => 55, 'max' => 69, 'label' => 'yeterli', 'numeric' => 3],
                        ['min' => 70, 'max' => 84, 'label' => 'iyi', 'numeric' => 4],
                        ['min' => 85, 'max' => 100, 'label' => 'mukemmel', 'numeric' => 5],
                    ],
                    'pass_threshold' => 55,
                ],
                'time_limit_minutes' => 45,
                'is_active' => true,
            ]);
        }

        // Create employee
        $employee = Employee::create(array_merge($employeeData, [
            'status' => 'active',
            'company_id' => null, // Will be set in actual implementation
        ]));

        // Create assessment session
        $session = AssessmentSession::create([
            'employee_id' => $employee->id,
            'template_id' => $template->id,
            'access_token' => bin2hex(random_bytes(32)),
            'token_expires_at' => now()->addDays(7),
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(1),
            'time_spent_seconds' => rand(1800, 2700),
        ]);

        // Create assessment result
        AssessmentResult::create([
            'session_id' => $session->id,
            'status' => 'completed',
            'ai_model' => 'gpt-4-turbo-preview',
            'input_tokens' => rand(2000, 4000),
            'output_tokens' => rand(1500, 3000),
            'cost_usd' => rand(5, 15) / 100,
            'analyzed_at' => now()->subHours(1),
            'overall_score' => $resultData['overall_score'],
            'competency_scores' => $resultData['competency_scores'],
            'risk_flags' => $resultData['risk_flags'],
            'risk_level' => $resultData['risk_level'],
            'level_label' => $resultData['level_label'],
            'level_numeric' => $resultData['level_numeric'],
            'development_plan' => $resultData['development_plan'],
            'strengths' => $resultData['strengths'],
            'improvement_areas' => $resultData['improvement_areas'],
            'promotion_suitable' => $resultData['promotion_suitable'],
            'promotion_readiness' => $resultData['promotion_readiness'],
            'promotion_notes' => $resultData['promotion_notes'],
            'question_analyses' => $resultData['question_analyses'] ?? [],
        ]);
    }
}
