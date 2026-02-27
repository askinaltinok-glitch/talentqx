<?php
// HOSPITALITY - 12 pozisyon × 8 soru = 96 soru
return [

'HOSP_FRONT_RECEPTIONIST' => [
    ['Uzun bir yolculuktan sonra yorgun ve sinirli gelen bir misafiri nasıl karşılarsınız?', 'CUSTOMER_FOCUS', 'situational', ['Empati','Hızlı check-in','Rahatlatıcı iletişim','Ekstra özen'], ['İlgisizlik','Mekanik karşılama','Sabırsızlık','Soğuk tutum'], 2],
    ['Resepsiyondaki ekip arkadaşınızla vardiya devri sırasında iletişim nasıl olmalı?', 'COMMUNICATION', 'behavioral', ['Detaylı bilgi aktarımı','Not tutma','Önemli misafir bilgisi','İşbirliği'], ['Yetersiz bilgilendirme','Not bırakmama','İletişim kopukluğu','Sorumsuzluk'], 2],
    ['Vardiya saatlerine, kıyafet koduna ve otel standartlarına uyum konusundaki tutumunuz nedir?', 'RELIABILITY', 'behavioral', ['Dakiklik','Profesyonel görünüm','Standart bağlılığı','Tutarlılık'], ['Geç kalma','Kıyafet ihmali','Standart düşürme','Mazeret üretme'], 2],
    ['Otelcilik sektöründe kariyer hedefiniz ne? Bu pozisyonu uzun vadeli mi görüyorsunuz?', 'LEARNING_AGILITY', 'experience', ['Ön büro şefliği hedefi','Dil öğrenme','Sektör tutkusu','Uzun vadeli plan'], ['Geçici iş','Hedefsizlik','Motivasyon eksikliği','Kısa vadeli bakış'], 2],
    ['Oteldeki farklı departmanlarla (kat hizmetleri, restoran, teknik) nasıl koordineli çalışırsınız?', 'TEAMWORK', 'experience', ['Departmanlar arası iletişim','Yardımlaşma','Bilgi paylaşımı','Ekip ruhu'], ['Sadece kendi alanı','İletişim kopukluğu','Departman çatışması'], 2],
    ['Overbooking durumunda misafiri başka bir otele yönlendirmeniz gerektiğinde nasıl hareket edersiniz?', 'ADAPTABILITY', 'situational', ['Empati','Profesyonel çözüm','Alternatif sunma','Telafi'], ['Panik','Suçlama','Çözümsüzlük','Misafiri suçlama'], 2],
    ['Misafir anahtarını kaybettiğini söylediğinde güvenlik prosedürünü nasıl uygularsınız?', 'PROBLEM_SOLVING', 'situational', ['Kimlik doğrulama','Prosedüre uyma','Hızlı çözüm','Güvenlik bilinci'], ['Sorgusuz anahtar verme','Prosedür bilmeme','Güvenlik ihmali'], 2],
    ['Önceki iş deneyimlerinizden neden ayrıldınız? Bu otelden beklentiniz ne?', 'ATTENTION_TO_DETAIL', 'experience', ['Yapıcı nedenler','Gerçekçi beklentiler','Sektör tutkusu','İstikrar'], ['Sık değişim','Olumsuz tutum','Suçlama','Geçici iş arayışı'], 2],
],

'HOSP_FRONT_SUPERVISOR' => [
    ['Misafir şikayetlerinin tekrar etmemesi için nasıl bir sistem kurarsınız?', 'CUSTOMER_FOCUS', 'behavioral', ['Şikayet analizi','Kök neden çözümü','Ekip eğitimi','Takip sistemi'], ['Reaktif yaklaşım','Analiz yapmama','Tekrarlayan sorunlar'], 3],
    ['Ön büro ekibinde performans düşüklüğü veya motivasyon sorunu yaşandığında nasıl müdahale edersiniz?', 'COMMUNICATION', 'behavioral', ['Birebir görüşme','Empati','Motivasyon kaynağı arama','Aksiyon planı'], ['Görmezden gelme','Baskıcılık','Cezalandırma','İletişimsizlik'], 3],
    ['Ön büro ekibinin disiplini, vardiya düzeni ve standartlara uyumunu nasıl sağlarsınız?', 'RELIABILITY', 'behavioral', ['Tutarlı uygulama','Kontrol listeleri','Örnek olma','Adil yaklaşım'], ['Tutarsızlık','Favoricilik','Kontrolsüzlük','Kendine farklı standart'], 3],
    ['Otel ön büro yönetiminde kariyer hedefiniz ne? 2-3 yıl sonra nerede olmak istiyorsunuz?', 'LEARNING_AGILITY', 'experience', ['Ön büro müdürlüğü','Genel müdür yardımcılığı','Uzun vadeli vizyon','Gelişim planı'], ['Kısa vadeli bakış','Tükenmişlik','Belirsiz hedefler','Motivasyon eksikliği'], 3],
    ['Ön büro, kat hizmetleri ve teknik servis arasındaki koordinasyonu nasıl yönetirsiniz?', 'TEAMWORK', 'behavioral', ['Departmanlar arası toplantılar','Ortak prosedürler','İletişim kanalları','Sorun çözme'], ['Departman çatışması','Koordinasyonsuzluk','Tek taraflı kararlar'], 3],
    ['Check-in sisteminin çöktüğü bir akşam nasıl organize olursunuz?', 'ADAPTABILITY', 'situational', ['Manuel prosedür','Ekip yönlendirme','Misafir bilgilendirme','IT desteği'], ['Panik','Çaresizlik','Misafiri bekletme','Organizasyon eksikliği'], 3],
    ['VIP misafirin odası hazır değilken aynı anda 3 misafir check-in için bekliyorsa ne yaparsınız?', 'PROBLEM_SOLVING', 'situational', ['Önceliklendirme','Lobi ikramı','Kat hizmetleri koordinasyonu','Paralel çözüm'], ['Tek soruna takılma','Diğer misafirleri ihmal','Panik','Çözümsüzlük'], 3],
    ['Otelcilik deneyimlerinizden en önemli öğreniminiz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Liderlik deneyimi','Büyüme motivasyonu','Sektör tutkusu','Yapıcı bakış'], ['Sık otel değişimi','Negatif tutum','Suçlayıcılık','Tükenmişlik'], 3],
],

'HOSP_FRONT_MANAGER' => [
    ['Otel misafir deneyimini sürekli iyileştirmek için hangi stratejileri kullanırsınız?', 'CUSTOMER_FOCUS', 'behavioral', ['Misafir geri bildirim analizi','KPI takibi','Eğitim programları','İnovasyon'], ['Reaktif yaklaşım','Strateji eksikliği','Veri kullanmama','Misafirden kopukluk'], 4],
    ['Büyük ön büro ekibini nasıl motive eder ve yüksek performansta tutarsınız?', 'COMMUNICATION', 'behavioral', ['Bireysel koçluk','Düzenli toplantılar','Motivasyon programları','Açık iletişim'], ['İletişimsizlik','Otoriter yönetim','Motivasyonsuz liderlik','Tek yönlü iletişim'], 4],
    ['Ön büro operasyonel standartlarını ve ekip disiplinini nasıl yönetir ve geliştirirsiniz?', 'RELIABILITY', 'behavioral', ['SOP oluşturma','Denetim sistemi','Eğitim programları','Tutarlı uygulama'], ['Standart düşmesi','Kontrolsüzlük','Tutarsızlık','Delegasyon eksikliği'], 4],
    ['Ön büro müdürü olarak uzun vadeli vizyonunuz ne? Bu otelde kariyer planınız nasıl?', 'LEARNING_AGILITY', 'experience', ['GM yardımcılığı hedefi','Otel geliştirme vizyonu','Uzun vadeli bağlılık','Sürekli gelişim'], ['Kısa vadeli bakış','Tükenmişlik','Vizyonsuzluk','Sık otel değişimi'], 4],
    ['Tüm otel departmanlarıyla uyumlu çalışma kültürü oluşturmak için neler yaparsınız?', 'TEAMWORK', 'behavioral', ['Cross-functional toplantılar','Ortak hedefler','İletişim protokolleri','Ekip etkinlikleri'], ['Departman çatışması','Siloculuk','İletişim kopukluğu','Tek taraflılık'], 4],
    ['Yüksek sezon başlangıcında personel eksikliği yaşandığında nasıl organize olursunuz?', 'ADAPTABILITY', 'situational', ['Hızlı işe alım','Esnek vardiya','Geçici personel','Ekip motivasyonu'], ['Plansızlık','Kalite düşürme','Stres yansıtma','Organizasyon eksikliği'], 4],
    ['Online yorum platformlarında tekrarlayan olumsuz geri bildirimler aldığınızda nasıl aksiyon alırsınız?', 'PROBLEM_SOLVING', 'behavioral', ['Trend analizi','Kök neden çözümü','Operasyonel iyileştirme','Yanıt stratejisi'], ['Görmezden gelme','Savunmacılık','Yüzeysel çözüm','Suçlama'], 4],
    ['Otel yönetimi kariyerinizden en değerli deneyiminiz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Stratejik başarılar','Büyüme motivasyonu','Liderlik vizyonu','Sektör tutkusu'], ['Sık otel değişimi','Olumsuz deneyimler','Kötüleme','Tükenmişlik'], 4],
],

'HOSP_HOUSEKEEP_ATTENDANT' => [
    ['Misafir odasını temizlerken nelere özellikle dikkat edersiniz?', 'CUSTOMER_FOCUS', 'experience', ['Detay temizliği','Hijyen standardı','Kişisel eşyalara saygı','Sunum kalitesi'], ['Özensizlik','Hız odaklı kalitesizlik','Detay atlanması','İlgisizlik'], 2],
    ['Kat ekibindeki arkadaşlarınızla oda paylaşımı veya iş dağılımı konusunda nasıl iletişim kurarsınız?', 'COMMUNICATION', 'situational', ['Adil paylaşım','Yardımlaşma','Saygılı iletişim','Uzlaşma'], ['Tartışma','Adaletsizlik','İletişimsizlik','Şikayetçi tutum'], 2],
    ['Çalışma saatlerine, kat temizlik prosedürlerine ve hijyen kurallarına uyum konusundaki tutumunuz nedir?', 'RELIABILITY', 'behavioral', ['Dakiklik','Prosedür uyumu','Hijyen bilinci','Tutarlılık'], ['Geç kalma','Prosedür ihmali','Hijyen ihmal','Düzensizlik'], 2],
    ['Otelcilik sektöründe kendinizi nasıl geliştirmek istiyorsunuz? Bu işi uzun vadeli mi görüyorsunuz?', 'LEARNING_AGILITY', 'experience', ['Kat şefliği hedefi','Gelişim isteği','Sektör bağlılığı','İstikrar'], ['Geçici iş','Hedefsizlik','Motivasyon eksikliği','Kısa vadeli bakış'], 2],
    ['Farklı vardiyalardaki ekiplerle uyumlu çalışmak için neler yaparsınız?', 'TEAMWORK', 'experience', ['Vardiya devri bilinci','Yardımlaşma','Ekip ruhu','İletişim'], ['Bireyselcilik','İletişimsizlik','Uyumsuzluk','Sorumsuzluk'], 2],
    ['Check-out saatinde çok sayıda oda hızla temizlenmesi gerektiğinde nasıl organize olursunuz?', 'ADAPTABILITY', 'situational', ['Hızlı çalışma','Önceliklendirme','Ekiple koordinasyon','Kaliteyi koruma'], ['Panik','Kalite düşürme','Yavaşlık','Şikayetçi tutum'], 2],
    ['Misafir odasında şüpheli veya unutulmuş bir eşya bulduğunuzda ne yaparsınız?', 'PROBLEM_SOLVING', 'situational', ['Dokunmama','Amire bildirme','Prosedüre uyma','Kayıt tutma'], ['Kendine alma','Görmezden gelme','Prosedür bilmeme','Sorumsuzluk'], 2],
    ['Daha önce çalıştığınız yerlerden neden ayrıldınız? Bu otelden beklentiniz ne?', 'ATTENTION_TO_DETAIL', 'experience', ['Yapıcı nedenler','Gerçekçi beklentiler','İstikrar arayışı','Olgun tutum'], ['Sık değişim','Fiziksel işten kaçınma','Olumsuz tutum','Suçlama'], 2],
],

'HOSP_HOUSEKEEP_SUPERVISOR' => [
    ['Kat hizmetleri kalite standartlarını nasıl oluşturur ve takip edersiniz?', 'CUSTOMER_FOCUS', 'behavioral', ['Kontrol listeleri','Düzenli denetim','Misafir geri bildirimi','Eğitim'], ['Kontrolsüzlük','Standart eksikliği','Reaktif yaklaşım'], 3],
    ['Kat ekibinde performans düşüklüğü veya kişisel sorunlar yaşayan bir çalışanla nasıl konuşursunuz?', 'COMMUNICATION', 'behavioral', ['Empati','Birebir görüşme','Destek sunma','Çözüm odaklılık'], ['Görmezden gelme','Baskıcılık','İlgisizlik','Cezalandırma'], 3],
    ['Kat ekibinin dakiklik, hijyen ve prosedür uyumunu nasıl sağlarsınız?', 'RELIABILITY', 'behavioral', ['Düzenli kontrol','Eğitim','Örnek olma','Tutarlı uygulama'], ['Kontrolsüzlük','Tutarsızlık','Kendi disiplinsizliği','Favoricilik'], 3],
    ['Kat hizmetleri yönetiminde kariyer hedefiniz ne? 2-3 yıl sonra nerede olmak istiyorsunuz?', 'LEARNING_AGILITY', 'experience', ['Müdürlük hedefi','Gelişim planı','Uzun vadeli vizyon','Sektör bağlılığı'], ['Kariyer planı yok','Kısa vadeli bakış','Tükenmişlik','Motivasyon eksikliği'], 3],
    ['Ön büro ve teknik servis ile koordinasyonunuz nasıl? Acil oda hazırlama taleplerini nasıl yönetirsiniz?', 'TEAMWORK', 'behavioral', ['Hızlı koordinasyon','Önceliklendirme','İletişim','İşbirliği'], ['Departman çatışması','İletişim kopukluğu','Gecikme','Direnç'], 3],
    ['Yüksek doluluk dönemlerinde personel eksikliği yaşandığında nasıl organize olursunuz?', 'ADAPTABILITY', 'situational', ['Esnek planlama','Önceliklendirme','Kendi katılımı','Ekip motivasyonu'], ['Panik','Kalite düşürme','Organizasyon eksikliği','Stres yansıtma'], 3],
    ['Tekrarlayan temizlik şikayeti alan bir oda veya kat için nasıl aksiyon alırsınız?', 'PROBLEM_SOLVING', 'situational', ['Kök neden analizi','Eğitim','Denetim artırma','Takip'], ['Suçlama','Görmezden gelme','Yüzeysel çözüm','Analiz yapmama'], 3],
    ['Otelcilik deneyimlerinizden en önemli öğreniminiz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Liderlik deneyimi','Büyüme motivasyonu','Sektör tutkusu','Yapıcı bakış'], ['Sık değişim','Negatif tutum','Suçlayıcılık','Tükenmişlik'], 3],
],

'HOSP_HOUSEKEEP_MANAGER' => [
    ['Otel genel temizlik ve kat hizmetleri kalite stratejinizi nasıl belirlersiniz?', 'CUSTOMER_FOCUS', 'behavioral', ['Kalite standartları','Misafir memnuniyet analizi','Benchmark','Sürekli iyileştirme'], ['Vizyonsuzluk','Standart eksikliği','Misafirden kopukluk'], 4],
    ['Büyük kat ekibini nasıl motive eder ve iletişim kurarsınız?', 'COMMUNICATION', 'behavioral', ['Düzenli toplantılar','Bireysel görüşmeler','Motivasyon programları','Açık kapı politikası'], ['İletişimsizlik','Otoriter yönetim','Motivasyonsuz liderlik'], 4],
    ['Kat hizmetleri operasyonel disiplinini ve tutarlılığını nasıl yönetirsiniz?', 'RELIABILITY', 'behavioral', ['SOP oluşturma','Denetim sistemi','Eğitim programları','Tutarlı uygulama'], ['Standart düşmesi','Denetimsizlik','Tutarsızlık','Kontrol kaybı'], 4],
    ['Kat hizmetleri müdürü olarak uzun vadeli vizyonunuz ne? Bu otelde kariyer planınız nasıl?', 'LEARNING_AGILITY', 'experience', ['Otel GM yardımcılığı','Departman geliştirme vizyonu','Uzun vadeli bağlılık','İnovasyon'], ['Kısa vadeli bakış','Tükenmişlik','Vizyonsuzluk','Sık değişim'], 4],
    ['Tüm otel departmanlarıyla uyumlu çalışma kültürü oluşturmak için neler yaparsınız?', 'TEAMWORK', 'behavioral', ['Cross-functional koordinasyon','Ortak standartlar','İletişim kanalları','İşbirliği kültürü'], ['Departman çatışması','Siloculuk','İletişim kopukluğu'], 4],
    ['Otelin yenileme veya tadilat döneminde kat hizmetlerini nasıl sürdürürsünüz?', 'ADAPTABILITY', 'situational', ['Esnek planlama','Alternatif düzenleme','Misafir yönetimi','Ekip organizasyonu'], ['Plansızlık','Kalite düşürme','Stres yansıtma','Organizasyon eksikliği'], 4],
    ['Temizlik malzeme maliyetleri yükseldiğinde kaliteyi düşürmeden nasıl optimizasyon yaparsınız?', 'PROBLEM_SOLVING', 'behavioral', ['Maliyet analizi','Alternatif ürün araştırma','Kullanım optimizasyonu','Tedarikçi müzakeresi'], ['Kaliteden kısma','Analiz yapmama','Çözümsüzlük','Maliyeti görmezden gelme'], 4],
    ['Kat hizmetleri yönetimi kariyerinizden en değerli deneyiminiz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Yönetim başarıları','Büyüme motivasyonu','Liderlik vizyonu','Sektör tutkusu'], ['Sık değişim','Olumsuz deneyimler','Kötüleme','Tükenmişlik'], 4],
],

'HOSP_CONCIERGE_AGENT' => [
    ['Misafir şehirde özel bir deneyim yaşamak istediğinde nasıl bir öneri ve organizasyon yaparsınız?', 'CUSTOMER_FOCUS', 'behavioral', ['Kişiselleştirilmiş öneriler','Yerel bilgi','Organizasyon yeteneği','Misafir profilini anlama'], ['Standart öneriler','Bilgi eksikliği','İlgisizlik','Yetersiz organizasyon'], 3],
    ['Ön büro ve diğer departmanlarla misafir talepleri konusunda nasıl koordineli çalışırsınız?', 'COMMUNICATION', 'behavioral', ['Net bilgi aktarımı','Takip sistemi','Proaktif iletişim','Koordinasyon'], ['İletişim kopukluğu','Bilgi kaybı','Koordinasyonsuzluk','Gecikmeli iletişim'], 3],
    ['Misafir taleplerini zamanında ve eksiksiz karşılama konusundaki tutumunuz nedir?', 'RELIABILITY', 'behavioral', ['Takip sistemi','Zamanında teslimat','Söz verilen şeyi yapma','Detay odaklılık'], ['Unutkanlık','Gecikme','Söz tutmama','Dikkatsizlik'], 3],
    ['Concierge olarak kendinizi nasıl geliştirmek istiyorsunuz? Uzun vadeli kariyer hedefiniz ne?', 'LEARNING_AGILITY', 'experience', ['Baş concierge hedefi','Dil öğrenme','Yerel ağ genişletme','Sektör bağlılığı'], ['Kariyer planı yok','Kısa vadeli bakış','Motivasyon eksikliği','Hedefsizlik'], 3],
    ['Otel ekibiyle ve dış tedarikçilerle (restoran, tur, transfer) nasıl bir işbirliği ağı kurarsınız?', 'TEAMWORK', 'behavioral', ['Güçlü ağ','İşbirliği','Bilgi paylaşımı','Karşılıklı fayda'], ['İzole çalışma','Ağ eksikliği','İşbirliği isteksizliği'], 3],
    ['Misafirin acil ve olağandışı bir talebi olduğunda (son dakika uçak bileti, hastane vb.) nasıl organize olursunuz?', 'ADAPTABILITY', 'situational', ['Hızlı çözüm','Kaynak mobilize','Sakinlik','Alternatif sunma'], ['Çaresizlik','Yavaş tepki','Panik','Sorumluluktan kaçınma'], 3],
    ['Misafirin önerdiğiniz restorandan memnun kalmayıp şikayet etmesi durumunda ne yaparsınız?', 'PROBLEM_SOLVING', 'situational', ['Empati','Telafi önerisi','Geri bildirim notu','İyileştirme'], ['Savunmacılık','Suçlama','Görmezden gelme','İlgisizlik'], 3],
    ['Otelcilik deneyimlerinizden en önemli öğreniminiz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Misafir hizmetine tutku','Somut deneyimler','Olumlu motivasyon','Kariyer uyumu'], ['Sık değişim','Negatif tutum','Suçlayıcılık','Gerçekdışı beklentiler'], 3],
],

'HOSP_CONCIERGE_CHIEF' => [
    ['Concierge ekibinizin misafir deneyimi kalitesini nasıl standartlaştırır ve yükseltirsiniz?', 'CUSTOMER_FOCUS', 'behavioral', ['Eğitim programları','Kalite standartları','Misafir geri bildirimi','İnovasyon'], ['Standart eksikliği','Reaktif yaklaşım','Kalite kontrolsüzlüğü'], 3],
    ['Concierge ekibinde farklı dil ve kültürden çalışanları nasıl yönetir ve motive edersiniz?', 'COMMUNICATION', 'behavioral', ['Kapsayıcı liderlik','Bireysel yaklaşım','Motivasyon','Kültürel hassasiyet'], ['Tek tip yaklaşım','İletişim eksikliği','Motivasyonsuz yönetim'], 3],
    ['Ekibin misafir taleplerini zamanında ve doğru şekilde karşılamasını nasıl garanti edersiniz?', 'RELIABILITY', 'behavioral', ['Takip sistemi','Denetim','Eğitim','Tutarlı standartlar'], ['Kontrolsüzlük','Tutarsızlık','Güvenilmezlik','Denetimsizlik'], 3],
    ['Baş Concierge olarak kariyer vizyonunuz ne? Uzun vadede nereye ulaşmak istiyorsunuz?', 'LEARNING_AGILITY', 'experience', ['GM yardımcılığı','Les Clefs d\'Or üyeliği','Uzun vadeli vizyon','Sürekli gelişim'], ['Kariyer durağanlığı','Kısa vadeli bakış','Motivasyon eksikliği'], 3],
    ['Otel yönetimi, ön büro ve satış departmanlarıyla nasıl stratejik işbirliği kurarsınız?', 'TEAMWORK', 'behavioral', ['Stratejik ortaklıklar','Gelir katkısı','Cross-selling','İşbirliği kültürü'], ['Siloculuk','İletişim kopukluğu','Stratejik düşünememe'], 3],
    ['Otelin yüksek sezon döneminde artan talepleri sınırlı ekiple nasıl karşılarsınız?', 'ADAPTABILITY', 'situational', ['Önceliklendirme','Esnek planlama','Teknoloji kullanımı','Ekip motivasyonu'], ['Panik','Kalite düşürme','Organizasyon eksikliği','Stres yansıtma'], 3],
    ['Concierge hizmetlerinde tekrarlayan bir sorun tespit ettiğinizde nasıl yapısal çözüm geliştirirsiniz?', 'PROBLEM_SOLVING', 'behavioral', ['Kök neden analizi','Süreç iyileştirme','Eğitim','Takip sistemi'], ['Yüzeysel çözüm','Suçlama','Analiz yapmama','Reaktif yaklaşım'], 3],
    ['Concierge yönetimi deneyimlerinizden en önemli dersiniz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Liderlik başarıları','Misafir hizmeti tutkusu','Büyüme motivasyonu','Yapıcı bakış'], ['Sık değişim','Negatif tutum','Tükenmişlik','Gerçekdışı beklentiler'], 3],
],

'HOSP_EVENTS_COORDINATOR' => [
    ['Bir düğün veya kurumsal etkinlik organizasyonunda müşteri beklentilerini nasıl yönetirsiniz?', 'CUSTOMER_FOCUS', 'behavioral', ['Detaylı brifing','Beklenti yönetimi','Proaktif iletişim','Kişiselleştirme'], ['Eksik bilgi toplama','İletişim yetersizliği','Beklenti karşılayamama'], 3],
    ['Etkinlik günü mutfak, servis ve teknik ekipler arasındaki koordinasyonu nasıl sağlarsınız?', 'COMMUNICATION', 'behavioral', ['Detaylı planlama','Brifing toplantıları','İletişim kanalları','Anlık koordinasyon'], ['Koordinasyonsuzluk','Bilgi eksikliği','İletişim kopukluğu'], 3],
    ['Etkinlik organizasyonunda zamanlamaya, kontrat şartlarına ve müşteri sözlerine uyum konusundaki tutumunuz nedir?', 'RELIABILITY', 'behavioral', ['Titiz planlama','Söz tutma','Zaman yönetimi','Kontrata bağlılık'], ['Gecikme','Söz tutmama','Detay kaçırma','Sorumsuzluk'], 3],
    ['Etkinlik organizasyonu alanında kariyer hedefiniz ne? Uzun vadeli planınız nasıl?', 'LEARNING_AGILITY', 'experience', ['Etkinlik müdürlüğü','Daha büyük organizasyonlar','Uzmanlık alanı','Uzun vadeli vizyon'], ['Kariyer planı yok','Kısa vadeli bakış','Motivasyon eksikliği','Hedefsizlik'], 3],
    ['Farklı departmanlar ve dış tedarikçilerle etkinlik hazırlığında nasıl ekip çalışması yaparsınız?', 'TEAMWORK', 'behavioral', ['Proje yönetimi','Tedarikçi koordinasyonu','Departman işbirliği','Ortak hedef'], ['Tek başına çalışma','Koordinasyon eksikliği','Tedarikçi yönetimi zafiyeti'], 3],
    ['Etkinlik günü beklenmedik bir sorun (elektrik kesintisi, tedarikçi gelmemesi vb.) yaşandığında ne yaparsınız?', 'ADAPTABILITY', 'situational', ['Plan B','Hızlı alternatif','Sakinlik','Müşteri yönetimi'], ['Panik','Çözümsüzlük','Müşteriyi bilgilendirmeme','Organizasyon çökmesi'], 3],
    ['Etkinlik bütçesini aşmadan müşteri memnuniyetini maksimize etmek için nasıl bir yaklaşım izlersiniz?', 'PROBLEM_SOLVING', 'behavioral', ['Bütçe yönetimi','Yaratıcı çözümler','Tedarikçi müzakeresi','Önceliklendirme'], ['Bütçe aşımı','Kaliteden kısma','Analiz yapmama','Plansız harcama'], 3],
    ['Etkinlik organizasyonu deneyimlerinizden en önemli dersiniz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Organizasyon başarıları','Büyüme motivasyonu','Detay tutkusu','Yapıcı bakış'], ['Sık değişim','Stres dayanıksızlığı','Negatif tutum','Tükenmişlik'], 3],
],

'HOSP_EVENTS_MANAGER' => [
    ['Otelin etkinlik gelirlerini artırmak için hangi stratejileri kullanırsınız?', 'CUSTOMER_FOCUS', 'behavioral', ['Pazar analizi','Satış stratejisi','Müşteri ilişkileri','Paket oluşturma'], ['Strateji eksikliği','Pasif yaklaşım','Pazar bilgisizliği'], 4],
    ['Etkinlik ekibini nasıl yönetir, motive eder ve gelişimlerini desteklersiniz?', 'COMMUNICATION', 'behavioral', ['Koçluk','Düzenli toplantılar','Performans yönetimi','Motivasyon programları'], ['İletişimsizlik','Otoriter yönetim','Motivasyonsuz liderlik'], 4],
    ['Etkinlik operasyonlarında standartları ve kaliteyi nasıl sürdürür ve geliştirirsiniz?', 'RELIABILITY', 'behavioral', ['SOP oluşturma','Kalite denetimi','Eğitim','Tutarlı uygulama'], ['Standart düşmesi','Kontrolsüzlük','Tutarsızlık'], 4],
    ['Etkinlik yönetimi müdürü olarak uzun vadeli vizyonunuz ne?', 'LEARNING_AGILITY', 'experience', ['Otel GM yardımcılığı','Etkinlik departmanı büyütme','Uzun vadeli bağlılık','İnovasyon'], ['Kısa vadeli bakış','Tükenmişlik','Vizyonsuzluk','Sık değişim'], 4],
    ['Satış, pazarlama ve operasyon departmanlarıyla nasıl stratejik işbirliği kurarsınız?', 'TEAMWORK', 'behavioral', ['Cross-functional stratejiler','Gelir ortaklığı','Ortak hedefler','İletişim kanalları'], ['Siloculuk','İletişim kopukluğu','Tek taraflılık'], 4],
    ['Ekonomik daralma döneminde etkinlik taleplerinin düştüğünde departmanı nasıl yönetirsiniz?', 'ADAPTABILITY', 'situational', ['Yeni segmentler','Maliyet optimizasyonu','Yaratıcı paketler','Alternatif gelir'], ['Pasiflik','Çaresizlik','Maliyet kesme refleksi','Stratejik düşünememe'], 4],
    ['Büyük bir kurumsal müşteri son dakika etkinliğini iptal ettiğinde nasıl hareket edersiniz?', 'PROBLEM_SOLVING', 'situational', ['İptal koşulları yönetimi','Alternatif satış','Mali etki azaltma','Müşteri ilişkisi koruma'], ['Panik','Müşteri kaybetme','Hukuki bilgisizlik','Reaktif yaklaşım'], 4],
    ['Etkinlik yönetimi kariyerinizden en değerli deneyiminiz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Stratejik başarılar','Büyüme motivasyonu','Vizyon','Liderlik tutkusu'], ['Sık değişim','Olumsuz deneyimler','Kötüleme','Tükenmişlik'], 4],
],

'HOSP_TRAVEL_AGENT' => [
    ['Müşterinin bütçesine ve tercihlerine uygun tatil planı oluştururken nasıl bir yaklaşım izlersiniz?', 'CUSTOMER_FOCUS', 'behavioral', ['İhtiyaç analizi','Kişiselleştirme','Bütçe yönetimi','Detaylı bilgilendirme'], ['Standart paket satma','Dinlememe','Bütçe uyumsuzluğu','Bilgi eksikliği'], 3],
    ['Tur operatörü veya otel ile müşteri arasında fiyat/hizmet anlaşmazlığı yaşandığında nasıl arabuluculuk yaparsınız?', 'COMMUNICATION', 'situational', ['İki tarafı dinleme','Çözüm odaklılık','Profesyonellik','Uzlaşma'], ['Tek tarafa yakınlık','Çatışma','İletişim kopukluğu','Sorunu büyütme'], 3],
    ['Rezervasyon doğruluğu, zamanında bilgilendirme ve müşteri sözlerine uyum konusundaki tutumunuz nedir?', 'RELIABILITY', 'behavioral', ['Dikkatli kayıt','Zamanında bilgilendirme','Söz tutma','Detay odaklılık'], ['Hatalı rezervasyon','Gecikme','Söz tutmama','Dikkatsizlik'], 3],
    ['Seyahat danışmanlığında kariyer hedefiniz ne? Uzun vadeli planınız nasıl?', 'LEARNING_AGILITY', 'experience', ['Kıdemli danışman hedefi','Destinasyon uzmanlığı','Sertifika','Uzun vadeli plan'], ['Kariyer planı yok','Kısa vadeli bakış','Motivasyon eksikliği','Sektörden soğuma'], 3],
    ['Tur operatörleri, oteller ve havayollarıyla nasıl bir işbirliği ağı kurarsınız?', 'TEAMWORK', 'behavioral', ['Güçlü tedarikçi ağı','Karşılıklı fayda','İletişim','Güven oluşturma'], ['İzole çalışma','Ağ eksikliği','İletişim kopukluğu'], 3],
    ['Müşterinizin seyahati sırasında otel sorunu, uçuş iptali gibi acil durumlar yaşandığında ne yaparsınız?', 'ADAPTABILITY', 'situational', ['Hızlı müdahale','Alternatif çözüm','7/24 erişilebilirlik','Sakinlik'], ['Ulaşılamama','Çaresizlik','Yavaş tepki','Sorumluluktan kaçınma'], 3],
    ['Müşteri tatilden memnun kalmayıp şikayet ettiğinde nasıl hareket edersiniz?', 'PROBLEM_SOLVING', 'situational', ['Empati','Sorun tespiti','Telafi önerisi','İyileştirme notları'], ['Savunmacılık','Suçlama','Görmezden gelme','İlgisizlik'], 3],
    ['Seyahat sektöründeki deneyimlerinizden en önemli öğreniminiz ne? Bu pozisyona neden başvuruyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Seyahat tutkusu','Müşteri hizmeti deneyimi','Olumlu motivasyon','Kariyer uyumu'], ['Sık değişim','Negatif tutum','Suçlayıcılık','Gerçekdışı beklentiler'], 3],
],

'HOSP_TRAVEL_MANAGER' => [
    ['Seyahat departmanının müşteri memnuniyetini ve satış performansını nasıl yönetirsiniz?', 'CUSTOMER_FOCUS', 'behavioral', ['KPI takibi','Müşteri analizi','Strateji oluşturma','Ekip eğitimi'], ['Ölçümsüzlük','Strateji eksikliği','Müşteriden kopukluk'], 4],
    ['Seyahat danışmanları ekibini nasıl yönetir ve gelişimlerini desteklersiniz?', 'COMMUNICATION', 'behavioral', ['Koçluk','Düzenli toplantılar','Performans yönetimi','Motivasyon'], ['İletişimsizlik','Otoriter yönetim','Motivasyonsuz liderlik'], 4],
    ['Operasyonel standartları, rezervasyon doğruluğunu ve müşteri hizmet kalitesini nasıl garanti edersiniz?', 'RELIABILITY', 'behavioral', ['Denetim sistemi','Eğitim','Kalite kontrol','Tutarlı standartlar'], ['Kontrolsüzlük','Tutarsızlık','Kalite düşüşü','Denetimsizlik'], 4],
    ['Tur operasyonları yönetiminde uzun vadeli vizyonunuz ne?', 'LEARNING_AGILITY', 'experience', ['Departman büyütme','Yeni destinasyonlar','Uzun vadeli bağlılık','Stratejik büyüme'], ['Kısa vadeli bakış','Tükenmişlik','Vizyonsuzluk','Motivasyon eksikliği'], 4],
    ['Satış, pazarlama ve operasyon ekipleriyle nasıl stratejik işbirliği yaparsınız?', 'TEAMWORK', 'behavioral', ['Cross-functional stratejiler','Ortak kampanyalar','İletişim kanalları','Sinerji'], ['Siloculuk','İletişim kopukluğu','Tek taraflılık'], 4],
    ['Seyahat sektöründe kriz (pandemi, doğal afet) yaşandığında departmanı nasıl yönetirsiniz?', 'ADAPTABILITY', 'situational', ['Kriz yönetimi','Müşteri koruma','Alternatif planlar','Ekip morali'], ['Çaresizlik','Plansızlık','Müşteri iletişimi kesme','Liderlik eksikliği'], 4],
    ['Departman cirosunu artırmak için hangi stratejik aksiyonları alırsınız?', 'PROBLEM_SOLVING', 'behavioral', ['Pazar analizi','Yeni segmentler','Dijital pazarlama','Tedarikçi müzakeresi'], ['Pasiflik','Strateji eksikliği','Analiz yapmama','Maliyet kesme refleksi'], 4],
    ['Seyahat yönetimi kariyerinizden en değerli deneyiminiz ne? Bu pozisyona neden ilgi duyuyorsunuz?', 'ATTENTION_TO_DETAIL', 'experience', ['Stratejik başarılar','Seyahat tutkusu','Büyüme motivasyonu','Liderlik vizyonu'], ['Sık değişim','Olumsuz deneyimler','Kötüleme','Tükenmişlik'], 4],
],

];
