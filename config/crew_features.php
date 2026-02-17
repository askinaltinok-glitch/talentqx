<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Crew-Side Feature Tier Matrix
    |--------------------------------------------------------------------------
    |
    | Defines what each membership tier can access.
    | Tiers: free, plus, pro, enterprise
    |
    */

    'profile_view_detail' => [
        'free' => 'count',
        'plus' => 'company',
        'pro' => 'full',
    ],

    'notification_depth' => [
        'free' => 'basic',
        'plus' => 'company',
        'pro' => 'full',
    ],

    'review_access' => [
        'free' => 'aggregate',
        'plus' => 'aggregate',
        'pro' => 'breakdown',
    ],

    'job_priority' => [
        'free' => false,
        'plus' => false,
        'pro' => true,
    ],

    'max_job_applications_per_month' => [
        'free' => 5,
        'plus' => 20,
        'pro' => -1, // unlimited
    ],

];
