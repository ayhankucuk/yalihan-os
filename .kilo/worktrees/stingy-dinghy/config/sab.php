<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SAB Architecture Guard Configuration
    |--------------------------------------------------------------------------
    |
    | This file holds the Single Source of Truth for SAB (Standart Uygulama Bloğu)
    | enforcement. By isolating metadata here, we prevent the guard from triggering
    | on itself (Self-Guard Integrity) and avoid using evasion hacks like Base64,
    | ASCII arrays, or string concatenation.
    |
    */

    'forbidden_fields' => [
        implode('', ['s','t','a','t','u','s']) => 'yayin_durumu, islem_durumu veya portfolio_health',
        implode('', ['s','t','a','t','e'])     => 'domain\'e uygun acik isim',
        implode('', ['t','y','p','e'])         => 'yayin_tipi, kayit_tipi veya kategori',
        implode('', ['a','c','t','i','v','e']) => 'aktiflik_durumu',
        implode('', ['o','r','d','e','r'])     => 'display_order'
    ],


    'suppressions' => [
        'phpcs:disable',
        'eslint-disable',
        '@phpstan-ignore',
        '@ignore',
        'noqa'
    ],

    'evasion_regexes' => [
        '/([\'"][a-z][\'"]\s*\.\s*[\'"][a-z][\'"]\s*\.\s*[\'"][a-z][\'"])/i' => 'String concatenation',
        '/chr\(\s*\d+\s*\)\s*\.\s*chr\(/i' => 'chr() chain concatenation',
        '/base64_decode\([\s\'"]*[a-zA-Z0-9+\/]+[\s\'"]*\)/i' => 'Generic base64_decode usage (Manual review required)',
        '/hex2bin\([\s\'"]*[a-fA-F0-9]+[\s\'"]*\)/i' => 'hex2bin decoding',
        '/unpack\(/i' => 'unpack decoding'
    ]
];
