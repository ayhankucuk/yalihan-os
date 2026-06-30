<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telemetry Event Allowlist
    |--------------------------------------------------------------------------
    |
    | Purpose: Prevent telemetry abuse and log pollution by enforcing
    | an allowlist of valid event names.
    |
    | Security: Backend telemetry endpoint MUST validate against this list.
    | Context7: All field naming must follow canonical standards.
    |
    | @see app/Http/Controllers/Admin/AdminTelemetryController.php
    | @see resources/js/global.js
    | @see resources/js/wizard/core/telemetry.js
    |
    */

    'allowed_events' => [
        // Wizard Events
        'wizard_fetch_context',
        'wizard_step_transition',
        'wizard_validation_error',
        'wizard_form_submit',

        // AI Events
        'ai_title_generation',
        'ai_description_generation',
        'ai_category_suggestion',
        'ai_quality_check',

        // Upload Events
        'photo_upload_start',
        'photo_upload_complete',
        'photo_upload_error',

        // Frontend Error Events
        'window_error',
        'unhandled_promise',
        'alpine_error',

        // Form Events
        'form_validation_error',
        'form_autosave',

        // API Events
        'api_request_start',
        'api_request_complete',
        'api_request_error',

        // Performance Events
        'page_load_complete',
        'interactive_ready',
        'lazy_load_trigger',

        // Property Hub Events
        'property_hub_templates_open',
        'property_hub_templates_edit_open',
        'property_hub_templates_ai_start',
        'property_hub_templates_ai_done',
        'property_hub_templates_ai_fail',

        // Governance Telemetry Events
        'governance.diff_viewed',
        'governance.publish_attempted',
        'governance.publish_rejected',
        'governance.publish_succeeded',
        'governance.shadow_evaluated',

        // OpenClaw Agent Audit Events
        'openclaw.gateway_open',
        'openclaw.gateway_blocked',
        'openclaw.scope_rejected',
        'openclaw.token_invalid',
        'openclaw.boundary_rejected',
        'openclaw.request_passed',
        'openclaw.write_violation',
        'openclaw.anomaly_detected',
    ],

    /*
    |--------------------------------------------------------------------------
    | Core Schema (MVP — 6 Fields)
    |--------------------------------------------------------------------------
    |
    | Every telemetry event uses the same universal schema.
    | No per-event validation. Context is free-form.
    |
    | 1. event          — string (required, must be in allowlist above)
    | 2. trace_id       — string (auto-generated server-side if absent)
    | 3. basarili       — bool   (Context7 canonical for "success")
    | 4. http_durum_kodu — int   (Context7 canonical for "http_status")
    | 5. duration_ms    — numeric (latency in milliseconds)
    | 6. context        — object (free-form: yayin_tipi_id, alt_kategori_id, user_id, etc.)
    |
    | Legacy mapping:
    | - payload → context
    | - url → istek_url
    |
    */

    'core_schema' => [
        'event'          => 'required|string|max:100',
        'trace_id'       => 'nullable|string|max:64',
        'basarili'       => 'nullable|boolean',
        'http_durum_kodu' => 'nullable|integer',
        'duration_ms'    => 'nullable|numeric',
        'context'        => 'nullable|array',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Retention Policy
    |--------------------------------------------------------------------------
    |
    | Defines how long telemetry logs should be retained.
    |
    */

    'retention' => [
        'telemetry' => 30, // days
        'security' => 90, // days
        'bekci' => 90, // days
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Defines acceptable performance thresholds for telemetry events.
    | Events exceeding these thresholds will trigger alerts.
    |
    */

    'thresholds' => [
        'wizard_fetch_context' => [
            'target' => 400, // ms
            'max_acceptable' => 600, // ms
            'p95' => 500, // ms
        ],
        'ai_title_generation' => [
            'target' => 3000, // ms
            'max_acceptable' => 5000, // ms
            'p95' => 4000, // ms
        ],
        'photo_upload_complete' => [
            'target' => 2000, // ms
            'max_acceptable' => 3000, // ms
            'p95' => 2500, // ms
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Anomaly Detection Rules (L5 Self-Protecting System)
    |--------------------------------------------------------------------------
    |
    | Defines rules for detecting unusual telemetry patterns.
    | Enables runtime anomaly detection with multi-channel alerts.
    |
    | @see app/Services/Telemetry/AnomalyDetector.php
    | @see app/Console/Commands/DetectTelemetryAnomalies.php
    |
    */

    'anomaly_detection' => [
        'enabled' => env('TELEMETRY_ANOMALY_DETECTION_ENABLED', true),
        'alert_threshold_percentage' => 50, // Alert if p95 exceeds target by 50%
        'alert_channels' => explode(',', env('TELEMETRY_ANOMALY_ALERT_CHANNELS', 'slack,log')),

        // Performance Budgets (from .env)
        'performance_budgets' => [
            'wizard_context_p95_ms' => env('PERF_WIZARD_CONTEXT_P95_MS', 400),
            'ai_generation_p95_ms' => env('PERF_AI_GENERATION_P95_MS', 3000),
            'dashboard_load_p95_ms' => env('PERF_DASHBOARD_LOAD_P95_MS', 1500),
            'error_rate_threshold' => env('PERF_ERROR_RATE_THRESHOLD', 0.02),
        ],

        // AI Cost Budget
        'ai_cost_budget' => [
            'daily_budget_usd' => env('AI_DAILY_BUDGET_USD', 50),
            'alert_threshold' => env('AI_COST_ALERT_THRESHOLD', 0.80),
            'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'openai-gpt-3.5-turbo'),
        ],

        // Alert Configuration
        'alerts' => [
            'slack_webhook_url' => env('SLACK_WEBHOOK_URL'),
            'alert_email' => env('ALERT_EMAIL', 'alerts@yalihanemlak.com.tr'),
        ],
    ],
];
