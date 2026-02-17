<?php

return [
    'mode' => env('CRM_MAIL_MODE', 'draft_only'), // draft_only | auto_send
    'quiet_hours' => [
        'enabled' => true,
        'start' => '22:00',
        'end' => '08:00',
        'timezone' => 'Europe/Istanbul',
    ],
    'auto_send_rules' => [
        'max_per_lead_per_day' => 2,
        'min_classification_confidence' => 70,
        'blocked_intents' => ['complaint', 'legal', 'unsubscribe', 'spam'],
    ],
    'ai' => [
        'model' => 'gpt-4o-mini',
        'max_tokens' => 1000,
        'temperature' => 0.7,
    ],
    'max_total_per_day' => env('CRM_MAIL_MAX_TOTAL_PER_DAY', 300),
    'template_cooldown_hours' => env('CRM_MAIL_TEMPLATE_COOLDOWN_HOURS', 48),
    'smtp_circuit_breaker' => [
        'failure_threshold' => 8,
        'window_minutes' => 60,
        'open_minutes' => 30,
        'half_open_after_minutes' => 15,
    ],
    'webhook_secret' => env('CRM_INBOUND_WEBHOOK_SECRET', ''),
    'default_language' => 'en',
    'supported_languages' => ['en', 'tr', 'ru'],
    'free_email_providers' => [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
        'mail.ru', 'yandex.ru', 'yandex.com', 'icloud.com',
        'aol.com', 'protonmail.com',
    ],

    // AI Personas for outbound emails
    'personas' => [
        'ceo' => [
            'name' => 'TalentQX',
            'title' => 'CEO',
            'from_email' => 'info@talentqx.com',
            'tone' => 'Professional, concise, sales-focused, no hype',
            'signature' => "Best regards,\nTalentQX Team",
        ],
        'crew_director' => [
            'name' => 'TalentQX Maritime',
            'title' => 'Crew Director',
            'from_email' => 'crew@talentqx.com',
            'tone' => 'Maritime industry expert, operational, direct',
            'signature' => "Best regards,\nTalentQX Maritime Crew Team",
        ],
        'hr_consultant' => [
            'name' => 'TalentQX',
            'title' => 'Enterprise HR Consultant',
            'from_email' => 'info@talentqx.com',
            'tone' => 'HR technology expert, consultative, data-driven',
            'signature' => "Best regards,\nTalentQX HR Solutions",
        ],
        'commercial_director' => [
            'name' => 'TalentQX Maritime',
            'title' => 'Commercial Director',
            'from_email' => 'crew@talentqx.com',
            'tone' => 'Direct, B2B, shipping industry expert, solution-focused, no hype. Knows crew supply chains, STCW compliance, and fleet operations.',
            'signature' => "Best regards,\nTalentQX Maritime\nCommercial Operations",
        ],
    ],

    // Rules for auto-selecting persona by industry
    'persona_rules' => [
        'maritime' => 'commercial_director',
        'hr' => 'hr_consultant',
        'default' => 'ceo',
    ],

    // Objection handling patterns for AI reply drafts
    'objection_handlers' => [
        'maritime' => [
            'already use agencies' => 'We don\'t replace agencies. We reduce bad joins by adding structured English + STCW verification before you commit to a candidate. Agencies still source — we help you pick better.',
            'don\'t need AI' => 'You already decide — we just make it measurable. Our system gives you a verified English level and STCW compliance check before the candidate boards. No black box, just data you can trust.',
            'send info' => 'Happy to send our one-pager. Would a 15-minute walkthrough alongside it be useful? I can show you live candidates matching your fleet profile.',
            'too expensive' => 'A single bad join costs $15-30K in repatriation, replacement, and lost time. Our verification catches mismatches before they board. The ROI is usually clear within the first crew change cycle.',
            'not the right time' => 'Understood — timing matters. I\'ll close the loop here and reconnect next quarter. If crewing challenges come up before then, feel free to reach out directly.',
        ],
    ],
];
