<?php

/**
 * Route Registry - Merkezi Route Yönetim Sistemi
 *
 * Context7 Standard: C7-ROUTE-REGISTRY-2025-12-06
 *
 * Merkezi route kayıt sistemi.
 * Tüm route'lar burada tanımlanır ve view'larda kullanılır.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'dashboard' => [
            'index' => 'admin.dashboard.index',
            'root' => 'admin.dashboard',
        ],
        'kullanicilar' => [
            'index' => 'admin.kullanicilar.index',
            'create' => 'admin.kullanicilar.create',
            'store' => 'admin.kullanicilar.store',
            'show' => 'admin.kullanicilar.show',
            'edit' => 'admin.kullanicilar.edit',
            'update' => 'admin.kullanicilar.update',
            'destroy' => 'admin.kullanicilar.destroy',
            'permissions' => [
                'update' => 'admin.kullanicilar.permissions.update',
            ],
        ],
        'ilanlar' => [
            'index' => 'admin.ilanlar.index',
            'create' => 'admin.ilanlar.create',
            'store' => 'admin.ilanlar.store',
            'show' => 'admin.ilanlar.show',
            'edit' => 'admin.ilanlar.edit',
            'update' => 'admin.ilanlar.update',
            'destroy' => 'admin.ilanlar.destroy',
        ],
        'kisiler' => [
            'index' => 'admin.kisiler.index',
            'create' => 'admin.kisiler.create',
            'store' => 'admin.kisiler.store',
            'show' => 'admin.kisiler.show',
            'edit' => 'admin.kisiler.edit',
            'update' => 'admin.kisiler.update',
            'destroy' => 'admin.kisiler.destroy',
        ],
        'finans' => [
            'islemler' => [
                'index' => 'admin.finans.islemler.index',
                'create' => 'admin.finans.islemler.create',
                'store' => 'admin.finans.islemler.store',
                'show' => 'admin.finans.islemler.show',
                'edit' => 'admin.finans.islemler.edit',
                'update' => 'admin.finans.islemler.update',
                'destroy' => 'admin.finans.islemler.destroy',
                'approve' => 'admin.finans.islemler.approve',
                'reject' => 'admin.finans.islemler.reject',
                'complete' => 'admin.finans.islemler.complete',
            ],
        ],
        'crm' => [
            'dashboard' => 'admin.crm.dashboard',
            'customers' => [
                'index' => 'admin.crm.customers.index',
                'show' => 'admin.crm.customers.show',
            ],
        ],
        'intelligence' => [
            'opportunities' => 'admin.intelligence.opportunities',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */
    'public' => [
        'home' => 'home',
        'about' => 'about',
        'contact' => 'contact',
        'ilanlar' => [
            'index' => 'ilanlar.index',
            'show' => 'ilanlar.show',
        ],
    ],
];

