<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Copilot Pipeline — Async Execution Configuration
    |--------------------------------------------------------------------------
    |
    | Queue routing, timeouts, retries, and control for the
    | async pipeline execution system (Faz 1).
    |
    */

    'enabled' => env('COPILOT_PIPELINE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Names
    |--------------------------------------------------------------------------
    | Separate queues prevent slow jobs (verification) from blocking
    | fast critical jobs (governance).
    */
    'queues' => [
        'high'         => env('COPILOT_QUEUE_HIGH', 'copilot-high'),
        'default'      => env('COPILOT_QUEUE_DEFAULT', 'copilot-default'),
        'verification' => env('COPILOT_QUEUE_VERIFICATION', 'copilot-verification'),
        'governance'   => env('COPILOT_QUEUE_GOVERNANCE', 'copilot-governance'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Step Timeouts (seconds)
    |--------------------------------------------------------------------------
    */
    'timeouts' => [
        'normalize'    => 30,
        'audit'        => 60,
        'fix'          => 60,
        'execution'    => 60,
        'verification' => 120,
        'govern'       => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    */
    'retries' => [
        'normalize'    => 2,
        'audit'        => 2,
        'fix'          => 2,
        'execution'    => 2,
        'verification' => 2,
        'govern'       => 1, // governance does not retry
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline Limits
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_concurrent_runs' => env('COPILOT_MAX_CONCURRENT_RUNS', 5),
        'max_run_duration_seconds' => 600, // 10 minutes total pipeline timeout
        'stale_run_threshold_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Shards (Fan-out Configuration)
    |--------------------------------------------------------------------------
    | Each shard runs as a parallel job inside a Bus::batch().
    | Add/remove shards here — batch and aggregator use this list.
    */
    'verification_shards' => [
        'feature_tests',
        'endpoint',
        'db',
        'regression',
    ],
];
