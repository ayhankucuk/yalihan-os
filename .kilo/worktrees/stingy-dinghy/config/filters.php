<?php

/**
 * ✅ P2: Advanced Filtering Configuration
 *
 * Provides dynamic filtering UI for:
 * - Kisiler (contacts) - by type, location, activity
 * - Ilanlar (listings) - by price, type, location, date
 * - Talepler (requests) - by status, location, type
 */

return [
    // Contact filters
    'kisiler' => [
        'filters' => [
            'type' => [
                'label' => 'Türü',
                'field' => 'tur',
                'type' => 'select',
                'options' => [
                    'Ev Sahibi' => 'Ev Sahibi',
                    'Emlakçı' => 'Emlakçı',
                    'Yatırımcı' => 'Yatırımcı',
                    'Kiracı' => 'Kiracı',
                ],
            ],
            'location' => [
                'label' => 'Lokasyon',
                'field' => 'il_id',
                'type' => 'multiselect',
                'relation' => 'il',
            ],
            'status' => [
                'label' => 'Durum',
                'field' => 'aktiflik_durumu',
                'type' => 'select',
                'options' => [
                    1 => 'Aktif',
                    0 => 'Pasif',
                ],
            ],
            'has_listings' => [
                'label' => 'İlanları Olanlar',
                'type' => 'checkbox',
                'query' => 'whereHas(ilanlar)',
            ],
            'recent' => [
                'label' => 'Son 30 Gün',
                'type' => 'checkbox',
                'query' => 'where("created_at", ">=", now()->subDays(30))',
            ],
        ],
        'sort_fields' => [
            'created_at' => 'Yeni Eklenenler',
            'ad_soyad' => 'Adı Soyadı',
            'updated_at' => 'Son Değişiklik',
        ],
    ],

    // Listing filters
    'ilanlar' => [
        'filters' => [
            'type' => [
                'label' => 'Türü',
                'field' => 'islem_turu',
                'type' => 'select',
                'options' => [
                    'Satış' => 'Satış',
                    'Kiralama' => 'Kiralama',
                    'Yönetim' => 'Yönetim',
                ],
            ],
            'property_type' => [
                'label' => 'Mülk Türü',
                'field' => 'emlak_turu',
                'type' => 'select',
                'options' => [
                    'Konut' => 'Konut',
                    'Ticari' => 'Ticari',
                    'Arsa' => 'Arsa',
                    'İnşaat' => 'İnşaat',
                ],
            ],
            'price_range' => [
                'label' => 'Fiyat Aralığı',
                'type' => 'range',
                'min' => 0,
                'max' => 10000000,
                'field' => 'fiyat',
            ],
            'location' => [
                'label' => 'Lokasyon',
                'field' => 'il_id',
                'type' => 'multiselect',
                'relation' => 'il',
            ],
            'neighborhood' => [
                'label' => 'Mahalle',
                'field' => 'mahalle_id',
                'type' => 'multiselect',
                'relation' => 'mahalle',
            ],
            'featured' => [
                'label' => 'Öne Çıkanlar',
                'field' => 'one_cikan',
                'type' => 'checkbox',
            ],
            'published' => [
                'label' => 'Yayında',
                'field' => 'yayin_durumu',
                'type' => 'select',
                'options' => [
                    'Aktif' => 'Aktif',
                    'Draft' => 'Taslak',
                    'Arşiv' => 'Arşiv',
                ],
            ],
            'recent' => [
                'label' => 'Son 7 Gün',
                'type' => 'checkbox',
                'query' => 'where("created_at", ">=", now()->subDays(7))',
            ],
        ],
        'sort_fields' => [
            'created_at' => 'Yeni Eklenenler',
            'fiyat' => 'Fiyat (Artan)',
            'updated_at' => 'Son Güncellenenler',
            'one_cikan' => 'Öne Çıkanlar',
        ],
    ],

    // Request filters
    'talepler' => [
        'filters' => [
            'type' => [
                'label' => 'Talep Türü',
                'field' => 'islem_turu',
                'type' => 'select',
                'options' => [
                    'Satış' => 'Satış',
                    'Kiralama' => 'Kiralama',
                ],
            ],
            'property_type' => [
                'label' => 'Mülk Türü',
                'field' => 'emlak_turu',
                'type' => 'select',
            ],
            'status' => [
                'label' => 'Durum',
                'field' => 'talep_durumu',
                'type' => 'select',
                'options' => [
                    'Açık' => 'Açık',
                    'Eşleştirildi' => 'Eşleştirildi',
                    'Kapatıldı' => 'Kapatıldı',
                ],
            ],
            'location' => [
                'label' => 'Lokasyon',
                'field' => 'il_id',
                'type' => 'multiselect',
            ],
            'recent' => [
                'label' => 'Son 30 Gün',
                'type' => 'checkbox',
                'query' => 'where("created_at", ">=", now()->subDays(30))',
            ],
        ],
        'sort_fields' => [
            'created_at' => 'Yeni Eklenenler',
            'talep_durumu' => 'Durum',
            'updated_at' => 'Son Güncellenenler',
        ],
    ],

    // Global search configuration
    'search' => [
        'enabled' => true,
        'min_chars' => 2,
        'debounce_ms' => 300,
        'results_limit' => 10,
    ],

    // Advanced filters UI settings
    'ui' => [
        'show_filter_count' => true,
        'show_clear_all' => true,
        'auto_apply' => true,
        'collapse_empty' => false,
    ],
];
