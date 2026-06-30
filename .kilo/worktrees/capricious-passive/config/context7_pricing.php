<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Context7 Pricing Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all pricing-related constants and configuration options
    | for the Yalıhan Emlak platform.
    |
    */

    'currency' => [
        'default' => 'TL',
        'symbol' => '₺',
        'code' => 'TRY',
    ],

    'discounts' => [
        'weekly' => 0.15, // 15% discount
        'monthly' => 0.30, // 30% discount
    ],

    'fees' => [
        'cleaning' => [
            'default' => 0,
            'min' => 0,
            'currency' => 'TL',
        ],
        'deposit' => [
            'default' => 0,
            'currency' => 'TL',
        ],
    ],

    'smart_fields' => [
        'price' => 50, // Cost per smart field generation
    ],

    'seasonal' => [
        'summer_days' => 90,
        'winter_days' => 90,
        'midseason_days' => 92, // Total 272? 365 - 180 = 185. Logic check: 90+90=180.
        // Let's use standard quarters or specific logic.
        // Original logic: summer=90, winter=90, mid=92. 90+90+92 = 272. Missing 93 days.
        // Let's stick to original values for now to avoid breaking existing logic, but add comment.
    ],

    'finance' => [
        'occupancy_rate' => 0.70,
        'commercial_opex_rate' => 0.20,
        'residential_maintenance_rate' => 0.25,
        'seasonal_management_rate' => 0.30,
        'market_metrics' => [
             'average_sqm_price' => 35000, // TL/m2
        ]
    ],

    'limits' => [
        'min_price' => 1000,
        'max_price' => 1_000_000_000,
    ],
];
