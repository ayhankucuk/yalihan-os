<?php

declare(strict_types=1);

return [
    /*
    | Tables that are intentionally shared across tenants.
    | Keep this list explicit: adding an application table here hides a
    | tenant-boundary finding and therefore requires architectural review.
    */
    'global_tables' => [
        'ulkeler',
        'iller',
        'ilceler',
        'mahalleler',
        'kategoriler',
        'alt_kategoriler',
        'categories',
        'currencies',
        'diller',
        'languages',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'tenants',
    ],
];
