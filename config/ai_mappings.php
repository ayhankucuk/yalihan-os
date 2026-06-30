<?php

/**
 * Visual AI Mappings
 *
 * Bu dosya, Google Vision API (veya benzeri görsel zeka servisleri) tarafından
 * döndürülen etiketlerin (labels) YalıhanAI UPS sistemindeki karşılıklarını tanımlar.
 */
return [

    'google_vision' => [

        // Mekan / Oda Tipleri
        'Kitchen' => ['slug' => 'mutfak', 'confidence_min' => 0.85],
        'Living room' => ['slug' => 'salon', 'confidence_min' => 0.80],
        'Bedroom' => ['slug' => 'oda-sayisi', 'confidence_min' => 0.90],
        'Bathroom' => ['slug' => 'banyo-sayisi', 'confidence_min' => 0.90],
        'Dining room' => ['slug' => 'yemek-odasi', 'confidence_min' => 0.85],
        'Garden' => ['slug' => 'bahce', 'confidence_min' => 0.90],
        'Swimming pool' => ['slug' => 'havuz', 'confidence_min' => 0.95],
        'Balcony' => ['slug' => 'balkon', 'confidence_min' => 0.85],
        'Terrace' => ['slug' => 'teras', 'confidence_min' => 0.85],

        // Lüks / Özel Özellikler
        'Fireplace' => ['slug' => 'somine', 'confidence_min' => 0.95],
        'Sauna' => ['slug' => 'sauna', 'confidence_min' => 0.90],
        'Jacuzzi' => ['slug' => 'jakuzi', 'confidence_min' => 0.90],
        'Gym' => ['slug' => 'spor-salonu', 'confidence_min' => 0.85],
        'Sea' => ['slug' => 'deniz-manzarasi', 'confidence_min' => 0.80],
        'Mountain' => ['slug' => 'manzara', 'confidence_min' => 0.80],

        // Mimari / Dekorasyon
        'Modern architecture' => ['slug' => 'mimari-tarz', 'value' => 'Modern'],
        'Hardwood flooring' => ['slug' => 'zemin-tipi', 'value' => 'Laminat/Parke'],
        'Marble' => ['slug' => 'zemin-tipi', 'value' => 'Mermer'],
        'Air conditioning' => ['slug' => 'klima', 'confidence_min' => 0.80],

    ],

    /**
     * Otomatik doldurma kuralları
     */
    'rules' => [
        'auto_verify' => false, // AI tespiti sonrası kullanıcı onayı gerekir
        'min_overall_confidence' => 0.75,
    ]

];
