<?php

namespace Database\Seeders;

use App\Models\InterviewTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterviewTemplateExactJsonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Stores EXACT JSON without any transformation.
     */
    public function run(): void
    {
        // EXACT JSON - DO NOT MODIFY
        $exactJson = <<<'JSON'
{
  "version": "v1",
  "language": "tr",
  "generic_template": {
    "questions": [
      {
        "slot": 1,
        "competency": "communication",
        "question": "Zor bir konuyu basit bir sekilde anlatmaniz gereken bir durumu anlatir misiniz? Ne yaptiniz ve sonuc ne oldu?",
        "method": "STAR",
        "scoring_rubric": {
          "1": "Konu anlatilamadi, dinleyici perspektifi yok, karisik ve daginik anlatim",
          "2": "Temel bilgi aktarildi ama yapilandirilmamis, dinleyiciye uyum yok",
          "3": "Anlasilir anlatim, temel yapilandirma var, geri bildirim almaya acik",
          "4": "Net ve organize anlatim, dinleyici seviyesine uyum, sorulara acik yaklasim",
          "5": "Mukemmel yapilandirma, empati ile dinleyici odakli anlatim, etkin geri bildirim dongusu"
        },
        "positive_signals": [
          "Dinleyicinin bilgi seviyesini sorguladi",
          "Ornekler ve benzetmeler kullandi",
          "Anlasildigindan emin olmak icin kontrol etti",
          "Geri bildirime gore yaklasimini degistirdi"
        ],
        "red_flag_hooks": [
          {
            "code": "RF_AVOID",
            "trigger_guidance": "Iletisim sorumlulugundan kacinma ifadeleri: 'bu benim isim degil', 'baskasi halletsin', 'ugrasmam boyle seylerle', 'sorumluluk almam bu konuda'",
            "severity": "medium"
          }
        ]
      },
      {
        "slot": 2,
        "competency": "accountability",
        "question": "Is yerinizde bir hata yaptiniz veya bir seyin yanlis gittigi bir durumu anlatir misiniz? Bu durumu nasil ele aldiniz?",
        "method": "BEI",
        "scoring_rubric": {
          "1": "Hatayi inkar etti veya tamamen baskalarina yukledi, sorumluluk almadi",
          "2": "Hatayi kabul etti ama cozum icin adim atmadi, pasif kaldi",
          "3": "Hatayi kabul etti ve temel duzeltici adimlar atti",
          "4": "Tam sorumluluk aldi, proaktif cozum uretti, ilgilileri bilgilendirdi",
          "5": "Hatayi sahiplendi, sistematik cozum gelistirdi, tekrarini onlemek icin surec iyilestirmesi onerdi"
        },
        "positive_signals": [
          "Hatayi acikca kabul etti",
          "Baskalarini suclama egilimi gostermedi",
          "Somut duzeltici adimlar anlatti",
          "Hatadan cikarilan dersi paylasti"
        ],
        "red_flag_hooks": [
          {
            "code": "RF_BLAME",
            "trigger_guidance": "Surekli dissal faktorlere atif: 'ekip beni desteklemedi', 'yonetici yanlis yonlendirdi', 'sistem hatasi yuzunden' gibi sorumluluktan kacinma kaliplari",
            "severity": "high"
          },
          {
            "code": "RF_INCONSIST",
            "trigger_guidance": "Hikayede tutarsizliklar: once baskasini suclayip sonra sahiplenme, veya detaylar soruldugunda celiskili bilgiler",
            "severity": "high"
          }
        ]
      },
      {
        "slot": 3,
        "competency": "teamwork",
        "question": "Farkli goruslere sahip ekip uyeleriyle birlikte calistiginiz bir projeyi anlatir misiniz? Farkli bakis acilarini nasil yonettiniz?",
        "method": "STAR",
        "scoring_rubric": {
          "1": "Ekip calismasindan kacindi veya kendi gorusunu dayatti, uzlasma aramadi",
          "2": "Pasif katilim, goruslerini ifade etmedi veya catismayi gormezden geldi",
          "3": "Farkli gorusleri dinledi, temel uzlasma cabalari gosterdi",
          "4": "Aktif olarak farkli perspektifleri entegre etti, yapici tartisma ortami yaratti",
          "5": "Farkliklardan sinerji yaratti, herkesin katilimini sagladi, ortak hedefe yonlendirdi"
        },
        "positive_signals": [
          "Baskalarinin fikirlerini aktif olarak sordu",
          "Kendi gorusunu degistirmeye acikti",
          "Catismayi yapici sekilde yonetti",
          "Ekip basarisini bireysel basarinin onune koydu"
        ],
        "red_flag_hooks": [
          {
            "code": "RF_EGO",
            "trigger_guidance": "Ekip basarisini sahiplenme: 'aslinda benim fikrimi uyguladilar', 'ben olmasam yapamazlardi', 'digerleri benim seviyemde degildi'",
            "severity": "medium"
          },
          {
            "code": "RF_AGGRESSION",
            "trigger_guidance": "Ekip uyelerine yonelik asagilayici ifadeler: hakaret iceren tanimlamalar, kisisel saldirilar, ofkeli tonlama",
            "severity": "critical"
          }
        ]
      },
      {
        "slot": 4,
        "competency": "stress_resilience",
        "question": "Yogun baski altinda calistiginiz ve ayni anda birden fazla oncelikli isiniz oldugu bir donemi anlatir misiniz? Nasil basa ciktiniz?",
        "method": "BEI",
        "scoring_rubric": {
          "1": "Stres karsisinda coktu, isleri tamamlayamadi, panik veya kacinma davranisi",
          "2": "Zorlanarak tamamladi, stres yonetim stratejisi yok, reaktif yaklasim",
          "3": "Isleri tamamladi, temel onceliklendirme yapti, orta duzeyde stres yonetimi",
          "4": "Etkili onceliklendirme, sakin kalarak sistematik yaklasim, kaliteyi koruyarak tamamladi",
          "5": "Baski altinda ustun performans, baskalarini da sakinlestirdi, stresi motive edici olarak kullandi"
        },
        "positive_signals": [
          "Somut onceliklendirme yontemi anlatti",
          "Duygusal kontrolu korudugunu gosterdi",
          "Gerektiginde yardim istedi",
          "Sonrasi icin ders cikardi"
        ],
        "red_flag_hooks": [
          {
            "code": "RF_UNSTABLE",
            "trigger_guidance": "Stres karsisinda kontrolsuz tepkiler: 'sinirden patladim', 'herseyi birakip gittim', 'tamamen kontrolumu kaybettim' gibi ekstrem ifadeler",
            "severity": "medium"
          },
          {
            "code": "RF_AVOID",
            "trigger_guidance": "Stresli durumlardan sistemik kacinma: 'boyle isler benim isim degil', 'bu tur sorumluluk almam', 'ugrasmam baski altinda'",
            "severity": "medium"
          }
        ]
      },
      {
        "slot": 5,
        "competency": "adaptability",
        "question": "Is yerinizde beklenmedik bir degisiklik yasandiginda nasil uyum sagladiniz? Bir ornek verebilir misiniz?",
        "method": "STAR",
        "scoring_rubric": {
          "1": "Degisiklige direndi, uyum saglamadi, sikayetci veya engelleyici davranis",
          "2": "Zorunlu olarak uyum sagladi ama isteksiz, negatif tutum korudu",
          "3": "Degisikligi kabul etti, makul surede uyum sagladi",
          "4": "Degisikligi hizla benimsedi, yeni durumda verimli calisti, baskalarinin uyumuna yardimci oldu",
          "5": "Degisikligi firsata cevirdi, proaktif oneriler sundu, degisim lideri rolu ustlendi"
        },
        "positive_signals": [
          "Degisikligin nedenini anlamaya calisti",
          "Hizla yeni beceriler edindi",
          "Pozitif tutum korudu",
          "Baskalarinin uyumuna destek oldu"
        ],
        "red_flag_hooks": [
          {
            "code": "RF_AVOID",
            "trigger_guidance": "Degisimden kacis ve red: 'yapmam boyle seyleri', 'benim isim degil yeni sistem ogrenmek', 'ugrasmam degisikliklerle'",
            "severity": "medium"
          }
        ]
      },
      {
        "slot": 6,
        "competency": "learning_agility",
        "question": "Tamamen yeni bir konu veya beceriyi kisa surede ogrenmeniz gereken bir durumu anlatir misiniz? Nasil yaklastiniz?",
        "method": "STAR",
        "scoring_rubric": {
          "1": "Ogrenme isteksizligi, pasif tutum, baskalarina bagimli kaldi",
          "2": "Temel duzeyde ogrendi ama derinlesemedi, sadece zorunlu olani yapti",
          "3": "Aktif ogrenme cabasi, standart kaynaklari kullandi, makul surede ogrendi",
          "4": "Hizli ve etkili ogrenme, birden fazla kaynak kullandi, ogrenileni hemen uyguladi",
          "5": "Ustun ogrenme hizi, ogrendiklerini gelistirdi, baskalarini da egitti"
        },
        "positive_signals": [
          "Birden fazla ogrenme kaynagi kullandi",
          "Soru sormaktan cekinmedi",
          "Ogrendiklerini pratikte uyguladigini anlatti",
          "Ogrenme surecinden keyif aldigini belirtti"
        ],
        "red_flag_hooks": [
          {
            "code": "RF_AVOID",
            "trigger_guidance": "Ogrenme sorumlulugundan kacinma: 'benim isim degil yeni seyler ogrenmek', 'baskasi ogretsin ugrasmam', 'sorumluluk almam bu konuda'",
            "severity": "medium"
          }
        ]
      },
      {
        "slot": 7,
        "competency": "integrity",
        "question": "Etik acidan zor bir kararla karsilastiginiz bir durumu anlatir misiniz? Nasil davrandiniz?",
        "method": "BEI",
        "scoring_rubric": {
          "1": "Etik disi davranis tarif etti veya kurallari esnetmeyi normal karsiladi",
          "2": "Etik ikilemi fark etti ama harekete gecmedi, pasif kaldi",
          "3": "Dogru olani yapti ama sadece zorunlu oldugu icin, icsel motivasyon belirsiz",
          "4": "Etik ilkelere bagli kaldi, zor durumda bile dogru karari verdi, tutarli davranis",
          "5": "Etik liderlik gosterdi, baskalarini da dogru davranisa yonlendirdi, risk alarak dogru olani savundu"
        },
        "positive_signals": [
          "Acik ve tutarli etik cerceve anlatti",
          "Kisisel bedele ragmen dogru olani yapti",
          "Seffaflik ve durustluk vurguladi",
          "Etik disi baskiya direndi"
        ],
        "red_flag_hooks": [
          {
            "code": "RF_INCONSIST",
            "trigger_guidance": "Etik tutarsizlik: duruma gore degisen kurallar, 'herkes yapiyor' normalizasyonu, rasyonalizasyon kaliplari",
            "severity": "high"
          },
          {
            "code": "RF_BLAME",
            "trigger_guidance": "Etik ihlali baskalarina yukleme: 'yonetici beni zorladi', 'sistem boyle kurulmus', 'baska secenegim yoktu'",
            "severity": "high"
          }
        ]
      },
      {
        "slot": 8,
        "competency": "role_competence",
        "question": "Bu pozisyonun temel gereksinimlerinden birini gerceklestirdiginiz bir deneyimi anlatir misiniz? Hangi yaklasimi kullandiniz ve sonuc ne oldu?",
        "method": "STAR",
        "scoring_rubric": {
          "1": "Ilgili deneyim yok veya cok yuzeysel, temel gereksinimleri anlamadigini gosterdi",
          "2": "Sinirli deneyim, temel kavramlari biliyor ama uygulamada zayif",
          "3": "Yeterli deneyim, standart surecleri dogru uyguladi, kabul edilebilir sonuclar",
          "4": "Guclu deneyim, kaliteli ve olculebilir sonuclar uretti, sureci iyilestirdi",
          "5": "Ustun performans, yenilikci yaklasimlar gelistirdi, baskalarini da egitebilecek seviyede"
        },
        "positive_signals": [
          "Somut ve olculebilir sonuclar paylasti",
          "Surec adimlarini dogru ve mantikli siraladi",
          "Karsilastigi problemleri nasil cozduguunu anlatti",
          "Surekli gelisim ornekleri verdi"
        ],
        "red_flag_hooks": [
          {
            "code": "RF_INCONSIST",
            "trigger_guidance": "Yetkinlik abartisi: detay soruldugunda tutarsizliklar, aciklama istendiginde belirsiz cevaplar, deneyim ile iddia arasinda uyumsuzluk",
            "severity": "high"
          },
          {
            "code": "RF_EGO",
            "trigger_guidance": "Gercekci olmayan ozguven: 'bu isi en iyi ben yaparim', 'kimse benim kadar bilmez', ekip katkilarini yok sayma",
            "severity": "medium"
          }
        ]
      }
    ]
  },
  "positions": [
    {
      "position_code": "retail_cashier",
      "title_tr": "Kasiyer",
      "title_en": "Cashier",
      "category": "Perakende",
      "skill_gate": {
        "gate": 45,
        "action": "HOLD",
        "safety_critical": false
      },
      "template": {
        "questions": [
          {
            "slot": 1,
            "competency": "communication",
            "question": "Musteri ile iletisimde zorluk yasadiginiz bir durumu anlatir misiniz? Nasil cozumlediniz?",
            "method": "STAR",
            "scoring_rubric": {
              "1": "Musteriyle iletisim kuramadi, savunmaci veya ilgisiz tutum",
              "2": "Temel iletisim kurdu ama etkisiz, musteri memnuniyeti saglanamadi",
              "3": "Sorunu dinledi ve standart cozum sundu, kabul edilebilir iletisim",
              "4": "Empati ile dinledi, net aciklama yapti, musteriyi memnun etti",
              "5": "Mukemmel musteri iletisimi, zor durumu firsata cevirdi, musteri sadakati yaratti"
            },
            "positive_signals": [
              "Musteriyi aktif olarak dinledi",
              "Sabirli ve saygiliydi",
              "Cozum odakli yaklasti",
              "Geri bildirim aldi"
            ],
            "red_flag_hooks": [
              {
                "code": "RF_AGGRESSION",
                "trigger_guidance": "Musteriye yonelik asagilayici ifadeler: hakaret, kucumseme, 'aptal musteri' gibi tanimlamalar",
                "severity": "critical"
              }
            ]
          },
          {
            "slot": 2,
            "competency": "accountability",
            "question": "Kasada bir hata yaptiniz veya kasa farki olustugunda nasil davrandiniz?",
            "method": "BEI",
            "scoring_rubric": {
              "1": "Hatayi sakladi veya baskasina yukledi, sorumluluk almadi",
              "2": "Hatayi kabul etti ama cozum uretmedi, pasif kaldi",
              "3": "Hatayi bildirdi ve prosedure uygun davrandi",
              "4": "Hemen bildirdi, nedenini arastirdi, duzeltici adim atti",
              "5": "Tam seffaflik, kok neden analizi, tekrar yasanmamasi icin oneri sundu"
            },
            "positive_signals": [
              "Hatayi hemen bildirdi",
              "Ozur diledi ve sahiplendi",
              "Duzeltme icin harekete gecti",
              "Prosedure uygun davrandi"
            ],
            "red_flag_hooks": [
              {
                "code": "RF_BLAME",
                "trigger_guidance": "Kasa farkini dissal faktorlere yukleme: 'sistem hatasi yuzunden', 'musteri yuzunden', 'ekip arkadasi yuzunden'",
                "severity": "high"
              },
              {
                "code": "RF_INCONSIST",
                "trigger_guidance": "Tutarsiz aciklamalar: rakamlar veya olaylar hakkinda celiskili bilgiler",
                "severity": "high"
              }
            ]
          },
          {
            "slot": 3,
            "competency": "teamwork",
            "question": "Yogun bir gunde ekip arkadaslariniza nasil destek oldugunuzu anlatir misiniz?",
            "method": "STAR",
            "scoring_rubric": {
              "1": "Sadece kendi isine odaklandi, ekibe destek vermedi",
              "2": "Istendiginde yardim etti ama proaktif degil",
              "3": "Ekip ihtiyaclarini fark etti ve temel destek sagladi",
              "4": "Proaktif olarak yardim teklif etti, is yukunu paylasti",
              "5": "Ekip koordinasyonu sagladi, herkesin yukunu dengeledi, motivasyonu artirdi"
            },
            "positive_signals": [
              "Yardim teklif etti",
              "Esneklik gosterdi",
              "Ekip basarisini vurguladi",
              "Iletisimde kaldi"
            ],
            "red_flag_hooks": [
              {
                "code": "RF_EGO",
                "trigger_guidance": "Bireysel basariyi one cikarma: 'en cok calisan bendim', 'bensiz yapamazlardi'",
                "severity": "medium"
              }
            ]
          },
          {
            "slot": 4,
            "competency": "stress_resilience",
            "question": "Kasada uzun kuyruk olustugunda ve musteriler sabrsizlandiginda nasil davranirsiniz?",
            "method": "BEI",
            "scoring_rubric": {
              "1": "Panikleyip hata yapti veya musterilere kotu davrandi",
              "2": "Stresli gorundu, yavaslamalar yasandi, sinirli hisler belli oldu",
              "3": "Sakin kalmaya calisti, standart hizda devam etti",
              "4": "Sakinligini korudu, hiz ve dogrulugi dengeledi, musterileri bilgilendirdi",
              "5": "Baski altinda ustun performans, musterileri rahatlatti, ekibi organize etti"
            },
            "positive_signals": [
              "Sakin ve kontrolluydu",
              "Musterileri bilgilendirdi",
              "Hiz ve dogrulugu korudu",
              "Gerekirse yardim istedi"
            ],
            "red_flag_hooks": [
              {
                "code": "RF_UNSTABLE",
                "trigger_guidance": "Kontrolsuz stres tepkileri: 'sinirden patladim', 'aglamaya basladim', 'herseyi birakip gittim'",
                "severity": "medium"
              },
              {
                "code": "RF_AGGRESSION",
                "trigger_guidance": "Musterilere veya ekibe yonelik sert tepkiler stres altinda",
                "severity": "critical"
              }
            ]
          },
          {
            "slot": 5,
            "competency": "adaptability",
            "question": "Yeni bir kasa sistemi veya satis proseduru degistiginde nasil uyum sagladiniz?",
            "method": "STAR",
            "scoring_rubric": {
              "1": "Degisiklige direndi, eski yontemi kullanmaya devam etti",
              "2": "Zorunlu olarak ogrendi ama sikayetci, yavas uyum",
              "3": "Degisikligi kabul etti, makul surede ogrendi",
              "4": "Hizla adapte oldu, yeni sistemin avantajlarini gordu",
              "5": "Degisimi benimsedi, baskalarinin uyumuna yardimci oldu, iyilestirme onerdi"
            },
            "positive_signals": [
              "Ogrenmeye acikti",
              "Soru sordu",
              "Pratik yapti",
              "Baskalarina yardim etti"
            ],
            "red_flag_hooks": [
              {
                "code": "RF_AVOID",
                "trigger_guidance": "Degisim ve ogrenme sorumlulugundan kacinma: 'benim isim degil yeni sistem ogrenmek', 'ugrasmam bunlarla', 'sorumluluk almam'",
                "severity": "medium"
              }
            ]
          },
          {
            "slot": 6,
            "competency": "learning_agility",
            "question": "Perakendede yeni bir urun grubu veya kampanya hakkinda hizla bilgi edinmeniz gereken bir durumu anlatir misiniz?",
            "method": "STAR",
            "scoring_rubric": {
              "1": "Ogrenme cabasi gostermedi, musterilere yanlis bilgi verdi",
              "2": "Temel bilgileri ogrendi ama yetersiz, detaylarda eksik kaldi",
              "3": "Gerekli bilgileri ogrendi, musterilere dogru bilgi verdi",
              "4": "Hizla ogrendi, musterilere detayli bilgi sundu, satis artti",
              "5": "Uzmanlasti, ekibi egitti, musterilerden olumlu geri bildirim aldi"
            },
            "positive_signals": [
              "Inisiyatif alarak arastirdi",
              "Notlar tuttu",
              "Soru sordu",
              "Bilgisini paylasti"
            ],
            "red_flag_hooks": [
              {
                "code": "RF_AVOID",
                "trigger_guidance": "Ogrenme sorumlulugundan kacinma: 'benim isim degil urun bilgisi', 'ugrasmam detaylarla', 'baskasi halletsin'",
                "severity": "medium"
              }
            ]
          },
          {
            "slot": 7,
            "competency": "integrity",
            "question": "Kasada fazla para veya urun farki fark ettiginizde ne yaptiniz? Ya da boyle bir durumla karsilassaniz ne yaparsiniz?",
            "method": "BEI",
            "scoring_rubric": {
              "1": "Farki bildirmeyecegini veya faydalanacagini ima etti",
              "2": "Ne yapacagindan emin degil, net etik durusu yok",
              "3": "Dogru olani yapacagini belirtti ama motivasyonu belirsiz",
              "4": "Net olarak bildirme ve duzeltme prosedurunu anlatti, tutarli etik yaklasim",
              "5": "Guclu etik durusu, orneklerle destekledi, diger durumlarda da tutarlilik gosterdi"
            },
            "positive_signals": [
              "Hemen bildirme refleksi",
              "Durustluk vurgusu",
              "Prosedur bilgisi",
              "Tutarli etik yaklasim"
            ],
            "red_flag_hooks": [
              {
                "code": "RF_INCONSIST",
                "trigger_guidance": "Etik tutarsizlik: 'duruma bagli', 'az miktarsa onemli degil', duruma gore degisen kurallar",
                "severity": "high"
              }
            ]
          },
          {
            "slot": 8,
            "competency": "role_competence",
            "question": "Gunluk kasa islemlerinizi anlatir misiniz? Acilis, islem ve kapanis sureclerinde neler yapiyorsunuz?",
            "method": "Direct",
            "scoring_rubric": {
              "1": "Temel kasa islemlerini bilmiyor, deneyim yok veya cok sinirli",
              "2": "Bazi islemleri biliyor ama eksikler var, yonlendirme gerektirir",
              "3": "Standart islemleri yapabilir: nakit, kredi karti, iade proseduru",
              "4": "Tum islemleri biliyor, kampanya uygulama, sorun giderme yapabilir",
              "5": "Uzman seviye: karmasik islemler, egitim verebilir, sistem sorun giderme"
            },
            "positive_signals": [
              "Islem adimlarini dogru siraladi",
              "Guvenlik protokollerini bildi",
              "Sorun giderme ornegi verdi",
              "Musteri memnuniyetine odaklandi"
            ],
            "red_flag_hooks": [
              {
                "code": "RF_INCONSIST",
                "trigger_guidance": "Deneyim abartisi: temel sorulara yanlis cevaplar, detay istendiginde belirsizlik, CV ile uyumsuz bilgiler",
                "severity": "high"
              }
            ]
          }
        ]
      }
    }
  ]
}
JSON;

        // Validate JSON
        $decoded = json_decode($exactJson, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON: ' . json_last_error_msg());
        }
        echo "JSON validation: PASSED\n\n";

        // STEP A: Deactivate OLD rows (only those with old titles, not the new exact JSON rows)
        echo "STEP A: Deactivating old rows...\n";
        $deactivated = DB::table('interview_templates')
            ->where('version', 'v1')
            ->where('language', 'tr')
            ->whereIn('title', ['Genel Mulakat Sablonu', 'Kasiyer Mulakat Sablonu'])
            ->update(['is_active' => false]);
        echo "  Deactivated rows: {$deactivated}\n\n";

        // STEP B: Rename old position_codes to avoid conflicts (only if not already renamed)
        echo "STEP B: Renaming old position_codes...\n";

        // Old cashier row -> retail_cashier_v0 (only if retail_cashier_v0 doesn't exist yet)
        $existingV0Cashier = DB::table('interview_templates')
            ->where('position_code', 'retail_cashier_v0')
            ->exists();

        if (!$existingV0Cashier) {
            $renamedCashier = DB::table('interview_templates')
                ->where('position_code', 'retail_cashier')
                ->where('is_active', false)
                ->whereIn('title', ['Kasiyer Mulakat Sablonu'])
                ->update(['position_code' => 'retail_cashier_v0']);
            echo "  Renamed cashier rows: {$renamedCashier}\n";
        } else {
            echo "  retail_cashier_v0 already exists (skipped)\n";
        }

        // Old generic row (NULL or generic_v0) -> generic_v0 (only if not already done)
        $existingV0Generic = DB::table('interview_templates')
            ->where('position_code', '__generic___v0')
            ->exists();

        if (!$existingV0Generic) {
            $renamedGeneric = DB::table('interview_templates')
                ->whereNull('position_code')
                ->where('is_active', false)
                ->update(['position_code' => '__generic___v0']);
            echo "  Renamed generic rows: {$renamedGeneric}\n\n";
        } else {
            echo "  generic_v0 already exists (skipped)\n\n";
        }

        // STEP C: Insert new rows with EXACT JSON (idempotent)
        echo "STEP C: Inserting new rows with EXACT JSON...\n";

        // Use updateOrCreate for idempotent generic row
        $genericRow = DB::table('interview_templates')
            ->where('version', 'v1')
            ->where('language', 'tr')
            ->where('position_code', '__generic__')
            ->first();

        if (!$genericRow) {
            $genericId = (string) \Illuminate\Support\Str::uuid();
            DB::table('interview_templates')->insert([
                'id' => $genericId,
                'version' => 'v1',
                'language' => 'tr',
                'position_code' => '__generic__',
                'title' => 'Generic Interview Template (Exact JSON)',
                'template_json' => $exactJson,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  Inserted __generic__ row: {$genericId}\n";
        } else {
            // Update existing row with exact JSON and ensure active
            DB::table('interview_templates')
                ->where('id', $genericRow->id)
                ->update([
                    'title' => 'Generic Interview Template (Exact JSON)',
                    'template_json' => $exactJson,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            echo "  Updated __generic__ row: {$genericRow->id}\n";
        }

        // Use updateOrCreate for idempotent retail_cashier row
        $cashierRow = DB::table('interview_templates')
            ->where('version', 'v1')
            ->where('language', 'tr')
            ->where('position_code', 'retail_cashier')
            ->first();

        if (!$cashierRow) {
            $cashierId = (string) \Illuminate\Support\Str::uuid();
            DB::table('interview_templates')->insert([
                'id' => $cashierId,
                'version' => 'v1',
                'language' => 'tr',
                'position_code' => 'retail_cashier',
                'title' => 'Kasiyer Interview Template (Exact JSON)',
                'template_json' => $exactJson,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  Inserted retail_cashier row: {$cashierId}\n";
        } else {
            // Update existing row with exact JSON and ensure active
            DB::table('interview_templates')
                ->where('id', $cashierRow->id)
                ->update([
                    'title' => 'Kasiyer Interview Template (Exact JSON)',
                    'template_json' => $exactJson,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            echo "  Updated retail_cashier row: {$cashierRow->id}\n";
        }

        // STEP D: Print final rows
        echo "\n========================================\n";
        echo "FINAL ROWS IN interview_templates:\n";
        echo "========================================\n";

        $allRows = DB::table('interview_templates')
            ->select('id', 'version', 'language', 'position_code', 'title', 'is_active')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        echo sprintf("%-38s | %-7s | %-5s | %-20s | %-45s | %-6s\n",
            'ID', 'VERSION', 'LANG', 'POSITION_CODE', 'TITLE', 'ACTIVE');
        echo str_repeat('-', 140) . "\n";

        foreach ($allRows as $row) {
            echo sprintf("%-38s | %-7s | %-5s | %-20s | %-45s | %-6s\n",
                $row->id,
                $row->version,
                $row->language,
                $row->position_code ?? 'NULL',
                substr($row->title ?? '', 0, 45),
                $row->is_active ? 'YES' : 'NO'
            );
        }

        echo "\nTotal rows: " . count($allRows) . "\n";
        echo "Active rows: " . $allRows->where('is_active', true)->count() . "\n";
        echo "Inactive rows: " . $allRows->where('is_active', false)->count() . "\n";
    }
}
