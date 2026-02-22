<?php

// --- Translation coverage ---
// TRANSLATED: validation.*, response.*, status.*, next_step.*, explanation.*, concern.*, decision.*, category.*
// ENGLISH FALLBACK: rank.*, department.*, cert.*, english.*, source.*, interview.*, qualification.*

return [
    // --- Scope A: Apply flow ---

    // Validation messages — TRANSLATED
    'validation.first_name_required' => 'Nama depan wajib diisi.',
    'validation.last_name_required' => 'Nama belakang wajib diisi.',
    'validation.email_required' => 'Alamat email wajib diisi.',
    'validation.email_invalid' => 'Masukkan alamat email yang valid.',
    'validation.phone_required' => 'Nomor telepon wajib diisi untuk kandidat maritim.',
    'validation.country_required' => 'Kode negara wajib diisi.',
    'validation.english_level_required' => 'Pilih tingkat bahasa Inggris Anda.',
    'validation.english_level_invalid' => 'Tingkat bahasa Inggris tidak valid. Pilihan: A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Pilih pangkat pelaut Anda.',
    'validation.rank_invalid' => 'Pangkat yang dipilih tidak valid.',
    'validation.source_required' => 'Saluran sumber wajib diisi.',
    'validation.source_invalid' => 'Saluran sumber tidak valid.',
    'validation.privacy_required' => 'Anda harus menerima kebijakan privasi.',
    'validation.data_processing_required' => 'Anda harus menyetujui pemrosesan data.',
    'validation.failed' => 'Validasi gagal',

    // Response messages — TRANSLATED
    'response.registration_success' => 'Pendaftaran berhasil. Selamat bergabung!',
    'response.welcome_back' => 'Selamat datang kembali! Profil kandidat ditemukan.',
    'response.already_hired' => 'Kandidat ini sudah diterima bekerja.',
    'response.candidate_not_found' => 'Kandidat tidak ditemukan.',
    'response.maritime_only' => 'Endpoint ini hanya untuk kandidat maritim.',
    'response.interview_active' => 'Kandidat memiliki wawancara aktif.',
    'response.interview_started' => 'Wawancara berhasil dimulai.',
    'response.cannot_start_interview' => 'Tidak dapat memulai wawancara untuk kandidat dengan status: :status',
    'response.english_submitted' => 'Penilaian bahasa Inggris berhasil dikirim.',
    'response.video_submitted' => 'Video berhasil dikirim.',
    'response.no_completed_interview' => 'Wawancara yang selesai tidak ditemukan. Selesaikan wawancara terlebih dahulu.',

    // Status labels — TRANSLATED
    'status.new' => 'Terdaftar',
    'status.assessed' => 'Penilaian Selesai',
    'status.in_pool' => 'Di Talent Pool',
    'status.presented' => 'Diperkenalkan ke Perusahaan',
    'status.hired' => 'Diterima Bekerja',
    'status.archived' => 'Diarsipkan',
    'status.unknown' => 'Tidak diketahui',

    // Next steps — TRANSLATED
    'next_step.start_interview' => 'Mulai wawancara penilaian Anda',
    'next_step.continue_interview' => 'Lanjutkan wawancara Anda',
    'next_step.complete_interview' => 'Selesaikan wawancara Anda',
    'next_step.complete_english' => 'Selesaikan penilaian bahasa Inggris',
    'next_step.submit_video' => 'Kirim video perkenalan',
    'next_step.profile_complete' => 'Profil Anda lengkap — kami akan menghubungi Anda untuk peluang kerja',

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
    'decision.hire' => 'Terima',
    'decision.review' => 'Tinjau',
    'decision.reject' => 'Tolak',

    'category.core_duty' => 'Tugas Inti',
    'category.risk_safety' => 'Risiko & Keselamatan',
    'category.procedure_discipline' => 'Disiplin Prosedur',
    'category.communication_judgment' => 'Komunikasi & Penilaian',

    'concern.critical_risk' => 'tanda risiko kritis',
    'concern.major_risk' => 'kekhawatiran risiko utama',
    'concern.expired_cert' => 'sertifikat kedaluwarsa',
    'concern.unverified_cert' => 'sertifikat belum terverifikasi',

    'explanation.recommendation' => 'Rekomendasi :decision (skor: :score/100, kepercayaan: :confidence%).',
    'explanation.strengths' => 'Kekuatan: :strengths.',
    'explanation.concerns' => 'Kekhawatiran: :concerns.',

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
    'behavioral.title' => 'Penilaian Perilaku',
    'behavioral.subtitle' => 'Silakan jawab pertanyaan berikut dengan jujur berdasarkan pengalaman nyata Anda.',
    'behavioral.category.discipline_procedure' => 'Disiplin dan Prosedur',
    'behavioral.category.stress_crisis' => 'Manajemen Stres dan Krisis',
    'behavioral.category.team_compatibility' => 'Kecocokan Tim',
    'behavioral.category.leadership_responsibility' => 'Kepemimpinan dan Tanggung Jawab',
    'behavioral.submit' => 'Kirim Penilaian',
    'behavioral.saved' => 'Jawaban disimpan',
    'behavioral.complete' => 'Penilaian berhasil dikirim',
    'behavioral.progress' => ':completed dari :total pertanyaan dijawab',
];
