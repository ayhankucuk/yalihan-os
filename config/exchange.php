<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Döviz Kurları
    |--------------------------------------------------------------------------
    |
    | TRY karşıısı döviz kurları. Bu değerler fiat para birimi dönüşümlerinde
    | kullanılır. Canlı kur için bir API servisinden güncellenmelidir.
    |
    */

    'eur_try' => env('EXCHANGE_EUR_TRY', 38.5),
    'usd_try' => env('EXCHANGE_USD_TRY', 36.8),
    'gbp_try' => env('EXCHANGE_GBP_TRY', 44.0),

    /*
    |--------------------------------------------------------------------------
    | Kur Kaynağı
    |--------------------------------------------------------------------------
    */
    'source' => env('EXCHANGE_SOURCE', 'manual'), // 'manual' | 'api'
    'api_url' => env('EXCHANGE_API_URL', 'https://api.exchangerate-api.com/v4/latest/TRY'),
    'cache_ttl' => env('EXCHANGE_CACHE_TTL', 3600), // saniye cinsinden
];
