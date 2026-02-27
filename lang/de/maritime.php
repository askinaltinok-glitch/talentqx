<?php

return [
    // --- Scope A: Apply flow ---

    // Validation messages
    'validation.first_name_required' => 'Vorname ist erforderlich.',
    'validation.last_name_required' => 'Nachname ist erforderlich.',
    'validation.email_required' => 'E-Mail-Adresse ist erforderlich.',
    'validation.email_invalid' => 'Bitte geben Sie eine gueltige E-Mail-Adresse an.',
    'validation.phone_required' => 'Telefonnummer ist fuer maritime Bewerber erforderlich.',
    'validation.country_required' => 'Laendercode ist erforderlich.',
    'validation.english_level_required' => 'Bitte waehlen Sie Ihr Englischniveau aus.',
    'validation.english_level_invalid' => 'Ungueltiges Englischniveau. Optionen: A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Bitte waehlen Sie Ihren Dienstgrad aus.',
    'validation.rank_invalid' => 'Ungueltiger Dienstgrad ausgewaehlt.',
    'validation.source_required' => 'Herkunftskanal ist erforderlich.',
    'validation.source_invalid' => 'Ungueltiger Herkunftskanal.',
    'validation.privacy_required' => 'Sie muessen die Datenschutzerklaerung akzeptieren.',
    'validation.data_processing_required' => 'Sie muessen der Datenverarbeitung zustimmen.',
    'validation.failed' => 'Validierung fehlgeschlagen',

    // Response messages
    'response.registration_success' => 'Registrierung erfolgreich. Willkommen an Bord!',
    'response.welcome_back' => 'Willkommen zurueck! Bewerberprofil gefunden.',
    'response.already_hired' => 'Dieser Bewerber wurde bereits eingestellt.',
    'response.candidate_not_found' => 'Bewerber nicht gefunden.',
    'response.maritime_only' => 'Dieser Endpunkt ist nur fuer maritime Bewerber.',
    'response.interview_active' => 'Bewerber hat ein laufendes Interview.',
    'response.interview_started' => 'Interview erfolgreich gestartet.',
    'response.cannot_start_interview' => 'Interview kann nicht gestartet werden fuer Bewerber mit Status: :status',
    'response.english_submitted' => 'Englischbewertung erfolgreich eingereicht.',
    'response.video_submitted' => 'Video erfolgreich eingereicht.',
    'response.no_completed_interview' => 'Kein abgeschlossenes Interview gefunden. Bitte schliessen Sie zuerst das Interview ab.',

    // Status labels
    'status.new' => 'Registriert',
    'status.assessed' => 'Bewertung abgeschlossen',
    'status.in_pool' => 'Im Talentpool',
    'status.presented' => 'Unternehmen vorgestellt',
    'status.hired' => 'Eingestellt',
    'status.archived' => 'Archiviert',
    'status.unknown' => 'Unbekannt',

    // Next steps
    'next_step.start_interview' => 'Starten Sie Ihr Bewertungsinterview',
    'next_step.continue_interview' => 'Setzen Sie Ihr Interview fort',
    'next_step.complete_interview' => 'Schliessen Sie Ihr Interview ab',
    'next_step.complete_english' => 'Englischbewertung abschliessen',
    'next_step.submit_video' => 'Videovorstellung einreichen',
    'next_step.profile_complete' => 'Ihr Profil ist vollstaendig - wir werden Sie ueber Moeglichkeiten informieren',

    // Ranks (18 keys)
    'rank.captain' => 'Kapitaen',
    'rank.chief_officer' => 'Erster Offizier',
    'rank.second_officer' => 'Zweiter Offizier',
    'rank.third_officer' => 'Dritter Offizier',
    'rank.bosun' => 'Bootsmann',
    'rank.able_seaman' => 'Vollmatrose (AB)',
    'rank.ordinary_seaman' => 'Leichtmatrose (OS)',
    'rank.chief_engineer' => 'Leitender Ingenieur',
    'rank.second_engineer' => 'Zweiter Ingenieur',
    'rank.third_engineer' => 'Dritter Ingenieur',
    'rank.motorman' => 'Motorenwart',
    'rank.oiler' => 'Schmierer',
    'rank.electrician' => 'Elektriker / ETO',
    'rank.cook' => 'Schiffskoch',
    'rank.steward' => 'Steward',
    'rank.messman' => 'Messmann',
    'rank.deck_cadet' => 'Deckkadett',
    'rank.engine_cadet' => 'Maschinenkadett',

    // Departments (4 keys)
    'department.deck' => 'Deck',
    'department.engine' => 'Maschine',
    'department.galley' => 'Kombüse',
    'department.cadet' => 'Kadett',

    // Certificates (8 keys)
    'cert.stcw' => 'STCW Grundlegende Sicherheit',
    'cert.coc' => 'Befähigungszeugnis (CoC)',
    'cert.goc' => 'Allgemeines Betriebszeugnis (GOC)',
    'cert.ecdis' => 'ECDIS',
    'cert.arpa' => 'ARPA',
    'cert.brm' => 'Brückenressourcenmanagement (BRM)',
    'cert.erm' => 'Maschinenressourcenmanagement (ERM)',
    'cert.hazmat' => 'Gefahrgut',
    'cert.medical' => 'Ärztliches Zeugnis',
    'cert.passport' => 'Reisepass',
    'cert.seamans_book' => 'Seefahrtbuch',
    'cert.flag_endorsement' => 'Flaggenstaatsvermerk',
    'cert.tanker_endorsement' => 'Tankervermerk',

    // English levels (6 keys)
    'english.a1' => 'A1 – Anfänger',
    'english.a2' => 'A2 – Grundlegende Kenntnisse',
    'english.b1' => 'B1 – Mittelstufe',
    'english.b2' => 'B2 – Gehobene Mittelstufe',
    'english.c1' => 'C1 – Fortgeschritten',
    'english.c2' => 'C2 – Kompetente Sprachverwendung',

    // Source channels (9 keys)
    'source.maritime_event' => 'Maritime Veranstaltung',
    'source.maritime_fair' => 'Maritime Messe',
    'source.linkedin' => 'LinkedIn',
    'source.referral' => 'Empfehlung',
    'source.job_board' => 'Jobbörse',
    'source.organic' => 'Organische Suche',
    'source.crewing_agency' => 'Crewing-Agentur',
    'source.maritime_school' => 'Seefahrtschule',
    'source.seafarer_union' => 'Seemannsgewerkschaft',

    // --- Scope B: Interview ---
    'interview.status.draft' => 'Entwurf',
    'interview.status.in_progress' => 'In Bearbeitung',
    'interview.status.completed' => 'Abgeschlossen',
    'interview.status.cancelled' => 'Abgebrochen',

    // --- Scope C: Decision ---
    'decision.hire' => 'Einstellen',
    'decision.review' => 'Überprüfen',
    'decision.reject' => 'Ablehnen',

    'category.core_duty' => 'Kernaufgabe',
    'category.risk_safety' => 'Risiko & Sicherheit',
    'category.procedure_discipline' => 'Verfahrensdisziplin',
    'category.communication_judgment' => 'Kommunikation & Urteilsvermögen',

    'concern.critical_risk' => 'kritische Risikohinweise',
    'concern.major_risk' => 'erhebliche Risikobedenken',
    'concern.expired_cert' => 'abgelaufenes Zertifikat',
    'concern.unverified_cert' => 'nicht verifiziertes Zertifikat',

    'explanation.recommendation' => ':decision Empfehlung (Punktzahl: :score/100, Konfidenz: :confidence%).',
    'explanation.strengths' => 'Stärken: :strengths.',
    'explanation.concerns' => 'Bedenken: :concerns.',

    // --- Scope D: Company Dashboard ---
    'qualification.stcw' => 'STCW',
    'qualification.coc' => 'COC',
    'qualification.goc' => 'GOC',
    'qualification.ecdis' => 'ECDIS',
    'qualification.brm' => 'BRM',
    'qualification.arpa' => 'ARPA',
    'qualification.passport' => 'Reisepass',
    'qualification.seamans_book' => 'Seefahrtbuch',
    'qualification.medical' => 'Ärztliches Zeugnis',

    // Behavioral Interview
    'behavioral.title' => 'Verhaltensbewertung',
    'behavioral.subtitle' => 'Bitte beantworten Sie die folgenden Fragen ehrlich basierend auf Ihrer tatsächlichen Erfahrung.',
    'behavioral.category.discipline_procedure' => 'Disziplin & Verfahren',
    'behavioral.category.stress_crisis' => 'Stress- & Krisenmanagement',
    'behavioral.category.team_compatibility' => 'Teamkompatibilität',
    'behavioral.category.leadership_responsibility' => 'Führung & Verantwortung',
    'behavioral.submit' => 'Bewertung einreichen',
    'behavioral.saved' => 'Antwort gespeichert',
    'behavioral.complete' => 'Bewertung erfolgreich eingereicht',
    'behavioral.progress' => ':completed von :total Fragen beantwortet',
];
