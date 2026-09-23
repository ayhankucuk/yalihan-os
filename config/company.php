<?php

return [
    'name' => env('COMPANY_NAME', 'Yalıhan Emlak'),
    'address' => env('COMPANY_ADDRESS', 'Yalıkavak, Şeyhül İslam Ömer Lütfi Cd. No:10 D:C, 48400 Bodrum/Muğla'),
    'phone' => env('COMPANY_PHONE', '0533 209 03 02'),
    'email' => env('COMPANY_EMAIL', 'info@yalihanemlak.com.tr'),
    'whatsapp_url' => 'https://wa.me/905332090302',
    'social' => [
        'facebook' => env('COMPANY_FACEBOOK', null),
        'twitter' => env('COMPANY_TWITTER', null),
        'instagram' => env('COMPANY_INSTAGRAM', null),
        'linkedin' => env('COMPANY_LINKEDIN', null),
    ],
];
