<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Matching Configuration
    |--------------------------------------------------------------------------
    |
    | Performance and accuracy settings for the Demand Matching Engine.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Strangler Fig Feature Flags
    |--------------------------------------------------------------------------
    */
    'use_domain_talep' => (bool) env('FEATURE_DOMAIN_TALEP', false),
    'demand_matching_enabled' => (bool) env('FEATURE_DEMAND_MATCHING', false),

    'matching' => [
        // Maximum number of listing candidates to process in memory after SQL pre-filtering
        'max_candidates' => 500,

        // Default price tolerance (0.15 = 15%) for SQL pre-filtering
        'price_tolerance' => 0.15,

        // Default area tolerance (0.10 = 10%) for SQL pre-filtering
        'area_tolerance' => 0.10,

        // Default match score threshold for reverse matching
        'min_match_score' => 70,
    ],
];
