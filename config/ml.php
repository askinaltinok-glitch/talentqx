<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ML Learning Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the machine learning weight update system.
    | These values control how aggressively the model learns from outcomes.
    |
    */

    // Learning rate for gradient updates (default: 0.02)
    'learning_rate' => env('ML_LEARNING_RATE_DEFAULT', 0.02),

    // Maximum delta per feature update (safety clamp)
    'max_delta_per_update' => env('ML_MAX_DELTA_PER_UPDATE', 0.15),

    // Minimum samples before applying global weight updates
    'warmup_min_samples' => env('ML_WARMUP_MIN_SAMPLES', 50),

    // Weight bounds
    'weight_min' => env('ML_WEIGHT_MIN', -25.0),
    'weight_max' => env('ML_WEIGHT_MAX', 10.0),

    // Delta clamp range
    'delta_min' => env('ML_DELTA_MIN', -2.5),
    'delta_max' => env('ML_DELTA_MAX', 2.5),

    // Minimum error threshold to trigger learning (skip if error too small)
    'min_error_threshold' => env('ML_MIN_ERROR_THRESHOLD', 5),

    // Auto-create new weights after this many samples
    'auto_weight_version_threshold' => env('ML_AUTO_WEIGHT_VERSION_THRESHOLD', 20),

    // Fairness alert thresholds
    'fairness' => [
        'score_delta_alert' => env('ML_FAIRNESS_SCORE_DELTA_ALERT', 10),
        'precision_gap_alert' => env('ML_FAIRNESS_PRECISION_GAP_ALERT', 15),
    ],

    // Feature stability threshold (flag as unstable if delta > this)
    'unstable_feature_threshold' => env('ML_UNSTABLE_FEATURE_THRESHOLD', 0.15),

    // Stability lock (Layer 4)
    'volatility_max_ratio' => env('ML_VOLATILITY_MAX_RATIO', 0.20),
    'sudden_shift_ratio' => env('ML_SUDDEN_SHIFT_RATIO', 0.30),
    'canary_mode_enabled' => env('ML_CANARY_MODE', false),
    'canary_industry' => env('ML_CANARY_INDUSTRY', 'maritime'),
    'allow_auto_update_when_frozen' => false,
];
