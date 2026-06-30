<?php

return [
    'poi_agirlik_haritasi' => [
        'plaj' => [
            'etiket' => 'Plaj',
            'tipler' => ['beach', 'amenity.beach', 'coast'],
            'pozitif_esik_metre' => 500,
            'pozitif_puan' => 25.0,
        ],
        'hastane' => [
            'etiket' => 'Hastane',
            'tipler' => ['amenity.hospital', 'hospital'],
            'pozitif_esik_metre' => 1500,
            'pozitif_puan' => 18.0,
        ],
        'okul' => [
            'etiket' => 'Okul',
            'tipler' => ['amenity.school', 'school'],
            'pozitif_esik_metre' => 1000,
            'pozitif_puan' => 12.0,
        ],
        'marina' => [
            'etiket' => 'Marina',
            'tipler' => ['marina', 'harbor', 'port'],
            'pozitif_esik_metre' => 1200,
            'pozitif_puan' => 16.0,
        ],
        'avm' => [
            'etiket' => 'AVM',
            'tipler' => ['mall', 'shopping', 'amenity.mall', 'shop'],
            'pozitif_esik_metre' => 2000,
            'pozitif_puan' => 10.0,
        ],
        'park' => [
            'etiket' => 'Park',
            'tipler' => ['park', 'amenity.park'],
            'pozitif_esik_metre' => 800,
            'pozitif_puan' => 8.0,
        ],
        'sanayi' => [
            'etiket' => 'Sanayi',
            'tipler' => ['industrial', 'factory', 'industrial_zone'],
            'negatif_esik_metre' => 2000,
            'negatif_puan' => -15.0,
        ],
    ],
];

