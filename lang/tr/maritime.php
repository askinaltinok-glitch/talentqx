<?php

return [
    // --- Scope A: Apply flow ---

    // Validation messages
    'validation.first_name_required' => 'Ad alanı zorunludur.',
    'validation.last_name_required' => 'Soyad alanı zorunludur.',
    'validation.email_required' => 'E-posta adresi zorunludur.',
    'validation.email_invalid' => 'Lütfen geçerli bir e-posta adresi giriniz.',
    'validation.phone_required' => 'Denizci adayları için telefon numarası zorunludur.',
    'validation.country_required' => 'Ülke kodu zorunludur.',
    'validation.english_level_required' => 'Lütfen İngilizce seviyenizi seçiniz.',
    'validation.english_level_invalid' => 'Geçersiz İngilizce seviyesi. Seçenekler: A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Lütfen denizci rütbenizi seçiniz.',
    'validation.rank_invalid' => 'Geçersiz rütbe seçimi.',
    'validation.source_required' => 'Kaynak kanalı zorunludur.',
    'validation.source_invalid' => 'Geçersiz kaynak kanalı.',
    'validation.privacy_required' => 'Gizlilik politikasını kabul etmelisiniz.',
    'validation.data_processing_required' => 'Veri işleme iznini onaylamalısınız.',
    'validation.failed' => 'Doğrulama başarısız',

    // Response messages
    'response.registration_success' => 'Kayıt başarılı. Aramıza hoş geldiniz!',
    'response.welcome_back' => 'Tekrar hoş geldiniz! Aday profiliniz bulundu.',
    'response.already_hired' => 'Bu aday zaten işe alınmış.',
    'response.candidate_not_found' => 'Aday bulunamadı.',
    'response.maritime_only' => 'Bu uç nokta yalnızca denizcilik adayları içindir.',
    'response.interview_active' => 'Adayın aktif bir mülakatı var.',
    'response.interview_started' => 'Mülakat başarıyla başlatıldı.',
    'response.cannot_start_interview' => 'Bu durumdaki aday için mülakat başlatılamaz: :status',
    'response.english_submitted' => 'İngilizce değerlendirmesi başarıyla gönderildi.',
    'response.video_submitted' => 'Video başarıyla gönderildi.',
    'response.no_completed_interview' => 'Tamamlanmış mülakat bulunamadı. Önce mülakatı tamamlayın.',

    // Status labels
    'status.new' => 'Kayıtlı',
    'status.assessed' => 'Değerlendirme Tamamlandı',
    'status.in_pool' => 'Yetenek Havuzunda',
    'status.presented' => 'Şirketlere Sunuldu',
    'status.hired' => 'İşe Alındı',
    'status.archived' => 'Arşivlendi',
    'status.unknown' => 'Bilinmiyor',

    // Next steps
    'next_step.start_interview' => 'Değerlendirme mülakatınızı başlatın',
    'next_step.continue_interview' => 'Mülakatınıza devam edin',
    'next_step.complete_interview' => 'Mülakatınızı tamamlayın',
    'next_step.complete_english' => 'İngilizce değerlendirmesini tamamlayın',
    'next_step.submit_video' => 'Tanıtım videonuzu gönderin',
    'next_step.profile_complete' => 'Profiliniz tamamlandı - fırsatlarla ilgili sizinle iletişime geçeceğiz',

    // Ranks (18 keys)
    'rank.captain' => 'Kaptan',
    'rank.chief_officer' => 'Birinci Zabit',
    'rank.second_officer' => 'İkinci Zabit',
    'rank.third_officer' => 'Üçüncü Zabit',
    'rank.bosun' => 'Lostromo',
    'rank.able_seaman' => 'Usta Gemici (AB)',
    'rank.ordinary_seaman' => 'Acemi Gemici (OS)',
    'rank.chief_engineer' => 'Başmühendis',
    'rank.second_engineer' => 'İkinci Mühendis',
    'rank.third_engineer' => 'Üçüncü Mühendis',
    'rank.motorman' => 'Motorcu',
    'rank.oiler' => 'Yağcı',
    'rank.electrician' => 'Elektrikçi / ETO',
    'rank.cook' => 'Aşçı',
    'rank.steward' => 'Kamarot',
    'rank.messman' => 'Kamarot Yardımcısı',
    'rank.deck_cadet' => 'Güverte Stajyeri',
    'rank.engine_cadet' => 'Makine Stajyeri',

    // Departments (4 keys)
    'department.deck' => 'Güverte',
    'department.engine' => 'Makine',
    'department.galley' => 'Mutfak',
    'department.cadet' => 'Stajyer',

    // Certificates (8+ keys)
    'cert.stcw' => 'STCW Temel Güvenlik',
    'cert.coc' => 'Yeterlik Belgesi (CoC)',
    'cert.goc' => 'Genel Operatör Sertifikası (GOC)',
    'cert.ecdis' => 'ECDIS',
    'cert.arpa' => 'ARPA',
    'cert.brm' => 'Köprü Kaynak Yönetimi (BRM)',
    'cert.erm' => 'Makine Kaynak Yönetimi (ERM)',
    'cert.hazmat' => 'Tehlikeli Maddeler',
    'cert.medical' => 'Sağlık Raporu',
    'cert.passport' => 'Pasaport',
    'cert.seamans_book' => 'Gemiadamı Cüzdanı',
    'cert.flag_endorsement' => 'Bayrak Devleti Onayı',
    'cert.tanker_endorsement' => 'Tanker Onayı',

    // English levels (6 keys)
    'english.a1' => 'A1 – Başlangıç',
    'english.a2' => 'A2 – Temel',
    'english.b1' => 'B1 – Orta',
    'english.b2' => 'B2 – Orta Üstü',
    'english.c1' => 'C1 – İleri',
    'english.c2' => 'C2 – Uzman',

    // Source channels (9 keys)
    'source.maritime_event' => 'Denizcilik Etkinliği',
    'source.maritime_fair' => 'Denizcilik Fuarı',
    'source.linkedin' => 'LinkedIn',
    'source.referral' => 'Referans',
    'source.job_board' => 'İş İlanı Sitesi',
    'source.organic' => 'Doğrudan Arama',
    'source.crewing_agency' => 'Mürettebat Ajansı',
    'source.maritime_school' => 'Denizcilik Okulu',
    'source.seafarer_union' => 'Denizci Sendikası',

    // --- Scope B: Interview ---
    'interview.status.draft' => 'Taslak',
    'interview.status.in_progress' => 'Devam Ediyor',
    'interview.status.completed' => 'Tamamlandı',
    'interview.status.cancelled' => 'İptal Edildi',

    // --- Scope C: Decision ---
    'decision.hire' => 'İşe Al',
    'decision.review' => 'İncele',
    'decision.reject' => 'Reddet',

    'category.core_duty' => 'Temel Görev',
    'category.risk_safety' => 'Risk ve Güvenlik',
    'category.procedure_discipline' => 'Prosedür Disiplini',
    'category.communication_judgment' => 'İletişim ve Karar Verme',

    'concern.critical_risk' => 'kritik risk işaretleri',
    'concern.major_risk' => 'büyük risk endişeleri',
    'concern.expired_cert' => 'süresi dolmuş sertifika',
    'concern.unverified_cert' => 'doğrulanmamış sertifika',

    'explanation.recommendation' => ':decision tavsiyesi (puan: :score/100, güven: %:confidence).',
    'explanation.strengths' => 'Güçlü yönler: :strengths.',
    'explanation.concerns' => 'Endişeler: :concerns.',

    // --- Scope D: Company Dashboard ---
    'qualification.stcw' => 'STCW',
    'qualification.coc' => 'COC',
    'qualification.goc' => 'GOC',
    'qualification.ecdis' => 'ECDIS',
    'qualification.brm' => 'BRM',
    'qualification.arpa' => 'ARPA',
    'qualification.passport' => 'Pasaport',
    'qualification.seamans_book' => 'Gemiadamı Cüzdanı',
    'qualification.medical' => 'Sağlık',

    // Behavioral Interview
    'behavioral.title' => 'Davranışsal Değerlendirme',
    'behavioral.subtitle' => 'Lütfen aşağıdaki soruları gerçek deneyimlerinize dayanarak dürüstçe cevaplayın.',
    'behavioral.category.discipline_procedure' => 'Disiplin ve Prosedür',
    'behavioral.category.stress_crisis' => 'Stres ve Kriz Yönetimi',
    'behavioral.category.team_compatibility' => 'Ekip Uyumu',
    'behavioral.category.leadership_responsibility' => 'Liderlik ve Sorumluluk',
    'behavioral.submit' => 'Değerlendirmeyi Gönder',
    'behavioral.saved' => 'Cevap kaydedildi',
    'behavioral.complete' => 'Değerlendirme başarıyla gönderildi',
    'behavioral.progress' => ':completed / :total soru cevaplandı',
];
