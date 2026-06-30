<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redis Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Context7 Standardı: C7-REDIS-CONFIG-2025-09-23
    | Amaç: Redis cache performansını optimize etmek
    |
    */

    'cache_prefix' => env('CACHE_PREFIX', 'emlak_pro'),

    'connections' => [

        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', 1),
            'options' => [
                'prefix' => env('CACHE_PREFIX', 'emlak_pro').':',
                'serializer' => 'php',
            ],
        ],

        'session' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', 2),
            'options' => [
                'prefix' => env('CACHE_PREFIX', 'emlak_pro').':session:',
            ],
        ],

        'queue' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', 3),
            'options' => [
                'prefix' => env('CACHE_PREFIX', 'emlak_pro').':queue:',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Settings
    |--------------------------------------------------------------------------
    */

    'ttl' => [
        'very_short' => 60,          // 1 minute
        'short' => 300,              // 5 minutes
        'medium' => 3600,            // 1 hour
        'long' => 86400,             // 1 day
        'very_long' => 604800,       // 1 week
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tags for Group Invalidation
    |--------------------------------------------------------------------------
    */

    'tags' => [
        'features' => 'feature_cache',
        'categories' => 'category_cache',
        'listings' => 'ilan_cache',
        'demands' => 'talep_cache',
        'statistics' => 'stats_cache',
        'search' => 'search_cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'status' => env('CACHE_MONITORING_ENABLED', true),
        'log_hits' => env('CACHE_LOG_HITS', false),
        'log_misses' => env('CACHE_LOG_MISSES', true),
        'performance_threshold' => env('CACHE_PERFORMANCE_THRESHOLD', 100), // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Strategies
    |--------------------------------------------------------------------------
    */

    'strategies' => [

        // Feature cache strategy
        'features' => [
            'ttl' => 3600, // 1 hour
            'tags' => ['features', 'categories'],
            'invalidate_on' => ['Feature', 'FeatureCategory', 'FeatureTranslation'],
        ],

        // Category cache strategy
        'categories' => [
            'ttl' => 7200, // 2 hours
            'tags' => ['categories'],
            'invalidate_on' => ['IlanKategori'],
        ],

        // Listing statistics
        'ilan_stats' => [
            'ttl' => 1800, // 30 minutes
            'tags' => ['statistics', 'listings'],
            'invalidate_on' => ['Ilan'],
        ],

        // Demand statistics
        'talep_stats' => [
            'ttl' => 1800, // 30 minutes
            'tags' => ['statistics', 'demands'],
            'invalidate_on' => ['Talep'],
        ],

        // Search results
        'search' => [
            'ttl' => 900, // 15 minutes
            'tags' => ['search'],
            'max_results' => 1000,
        ],

    ],

];
