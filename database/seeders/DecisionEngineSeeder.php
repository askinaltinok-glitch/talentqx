<?php

namespace Database\Seeders;

use App\Models\CoreCompetency;
use App\Models\ScoringRule;
use App\Models\DecisionRule;
use App\Models\RedFlag;
use Illuminate\Database\Seeder;

class DecisionEngineSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCoreCompetencies();
        $this->seedScoringRules();
        $this->seedDecisionRules();
        $this->seedRedFlags();

        $this->command->info('TalentQX Decision Engine seeded successfully!');
    }

    private function seedCoreCompetencies(): void
    {
        $competencies = [
            [
                'code' => 'communication',
                'name_tr' => 'Iletisim',
                'name_en' => 'Communication',
                'description_tr' => 'Dusuncelerini acik, net ve etkili sekilde ifade edebilme',
                'description_en' => 'Ability to express thoughts clearly, concisely and effectively',
                'category' => 'core',
                'weight' => 15,
                'measurable_signals' => [
                    'Cumleleri tamamliyor, kopuk konusmuyor',
                    'Soruyu anliyor ve direkt cevapliyor',
                    'Mantiksal siralama ile anlatim yapiyor',
                    'Gereksiz dolgu kelime kullanmiyor',
                    'Dinleyiciyi dusunuyor, anlasilir konusuyor',
                ],
                'negative_signals' => [
                    'Konuyu dagitiyor, sorudan kaciyor',
                    'Cok kisa veya asiri uzun cevaplar',
                    'Anlamsiz tekrarlar',
                    'Soruyu yanlis anliyor',
                    'Tutuk, belirsiz ifadeler',
                ],
                'scoring_rubric' => [
                    '5' => 'Mukemmel - Akici, yapisal, ozlu, etkileyici',
                    '4' => 'Iyi - Acik ve anlasilir, minor eksikler',
                    '3' => 'Orta - Anlasilir ama daginik veya belirsiz kisimlar var',
                    '2' => 'Zayif - Zorlukla anlasilir, kopuk',
                    '1' => 'Yetersiz - Iletisim kurulamiyor',
                ],
                'sort_order' => 1,
            ],
            [
                'code' => 'accountability',
                'name_tr' => 'Sorumluluk',
                'name_en' => 'Accountability',
                'description_tr' => 'Hatalari kabul etme, sorumluluk alma, sonuclarin sahibi olma',
                'description_en' => 'Accepting mistakes, taking responsibility, owning outcomes',
                'category' => 'core',
                'weight' => 20,
                'measurable_signals' => [
                    'Hatalarini acikca kabul ediyor',
                    'Ben yaptim diyebiliyor',
                    'Sonuclardan ders cikardigini belirtiyor',
                    'Mazeret uretmiyor',
                    'Gelecekte nasil onleyecegini acikliyor',
                ],
                'negative_signals' => [
                    'Baskalarini sucluyor',
                    'Dis faktorlere yukluyor',
                    'Kucumsuyor veya gizliyor',
                    'Savunmaci tavir',
                    'Benim sucum degil vurgusu',
                ],
                'scoring_rubric' => [
                    '5' => 'Tam sahiplik - Acik kabul, ders cikarma, gelisme',
                    '4' => 'Yuksek - Cogunlukla sahip cikiyor, bazen belirsiz',
                    '3' => 'Orta - Kismi kabul, kismi mazeret',
                    '2' => 'Dusuk - Genellikle dis faktorlere yukluyor',
                    '1' => 'Yok - Surekli baskalarini sucluyor',
                ],
                'sort_order' => 2,
            ],
            [
                'code' => 'teamwork',
                'name_tr' => 'Takim Calismasi',
                'name_en' => 'Teamwork',
                'description_tr' => 'Baskalarıyla uyum icinde calisabilme, isbirligi, ortak hedef',
                'description_en' => 'Working harmoniously with others, collaboration, shared goals',
                'category' => 'core',
                'weight' => 15,
                'measurable_signals' => [
                    'Biz dili kullaniyor',
                    'Baskalarina kredi veriyor',
                    'Farkli goruslere acik',
                    'Catismada cozum ariyor',
                    'Yardim etmekten bahsediyor',
                ],
                'negative_signals' => [
                    'Surekli Ben odakli',
                    'Eski arkadaslarini elestiriyor',
                    'Yalniz calismayi tercih',
                    'Rekabetci ustunluk dili',
                    'Onlarla anlasamadim tekrari',
                ],
                'scoring_rubric' => [
                    '5' => 'Mukemmel - Guclu isbirligi, biz odakli, destekleyici',
                    '4' => 'Iyi - Takim oyuncusu, minor ego belirtileri',
                    '3' => 'Orta - Bazen takim, bazen bireysel',
                    '2' => 'Zayif - Bireysel odakli, takimla sorun gecmisi',
                    '1' => 'Uyumsuz - Takima zarar verebilir',
                ],
                'sort_order' => 3,
            ],
            [
                'code' => 'stress_resilience',
                'name_tr' => 'Stres Dayanikliligi',
                'name_en' => 'Stress Resilience',
                'description_tr' => 'Baski altinda sakin kalma, performans surdurebilme',
                'description_en' => 'Staying calm under pressure, maintaining performance',
                'category' => 'core',
                'weight' => 15,
                'measurable_signals' => [
                    'Stresli durumu yapisal anlatiyor',
                    'Basa cikma stratejisi var',
                    'Sakinlik vurgusu yapiyor',
                    'Onceliklendirme yaptigini belirtiyor',
                    'Olumlu sonuca ulastigini soyluyor',
                ],
                'negative_signals' => [
                    'Stres altinda patlama ifadeleri',
                    'Kacma birakma egilimi',
                    'Fiziksel belirtiler',
                    'Baskalarına yansitma',
                    'Dayanamadim ifadesi',
                ],
                'scoring_rubric' => [
                    '5' => 'Yuksek dayaniklilik - Sakin, cozum odakli',
                    '4' => 'Iyi - Genelde dayanikli, ara sira zorlanir',
                    '3' => 'Orta - Belirli stres seviyesine kadar dayanikli',
                    '2' => 'Dusuk - Kolayca etkiliyor, performans dusuyor',
                    '1' => 'Kirilgan - Stres altinda cozuluyor',
                ],
                'sort_order' => 4,
            ],
            [
                'code' => 'adaptability',
                'name_tr' => 'Uyum Saglama',
                'name_en' => 'Adaptability',
                'description_tr' => 'Degisime aciklik, yeni durumlara uyum, esneklik',
                'description_en' => 'Openness to change, adapting to new situations, flexibility',
                'category' => 'core',
                'weight' => 10,
                'measurable_signals' => [
                    'Degisimi firsat olarak goruyor',
                    'Farkli rollerde calistigini belirtiyor',
                    'Uyum sagladim ifadeleri',
                    'Yeni ortamlara gecis ornekleri',
                    'Esnek calisma istekliligi',
                ],
                'negative_signals' => [
                    'Ben boyle yaparim katiligi',
                    'Degisime direnc ifadeleri',
                    'Konfor alaninda kalma tercihi',
                    'Eski sistem daha iyiydi sikayeti',
                    'Tek yonlu kariyer',
                ],
                'scoring_rubric' => [
                    '5' => 'Cok esnek - Degisimi kucakliyor, hizli adapte',
                    '4' => 'Esnek - Degisime acik, makul surede uyum',
                    '3' => 'Orta - Gerektiginde uyum, tercihen stabilite',
                    '2' => 'Kati - Degisime direncli, yavas uyum',
                    '1' => 'Uyumsuz - Degisimi reddediyor',
                ],
                'sort_order' => 5,
            ],
            [
                'code' => 'learning_agility',
                'name_tr' => 'Ogrenme Kapasitesi',
                'name_en' => 'Learning Agility',
                'description_tr' => 'Yeni bilgi ve becerileri hizli ogrenme, gelisime aciklik',
                'description_en' => 'Quick learning of new skills, openness to development',
                'category' => 'core',
                'weight' => 10,
                'measurable_signals' => [
                    'Ogrenme ornekleri paylasiyor',
                    'Merak ve soru sorma egilimi',
                    'Hatalardan ders cikarma',
                    'Kendi kendine ogrenme',
                    'Gelisim alanlari farkindiligi',
                ],
                'negative_signals' => [
                    'Biliyorum tavri',
                    'Geri bildirime kapali',
                    'Ogrenme ornegi veremiyor',
                    'Statik beceri seti',
                    'Ogretilmedi mazereti',
                ],
                'scoring_rubric' => [
                    '5' => 'Hizli ogrenen - Merakli, proaktif, surekli gelisim',
                    '4' => 'Iyi - Ogrenmeye acik, makul hizda',
                    '3' => 'Orta - Gerektiginde ogrenir, proaktif degil',
                    '2' => 'Yavas - Ogrenmeye isteksiz',
                    '1' => 'Kapali - Yeni sey ogrenmiyor',
                ],
                'sort_order' => 6,
            ],
            [
                'code' => 'integrity',
                'name_tr' => 'Durustluk',
                'name_en' => 'Integrity',
                'description_tr' => 'Etik davranis, durustluk, guvenilir olma',
                'description_en' => 'Ethical behavior, honesty, being trustworthy',
                'category' => 'core',
                'weight' => 20,
                'measurable_signals' => [
                    'Tutarli anlatim',
                    'Zor konulari da paylasir',
                    'Abartisiz gercekci',
                    'Eski isvereni hakkinda dengeli',
                    'Somut dogrulanabilir ornekler',
                ],
                'negative_signals' => [
                    'Anlatimda celiskiler',
                    'Asiri mukemmellik iddiasi',
                    'Eski isveren hakkinda agir suclamalar',
                    'Belirsiz dogrulanamaz iddialar',
                    'Kucuk yalanlar abartmalar',
                ],
                'scoring_rubric' => [
                    '5' => 'Yuksek guven - Tutarli, acik, dogrulanabilir',
                    '4' => 'Guvenilir - Cogunlukla tutarli, minor belirsizlik',
                    '3' => 'Orta - Bazi tutarsizliklar',
                    '2' => 'Suphe - Belirgin tutarsizliklar',
                    '1' => 'Guvenilmez - Celiskili, muhtemel yalan',
                ],
                'sort_order' => 7,
            ],
            [
                'code' => 'role_competence',
                'name_tr' => 'Pozisyon Yetkinligi',
                'name_en' => 'Role Competence',
                'description_tr' => 'Ilgili is icin teknik bilgi, deneyim, beceri',
                'description_en' => 'Technical knowledge, experience, skills for the role',
                'category' => 'role_specific',
                'weight' => 25,
                'measurable_signals' => [
                    'Ilgili deneyim ornekleri',
                    'Teknik terminoloji kullanimi',
                    'Pratik bilgi gosterimi',
                    'Sektore ozgu farkindalik',
                    'Somut basari ornekleri',
                ],
                'negative_signals' => [
                    'Deneyim eksikligi',
                    'Yanlis terminoloji',
                    'Teorik bilgi pratik yok',
                    'Sektoru bilmiyor',
                    'Alakasiz deneyimler',
                ],
                'scoring_rubric' => [
                    '5' => 'Uzman - Derin bilgi, zengin deneyim',
                    '4' => 'Deneyimli - Iyi bilgi, yeterli deneyim',
                    '3' => 'Temel - Baslangic seviye, potansiyel var',
                    '2' => 'Zayif - Yetersiz bilgi deneyim',
                    '1' => 'Uyumsuz - Pozisyona uygun degil',
                ],
                'sort_order' => 8,
            ],
        ];

        foreach ($competencies as $data) {
            CoreCompetency::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command->info('Core Competencies: ' . count($competencies) . ' items seeded.');
    }

    private function seedScoringRules(): void
    {
        $rules = [
            // Primary scores
            [
                'code' => 'communication_score',
                'name_tr' => 'Iletisim Skoru',
                'name_en' => 'Communication Score',
                'score_type' => 'primary',
                'weight_percent' => 15,
                'source_competencies' => ['communication'],
                'formula' => 'communication * 20',
                'min_value' => 0,
                'max_value' => 100,
                'display_labels' => [
                    '90-100' => ['label' => 'Mukemmel', 'color' => '#1b5e20'],
                    '75-89' => ['label' => 'Cok Iyi', 'color' => '#2e7d32'],
                    '60-74' => ['label' => 'Iyi', 'color' => '#558b2f'],
                    '40-59' => ['label' => 'Orta', 'color' => '#f57c00'],
                    '0-39' => ['label' => 'Zayif', 'color' => '#c62828'],
                ],
                'sort_order' => 1,
            ],
            [
                'code' => 'reliability_score',
                'name_tr' => 'Guvenilirlik Skoru',
                'name_en' => 'Reliability Score',
                'score_type' => 'primary',
                'weight_percent' => 20,
                'source_competencies' => ['accountability', 'integrity'],
                'formula' => 'average(accountability, integrity) * 20',
                'min_value' => 0,
                'max_value' => 100,
                'display_labels' => [
                    '90-100' => ['label' => 'Mukemmel', 'color' => '#1b5e20'],
                    '75-89' => ['label' => 'Cok Iyi', 'color' => '#2e7d32'],
                    '60-74' => ['label' => 'Iyi', 'color' => '#558b2f'],
                    '40-59' => ['label' => 'Orta', 'color' => '#f57c00'],
                    '0-39' => ['label' => 'Zayif', 'color' => '#c62828'],
                ],
                'sort_order' => 2,
            ],
            [
                'code' => 'team_fit_score',
                'name_tr' => 'Takim Uyumu Skoru',
                'name_en' => 'Team Fit Score',
                'score_type' => 'primary',
                'weight_percent' => 15,
                'source_competencies' => ['teamwork', 'adaptability'],
                'formula' => 'average(teamwork, adaptability) * 20',
                'min_value' => 0,
                'max_value' => 100,
                'display_labels' => [
                    '90-100' => ['label' => 'Mukemmel', 'color' => '#1b5e20'],
                    '75-89' => ['label' => 'Cok Iyi', 'color' => '#2e7d32'],
                    '60-74' => ['label' => 'Iyi', 'color' => '#558b2f'],
                    '40-59' => ['label' => 'Orta', 'color' => '#f57c00'],
                    '0-39' => ['label' => 'Zayif', 'color' => '#c62828'],
                ],
                'sort_order' => 3,
            ],
            [
                'code' => 'stress_score',
                'name_tr' => 'Stres Dayanikliligi Skoru',
                'name_en' => 'Stress Score',
                'score_type' => 'primary',
                'weight_percent' => 15,
                'source_competencies' => ['stress_resilience'],
                'formula' => 'stress_resilience * 20',
                'min_value' => 0,
                'max_value' => 100,
                'display_labels' => [
                    '90-100' => ['label' => 'Cok Dayanikli', 'color' => '#1b5e20'],
                    '75-89' => ['label' => 'Dayanikli', 'color' => '#2e7d32'],
                    '60-74' => ['label' => 'Orta', 'color' => '#558b2f'],
                    '40-59' => ['label' => 'Hassas', 'color' => '#f57c00'],
                    '0-39' => ['label' => 'Kirilgan', 'color' => '#c62828'],
                ],
                'sort_order' => 4,
            ],
            [
                'code' => 'growth_potential',
                'name_tr' => 'Gelisim Potansiyeli',
                'name_en' => 'Growth Potential',
                'score_type' => 'primary',
                'weight_percent' => 10,
                'source_competencies' => ['learning_agility', 'adaptability'],
                'formula' => 'average(learning_agility, adaptability) * 20',
                'min_value' => 0,
                'max_value' => 100,
                'display_labels' => [
                    '90-100' => ['label' => 'Cok Yuksek', 'color' => '#1b5e20'],
                    '75-89' => ['label' => 'Yuksek', 'color' => '#2e7d32'],
                    '60-74' => ['label' => 'Orta', 'color' => '#558b2f'],
                    '40-59' => ['label' => 'Dusuk', 'color' => '#f57c00'],
                    '0-39' => ['label' => 'Cok Dusuk', 'color' => '#c62828'],
                ],
                'sort_order' => 5,
            ],
            [
                'code' => 'job_fit_score',
                'name_tr' => 'Pozisyon Uyumu Skoru',
                'name_en' => 'Job Fit Score',
                'score_type' => 'primary',
                'weight_percent' => 25,
                'source_competencies' => ['role_competence'],
                'formula' => 'role_competence * 20',
                'min_value' => 0,
                'max_value' => 100,
                'display_labels' => [
                    '90-100' => ['label' => 'Mukemmel Eslesme', 'color' => '#1b5e20'],
                    '75-89' => ['label' => 'Iyi Eslesme', 'color' => '#2e7d32'],
                    '60-74' => ['label' => 'Uygun', 'color' => '#558b2f'],
                    '40-59' => ['label' => 'Kismi Uyum', 'color' => '#f57c00'],
                    '0-39' => ['label' => 'Uyumsuz', 'color' => '#c62828'],
                ],
                'sort_order' => 6,
            ],
            // Risk scores
            [
                'code' => 'integrity_risk',
                'name_tr' => 'Guvenilirlik Riski',
                'name_en' => 'Integrity Risk',
                'score_type' => 'risk',
                'weight_percent' => 0,
                'source_competencies' => [],
                'formula' => 'from_red_flags(RF_INCONSIST, RF_AVOID)',
                'min_value' => 0,
                'max_value' => 100,
                'warning_threshold' => 40,
                'critical_threshold' => 70,
                'display_labels' => [
                    '0-20' => ['label' => 'Dusuk Risk', 'color' => '#2e7d32'],
                    '21-40' => ['label' => 'Normal', 'color' => '#558b2f'],
                    '41-70' => ['label' => 'Dikkat', 'color' => '#f57c00'],
                    '71-100' => ['label' => 'Yuksek Risk', 'color' => '#c62828'],
                ],
                'sort_order' => 10,
            ],
            [
                'code' => 'team_risk',
                'name_tr' => 'Takim Riski',
                'name_en' => 'Team Risk',
                'score_type' => 'risk',
                'weight_percent' => 0,
                'source_competencies' => [],
                'formula' => 'from_red_flags(RF_EGO, RF_BLAME)',
                'min_value' => 0,
                'max_value' => 100,
                'warning_threshold' => 40,
                'critical_threshold' => 70,
                'display_labels' => [
                    '0-20' => ['label' => 'Dusuk Risk', 'color' => '#2e7d32'],
                    '21-40' => ['label' => 'Normal', 'color' => '#558b2f'],
                    '41-70' => ['label' => 'Dikkat', 'color' => '#f57c00'],
                    '71-100' => ['label' => 'Yuksek Risk', 'color' => '#c62828'],
                ],
                'sort_order' => 11,
            ],
            [
                'code' => 'stability_risk',
                'name_tr' => 'Stabilite Riski',
                'name_en' => 'Stability Risk',
                'score_type' => 'risk',
                'weight_percent' => 0,
                'source_competencies' => [],
                'formula' => 'from_red_flags(RF_UNSTABLE, RF_AVOID)',
                'min_value' => 0,
                'max_value' => 100,
                'warning_threshold' => 40,
                'critical_threshold' => 70,
                'display_labels' => [
                    '0-20' => ['label' => 'Dusuk Risk', 'color' => '#2e7d32'],
                    '21-40' => ['label' => 'Normal', 'color' => '#558b2f'],
                    '41-70' => ['label' => 'Dikkat', 'color' => '#f57c00'],
                    '71-100' => ['label' => 'Yuksek Risk', 'color' => '#c62828'],
                ],
                'sort_order' => 12,
            ],
        ];

        foreach ($rules as $data) {
            ScoringRule::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command->info('Scoring Rules: ' . count($rules) . ' items seeded.');
    }

    private function seedDecisionRules(): void
    {
        $decisions = [
            [
                'decision' => 'HIRE',
                'label_tr' => 'Ise Al',
                'label_en' => 'Hire',
                'conditions' => [
                    'overall_score >= 75',
                    'no critical_red_flags',
                    'integrity_risk < 40',
                    'job_fit_score >= 60',
                ],
                'color' => '#2e7d32',
                'icon' => 'check_circle',
                'description_tr' => 'Aday pozisyon icin uygun. Ise alim oneriliyor.',
                'priority' => 1,
            ],
            [
                'decision' => 'HOLD',
                'label_tr' => 'Beklet',
                'label_en' => 'Hold',
                'conditions' => [
                    'overall_score >= 60',
                    'overall_score < 75',
                    'no critical_red_flags',
                ],
                'color' => '#f57c00',
                'icon' => 'pause_circle',
                'description_tr' => 'Aday potansiyel gosteriyor. Ikinci mulakat veya ek degerlendirme oneriliyor.',
                'priority' => 2,
            ],
            [
                'decision' => 'REJECT',
                'label_tr' => 'Reddet',
                'label_en' => 'Reject',
                'conditions' => [
                    'overall_score < 60',
                ],
                'color' => '#c62828',
                'icon' => 'cancel',
                'description_tr' => 'Aday pozisyon icin uygun degil.',
                'priority' => 3,
            ],
        ];

        foreach ($decisions as $data) {
            DecisionRule::updateOrCreate(
                ['decision' => $data['decision']],
                $data
            );
        }

        $this->command->info('Decision Rules: ' . count($decisions) . ' items seeded.');
    }

    private function seedRedFlags(): void
    {
        $flags = [
            [
                'code' => 'RF_BLAME',
                'name_tr' => 'Sorumluluk Atma',
                'name_en' => 'Blame Shifting',
                'severity' => 'high',
                'description_tr' => 'Hatalari ve olumsuz sonuclari baskalarına veya dis etkenlere yukluyor',
                'description_en' => 'Attributing mistakes and negative outcomes to others or external factors',
                'trigger_phrases' => [
                    'onlar yuzunden',
                    'bana soylemediler',
                    'patron haksizlik yapti',
                    'sistem yanlistı',
                    'herkes oyle yapiyor',
                    'benim sucum degil',
                    'bana bilgi verilmedi',
                    'o yuzden oldu',
                    'onlar yapmasaydi',
                ],
                'behavioral_patterns' => [
                    'Hatalardan bahsederken surekli dis faktorlere atif',
                    'Kendi rolunu kucumseme',
                    'Eski isveren veya calisanlari suclama',
                ],
                'detection_method' => 'phrase_match',
                'impact' => [
                    'accountability_score' => -30,
                    'reliability_score' => -20,
                    'team_fit_score' => -15,
                ],
                'analysis_note_tr' => 'Sahiplenme ve sorumluluk almada ciddi eksiklik',
                'analysis_note_en' => 'Serious lack of ownership and accountability',
                'causes_auto_reject' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'RF_INCONSIST',
                'name_tr' => 'Tutarsizlik',
                'name_en' => 'Inconsistency',
                'severity' => 'high',
                'description_tr' => 'Farkli sorularda celisen bilgiler veya dogrulanamayan iddialar',
                'description_en' => 'Conflicting information across questions or unverifiable claims',
                'trigger_phrases' => [],
                'behavioral_patterns' => [
                    'Tarih ve sure celiskileri',
                    'Farkli sorularda farkli hikayeler',
                    'Abartili veya gercekdisi rakamlar',
                    'Dogrulanamayan basari iddialari',
                    'Onceki cevaplarla celisen ifadeler',
                ],
                'detection_method' => 'cross_reference',
                'impact' => [
                    'integrity_risk' => 40,
                    'reliability_score' => -25,
                ],
                'analysis_note_tr' => 'Guvenilirlik konusunda ciddi soru isaretleri',
                'analysis_note_en' => 'Serious reliability concerns',
                'causes_auto_reject' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'RF_EGO',
                'name_tr' => 'Ego Baskinligi',
                'name_en' => 'Ego Dominance',
                'severity' => 'medium',
                'description_tr' => 'Asiri ben odaklilik, ustunluk iddiasi, baskalarini kucumseme',
                'description_en' => 'Excessive self-focus, superiority claims, belittling others',
                'trigger_phrases' => [
                    'ben en iyisiydim',
                    'kimse benim kadar',
                    'onlar benim seviyemde degil',
                    'tek basima yaptim',
                    'digerleri yavas',
                    'benden iyi yapan yok',
                    'hep ben kurtardim',
                ],
                'behavioral_patterns' => [
                    'Surekli Ben zamiri vurgusu',
                    'Baskalarina kredi vermeme',
                    'Kendi basarilarini abartma',
                    'Takim basarilarini sahiplenme',
                ],
                'detection_method' => 'phrase_match',
                'impact' => [
                    'team_fit_score' => -25,
                    'team_risk' => 30,
                ],
                'analysis_note_tr' => 'Takim uyumu konusunda potansiyel sorunlar',
                'analysis_note_en' => 'Potential team fit issues',
                'causes_auto_reject' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'RF_AVOID',
                'name_tr' => 'Kacinma',
                'name_en' => 'Avoidance',
                'severity' => 'medium',
                'description_tr' => 'Sorulari cevaplamaktan kacinma, belirsiz veya genel cevaplar',
                'description_en' => 'Avoiding questions, vague or generic answers',
                'trigger_phrases' => [
                    'hatirlamiyorum',
                    'bilmiyorum',
                    'oyle bir sey olmadi',
                ],
                'behavioral_patterns' => [
                    'Soruyu cevaplamadan konu degistirme',
                    'Cok kisa ve icerikisiz cevaplar',
                    'Hatirlamiyorum tekrari',
                    'Genel ve somut olmayan ifadeler',
                    'Detay vermekten kacinma',
                ],
                'detection_method' => 'pattern_analysis',
                'impact' => [
                    'integrity_risk' => 20,
                    'communication_score' => -15,
                ],
                'analysis_note_tr' => 'Seffaflik eksikligi veya gizlenen bilgi olabilir',
                'analysis_note_en' => 'Lack of transparency or hidden information possible',
                'causes_auto_reject' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'RF_AGGRESSION',
                'name_tr' => 'Agresif Dil',
                'name_en' => 'Aggressive Language',
                'severity' => 'critical',
                'description_tr' => 'Siddet, tehdit, kufur veya asiri olumsuz ifadeler',
                'description_en' => 'Violence, threats, profanity or extremely negative expressions',
                'trigger_phrases' => [
                    'kirarim',
                    'doverim',
                    'oldururum',
                    'kafasini kirarim',
                    'gebertirdim',
                    'parcalarim',
                    'hadlerini bildiririm',
                ],
                'behavioral_patterns' => [
                    'Eski isveren veya calisanlar hakkinda agresif dil',
                    'Catisma anlatirken siddet imasi',
                    'Kontrol kaybi ifadeleri',
                ],
                'detection_method' => 'phrase_match',
                'impact' => [
                    'team_risk' => 100,
                    'stability_risk' => 100,
                ],
                'analysis_note_tr' => 'KRITIK - Is ortami guvenligi riski. Otomatik red onerisi.',
                'analysis_note_en' => 'CRITICAL - Workplace safety risk. Automatic rejection recommended.',
                'causes_auto_reject' => true,
                'max_score_override' => 30,
                'sort_order' => 5,
            ],
            [
                'code' => 'RF_UNSTABLE',
                'name_tr' => 'Istikrarsizlik',
                'name_en' => 'Instability',
                'severity' => 'medium',
                'description_tr' => 'Kararsizlik, sik is degisikligi, tutarsiz hedefler',
                'description_en' => 'Indecision, frequent job changes, inconsistent goals',
                'trigger_phrases' => [
                    'cok sik is degistirdim',
                    'karar veremedim',
                    'sikildim biraktim',
                ],
                'behavioral_patterns' => [
                    'Kisa surede cok fazla is degisikligi aciklamasiz',
                    'Ani ve plansiz kararlar',
                    'Tutarsiz kariyer hedefleri',
                    'Her isten olumsuz ayrilma',
                    'Karar verememe',
                ],
                'detection_method' => 'pattern_analysis',
                'impact' => [
                    'reliability_score' => -20,
                    'stability_risk' => 25,
                ],
                'analysis_note_tr' => 'Uzun vadeli baglilik konusunda soru isaretleri',
                'analysis_note_en' => 'Questions about long-term commitment',
                'causes_auto_reject' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($flags as $data) {
            RedFlag::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command->info('Red Flags: ' . count($flags) . ' items seeded.');
    }
}
