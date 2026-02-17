<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interview Configuration
    |--------------------------------------------------------------------------
    */

    'token_expiry_hours' => env('INTERVIEW_TOKEN_EXPIRY_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags (v1.0: text-only, v2: voice/video)
    |--------------------------------------------------------------------------
    */

    'voice_enabled' => env('VOICE_INTERVIEW_ENABLED', false),
    'video_enabled' => env('VIDEO_INTERVIEW_ENABLED', false),

    'max_duration_minutes' => env('INTERVIEW_MAX_DURATION_MINUTES', 45),

    'default_question_time' => env('INTERVIEW_DEFAULT_QUESTION_TIME', 180),

    'default_questions_count' => 10,

    'question_types' => [
        'technical' => [
            'name' => 'Teknik',
            'default_count' => 4,
        ],
        'behavioral' => [
            'name' => 'Davranissal',
            'default_count' => 3,
        ],
        'scenario' => [
            'name' => 'Senaryo',
            'default_count' => 2,
        ],
        'culture' => [
            'name' => 'Kultur Uyumu',
            'default_count' => 1,
        ],
    ],

    'scoring' => [
        'min' => 0,
        'max' => 5,
        'pass_threshold' => 3,
    ],

    'recommendations' => [
        'hire' => [
            'min_score' => 70,
            'max_red_flags' => 0,
        ],
        'hold' => [
            'min_score' => 50,
            'max_red_flags' => 1,
        ],
        'reject' => [
            'below_score' => 50,
        ],
    ],

];
