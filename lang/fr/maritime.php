<?php

return [
    // --- Scope A: Apply flow ---

    // Validation messages
    'validation.first_name_required' => 'Le prénom est requis.',
    'validation.last_name_required' => 'Le nom de famille est requis.',
    'validation.email_required' => 'L\'adresse e-mail est requise.',
    'validation.email_invalid' => 'Veuillez fournir une adresse e-mail valide.',
    'validation.phone_required' => 'Le numéro de téléphone est requis pour les candidats maritimes.',
    'validation.country_required' => 'Le code pays est requis.',
    'validation.english_level_required' => 'Veuillez sélectionner votre niveau d\'anglais.',
    'validation.english_level_invalid' => 'Niveau d\'anglais invalide. Options : A1, A2, B1, B2, C1, C2.',
    'validation.rank_required' => 'Veuillez sélectionner votre grade de marin.',
    'validation.rank_invalid' => 'Grade sélectionné invalide.',
    'validation.source_required' => 'Le canal source est requis.',
    'validation.source_invalid' => 'Canal source invalide.',
    'validation.privacy_required' => 'Vous devez accepter la politique de confidentialité.',
    'validation.data_processing_required' => 'Vous devez consentir au traitement des données.',
    'validation.failed' => 'Échec de la validation',

    // Response messages
    'response.registration_success' => 'Inscription réussie. Bienvenue à bord !',
    'response.welcome_back' => 'Bon retour ! Profil du candidat trouvé.',
    'response.already_hired' => 'Ce candidat a déjà été embauché.',
    'response.candidate_not_found' => 'Candidat introuvable.',
    'response.maritime_only' => 'Ce point d\'accès est réservé aux candidats maritimes.',
    'response.interview_active' => 'Le candidat a un entretien en cours.',
    'response.interview_started' => 'Entretien démarré avec succès.',
    'response.cannot_start_interview' => 'Impossible de démarrer l\'entretien pour le candidat avec le statut : :status',
    'response.english_submitted' => 'Évaluation d\'anglais soumise avec succès.',
    'response.video_submitted' => 'Vidéo soumise avec succès.',
    'response.no_completed_interview' => 'Aucun entretien terminé trouvé. Veuillez d\'abord compléter l\'entretien.',

    // Status labels
    'status.new' => 'Inscrit',
    'status.assessed' => 'Évaluation terminée',
    'status.in_pool' => 'Dans le vivier de talents',
    'status.presented' => 'Présenté aux entreprises',
    'status.hired' => 'Embauché',
    'status.archived' => 'Archivé',
    'status.unknown' => 'Inconnu',

    // Next steps
    'next_step.start_interview' => 'Démarrer votre entretien d\'évaluation',
    'next_step.continue_interview' => 'Continuer votre entretien',
    'next_step.complete_interview' => 'Terminer votre entretien',
    'next_step.complete_english' => 'Compléter l\'évaluation d\'anglais',
    'next_step.submit_video' => 'Soumettre la vidéo de présentation',
    'next_step.profile_complete' => 'Votre profil est complet - nous vous contacterons avec des opportunités',

    // Ranks (18 keys)
    'rank.captain' => 'Capitaine',
    'rank.chief_officer' => 'Second capitaine',
    'rank.second_officer' => 'Deuxième officier',
    'rank.third_officer' => 'Troisième officier',
    'rank.bosun' => 'Maître d\'équipage',
    'rank.able_seaman' => 'Matelot qualifié (AB)',
    'rank.ordinary_seaman' => 'Matelot léger (OS)',
    'rank.chief_engineer' => 'Chef mécanicien',
    'rank.second_engineer' => 'Second mécanicien',
    'rank.third_engineer' => 'Troisième mécanicien',
    'rank.motorman' => 'Motoriste',
    'rank.oiler' => 'Graisseur',
    'rank.electrician' => 'Électricien / ETO',
    'rank.cook' => 'Cuisinier de bord',
    'rank.steward' => 'Steward',
    'rank.messman' => 'Garçon de mess',
    'rank.deck_cadet' => 'Élève officier pont',
    'rank.engine_cadet' => 'Élève officier machine',

    // Departments (4 keys)
    'department.deck' => 'Pont',
    'department.engine' => 'Machine',
    'department.galley' => 'Cuisine',
    'department.cadet' => 'Élève officier',

    // Certificates (8 keys)
    'cert.stcw' => 'STCW Sécurité de base',
    'cert.coc' => 'Brevet de compétence (CoC)',
    'cert.goc' => 'Certificat général d\'opérateur (GOC)',
    'cert.ecdis' => 'ECDIS',
    'cert.arpa' => 'ARPA',
    'cert.brm' => 'Gestion des ressources à la passerelle (BRM)',
    'cert.erm' => 'Gestion des ressources machine (ERM)',
    'cert.hazmat' => 'Matières dangereuses',
    'cert.medical' => 'Certificat médical',
    'cert.passport' => 'Passeport',
    'cert.seamans_book' => 'Livret maritime',
    'cert.flag_endorsement' => 'Visa de l\'État du pavillon',
    'cert.tanker_endorsement' => 'Visa pétrolier',

    // English levels (6 keys)
    'english.a1' => 'A1 – Débutant',
    'english.a2' => 'A2 – Élémentaire',
    'english.b1' => 'B1 – Intermédiaire',
    'english.b2' => 'B2 – Intermédiaire supérieur',
    'english.c1' => 'C1 – Avancé',
    'english.c2' => 'C2 – Maîtrise',

    // Source channels (9 keys)
    'source.maritime_event' => 'Événement maritime',
    'source.maritime_fair' => 'Salon maritime',
    'source.linkedin' => 'LinkedIn',
    'source.referral' => 'Recommandation',
    'source.job_board' => 'Site d\'emploi',
    'source.organic' => 'Recherche organique',
    'source.crewing_agency' => 'Agence de recrutement maritime',
    'source.maritime_school' => 'École maritime',
    'source.seafarer_union' => 'Syndicat des gens de mer',

    // --- Scope B: Interview ---
    'interview.status.draft' => 'Brouillon',
    'interview.status.in_progress' => 'En cours',
    'interview.status.completed' => 'Terminé',
    'interview.status.cancelled' => 'Annulé',

    // --- Scope C: Decision ---
    'decision.hire' => 'Embaucher',
    'decision.review' => 'Examiner',
    'decision.reject' => 'Rejeter',

    'category.core_duty' => 'Fonction principale',
    'category.risk_safety' => 'Risque & Sécurité',
    'category.procedure_discipline' => 'Discipline procédurale',
    'category.communication_judgment' => 'Communication & Jugement',

    'concern.critical_risk' => 'signalements de risque critique',
    'concern.major_risk' => 'préoccupations de risque majeur',
    'concern.expired_cert' => 'certificat expiré',
    'concern.unverified_cert' => 'certificat non vérifié',

    'explanation.recommendation' => 'Recommandation :decision (score : :score/100, confiance : :confidence%).',
    'explanation.strengths' => 'Points forts : :strengths.',
    'explanation.concerns' => 'Préoccupations : :concerns.',

    // --- Scope D: Company Dashboard ---
    'qualification.stcw' => 'STCW',
    'qualification.coc' => 'COC',
    'qualification.goc' => 'GOC',
    'qualification.ecdis' => 'ECDIS',
    'qualification.brm' => 'BRM',
    'qualification.arpa' => 'ARPA',
    'qualification.passport' => 'Passeport',
    'qualification.seamans_book' => 'Livret maritime',
    'qualification.medical' => 'Certificat médical',

    // Behavioral Interview
    'behavioral.title' => 'Évaluation comportementale',
    'behavioral.subtitle' => 'Veuillez répondre aux questions suivantes honnêtement en vous basant sur votre expérience réelle.',
    'behavioral.category.discipline_procedure' => 'Discipline & Procédures',
    'behavioral.category.stress_crisis' => 'Gestion du stress & des crises',
    'behavioral.category.team_compatibility' => 'Compatibilité d\'équipe',
    'behavioral.category.leadership_responsibility' => 'Leadership & Responsabilité',
    'behavioral.submit' => 'Soumettre l\'évaluation',
    'behavioral.saved' => 'Réponse enregistrée',
    'behavioral.complete' => 'Évaluation soumise avec succès',
    'behavioral.progress' => ':completed sur :total questions répondues',
];
