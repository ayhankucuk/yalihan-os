<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Yalıhan Emlak Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for Yalıhan Emlak system-wide settings.
    | This file follows Context7 naming conventions and SSOT principles.
    |
    */

    'pagination' => [
        'default_per_page' => env('PAGINATION_PER_PAGE', 20),
        'max_per_page' => 100,
        'ilan_per_page' => 20,
        'kisiler_per_page' => 50,
    ],

    'cache' => [
        // TTL values in seconds
        'ilan_ttl' => env('CACHE_ILAN_TTL', 3600), // 1 hour
        'kategori_ttl' => env('CACHE_KATEGORI_TTL', 86400), // 24 hours
        'ozellik_ttl' => env('CACHE_OZELLIK_TTL', 43200), // 12 hours
        'kullanici_ttl' => env('CACHE_KULLANICI_TTL', 1800), // 30 minutes
        'search_ttl' => env('CACHE_SEARCH_TTL', 600), // 10 minutes

        // Cache tags
        'tags' => [
            'ilan' => 'ilan',
            'kategori' => 'kategori',
            'ozellik' => 'ozellik',
            'kullanici' => 'kullanici',
        ],
    ],

    'features' => [
        'ai_enabled' => env('AI_FEATURES_ENABLED', true),
        'spatial_search' => env('SPATIAL_SEARCH_ENABLED', false),
        'advanced_filters' => env('ADVANCED_FILTERS_ENABLED', true),
        'auto_save' => env('AUTO_SAVE_ENABLED', true),
    ],

    'limits' => [
        'max_photos_per_ilan' => 20,
        'max_file_size_mb' => 10,
        'max_video_size_mb' => 100,
        'max_pdf_size_mb' => 5,
    ],

    'api' => [
        'rate_limit' => env('API_RATE_LIMIT', 60),
        'timeout' => env('API_TIMEOUT', 30),
    ],

    'performance' => [
        'spatial_index_threshold' => 10000, // Use spatial index when ilan count > 10K
        'partitioning_threshold' => 100000, // Use partitioning when ilan count > 100K
    ],
];
