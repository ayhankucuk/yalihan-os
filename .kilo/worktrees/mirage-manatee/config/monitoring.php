<?php

/**
 * ✅ P3: Monitoring Dashboard Configuration
 *
 * Provides real-time metrics for:
 * - Performance (query times, memory, CPU)
 * - Usage (active users, API requests)
 * - System Health (databases, cache, queue)
 * - Errors (exceptions, failed jobs)
 *
 * Integrations:
 * - Laravel Telescope (local development)
 * - Sentry (error tracking)
 * - New Relic (optional APM)
 */

return [
    // Dashboard access control
    'access' => [
        'authenticated' => true,  // Require login
        'admins_only' => true,    // Admin role required
        'ip_whitelist' => env('MONITORING_IP_WHITELIST', '127.0.0.1'), // Comma-separated
    ],

    // Metrics refresh interval (seconds)
    'refresh_interval' => env('MONITORING_REFRESH_INTERVAL', 30),

    // Metrics to display
    'metrics' => [
        'performance' => [
            'enabled' => true,
            'track_slow_queries' => true,
            'slow_query_threshold_ms' => 1000,
            'track_memory_usage' => true,
            'track_cpu_usage' => true,
        ],
        'usage' => [
            'enabled' => true,
            'track_active_users' => true,
            'track_api_requests' => true,
            'track_page_views' => true,
        ],
        'system' => [
            'enabled' => true,
            'track_database_status' => true,
            'track_cache_status' => true,
            'track_queue_status' => true,
            'track_storage_usage' => true,
        ],
        'errors' => [
            'enabled' => true,
            'track_exceptions' => true,
            'track_failed_jobs' => true,
            'track_failed_logins' => true,
        ],
    ],

    // Data retention
    'retention' => [
        'metrics_days' => 30,
        'errors_days' => 90,
        'logs_days' => 7,
    ],

    // Alerts
    'alerts' => [
        'high_error_rate' => [
            'enabled' => true,
            'threshold_percent' => 5, // Alert if >5% errors
            'check_interval_minutes' => 5,
        ],
        'slow_response' => [
            'enabled' => true,
            'threshold_ms' => 5000, // Alert if avg response > 5s
            'check_interval_minutes' => 10,
        ],
        'high_memory' => [
            'enabled' => true,
            'threshold_percent' => 80, // Alert if >80% memory
            'check_interval_minutes' => 5,
        ],
        'queue_overflow' => [
            'enabled' => true,
            'threshold_jobs' => 1000, // Alert if > 1000 pending
            'check_interval_minutes' => 10,
        ],
    ],

    // Notification channels for alerts
    'notifications' => [
        'email' => env('MONITORING_EMAIL', ''),
        'slack' => env('MONITORING_SLACK_WEBHOOK', ''),
        'sentry' => env('SENTRY_DSN', null) ? true : false,
    ],

    // Charts and graphs
    'charts' => [
        'response_time' => ['data_points' => 60, 'interval_minutes' => 1],
        'error_rate' => ['data_points' => 60, 'interval_minutes' => 1],
        'memory_usage' => ['data_points' => 60, 'interval_minutes' => 1],
        'database_queries' => ['data_points' => 60, 'interval_minutes' => 1],
    ],

    // Integration providers
    'providers' => [
        'telescope' => env('TELESCOPE_ENABLED', config('app.env') === 'local'),
        'sentry' => env('SENTRY_LARAVEL_ENABLED', false),
        'new_relic' => env('NEWRELIC_ENABLED', false),
        'datadog' => env('DATADOG_ENABLED', false),
    ],

    // API rate limiting for monitoring endpoints
    'api_rate_limit' => [
        'requests_per_minute' => 60,
        'burst_requests' => 10,
    ],
];
