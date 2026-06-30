<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Token Budgets (feature-level)
    |--------------------------------------------------------------------------
    | tokens_per_day: günlük bütçe
    | soft_cap_ratio: 0.80 => %80'de uyarı
    | hard_cap_enabled: true by default (env override: AI_HARD_CAP_ENABLED)
    */
    'defaults' => [
        'tokens_per_day' => (int) env('AI_DEFAULT_TOKENS_PER_DAY', 200_000),
        'usd_per_day' => (float) env('AI_DEFAULT_USD_PER_DAY', 1.0),
        'soft_cap_ratio' => (float) env('AI_SOFT_CAP_RATIO', 0.80),
        'hard_cap_enabled' => (bool) env('AI_HARD_CAP_ENABLED', true),
        'hard_cap_ratio' => (float) env('AI_HARD_CAP_RATIO', 1.0),
        'grace_ratio' => (float) env('AI_GRACE_RATIO', 1.1),
        'allow_admin_override' => (bool) env('AI_ALLOW_ADMIN_OVERRIDE', false),
    ],

    'features' => [
        // UPS Template Generation
        'ups_template_generate' => [
            'tokens_per_day' => (int) env('AI_UPS_TEMPLATE_TOKENS_PER_DAY', 50_000),
            'usd_per_day' => 0.5,
            'soft_cap_ratio' => 0.80,
            'hard_cap_enabled' => (bool) env('AI_HARD_CAP_ENABLED', true),
        ],

        // Wizard Storytelling
        'wizard_storytelling' => [
            'tokens_per_day' => (int) env('AI_WIZARD_STORY_TOKENS_PER_DAY', 30_000),
            'usd_per_day' => 0.3,
            'soft_cap_ratio' => 0.85,
            'hard_cap_enabled' => (bool) env('AI_HARD_CAP_ENABLED', true),
        ],

        // AI Governance / Prompt Analysis
        'governance_analysis' => [
            'tokens_per_day' => (int) env('AI_GOVERNANCE_TOKENS_PER_DAY', 20_000),
            'usd_per_day' => 0.2,
            'soft_cap_ratio' => 0.75,
            'hard_cap_enabled' => (bool) env('AI_HARD_CAP_ENABLED', true),
        ],

        // Property Description Generation
        'property_description' => [
            'tokens_per_day' => (int) env('AI_PROPERTY_DESC_TOKENS_PER_DAY', 40_000),
            'usd_per_day' => 0.4,
            'soft_cap_ratio' => 0.80,
            'hard_cap_enabled' => (bool) env('AI_HARD_CAP_ENABLED', true),
        ],

        // Voice Search / NLP
        'voice_search' => [
            'tokens_per_day' => (int) env('AI_VOICE_SEARCH_TOKENS_PER_DAY', 15_000),
            'usd_per_day' => 0.15,
            'soft_cap_ratio' => 0.85,
            'hard_cap_enabled' => (bool) env('AI_HARD_CAP_ENABLED', true),
        ],
    ],
];
