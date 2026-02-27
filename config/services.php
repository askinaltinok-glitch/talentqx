<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4-turbo-preview'),
        'fallback_model' => env('OPENAI_FALLBACK_MODEL', 'gpt-4o-mini'),
        'whisper_model' => env('OPENAI_WHISPER_MODEL', 'whisper-1'),
        'timeout' => env('OPENAI_TIMEOUT', 120),
        'max_cost_per_session' => env('OPENAI_MAX_COST_PER_SESSION', 0.50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kimi AI (Moonshot) Configuration
    |--------------------------------------------------------------------------
    */

    'kimi' => [
        'api_key' => env('KIMI_API_KEY'),
        'base_url' => env('KIMI_BASE_URL', 'https://api.moonshot.ai/v1'),
        'model' => env('KIMI_MODEL', 'moonshot-v1-128k'),
        'timeout' => env('KIMI_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Provider Selection
    |--------------------------------------------------------------------------
    | Default AI provider to use: 'openai' or 'kimi'
    | This can be overridden per-company in company settings
    */

    'ai' => [
        'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    ],

    /*
    |--------------------------------------------------------------------------
    | İyzico Payment Gateway
    |--------------------------------------------------------------------------
    */

    'iyzico' => [
        'api_key' => env('IYZICO_API_KEY'),
        'secret_key' => env('IYZICO_SECRET_KEY'),
        'sandbox' => env('IYZICO_SANDBOX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paraşüt E-Fatura
    |--------------------------------------------------------------------------
    */

    'parasut' => [
        'client_id' => env('PARASUT_CLIENT_ID'),
        'client_secret' => env('PARASUT_CLIENT_SECRET'),
        'username' => env('PARASUT_USERNAME'),
        'password' => env('PARASUT_PASSWORD'),
        'company_id' => env('PARASUT_COMPANY_ID'),
        'sandbox' => env('PARASUT_SANDBOX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Verimor SMS Gateway
    |--------------------------------------------------------------------------
    */

    'verimor' => [
        'username' => env('VERIMOR_USERNAME'),
        'password' => env('VERIMOR_PASSWORD'),
        'source_addr' => env('VERIMOR_SOURCE_ADDR', 'TALENTQX'),
        'enabled' => env('VERIMOR_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | TalentQX API Authentication
    |--------------------------------------------------------------------------
    */

    'talentqx' => [
        'api_token' => env('TALENTQX_API_TOKEN'),
    ],

    'demo_request_to' => env('DEMO_REQUEST_TO'),

    /*
    |--------------------------------------------------------------------------
    | Octopus AiModelsPanel (Self-hosted Whisper / voice services)
    |--------------------------------------------------------------------------
    */

    'ai_models_panel' => [
        'base_url'   => env('AI_MODELS_PANEL_BASE_URL', 'https://ekler.istanbul/AiModelsPanel'),
        'api_key'    => env('AI_MODELS_PANEL_API_KEY'),
        'timeout'    => (int) env('AI_MODELS_PANEL_TIMEOUT', 60),
        'verify_ssl' => filter_var(env('AI_MODELS_PANEL_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    ],

    'whisper' => [
        'model'           => in_array(env('WHISPER_MODEL', 'large-v3'), [
            'tiny', 'tiny.en', 'base', 'base.en', 'small', 'small.en',
            'medium', 'medium.en', 'large-v1', 'large-v2', 'large-v3',
            'large', 'turbo', 'large-v3-turbo',
        ]) ? env('WHISPER_MODEL', 'large-v3') : 'large-v3',
        'language'        => env('WHISPER_LANGUAGE', 'en'),
        'temperature'     => (int) env('WHISPER_TEMPERATURE', 0),
        'response_format' => env('WHISPER_RESPONSE_FORMAT', 'json'),
        'prompt'          => env('WHISPER_PROMPT', ''),
    ],

    'voice' => [
        'max_upload_mb'       => (int) env('VOICE_MAX_UPLOAD_MB', 12),
        'max_duration_seconds' => (int) env('VOICE_MAX_DURATION_SECONDS', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Push (VAPID)
    |--------------------------------------------------------------------------
    */

    'web_push' => [
        'subject'     => env('VAPID_SUBJECT', 'mailto:noreply@talentqx.com'),
        'public_key'  => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
    ],

];
