<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Trait IncrementsStateVersion
 * 
 * Model her güncellendiğinde veya silindiğinde global uygulama versionunu artırır.
 * Frontend bu versionu kontrol ederek kendi cache'ini invalidate eder.
 */
trait IncrementsStateVersion
{
    public static function bootIncrementsStateVersion()
    {
        static::saved(function ($model) {
            static::incrementStateVersion();
        });

        static::deleted(function ($model) {
            static::incrementStateVersion();
        });
    }

    public static function incrementStateVersion()
    {
        Cache::put('app_state_version', now()->timestamp, 86400); // 24h
        
        // Opsiyonel: Kategori spesifik versioning
        if (property_exists(static::class, 'stateCategory')) {
            Cache::put('app_state_version_' . static::$stateCategory, now()->timestamp, 86400);
        }
    }
}
