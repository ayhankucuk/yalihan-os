<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Context7 Dead Code Allowlist
    |--------------------------------------------------------------------------
    |
    | Buradaki sınıflar ve metodlar "Dead Code" taramasından muaf tutulur.
    | Her giriş için bir 'reason' belirtilmesi zorunludur.
    |
    */

    'classes' => [
        // App\Models\Legacy\OldModel::class => 'Legacy support',
    ],

    'methods' => [
        // 'App\Http\Controllers\SpecialController@handle' => 'Dynamic framework call',
    ],

    'paths' => [
        'app/Providers/*',
        'app/Console/Kernel.php',
        'app/Http/Kernel.php',
        'app/Http/Middleware/*',
        'app/Observers/*',
        'app/Policies/*',
    ],

    'namespaces' => [
        'App\Providers',
        'App\Observers',
        'App\Policies',
    ],
];
