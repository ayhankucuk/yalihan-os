<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Yalıhan Copilot — Configuration
    |--------------------------------------------------------------------------
    |
    | Context-aware AI assistant for the admin panel.
    | Architecture: Rules (deterministic) → Predictions (scoring) → Audit (integrity)
    |
    */

    'enabled' => env('COPILOT_ENABLED', true),
    'version' => '2.0.0',

    /*
    |--------------------------------------------------------------------------
    | Insight Limits
    |--------------------------------------------------------------------------
    */
    'max_insights' => 5,
    'max_audit_findings' => 5,
    'auto_fetch_on_load' => true,

    /*
    |--------------------------------------------------------------------------
    | Performance Budget (ms)
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'max_response_ms' => 600,
        'context_collection_ms' => 200,
        'rule_evaluation_ms' => 50,
        'prediction_ms' => 300,
        'audit_ms' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'min_photos' => 5,
        'min_description_length' => 150,
        'stale_listing_days' => 90,
        'stale_draft_days' => 30,
        'stale_update_days' => 120,
        'max_drafts_warning' => 3,
        'max_drafts_list_warning' => 5,
        'unmatched_talep_threshold' => 0.5,

        // CRM decay thresholds (days)
        'crm_stale_lead_days' => 90,
        'crm_stale_talep_days' => 60,
        'crm_eslesme_timeout_days' => 14,

        // Location boundaries (Turkey bounding box)
        'turkey_lat_min' => 35.8,
        'turkey_lat_max' => 42.1,
        'turkey_lng_min' => 25.6,
        'turkey_lng_max' => 44.8,

        // Bodrum center for proximity rules
        'bodrum_lat' => 37.0344,
        'bodrum_lng' => 27.4305,
        'bodrum_radius_km' => 50,

        // Wizard thresholds
        'wizard_min_features_per_template' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Wizard Feature Flags
    |--------------------------------------------------------------------------
    */
    'wizard_schema_driven_step2' => env('WIZARD_SCHEMA_DRIVEN_STEP2', false),

    /*
    |--------------------------------------------------------------------------
    | Audit Severity Mappings
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'severity_order' => [
            'critical' => 0,
            'high' => 1,
            'medium' => 2,
            'low' => 3,
            'info' => 4,
        ],
        'categories' => [
            'data_quality' => 'Veri Kalitesi',
            'data_completeness' => 'Veri Tamlığı',
            'data_consistency' => 'Veri Tutarlılığı',
            'data_integrity' => 'Veri Bütünlüğü',
            'data_freshness' => 'Veri Güncelliği',
            'configuration' => 'Yapılandırma',
            'operational' => 'Operasyonel',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prediction Sources
    |--------------------------------------------------------------------------
    */
    'predictions' => [
        'use_intelligence_hub' => true,
        'use_deal_predictor' => true,
        'fallback_to_data_scoring' => true,
        'explainable' => true, // §4.3 Explainable predictions with signals
        'health_weights' => [
            'market' => 0.25,
            'quality' => 0.35,
            'seo' => 0.25,
            'match' => 0.15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Confidence Scoring
    |--------------------------------------------------------------------------
    */
    'confidence' => [
        'base_score' => 50,
        'high_data_bonus' => 25,
        'medium_data_bonus' => 15,
        'prediction_bonus' => 15,
        'audit_bonus' => 10,
        'high_threshold' => 75,
        'medium_threshold' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Label Map (Turkish)
    |--------------------------------------------------------------------------
    */
    'context_labels' => [
        'dashboard' => 'Dashboard',
        'ilan-detail' => 'İlan Detay',
        'ilan-edit' => 'İlan Düzenle',
        'ilan-create' => 'Yeni İlan',
        'ilan-list' => 'İlanlar',
        'wizard' => 'Wizard',
        'crm-detail' => 'Kişi Detay',
        'crm-edit' => 'Kişi Düzenle',
        'crm-list' => 'CRM',
        'crm-dashboard' => 'CRM',
        'crm-create' => 'Yeni Kişi',
        'talep-detail' => 'Talep Detay',
        'talep-edit' => 'Talep Düzenle',
        'talep-list' => 'Talepler',
        'talep-create' => 'Yeni Talep',
        'eslesme-list' => 'Eşleştirmeler',
        'eslesme-detail' => 'Eşleştirme',
        'eslesme-create' => 'Yeni Eşleştirme',
        'danisman-list' => 'Danışmanlar',
        'danisman-detail' => 'Danışman Detay',
        'property-hub' => 'Property Hub',
        'property-hub-features' => 'Özellikler',
        'property-hub-templates' => 'Şablonlar',
        'ai-dashboard' => 'AI Sistem',
        'ai-monitor' => 'AI Monitör',
        'analytics' => 'Analitik',
    ],

    /*
    |--------------------------------------------------------------------------
    | Specialized Services
    |--------------------------------------------------------------------------
    */
    'services' => [
        'wizard' => [
            'enabled' => true,
            'geo_keywords' => ['arsa', 'arazi', 'tarla', 'zeytinlik', 'bağ', 'bahçe'],
            'publish_blockers' => ['baslik', 'fiyat', 'ana_kategori_id'],
            'publish_warnings' => ['aciklama', 'il_id', 'lat', 'lng'],
        ],
        'crm' => [
            'enabled' => true,
            'profile_weight' => 40, // out of 100
            'activity_weight' => 60, // out of 100
            'decay_levels' => [
                'critical' => 180, // days
                'high' => 90,
                'medium' => 30,
                'low' => 0,
            ],
            'lead_temperature' => [
                'hot' => 70,  // score >= 70
                'warm' => 40, // score >= 40
                'cold' => 0,  // score < 40
            ],
        ],
        'location' => [
            'enabled' => true,
            'poi_radius_km' => 2,
            'coordinate_precision_threshold' => 4, // decimal places
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'requests_per_minute' => 30,
        'cooldown_seconds' => 2,
    ],
];
