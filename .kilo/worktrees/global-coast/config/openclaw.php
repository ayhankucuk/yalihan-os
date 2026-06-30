<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenClaw Agent Kill Switch
    |--------------------------------------------------------------------------
    |
    | Master toggle for all AI agent-initiated actions.
    | When false, ALL agent requests are rejected with 503.
    | This is the primary circuit breaker for autonomous AI operations.
    |
    */

    'enabled' => env('OPENCLAW_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Proposal-Only Mode
    |--------------------------------------------------------------------------
    |
    | When true, agents can ONLY submit proposals (read + propose).
    | Direct mutations (create, update, delete) are forbidden.
    | Must be true in production until Human-in-the-Loop review is mature.
    |
    */

    'proposal_only' => env('OPENCLAW_PROPOSAL_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed Routes (Wildcard Allowlist)
    |--------------------------------------------------------------------------
    |
    | Agent tokens can ONLY access routes matching these patterns.
    | Supports wildcard (*) matching against named routes.
    | Any request to a non-matching route is rejected with 403.
    | Keep this list minimal — expand only with ADR justification.
    |
    */

    'allowed_routes' => [
        'api.agent.context.*',
        'api.agent.suggestions.*',
        'api.agent.proposals.*',
        'api.agent.health',
        'api.integrations.n8n.*',
        'api.telegram.advisor.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Forbidden Route Patterns (Explicit Deny)
    |--------------------------------------------------------------------------
    |
    | Routes matching these patterns are ALWAYS rejected, even if they
    | match allowed_routes. Deny takes precedence over allow.
    | Prevents agents from accessing admin, governance, or write endpoints.
    |
    */

    'forbidden_route_patterns' => [
        'admin.*',
        'api.governance.write.*',
        'api.features.assign*',
        'api.templates.apply*',
        'api.listings.publish*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes that agent tokens may claim. Validated against the
    | X-Agent-Scope header. Any scope not in this list is rejected.
    |
    */

    'allowed_scopes' => [
        'agent.read.context',
        'agent.trigger.workflow',
        'agent.request.suggestion',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits (per agent token)
    |--------------------------------------------------------------------------
    |
    | Enforced per scoped token. Exceeding limits returns 429.
    |
    */

    'rate_limits' => [
        'requests_per_minute' => env('OPENCLAW_RPM', 30),
        'requests_per_hour' => env('OPENCLAW_RPH', 500),
        'max_payload_bytes' => env('OPENCLAW_MAX_PAYLOAD', 65536), // 64KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoped Token Configuration
    |--------------------------------------------------------------------------
    |
    | Agent authentication uses a scoped bearer token.
    | Tokens are validated via X-Agent-Token header against config/DB.
    |
    */

    'token' => [
        'header' => 'X-Agent-Token',
        'value' => env('OPENCLAW_AGENT_TOKEN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Contract Headers
    |--------------------------------------------------------------------------
    |
    | Required headers for every agent request.
    | X-Agent-Source: must be 'openclaw'
    | X-Agent-Token: scoped bearer token
    | X-Agent-Mode: 'proposal_only' or 'execute'
    | X-Correlation-Id: unique request correlation identifier
    |
    */

    'headers' => [
        'source' => 'X-Agent-Source',
        'token' => 'X-Agent-Token',
        'mode' => 'X-Agent-Mode',
        'correlation_id' => 'X-Correlation-Id',
        'scope' => 'X-Agent-Scope',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Every agent request is logged to the security channel.
    | No agent action may bypass audit — middleware rejects audit-less calls.
    |
    */

    'audit' => [
        'log_channel' => 'security',
        'log_payload' => env('OPENCLAW_LOG_PAYLOAD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anomaly Detection Thresholds
    |--------------------------------------------------------------------------
    |
    | Used by `php artisan openclaw:detect-anomalies`.
    | Schedule: Every 10 minutes (production), hourly (staging).
    |
    */

    'anomaly_detection' => [
        'violation_burst_threshold' => env('OPENCLAW_ANOMALY_VIOLATION_BURST', 3),
        'block_rate_threshold' => env('OPENCLAW_ANOMALY_BLOCK_RATE', 0.5),
        'token_proliferation_threshold' => env('OPENCLAW_ANOMALY_TOKEN_PROLIFERATION', 5),
        'baseline_requests_per_minute' => env('OPENCLAW_ANOMALY_BASELINE_RPM', 10),
        'spike_multiplier' => env('OPENCLAW_ANOMALY_SPIKE_MULTIPLIER', 2.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Services Registry
    |--------------------------------------------------------------------------
    |
    | Services intentionally exempt from GuardsAgentWrites.
    | Each entry: FQCN → [reason, domain, review_date].
    |
    | POLICY: This list must NOT grow without ADR justification.
    | Growth pattern ≥ 10 entries = architectural review trigger.
    | Reviewed each sprint; stale entries must be re-evaluated.
    |
    */

    'excluded_services' => [
        'App\\Services\\FlexibleStorageManager' => [
            'reason' => 'Internal AI pattern storage (deprecated bookkeeping). No domain entity writes.',
            'domain' => 'AI/Intelligence',
            'review_date' => '2026-07-01',
        ],
        'App\\Services\\Ups\\UpsImportExportService' => [
            'reason' => 'File storage deletion only. No domain entity writes — operates on filesystem.',
            'domain' => 'Ups/Template',
            'review_date' => '2026-07-01',
        ],
        'App\\Services\\QRCodeService' => [
            'reason' => 'File storage + cache forget. Generates/deletes images, no domain DB writes.',
            'domain' => 'Listing/Utility',
            'review_date' => '2026-07-01',
        ],
        'App\\Services\\AICoreSystem' => [
            'reason' => 'Internal AI success rate metrics. Bookkeeping counters, not domain mutations.',
            'domain' => 'AI/Intelligence',
            'review_date' => '2026-07-01',
        ],
        'App\\Services\\Ilan\\IlanBulkService' => [
            'reason' => 'Delegation-only: all writes route through already-guarded IlanCrudService.',
            'domain' => 'Listing/Bulk',
            'review_date' => '2026-07-01',
        ],
        'App\\Services\\IlanService' => [
            'reason' => 'SEALED: all write methods throw RuntimeException. Read methods still active.',
            'domain' => 'Listing/Legacy',
            'review_date' => '2026-07-01',
        ],
        'App\\Services\\AI\\AIOrchestrator' => [
            'reason' => 'Returns unsaved model object (fill only). No persist/save call, no DB write.',
            'domain' => 'AI/Intelligence',
            'review_date' => '2026-07-01',
        ],
    ],

];
