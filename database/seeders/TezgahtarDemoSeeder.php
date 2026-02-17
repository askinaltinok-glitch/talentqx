<?php

namespace Database\Seeders;

use App\Models\AssessmentTemplate;
use Illuminate\Database\Seeder;

class TezgahtarDemoSeeder extends Seeder
{
    public function run(): void
    {
        AssessmentTemplate::updateOrCreate(
            ['slug' => 'tezgahtar-kasiyer-demo'],
            $this->getDemoTemplate()
        );
    }

    private function getDemoTemplate(): array
    {
        return [
            'name' => 'Tezgahtar/Kasiyer DEMO (10 Soru)',
            'slug' => 'tezgahtar-kasiyer-demo',
            'role_category' => 'CASHIER',
            'description' => 'Mağaza satış ve kasa personeli için serbest metin tabanlı yetkinlik değerlendirmesi. Dürüstlük, hijyen, müşteri iletişimi ve stres yönetimi ölçümlenir.',
            'competencies' => [
                [
                    'code' => 'integrity',
                    'name' => 'Dürüstlük',
                    'name_en' => 'Integrity',
                    'description' => 'Para, ürün ve bilgi konusunda dürüst davranış',
                    'weight' => 0.25,
                ],
                [
                    'code' => 'hygiene_responsibility',
                    'name' => 'Hijyen ve Sorumluluk',
                    'name_en' => 'Hygiene & Responsibility',
                    'description' => 'Gıda güvenliği ve kişisel hijyen bilinci',
                    'weight' => 0.20,
                ],
                [
                    'code' => 'customer_communication',
                    'name' => 'Müşteri İletişimi',
                    'name_en' => 'Customer Communication',
                    'description' => 'Etkili, sabırlı ve çözüm odaklı iletişim',
                    'weight' => 0.20,
                ],
                [
                    'code' => 'stress_management',
                    'name' => 'Stres Yönetimi',
                    'name_en' => 'Stress Management',
                    'description' => 'Yoğun ve zor durumlarda sakin kalma',
                    'weight' => 0.15,
                ],
                [
                    'code' => 'teamwork',
                    'name' => 'Ekip Uyumu',
                    'name_en' => 'Teamwork',
                    'description' => 'Çalışma arkadaşlarıyla işbirliği',
                    'weight' => 0.10,
                ],
                [
                    'code' => 'motivation',
                    'name' => 'Motivasyon',
                    'name_en' => 'Motivation',
                    'description' => 'İş motivasyonu ve kariyer hedefleri',
                    'weight' => 0.10,
                ],
            ],
            'red_flags' => [
                [
                    'code' => 'CASH_DISHONESTY',
                    'name' => 'Kasa Dürüstlük Sorunu',
                    'description' => 'Fazla parayı sahiplenme veya bildirmeme eğilimi',
                    'severity' => 'critical',
                    'keywords' => ['sahiplenirim', 'cebime', 'kimse görmez', 'fark etmez', 'almam normal', 'saklarım'],
                ],
                [
                    'code' => 'HYGIENE_NORMALIZE',
                    'name' => 'Hijyen İhlali Normalleştirme',
                    'description' => 'Hijyen kurallarını önemsememe veya normalleştirme',
                    'severity' => 'critical',
                    'keywords' => ['bir sefer olmaz', 'acele', 'zaman yok', 'gerek yok', 'sorun değil', 'devam ederim'],
                ],
                [
                    'code' => 'RULE_IGNORE',
                    'name' => 'Kural İhlali Görmezden Gelme',
                    'description' => 'Kural ihlallerini görmezden gelme veya destekleme',
                    'severity' => 'high',
                    'keywords' => ['karıştırmam', 'bana ne', 'onun sorunu', 'susmak', 'ben de yaparım'],
                ],
                [
                    'code' => 'INFO_FABRICATION',
                    'name' => 'Uydurma Bilgi',
                    'description' => 'Müşteriye uydurma veya yanlış bilgi verme',
                    'severity' => 'high',
                    'keywords' => ['uydururum', 'geçiştiririm', 'bilmiyorum ama söylerim'],
                ],
            ],
            'questions' => [
                [
                    'id' => 'Q1',
                    'order' => 1,
                    'type' => 'free_text',
                    'text' => 'Yoğun bir saatte sırada bekleyen bir müşteri sinirli şekilde senden öne geçmek istiyor. Diğer müşteriler de var. Nasıl davranırsın?',
                    'text_en' => 'During a busy hour, an angry customer in line wants to cut ahead of you. There are other customers too. How would you behave?',
                    'competency_codes' => ['customer_communication', 'stress_management', 'integrity'],
                    'scoring_rubric' => [
                        '0' => 'Kavgacı, küçümseyici, kontrolsüz tepki.',
                        '1' => 'Sorunu büyüten, empatisiz, kuralı açıklamayan yaklaşım.',
                        '2' => 'Kibar ama çözüm sunmayan, ortamı yönetemeyen yaklaşım.',
                        '3' => 'Kibar, sırayı koruyan, basit çözüm öneren yaklaşım.',
                        '4' => 'Sakinleştirici, adil, hızlı çözüm üreten, süreci yöneten yaklaşım.',
                        '5' => 'Profesyonel, empatik, adil, gerilimi düşüren ve diğer müşterileri de gözeten örnek yaklaşım.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q2',
                    'order' => 2,
                    'type' => 'free_text',
                    'text' => 'Kasa sayımı sırasında fazladan para çıktığını fark ettin. Kimse seni görmüyor. Ne yaparsın?',
                    'text_en' => 'During cash register counting, you notice extra money. No one is watching you. What do you do?',
                    'competency_codes' => ['integrity'],
                    'is_critical' => true,
                    'red_flags' => ['Parayı sahiplenmek', 'Kimseye bildirmemek', 'Görmezden gelmek'],
                    'scoring_rubric' => [
                        '0' => 'Parayı alırım / saklarım.',
                        '1' => 'Bilmiyorum, umursamam, kimseye söylemem.',
                        '2' => 'Sonra bakarım, net bir bildirim yok.',
                        '3' => 'Sorumluya haber veririm ama süreç belirsiz.',
                        '4' => 'Hemen sorumluya bildirir, tutanak/sayım tekrarı isterim.',
                        '5' => 'Derhal bildirir, ikinci sayım + kayıt/tutanak ile şeffaf şekilde süreci tamamlarım.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q3',
                    'order' => 3,
                    'type' => 'free_text',
                    'text' => 'Eldivenin yırtıldı ama müşteri yoğun ve sırayı durdurmak zor. Ne yaparsın?',
                    'text_en' => 'Your glove is torn but it\'s busy and hard to stop the line. What do you do?',
                    'competency_codes' => ['hygiene_responsibility', 'stress_management'],
                    'red_flags' => ['Hijyen ihlalini normalleştirme', 'Yırtık eldivenle devam etme'],
                    'scoring_rubric' => [
                        '0' => 'Devam ederim, sorun değil.',
                        '1' => 'Hızlıca idare ederim, sonra değiştiririm.',
                        '2' => 'Kısaca durup değiştiririm ama hijyen adımı eksik.',
                        '3' => 'Kısa ara verip değiştiririm, müşteriyi bilgilendiririm.',
                        '4' => 'Doğru hijyen prosedürü ile değiştirir, işi güvenli şekilde sürdürürüm.',
                        '5' => 'Hijyeni öncelikler, doğru prosedürü uygular, yoğunlukta bile standartları korurum.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q4',
                    'order' => 4,
                    'type' => 'free_text',
                    'text' => 'Yanındaki çalışan görevini aksatıyor ve yük sana kalıyor. Nasıl bir yol izlersin?',
                    'text_en' => 'The coworker next to you is slacking and the burden falls on you. What approach would you take?',
                    'competency_codes' => ['teamwork', 'stress_management', 'integrity'],
                    'scoring_rubric' => [
                        '0' => 'Kavga ederim / işi bırakırım.',
                        '1' => 'Arkadan konuşurum, ortamı bozarım.',
                        '2' => 'Sessizce katlanırım ama performans düşer.',
                        '3' => 'Nazikçe konuşur, görev paylaşımı öneririm.',
                        '4' => 'Somut örnekle konuşur, çözüm planlar, gerekiyorsa sorumluya taşırım.',
                        '5' => 'Ekip dengesini bozmadan, yapıcı geri bildirimle sürdürülebilir çözüm üretirim.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q5',
                    'order' => 5,
                    'type' => 'free_text',
                    'text' => 'Müşteri ürün içeriği soruyor ama emin değilsin. Ne yaparsın?',
                    'text_en' => 'A customer asks about product ingredients but you\'re not sure. What do you do?',
                    'competency_codes' => ['customer_communication', 'integrity'],
                    'red_flags' => ['Uydurma bilgi vermek'],
                    'scoring_rubric' => [
                        '0' => 'Uydururum.',
                        '1' => 'Geçiştiririm, başımdan savarım.',
                        '2' => 'Tam bilmiyorum derim ama çözüm yok.',
                        '3' => 'Kontrol edip geri dönerim derim.',
                        '4' => 'Etiket/ürün kartı/sorumlu ile doğrularım ve net bilgi veririm.',
                        '5' => 'Doğru kanaldan teyit eder, alerjen hassasiyetini de gözeterek güven veriririm.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q6',
                    'order' => 6,
                    'type' => 'free_text',
                    'text' => 'Yanlış ürünü verdin ve müşteri bunu fark edip geri geldi. Nasıl davranırsın?',
                    'text_en' => 'You gave the wrong product and the customer came back after noticing. How do you behave?',
                    'competency_codes' => ['customer_communication', 'integrity', 'stress_management'],
                    'scoring_rubric' => [
                        '0' => 'Müşteriyi suçlarım.',
                        '1' => 'İnat ederim, kabul etmem.',
                        '2' => 'İsteksiz düzeltirim, özür yok.',
                        '3' => 'Özür diler, düzeltirim.',
                        '4' => 'Hızlıca düzeltir, telafi sunar, tekrarını önlemek için not alırım.',
                        '5' => 'Profesyonel şekilde telafi eder, süreç iyileştirmesi için adım atarım.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q7',
                    'order' => 7,
                    'type' => 'free_text',
                    'text' => 'Aynı anda kasa, paketleme ve müşteri soruları var. Önceliği nasıl belirlersin?',
                    'text_en' => 'You have cash register, packaging, and customer questions all at once. How do you prioritize?',
                    'competency_codes' => ['stress_management', 'customer_communication'],
                    'scoring_rubric' => [
                        '0' => 'Paniklerim, rastgele davranırım.',
                        '1' => 'Öncelik koyamam, karışırım.',
                        '2' => 'Kısmen öncelik koyarım ama iletişim zayıf.',
                        '3' => 'Kısa plan yapar, sırayı yönetirim.',
                        '4' => 'İş akışını düzenler, gerektiğinde ekipten destek isterim.',
                        '5' => 'Yoğunluğu profesyonel yönetir, müşteri iletişimini koparmadan akışı optimize ederim.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q8',
                    'order' => 8,
                    'type' => 'free_text',
                    'text' => 'Bir çalışma arkadaşın "kimse görmez" diyerek küçük bir kuralı çiğniyor. Ne yaparsın?',
                    'text_en' => 'A coworker breaks a small rule saying "no one will see." What do you do?',
                    'competency_codes' => ['integrity', 'teamwork'],
                    'is_critical' => true,
                    'red_flags' => ['Kural ihlalini görmezden gelmek', '"Sorun değil" demek'],
                    'scoring_rubric' => [
                        '0' => 'Ben de yaparım.',
                        '1' => 'Görmezden gelirim.',
                        '2' => 'Sadece uyarırım ama takip etmem.',
                        '3' => 'Kibarca uyarır, doğru yöntemi anlatırım.',
                        '4' => 'Tekrarlanırsa sorumluya taşırım.',
                        '5' => 'Marka standardını koruyacak şekilde yapıcı biçimde müdahale ederim.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q9',
                    'order' => 9,
                    'type' => 'free_text',
                    'text' => 'Müşteri haklı ama üslubu çok sert. Nasıl sakinleştirirsin?',
                    'text_en' => 'The customer is right but their tone is very harsh. How do you calm them down?',
                    'competency_codes' => ['customer_communication', 'stress_management'],
                    'scoring_rubric' => [
                        '0' => 'Aynı sertlikle karşılık veririm.',
                        '1' => 'Tartışırım.',
                        '2' => 'Sessiz kalırım ama çözüm sunmam.',
                        '3' => 'Sakin kalır, çözüm sunarım.',
                        '4' => 'Empati kurar, net telafi sunar, süreci hızlı kapatırım.',
                        '5' => 'Profesyonel kriz yönetimiyle hem müşteriyi hem ortamı sakinleştiririm.',
                    ],
                    'max_score' => 5,
                ],
                [
                    'id' => 'Q10',
                    'order' => 10,
                    'type' => 'free_text',
                    'text' => 'Bu işi neden yapmak istiyorsun? Seni bu pozisyonda tutacak şey ne olur?',
                    'text_en' => 'Why do you want this job? What would keep you in this position?',
                    'competency_codes' => ['motivation'],
                    'scoring_rubric' => [
                        '0' => 'İlgisiz, olumsuz, küçümseyici.',
                        '1' => 'Sadece para, başka motivasyon yok.',
                        '2' => 'Genel cevap, düşük bağlılık.',
                        '3' => 'İş ve müşteri odaklı makul motivasyon.',
                        '4' => 'Gelişim ve sorumluluk içeren güçlü motivasyon.',
                        '5' => 'Marka temsilini, disiplini ve gelişimi sahiplenen yüksek motivasyon.',
                    ],
                    'max_score' => 5,
                ],
            ],
            'scoring_config' => [
                'passing_score' => 60,
                'total_max_score' => 50,
                'normalized_max' => 100,
                'level_thresholds' => [
                    'basarisiz' => 40,
                    'gelisime_acik' => 55,
                    'yeterli' => 70,
                    'iyi' => 85,
                    'mukemmel' => 100,
                ],
                'cashier_fit' => [
                    'FIT' => 'Kasaya ver',
                    'FIT_WITH_TRAINING' => 'Eğitim sonrası değerlendir',
                    'NOT_RECOMMENDED' => 'Riskli - önerilmez',
                ],
                'output_schema' => [
                    'overall_score_0_100' => 'number',
                    'risk_level' => 'LOW|MEDIUM|HIGH',
                    'cashier_fit' => 'FIT|FIT_WITH_TRAINING|NOT_RECOMMENDED',
                    'top_strengths' => ['string'],
                    'top_risks' => ['string'],
                    'red_flags_detected' => ['string'],
                    'manager_summary_tr' => 'string',
                    'recommendation_tr' => 'string',
                    'training_suggestions_tr' => ['string'],
                ],
                'instructions' => [
                    'tr' => 'Lütfen her soruya kısa ama net şekilde, gerçek hayatta nasıl davranacağını anlatarak cevap ver. Kopyala-yapıştır yapma. En az 2-3 cümle.',
                    'en' => 'Answer each question clearly with how you would act in real life. Avoid copy-paste. Minimum 2-3 sentences.',
                ],
                'role_name' => [
                    'tr' => 'Tezgahtar / Kasiyer',
                    'en' => 'Cashier / Counter Staff',
                ],
                'analysis_prompt' => <<<'PROMPT'
Sen bir HR değerlendirme uzmanısın. Aşağıdaki tezgahtar/kasiyer adayının cevaplarını değerlendir.

DEĞERLENDİRME KRİTERLERİ:
- Dürüstlük (%25): Para, ürün ve bilgi konusunda dürüst davranış
- Hijyen ve Sorumluluk (%20): Gıda güvenliği ve kişisel hijyen bilinci
- Müşteri İletişimi (%20): Etkili, sabırlı ve çözüm odaklı iletişim
- Stres Yönetimi (%15): Yoğun ve zor durumlarda sakin kalma
- Ekip Uyumu (%10): Çalışma arkadaşlarıyla işbirliği
- Motivasyon (%10): İş motivasyonu ve kariyer hedefleri

RED FLAG KURALLARI (bunlardan biri varsa otomatik risk işareti):
- Fazla parayı sahiplenme veya bildirmeme eğilimi
- Hijyen ihlalini normalleştirme ("bir sefer olmaz" yaklaşımı)
- Kural ihlalini görmezden gelme veya destekleme
- Müşteriye uydurma bilgi verme

HER SORU İÇİN PUANLAMA REHBERİ:
0 = Tamamen olumsuz, tehlikeli davranış
1 = Ciddi eksiklik, sorunlu yaklaşım
2 = Zayıf, gelişime ihtiyaç var
3 = Kabul edilebilir, ortalama
4 = İyi, beklentilerin üzerinde
5 = Mükemmel, örnek davranış

SONUÇTA ŞU FORMATTA JSON ÇIKTI VER:
{
  "soru_puanlari": [
    {"soru": "Q1", "puan": X, "gerekce": "..."},
    ...
  ],
  "yetkinlik_skorlari": {
    "integrity": X,
    "hygiene_responsibility": X,
    "customer_communication": X,
    "stress_management": X,
    "teamwork": X,
    "motivation": X
  },
  "overall_score_0_100": 0-100,
  "risk_level": "LOW|MEDIUM|HIGH",
  "cashier_fit": "FIT|FIT_WITH_TRAINING|NOT_RECOMMENDED",
  "top_strengths": ["güçlü yön 1", "güçlü yön 2"],
  "top_risks": ["risk 1", "risk 2"],
  "red_flags_detected": ["flag_kodu", ...],
  "manager_summary_tr": "3-4 cümlelik yönetici özeti",
  "recommendation_tr": "İşe alım önerisi",
  "training_suggestions_tr": ["eğitim önerisi 1", "eğitim önerisi 2"]
}
PROMPT,
            ],
            'time_limit_minutes' => 18,
            'is_active' => true,
        ];
    }
}
