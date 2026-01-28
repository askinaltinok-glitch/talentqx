# Assessment Engine: Üretim Personeli

## Genel Bakış

Bu motor, üretim/imalat sektöründe çalışacak personeli değerlendirmek üzere tasarlanmıştır. 10 senaryo bazlı soru ile adayların iş güvenliği, kalite bilinci ve disiplin yetkinliklerini ölçer.

---

## Yetkinlikler (Competencies)

| Kod | Yetkinlik | Ağırlık | Açıklama |
|-----|-----------|---------|----------|
| SAFETY_AWARENESS | İş Güvenliği Bilinci | %25 | KKD kullanımı, güvenlik prosedürlerine uyum, tehlike farkındalığı |
| QUALITY_FOCUS | Kalite Odaklılık | %20 | Üretim standartlarına uyum, hata önleme, kalite kontrol bilinci |
| DISCIPLINE | Disiplin ve Düzen | %15 | Dakiklik, kurallara uyum, düzenli çalışma |
| TEAMWORK | Takım Çalışması | %15 | Vardiya arkadaşlarıyla uyum, iletişim, yardımlaşma |
| RESPONSIBILITY | Sorumluluk | %15 | İş sahiplenme, hata bildirme, görev bilinci |
| ADAPTABILITY | Uyum ve Öğrenme | %10 | Yeni makinelere uyum, talimat takibi, değişime açıklık |

---

## Kırmızı Bayraklar (Red Flags)

| Kod | Bayrak | Ciddiyet | Tetikleyiciler |
|-----|--------|----------|----------------|
| SAFETY_NEGLIGENCE | Güvenlik İhmali | 🔴 Critical | KKD reddi, güvenliği gereksiz bulma, tehlikeyi önemsizleştirme |
| QUALITY_SABOTAGE | Kalite Sabotajı | 🔴 Critical | Hatalı ürünü bilerek geçirme, kalite kontrolü atlatma |
| SUBSTANCE_ABUSE_SIGNS | Madde Kullanımı | 🔴 Critical | Alkollü gelmeyi normalleştirme, uyuşturucu kullanımı |
| CONFLICT_PRONE | Çatışmaya Meyil | 🟠 High | Kavga, amire karşı gelme, fiziksel tehdit |
| ATTENDANCE_ISSUES | Devamsızlık Eğilimi | 🟡 Medium | Sürekli mazeret, geç kalmayı normalleştirme |

---

## Sorular ve Değerlendirme Kriterleri

### Soru 1: Çizik Koruyucu Gözlük (SAFETY_AWARENESS)
**Senaryo:** Gözlüğünüzün camı çizilmiş, net göremiyorsunuz. Yedek almak 10 dakika sürecek, üretim hedefi sıkışık.

| Puan | Kriter |
|------|--------|
| 5 | Derhal durur, yedek gözlük alır, amire bildirir, güvenliği öncelikler |
| 4 | Yedek gözlük almaya gider, amire bilgi verir |
| 3 | Yedek alır ama şikayet eder |
| 2 | Biraz daha dayanır, mola gelince değiştirir |
| 1 | Gözlüğü çıkarıp çalışmaya devam eder |
| 0 | Güvenlik ekipmanını gereksiz bulur |

**🚩 Kırmızı Bayrak:** KKD'yi çıkarma, güvenliği gereksiz bulma

---

### Soru 2: Çizik Parça (QUALITY_FOCUS)
**Senaryo:** Ürettiğiniz parçada küçük çizik var. Tolerans içinde ama görünür. Kalite kontrol muhtemelen fark etmeyecek.

| Puan | Kriter |
|------|--------|
| 5 | Parçayı ayırır, bildirir, sebebini araştırır, önlem önerir |
| 4 | Parçayı ayırır, amire/kalite kontrole bildirir |
| 3 | Ayrı koyar, vardiya sonunda bildirir |
| 2 | Kalite kontrolün karar vermesini bekler |
| 1 | Geçirir, 'nasılsa fark etmezler' der |
| 0 | Gizler veya kapatmaya çalışır |

**🚩 Kırmızı Bayrak:** Bilerek geçirme, gizleme, kaliteyi önemsememe

---

### Soru 3: Yanlış Ayar (RESPONSIBILITY)
**Senaryo:** Makineyi yanlış ayar yaptınız, son 30 dakikada üretilen parçalar hatalı olabilir. Henüz kimse fark etmedi.

| Puan | Kriter |
|------|--------|
| 5 | Hemen durdurur, amire bildirir, hatalı parçaları işaretler, önlem önerir |
| 4 | Makineyi durdurur, bildirir, parçaları ayırır |
| 3 | Bildirir ama gecikmeli, önce kendisi düzeltmeye çalışır |
| 2 | Sessizce düzeltir, kimseye söylemez |
| 1 | Başkasını suçlamaya çalışır |
| 0 | Görmezden gelir, devam eder |

**🚩 Kırmızı Bayrak:** Gizleme, suçlama, görmezden gelme

---

### Soru 4: Yardım İsteyen Arkadaş (TEAMWORK)
**Senaryo:** Vardiya arkadaşı yetiştiremiyor, yardım istiyor. Yardım ederseniz kendi hedefinizi kaçırabilirsiniz.

| Puan | Kriter |
|------|--------|
| 5 | Kendi işini hızlandırır, sonra yardım eder, amire bilgi verir, takımı öncelikler |
| 4 | Amire bildirir, birlikte çözüm arar |
| 3 | Biraz yardım eder, kendi işini de aksatmamaya çalışır |
| 2 | Yardım etmez ama nazikçe reddeder |
| 1 | Kaba reddeder, 'benim sorunum değil' der |
| 0 | Dalga geçer veya zorlaştırır |

**🚩 Kırmızı Bayrak:** Dalga geçme, sabotaj, takım karşıtlığı

---

### Soru 5: 5 Dakika Geç Kalma (DISCIPLINE)
**Senaryo:** Sabah 5 dakika geç kaldınız. Kart basmadan tuvalete gittiğiniz için amir fark etmedi.

| Puan | Kriter |
|------|--------|
| 5 | Amire gidip bildirir, özür diler, telafi teklif eder |
| 4 | Amire bildirir, özür diler |
| 3 | Bu sefer söylemez, bir dahakine dikkat eder |
| 2 | 'Zaten 5 dakika' diye normalleştirir |
| 1 | Herkesin geç kaldığını düşünür |
| 0 | Sistemi kandırmaya devam eder, kart hilesi yapar |

**🚩 Kırmızı Bayrak:** Sistemi kandırma, kart hilesi

---

### Soru 6: Korumasız Makine (SAFETY_AWARENESS)
**Senaryo:** İş arkadaşı makineyi koruma kalkanı olmadan kullanıyor. 'Daha rahat' diyor.

| Puan | Kriter |
|------|--------|
| 5 | Nazikçe uyarır, tehlikeyi açıklar, gerekirse amire bildirir |
| 4 | Uyarır ve kalkanı takmasını ister |
| 3 | Uyarır ama ısrar etmez |
| 2 | Görmezden gelir |
| 1 | Kendisi de korumayı çıkarır |
| 0 | Güvenlik kurallarını eleştirir, arkadaşı destekler |

**🚩 Kırmızı Bayrak:** Güvenlik eleştirisi, tehlikeli davranışı destekleme

---

### Soru 7: Yeni Makine (ADAPTABILITY)
**Senaryo:** Yeni ve karmaşık bir makine kullanmanız isteniyor. Daha önce hiç kullanmadınız.

| Puan | Kriter |
|------|--------|
| 5 | Heyecanla öğrenmeye istekli, eğitim ister, notlar alır, pratik yapar |
| 4 | Eğitim alır, öğrenmeye çalışır, soru sorar |
| 3 | Kabul eder ama isteksiz |
| 2 | Şikayet eder, eski makinede kalmak ister |
| 1 | Reddetmeye çalışır |
| 0 | Öğrenmeyi reddeder, sabotaj yapar |

**🚩 Kırmızı Bayrak:** Öğrenmeyi reddetme, sabotaj

---

### Soru 8: Uzun Mola (DISCIPLINE)
**Senaryo:** 15 dakikalık mola hakkınız var. İş arkadaşları 20-25 dakika yapıyor, amir ses çıkarmıyor.

| Puan | Kriter |
|------|--------|
| 5 | 15 dakikada döner, başkaları uzatsa bile disiplinini korur |
| 4 | 15 dakikada döner, bazen 1-2 dakika gecikir |
| 3 | 18-20 dakikada döner |
| 2 | Herkes gibi 20-25 dakika yapar |
| 1 | Daha uzun yapar, 'ses çıkmıyor' der |
| 0 | 30+ dakika mola, savsaklar |

**🚩 Kırmızı Bayrak:** Aşırı uzun mola, iş savsaklama

---

### Soru 9: Hedef Baskısı (QUALITY_FOCUS)
**Senaryo:** 100 parça daha üretmeniz lazım, 1 saat kaldı. Normal hızda 80 parça üretebilirsiniz.

| Puan | Kriter |
|------|--------|
| 5 | Kaliteden ödün vermeden maksimum çalışır, durumu bildirir, gerçekçi hedef önerir |
| 4 | Elinden gelenin en iyisini yapar, kaliteyi korur, bildirir |
| 3 | Hızlanır, biraz kalite düşer |
| 2 | Kaliteyi ihmal eder, sayıya odaklanır |
| 1 | Hatalı ürün geçirir, kalite kontrolü atlar |
| 0 | Sahte sayı girer veya başkasının üretimine yazar |

**🚩 Kırmızı Bayrak:** Sahte sayı, kalite sabotajı, hile

---

### Soru 10: Cumartesi Mesaisi (RESPONSIBILITY)
**Senaryo:** Cumartesi mesai isteniyor, ailevi planınız var. Üretim gerçekten sıkışık.

| Puan | Kriter |
|------|--------|
| 5 | Açıkça anlatır, alternatif sunar (yarım gün, başka gün), uzlaşma arar |
| 4 | Durumu açıklar, mümkünse katılmaya çalışır |
| 3 | Gelemeyeceğini nazikçe söyler, özür diler |
| 2 | Bahane uydurur |
| 1 | Haber vermeden gelmez |
| 0 | Başkalarını kışkırtır, 'gelmeyin' der |

**🚩 Kırmızı Bayrak:** Haber vermeden gelmeme, kışkırtma

---

## Skorlama ve Karar

### Performans Seviyeleri

| Skor | Seviye | Numara | Öneri |
|------|--------|--------|-------|
| 85-100 | Mükemmel | 5 | Öncelikli işe al |
| 70-84 | İyi | 4 | İşe al |
| 55-69 | Yeterli | 3 | Eğitimle işe al |
| 40-54 | Geliştirilmeli | 2 | Koşullu değerlendir |
| 0-39 | Yetersiz | 1 | Reddet |

### Özel Kurallar (Üretim Sektörü)

1. **Güvenlik İhmali = Otomatik RED**
   - SAFETY_NEGLIGENCE bayrağı tespit edilirse, skora bakılmaksızın işe alım önerilmez

2. **Kalite Sabotajı = Otomatik RED**
   - QUALITY_SABOTAGE bayrağı tespit edilirse işe alım önerilmez

3. **Madde Kullanımı İşaretleri = Otomatik RED**
   - SUBSTANCE_ABUSE_SIGNS bayrağı tespit edilirse işe alım önerilmez

---

## Çıktı Formatı

```json
{
  "competency_scores": {
    "SAFETY_AWARENESS": { "score": 90, "feedback": "Güvenlik bilinci çok yüksek" },
    "QUALITY_FOCUS": { "score": 80, "feedback": "Kalite standartlarına dikkat ediyor" },
    "DISCIPLINE": { "score": 75, "feedback": "Kurallara uyumlu" },
    "TEAMWORK": { "score": 70, "feedback": "Takım çalışmasına yatkın" },
    "RESPONSIBILITY": { "score": 85, "feedback": "Sorumluluk sahibi" },
    "ADAPTABILITY": { "score": 65, "feedback": "Yeni duruma uyum sağlayabilir" }
  },
  "overall_score": 79,
  "level_label": "İyi",
  "level_numeric": 4,
  "risk_flags": [],
  "risk_level": "low",
  "manager_summary": "Aday, İyi seviyesinde performans gösterdi. Güvenlik bilinci yüksek, kaliteye önem veriyor. Öneri: İşe alınması önerilir.",
  "hiring_recommendation": "hire",
  "strengths": ["İş Güvenliği Bilinci", "Sorumluluk"],
  "development_areas": [
    { "competency": "Uyum ve Öğrenme", "suggestion": "Yeni teknolojilere adaptasyon eğitimi" }
  ]
}
```

---

## Kullanım

```php
$service = new AssessmentEvaluationService();
$service->loadTemplate('uretim-personeli');

// Soruları al
$questions = $service->getQuestions();

// Yanıtları değerlendir
$responses = [
    ['question_order' => 1, 'response' => 'Hemen durup yedek gözlük alırım...'],
    // ...
];
$prompt = $service->buildEvaluationPrompt($responses);

// AI değerlendirmesi
$aiResponse = // AI API çağrısı
$result = $service->calculateFinalScores($aiResponse);
```
