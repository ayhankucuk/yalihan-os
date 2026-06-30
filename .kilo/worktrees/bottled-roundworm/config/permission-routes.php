<?php

/**
 * Permission Routes Registry - Merkezi Permission-Based Route Yönetimi
 *
 * Context7 Standard: C7-PERMISSION-ROUTES-2025-12-06
 *
 * Her route için gerekli permission'ları tanımlar.
 * Permission kontrolü ile route erişimi yönetilir.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Routes - Permission Mapping
    |--------------------------------------------------------------------------
    |
    | Her route için gerekli permission'ları tanımlar.
    | Format: 'route.name' => ['permission1', 'permission2'] veya 'gate.name'
    |
    */

    'admin' => [
        'dashboard' => [
            'index' => 'view-admin-panel',
            'root' => 'view-admin-panel',
        ],

        'kullanicilar' => [
            'index' => 'manage-users',
            'create' => 'manage-users',
            'store' => 'manage-users',
            'show' => 'manage-users',
            'edit' => 'manage-users',
            'update' => 'manage-users',
            'destroy' => 'manage-users',
            'permissions' => [
                'update' => 'manage-users',
            ],
        ],

        'ilanlar' => [
            'index' => 'manage-ilanlar',
            'create' => 'manage-ilanlar',
            'store' => 'manage-ilanlar',
            'show' => 'manage-ilanlar',
            'edit' => 'edit-ilanlar',
            'update' => 'edit-ilanlar',
            'destroy' => 'manage-ilanlar',
        ],

        'kisiler' => [
            'index' => 'view-admin-panel',
            'create' => 'view-admin-panel',
            'store' => 'view-admin-panel',
            'show' => 'view-admin-panel',
            'edit' => 'view-admin-panel',
            'update' => 'view-admin-panel',
            'destroy' => 'view-admin-panel',
        ],

        'finans' => [
            'islemler' => [
                'index' => 'view-admin-panel',
                'create' => 'view-admin-panel',
                'store' => 'view-admin-panel',
                'show' => 'view-admin-panel',
                'edit' => 'view-admin-panel',
                'update' => 'view-admin-panel',
                'destroy' => 'view-admin-panel',
            ],
        ],

        'crm' => [
            'dashboard' => 'view-admin-panel',
            'customers' => [
                'index' => 'view-admin-panel',
                'show' => 'view-admin-panel',
            ],
        ],

        'intelligence' => [
            'opportunities' => 'view-admin-panel',
        ],

        'ayarlar' => [
            'index' => 'manage-settings',
            'create' => 'manage-settings',
            'store' => 'manage-settings',
            'show' => 'manage-settings',
            'edit' => 'manage-settings',
            'update' => 'manage-settings',
            'destroy' => 'manage-settings',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Types
    |--------------------------------------------------------------------------
    |
    | Permission türleri:
    | - 'gate': Laravel Gate kullanımı (Gate::allows())
    | - 'permission': Spatie Permission kullanımı (hasPermissionTo())
    | - 'role': Role-based kontrol (hasRole())
    | - 'policy': Policy kullanımı (authorize())
    |
    */

    'types' => [
        'gate' => [
            'view-admin-panel',
            'manage-users',
            'manage-settings',
            'manage-ilanlar',
            'edit-ilanlar',
        ],
        'permission' => [
            // Spatie Permission permission'ları buraya eklenir
        ],
        'role' => [
            'superadmin',
            'admin',
            'danisman',
            'editor',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Permissions
    |--------------------------------------------------------------------------
    |
    | Varsayılan permission'lar (route tanımlı değilse)
    |
    */

    'default' => 'view-admin-panel',

    /*
    |--------------------------------------------------------------------------
    | Public Routes (No Permission Required)
    |--------------------------------------------------------------------------
    |
    | Permission gerektirmeyen public route'lar
    |
    */

    'public' => [
        'home',
        'login',
        'register',
        'password.request',
        'password.reset',
    ],
];
