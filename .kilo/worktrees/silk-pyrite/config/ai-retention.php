<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Data Retention Policy
    |--------------------------------------------------------------------------
    | 90-day retention for high-volume AI telemetry tables
    */

    'default_retention_days' => env('AI_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Tables Subject to Retention Policy
    |--------------------------------------------------------------------------
    */

    'tables' => [
        'ai_feature_usages' => [
            'retention_days' => 90,
            'archive_enabled' => true,
            'date_column' => 'created_at',
        ],
        
        'ai_logs' => [
            'retention_days' => 90,
            'archive_enabled' => true,
            'date_column' => 'created_at',
        ],
        
        'ai_provider_decisions' => [
            'retention_days' => 90,
            'archive_enabled' => true,
            'date_column' => 'created_at',
        ],
        
        'ai_learning_signals' => [
            'retention_days' => 90,
            'archive_enabled' => true,
            'date_column' => 'created_at',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Archive Settings
    |--------------------------------------------------------------------------
    */

    'archive' => [
        'batch_size' => 500,
        'use_transactions' => true,
        'verify_before_delete' => true,
    ],
];
