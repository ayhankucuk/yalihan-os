<?php

return [
    /*
    |--------------------------------------------------------------------------
    | n8n Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | YalıhanAI → n8n → Telegram/WhatsApp/Slack entegrasyonu
    | n8n "Diplomat" görevi görür - Laravel ile dış dünya arasında köprü
    |
    */

    'enabled' => env('N8N_ENABLED', false),

    'webhooks' => [
        // 🎯 Yüksek Skorlu Eşleşmeler (%90+)
        // n8n Akışı: Webhook → Cortex AI Analizi → Telegram Bildirimi
        'high_match' => env('N8N_WEBHOOK_HIGH_MATCH', null),

        // 🏠 Yeni İlanlar
        // n8n Akışı: Webhook → Bekleyen Talepler Kontrolü → Eşleştirme
        'new_listing' => env('N8N_WEBHOOK_NEW_LISTING', null),

        // ✅ Karşılanan Talepler
        // n8n Akışı: Webhook → Başarı Bildirimi → Danışman Performans Kaydı
        'demand_fulfilled' => env('N8N_WEBHOOK_DEMAND_FULFILLED', null),

        // 🚨 Kritik Güncellemeler
        // n8n Akışı: Webhook → Durum Değişikliği Analizi → İlgili Taraflara Bildirim
        'critical_update' => env('N8N_WEBHOOK_CRITICAL_UPDATE', null),
        
        // 📄 [NEURAL_HANDSHAKE] Mühürlü Rapor Bildirimleri
        // n8n Akışı: Webhook → VIP Filtrele → WhatsApp/Telegram Gönder
        'rapor_bildirimi' => env('N8N_WEBHOOK_RAPOR_BILDIRIMI', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bildirim Eşikleri
    |--------------------------------------------------------------------------
    */

    'thresholds' => [
        // Minimum eşleşme skoru (altındakiler bildirilmez)
        'min_match_score' => env('N8N_MIN_MATCH_SCORE', 90),

        // Yeni ilan bildirimi gecikmesi (saniye)
        'new_listing_delay' => env('N8N_NEW_LISTING_DELAY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry & Timeout Ayarları
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout' => env('N8N_HTTP_TIMEOUT', 10), // saniye
        'retry_times' => env('N8N_RETRY_TIMES', 3),
        'retry_delay' => env('N8N_RETRY_DELAY', 100), // milisaniye
    ],

    /*
    |--------------------------------------------------------------------------
    | Cortex AI Entegrasyonu
    |--------------------------------------------------------------------------
    |
    | n8n üzerinde AI Agent nodları için meta data
    |
    */

    'cortex_integration' => [
        'enabled' => env('N8N_CORTEX_ENABLED', true),
        
        // Cortex'in analiz etmesi gereken olaylar
        'analyze_events' => [
            'high_match',
            'new_listing',
        ],
    ],
];
