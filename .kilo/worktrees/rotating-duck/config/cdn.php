<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized CDN and external asset configuration for CSP headers
    | and asset management. This prevents hardcoded URLs in middleware.
    |
    | Context7 Standard: C7-CDN-CONFIG-2026-05-19
    |
    */

    'enabled' => env('CDN_ENABLED', false),
    'url' => env('CDN_URL', ''),
    'asset_url' => env('ASSET_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP) Allowed Sources
    |--------------------------------------------------------------------------
    |
    | Define allowed external sources for scripts, styles, fonts, etc.
    | Used by SecurityMiddleware and SecureHeaders middleware.
    |
    */

    'allowed_sources' => [
        'scripts' => [
            'https://cdn.jsdelivr.net',
            'https://unpkg.com',
            'https://code.jquery.com',
            'https://maps.googleapis.com',
        ],

        'styles' => [
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://unpkg.com',
        ],

        'fonts' => [
            'https://fonts.gstatic.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
        ],

        'images' => [
            'https://images.unsplash.com',
            'https://maps.googleapis.com',
            'https://maps.gstatic.com',
        ],

        'connect' => [
            'https://maps.googleapis.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Local development URLs and ports for Vite HMR and local services.
    | Only used when APP_ENV=local or APP_DEBUG=true.
    |
    */

    'development' => [
        // Vite HMR ports
        'vite_ports' => [5173, 5174, 5175],
        'vite_hosts' => ['localhost', '127.0.0.1'],

        // Local AI/Integration services
        'local_services' => [
            'ollama' => env('OLLAMA_PORT', 11434),
            'n8n' => env('N8N_PORT', 5678),
            'whisper' => env('WHISPER_PORT', 9000),
        ],

        // WebSocket support
        'websocket_enabled' => env('WEBSOCKET_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Additional security configuration for CSP and other headers.
    |
    */

    'security' => [
        'csp_report_uri' => env('CSP_REPORT_URI', ''),
        'csp_report_only' => env('CSP_REPORT_ONLY', false),
        'upgrade_insecure_requests' => env('CSP_UPGRADE_INSECURE', true),
    ],
];
