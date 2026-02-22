<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Weights
    |--------------------------------------------------------------------------
    | Score blending weights. Must sum to 1.0.
    | When a signal is missing, remaining weights are re-normalized.
    */
    'weights' => [
        'mcq'       => (float) env('LANG_WEIGHT_MCQ', 0.45),
        'writing'   => (float) env('LANG_WEIGHT_WRITING', 0.35),
        'interview' => (float) env('LANG_WEIGHT_INTERVIEW', 0.20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Specific Weight Overrides
    |--------------------------------------------------------------------------
    | Command roles (Captain, Chief Officer, Chief Engineer) need stronger
    | productive skills — writing weight goes up, MCQ down.
    */
    'role_weights' => [
        'command' => [
            'mcq'       => 0.30,
            'writing'   => 0.45,
            'interview' => 0.25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Rank Codes
    |--------------------------------------------------------------------------
    | Ranks that trigger command-role weight overrides.
    */
    'command_ranks' => [
        'MASTER', 'C/O', 'C/E',
        'captain', 'chief_officer', 'chief_engineer',
        'chief officer', 'chief engineer',
    ],

    /*
    |--------------------------------------------------------------------------
    | CEFR Level Cutoffs
    |--------------------------------------------------------------------------
    | overall_score → estimated_level mapping.
    */
    'level_cutoffs' => [
        ['min' =>  0, 'max' => 24, 'level' => 'A1'],
        ['min' => 25, 'max' => 39, 'level' => 'A2'],
        ['min' => 40, 'max' => 59, 'level' => 'B1'],
        ['min' => 60, 'max' => 74, 'level' => 'B2'],
        ['min' => 75, 'max' => 89, 'level' => 'C1'],
        ['min' => 90, 'max' => 100, 'level' => 'C2'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Writing-Based Level Caps
    |--------------------------------------------------------------------------
    | Prevents inflated levels from MCQ-only performance.
    | Evaluated in order; first matching rule wins.
    */
    'writing_level_caps' => [
        ['writing_max' => 30, 'cap' => 'A2'],
        ['writing_max' => 45, 'cap' => 'B1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Confidence Penalties
    |--------------------------------------------------------------------------
    */
    'confidence' => [
        'base'                 => 0.80,
        'floor'                => 0.30,
        'writing_short_words'  => 60,   // word count threshold
        'writing_short_penalty'=> 0.15,
        'mcq_writing_mismatch' => [
            'mcq_min'       => 75,
            'writing_max'   => 45,
            'penalty'       => 0.20,
        ],
        'declared_gap_levels'  => 2,    // gap threshold
        'declared_gap_penalty' => 0.10,
        'no_interview_penalty' => 0.05,
        'no_interview_command_penalty' => 0.10, // higher for command roles
    ],

    /*
    |--------------------------------------------------------------------------
    | Question Bank
    |--------------------------------------------------------------------------
    */
    'question_bank' => [
        'path'            => 'templates/maritime/language_mcq_en_v1.json',
        'select_count'    => 12,
        'time_limit_minutes' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Writing Score Estimation (word-count heuristic)
    |--------------------------------------------------------------------------
    | Used as placeholder until AI-assist or manual rubric grading.
    */
    'writing_estimate' => [
        ['max_words' => 20, 'score' => 15],
        ['max_words' => 40, 'score' => 30],
        ['max_words' => 60, 'score' => 45],
        ['max_words' => 80, 'score' => 55],
        ['max_words' => 100, 'score' => 65],
        ['max_words' => PHP_INT_MAX, 'score' => 70],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retake Policy
    |--------------------------------------------------------------------------
    */
    'retake' => [
        'max_per_30_days' => (int) env('LANG_MAX_RETAKES_30D', 2),
        'locked_blocks_retake' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Based English Requirements (Phase B)
    |--------------------------------------------------------------------------
    | Min CEFR level and English question profile per maritime rank.
    | profile maps to ROLE_ENGLISH_PROFILES in LanguageAssessmentService.
    */
    'role_english_requirements' => [
        'captain'         => ['min_level' => 'B2', 'profile' => 'command'],
        'chief_officer'   => ['min_level' => 'B2', 'profile' => 'command'],
        'chief_engineer'  => ['min_level' => 'B1', 'profile' => 'command'],
        'second_officer'  => ['min_level' => 'B1', 'profile' => 'officers'],
        'third_officer'   => ['min_level' => 'B1', 'profile' => 'officers'],
        'second_engineer' => ['min_level' => 'B1', 'profile' => 'engine'],
        'third_engineer'  => ['min_level' => 'A2', 'profile' => 'engine'],
        'bosun'           => ['min_level' => 'A2', 'profile' => 'deck_ratings'],
        'able_seaman'     => ['min_level' => 'A2', 'profile' => 'deck_ratings'],
        'ordinary_seaman' => ['min_level' => 'A1', 'profile' => 'deck_ratings'],
        'motorman'        => ['min_level' => 'A2', 'profile' => 'engine'],
        'oiler'           => ['min_level' => 'A1', 'profile' => 'engine'],
        'electrician'     => ['min_level' => 'B1', 'profile' => 'engine'],
        'cook'            => ['min_level' => 'A1', 'profile' => 'deck_ratings'],
        'steward'         => ['min_level' => 'A2', 'profile' => 'deck_ratings'],
        'messman'         => ['min_level' => 'A1', 'profile' => 'deck_ratings'],
        'deck_cadet'      => ['min_level' => 'B1', 'profile' => 'officers'],
        'engine_cadet'    => ['min_level' => 'A2', 'profile' => 'engine'],
    ],

];
