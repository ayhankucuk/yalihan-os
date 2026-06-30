<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Cost Guard Configuration
    |--------------------------------------------------------------------------
    |
    | Günlük ve aylık bütçe limitleri, otomatik aksiyon eşikleri (thresholds)
    | ve maliyet kontrol politikaları burada tanımlanır.
    |
    */

    'enabled' => env('AI_COST_GUARD_ENABLED', true),

    'budgets' => [
        'daily' => [
            'global_limit_usd' => env('AI_DAILY_BUDGET_USD', 5.00),
            
            'providers' => [
                'openai' => 3.50,
                'vertex' => 2.50,
                'gemini' => 1.50,
            ],

            'categories' => [
                'yazlik' => 2.00,
                'konut' => 1.50,
                'arsa' => 1.00,
            ],
        ],
    ],

    'thresholds' => [
        'warning' => 0.80,   // %80: Uyarı + Zorunlu Cache
        'downgrade' => 0.95, // %95: Daha ucuz modele geçiş (Provider Optimization)
        'kill_switch' => 1.00, // %100: AI isteklerini durdur, fallback'e geç
    ],

    'fallback' => [
        'use_cached_only' => true,
        'default_provider' => 'gemini', // En ucuz model
    ],
];
