# Assessment Question & Evaluation Engine

## Tezgahtar / Kasiyer Değerlendirme Motoru

### Genel Bakış

Bu motor, perakende sektöründe tezgahtar ve kasiyer pozisyonları için adayları değerlendirmek üzere tasarlanmıştır. 10 senaryo bazlı soru ile adayların davranışsal yetkinliklerini ölçer.

---

## Yetkinlikler (Competencies)

| Kod | Yetkinlik | Ağırlık | Açıklama |
|-----|-----------|---------|----------|
| CUSTOMER_SERVICE | Müşteri Hizmeti | %20 | Müşterilerle etkili iletişim, nazik davranış, çözüm odaklılık |
| INTEGRITY | Dürüstlük ve Güvenilirlik | %20 | Para işlemlerinde doğruluk, hırsızlık önleme, etik davranış |
| HYGIENE | Hijyen ve Temizlik | %15 | Kişisel hijyen, çalışma alanı temizliği, gıda güvenliği |
| STRESS_HANDLING | Stres Yönetimi | %15 | Yoğun dönemlerde sakin kalma, baskı altında performans |
| RESPONSIBILITY | Sorumluluk | %15 | İş sahiplenme, dakiklik, görev bilinci |
| TEAMWORK | Takım Çalışması | %15 | İş arkadaşlarıyla uyum, yardımlaşma, iletişim |

---

## Kırmızı Bayraklar (Red Flags)

| Kod | Bayrak | Ciddiyet | Tetikleyiciler |
|-----|--------|----------|----------------|
| THEFT_TENDENCY | Hırsızlık Eğilimi | 🔴 Critical | Kasa açığını normalleştirme, küçük hırsızlıkları mazur görme |
| CUSTOMER_AGGRESSION | Müşteriye Saldırganlık | 🟠 High | Bağırma, hakaret, fiziksel tehdit |
| HYGIENE_NEGLIGENCE | Hijyen İhmali | 🟠 High | El yıkamayı gereksiz bulma, kirli ürün satışı |
| RESPONSIBILITY_AVOIDANCE | Sorumluluktan Kaçınma | 🟡 Medium | Hataları başkasına yükleme, mazeret üretme |
| CONFLICT_PRONE | Çatışmaya Meyil | 🟡 Medium | İş arkadaşlarıyla sürekli sorun, takım uyumsuzluğu |

---

## Sorular ve Değerlendirme Kriterleri

### Soru 1: Sinirli Müşteri (CUSTOMER_SERVICE)
**Senaryo:** Bir müşteri bozuk ürün iddiasıyla sinirli şekilde geliyor, yüksek sesle şikayet ediyor.

| Puan | Kriter |
|------|--------|
| 5 | Sakinleştirir, özür diler, kenara alır, çözüm sunar, memnuniyet sağlar |
| 4 | Dinler, özür diler, yöneticiye yönlendirir, profesyonel kalır |
| 3 | Özür diler ama çözüm sunmada yetersiz |
| 2 | Savunmaya geçer ama kontrol altında tutar |
| 1 | Tartışır, sahne yaratır |
| 0 | Görmezden gelir veya hakaret eder |

**🚩 Kırmızı Bayrak:** Bağırma, hakaret, fiziksel tehdit, görmezden gelme

---

### Soru 2: Kasa Açığı (INTEGRITY)
**Senaryo:** Gün sonunda kasada 50 TL eksik var, hata hatırlamıyor.

| Puan | Kriter |
|------|--------|
| 5 | Hemen yöneticiye bildirir, kamera incelenmesini ister, işbirliği yapar |
| 4 | Yöneticiye bildirir, açıklar, soruşturmaya yardımcı olur |
| 3 | Bildirir ama tedirgin, açıklamakta zorlanır |
| 2 | Bildirmeyi geciktirir, kendisi çözmeye çalışır |
| 1 | Gizlemeye çalışır veya başkasını suçlar |
| 0 | Kapatmaya çalışır veya fark etmemiş gibi davranır |

**🚩 Kırmızı Bayrak:** Gizleme, kapatma, suçlama, normalleştirme

---

### Soru 3: Son Kullanma Tarihi (HYGIENE)
**Senaryo:** Ürünün SKT'si bugün, rafta çoğu bugün/yarın tarihli.

| Puan | Kriter |
|------|--------|
| 5 | Hepsini çeker, yöneticiye bildirir, müşteri güvenliğini ön planda tutar |
| 4 | Yöneticiye bildirir, talimat bekler, satıştan çeker |
| 3 | Sadece bugünküleri çeker, yöneticiye bildirir |
| 2 | Görmezden gelir, müşteri alırsa uyarır |
| 1 | Arkaya koyar, eski tarihlileri öne dizer |
| 0 | Hiçbir şey yapmaz |

**🚩 Kırmızı Bayrak:** Satmaya devam, görmezden gelme, müşteri sağlığını önemsememe

---

### Soru 4: Yoğun Kasa (STRESS_HANDLING)
**Senaryo:** Cumartesi, uzun kuyruk, yavaş POS, acele ettiren müşteri, yardım isteyen iş arkadaşı.

| Puan | Kriter |
|------|--------|
| 5 | Sakin kalır, durumu açıklar, öncelikleri belirler, hızlı ama doğru çalışır |
| 4 | Sakin kalır, özür diler, işine devam eder |
| 3 | Biraz stresli ama kontrol altında |
| 2 | Stres belirtileri, hatalar yapmaya başlar |
| 1 | Panikler, kaba davranır, suçlar |
| 0 | Kasayı terk eder, kontrolü kaybeder |

**🚩 Kırmızı Bayrak:** Kasayı terk, müşteriye bağırma, panik

---

### Soru 5: Ekstra İş (RESPONSIBILITY)
**Senaryo:** İş arkadaşı gelmedi, onun bölümünü de kapatmanız gerekiyor.

| Puan | Kriter |
|------|--------|
| 5 | Sahiplenir, öğrenmeye istekli, ekstra çaba, şikayet etmez |
| 4 | Kabul eder, elinden gelenin en iyisini yapar |
| 3 | Yapar ama isteksiz, sürekli yardım ister |
| 2 | Şikayet eder, minimum çaba |
| 1 | Reddetmeye çalışır, savsaklar |
| 0 | Kesinlikle reddeder veya bırakır |

**🚩 Kırmızı Bayrak:** Reddetme, bırakma, sabotaj

---

### Soru 6: Kaytaran İş Arkadaşı (TEAMWORK)
**Senaryo:** İş arkadaşı kaytarıyor, siz daha fazla iş yapıyorsunuz.

| Puan | Kriter |
|------|--------|
| 5 | Önce özel konuşur, anlamaya çalışır, çözüm bulamazsa profesyonelce bildirir |
| 4 | Yöneticiye sakin iletir, kişisel saldırı yapmaz |
| 3 | Sessiz kalır ama işini yapar |
| 2 | Arkasından konuşur, dedikodu yapar |
| 1 | Tartışır, sahne yaratır |
| 0 | İntikam alır, takımı bozar |

**🚩 Kırmızı Bayrak:** İntikam, sabotaj, fiziksel çatışma

---

### Soru 7: Arkadaş İndirimi (INTEGRITY)
**Senaryo:** Yakın arkadaş "Bana indirim yapsana, kimse fark etmez" diyor.

| Puan | Kriter |
|------|--------|
| 5 | Nazikçe ama kesinlikle reddeder, kuralları açıklar, gerekirse bildirir |
| 4 | Reddeder, kuralları açıklar, kararlı durur |
| 3 | Reddeder ama rahatsız olur |
| 2 | Tereddüt eder, "belki bir kerelik" der |
| 1 | Küçük indirim yapar, "kimse görmez" mantığıyla |
| 0 | İndirim yapar veya ürün verir, normal görür |

**🚩 Kırmızı Bayrak:** İndirim yapma, ürün verme, kuralları çiğneme

---

### Soru 8: Yaşlı Müşteri (CUSTOMER_SERVICE)
**Senaryo:** Yaşlı müşteri yavaş konuşuyor, aynı soruları tekrarlıyor, arkada bekleyenler var.

| Puan | Kriter |
|------|--------|
| 5 | Sabırla dinler, yavaş açıklar, anladığından emin olur, bekleyenlere mesaj verir |
| 4 | Sabırlı davranır, açıklamaya çalışır, müşteri memnun ayrılır |
| 3 | Biraz sabırsız ama profesyonel kalır |
| 2 | Açıkça sabırsızlık, acele ettirir |
| 1 | Başka birine yönlendirir, ilgilenmek istemez |
| 0 | Kaba davranır, aşağılar, görmezden gelir |

**🚩 Kırmızı Bayrak:** Aşağılama, yaş ayrımcılığı, görmezden gelme

---

### Soru 9: Moladan Dönüş (HYGIENE)
**Senaryo:** Moladan dönüyorsunuz, el yıkamadan kasaya geçmeniz gerekiyor, müşteriler bekliyor.

| Puan | Kriter |
|------|--------|
| 5 | Kesinlikle önce yıkar, 30 saniye özür diler, hijyen öncelik |
| 4 | Elini yıkar, hızlıca döner |
| 3 | Dezenfektan kullanır |
| 2 | Tereddüt eder, bazen yıkar bazen yıkamaz |
| 1 | Yıkamadan geçer, "bir şey olmaz" der |
| 0 | Hiç önemsemez, hijyeni gereksiz bulur |

**🚩 Kırmızı Bayrak:** Hijyeni gereksiz bulma, hiç yıkamama

---

### Soru 10: Mesai Bitimi (RESPONSIBILITY)
**Senaryo:** Mesai bitti, kasa devir yapılmadı, devralacak kişi 15 dk geç kalacak.

| Puan | Kriter |
|------|--------|
| 5 | Bekler, işini tamamlar, düzgün devreder, mesai dışı çalışmayı bildirir |
| 4 | Bekler ve işini tamamlar |
| 3 | Şikayet ederek bekler ama yapar |
| 2 | Yöneticiye bildirip gitmek ister |
| 1 | Kasayı açık bırakıp gider |
| 0 | Kontrolsüz bırakır, parayı masada bırakır |

**🚩 Kırmızı Bayrak:** Kasayı kontrolsüz bırakma, parayı açıkta bırakma, terk etme

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

### Risk Seviyesi Etkisi

- **Critical Risk:** Otomatik RED (skora bakılmaksızın)
- **High Risk:** Maksimum "Koşullu" öneri
- **Medium Risk:** Skor 70+ ise "Eğitimle işe al"
- **Low Risk:** Skora göre normal öneri

### Yetkinlik Skoru Cezası

Kırmızı bayrak tespit edildiğinde ilgili yetkinlik skoru maksimum %50'ye düşürülür.

---

## Çıktı Formatı

```json
{
  "competency_scores": {
    "CUSTOMER_SERVICE": { "score": 80, "feedback": "..." },
    "INTEGRITY": { "score": 90, "feedback": "..." },
    "HYGIENE": { "score": 75, "feedback": "..." },
    "STRESS_HANDLING": { "score": 70, "feedback": "..." },
    "RESPONSIBILITY": { "score": 85, "feedback": "..." },
    "TEAMWORK": { "score": 80, "feedback": "..." }
  },
  "overall_score": 81,
  "level_label": "İyi",
  "level_numeric": 4,
  "risk_flags": [],
  "risk_level": "low",
  "manager_summary": "Aday, İyi seviyesinde performans gösterdi...",
  "hiring_recommendation": "hire",
  "strengths": ["Dürüstlük", "Sorumluluk"],
  "development_areas": [
    { "competency": "Stres Yönetimi", "suggestion": "..." }
  ]
}
```

---

## Kullanım

```php
$service = new AssessmentEvaluationService();
$service->loadTemplate('tezgahtar-kasiyer');

// Soruları al
$questions = $service->getQuestions();

// Yanıtları topla ve AI prompt oluştur
$responses = [
    ['question_order' => 1, 'response' => 'Aday yanıtı...'],
    // ...
];
$prompt = $service->buildEvaluationPrompt($responses);

// AI'dan gelen yanıtı işle
$aiResponse = // AI API çağrısı
$result = $service->calculateFinalScores($aiResponse);

// Yönetici özeti
$summary = $service->generateManagerSummary('Ahmet Yılmaz', $result);
```
