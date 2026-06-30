<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Provider Optimization v3 Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('AI_PROVIDER_OPT_V3_ENABLED', true),

    'weights' => [
        'default' => [
            'accept_rate' => 0.45,
            'latency' => 0.20,
            'cost' => 0.20,
            'error' => 0.10,
            'cache' => 0.05,
        ],
        'overrides' => [
            'yazlik' => [
                'accept_rate' => 0.55,
                'latency' => 0.15,
                'cost' => 0.10,
                'error' => 0.15,
                'cache' => 0.05,
            ],
        ],
    ],

    'cooldown' => [
        'error_threshold' => 0.10, // 10% errors in 7d triggers cooldown
        'minutes' => 60,
    ],

    'sample_size' => [
        'min_7d' => 50,
        'min_30d' => 100,
    ],

    'static_priority' => [
        'openai',
        'vertex',
        'gemini',
    ],
];
