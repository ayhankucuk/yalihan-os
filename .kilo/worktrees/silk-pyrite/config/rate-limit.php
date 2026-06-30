<?php

/**
 * ✅ P0: Rate Limiting Configuration
 *
 * Defines limits for:
 * - API endpoints (per minute)
 * - Auth attempts (prevent brute force)
 * - File uploads (prevent storage abuse)
 * - WebSocket connections (prevent floods)
 */

return [
    // API rate limits
    'api' => [
        'enabled' => env('RATE_LIMIT_ENABLED', true),
        'requests_per_minute' => env('RATE_LIMIT_REQUESTS_PER_MINUTE', 60),
        'window_minutes' => 1,
        'cache_driver' => env('RATE_LIMIT_CACHE_DRIVER', 'redis'), // Use Redis for distributed systems
    ],

    // Authentication attempts (login, password reset)
    'auth' => [
        'enabled' => env('RATE_LIMIT_AUTH_ENABLED', true),
        'attempts' => env('RATE_LIMIT_AUTH_ATTEMPTS', 5), // Max login attempts
        'decay_minutes' => env('RATE_LIMIT_AUTH_DECAY', 60), // Lockout duration
    ],

    // File upload limits
    'uploads' => [
        'enabled' => env('RATE_LIMIT_UPLOADS_ENABLED', true),
        'files_per_hour' => env('RATE_LIMIT_UPLOADS_PER_HOUR', 50),
        'total_size_mb_per_hour' => env('RATE_LIMIT_UPLOADS_SIZE_MB', 500),
    ],

    // Admin endpoints (looser limits)
    'admin' => [
        'enabled' => env('RATE_LIMIT_ADMIN_ENABLED', true),
        'requests_per_minute' => env('RATE_LIMIT_ADMIN_REQUESTS_PER_MINUTE', 300),
        'window_minutes' => 1,
    ],

    // Public endpoints (stricter limits)
    'public' => [
        'enabled' => env('RATE_LIMIT_PUBLIC_ENABLED', true),
        'requests_per_minute' => env('RATE_LIMIT_PUBLIC_REQUESTS_PER_MINUTE', 30),
        'window_minutes' => 1,
    ],

    // Exempt IPs (trusted sources, don't rate limit)
    'exempt_ips' => [
        '127.0.0.1',
        '::1',
        // Add internal service IPs here
    ],

    // Custom endpoints with specific limits
    'endpoints' => [
        'POST /api/kisiler' => ['requests_per_minute' => 10], // Contact creation
        'GET /api/ilanlar' => ['requests_per_minute' => 60], // List listings
        'POST /api/wizard' => ['requests_per_minute' => 5], // Wizard submissions (likely bot target)
    ],
];
