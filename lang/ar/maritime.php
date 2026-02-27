<?php

return [
    // --- Scope A: Apply flow ---

    // Validation messages
    'validation.first_name_required' => 'الاسم الأول مطلوب.',
    'validation.last_name_required' => 'اسم العائلة مطلوب.',
    'validation.email_required' => 'البريد الإلكتروني مطلوب.',
    'validation.email_invalid' => 'يرجى تقديم عنوان بريد إلكتروني صالح.',
    'validation.phone_required' => 'رقم الهاتف مطلوب للمرشحين البحريين.',
    'validation.country_required' => 'رمز الدولة مطلوب.',
    'validation.english_level_required' => 'يرجى اختيار مستوى اللغة الإنجليزية.',
    'validation.english_level_invalid' => 'مستوى اللغة الإنجليزية غير صالح. الخيارات: A1، A2، B1، B2، C1، C2.',
    'validation.rank_required' => 'يرجى اختيار رتبتك البحرية.',
    'validation.rank_invalid' => 'الرتبة المختارة غير صالحة.',
    'validation.source_required' => 'قناة المصدر مطلوبة.',
    'validation.source_invalid' => 'قناة المصدر غير صالحة.',
    'validation.privacy_required' => 'يجب عليك قبول سياسة الخصوصية.',
    'validation.data_processing_required' => 'يجب عليك الموافقة على معالجة البيانات.',
    'validation.failed' => 'فشل التحقق',

    // Response messages
    'response.registration_success' => 'تم التسجيل بنجاح. أهلاً بك على متن السفينة!',
    'response.welcome_back' => 'مرحباً بعودتك! تم العثور على ملف المرشح.',
    'response.already_hired' => 'تم توظيف هذا المرشح بالفعل.',
    'response.candidate_not_found' => 'لم يتم العثور على المرشح.',
    'response.maritime_only' => 'نقطة الوصول هذه مخصصة للمرشحين البحريين فقط.',
    'response.interview_active' => 'لدى المرشح مقابلة جارية.',
    'response.interview_started' => 'تم بدء المقابلة بنجاح.',
    'response.cannot_start_interview' => 'لا يمكن بدء المقابلة للمرشح ذي الحالة: :status',
    'response.english_submitted' => 'تم تقديم تقييم اللغة الإنجليزية بنجاح.',
    'response.video_submitted' => 'تم تقديم الفيديو بنجاح.',
    'response.no_completed_interview' => 'لم يتم العثور على مقابلة مكتملة. يرجى إكمال المقابلة أولاً.',

    // Status labels
    'status.new' => 'مسجّل',
    'status.assessed' => 'اكتمل التقييم',
    'status.in_pool' => 'في مجمع المواهب',
    'status.presented' => 'تم التقديم للشركات',
    'status.hired' => 'تم التوظيف',
    'status.archived' => 'مؤرشف',
    'status.unknown' => 'غير معروف',

    // Next steps
    'next_step.start_interview' => 'ابدأ مقابلة التقييم الخاصة بك',
    'next_step.continue_interview' => 'تابع مقابلتك',
    'next_step.complete_interview' => 'أكمل مقابلتك',
    'next_step.complete_english' => 'أكمل تقييم اللغة الإنجليزية',
    'next_step.submit_video' => 'قدّم مقطع الفيديو التعريفي',
    'next_step.profile_complete' => 'ملفك الشخصي مكتمل - سنتواصل معك بشأن الفرص المتاحة',

    // Ranks (18 keys)
    'rank.captain' => 'ربّان',
    'rank.chief_officer' => 'كبير الضباط',
    'rank.second_officer' => 'الضابط الثاني',
    'rank.third_officer' => 'الضابط الثالث',
    'rank.bosun' => 'رئيس البحّارة',
    'rank.able_seaman' => 'بحّار ماهر (AB)',
    'rank.ordinary_seaman' => 'بحّار عادي (OS)',
    'rank.chief_engineer' => 'كبير المهندسين',
    'rank.second_engineer' => 'المهندس الثاني',
    'rank.third_engineer' => 'المهندس الثالث',
    'rank.motorman' => 'فنّي محركات',
    'rank.oiler' => 'مشحّم',
    'rank.electrician' => 'كهربائي / ETO',
    'rank.cook' => 'طبّاخ السفينة',
    'rank.steward' => 'مضيف',
    'rank.messman' => 'عامل المطعم',
    'rank.deck_cadet' => 'طالب ضابط سطح',
    'rank.engine_cadet' => 'طالب ضابط آلات',

    // Departments (4 keys)
    'department.deck' => 'السطح',
    'department.engine' => 'الآلات',
    'department.galley' => 'المطبخ',
    'department.cadet' => 'الطلاب',

    // Certificates (8 keys)
    'cert.stcw' => 'STCW السلامة الأساسية',
    'cert.coc' => 'شهادة الكفاءة (CoC)',
    'cert.goc' => 'شهادة المشغّل العام (GOC)',
    'cert.ecdis' => 'ECDIS',
    'cert.arpa' => 'ARPA',
    'cert.brm' => 'إدارة موارد الجسر (BRM)',
    'cert.erm' => 'إدارة موارد غرفة المحركات (ERM)',
    'cert.hazmat' => 'المواد الخطرة',
    'cert.medical' => 'الشهادة الطبية',
    'cert.passport' => 'جواز السفر',
    'cert.seamans_book' => 'دفتر البحّار',
    'cert.flag_endorsement' => 'تصديق دولة العلم',
    'cert.tanker_endorsement' => 'تصديق الناقلات',

    // English levels (6 keys)
    'english.a1' => 'A1 – مبتدئ',
    'english.a2' => 'A2 – أساسي',
    'english.b1' => 'B1 – متوسط',
    'english.b2' => 'B2 – فوق المتوسط',
    'english.c1' => 'C1 – متقدم',
    'english.c2' => 'C2 – إتقان',

    // Source channels (9 keys)
    'source.maritime_event' => 'فعالية بحرية',
    'source.maritime_fair' => 'معرض بحري',
    'source.linkedin' => 'لينكد إن',
    'source.referral' => 'توصية',
    'source.job_board' => 'منصة توظيف',
    'source.organic' => 'بحث عضوي',
    'source.crewing_agency' => 'وكالة تجنيد بحري',
    'source.maritime_school' => 'مدرسة بحرية',
    'source.seafarer_union' => 'نقابة البحّارة',

    // --- Scope B: Interview ---
    'interview.status.draft' => 'مسودة',
    'interview.status.in_progress' => 'قيد التنفيذ',
    'interview.status.completed' => 'مكتملة',
    'interview.status.cancelled' => 'ملغاة',

    // --- Scope C: Decision ---
    'decision.hire' => 'توظيف',
    'decision.review' => 'مراجعة',
    'decision.reject' => 'رفض',

    'category.core_duty' => 'المهمة الأساسية',
    'category.risk_safety' => 'المخاطر والسلامة',
    'category.procedure_discipline' => 'الانضباط الإجرائي',
    'category.communication_judgment' => 'التواصل والحكم',

    'concern.critical_risk' => 'مؤشرات مخاطر حرجة',
    'concern.major_risk' => 'مخاوف مخاطر رئيسية',
    'concern.expired_cert' => 'شهادة منتهية الصلاحية',
    'concern.unverified_cert' => 'شهادة غير موثّقة',

    'explanation.recommendation' => 'توصية :decision (الدرجة: :score/100، الثقة: :confidence%).',
    'explanation.strengths' => 'نقاط القوة: :strengths.',
    'explanation.concerns' => 'المخاوف: :concerns.',

    // --- Scope D: Company Dashboard ---
    'qualification.stcw' => 'STCW',
    'qualification.coc' => 'COC',
    'qualification.goc' => 'GOC',
    'qualification.ecdis' => 'ECDIS',
    'qualification.brm' => 'BRM',
    'qualification.arpa' => 'ARPA',
    'qualification.passport' => 'جواز السفر',
    'qualification.seamans_book' => 'دفتر البحّار',
    'qualification.medical' => 'الشهادة الطبية',

    // Behavioral Interview
    'behavioral.title' => 'التقييم السلوكي',
    'behavioral.subtitle' => 'يرجى الإجابة على الأسئلة التالية بصدق بناءً على تجربتك الفعلية.',
    'behavioral.category.discipline_procedure' => 'الانضباط والإجراءات',
    'behavioral.category.stress_crisis' => 'إدارة الضغوط والأزمات',
    'behavioral.category.team_compatibility' => 'التوافق مع الفريق',
    'behavioral.category.leadership_responsibility' => 'القيادة والمسؤولية',
    'behavioral.submit' => 'تقديم التقييم',
    'behavioral.saved' => 'تم حفظ الإجابة',
    'behavioral.complete' => 'تم تقديم التقييم بنجاح',
    'behavioral.progress' => ':completed من :total سؤال تمت الإجابة عليه',
];
