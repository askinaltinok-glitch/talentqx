<?php

namespace Database\Seeders;

use App\Models\PositionTemplate;
use Illuminate\Database\Seeder;

class PositionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            $this->getTezgahtarKasiyerTemplate(),
            $this->getSoforTemplate(),
            $this->getDepocuTemplate(),
            $this->getImalatPersoneliTemplate(),
            $this->getUretimSefiTemplate(),
        ];

        foreach ($templates as $template) {
            PositionTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }

    private function getTezgahtarKasiyerTemplate(): array
    {
        return [
            'name' => 'Magaza Tezgahtar / Kasiyer',
            'slug' => 'tezgahtar-kasiyer',
            'description' => 'Perakende sektorunde magaza tezgahtari ve kasiyer pozisyonlari icin mulakat sablonu',
            'category' => 'retail',
            'competencies' => [
                [
                    'code' => 'customer_communication',
                    'name' => 'Musteri Iletisimi',
                    'weight' => 25,
                    'description' => 'Musteri ile etkili, nazik ve cozum odakli iletisim kurabilme'
                ],
                [
                    'code' => 'attention_speed',
                    'name' => 'Dikkat & Hiz',
                    'weight' => 25,
                    'description' => 'Hizli ve dikkatli calisabilme, hata yapmadan islem yapabilme'
                ],
                [
                    'code' => 'cash_discipline',
                    'name' => 'Nakit / POS Disiplini',
                    'weight' => 20,
                    'description' => 'Para ve odeme sistemleri ile dikkatli ve kurallara uygun calisma'
                ],
                [
                    'code' => 'stress_management',
                    'name' => 'Stres Yonetimi',
                    'weight' => 15,
                    'description' => 'Yogun ve stresli ortamlarda sakin kalabilme'
                ],
                [
                    'code' => 'hygiene_order',
                    'name' => 'Hijyen & Duzen',
                    'weight' => 15,
                    'description' => 'Temizlik ve duzen kurallarına uyum'
                ]
            ],
            'red_flags' => [
                [
                    'code' => 'cash_avoidance',
                    'description' => 'Para ile calismaktan kacinma',
                    'severity' => 'high',
                    'keywords' => ['para istemiyorum', 'kasa olmaz', 'nakit zor', 'kasada calismak istemem']
                ],
                [
                    'code' => 'workload_rejection',
                    'description' => 'Yogunluk reddi',
                    'severity' => 'high',
                    'keywords' => ['cok yogun', 'bu kadar is olmaz', 'fazla mesai yapmam', 'yogunlugu sevmem']
                ],
                [
                    'code' => 'customer_conflict',
                    'description' => 'Musteri ile catisma gecmisi',
                    'severity' => 'high',
                    'keywords' => ['musteriye bagirdim', 'hakli olsam da', 'tartistim', 'kavga ettim']
                ],
                [
                    'code' => 'schedule_inflexibility',
                    'description' => 'Vardiya esnekligi yok',
                    'severity' => 'medium',
                    'keywords' => ['hafta sonu calismam', 'sadece sabah', 'aksam olmaz', 'belli saatler']
                ]
            ],
            'question_rules' => [
                'technical_count' => 4,
                'behavioral_count' => 3,
                'scenario_count' => 2,
                'culture_count' => 1,
                'total_count' => 10,
                'sample_questions' => [
                    [
                        'type' => 'scenario',
                        'text' => 'Yogun bir gunde musteri yanlis fiyat etiketi oldugunu iddia ederek sizden indirim istiyor. Nasil davranirsiniz?',
                        'competency_code' => 'customer_communication'
                    ],
                    [
                        'type' => 'scenario',
                        'text' => 'Kasa kapatirken 50 TL eksik cikiyor. Bu durumda ne yaparsiniz?',
                        'competency_code' => 'cash_discipline'
                    ],
                    [
                        'type' => 'behavioral',
                        'text' => 'Cok yogun bir gunde birden fazla musteriye ayni anda hizmet vermek zorunda kaldiniz mi? Nasil basettiniz?',
                        'competency_code' => 'stress_management'
                    ],
                    [
                        'type' => 'technical',
                        'text' => 'Para ustu verirken nelere dikkat edersiniz?',
                        'competency_code' => 'attention_speed'
                    ]
                ]
            ],
            'scoring_rubric' => [
                '0' => 'Cevap yok veya tamamen alakasiz',
                '1' => 'Cok zayif - temel anlayis yok, riskli ifadeler',
                '2' => 'Zayif - kismi anlayis, eksik noktalar var',
                '3' => 'Orta - kabul edilebilir seviye, gelistirilebilir',
                '4' => 'Iyi - beklentileri karsilar, olumlu yaklasim',
                '5' => 'Mukemmel - beklentilerin ustunde, proaktif yaklasim'
            ],
            'critical_behaviors' => [
                'Musteri onunde sakin kalma',
                'Nakit islemlerinde dikkatli olma',
                'Temizlik kurallarına uyum',
                'Takim arkadaslariyla uyum'
            ],
            'is_active' => true
        ];
    }

    private function getSoforTemplate(): array
    {
        return [
            'name' => 'Sofor (Dagitim / Sevkiyat)',
            'slug' => 'sofor',
            'description' => 'Dagitim, sevkiyat ve kurye sofor pozisyonlari icin mulakat sablonu',
            'category' => 'logistics',
            'competencies' => [
                [
                    'code' => 'driving_safety',
                    'name' => 'Surus Disiplini & Guvenlik',
                    'weight' => 30,
                    'description' => 'Guvenli ve kurallara uygun surus yapabilme'
                ],
                [
                    'code' => 'time_management',
                    'name' => 'Zaman Yonetimi',
                    'weight' => 20,
                    'description' => 'Teslimat surelerini etkin yonetebilme'
                ],
                [
                    'code' => 'responsibility',
                    'name' => 'Sorumluluk Bilinci',
                    'weight' => 20,
                    'description' => 'Is ve arac sorumlulugunu ustlenebilme'
                ],
                [
                    'code' => 'documentation',
                    'name' => 'Evrak / Teslimat Dogrulugu',
                    'weight' => 15,
                    'description' => 'Belge ve teslimat islemlerinde dikkatli olma'
                ],
                [
                    'code' => 'road_stress',
                    'name' => 'Stres & Yol Sartlari',
                    'weight' => 15,
                    'description' => 'Trafik ve zor kosullarda sakin kalabilme'
                ]
            ],
            'red_flags' => [
                [
                    'code' => 'traffic_violations',
                    'description' => 'Trafik cezasi gecmisi',
                    'severity' => 'high',
                    'keywords' => ['ceza yedim', 'ehliyet cezasi', 'hiz cezasi', 'kirmizi isik']
                ],
                [
                    'code' => 'substance_risk',
                    'description' => 'Alkol / madde ima riski',
                    'severity' => 'critical',
                    'keywords' => ['bir iki bira', 'aksam icince', 'icki kullanirim', 'sosyal icki']
                ],
                [
                    'code' => 'rule_bending',
                    'description' => 'Kurallari esnetirim soylemi',
                    'severity' => 'high',
                    'keywords' => ['kurallari esnetirim', 'bazen yasak', 'gerekirse kural', 'kural her zaman gecerli degil']
                ],
                [
                    'code' => 'navigation_rejection',
                    'description' => 'Navigasyon / plan reddi',
                    'severity' => 'medium',
                    'keywords' => ['navigasyon kullanmam', 'kendi bildigim yol', 'plan gerekmez', 'kafama gore']
                ]
            ],
            'question_rules' => [
                'technical_count' => 4,
                'behavioral_count' => 3,
                'scenario_count' => 2,
                'culture_count' => 1,
                'total_count' => 10,
                'sample_questions' => [
                    [
                        'type' => 'scenario',
                        'text' => 'Teslimat icin yola ciktiniz ama trafik yogunlugu nedeniyle gecikeceginizi anliyorsunuz. Ne yaparsiniz?',
                        'competency_code' => 'time_management'
                    ],
                    [
                        'type' => 'scenario',
                        'text' => 'Teslimat sirasinda aracta hasar oldugunu fark ettiniz. Nasil davranirsiniz?',
                        'competency_code' => 'responsibility'
                    ],
                    [
                        'type' => 'behavioral',
                        'text' => 'Daha once zor hava kosullarinda surus yaptiginiz bir durumu anlatir misiniz?',
                        'competency_code' => 'road_stress'
                    ],
                    [
                        'type' => 'technical',
                        'text' => 'Arac bakim kontrolu yaparken nelere dikkat edersiniz?',
                        'competency_code' => 'driving_safety'
                    ]
                ]
            ],
            'scoring_rubric' => [
                '0' => 'Cevap yok veya tamamen alakasiz',
                '1' => 'Cok zayif - guvenlik riski, kurallara uymayan yaklasim',
                '2' => 'Zayif - kismi anlayis, eksik guvenlik bilinci',
                '3' => 'Orta - kabul edilebilir, gelistirilebilir',
                '4' => 'Iyi - guvenlik odakli, sorumlu yaklasim',
                '5' => 'Mukemmel - proaktif guvenlik bilinci, ornek davranis'
            ],
            'critical_behaviors' => [
                'Guvenli surus',
                'Zamaninda teslimat',
                'Arac bakimi',
                'Evrak duzeni'
            ],
            'is_active' => true
        ];
    }

    private function getDepocuTemplate(): array
    {
        return [
            'name' => 'Depocu',
            'slug' => 'depocu',
            'description' => 'Depo ve stok yonetimi pozisyonlari icin mulakat sablonu',
            'category' => 'warehouse',
            'competencies' => [
                [
                    'code' => 'organization',
                    'name' => 'Duzen & Sistematik Calisma',
                    'weight' => 30,
                    'description' => 'Depo duzenini saglama ve sistematik calisabilme'
                ],
                [
                    'code' => 'counting_attention',
                    'name' => 'Sayim & Dikkat',
                    'weight' => 25,
                    'description' => 'Stok sayiminda dikkatli ve dogru calisma'
                ],
                [
                    'code' => 'physical_endurance',
                    'name' => 'Fiziksel Dayaniklilik',
                    'weight' => 15,
                    'description' => 'Fiziksel is yukune dayanabilme'
                ],
                [
                    'code' => 'instruction_compliance',
                    'name' => 'Talimat Uyumu',
                    'weight' => 15,
                    'description' => 'Verilen talimatlara uygun calisma'
                ],
                [
                    'code' => 'responsibility',
                    'name' => 'Sorumluluk',
                    'weight' => 15,
                    'description' => 'Is ve urunlere karsi sorumluluk bilinci'
                ]
            ],
            'red_flags' => [
                [
                    'code' => 'counting_dismissal',
                    'description' => 'Sayim hatalarini kucumseme',
                    'severity' => 'high',
                    'keywords' => ['bir iki urun', 'onemli degil', 'sayim hata olur', 'kucuk farklar']
                ],
                [
                    'code' => 'disorder_tendency',
                    'description' => 'Duzen karsitligi',
                    'severity' => 'high',
                    'keywords' => ['duzen onemli degil', 'bulurum nasil olsa', 'karisik olabilir', 'duzen zaman kaybi']
                ],
                [
                    'code' => 'instruction_rejection',
                    'description' => 'Talimat reddi',
                    'severity' => 'high',
                    'keywords' => ['kendi bildigim', 'talimat gerekmez', 'tecrubem var', 'ben bilirim']
                ],
                [
                    'code' => 'shrinkage_acceptance',
                    'description' => 'Fireyi normal gorme',
                    'severity' => 'high',
                    'keywords' => ['fire normal', 'kayip olur', 'her yerde kayip', 'engellenemez']
                ]
            ],
            'question_rules' => [
                'technical_count' => 4,
                'behavioral_count' => 3,
                'scenario_count' => 2,
                'culture_count' => 1,
                'total_count' => 10,
                'sample_questions' => [
                    [
                        'type' => 'scenario',
                        'text' => 'Stok sayiminda sistemdeki rakamla fiziksel sayim arasinda fark cikiyor. Ne yaparsiniz?',
                        'competency_code' => 'counting_attention'
                    ],
                    [
                        'type' => 'technical',
                        'text' => 'FIFO (First In First Out) prensibi nedir ve neden onemlidir?',
                        'competency_code' => 'organization'
                    ],
                    [
                        'type' => 'behavioral',
                        'text' => 'Cok yogun bir yukleme/bosaltma gununde nasil organize oldunuz?',
                        'competency_code' => 'physical_endurance'
                    ],
                    [
                        'type' => 'scenario',
                        'text' => 'Hasarli urun tespit ettiniz ama sevkiyat suresi yaklasiyordu. Nasil davranirsiniz?',
                        'competency_code' => 'responsibility'
                    ]
                ]
            ],
            'scoring_rubric' => [
                '0' => 'Cevap yok veya tamamen alakasiz',
                '1' => 'Cok zayif - duzen ve dikkat eksikligi',
                '2' => 'Zayif - kismi anlayis, eksikler var',
                '3' => 'Orta - kabul edilebilir seviye',
                '4' => 'Iyi - sistematik yaklasim',
                '5' => 'Mukemmel - proaktif ve duzenli'
            ],
            'critical_behaviors' => [
                'Stok dogrulugu',
                'Depo duzeni',
                'Talimat uyumu',
                'Fiziksel dayaniklilik'
            ],
            'is_active' => true
        ];
    }

    private function getImalatPersoneliTemplate(): array
    {
        return [
            'name' => 'Imalat Personeli (Pastahane / Uretim)',
            'slug' => 'imalat-personeli',
            'description' => 'Pastahane, gida uretimi ve imalat pozisyonlari icin mulakat sablonu',
            'category' => 'production',
            'competencies' => [
                [
                    'code' => 'hygiene_awareness',
                    'name' => 'Hijyen Bilinci',
                    'weight' => 30,
                    'description' => 'Gida guvenligi ve hijyen kurallarına tam uyum'
                ],
                [
                    'code' => 'instruction_compliance',
                    'name' => 'Talimat Uyumu',
                    'weight' => 25,
                    'description' => 'Recete ve uretim talimatlarına uyum'
                ],
                [
                    'code' => 'manual_skill',
                    'name' => 'El Becerisi & Dikkat',
                    'weight' => 20,
                    'description' => 'El isi gerektiren gorevlerde ustalık'
                ],
                [
                    'code' => 'repetitive_tolerance',
                    'name' => 'Tekrarli Is Toleransi',
                    'weight' => 15,
                    'description' => 'Tekrarli islere sabir ve dikkat'
                ],
                [
                    'code' => 'team_harmony',
                    'name' => 'Ekip Uyumu',
                    'weight' => 10,
                    'description' => 'Takim icinde uyumlu calisma'
                ]
            ],
            'red_flags' => [
                [
                    'code' => 'hygiene_dismissal',
                    'description' => 'Hijyeni kucumseme',
                    'severity' => 'critical',
                    'keywords' => ['abartili', 'o kadar da degil', 'temizlik yeterli', 'hijyen abartiliyor']
                ],
                [
                    'code' => 'know_it_all',
                    'description' => 'Ben bildigimi yaparim soylemi',
                    'severity' => 'high',
                    'keywords' => ['ben bilirim', 'kendi yontemim', 'recete sarttir degil', 'tecrubeme gore']
                ],
                [
                    'code' => 'repetitive_impatience',
                    'description' => 'Tekrarli ise sabirsizlik',
                    'severity' => 'medium',
                    'keywords' => ['sikici', 'monoton', 'hep ayni', 'degisiklik istiyorum']
                ],
                [
                    'code' => 'measurement_carelessness',
                    'description' => 'Olcu & gramaj umursamama',
                    'severity' => 'high',
                    'keywords' => ['az fazla farketmez', 'goz karariyla', 'olcu sarttir degil', 'yaklasik']
                ]
            ],
            'question_rules' => [
                'technical_count' => 4,
                'behavioral_count' => 3,
                'scenario_count' => 2,
                'culture_count' => 1,
                'total_count' => 10,
                'sample_questions' => [
                    [
                        'type' => 'scenario',
                        'text' => 'Is arkadasinizin eldiven takmadan hamura dokundugunu goruyorsunuz. Ne yaparsiniz?',
                        'competency_code' => 'hygiene_awareness'
                    ],
                    [
                        'type' => 'scenario',
                        'text' => 'Recetede olmayan bir malzemeyi eklerseniz daha iyi olacagini dusunuyorsunuz. Nasil davranirsiniz?',
                        'competency_code' => 'instruction_compliance'
                    ],
                    [
                        'type' => 'behavioral',
                        'text' => 'Yogun uretim donemlerinde nasil motive kaliyorsunuz?',
                        'competency_code' => 'repetitive_tolerance'
                    ],
                    [
                        'type' => 'technical',
                        'text' => 'Gida uretiminde olcu ve gramaj neden onemlidir?',
                        'competency_code' => 'manual_skill'
                    ]
                ]
            ],
            'scoring_rubric' => [
                '0' => 'Cevap yok veya tamamen alakasiz',
                '1' => 'Cok zayif - hijyen riski, kurallara uymayan',
                '2' => 'Zayif - kismi anlayis, eksik hijyen bilinci',
                '3' => 'Orta - kabul edilebilir, gelistirilebilir',
                '4' => 'Iyi - hijyen odakli, talimat uyumlu',
                '5' => 'Mukemmel - ornek hijyen bilinci, titiz yaklasim'
            ],
            'critical_behaviors' => [
                'Hijyen kurallarina tam uyum',
                'Recete ve talimat takibi',
                'Olcu ve gramaj dikkati',
                'Temizlik rutini'
            ],
            'is_active' => true
        ];
    }

    private function getUretimSefiTemplate(): array
    {
        return [
            'name' => 'Uretim Sefi',
            'slug' => 'uretim-sefi',
            'description' => 'Uretim sefi ve vardiya sefi pozisyonlari icin mulakat sablonu',
            'category' => 'management',
            'competencies' => [
                [
                    'code' => 'planning',
                    'name' => 'Planlama & Organizasyon',
                    'weight' => 25,
                    'description' => 'Uretim planlamasi ve kaynak organizasyonu'
                ],
                [
                    'code' => 'team_management',
                    'name' => 'Ekip Yonetimi',
                    'weight' => 25,
                    'description' => 'Personel yonetimi ve motivasyonu'
                ],
                [
                    'code' => 'crisis_management',
                    'name' => 'Kriz & Fire Yonetimi',
                    'weight' => 20,
                    'description' => 'Beklenmedik durumlar ve fire kontrolu'
                ],
                [
                    'code' => 'quality_standards',
                    'name' => 'Kalite & Standart',
                    'weight' => 20,
                    'description' => 'Kalite standartlarinin saglanmasi'
                ],
                [
                    'code' => 'communication',
                    'name' => 'Iletisim & Raporlama',
                    'weight' => 10,
                    'description' => 'Etkili iletisim ve raporlama'
                ]
            ],
            'red_flags' => [
                [
                    'code' => 'blame_shifting',
                    'description' => 'Sorumlulugu astlara atma',
                    'severity' => 'high',
                    'keywords' => ['onlarin hatasi', 'ben soylemedim mi', 'personel hata', 'benim sucum degil']
                ],
                [
                    'code' => 'shrinkage_acceptance',
                    'description' => 'Fireyi kabullenme',
                    'severity' => 'high',
                    'keywords' => ['fire normal', 'her uretimde olur', 'engellenemez', 'kabul edilebilir kayip']
                ],
                [
                    'code' => 'plan_rejection',
                    'description' => 'Plan reddi',
                    'severity' => 'medium',
                    'keywords' => ['plan esnek olmali', 'planlar degisir', 'ani karar', 'plan gerekmez']
                ],
                [
                    'code' => 'discipline_weakness',
                    'description' => 'Disiplin zayifligi',
                    'severity' => 'high',
                    'keywords' => ['sert olmak istemem', 'herkes arkadas', 'uyari vermem', 'rahat ortam']
                ]
            ],
            'question_rules' => [
                'technical_count' => 4,
                'behavioral_count' => 3,
                'scenario_count' => 2,
                'culture_count' => 1,
                'total_count' => 10,
                'sample_questions' => [
                    [
                        'type' => 'scenario',
                        'text' => 'Uretim hattinda beklenmedik bir ariza cikti ve siparis teslim suresi yaklasiyordu. Nasil yonetirsiniz?',
                        'competency_code' => 'crisis_management'
                    ],
                    [
                        'type' => 'scenario',
                        'text' => 'Fire oranlari son hafta %20 artti. Bu durumda ne adimlar atarsiniz?',
                        'competency_code' => 'quality_standards'
                    ],
                    [
                        'type' => 'behavioral',
                        'text' => 'Motivasyonu dusuk bir personeli nasil motive edersiniz? Ornek verir misiniz?',
                        'competency_code' => 'team_management'
                    ],
                    [
                        'type' => 'technical',
                        'text' => 'Gunluk uretim planlamasi yaparken hangi kriterleri goz onunde bulundurursunuz?',
                        'competency_code' => 'planning'
                    ]
                ]
            ],
            'scoring_rubric' => [
                '0' => 'Cevap yok veya tamamen alakasiz',
                '1' => 'Cok zayif - liderlik eksikligi, sorumluluk almayan',
                '2' => 'Zayif - kismi liderlik, reaktif yaklasim',
                '3' => 'Orta - kabul edilebilir yonetim becerisi',
                '4' => 'Iyi - proaktif, sorumluluk alan lider',
                '5' => 'Mukemmel - vizyoner lider, ornek yonetici'
            ],
            'critical_behaviors' => [
                'Sorumluluk alma',
                'Personel motivasyonu',
                'Kalite takibi',
                'Kriz yonetimi'
            ],
            'is_active' => true
        ];
    }
}
