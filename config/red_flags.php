<?php

/**
 * Red Flag Action Mapping Configuration
 *
 * Maps red flag codes to categories, severity levels, and recommended actions.
 * This config is the single source of truth for red flag handling.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Red Flag Definitions
    |--------------------------------------------------------------------------
    |
    | Each flag has:
    | - category: hygiene, performance, behavior
    | - severity: high, medium, low
    | - actions: array of action codes to recommend
    |
    */

    'flags' => [
        // Hygiene & Safety (H)
        'RF-H1' => [
            'category' => 'hygiene',
            'severity' => 'high',
            'label_key' => 'redFlags.RF-H1.label',
            'actions' => ['reject', 'not_suitable_for_food_roles'],
        ],
        'RF-H2' => [
            'category' => 'hygiene',
            'severity' => 'medium',
            'label_key' => 'redFlags.RF-H2.label',
            'actions' => ['second_interview', 'hygiene_scenario'],
        ],
        'RF-H3' => [
            'category' => 'hygiene',
            'severity' => 'low',
            'label_key' => 'redFlags.RF-H3.label',
            'actions' => ['conditional_hire', 'hygiene_training'],
        ],

        // Performance & Competency (P)
        'RF-P1' => [
            'category' => 'performance',
            'severity' => 'medium',
            'label_key' => 'redFlags.RF-P1.label',
            'actions' => ['second_interview'],
        ],
        'RF-P2' => [
            'category' => 'performance',
            'severity' => 'medium',
            'label_key' => 'redFlags.RF-P2.label',
            'actions' => ['trial_period', 'close_monitoring'],
        ],
        'RF-P3' => [
            'category' => 'performance',
            'severity' => 'medium',
            'label_key' => 'redFlags.RF-P3.label',
            'actions' => ['reference_check'],
        ],

        // Behavior & Attitude (S)
        'RF-S1' => [
            'category' => 'behavior',
            'severity' => 'high',
            'label_key' => 'redFlags.RF-S1.label',
            'actions' => ['manager_interview'],
        ],
        'RF-S2' => [
            'category' => 'behavior',
            'severity' => 'medium',
            'label_key' => 'redFlags.RF-S2.label',
            'actions' => ['role_play'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Definitions
    |--------------------------------------------------------------------------
    |
    | Each action has:
    | - label_key: i18n translation key
    | - icon: optional icon identifier for UI
    | - severity: indicates how serious the action is
    |
    */

    'actions' => [
        'reject' => [
            'label_key' => 'actions.reject',
            'icon' => 'x-circle',
            'severity' => 'high',
        ],
        'not_suitable_for_food_roles' => [
            'label_key' => 'actions.not_suitable_for_food_roles',
            'icon' => 'exclamation-triangle',
            'severity' => 'high',
        ],
        'second_interview' => [
            'label_key' => 'actions.second_interview',
            'icon' => 'chat-bubble',
            'severity' => 'medium',
        ],
        'hygiene_scenario' => [
            'label_key' => 'actions.hygiene_scenario',
            'icon' => 'clipboard-document-check',
            'severity' => 'medium',
        ],
        'conditional_hire' => [
            'label_key' => 'actions.conditional_hire',
            'icon' => 'check-badge',
            'severity' => 'low',
        ],
        'hygiene_training' => [
            'label_key' => 'actions.hygiene_training',
            'icon' => 'academic-cap',
            'severity' => 'low',
        ],
        'trial_period' => [
            'label_key' => 'actions.trial_period',
            'icon' => 'clock',
            'severity' => 'medium',
        ],
        'close_monitoring' => [
            'label_key' => 'actions.close_monitoring',
            'icon' => 'eye',
            'severity' => 'medium',
        ],
        'reference_check' => [
            'label_key' => 'actions.reference_check',
            'icon' => 'phone',
            'severity' => 'medium',
        ],
        'manager_interview' => [
            'label_key' => 'actions.manager_interview',
            'icon' => 'user-group',
            'severity' => 'high',
        ],
        'role_play' => [
            'label_key' => 'actions.role_play',
            'icon' => 'play',
            'severity' => 'medium',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Level Actions
    |--------------------------------------------------------------------------
    |
    | Default actions based on overall risk level.
    |
    */

    'risk_level_actions' => [
        'none' => ['proceed'],
        'low' => ['proceed'],
        'medium' => ['second_interview'],
        'high' => ['manager_interview', 'human_review_required'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Labels
    |--------------------------------------------------------------------------
    */

    'categories' => [
        'hygiene' => [
            'label_key' => 'categories.hygiene',
            'icon' => 'shield-check',
        ],
        'performance' => [
            'label_key' => 'categories.performance',
            'icon' => 'chart-bar',
        ],
        'behavior' => [
            'label_key' => 'categories.behavior',
            'icon' => 'user',
        ],
    ],
];
