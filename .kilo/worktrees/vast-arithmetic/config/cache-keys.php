<?php

/**
 * Cache Keys Registry - Merkezi Cache Key Yönetimi
 *
 * Context7 Standard: C7-CACHE-KEYS-2025-12-06
 *
 * Tüm cache key'ler merkezi config'de tanımlanır.
 * Cache key çakışmalarını önler ve cache invalidation kolaylaştırır.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Key Definitions
    |--------------------------------------------------------------------------
    |
    | Format: 'namespace.key' => [
    |     'namespace' => 'namespace',
    |     'key' => 'key',
    |     'ttl' => 3600, // veya 'short', 'medium', 'long'
    |     'tags' => ['tag1', 'tag2'],
    |     'params' => ['param1', 'param2'], // Key'de kullanılacak parametreler
    | ]
    |
    */

    'ilan' => [
        'list' => [
            'namespace' => 'ilan',
            'key' => 'list',
            'ttl' => 'medium', // 1 hour
            'tags' => ['ilan_cache'],
            'params' => ['il_id', 'status', 'kategori_id'],
        ],
        'stats' => [
            'namespace' => 'ilan',
            'key' => 'stats',
            'ttl' => 'short', // 5 minutes
            'tags' => ['ilan_cache', 'stats_cache'],
            'params' => ['il_id'],
        ],
        'model' => [
            'namespace' => 'ilan',
            'key' => 'model',
            'ttl' => 'long', // 1 day
            'tags' => ['ilan_cache'],
            'params' => ['id'],
        ],
    ],

    'category' => [
        'list' => [
            'namespace' => 'category',
            'key' => 'list',
            'ttl' => 'long',
            'tags' => ['category_cache'],
            'params' => [],
        ],
        'tree' => [
            'namespace' => 'category',
            'key' => 'tree',
            'ttl' => 'long',
            'tags' => ['category_cache'],
            'params' => [],
        ],
    ],

    'tkgm' => [
        'pattern_update' => [
            'namespace' => 'tkgm',
            'key' => 'pattern_update',
            'ttl' => 'short',
            'tags' => ['tkgm_cache'],
            'params' => ['il_id', 'ilce_id'],
        ],
        'market_analysis' => [
            'namespace' => 'tkgm',
            'key' => 'market_analysis',
            'ttl' => 'medium',
            'tags' => ['tkgm_cache', 'stats_cache'],
            'params' => ['il_id', 'ilce_id'],
        ],
        'coordinate' => [
            'namespace' => 'tkgm',
            'key' => 'coordinate',
            'ttl' => 'very_long', // 1 week
            'tags' => ['tkgm_cache'],
            'params' => ['lat', 'lon'],
        ],
    ],

    'cortex' => [
        'construction' => [
            'namespace' => 'cortex',
            'key' => 'construction',
            'ttl' => 'long', // 24 hours
            'tags' => ['ai_cache', 'cortex_cache'],
            'params' => ['ilce', 'mahalle', 'ada_no', 'parsel_no'],
        ],
    ],

    'menu' => [
        'items' => [
            'namespace' => 'menu',
            'key' => 'items',
            'ttl' => 'short',
            'tags' => ['menu_cache'],
            'params' => ['role', 'user_id'],
        ],
    ],

    'stats' => [
        'dashboard' => [
            'namespace' => 'stats',
            'key' => 'dashboard',
            'ttl' => 'short',
            'tags' => ['stats_cache', 'dashboard_cache'],
            'params' => [],
        ],
        'market_summary' => [
            'namespace' => 'stats',
            'key' => 'market_summary',
            'ttl' => 'short',
            'tags' => ['stats_cache'],
            'params' => ['il_id'],
        ],
    ],

    'ai' => [
        'analysis' => [
            'namespace' => 'ai',
            'key' => 'analysis',
            'ttl' => 'medium',
            'tags' => ['ai_cache'],
            'params' => ['type', 'context_hash'],
        ],
        'suggestions' => [
            'namespace' => 'ai',
            'key' => 'suggestions',
            'ttl' => 'short',
            'tags' => ['ai_cache'],
            'params' => ['category', 'context_hash'],
        ],
    ],

    'search' => [
        'results' => [
            'namespace' => 'search',
            'key' => 'results',
            'ttl' => 'short',
            'tags' => ['search_cache'],
            'params' => ['query_hash', 'filters_hash'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | TTL Presets
    |--------------------------------------------------------------------------
    |
    | Cache TTL değerleri (saniye cinsinden)
    |
    */

    'ttl' => [
        'very_short' => 60,      // 1 minute
        'short' => 300,          // 5 minutes
        'medium' => 3600,        // 1 hour
        'long' => 86400,         // 1 day
        'very_long' => 604800,   // 1 week
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Cache tag'leri için namespace mapping
    |
    */

    'tags' => [
        'ilan' => 'ilan_cache',
        'category' => 'category_cache',
        'tkgm' => 'tkgm_cache',
        'cortex' => 'cortex_cache',
        'menu' => 'menu_cache',
        'stats' => 'stats_cache',
        'dashboard' => 'dashboard_cache',
        'ai' => 'ai_cache',
        'search' => 'search_cache',
    ],
];
