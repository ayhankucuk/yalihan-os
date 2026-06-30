<?php

/**
 * SAB Governance Configuration
 *
 * This file handles system-level governance settings, safety switches,
 * and standard environment variables used in application logic.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Context7 Guard Switches
    |--------------------------------------------------------------------------
    | Force enables or disables architectural protection layers.
    */
    'guard_force' => env('CONTEXT7_GUARD_FORCE', false),

    /*
    |--------------------------------------------------------------------------
    | Auto-Repair & Self-Healing
    |--------------------------------------------------------------------------
    | Determines if the system should attempt to automatically fix minor
    | governance or data integrity issues.
    */
    'auto_repair_enabled' => env('AUTO_REPAIR_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Governance Database Hardening
    |--------------------------------------------------------------------------
    | Default credentials and targets for the DB hardening command.
    */
    'db_harden' => [
        'default_user' => env('DB_USERNAME', 'root'),
        'default_database' => env('DB_DATABASE', 'yalihanai_v2_production'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Production Lock Safety Switch
    |--------------------------------------------------------------------------
    | When set to 'OPEN', allows web requests. Defaults to 'LOCKED'.
    */
    'production_lock' => env('PRODUCTION_LOCK', 'LOCKED'),

    /*
    |--------------------------------------------------------------------------
    | Phase 4C — Governance Telemetry
    |--------------------------------------------------------------------------
    | Yönetişim kurallarının üretimde gerçekten uygulanıp uygulanmadığını
    | gerçek zamanlı izleyen telemetri sistemi ayarları.
    |
    | KRİTİK: Telemetri sadece gözlemler, asla zorlamaz (Passive Observer).
    | Telemetri hatası iş akışını kesmez (Fail-Open).
    */
    'telemetry' => [

        // Telemetriyi tamamen devre dışı bırakmak production'ı kesmez
        'enabled' => env('GOVERNANCE_TELEMETRY_ENABLED', true),

        // Redis prefix — ana cache verileriyle karışmayı önler
        'redis_prefix' => 'governance:metrics:',

        // Redis key TTL (saniye) — 7 gün
        'redis_ttl' => env('GOVERNANCE_METRICS_TTL', 604800),

        // Performance budget: telemetri bu süreden fazla bloklamaz (ms)
        'performance_budget_ms' => env('GOVERNANCE_PERF_BUDGET_MS', 10),

        // In-memory buffer — bu sayıya ulaşınca async flush tetiklenir
        'buffer_flush_threshold' => env('GOVERNANCE_BUFFER_THRESHOLD', 100),

        /*
        | Drift Detection (Safety Guardrail #13)
        | Eşik config-kontrollüdür, self-adaptive olamaz.
        */
        'drift_detection' => [
            'enabled'                => env('GOVERNANCE_DRIFT_DETECTION_ENABLED', true),
            'threshold_percentage'   => env('GOVERNANCE_DRIFT_THRESHOLD', 10),
            'baseline_period_days'   => env('GOVERNANCE_BASELINE_DAYS', 7),
            'alert_severity'         => 'high',
        ],

        /*
        | Event Retention (Safety Guardrail #17)
        | Tiered retention: ham → özet → ihlal → arşiv
        */
        'retention' => [
            'detailed_days'    => env('GOVERNANCE_RETENTION_DETAILED', 7),
            'aggregated_days'  => env('GOVERNANCE_RETENTION_AGGREGATED', 90),
            'violations_days'  => env('GOVERNANCE_RETENTION_VIOLATIONS', 365),
        ],

        /*
        | Health Score Thresholds (Composite — Safety Guardrail #12)
        | Single int kesinlikle yasak; breakdown array zorunlu.
        */
        'health_thresholds' => [
            'overall_minimum'        => 95,
            'repository_minimum'     => 98,
            'tenant_isolation_min'   => 95,
            'queue_safety_min'       => 90,
            'cache_governance_min'   => 90,
            'ci_compliance_min'      => 100,
            'drift_stability_min'    => 85,
        ],

        /*
        | Alert Settings (Safety Guardrail #14 — Fatigue Prevention)
        */
        'alerting' => [
            'enabled'              => env('GOVERNANCE_ALERTING_ENABLED', true),
            'dedup_window_minutes' => env('GOVERNANCE_ALERT_DEDUP_WINDOW', 60),
            'rate_limit_per_hour'  => env('GOVERNANCE_ALERT_RATE_LIMIT', 20),
            'channels'             => [
                'slack'   => env('MONITORING_SLACK_WEBHOOK', null),
                'email'   => env('MONITORING_EMAIL', null),
            ],
        ],

    ],

];
