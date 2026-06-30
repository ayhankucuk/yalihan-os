<?php

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Configuration for Hermes event dispatcher.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Async Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, handlers are dispatched to the queue instead of executing
    | synchronously. Default is false (sync mode).
    |
    */
    'async_enabled' => env('HERMES_ASYNC_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The queue name used for async handler dispatch.
    |
    */
    'queue_name' => env('HERMES_QUEUE_NAME', 'hermes'),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Default retry policy for handler execution.
    |
    */
    'retry' => [
        'max_attempts' => env('HERMES_MAX_ATTEMPTS', 3),
        'base_delay_seconds' => env('HERMES_BASE_DELAY', 10),
        'multiplier' => env('HERMES_MULTIPLIER', 2.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Handler Configuration
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific handlers.
    |
    */
    'handlers' => [
        'telegram' => [
            'enabled' => env('HERMES_TELEGRAM_ENABLED', false),
        ],
        'notification_logger' => [
            'enabled' => env('HERMES_NOTIFICATION_LOGGER_ENABLED', true),
        ],
    ],
];
