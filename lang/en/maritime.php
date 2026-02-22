<?php

return [
    // --- Scope A: Apply flow ---

    // Validation messages
    'validation.first_name_required' => 'First name is required.',
    'validation.last_name_required' => 'Last name is required.',
    'validation.email_required' => 'Email is required.',
    'validation.email_invalid' => 'Please provide a valid email address.',
    'validation.phone_required' => 'Phone number is required for maritime candidates.',
    'validation.country_required' => 'Country code is required.',
    'validation.english_level_required' => 'Please select your English level.',
    'validation.english_level_invalid' => 'Invalid English level. Options: A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Please select your seafarer rank.',
    'validation.rank_invalid' => 'Invalid rank selected.',
    'validation.source_required' => 'Source channel is required.',
    'validation.source_invalid' => 'Invalid source channel.',
    'validation.privacy_required' => 'You must accept the privacy policy.',
    'validation.data_processing_required' => 'You must consent to data processing.',
    'validation.failed' => 'Validation failed',

    // Response messages
    'response.registration_success' => 'Registration successful. Welcome aboard!',
    'response.welcome_back' => 'Welcome back! Candidate profile found.',
    'response.already_hired' => 'This candidate has already been hired.',
    'response.candidate_not_found' => 'Candidate not found.',
    'response.maritime_only' => 'This endpoint is only for maritime candidates.',
    'response.interview_active' => 'Candidate has an active interview.',
    'response.interview_started' => 'Interview started successfully.',
    'response.cannot_start_interview' => 'Cannot start interview for candidate with status: :status',
    'response.english_submitted' => 'English assessment submitted successfully.',
    'response.video_submitted' => 'Video submitted successfully.',
    'response.no_completed_interview' => 'No completed interview found. Complete the interview first.',

    // Status labels
    'status.new' => 'Registered',
    'status.assessed' => 'Assessment Complete',
    'status.in_pool' => 'In Talent Pool',
    'status.presented' => 'Presented to Companies',
    'status.hired' => 'Hired',
    'status.archived' => 'Archived',
    'status.unknown' => 'Unknown',

    // Next steps
    'next_step.start_interview' => 'Start your assessment interview',
    'next_step.continue_interview' => 'Continue your interview',
    'next_step.complete_interview' => 'Complete your interview',
    'next_step.complete_english' => 'Complete English assessment',
    'next_step.submit_video' => 'Submit video introduction',
    'next_step.profile_complete' => 'Your profile is complete - we will contact you with opportunities',

    // Ranks (18 keys)
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

    // Departments (4 keys)
    'department.deck' => 'Deck',
    'department.engine' => 'Engine',
    'department.galley' => 'Galley',
    'department.cadet' => 'Cadet',

    // Certificates (8 keys)
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

    // English levels (6 keys)
    'english.a1' => 'A1 – Beginner',
    'english.a2' => 'A2 – Elementary',
    'english.b1' => 'B1 – Intermediate',
    'english.b2' => 'B2 – Upper Intermediate',
    'english.c1' => 'C1 – Advanced',
    'english.c2' => 'C2 – Proficient',

    // Source channels (9 keys)
    'source.maritime_event' => 'Maritime Event',
    'source.maritime_fair' => 'Maritime Fair',
    'source.linkedin' => 'LinkedIn',
    'source.referral' => 'Referral',
    'source.job_board' => 'Job Board',
    'source.organic' => 'Organic Search',
    'source.crewing_agency' => 'Crewing Agency',
    'source.maritime_school' => 'Maritime School',
    'source.seafarer_union' => 'Seafarer Union',

    // --- Scope B: Interview ---
    'interview.status.draft' => 'Draft',
    'interview.status.in_progress' => 'In Progress',
    'interview.status.completed' => 'Completed',
    'interview.status.cancelled' => 'Cancelled',

    // --- Scope C: Decision ---
    'decision.hire' => 'Hire',
    'decision.review' => 'Review',
    'decision.reject' => 'Reject',

    'category.core_duty' => 'Core Duty',
    'category.risk_safety' => 'Risk & Safety',
    'category.procedure_discipline' => 'Procedure Discipline',
    'category.communication_judgment' => 'Communication & Judgment',

    'concern.critical_risk' => 'critical risk flags',
    'concern.major_risk' => 'major risk concerns',
    'concern.expired_cert' => 'expired certificate',
    'concern.unverified_cert' => 'unverified certificate',

    'explanation.recommendation' => ':decision recommendation (score: :score/100, confidence: :confidence%).',
    'explanation.strengths' => 'Strengths: :strengths.',
    'explanation.concerns' => 'Concerns: :concerns.',

    // --- Scope D: Company Dashboard ---
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
    'behavioral.subtitle' => 'Please answer the following questions honestly based on your real experience.',
    'behavioral.category.discipline_procedure' => 'Discipline & Procedure',
    'behavioral.category.stress_crisis' => 'Stress & Crisis Management',
    'behavioral.category.team_compatibility' => 'Team Compatibility',
    'behavioral.category.leadership_responsibility' => 'Leadership & Responsibility',
    'behavioral.submit' => 'Submit Assessment',
    'behavioral.saved' => 'Answer saved',
    'behavioral.complete' => 'Assessment submitted successfully',
    'behavioral.progress' => ':completed of :total questions answered',
];
