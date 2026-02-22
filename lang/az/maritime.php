<?php

return [
    // --- Scope A: Apply flow ---

    // Validation messages
    'validation.first_name_required' => 'Ad sahəsi tələb olunur.',
    'validation.last_name_required' => 'Soyad sahəsi tələb olunur.',
    'validation.email_required' => 'E-poçt ünvanı tələb olunur.',
    'validation.email_invalid' => 'Düzgün e-poçt ünvanı daxil edin.',
    'validation.phone_required' => 'Dənizçi namizədlər üçün telefon nömrəsi tələb olunur.',
    'validation.country_required' => 'Ölkə kodu tələb olunur.',
    'validation.english_level_required' => 'İngilis dili səviyyənizi seçin.',
    'validation.english_level_invalid' => 'Yanlış ingilis dili səviyyəsi. Seçimlər: A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Dənizçi rütbənizi seçin.',
    'validation.rank_invalid' => 'Yanlış rütbə seçildi.',
    'validation.source_required' => 'Mənbə kanalı tələb olunur.',
    'validation.source_invalid' => 'Yanlış mənbə kanalı.',
    'validation.privacy_required' => 'Məxfilik siyasətini qəbul etməlisiniz.',
    'validation.data_processing_required' => 'Məlumatların emalına razılıq verməlisiniz.',
    'validation.failed' => 'Doğrulama uğursuz oldu',

    // Response messages
    'response.registration_success' => 'Qeydiyyat uğurla tamamlandı. Xoş gəldiniz!',
    'response.welcome_back' => 'Xoş gəldiniz! Namizəd profili tapıldı.',
    'response.already_hired' => 'Bu namizəd artıq işə qəbul edilib.',
    'response.candidate_not_found' => 'Namizəd tapılmadı.',
    'response.maritime_only' => 'Bu son nöqtə yalnız dənizçilik namizədləri üçündür.',
    'response.interview_active' => 'Namizədin aktiv müsahibəsi var.',
    'response.interview_started' => 'Müsahibə uğurla başladı.',
    'response.cannot_start_interview' => 'Bu statuslu namizəd üçün müsahibə başladıla bilməz: :status',
    'response.english_submitted' => 'İngilis dili qiymətləndirməsi uğurla göndərildi.',
    'response.video_submitted' => 'Video uğurla göndərildi.',
    'response.no_completed_interview' => 'Tamamlanmış müsahibə tapılmadı. Əvvəlcə müsahibəni tamamlayın.',

    // Status labels
    'status.new' => 'Qeydiyyatdan keçib',
    'status.assessed' => 'Qiymətləndirmə tamamlandı',
    'status.in_pool' => 'İstedadlar hovuzunda',
    'status.presented' => 'Şirkətlərə təqdim edilib',
    'status.hired' => 'İşə qəbul edilib',
    'status.archived' => 'Arxivləşdirilib',
    'status.unknown' => 'Naməlum',

    // Next steps
    'next_step.start_interview' => 'Qiymətləndirmə müsahibənizi başladın',
    'next_step.continue_interview' => 'Müsahibənizə davam edin',
    'next_step.complete_interview' => 'Müsahibənizi tamamlayın',
    'next_step.complete_english' => 'İngilis dili qiymətləndirməsini tamamlayın',
    'next_step.submit_video' => 'Təqdimat videonuzu göndərin',
    'next_step.profile_complete' => 'Profiliniz tamamdır — imkanlarla bağlı sizinlə əlaqə saxlayacağıq',

    // Ranks (18 keys)
    'rank.captain' => 'Kapitan',
    'rank.chief_officer' => 'Baş zabit',
    'rank.second_officer' => 'İkinci zabit',
    'rank.third_officer' => 'Üçüncü zabit',
    'rank.bosun' => 'Bosman',
    'rank.able_seaman' => 'Bacarıqlı dənizçi (AB)',
    'rank.ordinary_seaman' => 'Adi dənizçi (OS)',
    'rank.chief_engineer' => 'Baş mühəndis',
    'rank.second_engineer' => 'İkinci mühəndis',
    'rank.third_engineer' => 'Üçüncü mühəndis',
    'rank.motorman' => 'Motorçu',
    'rank.oiler' => 'Yağçı',
    'rank.electrician' => 'Elektrik / ETO',
    'rank.cook' => 'Aşpaz',
    'rank.steward' => 'Stüard',
    'rank.messman' => 'Ofisiant',
    'rank.deck_cadet' => 'Göyərtə kurssantı',
    'rank.engine_cadet' => 'Maşın kurssantı',

    // Departments (4 keys)
    'department.deck' => 'Göyərtə',
    'department.engine' => 'Maşın',
    'department.galley' => 'Mətbəx',
    'department.cadet' => 'Kurssant',

    // Certificates (13 keys)
    'cert.stcw' => 'STCW Əsas Təhlükəsizlik',
    'cert.coc' => 'Səriştəlilik Sertifikatı (CoC)',
    'cert.goc' => 'Ümumi Operator Sertifikatı (GOC)',
    'cert.ecdis' => 'ECDIS',
    'cert.arpa' => 'ARPA',
    'cert.brm' => 'Körpü Resurs İdarəetməsi (BRM)',
    'cert.erm' => 'Maşın Resurs İdarəetməsi (ERM)',
    'cert.hazmat' => 'Təhlükəli materiallar',
    'cert.medical' => 'Tibbi sertifikat',
    'cert.passport' => 'Pasport',
    'cert.seamans_book' => 'Dənizçi kitabçası',
    'cert.flag_endorsement' => 'Bayraq dövləti təsdiqi',
    'cert.tanker_endorsement' => 'Tanker təsdiqi',

    // English levels (6 keys)
    'english.a1' => 'A1 – Başlanğıc',
    'english.a2' => 'A2 – Elementar',
    'english.b1' => 'B1 – Orta',
    'english.b2' => 'B2 – Orta yuxarı',
    'english.c1' => 'C1 – İrəliləmiş',
    'english.c2' => 'C2 – Sərbəst',

    // Source channels (9 keys)
    'source.maritime_event' => 'Dənizçilik tədbirləri',
    'source.maritime_fair' => 'Dənizçilik sərgisi',
    'source.linkedin' => 'LinkedIn',
    'source.referral' => 'Tövsiyə',
    'source.job_board' => 'İş elanları lövhəsi',
    'source.organic' => 'Orqanik axtarış',
    'source.crewing_agency' => 'Ekipaj agentliyi',
    'source.maritime_school' => 'Dənizçilik məktəbi',
    'source.seafarer_union' => 'Dənizçilər ittifaqı',

    // --- Scope B: Interview ---
    'interview.status.draft' => 'Qaralama',
    'interview.status.in_progress' => 'Davam edir',
    'interview.status.completed' => 'Tamamlandı',
    'interview.status.cancelled' => 'Ləğv edildi',

    // --- Scope C: Decision ---
    'decision.hire' => 'İşə götür',
    'decision.review' => 'Nəzərdən keçir',
    'decision.reject' => 'Rədd et',

    'category.core_duty' => 'Əsas vəzifə',
    'category.risk_safety' => 'Risk və təhlükəsizlik',
    'category.procedure_discipline' => 'Prosedur intizamı',
    'category.communication_judgment' => 'Ünsiyyət və mühakimə',

    'concern.critical_risk' => 'kritik risk işarələri',
    'concern.major_risk' => 'əsas risk narahatlıqları',
    'concern.expired_cert' => 'vaxtı keçmiş sertifikat',
    'concern.unverified_cert' => 'təsdiqlənməmiş sertifikat',

    'explanation.recommendation' => ':decision tövsiyəsi (bal: :score/100, etibarlılıq: :confidence%).',
    'explanation.strengths' => 'Güclü tərəflər: :strengths.',
    'explanation.concerns' => 'Narahatlıqlar: :concerns.',

    // --- Scope D: Company Dashboard ---
    'qualification.stcw' => 'STCW',
    'qualification.coc' => 'COC',
    'qualification.goc' => 'GOC',
    'qualification.ecdis' => 'ECDIS',
    'qualification.brm' => 'BRM',
    'qualification.arpa' => 'ARPA',
    'qualification.passport' => 'Pasport',
    'qualification.seamans_book' => 'Dənizçi kitabçası',
    'qualification.medical' => 'Tibbi',

    // Behavioral Interview
    'behavioral.title' => 'Davranış Qiymətləndirməsi',
    'behavioral.subtitle' => 'Xahiş edirik suallara real təcrübənizə əsaslanaraq cavab verin.',
    'behavioral.category.discipline_procedure' => 'İntizam və Prosedur',
    'behavioral.category.stress_crisis' => 'Stress və Böhran İdarəetməsi',
    'behavioral.category.team_compatibility' => 'Komanda Uyğunluğu',
    'behavioral.category.leadership_responsibility' => 'Liderlik və Məsuliyyət',
    'behavioral.submit' => 'Qiymətləndirməni Göndər',
    'behavioral.saved' => 'Cavab saxlanıldı',
    'behavioral.complete' => 'Qiymətləndirmə uğurla göndərildi',
    'behavioral.progress' => ':completed / :total sual cavablandı',
];
