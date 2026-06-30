<?php

/**
 * API Endpoint Registry
 *
 * Context7 Standard: C7-API-ENDPOINT-REGISTRY-2025-12-03
 *
 * Merkezi API endpoint kayıt sistemi.
 * Tüm endpoint'ler burada tanımlanır ve JavaScript'te kullanılır.
 *
 * @version 1.0.0
 * @since 2025-12-03
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Base URLs
    |--------------------------------------------------------------------------
    */
    'base_url' => env('APP_URL', 'http://localhost:8000'),
    'api_prefix' => '/api',
    'api_v1_prefix' => '/api/v1',

    /*
    |--------------------------------------------------------------------------
    | Location API Endpoints
    |--------------------------------------------------------------------------
    */
    'location' => [
        'provinces' => '/api/v1/location/provinces',
        'districts' => '/api/v1/location/districts/{id}',
        'neighborhoods' => '/api/v1/location/neighborhoods/{id}',
        'neighborhood_coordinates' => '/api/v1/location/neighborhood/{id}/coordinates',
        'geocode' => '/api/v1/location/geocode',
        'reverse_geocode' => '/api/v1/location/reverse-geocode',
        'nearby' => '/api/v1/location/nearby/{lat}/{lng}/{radius?}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Geo Proxy API Endpoints (Nominatim Backend Proxy)
    |--------------------------------------------------------------------------
    | Context7: Hardcoded Nominatim yasak - Backend proxy kullan
    | Yalıhan Bekçi: Rate limit, cache ve error handling
    */
    'geo' => [
        'geocode' => '/api/v1/geo/geocode',
        'reverse_geocode' => '/api/v1/geo/reverse-geocode',
        'nearby' => '/api/v1/geo/nearby',
    ],

    /*
    |--------------------------------------------------------------------------
    | Categories API Endpoints
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'subcategories' => '/api/v1/categories/sub/{parentId}',
        'publication_types' => '/api/v1/categories/publication-types/{categoryId}',
        'fields' => '/api/v1/categories/fields/{categoryId}/{publicationTypeId?}',
        'detail' => '/api/v1/categories/{id}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Live Search API Endpoints
    |--------------------------------------------------------------------------
    */
    'live_search' => [
        'kisiler' => '/api/v1/kisiler/search',
        'danismanlar' => '/api/v1/users/search',
        'sites' => '/api/v1/sites/search',
        'unified' => '/api/v1/search/unified',
    ],

    /*
    |--------------------------------------------------------------------------
    | TKGM API Endpoints
    |--------------------------------------------------------------------------
    */
    'tkgm' => [
        'parsel_sorgu' => '/api/v1/tkgm/parsel-sorgu',
        'yatirim_analizi' => '/api/v1/tkgm/yatirim-analizi',
        'health' => '/api/v1/tkgm/health',
    ],

    /*
    |--------------------------------------------------------------------------
    | Properties API Endpoints
    |--------------------------------------------------------------------------
    */
    'properties' => [
        'tkgm_lookup' => '/api/v1/properties/tkgm-lookup',
        'calculate' => '/api/v1/properties/calculate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment API Endpoints
    |--------------------------------------------------------------------------
    */
    'environment' => [
        'analyze' => '/api/v1/environment/analyze',
        'category' => '/api/v1/environment/category/{category}',
        'value_prediction' => '/api/v1/environment/value-prediction',
        'pois' => '/api/v1/environment/pois',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI API Endpoints
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'analyze' => '/api/v1/ai/analyze',
        'suggest' => '/api/v1/ai/suggest',
        'generate' => '/api/v1/ai/generate',
        'health' => '/api/v1/ai/health',
        'start_video_render' => '/api/v1/ai/start-video-render/{ilanId}',
        'video_isleme_durumu' => '/api/v1/ai/video-durumu/{ilanId}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin API Endpoints
    |--------------------------------------------------------------------------
    */
    'admin' => [
        // ✅ Phase B/G: Standard AI endpoints
        'ai_title' => '/admin/ilanlar/ai/title',
        'ai_description' => '/admin/ilanlar/ai/description',
        'ai_quality_check' => '/admin/ilanlar/ai/quality-check', // Phase C

        // Legacy endpoints (deprecated, use ai_* instead)
        'generate_ai_title' => '/admin/ilanlar/generate-ai-title',
        'generate_ai_description' => '/admin/ilanlar/generate-ai-description',
        'generate_ai_copy' => '/admin/ilanlar/{ilan}/ai-copy',

        'convert_price_to_text' => '/admin/ilanlar/convert-price-to-text',
        'live_search' => '/admin/ilanlar/live-search',
    ],

    /*
    |--------------------------------------------------------------------------
    | Yalihan Cortex API Endpoints
    |--------------------------------------------------------------------------
    */
    'cortex' => [
        'analyze' => '/api/admin/cortex/analyze/{id}',
        'video' => '/api/admin/cortex/video/{id}',
        'photos' => '/api/admin/cortex/photos/{id}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Market Analysis API (TKGM Learning Engine)
    |--------------------------------------------------------------------------
    */
    'market_analysis' => [
        'predict_price' => '/api/v1/market-analysis/predict-price',
        'analysis' => '/api/v1/market-analysis/{il_id}/{ilce_id?}',
        'hotspots' => '/api/v1/market-analysis/hotspots/{il_id}',
        'stats' => '/api/v1/market-analysis/stats',
    ],

    /*
    |--------------------------------------------------------------------------
    | Wizard API (Phase 6: UI/UX Smart Features)
    |--------------------------------------------------------------------------
    | Context7: Merkezi API endpoint yönetimi
    | Performance: 3ms eager loading (Phase 5)
    | AI: SmartFieldGenerationService entegrasyonu
    */
    'wizard' => [
        'features' => '/api/v1/wizard/features',
        'suggest' => '/api/v1/wizard/suggest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Helper Functions - Note: Use ApiEndpointHelper class instead
    |--------------------------------------------------------------------------
    */
];
