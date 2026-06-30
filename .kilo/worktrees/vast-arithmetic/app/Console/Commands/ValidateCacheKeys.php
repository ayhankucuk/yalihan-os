<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidateCacheKeys extends Command
{
    protected $signature = 'cache-keys:validate';
    protected $description = 'Validate cache keys against central registry and detect conflicts';

    public function handle()
    {
        $this->info('🔍 Cache Keys validation başlatılıyor...');

        $cacheKeys = config('cache-keys', []);
        $errors = [];
        $warnings = [];
        $conflicts = [];

        // Tüm cache key'leri kontrol et
        $this->validateCacheKeys($cacheKeys, $errors, $warnings);

        // Hardcoded cache key'leri tespit et
        $this->detectHardcodedKeys($warnings);

        // Cache key çakışmalarını tespit et
        $this->detectConflicts($conflicts);

        // Sonuçları göster
        if (empty($errors) && empty($warnings) && empty($conflicts)) {
            $this->info('✅ Tüm cache key\'ler geçerli!');
            return 0;
        }

        if (!empty($errors)) {
            $this->error('❌ Hatalar bulundu:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if (!empty($conflicts)) {
            $this->error('⚠️  Cache key çakışmaları:');
            foreach ($conflicts as $conflict) {
                $this->error("  - {$conflict}");
            }
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Uyarılar:');
            foreach (array_slice($warnings, 0, 20) as $warning) {
                $this->warn("  - {$warning}");
            }
            if (count($warnings) > 20) {
                $this->warn("  ... ve " . (count($warnings) - 20) . " uyarı daha");
            }
        }

        return 1;
    }

    protected function validateCacheKeys(array $keys, array &$errors, array &$warnings, string $prefix = '')
    {
        // 'ttl' ve 'tags' gibi özel key'leri atla
        if (in_array($prefix, ['ttl', 'tags'])) {
            return;
        }

        foreach ($keys as $key => $value) {
            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                // Cache key definition kontrolü
                if (isset($value['namespace']) && isset($value['key'])) {
                    $this->validateKeyDefinition($value, $errors, $warnings, $currentPath);
                } else {
                    // Nested structure
                    $this->validateCacheKeys($value, $errors, $warnings, $currentPath);
                }
            }
        }
    }

    protected function validateKeyDefinition(array $config, array &$errors, array &$warnings, string $path)
    {
        // Gerekli alanlar
        if (!isset($config['namespace'])) {
            $errors[] = "Cache key'de 'namespace' eksik: {$path}";
        }

        if (!isset($config['key'])) {
            $errors[] = "Cache key'de 'key' eksik: {$path}";
        }

        // TTL kontrolü
        if (isset($config['ttl'])) {
            $ttl = $config['ttl'];
            if (is_string($ttl)) {
                $ttlPresets = config('cache-keys.ttl', []);
                if (!isset($ttlPresets[$ttl])) {
                    $warnings[] = "TTL preset tanımlı değil: {$ttl} ({$path})";
                }
            } elseif (!is_numeric($ttl)) {
                $warnings[] = "TTL geçersiz format: {$ttl} ({$path})";
            }
        }

        // Tags kontrolü
        if (isset($config['tags']) && is_array($config['tags'])) {
            $definedTags = config('cache-keys.tags', []);
            foreach ($config['tags'] as $tag) {
                if (!in_array($tag, $definedTags) && !in_array($tag, array_values($definedTags))) {
                    $warnings[] = "Tag tanımlı değil: {$tag} ({$path})";
                }
            }
        }
    }

    protected function detectHardcodedKeys(array &$warnings)
    {
        $pattern = '/Cache::(remember|get|put|has|forget)\s*\(\s*[\'"]([^\'"]+)[\'"]/';
        $files = File::allFiles(app_path());

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[2] as $key) {
                    if (!$this->isKeyInConfig($key)) {
                        $warnings[] = "Hardcoded cache key: {$key} ({$file->getRelativePathname()})";
                    }
                }
            }
        }
    }

    protected function detectConflicts(array &$conflicts)
    {
        $cacheKeys = config('cache-keys', []);
        $allKeys = [];

        $this->collectKeys($cacheKeys, $allKeys, '');

        // Aynı key'den birden fazla tanım var mı kontrol et
        $keyCounts = array_count_values($allKeys);
        foreach ($keyCounts as $key => $count) {
            if ($count > 1) {
                $conflicts[] = "Cache key çakışması: {$key} ({$count} kez tanımlı)";
            }
        }
    }

    protected function collectKeys(array $keys, array &$allKeys, string $prefix): void
    {
        foreach ($keys as $key => $value) {
            if (in_array($key, ['ttl', 'tags'])) {
                continue;
            }

            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (isset($value['namespace']) && isset($value['key'])) {
                    // Cache key definition
                    $namespace = $value['namespace'];
                    $keyName = $value['key'];
                    $fullKey = "{$namespace}:{$keyName}";
                    $allKeys[] = $fullKey;
                } else {
                    // Nested structure
                    $this->collectKeys($value, $allKeys, $currentPath);
                }
            }
        }
    }

    protected function isKeyInConfig(string $key): bool
    {
        $cacheKeys = config('cache-keys', []);

        // Basit kontrol: Key config'de var mı?
        // Daha detaylı kontrol için key pattern matching yapılabilir
        return $this->findKeyInConfig($key, $cacheKeys);
    }

    protected function findKeyInConfig(string $searchKey, array $config, string $path = ''): bool
    {
        foreach ($config as $key => $value) {
            if (in_array($key, ['ttl', 'tags'])) {
                continue;
            }

            if (is_array($value)) {
                if (isset($value['namespace'], $value['key'])) {
                    $fullKey = "{$value['namespace']}:{$value['key']}";
                    if (str_contains($searchKey, $fullKey) || str_contains($fullKey, $searchKey)) {
                        return true;
                    }
                } else {
                    $currentPath = $path ? "{$path}.{$key}" : $key;
                    if ($this->findKeyInConfig($searchKey, $value, $currentPath)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
