<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Copilot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI Copilot feature including OpenAI API settings,
    | KVKK compliance options, and structured output settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Feature Toggle
    |--------------------------------------------------------------------------
    |
    | Enable or disable the AI Copilot feature globally.
    |
    */

    'enabled' => env('COPILOT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Structured Output
    |--------------------------------------------------------------------------
    |
    | Force the model to output strict JSON format. This improves consistency
    | and makes parsing more reliable.
    |
    */

    'structured_output' => env('COPILOT_STRUCTURED_OUTPUT', true),

    /*
    |--------------------------------------------------------------------------
    | API Fallback
    |--------------------------------------------------------------------------
    |
    | Whether to allow fallback to Chat Completions API if Responses API fails.
    | Default is false for stricter error handling - returns 503 if primary fails.
    | Set to true only if you want automatic fallback behavior.
    |
    */

    'allow_fallback' => env('COPILOT_ALLOW_FALLBACK', false),

    /*
    |--------------------------------------------------------------------------
    | Include Transcripts
    |--------------------------------------------------------------------------
    |
    | Whether to include interview/assessment transcripts in the context.
    | Disabling this reduces context size and improves privacy.
    | When false, only anonymized summaries are included.
    |
    */

    'include_transcripts' => env('COPILOT_INCLUDE_TRANSCRIPTS', false),

    /*
    |--------------------------------------------------------------------------
    | Context Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of items to include in context to manage token usage.
    |
    */

    'context_limits' => [
        'competencies' => env('COPILOT_MAX_COMPETENCIES', 20),
        'risk_factors' => env('COPILOT_MAX_RISK_FACTORS', 10),
        'candidates' => env('COPILOT_MAX_CANDIDATES', 10),
        'history_messages' => env('COPILOT_MAX_HISTORY_MESSAGES', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversation History Limit
    |--------------------------------------------------------------------------
    |
    | Number of previous messages to include in conversation context.
    |
    */

    'history_limit' => env('COPILOT_HISTORY_LIMIT', 6),

    /*
    |--------------------------------------------------------------------------
    | Guardrails Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for input/output filtering and safety measures.
    |
    */

    'guardrails' => [
        'enabled' => env('COPILOT_GUARDRAILS_ENABLED', true),
        'max_input_length' => env('COPILOT_MAX_INPUT_LENGTH', 4000),
        'max_output_length' => env('COPILOT_MAX_OUTPUT_LENGTH', 8000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | KVKK-safe logging settings. When strict mode is enabled,
    | NO message content or PII is ever logged.
    |
    */

    'logging' => [
        'enabled' => env('COPILOT_LOGGING_ENABLED', true),
        'kvkk_strict' => env('COPILOT_LOGGING_KVKK_STRICT', true),
        'log_costs' => env('COPILOT_LOG_COSTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limit the number of requests per user to prevent abuse.
    |
    */

    'rate_limits' => [
        'requests_per_minute' => env('COPILOT_RATE_LIMIT_PER_MINUTE', 20),
        'requests_per_hour' => env('COPILOT_RATE_LIMIT_PER_HOUR', 100),
    ],

];
