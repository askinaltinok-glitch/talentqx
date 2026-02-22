<?php

return [
    // --- Scope A: Apply flow ---

    // Validation messages
    'validation.first_name_required' => 'Имя обязательно для заполнения.',
    'validation.last_name_required' => 'Фамилия обязательна для заполнения.',
    'validation.email_required' => 'Электронная почта обязательна.',
    'validation.email_invalid' => 'Пожалуйста, укажите действительный адрес электронной почты.',
    'validation.phone_required' => 'Номер телефона обязателен для морских кандидатов.',
    'validation.country_required' => 'Код страны обязателен.',
    'validation.english_level_required' => 'Пожалуйста, выберите уровень английского языка.',
    'validation.english_level_invalid' => 'Недопустимый уровень английского. Варианты: A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Пожалуйста, выберите вашу должность моряка.',
    'validation.rank_invalid' => 'Выбрана недопустимая должность.',
    'validation.source_required' => 'Источник обязателен.',
    'validation.source_invalid' => 'Недопустимый источник.',
    'validation.privacy_required' => 'Вы должны принять политику конфиденциальности.',
    'validation.data_processing_required' => 'Вы должны дать согласие на обработку данных.',
    'validation.failed' => 'Ошибка валидации',

    // Response messages
    'response.registration_success' => 'Регистрация успешна. Добро пожаловать на борт!',
    'response.welcome_back' => 'С возвращением! Профиль кандидата найден.',
    'response.already_hired' => 'Этот кандидат уже принят на работу.',
    'response.candidate_not_found' => 'Кандидат не найден.',
    'response.maritime_only' => 'Этот ресурс только для морских кандидатов.',
    'response.interview_active' => 'У кандидата есть активное собеседование.',
    'response.interview_started' => 'Собеседование успешно начато.',
    'response.cannot_start_interview' => 'Невозможно начать собеседование для кандидата со статусом: :status',
    'response.english_submitted' => 'Оценка английского языка успешно отправлена.',
    'response.video_submitted' => 'Видео успешно отправлено.',
    'response.no_completed_interview' => 'Завершённое собеседование не найдено. Сначала завершите собеседование.',

    // Status labels
    'status.new' => 'Зарегистрирован',
    'status.assessed' => 'Оценка завершена',
    'status.in_pool' => 'В кадровом резерве',
    'status.presented' => 'Представлен компаниям',
    'status.hired' => 'Принят на работу',
    'status.archived' => 'В архиве',
    'status.unknown' => 'Неизвестно',

    // Next steps
    'next_step.start_interview' => 'Начните оценочное собеседование',
    'next_step.continue_interview' => 'Продолжите собеседование',
    'next_step.complete_interview' => 'Завершите собеседование',
    'next_step.complete_english' => 'Пройдите оценку английского языка',
    'next_step.submit_video' => 'Отправьте видео-представление',
    'next_step.profile_complete' => 'Ваш профиль заполнен — мы свяжемся с вами при появлении вакансий',

    // Ranks (18 keys)
    'rank.captain' => 'Капитан',
    'rank.chief_officer' => 'Старший помощник',
    'rank.second_officer' => 'Второй помощник',
    'rank.third_officer' => 'Третий помощник',
    'rank.bosun' => 'Боцман',
    'rank.able_seaman' => 'Матрос 1 класса (AB)',
    'rank.ordinary_seaman' => 'Матрос 2 класса (OS)',
    'rank.chief_engineer' => 'Старший механик',
    'rank.second_engineer' => 'Второй механик',
    'rank.third_engineer' => 'Третий механик',
    'rank.motorman' => 'Моторист',
    'rank.oiler' => 'Машинист-смазчик',
    'rank.electrician' => 'Электромеханик / ETO',
    'rank.cook' => 'Повар',
    'rank.steward' => 'Стюард',
    'rank.messman' => 'Официант',
    'rank.deck_cadet' => 'Курсант палубной службы',
    'rank.engine_cadet' => 'Курсант машинного отделения',

    // Departments (4 keys)
    'department.deck' => 'Палубная служба',
    'department.engine' => 'Машинное отделение',
    'department.galley' => 'Камбуз',
    'department.cadet' => 'Курсант',

    // Certificates (8+ keys)
    'cert.stcw' => 'ПДНВ базовая безопасность',
    'cert.coc' => 'Диплом о компетентности (CoC)',
    'cert.goc' => 'Общий сертификат оператора (GOC)',
    'cert.ecdis' => 'ECDIS',
    'cert.arpa' => 'ARPA',
    'cert.brm' => 'Управление ресурсами мостика (BRM)',
    'cert.erm' => 'Управление ресурсами машинного отделения (ERM)',
    'cert.hazmat' => 'Опасные грузы',
    'cert.medical' => 'Медицинское свидетельство',
    'cert.passport' => 'Паспорт',
    'cert.seamans_book' => 'Паспорт моряка',
    'cert.flag_endorsement' => 'Подтверждение флага',
    'cert.tanker_endorsement' => 'Танкерное подтверждение',

    // English levels (6 keys)
    'english.a1' => 'A1 – Начальный',
    'english.a2' => 'A2 – Элементарный',
    'english.b1' => 'B1 – Средний',
    'english.b2' => 'B2 – Выше среднего',
    'english.c1' => 'C1 – Продвинутый',
    'english.c2' => 'C2 – Свободное владение',

    // Source channels (9 keys)
    'source.maritime_event' => 'Морское мероприятие',
    'source.maritime_fair' => 'Морская выставка',
    'source.linkedin' => 'LinkedIn',
    'source.referral' => 'Рекомендация',
    'source.job_board' => 'Доска объявлений',
    'source.organic' => 'Органический поиск',
    'source.crewing_agency' => 'Крюинговое агентство',
    'source.maritime_school' => 'Морское учебное заведение',
    'source.seafarer_union' => 'Профсоюз моряков',

    // --- Scope B: Interview ---
    'interview.status.draft' => 'Черновик',
    'interview.status.in_progress' => 'В процессе',
    'interview.status.completed' => 'Завершено',
    'interview.status.cancelled' => 'Отменено',

    // --- Scope C: Decision ---
    'decision.hire' => 'Принять',
    'decision.review' => 'На рассмотрение',
    'decision.reject' => 'Отклонить',

    'category.core_duty' => 'Основные обязанности',
    'category.risk_safety' => 'Риск и безопасность',
    'category.procedure_discipline' => 'Процедурная дисциплина',
    'category.communication_judgment' => 'Коммуникация и суждение',

    'concern.critical_risk' => 'критические факторы риска',
    'concern.major_risk' => 'серьёзные опасения',
    'concern.expired_cert' => 'просроченный сертификат',
    'concern.unverified_cert' => 'неподтверждённый сертификат',

    'explanation.recommendation' => 'Рекомендация :decision (балл: :score/100, уверенность: :confidence%).',
    'explanation.strengths' => 'Сильные стороны: :strengths.',
    'explanation.concerns' => 'Опасения: :concerns.',

    // --- Scope D: Company Dashboard ---
    'qualification.stcw' => 'ПДНВ',
    'qualification.coc' => 'COC',
    'qualification.goc' => 'GOC',
    'qualification.ecdis' => 'ECDIS',
    'qualification.brm' => 'BRM',
    'qualification.arpa' => 'ARPA',
    'qualification.passport' => 'Паспорт',
    'qualification.seamans_book' => 'Паспорт моряка',
    'qualification.medical' => 'Медицинское',

    // Behavioral Interview
    'behavioral.title' => 'Поведенческая оценка',
    'behavioral.subtitle' => 'Пожалуйста, ответьте на вопросы честно, основываясь на вашем реальном опыте.',
    'behavioral.category.discipline_procedure' => 'Дисциплина и процедуры',
    'behavioral.category.stress_crisis' => 'Стресс и кризисное управление',
    'behavioral.category.team_compatibility' => 'Совместимость с командой',
    'behavioral.category.leadership_responsibility' => 'Лидерство и ответственность',
    'behavioral.submit' => 'Отправить оценку',
    'behavioral.saved' => 'Ответ сохранён',
    'behavioral.complete' => 'Оценка успешно отправлена',
    'behavioral.progress' => ':completed из :total вопросов отвечено',
];
