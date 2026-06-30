<?php

return [
    'providers' => [
        'tcmb',
        'dummy',
    ],
    'currencies' => ['USD', 'EUR', 'GBP'],
    'tcmb_url' => 'https://www.tcmb.gov.tr/kurlar/today.xml',
    'cache_ttl' => 900,
];
