<?php

/**
 * Location Intelligence Configuration — MIE V4
 *
 * POI sınıflandırma, mesafe kovaları ve ağırlıklandırma kuralları.
 * Deterministic — rand() sıfır, AI sıfır.
 *
 * Context7: location_signal_score, location_confidence, poi_access_score,
 *           poi_density_score, poi_coverage_score
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Radius (km)
    |--------------------------------------------------------------------------
    */
    'default_radius_km' => 3.0,

    /*
    |--------------------------------------------------------------------------
    | POI Group Classification
    |--------------------------------------------------------------------------
    | Raw poi_turu → domain group mapping.
    | Her poi_turu tek bir gruba ait olabilir.
    */
    'poi_groups' => [
        'education' => [
            'label' => 'Eğitim',
            'icon' => 'graduation-cap',
            'types' => [
                'school', 'university', 'kindergarten', 'college',
                'amenity.school', 'amenity.university', 'amenity.kindergarten',
                'primary_school', 'secondary_school', 'high_school',
                'language_school', 'driving_school', 'music_school',
            ],
        ],
        'health' => [
            'label' => 'Sağlık',
            'icon' => 'heart-pulse',
            'types' => [
                'hospital', 'clinic', 'pharmacy', 'doctor', 'dentist',
                'amenity.hospital', 'amenity.clinic', 'amenity.pharmacy',
                'veterinary', 'optician', 'health',
            ],
        ],
        'shopping' => [
            'label' => 'Alışveriş',
            'icon' => 'shopping-bag',
            'types' => [
                'mall', 'shopping', 'shopping_mall', 'supermarket', 'grocery',
                'amenity.mall', 'shop', 'market', 'department_store',
                'clothing_store', 'electronics_store', 'furniture_store',
            ],
        ],
        'transport' => [
            'label' => 'Ulaşım',
            'icon' => 'bus',
            'types' => [
                'bus_stop', 'bus_station', 'metro', 'station', 'ferry',
                'ferry_terminal', 'taxi', 'parking', 'airport',
                'transit_station', 'train_station', 'subway_station',
                'transportation', 'light_rail_station',
            ],
        ],
        'food_social' => [
            'label' => 'Yeme-İçme',
            'icon' => 'utensils',
            'types' => [
                'restaurant', 'cafe', 'bar', 'bakery', 'fast_food',
                'food_court', 'coffee_shop', 'pub', 'bistro',
                'food', 'meal_delivery', 'meal_takeaway',
            ],
        ],
        'green_leisure' => [
            'label' => 'Yeşil Alan / Eğlence',
            'icon' => 'tree',
            'types' => [
                'park', 'beach', 'sports', 'gym', 'swimming_pool',
                'amenity.park', 'garden', 'playground', 'stadium',
                'sports_centre', 'fitness_centre', 'marina',
                'tourist_attraction', 'museum', 'theater', 'cinema',
            ],
        ],
        'daily_need' => [
            'label' => 'Günlük İhtiyaç',
            'icon' => 'building-2',
            'types' => [
                'bank', 'atm', 'post_office', 'cargo', 'municipal',
                'police', 'fire_station', 'courthouse', 'notary',
                'gas_station', 'fuel', 'car_wash', 'laundry',
                'finance', 'government', 'library',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Distance Buckets
    |--------------------------------------------------------------------------
    | Mesafe kovaları: max_m (exclusive), weight 1.0 → 0.0 azalan.
    */
    'distance_buckets' => [
        ['max_m' => 250,  'weight' => 1.0, 'label' => 'Çok yakın'],
        ['max_m' => 750,  'weight' => 0.7, 'label' => 'Yakın'],
        ['max_m' => 1500, 'weight' => 0.4, 'label' => 'Orta'],
        ['max_m' => 3000, 'weight' => 0.15, 'label' => 'Uzak'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Score Weights (max total = 40)
    |--------------------------------------------------------------------------
    | Kritik ihtiyaç grupları erişim ağırlığı.
    */
    'access_weights' => [
        'education'   => 10.0,
        'health'      => 10.0,
        'transport'   => 10.0,
        'daily_need'  => 10.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Density Score Config (max = 30)
    |--------------------------------------------------------------------------
    */
    'density' => [
        'max_score'            => 30,
        'cap_per_group'        => 5,   // Grup başına max katkı sayısı
        'dedup_min_distance_m' => 50,  // 50m'den yakın aynı grup = duplicate
    ],

    /*
    |--------------------------------------------------------------------------
    | Coverage Score Config (max = 30)
    |--------------------------------------------------------------------------
    | Farklı grup kapsama seviyesi.
    */
    'coverage' => [
        'max_score'           => 30,
        'total_groups'        => 7,    // Toplam olası grup sayısı
        'min_groups_for_full' => 5,    // 5+ farklı grup = full coverage score
    ],

    /*
    |--------------------------------------------------------------------------
    | Confidence Thresholds
    |--------------------------------------------------------------------------
    */
    'confidence' => [
        'min_poi_count'          => 3,   // minimum toplam POI
        'min_group_count'        => 2,   // minimum farklı grup
        'high_min_poi'           => 10,
        'high_min_groups'        => 4,
        'medium_min_poi'         => 5,
        'medium_min_groups'      => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Demand Modifier Config
    |--------------------------------------------------------------------------
    | location signal → demand score katkısı (capped).
    */
    'demand_modifier' => [
        'max_positive'  => 10,   // max +10
        'max_negative'  => 0,    // location sinyali negatif katkı yapmaz
        'threshold'     => 50,   // location_signal_score > 50 → modifier aktif
    ],

    /*
    |--------------------------------------------------------------------------
    | Reason Code Definitions
    |--------------------------------------------------------------------------
    */
    'reason_codes' => [
        'near_education_access'       => 'Yakın çevrede eğitim erişimi mevcut',
        'near_health_access'          => 'Yakın çevrede sağlık erişimi mevcut',
        'strong_transport_access'     => 'Güçlü ulaşım erişimi',
        'near_daily_need_access'      => 'Günlük ihtiyaç noktaları erişilebilir',
        'near_shopping_access'        => 'Alışveriş erişimi mevcut',
        'near_food_social_access'     => 'Yeme-içme ve sosyal alan erişimi mevcut',
        'near_green_leisure_access'   => 'Yeşil alan ve eğlence erişimi mevcut',
        'strong_poi_coverage'         => 'Çevresel hizmet çeşitliliği güçlü',
        'moderate_poi_coverage'       => 'Çevresel hizmet çeşitliliği orta düzeyde',
        'weak_poi_coverage'           => 'Çevresel hizmet çeşitliliği sınırlı',
        'high_poi_density'            => 'Çevrede yoğun hizmet noktası mevcut',
        'insufficient_location_data'  => 'Konum verisi yetersiz, çevresel kıyas sinyali oluşturulamadı',
        'no_coordinates'              => 'İlan koordinatı bulunmuyor',
        'limited_neighborhood_signal' => 'Çevre verisi sınırlı, temkinli değerlendirme önerilir',
    ],

    /*
    |--------------------------------------------------------------------------
    | Human Summary Templates
    |--------------------------------------------------------------------------
    | Deterministic template → no AI needed.
    */
    'summary_templates' => [
        'strong' => 'Yakın çevrede {:groups} erişimi güçlü. Çevresel sinyal destekleyici.',
        'moderate' => 'Çevresel erişim orta düzeyde. {:groups} noktaları mevcut.',
        'weak' => 'Çevresel veri sınırlı. Konum sinyali düşük güvenle değerlendirildi.',
        'insufficient' => 'Konum verisi yetersiz, çevresel kıyas sinyali oluşturulamadı.',
        'no_coordinates' => 'İlan koordinatı bulunmadığı için konum değerlendirmesi yapılamadı.',
    ],

];
