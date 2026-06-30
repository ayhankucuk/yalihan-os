<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Alerts Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('AI_ALERTS_ENABLED', true),

    'cost_guard' => [
        'warning_threshold' => 0.80,
        'critical_threshold' => 0.95,
        'kill_switch_threshold' => 1.00,
    ],

    'provider_errors' => [
        'warning_threshold' => 0.10,
        'critical_threshold' => 0.20,
    ],

    'channels' => [
        'log' => true,
        'slack' => env('AI_ALERTS_SLACK_ENABLED', false),
        'email' => env('AI_ALERTS_EMAIL_ENABLED', false),
    ],

    'slack' => [
        'webhook_url' => env('AI_ALERTS_SLACK_WEBHOOK'),
        'channel' => '#ai-alerts',
        'username' => 'Yalıhan AI Monitor',
        'icon_emoji' => ':robot_face:',
    ],

    'email' => [
        'to' => env('AI_ALERTS_EMAIL_TO', 'admin@yalihan.com'),
        'from' => 'ai-system@yalihan.com',
        'subject_prefix' => '[Yalıhan AI]',
    ],
];
