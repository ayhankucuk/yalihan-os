<?php

/**
 * ✅ P1: Audit Logging Configuration (spatie/laravel-activitylog)
 *
 * Provides comprehensive activity tracking:
 * - User actions (CRUD operations)
 * - Contact changes (kiss changes)
 * - Listing state changes
 * - System events
 *
 * Benefits:
 * - Compliance (GDPR audit trail)
 * - Debugging (who changed what when)
 * - Security (detect suspicious activity)
 * - Analytics (user behavior patterns)
 *
 * Install: composer require spatie/laravel-activitylog:^4.0
 */

return [
    // Database table for activity logs
    'table_name' => 'activity_log',

    // Activity model namespace (string to avoid undefined type before package install)
    'activity_model' => \Spatie\ActivityLog\Models\Activity::class,

    // Enable/disable logging
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    // Default log name (categorize activities)
    'default_log_name' => 'default',

    // Custom log names by model (optional)
    'log_names' => [
        'kisiler' => 'contacts',           // Contact changes
        'ilanlar' => 'listings',           // Listing changes
        'talepler' => 'requests',          // Request changes
        'admin' => 'admin_actions',        // Admin operations
        'security' => 'security_events',   // Login, logout, failed auth
    ],

    // Which models to track
    'models' => [
        \App\Models\Kisi::class => [
            'log_name' => 'contacts',
            'description' => 'Kişi Yönetimi',
            'track_changes' => true,
            'track_fields' => ['ad_soyad', 'email', 'telefon', 'aktiflik_durumu'],
        ],
        \App\Models\Ilan::class => [
            'log_name' => 'listings',
            'description' => 'İlan Yönetimi',
            'track_changes' => true,
            'track_fields' => ['baslik', 'yayin_durumu', 'one_cikan', 'fiyat'],
        ],
        \App\Models\Talep::class => [
            'log_name' => 'requests',
            'description' => 'Talep Yönetimi',
            'track_changes' => true,
            'track_fields' => ['talep_durumu', 'islem_turu'],
        ],
    ],

    // Events to log
    'events' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
        'restored' => true,
        'force_deleted' => true,
    ],

    // Exclude fields from tracking (passwords, tokens, etc)
    'except_attribute_keys' => [
        'password',
        'password_confirmation',
        'api_token',
        'remember_token',
        'created_at',
        'updated_at',
    ],

    // Batch operations (for bulk updates)
    'batch_mode' => env('ACTIVITY_LOG_BATCH_MODE', false),

    // Retention policy (keep logs for N days, 0 = never delete)
    'retention_days' => env('ACTIVITY_LOG_RETENTION_DAYS', 90),

    // User identification - use default Laravel auth driver (web/api)
    'default_auth_driver' => env('ACTIVITY_LOG_AUTH_DRIVER', 'web'),
];
