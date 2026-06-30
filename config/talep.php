<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Talep Modülü Yapılandırma
    |--------------------------------------------------------------------------
    |
    | Bu dosya, Talep modülünün temel yapılandırma ayarlarını içerir.
    |
    */

    'analiz' => [
        // AI analizi için ayarlar
        'ai_engine' => env('TALEP_AI_ENGINE', 'openai'),
        'match_threshold' => 60, // En az %60 eşleşme skoru olan ilanları getir
        'max_results' => 5, // Varsayılan olarak en fazla 5 eşleşme göster
    ],

    // Talep kategorileri
    'kategoriler' => [
        'satis' => 'Satılık',
        'kira' => 'Kiralık',
        'gunluk' => 'Günlük Kiralık',
    ],

    // Talep öncelikleri
    'oncelikler' => [
        'normal' => 'Normal',
        'yuksek' => 'Yüksek',
        'acil' => 'Acil',
    ],

    // Talep statusları
    'statuslar' => [
        'active' => 'Aktif',
        'beklemede' => 'Beklemede',
        'tamamlandi' => 'Tamamlandı',
        'iptal' => 'İptal Edildi',
    ],

    // Talep analizi için puan sistemi
    'puan_sistemi' => [
        'il_uyumu' => 15,
        'ilce_uyumu' => 10,
        'mahalle_uyumu' => 10,
        'tur_uyumu' => 15,
        'fiyat_uyumu' => 20,
        'metrekare_uyumu' => 10,
        'oda_sayisi_uyumu' => 10,
        'ozellikler_uyumu' => 10,
    ],
];
