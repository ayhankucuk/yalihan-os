<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Phase K: UPS AI Feature Flags & Guardrails
    |--------------------------------------------------------------------------
    |
    | Production-ready controls for AI workflows
    | Context7: Flag-controlled, safe rollback, observer mode preserved
    |
    */

    // Master switch (✅ Context7: aktiflik_durumu)
    'aktiflik_durumu' => env('UPS_AI_ENABLED', true),

    // Phase B: AI Title/Description generation
    'assist_aktiflik_durumu' => env('UPS_AI_ASSIST_ENABLED', true),

    // Phase C: Quality check
    'quality_check_aktiflik_durumu' => env('UPS_AI_QUALITY_CHECK_ENABLED', true),

    // Phase D: Publish gate
    'publish_gate_aktiflik_durumu' => env('UPS_AI_PUBLISH_GATE_ENABLED', true),
    'publish_gate_mode' => env('UPS_AI_PUBLISH_GATE_MODE', 'soft'), // soft|hard
    'quality_min_score' => (int) env('UPS_AI_QUALITY_MIN_SCORE', 60),

    // Provider fallback
    'provider_fallback_aktiflik_durumu' => env('UPS_AI_PROVIDER_FALLBACK_ENABLED', true),

    // Context limits
    'max_features_in_prompt' => (int) env('UPS_AI_MAX_FEATURES_IN_PROMPT', 25),

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerting
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'error_rate_threshold' => (float) env('UPS_AI_ERROR_RATE_THRESHOLD', 0.10), // 10%
        'p95_threshold_ms' => (int) env('UPS_AI_P95_THRESHOLD_MS', 3000),
        'block_rate_threshold' => (float) env('UPS_AI_BLOCK_RATE_THRESHOLD', 0.50), // 50%
    ],
];
