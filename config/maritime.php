<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maritime Command Engine v2 Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable order: A → B → C → D
    | Detection runs silently until validated.
    | No production routing impact until flags are enabled.
    |
    */

    // Step A: Command class detection engine
    'command_engine_v2' => (bool) env('MARITIME_COMMAND_ENGINE_V2', false),

    // Step B: Phase-1 identity capture replaces old maritime template
    'identity_v2' => (bool) env('MARITIME_IDENTITY_V2', false),

    // Step C: 7-axis capability matrix replaces global score
    'capability_matrix_v2' => (bool) env('MARITIME_CAPABILITY_MATRIX_V2', false),

    // Step D: Profile-driven resolver replaces role-driven resolver
    'resolver_v2' => (bool) env('MARITIME_RESOLVER_V2', false),

    // Step E: Scenario bank for Phase-2 structured assessments
    'scenario_bank_v2' => (bool) env('MARITIME_SCENARIO_BANK_V2', false),

    // Master switch: full v2 pipeline enabled
    'v2_enabled' => (bool) env('MARITIME_V2_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Behavioral Matching Engine v1
    |--------------------------------------------------------------------------
    | Independent from technical scoring. Produces advisory behavioral profile.
    */
    'behavioral_v1' => (bool) env('MARITIME_BEHAVIORAL_V1', true),
    'behavioral_incremental' => (bool) env('MARITIME_BEHAVIORAL_INCREMENTAL', false),

    /*
    |--------------------------------------------------------------------------
    | Behavioral Interview v1 (Structured 12-question assessment)
    |--------------------------------------------------------------------------
    | Separate from behavioral_v1 (keyword heuristics on technical interview).
    | This is a standalone structured behavioral assessment sent via email.
    */
    'behavioral_interview_v1' => (bool) env('MARITIME_BEHAVIORAL_INTERVIEW_V1', true),

    /*
    |--------------------------------------------------------------------------
    | Interview Engine v2 (Versioned, role-aware, culture-aware)
    |--------------------------------------------------------------------------
    | Standalone structured behavioral assessment with 12 questions,
    | 7 dimensions, locale-aware question sets, and v2 scoring.
    | Runs in parallel with v1 — does NOT break existing endpoints.
    */
    'interview_engine_v2' => (bool) env('MARITIME_INTERVIEW_ENGINE_V2', false),
    'behavioral_invite_delay' => (int) env('MARITIME_BEHAVIORAL_INVITE_DELAY', 180), // minutes

    /*
    |--------------------------------------------------------------------------
    | Candidate Scoring Vector v1
    |--------------------------------------------------------------------------
    | Unified scoring vector [technical, behavior, reliability, personality, english]
    */
    'vector_v1' => (bool) env('MARITIME_VECTOR_V1', true),

    /*
    |--------------------------------------------------------------------------
    | Token Enforcement (public candidate endpoints)
    |--------------------------------------------------------------------------
    | When true (default), all public candidate endpoints require ?t=<token>.
    | Set to false for a 24-hour grace period during deploys (frontend → backend).
    */
    'token_enforcement' => (bool) env('MARITIME_TOKEN_ENFORCEMENT', true),

    /*
    |--------------------------------------------------------------------------
    | AIS Verification Engine v1
    |--------------------------------------------------------------------------
    | ais_v1: master switch — all AIS engine code checks this
    | ais_mock: when true → MockAisProvider, when false → HttpAisProvider
    | ais_auto_verify: when true → cron picks up pending contracts automatically
    */
    'ais_v1'          => (bool) env('MARITIME_AIS_V1', false),
    'ais_mock'        => (bool) env('MARITIME_AIS_MOCK', true),
    'ais_auto_verify' => (bool) env('MARITIME_AIS_AUTO_VERIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Sea-Time Intelligence Engine v1
    |--------------------------------------------------------------------------
    | sea_time_v1: master switch — all sea-time engine code checks this
    | sea_time_auto_compute: when true → cron recomputes pending candidates
    */
    'sea_time_v1'           => (bool) env('MARITIME_SEA_TIME_V1', false),
    'sea_time_auto_compute' => (bool) env('MARITIME_SEA_TIME_AUTO_COMPUTE', false),

    /*
    |--------------------------------------------------------------------------
    | Rank & STCW Rule Engine v1
    |--------------------------------------------------------------------------
    | rank_stcw_v1: master switch — technical score, STCW compliance, promotion gap
    | rank_stcw_auto_compute: when true → cron recomputes pending candidates
    */
    'rank_stcw_v1'           => (bool) env('MARITIME_RANK_STCW_V1', false),
    'rank_stcw_auto_compute' => (bool) env('MARITIME_RANK_STCW_AUTO_COMPUTE', false),

    /*
    |--------------------------------------------------------------------------
    | Stability & Risk Engine v1
    |--------------------------------------------------------------------------
    | stability_v1: master switch — stability index, risk score, risk tier
    | stability_auto_compute: when true → cron recomputes pending candidates
    */
    'stability_v1'           => (bool) env('MARITIME_STABILITY_V1', false),
    'stability_auto_compute' => (bool) env('MARITIME_STABILITY_AUTO_COMPUTE', false),

    /*
    |--------------------------------------------------------------------------
    | Stability & Risk Engine v1.1 — Configurable Thresholds
    |--------------------------------------------------------------------------
    | All values previously hardcoded in calculators.
    | No magic numbers remain in engine code.
    */
    'stability' => [
        // ── Contract Pattern Analysis ──
        'short_contract_months'     => 6,       // contracts shorter than this = "short"
        'short_contract_months_by_rank' => [     // rank-aware override (Phase 2)
            'DC'  => 3, 'EC'  => 3,              // cadets: 3-month short threshold
            'OS'  => 4, 'WP'  => 4,              // junior ratings
            'AB'  => 4, 'OL'  => 4, 'MO' => 4,   // ratings
            'BSN' => 5,                           // bosun
            '3/O' => 5, '4/E' => 5,              // junior officers
            '2/O' => 6, '3/E' => 6,              // mid officers (default)
            'C/O' => 8, '2/E' => 8,              // senior officers
            'MASTER' => 9, 'C/E' => 9,           // top officers: 9-month threshold
            'ETO' => 5, 'ELECTRO' => 4,
            'MESS' => 3, 'COOK' => 4, 'CH.COOK' => 5,
            'STEWARD' => 4, 'CH.STEWARD' => 5,
        ],
        'short_ratio_flag_threshold' => 0.6,     // > 60% short → FLAG_SHORT_PATTERN
        'gap_months_flag_threshold'  => 18,       // > 18 months total gap → FLAG_LONG_GAP
        'frequent_switch_flag_threshold' => 6,    // > 6 unique companies in window → FLAG
        'recent_companies_window_years' => 3,     // time window for "recent" companies

        // ── Risk Score Factor Weights (must sum to 1.0) ──
        'factor_weights' => [
            'short_ratio'        => 0.20,   // was 0.25 — reduced, now rank-aware
            'gap_months'         => 0.18,   // was 0.20
            'overlap_count'      => 0.18,   // was 0.20
            'rank_anomaly'       => 0.12,   // was 0.15
            'frequent_switch'    => 0.08,   // was 0.10
            'stability_inverse'  => 0.09,   // was 0.10
            'vessel_diversity'   => 0.05,   // NEW (Phase 5) — positive modifier
            // Reserved weight for temporal + promotion context modifiers
            'temporal_recency'   => 0.10,   // NEW (Phase 4)
        ],

        // ── Normalization Scales ──
        'gap_months_norm_cap'        => 36.0,   // gap months capped at 36 for normalization
        'overlap_count_norm_cap'     => 5.0,    // overlap count capped at 5
        'frequent_switch_norm_cap'   => 8.0,    // recent unique companies capped at 8
        'stability_index_norm_pivot' => 5.0,    // SI ≥ pivot → 0 risk
        'stability_index_neutral'    => 0.5,    // neutral when insufficient data

        // ── Stability Index ──
        'stability_index_min_contracts' => 2,     // min completed contracts for SI
        'stability_index_std_threshold' => 0.001, // σ below this → perfectly stable
        'stability_index_max_cap'       => 10.0,  // max cap for SI

        // ── Risk Tier Boundaries ──
        'risk_tier_thresholds' => [
            'critical' => 0.75,
            'high'     => 0.50,
            'medium'   => 0.25,
        ],

        // ── Rank Progression Anomaly Detection ──
        'unrealistic_promotion_months' => 6,   // multi-level jump in < N months = anomaly
        'unrealistic_promotion_levels' => 2,   // jump of N+ levels triggers anomaly

        // ── Promotion Window Context (Phase 3) ──
        'promotion_window_months'       => 12,  // months before/after expected promotion
        'promotion_penalty_modifier'    => 0.5, // multiply short/switch penalty by this (50%)

        // ── Temporal Decay (Phase 4) ──
        'temporal_decay' => [
            'recent_months'     => 36,   // contracts within 36 months = "recent"
            'old_months'        => 60,   // contracts older than 60 months = "old"
            'recent_weight'     => 1.5,  // weight multiplier for recent contracts
            'old_weight'        => 0.5,  // weight multiplier for old contracts
            'default_weight'    => 1.0,  // weight for contracts in between
        ],

        // ── Vessel Diversity (Phase 5) ──
        'vessel_diversity' => [
            'min_types_for_bonus'       => 2,   // need 2+ types for positive modifier
            'max_types_for_bonus'       => 5,   // diminishing returns above this
            'min_tenure_months'         => 6,   // must have tenure per type for bonus
            'max_score'                 => 1.0, // max vessel diversity score (0-1)
        ],

        // ── Fleet Profiles (Phase 6) ──
        'fleet_profiles' => [
            'tanker' => [
                'short_contract_months' => 5,
                'factor_weights' => [
                    'short_ratio'   => 0.15,
                    'gap_months'    => 0.20,
                    'overlap_count' => 0.18,
                ],
            ],
            'bulk' => [
                'short_contract_months' => 7,
            ],
            'container' => [
                'short_contract_months' => 6,
                'factor_weights' => [
                    'frequent_switch' => 0.12,
                ],
            ],
            'river' => [
                'short_contract_months' => 3,
                'risk_tier_thresholds' => [
                    'critical' => 0.80,
                    'high'     => 0.60,
                    'medium'   => 0.30,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Pack v1
    |--------------------------------------------------------------------------
    | compliance_v1: master switch — unified compliance score, status, PDF report
    | compliance_auto_compute: when true → cron recomputes pending candidates
    */
    'compliance_v1'           => (bool) env('MARITIME_COMPLIANCE_V1', false),
    'compliance_auto_compute' => (bool) env('MARITIME_COMPLIANCE_AUTO_COMPUTE', false),

    /*
    |--------------------------------------------------------------------------
    | Executive Summary v1
    |--------------------------------------------------------------------------
    | exec_summary_v1: master switch — executive summary in candidate show()
    | exec_summary_override_v1: enable manual decision override endpoint
    | exec_summary_confidence_stale_days: engine data older than N days = stale
    | exec_summary_thresholds: decision resolver thresholds
    */
    'exec_summary_v1'          => (bool) env('MARITIME_EXEC_SUMMARY_V1', false),
    'exec_summary_override_v1' => (bool) env('MARITIME_EXEC_SUMMARY_OVERRIDE_V1', false),
    'exec_summary_confidence_stale_days' => (int) env('MARITIME_EXEC_SUMMARY_STALE_DAYS', 14),
    'exec_summary_thresholds' => [
        'technical_review_below' => (float) env('MARITIME_EXEC_TECH_REVIEW_BELOW', 0.4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Competency Engine v1
    |--------------------------------------------------------------------------
    | competency_v1: master switch — rubric-based competency scoring
    | competency_auto_compute: when true → cron recomputes pending candidates
    */
    'competency_v1'           => (bool) env('MARITIME_COMPETENCY_V1', false),
    'competency_auto_compute' => (bool) env('MARITIME_COMPETENCY_AUTO_COMPUTE', false),

    'competency' => [
        // Dimension weights (must sum to 1.0)
        'dimension_weights' => [
            'DISCIPLINE'      => 0.20,
            'LEADERSHIP'      => 0.15,
            'STRESS'          => 0.15,
            'TEAMWORK'        => 0.20,
            'COMMS'           => 0.15,
            'TECH_PRACTICAL'  => 0.15,
        ],

        // Flag thresholds: dimension score below this → flag triggered
        'flag_thresholds' => [
            'low_discipline'          => 40, // dimension score < 40 → flag
            'poor_teamwork'           => 40,
            'high_stress_risk'        => 35,
            'communication_gap'       => 40,
            'safety_mindset_missing'  => 30, // critical
            'leadership_risk'         => 35,
        ],

        // Flag → dimension mapping
        'flag_dimension_map' => [
            'low_discipline'          => 'DISCIPLINE',
            'poor_teamwork'           => 'TEAMWORK',
            'high_stress_risk'        => 'STRESS',
            'communication_gap'       => 'COMMS',
            'safety_mindset_missing'  => 'DISCIPLINE', // cross-cutting safety
            'leadership_risk'         => 'LEADERSHIP',
        ],

        // Critical flags that can trigger REJECT in exec summary
        'critical_flags' => ['safety_mindset_missing'],

        // Scoring
        'max_score_per_question' => 5,
        'minimum_answer_length'  => 10,  // chars; below this → score 0
        'minimum_examples'       => 0,   // v1: no example requirement

        // Executive summary integration
        'review_threshold'       => 45,  // competency_score < this → at least REVIEW
        'reject_on_critical_flag' => true,

        // Status mapping
        'status_thresholds' => [
            'strong'   => 70,  // score >= 70 → strong
            'moderate' => 45,  // score >= 45 → moderate
            // below 45 → weak
        ],

        /*
        |----------------------------------------------------------------------
        | Multi-language keyword sets for deterministic scoring
        |----------------------------------------------------------------------
        | Per-dimension keywords (EN + TR). Scorer detects language and uses
        | the matching set. Stems are preferred so agglutinative Turkish
        | forms match via str_contains().
        */
        'keywords' => [
            'DISCIPLINE' => [
                'en' => ['ism', 'solas', 'safety', 'procedure', 'checklist', 'drill', 'emergency', 'ppe', 'risk assessment', 'permit to work', 'near-miss', 'near miss', 'compliance', 'regulation', 'muster', 'protocol'],
                'tr' => ['disiplin', 'talimat', 'emir', 'hiyerarşi', 'kurallara', 'rapor', 'kayıt', 'vardiya', 'zabit', 'ast', 'üst', 'denetim', 'kontrol', 'uyarı', 'tutanak', 'emniyet', 'güvenlik', 'iş güvenliği', 'kişisel koruyucu', 'kask', 'eldiven', 'gözlük', 'prosedür', 'talimat', 'kontrol listesi', 'izin formu', 'permit', 'toolbox', 'risk analizi', 'işbaşı', 'isps', 'kaza', 'ramak kala'],
            ],
            'LEADERSHIP' => [
                'en' => ['delegate', 'coordinate', 'mentor', 'brief', 'debrief', 'training', 'competency', 'assessment', 'feedback', 'decision', 'strategy', 'plan', 'priority', 'resource', 'evaluate'],
                'tr' => ['lider', 'yönetim', 'yönetici', 'karar', 'strateji', 'plan', 'öncelik', 'kaynak', 'yetki devri', 'koordin', 'danışman', 'mentor', 'değerlendirme', 'geri bildirim', 'sorumluluk', 'inisiyatif', 'hedef', 'vizyon', 'yönlendirme'],
            ],
            'STRESS' => [
                'en' => ['stress', 'pressure', 'calm', 'priority', 'crisis', 'emergency', 'alarm', 'plan', 'procedure', 'risk', 'composure', 'focus', 'resilience', 'workload', 'fatigue'],
                'tr' => ['stres', 'baskı', 'panik', 'sakin', 'soğukkanlı', 'öncelik', 'kriz', 'acil', 'acil durum', 'yangın', 'alarm', 'plan', 'prosedür', 'risk', 'odak', 'dayanıklılık', 'yoğunluk', 'yorgunluk'],
            ],
            'TEAMWORK' => [
                'en' => ['team', 'collaborate', 'cooperate', 'support', 'assist', 'conflict', 'mediate', 'common', 'coordinate', 'morale', 'motivation', 'share', 'together', 'crew', 'bridge team'],
                'tr' => ['ekip', 'takım', 'uyum', 'işbirliği', 'yardımlaşma', 'çatışma', 'arabulucu', 'ortak', 'koordinasyon', 'moral', 'motivasyon', 'paylaşmak', 'birlikte', 'mürettebat', 'dayanışma'],
            ],
            'COMMS' => [
                'en' => ['smcp', 'brm', 'closed-loop', 'closed loop', 'gmdss', 'sitrep', 'report', 'log', 'document', 'brief', 'debrief', 'communicate', 'confirm', 'repeat', 'acknowledge'],
                'tr' => ['net', 'açık', 'sade', 'anlaşılır', 'geri bildirim', 'teyit', 'repeat back', 'anladın mı', 'toplantı', 'briefing', 'debrief', 'iletişim', 'dinlemek', 'soru sormak', 'bildirim', 'rapor', 'kayıt', 'belge'],
            ],
            'TECH_PRACTICAL' => [
                'en' => ['troubleshoot', 'diagnos', 'maintenance', 'pms', 'inspection', 'spare part', 'calibrat', 'test', 'bridge', 'engine room', 'cargo', 'ballast', 'anchor', 'mooring', 'navigation', 'maneuver'],
                'tr' => ['arıza', 'bakım', 'onarım', 'test', 'kontrol', 'seyir', 'manevra', 'navigasyon', 'radar', 'ecdis', 'jeneratör', 'pompa', 'valf', 'basınç', 'yakıt', 'balast', 'kargo', 'tank', 'dwt', 'groston', 'evrak', 'psc', 'denetim', 'yedek parça', 'kalibrasyon'],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Structure / Ownership / Outcome markers (multi-language)
        |----------------------------------------------------------------------
        | Used by the additive rubric: base(length) + example + dimension + ownership
        */
        'markers' => [
            'example' => [
                'en' => ['example', 'for instance', 'one time', 'during', 'incident', 'situation', 'result', 'lesson', 'learned', 'solution', 'resolved', 'applied'],
                'tr' => ['örnek', 'mesela', 'bir keresinde', 'bir seferinde', 'olay', 'durum', 'sonuç', 'ders', 'öğrendim', 'çözüm', 'aksiyon', 'uyguladım', 'yaşadığım', 'tecrübe'],
            ],
            'ownership' => [
                'en' => ['i decided', 'i took', 'my responsibility', 'i led', 'i ensured', 'i managed', 'i coordinated', 'i reported', 'i initiated'],
                'tr' => ['sorumluluk', 'inisiyatif', 'karar verdim', 'uyguladım', 'yönettim', 'bildirdim', 'başlattım', 'üstlendim', 'organize ettim'],
            ],
            'outcome' => [
                'en' => ['as a result', 'consequently', 'therefore', 'we achieved', 'reduced', 'prevented', 'improved', 'resolved', 'succeeded', 'outcome'],
                'tr' => ['sonuçta', 'böylece', 'netice', 'başardık', 'azaldı', 'önledik', 'düzelttik', 'iyileştirdik', 'sağlandı', 'kazandık', 'elde ettik'],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Language detection: Turkish stopwords for heuristic
        |----------------------------------------------------------------------
        */
        'tr_stopwords' => ['ve', 'bir', 'bu', 'için', 'ile', 'ama', 'çünkü', 'gibi', 'olarak', 'olan', 'ancak', 'hem', 'çok', 'daha', 'olan'],
        'tr_chars'     => ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'],

        /*
        |----------------------------------------------------------------------
        | Technical Depth Layer v1 — rank-specific keyword packs
        |----------------------------------------------------------------------
        | Senior ranks (Master, Chief Engineer, C/O) answering with real
        | maritime technical terms get extra credit via technical_depth_index.
        */
        'technical_depth' => [
            // Rank-specific keyword packs — only applied when candidate rank matches
            'rank_packs' => [
                'MASTER' => [
                    'NAVIGATION' => ['colreg', 'passage plan', 'ecdis', 'radar plotting', 'bridge team', 'pilotage', 'maneuvering', 'manoeuv', 'gyro', 'chart correction'],
                    'COMPLIANCE' => ['ism', 'isps', 'psc', 'sms', 'flag inspection', 'audit', 'vetting', 'sire', 'class survey'],
                    'EMERGENCY'  => ['drill', 'abandon ship', 'fire plan', 'damage control', 'oil spill', 'collision response', 'contingency', 'mustering'],
                ],
                'CHIEF_ENG' => [
                    'ENGINE' => ['purifier', 'separator', 'main engine overhaul', 'auxiliary engine', 'fuel system', 'boiler', 'turbocharger', 'crankshaft', 'piston', 'cylinder liner', 'fuel injection'],
                    'SAFETY' => ['enclosed space', 'loto', 'permit to work', 'hot work', 'gas free', 'confined space', 'lock-out tag-out'],
                ],
                'CHIEF_MATE' => [
                    'NAVIGATION' => ['colreg', 'passage plan', 'ecdis', 'radar plotting', 'bridge team', 'pilotage', 'maneuvering'],
                    'CARGO' => ['loading plan', 'cargo securing', 'ballast exchange', 'stability', 'draft survey', 'lashing', 'imdg', 'dangerous goods'],
                    'COMPLIANCE' => ['ism', 'isps', 'psc', 'sms', 'safety meeting', 'audit'],
                ],
            ],

            // Scoring rules
            'min_signals_for_bonus' => 3,     // Need 3+ terms from primary category to trigger bonus
            'secondary_bonus_rule' => [2, 1], // 2 terms from secondary + 1 from tertiary = +10 bonus
            'total_signals_for_cap' => 5,     // 5+ total technical signals → cap dimension at 85
            'primary_bonus_score' => 70,      // TECH_PRACTICAL >= 70 if primary category hits threshold
            'secondary_bonus_points' => 10,   // +10 bonus for cross-category depth
            'cap_score' => 85,                // Max dimension cap when total signals high
            'phrase_weight' => 2,             // Multi-word phrases count as 2 signals

            // Ranks that are EXCLUDED from technical depth bonus
            'excluded_role_scopes' => ['AB', 'OILER', 'COOK', 'ALL'],

            // Technical depth index weights
            'depth_index_weights' => [
                'keyword_density' => 0.40,     // Proportion of matched keywords
                'category_diversity' => 0.35,  // How many categories hit (nav + compliance + emergency)
                'specificity' => 0.25,         // Phrase vs single-word ratio
            ],

            // ── Anti-inflation guardrails (v1.1) ──────────────────
            // depth_index below this → no TECH_PRACTICAL boost at all
            'depth_boost_min_index' => 40,

            // depth_index tier → max TECH_PRACTICAL allowed after boost
            // Checked in descending order; first match wins.
            'depth_boost_cap_tiers' => [
                75 => 85,    // depth >= 75 → cap at 85
                60 => 75,    // depth 60-74 → cap at 75
                40 => 60,    // depth 40-59 → cap at 60
            ],

            // Max total competency score uplift from depth (points)
            'max_total_score_uplift' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Engine Correlation Layer v1
    |--------------------------------------------------------------------------
    | correlation_v1: master switch — cross-engine pattern detection
    | Thresholds for each behavioral pattern detector.
    */
    'correlation_v1' => (bool) env('MARITIME_CORRELATION_V1', false),

    'correlation' => [
        // Pattern 1: Expert but Unstable
        'expert_depth_threshold'     => 75,    // depth_index >= this = "expert"
        'stability_low_threshold'    => 4.0,   // SI < this = "unstable"

        // Pattern 2: Safe but Inexperienced
        'compliance_high_threshold'  => 80,    // compliance >= this = "compliant"
        'sea_time_low_days_threshold' => 365,  // merged_total_days < this = "low experience"

        // Pattern 3: Stable but Technically Weak
        'stability_high_threshold'   => 7.0,   // SI >= this = "stable"
        'depth_weak_threshold'       => 50,    // depth_index < this = "weak"

        // Pattern 4: High Risk but Strong Skill
        'risk_high_threshold'        => 0.6,   // risk_score >= this = "high risk"
        'depth_skill_threshold'      => 70,    // depth_index >= this = "skilled"
    ],

    /*
    |--------------------------------------------------------------------------
    | Fleet / Company Calibration v1
    |--------------------------------------------------------------------------
    | Per-fleet-type overrides for decision thresholds and dimension weights.
    | Resolved by CalibrationConfig (fleet profile → base config fallback).
    | Company-level override hook deferred to v2 (DB-backed).
    */
    'calibration' => [
        'fleet_profiles' => [
            'tanker' => [
                'competency' => [
                    'review_threshold' => 50,              // ↑ stricter: score < 50 → review (default=45)
                    'reject_on_critical_flag' => true,
                    'dimension_weights' => [
                        'DISCIPLINE'     => 0.25,          // ↑ safety emphasis
                        'LEADERSHIP'     => 0.15,
                        'STRESS'         => 0.15,
                        'TEAMWORK'       => 0.15,
                        'COMMS'          => 0.15,
                        'TECH_PRACTICAL' => 0.15,
                    ],
                ],
                'exec_summary_thresholds' => [
                    'technical_review_below' => 0.5,       // ↑ higher technical bar
                ],
                'correlation' => [
                    'compliance_high_threshold' => 85,     // ↑ stricter compliance
                ],
                'predictive' => [
                    'review_threshold' => 55,              // ↑ stricter predictive review
                    'confirm_threshold' => 70,             // ↑ stricter confirmation
                ],
            ],
            'bulk' => [
                // Balanced — mostly defaults, only minor tuning
                'competency' => [
                    'review_threshold' => 45,              // same as default
                ],
            ],
            'container' => [
                'competency' => [
                    'dimension_weights' => [
                        'DISCIPLINE'     => 0.15,
                        'LEADERSHIP'     => 0.10,
                        'STRESS'         => 0.20,          // ↑ fast-paced ops
                        'TEAMWORK'       => 0.15,
                        'COMMS'          => 0.25,          // ↑ port coordination
                        'TECH_PRACTICAL' => 0.15,
                    ],
                ],
            ],
            'river' => [
                'competency' => [
                    'dimension_weights' => [
                        'DISCIPLINE'     => 0.15,
                        'LEADERSHIP'     => 0.10,
                        'STRESS'         => 0.10,
                        'TEAMWORK'       => 0.30,          // ↑ smaller crews
                        'COMMS'          => 0.20,          // ↑ close-quarters
                        'TECH_PRACTICAL' => 0.15,
                    ],
                ],
                'correlation' => [
                    'stability_high_threshold' => 6.0,     // ↓ more forgiving
                    'sea_time_low_days_threshold' => 180,  // ↓ shorter expected sea time
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Predictive Maritime Risk Model v1
    |--------------------------------------------------------------------------
    | predictive_v1: master switch — predictive risk index computation
    | predictive_auto_compute: when true → cron recomputes eligible candidates
    */
    'predictive_v1'           => (bool) env('MARITIME_PREDICTIVE_V1', false),
    'predictive_auto_compute' => (bool) env('MARITIME_PREDICTIVE_AUTO_COMPUTE', false),

    /*
    |--------------------------------------------------------------------------
    | Vessel Risk Map v1
    |--------------------------------------------------------------------------
    | vessel_risk_v1: master switch — vessel-level risk aggregation from crew profiles
    */
    'vessel_risk_v1' => (bool) env('MARITIME_VESSEL_RISK_V1', false),

    /*
    |--------------------------------------------------------------------------
    | Decision Panel v1
    |--------------------------------------------------------------------------
    | Cert alias map normalizes variant spellings from source_meta.certificates.
    | Phase whitelist controls which phase keys are accepted for review.
    */
    'decision_panel' => [
        'cert_aliases' => [
            'g.o.c'       => 'goc',
            'g.o.c.'      => 'goc',
            'goc_cert'    => 'goc',
            'b.r.m'       => 'brm',
            'b.r.m.'      => 'brm',
            'brm_cert'    => 'brm',
            'a.r.p.a'     => 'arpa',
            'a.r.p.a.'    => 'arpa',
            'arpa_cert'   => 'arpa',
            'e.c.d.i.s'   => 'ecdis',
            'ecdis_cert'  => 'ecdis',
            's.t.c.w'     => 'stcw',
            'stcw_cert'   => 'stcw',
            'c.o.c'       => 'coc',
            'c.o.c.'      => 'coc',
            'coc_cert'    => 'coc',
            'seaman_book' => 'seamans_book',
            'seamanbook'  => 'seamans_book',
            'seamansbook' => 'seamans_book',
            'medical_cert'=> 'medical',
            'med'         => 'medical',
        ],
        'phase_whitelist' => [
            'standard_competency',
            'phase1_identity',
            'phase2_command',
        ],
    ],

    'predictive' => [
        // Blend weights (must sum to 1.0)
        'blend_weights' => [
            'current_risk' => 0.45,
            'trend'        => 0.35,
            'correlation'  => 0.20,
        ],

        // Tier boundaries
        'tier_boundaries' => [
            'critical' => 75,
            'high'     => 60,
            'medium'   => 40,
        ],

        // Policy thresholds
        'review_threshold'  => 60,   // predictive_risk_index >= this → REVIEW
        'confirm_threshold' => 75,   // predictive_risk_index >= this → REQUIRE_CONFIRMATION

        // Snapshot window for trend analysis (months)
        'snapshot_window' => 6,

        // Pattern point overrides
        'escalating_instability_points' => 20,
        'escalating_instability_min_increase' => 0.10,
        'switching_acceleration_points' => 20,
        'gap_growth_points' => 15,
        'gap_growth_min_months' => 3,
        'promotion_pressure_points' => 15,
        'compliance_drift_points' => 25,
        'compliance_drift_min_drop' => 10,
        'behavioral_technical_mismatch_points' => 10,
        'behavioral_technical_min_divergence' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolver Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'low_confidence_threshold'    => 0.65,
        'multi_class_delta_threshold' => 0.08,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-class Blending
    |--------------------------------------------------------------------------
    | When delta between top-2 classes < multi_class_delta_threshold,
    | blend scenarios: primary slots 1-6, secondary slots 7-8.
    */
    'blending' => [
        'primary'   => 6,
        'secondary' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Disposable Email Domain Denylist
    |--------------------------------------------------------------------------
    | Domains listed here will be blocked during maritime apply.
    | Add more domains as spam patterns emerge.
    */
    /*
    |--------------------------------------------------------------------------
    | Crew Synergy Engine V2
    |--------------------------------------------------------------------------
    | 4-pillar crew compatibility scoring: captain fit, team balance,
    | vessel fit, operational risk. Feature-flagged for parallel rollout.
    */
    'crew_synergy_v2' => (bool) env('CREW_SYNERGY_ENGINE_V2', false),

    'synergy_v2' => [
        'captain_style_thresholds' => [
            'authoritative' => ['DISCIPLINE_COMPLIANCE' => 70, 'CONFLICT_RISK' => 60],
            'collaborative' => ['TEAM_COOPERATION' => 70, 'COMM_CLARITY' => 65],
            'adaptive'      => ['STRESS_CONTROL' => 65, 'LEARNING_GROWTH' => 65],
        ],
        'team_balance_ideal_std_range' => [8, 25],
        'component_weights' => [
            'captain_fit'      => 0.25,
            'team_balance'     => 0.20,
            'vessel_fit'       => 0.30,
            'operational_risk' => 0.25,
        ],
        'cert_expiry_warning_days' => 90,
        'early_termination_threshold_ratio' => 0.3,
        'cache_ttl_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Synergy V2 — Role-Based Weight Map (respect/discipline)
    |--------------------------------------------------------------------------
    | Per-role behavioral dimension weights for crew synergy scoring.
    | Used by RoleWeightMap service. Tenant overrides via feature flag payload.
    */
    'synergy_weights' => [
        'version' => '2026-02-23',

        'missing_dimension_default_score' => 0.5,

        'normalization' => [
            'sum_to_1' => true,
        ],

        'defaults' => [
            'weights' => [
                'respect' => 0.14,
                'discipline' => 0.14,
                'communication' => 0.14,
                'initiative' => 0.12,
                'conflict_handling' => 0.12,
                'teamwork' => 0.18,
                'stress_tolerance' => 0.16,
            ],
        ],

        'roles' => [
            'master' => [
                'weights' => [
                    'respect' => 0.22,
                    'discipline' => 0.18,
                    'communication' => 0.16,
                    'initiative' => 0.10,
                    'conflict_handling' => 0.10,
                    'teamwork' => 0.14,
                    'stress_tolerance' => 0.10,
                ],
            ],
            'chief_officer' => [
                'weights' => [
                    'respect' => 0.18,
                    'discipline' => 0.18,
                    'communication' => 0.18,
                    'initiative' => 0.10,
                    'conflict_handling' => 0.10,
                    'teamwork' => 0.14,
                    'stress_tolerance' => 0.12,
                ],
            ],
            'chief_engineer' => [
                'weights' => [
                    'respect' => 0.18,
                    'discipline' => 0.22,
                    'communication' => 0.14,
                    'initiative' => 0.12,
                    'conflict_handling' => 0.10,
                    'teamwork' => 0.12,
                    'stress_tolerance' => 0.12,
                ],
            ],
            'able_seaman' => [
                'weights' => [
                    'respect' => 0.14,
                    'discipline' => 0.16,
                    'communication' => 0.12,
                    'initiative' => 0.10,
                    'conflict_handling' => 0.10,
                    'teamwork' => 0.22,
                    'stress_tolerance' => 0.16,
                ],
            ],
        ],
    ],

    'disposable_email_domains' => [
        'tempmail.com', 'temp-mail.org', 'temp-mail.io',
        '10minutemail.com', '10minutemail.net',
        'guerrillamail.com', 'guerrillamail.net', 'guerrillamail.org',
        'mailinator.com', 'mailinator.net',
        'throwaway.email', 'throwaway.com',
        'yopmail.com', 'yopmail.fr', 'yopmail.net',
        'sharklasers.com', 'guerrillamailblock.com',
        'grr.la', 'dispostable.com', 'trashmail.com', 'trashmail.net',
        'maildrop.cc', 'mailnesia.com', 'tempail.com',
        'fakeinbox.com', 'mailcatch.com', 'tempr.email',
        'discard.email', 'getnada.com', 'emailondeck.com',
        'mohmal.com', 'minutemail.com',
        'burnermail.io', 'inboxbear.com', 'mytemp.email',
        'tempmailaddress.com', 'tempinbox.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Clean Recruitment Workflow v1
    |--------------------------------------------------------------------------
    | Separates application (factual data) from behavioral interview.
    | When enabled: application collects data only, then a dedicated invitation
    | system gates the behavioral interview with a 48-hour expiring token.
    */
    'clean_workflow_v1' => (bool) env('MARITIME_CLEAN_WORKFLOW_V1', false),

    /*
    |--------------------------------------------------------------------------
    | Immediate OTP Verification v1
    |--------------------------------------------------------------------------
    | When enabled: application form → instant 6-digit OTP email → verify → interview.
    | Replaces the delayed interview invitation flow (clean_workflow_v1).
    | Takes priority over clean_workflow_v1 when both are true.
    */
    'immediate_verification_v1' => (bool) env('MARITIME_IMMEDIATE_VERIFICATION_V1', false),

    /*
    |--------------------------------------------------------------------------
    | Vessel Requirement Engine v1
    |--------------------------------------------------------------------------
    | When enabled, CandidateDecisionService uses vessel-type requirement
    | profiles (cert fit, experience fit, behavior fit, availability fit)
    | instead of the legacy hardcoded 4-pillar weights.
    */
    'vessel_requirement_engine_v1' => (bool) env('VESSEL_REQUIREMENT_ENGINE_V1', false),
    'interview_invite_delay_minutes' => (int) env('MARITIME_INTERVIEW_INVITE_DELAY_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Role Question Bank v1
    |--------------------------------------------------------------------------
    | 25-question per role interview bank: 12 CORE + 6 ROLE + 4 DEPT + 3 ENGLISH.
    | Feature-flagged for gradual rollout.
    */
    'question_bank_v1' => (bool) env('MARITIME_QUESTION_BANK_V1', false),

    'question_bank' => [
        'source_path'   => storage_path('app/question_bank'),
        'cache_ttl'     => (int) env('QUESTION_BANK_CACHE_TTL', 3600),
        'default_locales' => ['en', 'tr'],
    ],

    /*
    |--------------------------------------------------------------------------
    | English Speaking Gate
    |--------------------------------------------------------------------------
    | Voice-based English assessment (3 prompts, 4 criteria each scored 0-5).
    | CEFR mapping and role minimums loaded from ENGLISH_GATE_v1.json.
    */
    'english_gate' => [
        'enabled' => (bool) env('MARITIME_ENGLISH_GATE_ENABLED', false),
        'max_prompts' => 3,
        'criteria' => ['fluency', 'clarity', 'accuracy', 'safety_vocabulary'],
        'max_score_per_criterion' => 5,
        'confidence_base' => 0.85,
        'confidence_floor' => 0.30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Fit Engine v1 — Configurable Thresholds
    |--------------------------------------------------------------------------
    | All values previously hardcoded in RoleFitEngine.
    | No magic numbers remain in engine code.
    */
    /*
    |--------------------------------------------------------------------------
    | Role-Fit Alerting
    |--------------------------------------------------------------------------
    | Fires a Slack/Telegram webhook when mismatch% crosses threshold.
    | Cooldown prevents alert storms.
    */
    'role_fit_alerts' => [
        'enabled'                  => (bool) env('ROLE_FIT_ALERTS_ENABLED', false),
        'window_hours'             => (int) env('ROLE_FIT_ALERTS_WINDOW_HOURS', 24),
        'mismatch_pct_threshold'   => (float) env('ROLE_FIT_ALERTS_MISMATCH_PCT', 30.0),
        'min_total_evaluations'    => (int) env('ROLE_FIT_ALERTS_MIN_TOTAL', 20),
        'cooldown_minutes'         => (int) env('ROLE_FIT_ALERTS_COOLDOWN_MIN', 120),
        'channel'                  => env('ROLE_FIT_ALERTS_CHANNEL', 'slack'),
        'webhook_url'              => env('ROLE_FIT_ALERTS_WEBHOOK_URL', ''),
    ],

    'role_fit' => [
        // Mismatch level thresholds
        'mismatch_strong_min_flags' => 3,       // flag count >= this → strong mismatch
        'cross_dept_gap_strong'     => 0.15,    // cross-dept score gap > this → strong mismatch
        'fit_score_strong_below'    => 0.25,    // fit score < this → strong mismatch
        'fit_score_weak_below'      => 0.40,    // fit score < this → weak mismatch

        // Suggestion limits
        'max_suggestions'           => 3,       // max adjacent role suggestions returned

        // Cache settings
        'cache_ttl_seconds'         => 600,     // 10 minutes for roles + DNA lookups

        // Relevance weights (behavioral dimension importance)
        'relevance_weight' => [
            'critical' => 1.0,
            'high'     => 0.75,
            'moderate' => 0.50,
            'low'      => 0.25,
        ],

        // Relevance thresholds (below = mismatch signal)
        'relevance_threshold' => [
            'critical' => 0.40,
            'high'     => 0.30,
            'moderate' => 0.20,
            'low'      => 0.10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Gateway (Deepgram streaming dictation)
    |--------------------------------------------------------------------------
    | Shared HMAC secret with AiModelsPanel voice gateway.
    */
    'voice_gateway_secret' => env('VOICE_GATEWAY_SECRET', ''),
];
