<?php

/**
 * Merkezi Event System Configuration
 *
 * Context7 Standard: C7-EVENT-SYSTEM-2025-12-06
 * Yalıhan Bekçi: Temiz, düzenli, merkezi yönetim
 *
 * Bu dosya tüm event'lerin merkezi tanımlarını içerir.
 * Event dispatch, listener mapping ve metadata buradan yönetilir.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Event Definitions
    |--------------------------------------------------------------------------
    |
    | Her event için:
    | - class: Event class adı
    | - listeners: Bu event'i dinleyen listener'lar
    | - broadcast: WebSocket/Pusher broadcast yapılsın mı?
    | - queue: Queue'da çalışsın mı?
    | - description: Event açıklaması
    |
    */

    'definitions' => [
        'ilan.created' => [
            'class' => \App\Events\IlanCreated::class,
            'listeners' => [
                \App\Listeners\FindMatchingDemands::class,
            ],
            'broadcast' => false,
            'queue' => true,
            'description' => 'Yeni ilan oluşturulduğunda fırlatılır',
            'category' => 'ilan',
        ],

        'ilan.price_changed' => [
            'class' => \App\Events\IlanPriceChanged::class,
            'listeners' => [
                \App\Listeners\NotifyN8nOnIlanPriceChanged::class,
            ],
            'broadcast' => false,
            'queue' => true,
            'description' => 'İlan fiyatı değiştiğinde fırlatılır',
            'category' => 'ilan',
        ],

        'talep.received' => [
            'class' => \App\Events\TalepReceived::class,
            'listeners' => [
                \App\Jobs\AnalyzeAndPrioritizeDemand::class,
            ],
            'broadcast' => false,
            'queue' => true,
            'description' => 'Yeni talep oluşturulduğunda fırlatılır',
            'category' => 'talep',
        ],

        'gorev.created' => [
            'class' => \App\Events\GorevCreated::class,
            'listeners' => [
                \App\Listeners\NotifyN8nOnGorevCreated::class,
            ],
            'broadcast' => false,
            'queue' => true,
            'description' => 'Yeni görev oluşturulduğunda fırlatılır',
            'category' => 'gorev',
        ],

        'gorev.status_changed' => [
            'class' => \App\Events\GorevDurumChanged::class,
            'listeners' => [
                \App\Listeners\NotifyN8nOnGorevDurumChanged::class,
            ],
            'broadcast' => false,
            'queue' => true,
            'description' => 'Görev statusu değiştiğinde fırlatılır',
            'category' => 'gorev',
        ],

        'gorev.deadline_yaklasiyor' => [
            'class' => \App\Events\GorevDeadlineYaklasiyor::class,
            'listeners' => [
                \App\Listeners\NotifyN8nOnGorevDeadlineYaklasiyor::class,
            ],
            'broadcast' => false,
            'queue' => true,
            'description' => 'Görev deadline\'ı yaklaştığında fırlatılır',
            'category' => 'gorev',
        ],

        'gorev.gecikti' => [
            'class' => \App\Events\GorevGecikti::class,
            'listeners' => [
                \App\Listeners\NotifyN8nOnGorevGecikti::class,
            ],
            'broadcast' => false,
            'queue' => true,
            'description' => 'Görev geciktiğinde fırlatılır',
            'category' => 'gorev',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Categories
    |--------------------------------------------------------------------------
    |
    | Event'ler kategori bazında gruplandırılır.
    |
    */

    'categories' => [
        'ilan' => [
            'name' => 'İlan Event\'leri',
            'description' => 'İlan oluşturma, güncelleme ve fiyat değişiklikleri',
        ],
        'talep' => [
            'name' => 'Talep Event\'leri',
            'description' => 'Talep oluşturma ve işleme',
        ],
        'gorev' => [
            'name' => 'Görev Event\'leri',
            'description' => 'Görev oluşturma, status değişiklikleri ve deadline takibi',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcast Channels
    |--------------------------------------------------------------------------
    |
    | WebSocket/Pusher broadcast kanalları.
    |
    */

    'broadcast_channels' => [
        'ilan' => 'ilan.{id}',
        'talep' => 'talep.{id}',
        'gorev' => 'gorev.{id}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Event queue ayarları.
    |
    */

    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'sync'),
        'queue' => 'events',
    ],
];
