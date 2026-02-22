<?php

// --- Translation coverage ---
// TRANSLATED: validation.*, response.*, status.*, next_step.*, explanation.*, concern.*, decision.*, category.*
// ENGLISH FALLBACK: rank.*, department.*, cert.*, english.*, source.*, interview.*, qualification.*

return [
    // --- Scope A: Apply flow ---

    // Validation messages — TRANSLATED
    'validation.first_name_required' => "Ім'я є обов'язковим.",
    'validation.last_name_required' => "Прізвище є обов'язковим.",
    'validation.email_required' => "Електронна адреса є обов'язковою.",
    'validation.email_invalid' => 'Вкажіть дійсну електронну адресу.',
    'validation.phone_required' => "Номер телефону є обов'язковим для морських кандидатів.",
    'validation.country_required' => "Код країни є обов'язковим.",
    'validation.english_level_required' => 'Оберіть рівень англійської мови.',
    'validation.english_level_invalid' => 'Недійсний рівень англійської. Варіанти: A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Оберіть посаду моряка.',
    'validation.rank_invalid' => 'Обрано недійсну посаду.',
    'validation.source_required' => "Канал джерела є обов'язковим.",
    'validation.source_invalid' => 'Недійсний канал джерела.',
    'validation.privacy_required' => 'Ви повинні прийняти політику конфіденційності.',
    'validation.data_processing_required' => 'Ви повинні надати згоду на обробку даних.',
    'validation.failed' => 'Помилка валідації',

    // Response messages — TRANSLATED
    'response.registration_success' => 'Реєстрація успішна. Ласкаво просимо!',
    'response.welcome_back' => 'З поверненням! Профіль кандидата знайдено.',
    'response.already_hired' => 'Цього кандидата вже прийнято на роботу.',
    'response.candidate_not_found' => 'Кандидата не знайдено.',
    'response.maritime_only' => 'Цей ресурс лише для морських кандидатів.',
    'response.interview_active' => "Кандидат має активне співбесіду.",
    'response.interview_started' => 'Співбесіду успішно розпочато.',
    'response.cannot_start_interview' => 'Неможливо розпочати співбесіду для кандидата зі статусом: :status',
    'response.english_submitted' => 'Оцінку англійської мови успішно надіслано.',
    'response.video_submitted' => 'Відео успішно надіслано.',
    'response.no_completed_interview' => 'Завершену співбесіду не знайдено. Спочатку завершіть співбесіду.',

    // Status labels — TRANSLATED
    'status.new' => 'Зареєстровано',
    'status.assessed' => 'Оцінку завершено',
    'status.in_pool' => 'У кадровому резерві',
    'status.presented' => 'Представлено компаніям',
    'status.hired' => 'Прийнято на роботу',
    'status.archived' => 'В архіві',
    'status.unknown' => 'Невідомо',

    // Next steps — TRANSLATED
    'next_step.start_interview' => 'Розпочніть оціночну співбесіду',
    'next_step.continue_interview' => 'Продовжіть співбесіду',
    'next_step.complete_interview' => 'Завершіть співбесіду',
    'next_step.complete_english' => 'Пройдіть оцінку англійської мови',
    'next_step.submit_video' => 'Надішліть відео-представлення',
    'next_step.profile_complete' => "Ваш профіль заповнено — ми зв'яжемося з вами щодо можливостей",

    // Ranks — ENGLISH FALLBACK
    'rank.captain' => 'Captain / Master',
    'rank.chief_officer' => 'Chief Officer',
    'rank.second_officer' => 'Second Officer',
    'rank.third_officer' => 'Third Officer',
    'rank.bosun' => 'Bosun',
    'rank.able_seaman' => 'Able Seaman (AB)',
    'rank.ordinary_seaman' => 'Ordinary Seaman (OS)',
    'rank.chief_engineer' => 'Chief Engineer',
    'rank.second_engineer' => 'Second Engineer',
    'rank.third_engineer' => 'Third Engineer',
    'rank.motorman' => 'Motorman',
    'rank.oiler' => 'Oiler',
    'rank.electrician' => 'Electrician / ETO',
    'rank.cook' => 'Cook',
    'rank.steward' => 'Steward',
    'rank.messman' => 'Messman',
    'rank.deck_cadet' => 'Deck Cadet',
    'rank.engine_cadet' => 'Engine Cadet',

    // Departments — ENGLISH FALLBACK
    'department.deck' => 'Deck',
    'department.engine' => 'Engine',
    'department.galley' => 'Galley',
    'department.cadet' => 'Cadet',

    // Certificates — ENGLISH FALLBACK
    'cert.stcw' => 'STCW Basic Safety',
    'cert.coc' => 'Certificate of Competency (CoC)',
    'cert.goc' => 'General Operator Certificate (GOC)',
    'cert.ecdis' => 'ECDIS',
    'cert.arpa' => 'ARPA',
    'cert.brm' => 'Bridge Resource Management (BRM)',
    'cert.erm' => 'Engine Resource Management (ERM)',
    'cert.hazmat' => 'Hazardous Materials',
    'cert.medical' => 'Medical Certificate',
    'cert.passport' => 'Passport',
    'cert.seamans_book' => "Seaman's Book",
    'cert.flag_endorsement' => 'Flag State Endorsement',
    'cert.tanker_endorsement' => 'Tanker Endorsement',

    // English levels — ENGLISH FALLBACK
    'english.a1' => 'A1 – Beginner',
    'english.a2' => 'A2 – Elementary',
    'english.b1' => 'B1 – Intermediate',
    'english.b2' => 'B2 – Upper Intermediate',
    'english.c1' => 'C1 – Advanced',
    'english.c2' => 'C2 – Proficient',

    // Source channels — ENGLISH FALLBACK
    'source.maritime_event' => 'Maritime Event',
    'source.maritime_fair' => 'Maritime Fair',
    'source.linkedin' => 'LinkedIn',
    'source.referral' => 'Referral',
    'source.job_board' => 'Job Board',
    'source.organic' => 'Organic Search',
    'source.crewing_agency' => 'Crewing Agency',
    'source.maritime_school' => 'Maritime School',
    'source.seafarer_union' => 'Seafarer Union',

    // --- Scope B: Interview --- ENGLISH FALLBACK
    'interview.status.draft' => 'Draft',
    'interview.status.in_progress' => 'In Progress',
    'interview.status.completed' => 'Completed',
    'interview.status.cancelled' => 'Cancelled',

    // --- Scope C: Decision --- TRANSLATED
    'decision.hire' => 'Прийняти',
    'decision.review' => 'На розгляд',
    'decision.reject' => 'Відхилити',

    'category.core_duty' => 'Основні обов\'язки',
    'category.risk_safety' => 'Ризик та безпека',
    'category.procedure_discipline' => 'Процедурна дисципліна',
    'category.communication_judgment' => 'Комунікація та судження',

    'concern.critical_risk' => 'критичні фактори ризику',
    'concern.major_risk' => 'серйозні занепокоєння',
    'concern.expired_cert' => 'прострочений сертифікат',
    'concern.unverified_cert' => 'непідтверджений сертифікат',

    'explanation.recommendation' => 'Рекомендація :decision (бал: :score/100, впевненість: :confidence%).',
    'explanation.strengths' => 'Сильні сторони: :strengths.',
    'explanation.concerns' => 'Занепокоєння: :concerns.',

    // --- Scope D: Company Dashboard --- ENGLISH FALLBACK
    'qualification.stcw' => 'STCW',
    'qualification.coc' => 'COC',
    'qualification.goc' => 'GOC',
    'qualification.ecdis' => 'ECDIS',
    'qualification.brm' => 'BRM',
    'qualification.arpa' => 'ARPA',
    'qualification.passport' => 'Passport',
    'qualification.seamans_book' => "Seaman's Book",
    'qualification.medical' => 'Medical',

    // Behavioral Interview
    'behavioral.title' => 'Поведінкова оцінка',
    'behavioral.subtitle' => 'Будь ласка, відповідайте на питання чесно, ґрунтуючись на вашому реальному досвіді.',
    'behavioral.category.discipline_procedure' => 'Дисципліна та процедури',
    'behavioral.category.stress_crisis' => 'Стрес та кризове управління',
    'behavioral.category.team_compatibility' => 'Сумісність з командою',
    'behavioral.category.leadership_responsibility' => 'Лідерство та відповідальність',
    'behavioral.submit' => 'Надіслати оцінку',
    'behavioral.saved' => 'Відповідь збережена',
    'behavioral.complete' => 'Оцінку успішно надіслано',
    'behavioral.progress' => ':completed з :total питань відповідено',
];
