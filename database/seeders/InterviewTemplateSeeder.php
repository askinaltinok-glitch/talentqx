<?php

namespace Database\Seeders;

use App\Models\InterviewTemplate;
use Illuminate\Database\Seeder;

class InterviewTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Generic Template (system template, no position_code)
        $genericTemplate = [
            "schema_version" => "1.0",
            "template_type" => "generic",
            "language" => "tr",
            "total_questions" => 8,
            "slots" => [
                [
                    "slot" => 1,
                    "competency" => "communication",
                    "weight" => 11.54,
                    "method" => "STAR",
                    "prompt_tr" => "Karmasik bir bilgiyi baskasina anlatmaniz gereken bir durumu dusunun. O durumu ve nasil ilettinizi anlatir misiniz?",
                    "follow_up" => "Karsi tarafin anlayip anlamadigini nasil olctunuz?",
                    "scoring_rubric" => [
                        "5" => "Durumu net tanimlar, yapilandirmis bir sekilde anlatir, dinleyicinin geri bildirimini alir ve buna gore uyum saglar.",
                        "4" => "Cogu unsuru kapsar; kucuk eksiklikler var ama genel olarak etkili.",
                        "3" => "Temel anlatim mevcut ama dinleyici odaklilik veya uyum gostermek eksik.",
                        "2" => "Belirsiz veya dagilmis; dinleyiciyi dikkate almayan anlatim.",
                        "1" => "Konuyu anlatamaz veya ilgisiz cevap verir."
                    ],
                    "positive_signals" => ["net ornekler", "dinleyici odaklilik", "geri bildirim alma"],
                    "red_flag_hooks" => ["RF_BLAME", "RF_INCONSIST"]
                ],
                [
                    "slot" => 2,
                    "competency" => "accountability",
                    "weight" => 15.38,
                    "method" => "BEI",
                    "prompt_tr" => "Is yerinde bir hata yaptiniz veya bir sey ters gitti. Bu durumu ve nasil ele aldiginizi anlatir misiniz?",
                    "follow_up" => "Bu deneyimden ne ogrendiniz?",
                    "scoring_rubric" => [
                        "5" => "Hatayi acikca kabul eder, duzeltme adimlari atar, gelecekte tekrar etmemek icin somut ders cikarir.",
                        "4" => "Kabul eder ve duzeltir ama ders cikarma kisimlari soyut kalir.",
                        "3" => "Kabul eder ancak duzeltme adimlari veya ogrenme eksik.",
                        "2" => "Hatayi kucultur veya baskalarina yonlendirir; sinirli sorumluluk.",
                        "1" => "Inkar eder, suclama yapar veya konuyu degistirir."
                    ],
                    "positive_signals" => ["sorumluluk kabulu", "duzeltici adim", "ogrenme gosterme"],
                    "red_flag_hooks" => ["RF_BLAME", "RF_INCONSIST", "RF_EGO"]
                ],
                [
                    "slot" => 3,
                    "competency" => "teamwork",
                    "weight" => 11.54,
                    "method" => "STAR",
                    "prompt_tr" => "Bir ekip icerisinde calistiginiz bir projeyi veya gorevi anlatir misiniz? Sizin roldunuz ve ekibe katkiniz ne oldu?",
                    "follow_up" => "Ekip icerisinde bir anlasmazlik oldu mu? Nasil cozuldu?",
                    "scoring_rubric" => [
                        "5" => "Ekip hedefini on plana koyar; aktif isbirligi yapar; catismada yapici davranir.",
                        "4" => "Iyi ekip oyuncusu; kucuk bireysel vurgular olabilir.",
                        "3" => "Katilim orta duzeyde; ekip dinamigine iliskin ayrintilar eksik.",
                        "2" => "Bireysel basarilara odaklanir; ekibi arka planda tutar.",
                        "1" => "Ekibi elestiri veya suclama araci olarak kullanir."
                    ],
                    "positive_signals" => ["biz dili", "isbirligi ornekleri", "catisma cozumu"],
                    "red_flag_hooks" => ["RF_BLAME", "RF_EGO"]
                ],
                [
                    "slot" => 4,
                    "competency" => "stress_resilience",
                    "weight" => 11.54,
                    "method" => "BEI",
                    "prompt_tr" => "Is yerinde yogun baski veya stres altinda oldugunuz bir donemi anlatir misiniz? Bu durumu nasil yonettiniz?",
                    "follow_up" => "Bu surecte motivasyonunuzu nasil korudunuz?",
                    "scoring_rubric" => [
                        "5" => "Stresi kabul eder; somut basa cikma stratejileri kullanir; performansi korur.",
                        "4" => "Stresi yonetir ama stratejiler daha az sistematik.",
                        "3" => "Stresi anlatir ama basa cikma yontemleri belirsiz.",
                        "2" => "Stres altinda performans dususu oldu, sinirli basa cikma.",
                        "1" => "Kontrolsuz tepkiler veya sorumluluklardan kacis."
                    ],
                    "positive_signals" => ["sakin kalma", "onceliklendirme", "destek arama"],
                    "red_flag_hooks" => ["RF_UNSTABLE", "RF_BLAME", "RF_AVOID"]
                ],
                [
                    "slot" => 5,
                    "competency" => "adaptability",
                    "weight" => 7.69,
                    "method" => "STAR",
                    "prompt_tr" => "Is yerinde beklenmedik bir degisiklige uyum saglamaniz gereken bir durumu anlatir misiniz?",
                    "follow_up" => "Bu degisiklige ilk tepkiniz ne oldu?",
                    "scoring_rubric" => [
                        "5" => "Degisikligi hizla kabul eder; proaktif uyum saglar; firsata dondurur.",
                        "4" => "Uyum saglar ama baslangicta kisa bir direnc gosterebilir.",
                        "3" => "Uyum saglar ancak pasif veya yavas.",
                        "2" => "Degisiklige karsi direnc belirgin; zorla uyum.",
                        "1" => "Uyum reddeder veya degisikligi engellemeye calisir."
                    ],
                    "positive_signals" => ["esneklik", "hizli uyum", "proaktif yaklasim"],
                    "red_flag_hooks" => ["RF_AVOID", "RF_UNSTABLE"]
                ],
                [
                    "slot" => 6,
                    "competency" => "learning_agility",
                    "weight" => 7.69,
                    "method" => "Direct",
                    "prompt_tr" => "Yeni bir beceri veya bilgi ogrenmeniz gereken bir durumu anlatir misiniz? Nasil ogrendiniz?",
                    "follow_up" => "Ogrenme surecinde hangi kaynaklari veya yontemleri kullandiniz?",
                    "scoring_rubric" => [
                        "5" => "Sistematik ogrenme yaklasimi; cesitli kaynaklar kullanir; bilgiyi uygular.",
                        "4" => "Iyi ogrenme gosterir ama yontem cesitliligi sinirli.",
                        "3" => "Ogrenir ama pasif veya yuzeysel.",
                        "2" => "Ogrenmeye karsi isteksiz; zorunlu oldugunda ogrenir.",
                        "1" => "Ogrenmeyi reddeder veya yeni bilgiye karsi direnc gosterir."
                    ],
                    "positive_signals" => ["merak", "kaynak cesitliligi", "uygulama ornegi"],
                    "red_flag_hooks" => ["RF_AVOID"]
                ],
                [
                    "slot" => 7,
                    "competency" => "integrity",
                    "weight" => 15.38,
                    "method" => "BEI",
                    "prompt_tr" => "Is yerinde etik bir ikilemle karsilastiginiz bir durumu anlatir misiniz? Nasil davrandiniz?",
                    "follow_up" => "Bu karari verirken neleri goz onunde bulundurdunuz?",
                    "scoring_rubric" => [
                        "5" => "Net etik ilkeler; zor durumda bile dogru olani yapar; seffaf davranir.",
                        "4" => "Dogru karari verir ama karar sureci daha az acik.",
                        "3" => "Etik davranir ama ikilem veya muhakeme ornegi zayif.",
                        "2" => "Gri alanlardan yararlanir veya kurallari esnetir.",
                        "1" => "Etik ihlal gosterir veya hileli davranisi normallestirir."
                    ],
                    "positive_signals" => ["seffaflik", "ilke odaklilik", "zor durumda dik durma"],
                    "red_flag_hooks" => ["RF_INCONSIST", "RF_AVOID"]
                ],
                [
                    "slot" => 8,
                    "competency" => "role_competence",
                    "weight" => 19.23,
                    "method" => "Direct",
                    "prompt_tr" => "Bu pozisyon icin en guclu yoniniz nedir? Bu gucu bir ornekle gosterir misiniz?",
                    "follow_up" => "Bu alanda kendinizi nasil gelistirdiniz?",
                    "scoring_rubric" => [
                        "5" => "Pozisyonla dogrudan ilgili guclu bir yetkinlik gosterir; somut orneklerle destekler.",
                        "4" => "Ilgili yetkinlik gosterir; ornek iyi ama baglanti biraz zayif.",
                        "3" => "Genel guc anlatir ama pozisyonla baglantisi belirsiz.",
                        "2" => "Guc ve pozisyon arasinda uyumsuzluk; zayif ornek.",
                        "1" => "Ilgili guc gosteremez veya pozisyonu yanlis anlar."
                    ],
                    "positive_signals" => ["somut ornek", "pozisyon uyumu", "derinlik"],
                    "red_flag_hooks" => ["RF_INCONSIST", "RF_EGO"]
                ]
            ],
            "guidance" => [
                "introduction" => "Merhaba, ben TalentQX mülakat asistaniyim. Size birkaç soru soracagim. Lutfen orneklerle cevap vermeye calisin.",
                "closing" => "Sorularim bu kadar. Bana sormak istediginiz bir sey var mi?",
                "rf_detection_rules" => [
                    "RF_BLAME" => "Sorumlulugu disariya atan ifadeler: 'onlar yapti', 'bana soylemediler', 'sistem yuzunden'",
                    "RF_INCONSIST" => "Ayni soru icinde celisen bilgiler veya kronolojik tutarsizliklar",
                    "RF_EGO" => "Asiri ben odakli; ekibi/basaridan dislar; kendi rolu abartilmis",
                    "RF_AVOID" => "Is veya sorumluluktan kacinma: 'yapmam', 'benim isim degil', 'ugrasimam'",
                    "RF_AGGRESSION" => "Ofkeli, saldirgan veya tehditkar dil",
                    "RF_UNSTABLE" => "Asiri duygusal tepkiler, kontrol kaybi gostergesi"
                ]
            ]
        ];

        // Retail Cashier Position Template
        $retailCashierTemplate = [
            "schema_version" => "1.0",
            "template_type" => "position",
            "position_code" => "retail_cashier",
            "position_title_tr" => "Kasiyer",
            "category" => "Perakende",
            "language" => "tr",
            "skill_gate" => [
                "role_competence_min" => 50,
                "action" => "HOLD",
                "is_safety_critical" => false
            ],
            "total_questions" => 8,
            "slots" => [
                [
                    "slot" => 1,
                    "competency" => "communication",
                    "weight" => 11.54,
                    "method" => "STAR",
                    "prompt_tr" => "Bir musteriyle iletisim kurdugunuz zor bir durumu anlatir misiniz? Nasil cozdinuz?",
                    "follow_up" => "Musterinin tepkisi ne oldu?",
                    "scoring_rubric" => [
                        "5" => "Musteriye sabir ve empati gosterir; sorunu cozme odakli iletisim kurar; olumlu sonuc alir.",
                        "4" => "Iyi iletisim; kucuk eksiklikler olabilir.",
                        "3" => "Temel iletisim; musteri odaklilik orta duzey.",
                        "2" => "Iletisim zorlugu; musteri memnuniyetsizligi yasanmis.",
                        "1" => "Kotu iletisim veya musteri ile catisma."
                    ],
                    "positive_signals" => ["sabir", "empati", "cozum odaklilik"],
                    "red_flag_hooks" => ["RF_BLAME", "RF_AGGRESSION"]
                ],
                [
                    "slot" => 2,
                    "competency" => "accountability",
                    "weight" => 15.38,
                    "method" => "BEI",
                    "prompt_tr" => "Kasada bir hata yaptiniz veya bir sorun yasandiniz. Bu durumu ve nasil ele aldiginizi anlatir misiniz?",
                    "follow_up" => "Bu durumdan ne ogrendiniz?",
                    "scoring_rubric" => [
                        "5" => "Hatayi hemen kabul eder; duzeltme adimlari atar; tekrar etmemek icin onlem alir.",
                        "4" => "Kabul eder ve duzeltir ama onlemler soyut.",
                        "3" => "Kabul eder ancak duzeltme sinirli.",
                        "2" => "Hatayi kucultur veya baskasina yonlendirir.",
                        "1" => "Inkar eder veya suclama yapar."
                    ],
                    "positive_signals" => ["sorumluluk", "duzeltme", "ogrenme"],
                    "red_flag_hooks" => ["RF_BLAME", "RF_INCONSIST"]
                ],
                [
                    "slot" => 3,
                    "competency" => "teamwork",
                    "weight" => 11.54,
                    "method" => "STAR",
                    "prompt_tr" => "Magaza ekibinizle birlikte calistiginiz yogun bir gunu anlatir misiniz? Ekibe nasil katki sagladiniz?",
                    "follow_up" => "Ekip arkadaslarinizla nasil koordine oldunuz?",
                    "scoring_rubric" => [
                        "5" => "Ekip isbirligini on planda tutar; proaktif destek saglar; yogunlugu birlikte yonetir.",
                        "4" => "Iyi ekip oyuncusu; kucuk bireysel vurgular olabilir.",
                        "3" => "Katilim orta; ekip dinamigi ayrinti eksik.",
                        "2" => "Bireysel odakli; ekibi arka planda tutar.",
                        "1" => "Ekiple sorun yasar veya isbirligi reddeder."
                    ],
                    "positive_signals" => ["isbirligi", "destek", "koordinasyon"],
                    "red_flag_hooks" => ["RF_EGO", "RF_BLAME"]
                ],
                [
                    "slot" => 4,
                    "competency" => "stress_resilience",
                    "weight" => 11.54,
                    "method" => "BEI",
                    "prompt_tr" => "Kasada uzun kuyruk veya yogun baski altinda oldugunuz bir ani anlatir misiniz? Nasil yonettiniz?",
                    "follow_up" => "Stres altinda nasil sakin kaldiniz?",
                    "scoring_rubric" => [
                        "5" => "Yogunlugu sakin ve sistematik yonetir; musteri memnuniyetini korur.",
                        "4" => "Iyi yonetir ama kucuk stres belirtileri olabilir.",
                        "3" => "Stresi anlatir ama basa cikma orta duzey.",
                        "2" => "Stres altinda performans duser; belirgin zorluk.",
                        "1" => "Kontrolsuz tepkiler veya gorevden kacis."
                    ],
                    "positive_signals" => ["sakinlik", "sistematiklik", "musteri odaklilik"],
                    "red_flag_hooks" => ["RF_UNSTABLE", "RF_AVOID"]
                ],
                [
                    "slot" => 5,
                    "competency" => "adaptability",
                    "weight" => 7.69,
                    "method" => "STAR",
                    "prompt_tr" => "Magazada ani bir degisiklik oldu (sistem degisikligi, yeni urun, prosedur degisikligi). Nasil uyum sagladiniz?",
                    "follow_up" => "Bu degisikligi ogrenmeniz ne kadar surdu?",
                    "scoring_rubric" => [
                        "5" => "Degisikligi hizla benimser; proaktif ogrenir; baskalarina yardim eder.",
                        "4" => "Uyum saglar ama baslangiçta kisa tereddut olabilir.",
                        "3" => "Uyum saglar ancak yavas veya pasif.",
                        "2" => "Degisiklige karsi direnc gosterir.",
                        "1" => "Uyum reddeder veya sikayete odaklanir."
                    ],
                    "positive_signals" => ["hizli ogrenme", "esneklik", "yardimcilik"],
                    "red_flag_hooks" => ["RF_AVOID", "RF_UNSTABLE"]
                ],
                [
                    "slot" => 6,
                    "competency" => "learning_agility",
                    "weight" => 7.69,
                    "method" => "Direct",
                    "prompt_tr" => "Kasiyerlik veya magaza islerinde yeni bir sey ogrendiginiz bir durumu anlatir misiniz?",
                    "follow_up" => "Bu bilgiyi isyerinde nasil uyguladiniz?",
                    "scoring_rubric" => [
                        "5" => "Sistematik ogrenir; uygular; baskalariyla paylaşir.",
                        "4" => "Iyi ogrenir ama uygulama sinirli.",
                        "3" => "Ogrenir ama pasif veya yuzeysel.",
                        "2" => "Ogrenmeye isteksiz; zorunlu oldugunda ogrenir.",
                        "1" => "Ogrenmeyi reddeder."
                    ],
                    "positive_signals" => ["merak", "uygulama", "paylasim"],
                    "red_flag_hooks" => ["RF_AVOID"]
                ],
                [
                    "slot" => 7,
                    "competency" => "integrity",
                    "weight" => 15.38,
                    "method" => "BEI",
                    "prompt_tr" => "Kasada veya magazada dogru olani yapmak icin zor bir karar verdiginiz bir durumu anlatir misiniz?",
                    "follow_up" => "Bu karari verirken neleri dusundunuz?",
                    "scoring_rubric" => [
                        "5" => "Net etik duruş; zor durumda bile dogru olani yapar; seffaf.",
                        "4" => "Dogru karar verir ama karar sureci daha az acik.",
                        "3" => "Etik davranir ama ornek zayif.",
                        "2" => "Gri alanlardan yararlanir.",
                        "1" => "Etik ihlal veya hileli davranis gosterir."
                    ],
                    "positive_signals" => ["durust", "seffaf", "ilke odakli"],
                    "red_flag_hooks" => ["RF_INCONSIST"]
                ],
                [
                    "slot" => 8,
                    "competency" => "role_competence",
                    "weight" => 19.23,
                    "method" => "Direct",
                    "prompt_tr" => "Kasiyerlik veya perakende sektorunde en guclu yoniniz nedir? Bir ornekle anlatir misiniz?",
                    "follow_up" => "Bu beceriyi nasil gelistirdiniz?",
                    "scoring_rubric" => [
                        "5" => "Kasiyerlikle dogrudan ilgili guclu yetkinlik gosterir; somut ornek.",
                        "4" => "Ilgili yetkinlik; ornek iyi ama baglanti biraz zayif.",
                        "3" => "Genel guc; pozisyonla baglanti belirsiz.",
                        "2" => "Guc ve pozisyon uyumsuz.",
                        "1" => "Ilgili guc gosteremez."
                    ],
                    "positive_signals" => ["perakende deneyimi", "musteri odaklilik", "hiz ve dogruluk"],
                    "red_flag_hooks" => ["RF_INCONSIST", "RF_EGO"]
                ]
            ],
            "guidance" => [
                "introduction" => "Merhaba, ben TalentQX mülakat asistaniyim. Kasiyer pozisyonu icin size birkaç soru soracagim.",
                "closing" => "Sorularim bu kadar. Bana sormak istediginiz bir sey var mi?",
                "rf_detection_rules" => [
                    "RF_BLAME" => "Sorumlulugu disariya atan ifadeler",
                    "RF_INCONSIST" => "Celisen bilgiler veya tutarsizliklar",
                    "RF_EGO" => "Asiri ben odakli; ekibi dislar",
                    "RF_AVOID" => "Is veya sorumluluktan kacinma: 'yapmam', 'benim isim degil', 'ugrasimam'",
                    "RF_AGGRESSION" => "Ofkeli veya saldirgan dil",
                    "RF_UNSTABLE" => "Asiri duygusal tepkiler"
                ]
            ]
        ];

        // Insert generic template (no position_code)
        $generic = InterviewTemplate::create([
            'version' => 'v1',
            'language' => 'tr',
            'position_code' => null,
            'title' => 'Genel Mulakat Sablonu',
            'template_json' => json_encode($genericTemplate, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'is_active' => true,
        ]);

        // Insert retail_cashier position template
        $cashier = InterviewTemplate::create([
            'version' => 'v1',
            'language' => 'tr',
            'position_code' => 'retail_cashier',
            'title' => 'Kasiyer Mulakat Sablonu',
            'template_json' => json_encode($retailCashierTemplate, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'is_active' => true,
        ]);

        echo "Inserted IDs:\n";
        echo "  - Generic Template: {$generic->id}\n";
        echo "  - Retail Cashier Template: {$cashier->id}\n";
        echo "Row count: " . InterviewTemplate::count() . "\n";
    }
}
