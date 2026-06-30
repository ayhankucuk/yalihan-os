<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Vision Provider
    |--------------------------------------------------------------------------
    | Supported: "openai", "vertex", "mock"
    */
    'provider' => env('VISION_PROVIDER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Vision Settings
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_VISION_MODEL', 'gpt-4o'),
        'max_tokens' => 500,
        'temperature' => 0.2,
        'detail' => 'low', // low, high, auto (low is cheaper)
    ],

    /*
    |--------------------------------------------------------------------------
    | Vertex AI (Gemini) settings
    |--------------------------------------------------------------------------
    */
    'vertex' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT'),
        'location' => env('GOOGLE_CLOUD_LOCATION', 'us-central1'),
        'model' => env('VERTEX_VISION_MODEL', 'gemini-1.5-flash'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Operational Bounds
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_images_per_request' => 5,
        'max_file_size_mb' => 4,
        'timeout_seconds' => 60,
    ],

    'enable_kill_switch' => env('VISION_KILL_SWITCH', false),
];
