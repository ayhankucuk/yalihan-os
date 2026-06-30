<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class ValidateRoutes extends Command
{
    protected $signature = 'route:validate';
    protected $description = 'Validate all routes against central registry';

    public function handle()
    {
        $this->info('🔍 Route validation başlatılıyor...');

        $routes = config('routes', []);
        $errors = [];
        $warnings = [];

        // Tüm route'ları kontrol et
        $this->validateRoutes($routes, $errors, $warnings);

        // View dosyalarında hardcoded route'ları kontrol et
        $this->checkViewFiles($warnings);

        // Sonuçları göster
        if (empty($errors) && empty($warnings)) {
            $this->info('✅ Tüm route\'lar geçerli!');
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

    protected function validateRoutes(array $routes, array &$errors, array &$warnings, string $prefix = '')
    {
        foreach ($routes as $key => $value) {
            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $this->validateRoutes($value, $errors, $warnings, $currentPath);
            } else {
                // Route ismini kontrol et
                if (!Route::has($value)) {
                    $errors[] = "Route tanımlı değil: {$value} (config: routes.{$currentPath})";
                }
            }
        }
    }

    protected function checkViewFiles(array &$warnings)
    {
        $viewPath = resource_path('views');
        $files = File::allFiles($viewPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());
            $pattern = "/route\(['\"](admin\.[^'\"]+)['\"]/";

            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $routeName) {
                    // Config'de kontrol et
                    $found = $this->findInConfig($routeName, config('routes', []));

                    if (!$found) {
                        $warnings[] = "Hardcoded route bulundu: {$routeName} ({$file->getRelativePathname()})";
                    }
                }
            }
        }
    }

    protected function findInConfig(string $routeName, array $config, string $path = ''): bool
    {
        foreach ($config as $key => $value) {
            $currentPath = $path ? "{$path}.{$key}" : $key;

            if (is_array($value)) {
                if ($this->findInConfig($routeName, $value, $currentPath)) {
                    return true;
                }
            } else {
                if ($value === $routeName) {
                    return true;
                }
            }
        }

        return false;
    }
}

