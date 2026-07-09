<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Vision Provider Driver
    |--------------------------------------------------------------------------
    | Supported: "openai", "mock"
    | Future: "gemini", "azure", "local"
    */
    'driver' => env('VISION_DRIVER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Vision Settings — Sprint 6.4
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key'      => env('OPENAI_API_KEY'),
        'model'        => env('OPENAI_VISION_MODEL', 'gpt-4o'),
        'max_tokens'    => (int) env('OPENAI_VISION_MAX_TOKENS', 1024),
        'temperature'   => (float) env('OPENAI_VISION_TEMPERATURE', 0.3),
        'detail'        => env('OPENAI_VISION_DETAIL', 'high'), // low, high, auto
    ],

    /*
    |--------------------------------------------------------------------------
    | Operational Bounds
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_images_per_request' => 5,
        'max_file_size_mb'      => 4,
        'timeout_seconds'        => 30,
    ],

    'kill_switch' => env('VISION_KILL_SWITCH', false),
];
