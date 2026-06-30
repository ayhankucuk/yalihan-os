<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global AI Confidence Thresholds
    |--------------------------------------------------------------------------
    */
    'global' => [
        'auto_apply_min_confidence' => env('AI_AUTO_APPLY_CONFIDENCE', 0.80),
        'suggest_min_confidence' => env('AI_SUGGEST_CONFIDENCE', 0.50),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Category-Specific Overrides
    |--------------------------------------------------------------------------
    | Higher confidence required for certain categories
    */
    'category_overrides' => [
        'yazlik' => [
            'auto_apply_min_confidence' => 0.90, // Stricter for high-value properties
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Forbidden Auto-Apply Features
    |--------------------------------------------------------------------------
    | Features that should NEVER be auto-applied (always require user approval)
    */
    'forbidden_auto_apply' => [
        'is-merkezi', 
        'site-ici',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (Future)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'ai_suggestions_per_hour' => 100,
        'ai_image_analysis_per_hour' => 50,
    ],
];
