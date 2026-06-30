<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class ValidateMenus extends Command
{
    protected $signature = 'menus:validate';
    protected $description = 'Validate menus against central registry';

    public function handle()
    {
        $this->info('🔍 Menu validation başlatılıyor...');

        $menus = config('menus', []);
        $errors = [];
        $warnings = [];

        // Tüm menu'ları kontrol et
        $this->validateMenus($menus, $errors, $warnings);

        // Sonuçları göster
        if (empty($errors) && empty($warnings)) {
            $this->info('✅ Tüm menu\'lar geçerli!');
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

    protected function validateMenus(array $menus, array &$errors, array &$warnings, string $prefix = '')
    {
        foreach ($menus as $key => $value) {
            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            // 'icons' gibi özel key'leri atla
            if ($key === 'icons') {
                continue;
            }

            if (is_array($value)) {
                // Menu item array'i kontrol et
                if (isset($value[0]) && is_array($value[0])) {
                    // Menu item'ları kontrol et
                    foreach ($value as $index => $item) {
                        $this->validateMenuItem($item, $errors, $warnings, "{$currentPath}[{$index}]");
                    }
                } else {
                    // Nested menu kontrolü
                    $this->validateMenus($value, $errors, $warnings, $currentPath);
                }
            }
        }
    }

    protected function validateMenuItem(array $item, array &$errors, array &$warnings, string $path)
    {
        // Gerekli alanlar
        if (!isset($item['id'])) {
            $warnings[] = "Menu item'da 'id' eksik: {$path}";
        }

        if (!isset($item['type'])) {
            $errors[] = "Menu item'da 'type' eksik: {$path}";
        }

        if (!isset($item['name'])) {
            $errors[] = "Menu item'da 'name' eksik: {$path}";
        }

        // Route kontrolü
        if (isset($item['route'])) {
            if (!Route::has($item['route'])) {
                $warnings[] = "Route tanımlı değil: {$item['route']} ({$path})";
            }
        }

        // Permission kontrolü
        if (isset($item['permission'])) {
            $permissionTypes = config('permission-routes.types', []);
            $found = false;

            foreach ($permissionTypes as $type => $permissions) {
                if (in_array($item['permission'], $permissions)) {
                    $found = true;
                    break;
                }
            }

            if (!$found && !in_array($item['permission'], config('permission-routes.public', []))) {
                $warnings[] = "Permission tanımlı değil: {$item['permission']} ({$path})";
            }
        }

        // Children kontrolü
        if (isset($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $index => $child) {
                $this->validateMenuItem($child, $errors, $warnings, "{$path}.children[{$index}]");
            }
        }
    }
}
