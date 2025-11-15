<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenRouter API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your OpenRouter API key and model preferences.
    | Get your API key from: https://openrouter.ai/keys
    |
    */

    'api_key' => env('OPENROUTER_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Site Configuration
    |--------------------------------------------------------------------------
    |
    | These are used for OpenRouter's attribution and analytics
    |
    */

    'site_url' => env('APP_URL'),
    'site_name' => env('APP_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Model Selection
    |--------------------------------------------------------------------------
    |
    | Choose which models to use for different tasks.
    | See available models: https://openrouter.ai/models
    |
    | Recommended models:
    | - Fast/Cheap: anthropic/claude-3-haiku, google/gemini-flash-1.5
    | - Balanced: anthropic/claude-3.5-sonnet, openai/gpt-4o-mini
    | - Best Quality: anthropic/claude-3-opus, openai/gpt-4o
    |
    */

    'models' => [
        // Model for SEO content generation (description, keywords, summary)
        'generation' => env('OPENROUTER_GENERATION_MODEL', 'google/gemini-2.0-flash-exp:free'),

        // Model for content translation
        'translation' => env('OPENROUTER_TRANSLATION_MODEL', 'google/gemini-2.0-flash-exp:free'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Configuration
    |--------------------------------------------------------------------------
    */

    'timeout' => env('OPENROUTER_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */

    'retry' => [
        'times' => (int) env('OPENROUTER_RETRY_TIMES', 2),
        'sleep' => (int) env('OPENROUTER_RETRY_SLEEP', 1000), // milliseconds
    ],
];
