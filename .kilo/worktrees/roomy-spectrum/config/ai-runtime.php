<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Runtime Control
    |--------------------------------------------------------------------------
    | Global kill-switch and feature flags for AI system
    */

    'ai_enabled' => env('AI_ENABLED', true),
    'vision_enabled' => env('VISION_ENABLED', true),
    'suggestion_enabled' => env('SUGGESTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Rollout Percentages (Canary Deployment)
    |--------------------------------------------------------------------------
    | 0-100: Percentage of users who will see AI features
    */

    'rollout' => [
        'vision_percentage' => env('AI_VISION_ROLLOUT_PCT', 100),
        'suggestion_percentage' => env('AI_SUGGESTION_ROLLOUT_PCT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Graceful Fallback Strategy
    |--------------------------------------------------------------------------
    | What to do when AI is disabled: cache | empty | error
    */

    'graceful_fallback' => [
        'vision' => 'cache', // cache | empty | error
        'suggestion' => 'empty',
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Shutdown
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Resilience: Circuit Breaker
    |--------------------------------------------------------------------------
    */
    'circuit_breaker' => [
        'failure_threshold' => (int) env('AI_CB_FAILURE_THRESHOLD', 5),
        'window_seconds' => (int) env('AI_CB_WINDOW', 60),
        'cooldown_seconds' => (int) env('AI_CB_OPEN_SECONDS', 120),
        'half_open_trial_count' => 1,
    ],

    'last_shutdown' => [
        'at' => null,
        'reason' => null,
        'by' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Versioning
    |--------------------------------------------------------------------------
    | Global mapping for feature prompts.
    */
    'prompt_versions' => [
        'wizard' => env('AI_PROMPT_WIZARD_VERSION', 'v1'),
        'classifier' => env('AI_PROMPT_CLASSIFIER_VERSION', 'v1'),
    ],
];
