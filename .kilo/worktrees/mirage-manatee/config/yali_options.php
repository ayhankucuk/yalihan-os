<?php

/*
|--------------------------------------------------------------------------
| Yalıhan Emlak Option Dictionaries - Context7 Standard
|--------------------------------------------------------------------------
|
| Form seçim listeleri ve sözlükler
| Source: docs/ai/GEMINI_COMPLETE_SYSTEM_DATA.json v2.0.0
| Context7: C7-OPTIONS-DICT-2025-11-27
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | İmar Durumu Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Arsa kategorisi için imar d-u-r-u-m-u seçenekleri
    |
    */
    'imar_durumu' => [
        'imarli' => [
            'label' => 'İmarlı',
            'description' => 'İmar planında belirtilen imar durumuna sahip arsa',
            'color' => 'green',
            'icon' => '✅',
        ],
        'imarsiz' => [
            'label' => 'İmarsız',
            'description' => 'İmar planı dışında kalan arsa',
            'color' => 'gray',
            'icon' => '⚪',
        ],
        'tarla' => [
            'label' => 'Tarla',
            'description' => 'Tarım arazisi statüsündeki arsa',
            'color' => 'yellow',
            'icon' => '🌾',
        ],
        'villa_imarli' => [
            'label' => 'Villa İmarlı',
            'description' => 'Villa inşaatı için özel imar durumuna sahip arsa',
            'color' => 'purple',
            'icon' => '🏡',
        ],
        'konut_imarli' => [
            'label' => 'Konut İmarlı',
            'description' => 'Konut yapımı için imar durumuna sahip arsa',
            'color' => 'blue',
            'icon' => '🏘️',
        ],
        'ticari_imarli' => [
            'label' => 'Ticari İmarlı',
            'description' => 'Ticari yapı inşaatı için imar durumuna sahip arsa',
            'color' => 'orange',
            'icon' => '🏢',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | KAKS Aralıkları (Kat Alanı Katsayısı)
    |--------------------------------------------------------------------------
    |
    | Arsa için KAKS değeri aralıkları ve açıklamaları
    |
    */
    'kaks_ranges' => [
        '0.00-0.50' => [
            'label' => '0.00 - 0.50',
            'description' => 'Çok düşük yoğunluk (Villa, bahçeli konut)',
            'density' => 'very_low',
        ],
        '0.51-1.00' => [
            'label' => '0.51 - 1.00',
            'description' => 'Düşük yoğunluk (Müstakil ev, az katlı)',
            'density' => 'low',
        ],
        '1.01-2.00' => [
            'label' => '1.01 - 2.00',
            'description' => 'Orta yoğunluk (4-6 katlı konut)',
            'density' => 'medium',
        ],
        '2.01-4.00' => [
            'label' => '2.01 - 4.00',
            'description' => 'Yüksek yoğunluk (8-12 katlı konut)',
            'density' => 'high',
        ],
        '4.01+' => [
            'label' => '4.01+',
            'description' => 'Çok yüksek yoğunluk (Gökdelen, plaza)',
            'density' => 'very_high',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | TAKS Aralıkları (Taban Alanı Katsayısı)
    |--------------------------------------------------------------------------
    |
    | Arsa için TAKS değeri aralıkları ve açıklamaları
    |
    */
    'taks_ranges' => [
        '0.00-0.20' => [
            'label' => '0.00 - 0.20',
            'description' => 'Minimum taban alanı (Geniş bahçe)',
            'coverage' => 'minimal',
        ],
        '0.21-0.35' => [
            'label' => '0.21 - 0.35',
            'description' => 'Düşük taban alanı (Villa, bahçeli)',
            'coverage' => 'low',
        ],
        '0.36-0.50' => [
            'label' => '0.36 - 0.50',
            'description' => 'Orta taban alanı (Standart konut)',
            'coverage' => 'medium',
        ],
        '0.51-0.70' => [
            'label' => '0.51 - 0.70',
            'description' => 'Yüksek taban alanı (Apartman)',
            'coverage' => 'high',
        ],
        '0.71+' => [
            'label' => '0.71+',
            'description' => 'Maksimum taban alanı (Ticari bina)',
            'coverage' => 'maximum',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gabari Aralıkları (Bina Yüksekliği)
    |--------------------------------------------------------------------------
    |
    | Arsa için gabari (yükseklik) aralıkları
    |
    */
    'gabari_ranges' => [
        '0-6.5' => [
            'label' => '0-6.5m',
            'description' => '1-2 kat (Müstakil ev)',
            'floors' => '1-2',
        ],
        '6.51-9.5' => [
            'label' => '6.51-9.5m',
            'description' => '2-3 kat (Müstakil ev, ikiz villa)',
            'floors' => '2-3',
        ],
        '9.51-12.5' => [
            'label' => '9.51-12.5m',
            'description' => '3-4 kat (Apartman)',
            'floors' => '3-4',
        ],
        '12.51-15.5' => [
            'label' => '12.51-15.5m',
            'description' => '4-5 kat (Apartman)',
            'floors' => '4-5',
        ],
        '15.51+' => [
            'label' => '15.51m+',
            'description' => '5+ kat (Yüksek bina)',
            'floors' => '5+',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Altyapı Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Arsa için altyapı bilgileri
    |
    */
    'altyapi' => [
        'elektrik' => [
            'label' => 'Elektrik',
            'icon' => '⚡',
            'field' => 'altyapi_elektrik',
        ],
        'su' => [
            'label' => 'Su',
            'icon' => '💧',
            'field' => 'altyapi_su',
        ],
        'dogalgaz' => [
            'label' => 'Doğalgaz',
            'icon' => '🔥',
            'field' => 'altyapi_dogalgaz',
        ],
        'kanalizasyon' => [
            'label' => 'Kanalizasyon',
            'icon' => '🚰',
            'field' => 'altyapi_kanalizasyon',
        ],
        'telefon' => [
            'label' => 'Telefon',
            'icon' => '📞',
            'field' => 'altyapi_telefon',
        ],
        'internet' => [
            'label' => 'İnternet/Fiber',
            'icon' => '🌐',
            'field' => 'altyapi_internet',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Oda Sayısı Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Konut kategorisi için oda sayısı seçenekleri
    | Context7: C7-KONUT-INTELLIGENCE-2025-11-30
    |
    */
    'oda_sayisi_options' => [
        ['value' => '1+0', 'label' => '1+0 (Stüdyo)', 'color' => 'text-blue-600 bg-blue-50 border-blue-200 dark:text-blue-400 dark:bg-blue-900/20 dark:border-blue-800', 'icon' => '🏠'],
        ['value' => '1+1', 'label' => '1+1', 'color' => 'text-blue-700 bg-blue-100 border-blue-300 dark:text-blue-300 dark:bg-blue-900/30 dark:border-blue-700', 'icon' => '👥'],
        ['value' => '1.5+1', 'label' => '1.5+1', 'color' => 'text-blue-800 bg-blue-100 border-blue-300 dark:text-blue-200 dark:bg-blue-900/30 dark:border-blue-700', 'icon' => '👥'],
        ['value' => '2+1', 'label' => '2+1', 'color' => 'text-green-600 bg-green-50 border-green-200 dark:text-green-400 dark:bg-green-900/20 dark:border-green-800', 'icon' => '👨‍👩‍👧'],
        ['value' => '2.5+1', 'label' => '2.5+1', 'color' => 'text-green-700 bg-green-100 border-green-300 dark:text-green-300 dark:bg-green-900/30 dark:border-green-700', 'icon' => '👨‍👩‍👧'],
        ['value' => '3+1', 'label' => '3+1', 'color' => 'text-orange-600 bg-orange-50 border-orange-200 dark:text-orange-400 dark:bg-orange-900/20 dark:border-orange-800', 'icon' => '👨‍👩‍👧‍👦'],
        ['value' => '3.5+1', 'label' => '3.5+1', 'color' => 'text-orange-700 bg-orange-100 border-orange-300 dark:text-orange-300 dark:bg-orange-900/30 dark:border-orange-700', 'icon' => '👨‍👩‍👧‍👦'],
        ['value' => '4+1', 'label' => '4+1', 'color' => 'text-purple-600 bg-purple-50 border-purple-200 dark:text-purple-400 dark:bg-purple-900/20 dark:border-purple-800', 'icon' => '🏰'],
        ['value' => '4.5+1', 'label' => '4.5+1', 'color' => 'text-purple-700 bg-purple-100 border-purple-300 dark:text-purple-300 dark:bg-purple-900/30 dark:border-purple-700', 'icon' => '🏰'],
        ['value' => '5+1', 'label' => '5+1', 'color' => 'text-purple-800 bg-purple-100 border-purple-300 dark:text-purple-200 dark:bg-purple-900/30 dark:border-purple-700', 'icon' => '🏰'],
        ['value' => '5.5+1', 'label' => '5.5+1', 'color' => 'text-purple-900 bg-purple-200 border-purple-400 dark:text-purple-100 dark:bg-purple-900/40 dark:border-purple-600', 'icon' => '🏰'],
        ['value' => '6+1', 'label' => '6+1', 'color' => 'text-indigo-600 bg-indigo-50 border-indigo-200 dark:text-indigo-400 dark:bg-indigo-900/20 dark:border-indigo-800', 'icon' => '🏰'],
        ['value' => '6+2', 'label' => '6+2', 'color' => 'text-indigo-700 bg-indigo-100 border-indigo-300 dark:text-indigo-300 dark:bg-indigo-900/30 dark:border-indigo-700', 'icon' => '🏰'],
        ['value' => '7+1', 'label' => '7+1', 'color' => 'text-indigo-800 bg-indigo-100 border-indigo-300 dark:text-indigo-200 dark:bg-indigo-900/30 dark:border-indigo-700', 'icon' => '🏰'],
        ['value' => '7+2', 'label' => '7+2', 'color' => 'text-indigo-900 bg-indigo-200 border-indigo-400 dark:text-indigo-100 dark:bg-indigo-900/40 dark:border-indigo-600', 'icon' => '🏰'],
        ['value' => '8+1', 'label' => '8+1', 'color' => 'text-pink-600 bg-pink-50 border-pink-200 dark:text-pink-400 dark:bg-pink-900/20 dark:border-pink-800', 'icon' => '🏰'],
        ['value' => '8+2', 'label' => '8+2', 'color' => 'text-pink-700 bg-pink-100 border-pink-300 dark:text-pink-300 dark:bg-pink-900/30 dark:border-pink-700', 'icon' => '🏰'],
        ['value' => '9+1', 'label' => '9+1', 'color' => 'text-pink-800 bg-pink-100 border-pink-300 dark:text-pink-200 dark:bg-pink-900/30 dark:border-pink-700', 'icon' => '🏰'],
        ['value' => '10+1', 'label' => '10+1', 'color' => 'text-pink-900 bg-pink-200 border-pink-400 dark:text-pink-100 dark:bg-pink-900/40 dark:border-pink-600', 'icon' => '🏰'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Banyo Sayısı Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Konut kategorisi için banyo sayısı seçenekleri
    |
    */
    'banyo_sayisi_options' => [
        '1',
        '2',
        '3',
        '4',
        '5',
        '6+',
    ],

    /*
    |--------------------------------------------------------------------------
    | Salon Sayısı Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Konut kategorisi için salon sayısı seçenekleri
    |
    */
    'salon_sayisi_options' => [
        '1',
        '2',
        '3',
        '4+',
    ],

    /*
    |--------------------------------------------------------------------------
    | Isıtma Tipi Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Konut ve yazlık kategorileri için ısıtma tipi seçenekleri
    |
    */
    'isitma_tipi_options' => [
        'Doğalgaz',
        'Kombi',
        'Merkezi',
        'Klima',
        'Soba',
        'Kat Kaloriferi',
        'Yerden Isıtma',
    ],

    /*
    |--------------------------------------------------------------------------
    | Para Birimleri
    |--------------------------------------------------------------------------
    |
    | İlan fiyatlandırması için para birimi seçenekleri
    |
    */
    'para_birimleri' => [
        'TRY',
        'USD',
        'EUR',
        'GBP',
    ],

    /*
    |--------------------------------------------------------------------------
    | Yayın Durumu Seçenekleri
    |--------------------------------------------------------------------------
    |
    | İlan yayın durumu seçenekleri
    | Context7: yayin_durumu field'ı için kullanılır
    |
    */
    'yayin_durumu_options' => [
        'Taslak',
        'Aktif',
        'Pasif',
        'Beklemede',
        'Yayında',
        'Satıldı',
        'Kiralandı',
    ],

    /*
    |--------------------------------------------------------------------------
    | Arsa Tipleri
    |--------------------------------------------------------------------------
    |
    | Arsa kategorisi için arsa tipi seçenekleri
    |
    */
    'arsa_tipleri' => [
        'konut' => 'Konut Arsası',
        'villa' => 'Villa Arsası',
        'ticari' => 'Ticari Arsa',
        'sanayi' => 'Sanayi Arsası',
        'tarim' => 'Tarım Arazisi',
        'bos' => 'Boş Arsa',
        'bag' => 'Bağ',
        'bahce' => 'Bahçe',
        'zeytinlik' => 'Zeytinlik',
        'kaynak_suyu' => 'Kaynak Suylu Arsa',
    ],

    /*
    |--------------------------------------------------------------------------
    | Yola Cephe Tipleri
    |--------------------------------------------------------------------------
    |
    | Arsa için yola cephe tipi seçenekleri
    |
    */
    'yola_cephe_tipleri' => [
        'tek_cephe' => 'Tek Cephe',
        'iki_cephe' => 'İki Cephe (Köşe Başı)',
        'uc_cephe' => 'Üç Cephe',
        'dort_cephe' => 'Dört Cephe (Ada İçi)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Konum Avantajları
    |--------------------------------------------------------------------------
    |
    | İlan için konum avantajı seçenekleri
    |
    */
    'konum_avantajlari' => [
        'denize_yakin' => 'Denize Yakın',
        'deniz_manzarali' => 'Deniz Manzaralı',
        'sehir_manzarali' => 'Şehir Manzaralı',
        'dag_manzarali' => 'Dağ Manzaralı',
        'golf_sahasi_yakin' => 'Golf Sahasına Yakın',
        'marina_yakin' => 'Marina Yakını',
        'havaalani_yakin' => 'Havaalanına Yakın',
        'otoban_yakin' => 'Otobana Yakın',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parsel Nitelikleri (TKGM)
    |--------------------------------------------------------------------------
    |
    | TKGM entegrasyonu için parsel nitelik seçenekleri
    |
    */
    'parsel_nitelikleri' => [
        'konut' => 'Konut',
        'ticaret' => 'Ticaret',
        'sanayi' => 'Sanayi',
        'turizm' => 'Turizm',
        'tarim' => 'Tarım',
        'ormani' => 'Orman',
        'mera' => 'Mera',
    ],

    /*
    |--------------------------------------------------------------------------
    | Yazlık Sezonluk Fiyatlandırma Kuralları
    |--------------------------------------------------------------------------
    |
    | Yazlık kiralama kategorisi için otomatik fiyatlandırma kuralları
    | Context7: C7-YAZLIK-PRICING-AUTOMATION-2025-11-30
    |
    */
    'pricing_rules' => [
        'discounts' => [
            'weekly' => 0.05,
            'monthly' => 0.15,
        ],
        'seasonal_multipliers' => [
            'yaz' => 1.00,
            'ara_sezon' => 0.70,
            'kis' => 0.50,
        ],
        'min_stay' => [
            'yaz' => 3,
            'ara_sezon' => 2,
            'kis' => 1,
        ],
        'long_stay_discounts' => [
            14 => 0.05,
            30 => 0.10,
        ],
        'special_days' => [
            'bayram' => [
                'multiplier' => 1.20,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sezon Tipleri
    |--------------------------------------------------------------------------
    |
    | Yazlık kiralama için sezon tipleri ve görsel tanımlamaları
    |
    */
    'sezon_tipleri' => [
        'yaz' => [
            'label' => 'Yaz Sezonu (Haziran-Ağustos)',
            'color' => 'yellow',
            'icon' => '☀️',
        ],
        'ara_sezon' => [
            'label' => 'Ara Sezon (Eylül-Ekim / Nisan-Mayıs)',
            'color' => 'orange',
            'icon' => '🍂',
        ],
        'kis' => [
            'label' => 'Kış Sezonu (Kasım-Mart)',
            'color' => 'blue',
            'icon' => '❄️',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tapu Tipi Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Konut kategorisi için tapu tipi seçenekleri
    | Context7: C7-OPTIONS-DICT-2025-12-11
    |
    */
    'tapu_tipi_options' => [
        'Kat Mülkiyeti' => 'Kat Mülkiyeti',
        'Kat İrtifakı' => 'Kat İrtifakı',
        'Arsa Tapusu' => 'Arsa Tapusu',
    ],

    /*
    |--------------------------------------------------------------------------
    | Eşyalı Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Konut ve yazlık kategorileri için eşyalı d-u-r-u-m-u seçenekleri
    | Context7: C7-OPTIONS-DICT-2025-12-11
    |
    */
    'esyali_options' => [
        'Hayır' => 'Hayır',
        'Kısmen' => 'Kısmen Eşyalı',
        'Tam Eşyalı' => 'Tam Eşyalı',
    ],

    /*
    |--------------------------------------------------------------------------
    | Check-in Saatleri
    |--------------------------------------------------------------------------
    |
    | Yazlık kiralama için giriş saati seçenekleri
    | Context7: C7-OPTIONS-DICT-2025-12-11
    |
    */
    'check_in_hours' => [
        '14:00' => '14:00',
        '15:00' => '15:00',
        '16:00' => '16:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | Check-out Saatleri
    |--------------------------------------------------------------------------
    |
    | Yazlık kiralama için çıkış saati seçenekleri
    | Context7: C7-OPTIONS-DICT-2025-12-11
    |
    */
    'check_out_hours' => [
        '10:00' => '10:00',
        '11:00' => '11:00',
        '12:00' => '12:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | İptal Politikası Seçenekleri
    |--------------------------------------------------------------------------
    |
    | Yazlık kiralama için iptal politikası seçenekleri
    | Context7: C7-OPTIONS-DICT-2025-12-11
    |
    */
    'iptal_politikasi_options' => [
        'Ücretsiz İptal' => 'Ücretsiz İptal',
        'Kısmi İade' => 'Kısmi İade',
        'İade Yok' => 'İade Yok',
        'Detaylı Bilgi' => 'Detaylı Bilgi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature System Options
    |--------------------------------------------------------------------------
    |
    | Context7: FeatureAssignment-based resolver toggle for wizard
    |
    */
    'features' => [
        // ✅ CRITICAL: .env-driven for safe production rollback
        // Set FEATURES_USE_ASSIGNMENT_RESOLVER=true in .env to enable new resolver
        // Default: false (legacy resolver, safe for production)
        'use_assignment_resolver' => env('FEATURES_USE_ASSIGNMENT_RESOLVER', false),

        // ✅ UPS TemplateResolver toggle (staging scoped)
        // USE_TEMPLATE_RESOLVER=true → enable FeatureTemplateResolver
        // UPS_TEMPLATE_RESOLVER_SCOPE="konut,arsa" → only these kategori slug'larında aktif
        'use_template_resolver' => env('USE_TEMPLATE_RESOLVER', false),
        'template_resolver_scope' => env('UPS_TEMPLATE_RESOLVER_SCOPE', ''),
    ],
];
