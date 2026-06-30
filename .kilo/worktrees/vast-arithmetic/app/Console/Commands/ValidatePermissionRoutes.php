<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class ValidatePermissionRoutes extends Command
{
    protected $signature = 'permission-routes:validate';
    protected $description = 'Validate permission routes against central registry';

    public function handle()
    {
        $this->info('🔍 Permission Routes validation başlatılıyor...');

        $permissionRoutes = config('permission-routes', []);
        $errors = [];
        $warnings = [];

        // Tüm permission route'ları kontrol et
        $this->validatePermissionRoutes($permissionRoutes, $errors, $warnings);

        // Route'ların permission mapping'de olup olmadığını kontrol et
        $this->checkRoutePermissions($warnings);

        // Sonuçları göster
        if (empty($errors) && empty($warnings)) {
            $this->info('✅ Tüm permission route\'lar geçerli!');
            return 0;
        }

        if (!empty($errors)) {
            $this->error('❌ Hatalar bulundu:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Uyarılar:');
            foreach ($warnings as $warning) {
                $this->warn("  - {$warning}");
            }
        }

        return 1;
    }

    protected function validatePermissionRoutes(array $routes, array &$errors, array &$warnings, string $prefix = '')
    {
        foreach ($routes as $key => $value) {
            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            // 'types', 'default', 'public' gibi özel key'leri atla
            if (in_array($key, ['types', 'default', 'public'])) {
                continue;
            }

            if (is_array($value)) {
                $this->validatePermissionRoutes($value, $errors, $warnings, $currentPath);
            } else {
                // Permission kontrolü
                $permission = $value;
                $permissionTypes = config('permission-routes.types', []);

                $found = false;
                foreach ($permissionTypes as $type => $permissions) {
                    if (in_array($permission, $permissions)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found && !in_array($permission, config('permission-routes.public', []))) {
                    $warnings[] = "Permission tanımlı değil: {$permission} (route: {$currentPath})";
                }
            }
        }
    }

    protected function checkRoutePermissions(array &$warnings)
    {
        $allRoutes = Route::getRoutes();
        $permissionRoutes = config('permission-routes', []);
        $definedRoutes = [];

        // Config'deki route'ları topla
        $this->collectDefinedRoutes($permissionRoutes, $definedRoutes, '');

        // Route'ları kontrol et
        foreach ($allRoutes as $route) {
            $routeName = $route->getName();

            if (!$routeName || strpos($routeName, 'admin.') !== 0) {
                continue;
            }

            // Public route kontrolü
            if (in_array($routeName, config('permission-routes.public', []))) {
                continue;
            }

            // Permission mapping'de var mı kontrol et
            if (!in_array($routeName, $definedRoutes)) {
                $warnings[] = "Route permission mapping'de yok: {$routeName}";
            }
        }
    }

    protected function collectDefinedRoutes(array $routes, array &$definedRoutes, string $prefix): void
    {
        foreach ($routes as $key => $value) {
            // 'types', 'default', 'public' gibi özel key'leri atla
            if (in_array($key, ['types', 'default', 'public'])) {
                continue;
            }

            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $this->collectDefinedRoutes($value, $definedRoutes, $currentPath);
            } else {
                // Route ismi oluştur
                $routeName = $currentPath;
                $definedRoutes[] = $routeName;
            }
        }
    }
}

