<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PropertyHub Engine Mode
    |--------------------------------------------------------------------------
    |
    | Modes:
    | - v2_only     : Only legacy V2 runs
    | - shadow      : V2 returns, V3 runs in parallel + logs mismatches
    | - v3_primary  : V3 returns, V2 ignored
    | - disabled    : Force V2 fallback (circuit override)
    |
    */

    'engine_mode' => env('PROPERTYHUB_ENGINE_MODE', 'v2_only'),

    'allow_v3_primary_in_prod' => env('PROPERTYHUB_ALLOW_V3_PRIMARY_IN_PROD', false),

    'engine' => [
        'mode' => env('PROPERTYHUB_ENGINE_MODE', 'v2_only'),
        // v2_only | shadow | v3_primary | disabled

        'mismatch' => [
            'log' => env('PROPERTYHUB_ENGINE_MISMATCH_LOG', true),
            'sample_rate' => (float) env('PROPERTYHUB_ENGINE_MISMATCH_SAMPLE_RATE', 1.0), // 0..1
        ],

        'shadow' => [
            'enabled' => env('PROPERTYHUB_ENGINE_SHADOW_ENABLED', true),
            'reverse_shadow_enabled' => env('PROPERTYHUB_REVERSE_SHADOW_ENABLED', true),
            'canary_weight' => (int) env('PROPERTYHUB_SHADOW_CANARY_WEIGHT', 100), // 0-100%
            'db_log' => env('PROPERTYHUB_ENGINE_SHADOW_DB_LOG', true),
        ],
    ],

    // Safety thresholds (from previous architecture, kept for future expansion)
    'circuit_breaker' => [
        'error_threshold' => (float) env('PROPERTYHUB_SHADOW_ERROR_THRESHOLD', 0.05), // 5%
        'mismatch_threshold' => (float) env('PROPERTYHUB_SHADOW_MISMATCH_THRESHOLD', 0.10), // 10%
        'slope_threshold' => (float) env('PROPERTYHUB_SHADOW_SLOPE_THRESHOLD', 0.05), // 5% increase per window
        'window_seconds' => (int) env('PROPERTYHUB_SHADOW_WINDOW_SECONDS', 300), // 5 mins
        'bucket_size' => (int) env('PROPERTYHUB_SHADOW_BUCKET_SIZE', 60), // 1 min buckets
    ],

    /*
    |--------------------------------------------------------------------------
    | Safe Context Fields
    |--------------------------------------------------------------------------
    |
    | Only these fields are included in context_hash for privacy/security.
    | NO PII (user IDs, emails, phones, addresses, free text).
    |
    */
    'safe_context_fields' => [
        'category_id',
        'publication_type_id',
        'sub_category_id',
        'city_id',
        'property_type_flags',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chaos & Resilience Settings (Sprint 13)
    |--------------------------------------------------------------------------
    */
    'chaos_enabled' => env('PROPERTYHUB_CHAOS_ENABLED', false),
    'active_chaos' => [], // Programmatic control principal
];
