<?php

/**
 * UPS (Unified Property System) Configuration
 *
 * Context7 Standard: C7-UPS-CONFIG-2026-01-11
 * Purpose: Centralized configuration for UPS Feature Template Resolver
 *
 * @see app/Services/Ups/FeatureTemplateResolver.php
 * @see KULLANIM_REHBERI.md Section 13: UPS & FeatureTemplateResolver
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | UPS Feature Template Resolver cache settings.
    | Redis tags: ups_features, category:{id}, yayin:{id}
    |
    */

    'cache' => [
        'enabled' => env('UPS_CACHE_ENABLED', true),
        'ttl' => env('UPS_CACHE_TTL', 600), // 10 minutes (seconds)
        'driver' => env('UPS_CACHE_DRIVER', 'redis'),
        'tags' => [
            'features' => 'ups_features',
            'category' => 'category', // category:{kategori_id}
            'yayin' => 'yayin',       // yayin:{yayin_tipi_id}
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Grouping Map
    |--------------------------------------------------------------------------
    |
    | Maps feature_category slugs to UI display groups.
    | Used in: FeatureTemplateResolver::resolveFeaturesGrouped()
    |
    | Context7 Note: Centralized mapping (moved from hard-coded)
    |
    */

    'ui_groups' => [
        // İç Özellikler (Interior Features)
        'ic-ozellikler' => 'İç Özellikler',
        'banyo' => 'İç Özellikler',
        'mutfak' => 'İç Özellikler',
        'oda' => 'İç Özellikler',
        'kat' => 'İç Özellikler',

        // Dış Özellikler (Exterior Features)
        'dis-ozellikler' => 'Dış Özellikler',
        'bahce' => 'Dış Özellikler',
        'otopark' => 'Dış Özellikler',
        'guvenlik' => 'Dış Özellikler',

        // Muhit (Neighborhood)
        'cevre' => 'Muhit',
        'ulasim' => 'Muhit',
        'sosyal-tesisler' => 'Muhit',
        'yakin-cevre' => 'Muhit',

        // Arsa Özellikleri (Land Features)
        'imar' => 'Arsa Özellikleri',
        'altyapi' => 'Arsa Özellikleri',
        'tapu' => 'Tapu & Hukuki',

        // Yazlık Özel (Vacation Rental Specific)
        'fiyatlandirma' => 'Fiyatlandırma',
        'rezervasyon' => 'Rezervasyon Kuralları',

        // Fallback
        'genel' => 'Genel Özellikler',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Group Priority Order
    |--------------------------------------------------------------------------
    |
    | Priority groups displayed first in UI (Wizard Step 2, Admin Panel)
    |
    */

    'priority_groups' => [
        'İç Özellikler',
        'Dış Özellikler',
        'Arsa Özellikleri',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Category Whitelist
    |--------------------------------------------------------------------------
    |
    | Category-specific feature category filtering.
    | Key: Root category slug (arsa, konut, yazlik, etc.)
    | Value: Array of allowed feature_category slugs
    |
    | Context7 Note: Moved from FeatureTemplateResolver::getFeatureCategoryWhitelist()
    | Future: Migrate to database table (category_feature_whitelist)
    |
    */

    'category_whitelist' => [
        // Arsa kategorisi (Land)
        'arsa-arazi' => [
            'imar',          // İmar planı, KAKS, gabari
            'altyapi',       // Elektrik, su, kanalizasyon
            'tapu',          // Tapu durumu, imar durumu
            'cevre',         // Çevre özellikleri
            'ulasim',        // Ulaşım
            'yakin-cevre',   // Yakın çevre (okul, hastane, etc.)
            'arsa-ozellikleri', // ⭐ Arsa özel: Arsa Tipi, İmar Durumu, vb.
        ],

        // Konut kategorisi (Residential)
        'konut' => [
            'ic-ozellikler', // İç özellikler
            'banyo',         // Banyo sayısı, özellikler
            'mutfak',        // Mutfak özellikleri
            'oda',           // Oda sayısı, tipleri
            'kat',           // Kat bilgisi
            'dis-ozellikler',// Dış özellikler
            'bahce',         // Bahçe
            'otopark',       // Otopark
            'guvenlik',      // Güvenlik
            'cevre',         // Çevre
            'ulasim',        // Ulaşım
            'sosyal-tesisler', // Sosyal tesisler
        ],

        // Yazlık kategorisi (Vacation Rental)
        'yazlik-kiralama' => [
            'ic-ozellikler',
            'banyo',
            'mutfak',
            'oda',
            'kat',
            'dis-ozellikler',
            'bahce',
            'otopark',
            'guvenlik',
            'cevre',
            'ulasim',
            'sosyal-tesisler',
            'fiyatlandirma', // ⭐ Yazlık özel: Günlük fiyat, sezon
            'rezervasyon',   // ⭐ Yazlık özel: Check-in/out, minimum gece
            'konaklama-bilgileri', // New from seeder
            'havuz-ve-su-sporlari', // New from seeder
            'dis-mekan-ozellikleri', // New from seeder
            'ic-mekan-donanimlari', // New from seeder
            'konfor-ve-eglence', // New from seeder
        ],

        // Ticari kategorisi (Commercial)
        'ticari' => [
            'ic-ozellikler',
            'kat',
            'dis-ozellikler',
            'otopark',
            'guvenlik',
            'cevre',
            'ulasim',
            'altyapi',       // Ticari altyapı (fiber, vb.)
        ],

        // Villa kategorisi (inherits from konut, but can override)
        'villa' => [
            'ic-ozellikler',
            'banyo',
            'mutfak',
            'oda',
            'kat',
            'dis-ozellikler',
            'bahce',         // ⭐ Villa'da bahçe önemli
            'otopark',
            'guvenlik',      // ⭐ Villa'da güvenlik kritik
            'cevre',
            'ulasim',
            'sosyal-tesisler',
        ],

        // Fallback: Empty array = no filtering (all features allowed)
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | LogService timer integration settings
    |
    */

    'monitoring' => [
        'enabled' => env('UPS_MONITORING_ENABLED', true),
        'slow_query_threshold_ms' => env('UPS_SLOW_QUERY_THRESHOLD', 100), // Log if >100ms
        'log_channel' => env('UPS_LOG_CHANNEL', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Inheritance Settings
    |--------------------------------------------------------------------------
    |
    | Recursive inheritance behavior
    |
    */

    'inheritance' => [
        'max_depth' => 5,               // Maximum inheritance chain depth (prevent infinite loops)
        'circular_detection' => true,   // Detect circular references
        'merge_strategy' => 'override', // 'override' | 'merge' | 'append'
    ],

];
