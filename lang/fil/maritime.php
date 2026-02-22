<?php

// --- Translation coverage ---
// TRANSLATED: validation.*, response.*, status.*, next_step.*, explanation.*, concern.*, decision.*, category.*
// ENGLISH FALLBACK: rank.*, department.*, cert.*, english.*, source.*, interview.*, qualification.*

return [
    // --- Scope A: Apply flow ---

    // Validation messages — TRANSLATED
    'validation.first_name_required' => 'Kinakailangan ang pangalan.',
    'validation.last_name_required' => 'Kinakailangan ang apelyido.',
    'validation.email_required' => 'Kinakailangan ang email address.',
    'validation.email_invalid' => 'Magbigay ng wastong email address.',
    'validation.phone_required' => 'Kinakailangan ang numero ng telepono para sa mga maritime candidate.',
    'validation.country_required' => 'Kinakailangan ang country code.',
    'validation.english_level_required' => 'Piliin ang iyong antas ng Ingles.',
    'validation.english_level_invalid' => 'Hindi wastong antas ng Ingles. Mga pagpipilian: A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Piliin ang iyong ranggo bilang marino.',
    'validation.rank_invalid' => 'Hindi wastong ranggo ang napili.',
    'validation.source_required' => 'Kinakailangan ang source channel.',
    'validation.source_invalid' => 'Hindi wastong source channel.',
    'validation.privacy_required' => 'Kailangan mong tanggapin ang privacy policy.',
    'validation.data_processing_required' => 'Kailangan mong pumayag sa pagproseso ng datos.',
    'validation.failed' => 'Nabigo ang validation',

    // Response messages — TRANSLATED
    'response.registration_success' => 'Matagumpay ang pagpaparehistro. Maligayang pagdating!',
    'response.welcome_back' => 'Maligayang pagbabalik! Natagpuan ang profile ng candidate.',
    'response.already_hired' => 'Nakunan na ng trabaho ang candidate na ito.',
    'response.candidate_not_found' => 'Hindi natagpuan ang candidate.',
    'response.maritime_only' => 'Para lamang sa mga maritime candidate ang endpoint na ito.',
    'response.interview_active' => 'May aktibong interview ang candidate.',
    'response.interview_started' => 'Matagumpay na nasimulan ang interview.',
    'response.cannot_start_interview' => 'Hindi masimulan ang interview para sa candidate na may status: :status',
    'response.english_submitted' => 'Matagumpay na naisumite ang English assessment.',
    'response.video_submitted' => 'Matagumpay na naisumite ang video.',
    'response.no_completed_interview' => 'Walang nakitang natapos na interview. Tapusin muna ang interview.',

    // Status labels — TRANSLATED
    'status.new' => 'Nakarehistro',
    'status.assessed' => 'Natapos ang Assessment',
    'status.in_pool' => 'Nasa Talent Pool',
    'status.presented' => 'Naipresenta sa mga Kumpanya',
    'status.hired' => 'Nakunan ng Trabaho',
    'status.archived' => 'Naka-archive',
    'status.unknown' => 'Hindi alam',

    // Next steps — TRANSLATED
    'next_step.start_interview' => 'Simulan ang iyong assessment interview',
    'next_step.continue_interview' => 'Ipagpatuloy ang iyong interview',
    'next_step.complete_interview' => 'Tapusin ang iyong interview',
    'next_step.complete_english' => 'Tapusin ang English assessment',
    'next_step.submit_video' => 'Isumite ang video introduction',
    'next_step.profile_complete' => 'Kumpleto na ang iyong profile — makikipag-ugnayan kami sa iyo para sa mga oportunidad',

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
    'decision.hire' => 'Kunin',
    'decision.review' => 'Suriin',
    'decision.reject' => 'Tanggihan',

    'category.core_duty' => 'Pangunahing Tungkulin',
    'category.risk_safety' => 'Panganib at Kaligtasan',
    'category.procedure_discipline' => 'Disiplina sa Pamamaraan',
    'category.communication_judgment' => 'Komunikasyon at Pagpapasya',

    'concern.critical_risk' => 'mga kritikal na risk flag',
    'concern.major_risk' => 'mga pangunahing risk concern',
    'concern.expired_cert' => 'nag-expire na sertipiko',
    'concern.unverified_cert' => 'hindi pa-verified na sertipiko',

    'explanation.recommendation' => ':decision na rekomendasyon (iskor: :score/100, kumpiyansa: :confidence%).',
    'explanation.strengths' => 'Mga Lakas: :strengths.',
    'explanation.concerns' => 'Mga Alalahanin: :concerns.',

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
    'behavioral.title' => 'Behavioral Assessment',
    'behavioral.subtitle' => 'Mangyaring sagutin ang mga tanong nang tapat batay sa iyong tunay na karanasan.',
    'behavioral.category.discipline_procedure' => 'Disiplina at Pamamaraan',
    'behavioral.category.stress_crisis' => 'Stress at Pamamahala ng Krisis',
    'behavioral.category.team_compatibility' => 'Pagkakatugma sa Koponan',
    'behavioral.category.leadership_responsibility' => 'Pamumuno at Responsibilidad',
    'behavioral.submit' => 'Isumite ang Assessment',
    'behavioral.saved' => 'Sagot ay na-save',
    'behavioral.complete' => 'Matagumpay na naisumite ang assessment',
    'behavioral.progress' => ':completed sa :total tanong ang nasagutan',
];
