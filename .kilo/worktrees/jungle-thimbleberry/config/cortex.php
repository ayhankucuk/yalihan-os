<?php

return [
    'weights' => [
        // Nexus Blueprint (feature completeness)
        'nexus' => 60,
        // Visual quality (photos)
        'visual' => 25,
        // Content depth (description length/quality)
        'content' => 15,
    ],

    'thresholds' => [
        'photo' => [
            // 0–4 photos → 0 points
            'min' => [
                'count' => 5,
                'points' => 15,
            ],
            // 10+ photos → 25 points
            'max' => [
                'count' => 10,
                'points' => 25,
            ],
        ],

        'description' => [
            // <100 chars → 0 points; between short & long → mid points
            'short' => [
                'len' => 100,
                'points' => 7,
            ],
            // >300 chars → full points
            'long' => [
                'len' => 300,
                'points' => 15,
            ],
        ],
    ],

    'status_limits' => [
        'excellent' => 90,
        'good' => 70,
        'average' => 40,
    ],
];
