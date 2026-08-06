<?php

return [
    'enabled' => env('GUEST_COMMUNICATION_ENABLED', false),
    'channels' => [
        'airbnb' => [
            'enabled' => env('GUEST_AIRBNB_ENABLED', false),
            'api_url' => env('AIRBNB_API_URL', 'https://api.airbnb.com/v2'),
        ],
        'whatsapp' => [
            'enabled' => env('GUEST_WHATSAPP_ENABLED', false),
        ],
        'email' => [
            'enabled' => env('GUEST_EMAIL_ENABLED', true),
        ],
    ],
    'messages' => [
        'welcome_enabled' => env('GUEST_WELCOME_ENABLED', true),
        'checkin_enabled' => env('GUEST_CHECKIN_ENABLED', false),
        'checkout_enabled' => env('GUEST_CHECKOUT_ENABLED', false),
        'review_enabled' => env('GUEST_REVIEW_ENABLED', false),
    ],
    'pilot' => [
        'strict_mode' => env('GUEST_PILOT_STRICT', true),
        'tenants' => [],
        'properties' => [],
    ],
    'retry' => [
        'enabled' => env('GUEST_RETRY_ENABLED', true),
        'max_attempts' => env('GUEST_RETRY_MAX_ATTEMPTS', 3),
        'backoff_seconds' => env('GUEST_RETRY_BACKOFF', 60),
    ],
];
