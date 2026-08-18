<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Auth\AuthServiceProvider::class,
    App\Providers\AIServiceProvider::class,
    App\Providers\TemplateServiceProvider::class,
    App\Providers\GovernanceServiceProvider::class,
    // Telescope loads only when installed (require-dev) AND local env — safe for --no-dev production builds
    ...(is_dir(__DIR__ . '/../vendor/laravel/telescope') && app()->environment('local')
        ? [App\Providers\TelescopeServiceProvider::class]
        : []),
];
