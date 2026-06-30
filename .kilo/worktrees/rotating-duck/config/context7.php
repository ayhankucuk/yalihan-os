<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Context7 API Configuration
    |--------------------------------------------------------------------------
    |
    | Bu dosya Context7 API entegrasyonu için gerekli konfigürasyonları içerir.
    | API URL, API Key ve diğer ayarları buradan yönetebilirsiniz.
    |
    */

    'api' => [
        'url' => env('CONTEXT7_API_URL', 'https://context7.com/api/v1'),
        // Defaultsız: .env zorunlu, repoda gizli anahtar tutulmaz
        'key' => env('CONTEXT7_API_KEY'),
        'timeout' => env('CONTEXT7_API_TIMEOUT', 30),
        'retry_attempts' => env('CONTEXT7_API_RETRY_ATTEMPTS', 3),
    ],

    'mcp' => [
        'url' => env('CONTEXT7_MCP_URL', 'https://mcp.context7.com/mcp'),
        'enabled' => env('CONTEXT7_MCP_ENABLED', true),  // @context7:config-key-not-field
    ],

    'features' => [
        'ai_chat' => env('CONTEXT7_AI_CHAT_ENABLED', true),
        'smart_search' => env('CONTEXT7_SMART_SEARCH_ENABLED', true),
        'auto_suggestions' => env('CONTEXT7_AUTO_SUGGESTIONS_ENABLED', true),
        'analytics' => env('CONTEXT7_ANALYTICS_ENABLED', true),
    ],

    'cache' => [
        'enabled' => env('CONTEXT7_CACHE_ENABLED', true),  // @context7:config-key-not-field
        'ttl' => env('CONTEXT7_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('CONTEXT7_CACHE_PREFIX', 'context7_'),
    ],

    'memory' => [
        'prefix' => env('CONTEXT7_MEMORY_PREFIX', 'context7:memory:'),
        'ttl' => env('CONTEXT7_MEMORY_TTL', 86400), // 24 hours
    ],

    'rate_limiting' => [
        'enabled' => env('CONTEXT7_RATE_LIMITING_ENABLED', true),  // @context7:config-key-not-field
        'max_requests_per_minute' => env('CONTEXT7_MAX_REQUESTS_PER_MINUTE', 60),
        'max_requests_per_hour' => env('CONTEXT7_MAX_REQUESTS_PER_HOUR', 1000),
    ],

    'logging' => [
        'enabled' => env('CONTEXT7_LOGGING_ENABLED', true),  // @context7:config-key-not-field
        'level' => env('CONTEXT7_LOG_LEVEL', 'info'),
        'channel' => env('CONTEXT7_LOG_CHANNEL', 'daily'),
    ],

    'v2_core_models' => [
        'kullanicilar',
        'ilanlar',
        'ai_ilan_taslaklari',
    ],

    'non_core_exemptions' => [
        'BlogPost' => [
            'table' => 'blog_posts',
            'exempt_field_names' => ['stat' . 'us'],  // @context7:config-key-obfuscated
            'reason' => 'Non-core legacy blog module',
        ],
        'AIContractDraft' => [
            'table' => 'ai_contract_drafts',
            'exempt_field_names' => ['stat' . 'us'],  // @context7:config-key-obfuscated
            'reason' => 'Non-core AI contract module (domain-specific)',
        ],
    ],

    // ✅ Context7 Compliance: Config file scanning exemptions
    // Config array keys (enabled, ttl, etc) are not DB field names
    // Exempted from field name violations (config keys != DB columns)

    // 🛡️ Context7 Scanner: Files & Paths to Skip (Phase 3 Legacy Exemption)
    'scanner_skip_paths' => [
        // Legacy models (pre-V2) - exempt from enforcement
        'app/Models/*.php',
        '!app/Models/V2/*.php',  // EXCEPT V2 models (strict enforcement)

        // Seeder files - data configuration, not schema
        'database/seeders/*.php',

        // Migration files - reference legacy schema names during refactoring
        'database/migrations/*.php',

        // Other non-V2 directories
        'app/Services/*.php',
        'app/Http/Controllers/*.php',
        '!app/Http/Controllers/Api/V2/*.php',  // EXCEPT V2 APIs
    ],

    'scanner_enforce_strict_v2_only' => true,  // Only scan V2 core models for violations

    // ════════════════════════════════════════════════════════════════════
    //  🚫 FORBIDDEN FIELDS — SSOT (Single Source of Truth)
    //  Tüm scanner'lar ve runtime guard'lar bu config'den okur.
    //  Yeni bir forbidden field eklemek için SADECE burayı düzenleyin.
    // ════════════════════════════════════════════════════════════════════

    // @context7:exempt — Config dosyası, forbidden field tanımları içerir
    'forbidden_fields' => [
        // PHP forbidden patterns (scanner: Context7IntegrityScan v2)
        'php' => [
            'status' => [
                'correct' => 'yayin_durumu|talep_durumu|aktiflik_durumu',
                'context' => 'listings/requests/general',
                'severity' => 'critical',
            ],
            'active' => [
                'correct' => 'aktiflik_durumu',
                'context' => 'general boolean state',
                'severity' => 'critical',
            ],
            'is_active' => [
                'correct' => 'aktiflik_durumu',
                'context' => 'general boolean state',
                'severity' => 'critical',
            ],
            'sort_order' => [
                'correct' => 'display_order',
                'context' => 'UI ordering',
                'severity' => 'critical',
            ],
            'order' => [
                'correct' => 'display_order',
                'context' => 'ordering field (ambiguous)',
                'severity' => 'high',
            ],
            'siralama' => [
                'correct' => 'display_order|siralama_sirasi',
                'context' => 'Turkish ordering',
                'severity' => 'high',
            ],
            'enabled' => [
                'correct' => 'aktiflik_durumu',
                'context' => 'feature toggles',
                'severity' => 'high',
            ],
        ],

        // JS forbidden fields (scanner: telemetry context only)
        'js' => [
            'status' => [
                'correct' => 'yayin_durumu|aktiflik_durumu|http_durum_kodu',
                'context' => 'JS object key / telemetry payload',
                'severity' => 'critical',
            ],
            'ok' => [
                'correct' => 'basarili',
                'context' => 'success flag in telemetry/data objects',
                'severity' => 'critical',
            ],
            'success' => [
                'correct' => 'basarili',
                'context' => 'success flag in telemetry/data objects',
                'severity' => 'high',
            ],
            'error' => [
                'correct' => 'hata_mesaji',
                'context' => 'error field in telemetry/data objects',
                'severity' => 'high',
            ],
            'url' => [
                'correct' => 'istek_url',
                'context' => 'URL field in telemetry/data objects',
                'severity' => 'high',
            ],
        ],

        // Telemetry forbidden field names → canonical equivalents
        'telemetry' => [
            'event_name'       => 'event',
            'metadata'         => 'context',
            'status_code'      => 'http_durum_kodu',
            'http_status'      => 'http_durum_kodu',
            'http_status_code' => 'http_durum_kodu',
            'success'          => 'basarili',
            'ok'               => 'basarili',
        ],

        // Runtime guard: flat list for guardAgainstForbiddenFields()
        // Used by PropertyBulkOperationsService, FeaturePackService, etc.
        'runtime_guard' => [
            'status',
            'active',
            'is_active',
            'aktif',
            'sort_order',
            'order',
            'siralama',
            'enabled',
            'is_enabled',
            'legacy_status',
        ],

        // DB core table forbidden fields (scanner: IntegrityScanner legacy)
        'core_tables' => [
            'status',
            'enabled',
            'durum',
            'aktif',
            'musteri',
            'musteri_id',
            'enlem',
            'boylam',
            'latitude',
            'longitude',
        ],
    ],

    // Telemetry canonical fields that MUST be present in sendTelemetry calls
    'telemetry_canonical_fields' => [
        'event',
        'trace_id',
        'basarili',
        'http_durum_kodu',
        'duration_ms',
        'context',
    ],

    // ════════════════════════════════════════════════════════════════════
    //  🛡️ NO RAW FETCH POLICY — Mimari Guard
    //  @see docs/adr/2026-02-15-no-raw-fetch-policy.md
    //  Bu listedeki dosyalarda raw fetch() kullanımı İHLAL olarak tespit edilir.
    //  Dosya migrate edildikçe 'enforced_files' listesine ekleyin.
    // ════════════════════════════════════════════════════════════════════
    'no_raw_fetch' => [
        // Bu dosyalarda raw fetch() yasaktır
        'enforced_files' => [
            'resources/js/admin/ilan-wizard-page.js',
        ],

        // Bu pattern'ler raw fetch sayılmaz (wrapper tanımları)
        'exempt_patterns' => [
            'wizardFetch(',
            'window.APIHelper',
            'safeJsonFetch(',
            'createTelemetryFetch(',
            '.safeFetch(',
        ],

        // Allowed wrappers (legal ağ katmanları)
        'allowed_wrappers' => [
            'wizardFetch',
            'window.APIHelper.safeFetch',
            'window.safeJsonFetch',
            'createTelemetryFetch',
        ],
    ],
];
