<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Popüler Şehirler
    |--------------------------------------------------------------------------
    |
    | Uygulamada öne çıkarılacak popüler şehirler için ID listesi
    | Örneğin: [34, 6, 35, 7, 16] (İstanbul, Ankara, İzmir, Antalya, Bursa)
    |
    */
    'popular_cities' => [34, 6, 35, 7, 16],

    /*
    |--------------------------------------------------------------------------
    | Önbellek Süresi
    |--------------------------------------------------------------------------
    |
    | Lokasyon verilerinin önbellek süresi (saniye)
    | Varsayılan: 86400 (1 gün)
    |
    */
    'cache_time' => 86400,

    /*
    |--------------------------------------------------------------------------
    | Koordinat Değerleri
    |--------------------------------------------------------------------------
    |
    | Harita ve coğrafi konumların merkezi ve yakınlık değerleri
    |
    */
    'map' => [
        'default_latitude' => 37.0346, // Bodrum merkez enlem
        'default_longitude' => 27.4309, // Bodrum merkez boylam
        'default_zoom' => 12, // Bodrum için daha yakın zoom seviyesi
        'search_radius' => 5, // Yakındaki yerler için varsayılan arama yarıçapı (km)
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Maps Ayarları (Opsiyonel)
    |--------------------------------------------------------------------------
    |
    | Google Maps kullanımını feature-flag ile kontrol edin.
    | API anahtarınızı alan adı kısıtlamasıyla güvenli şekilde kullanın.
    */
    'google_maps' => [
        'enabled' => env('GOOGLE_MAPS_ENABLED', false), // Context7: 'status' yasak, 'enabled' kanonik
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'libraries' => env('GOOGLE_MAPS_LIBRARIES', 'places'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bodrum Özel Lokasyonları
    |--------------------------------------------------------------------------
    |
    | Bodrum bölgesi için özel koordinatlar ve yakındaki yerler
    |
    */
    'bodrum' => [
        'center' => [
            'latitude' => 37.0346,
            'longitude' => 27.4309,
            'name' => 'Bodrum Merkez',
        ],
        'popular_locations' => [
            'yalikavak' => [
                'name' => 'Yalıkavak',
                'latitude' => 37.0739,
                'longitude' => 27.3386,
                'description' => 'Lüks yat limanı ve butik oteller',
            ],
            'gumusluk' => [
                'name' => 'Gümüşlük',
                'latitude' => 37.0500,
                'longitude' => 27.3167,
                'description' => 'Tarihi antik kent ve balık restoranları',
            ],
            'bitez' => [
                'name' => 'Bitez',
                'latitude' => 37.0167,
                'longitude' => 27.4500,
                'description' => 'Sakin plaj ve windsurf merkezi',
            ],
            'gumbet' => [
                'name' => 'Gümbet',
                'latitude' => 37.0202,
                'longitude' => 27.4092,
                'description' => 'Gençlik ve eğlence merkezi',
            ],
            'ortakent' => [
                'name' => 'Ortakent',
                'latitude' => 37.0278,
                'longitude' => 27.4833,
                'description' => 'Aile dostu plaj ve restoranlar',
            ],
        ],
        'nearby_places' => [
            'restaurants' => [
                'radius' => 2, // km
                'categories' => ['restaurant', 'cafe', 'bar'],
            ],
            'shopping' => [
                'radius' => 1.5, // km
                'categories' => ['store', 'shopping_mall', 'supermarket'],
            ],
            'transportation' => [
                'radius' => 1, // km
                'categories' => ['bus_station', 'taxi_stand', 'car_rental'],
            ],
            'healthcare' => [
                'radius' => 3, // km
                'categories' => ['hospital', 'pharmacy', 'doctor'],
            ],
            'education' => [
                'radius' => 2, // km
                'categories' => ['school', 'university', 'library'],
            ],
            'entertainment' => [
                'radius' => 2.5, // km
                'categories' => ['movie_theater', 'museum', 'park'],
            ],
        ],
    ],
];
