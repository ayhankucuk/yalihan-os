<?php

namespace App\Services\Settings;

use App\Contracts\Settings\ConfigurationRegistryInterface;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 🛡️ SAB SEALED: Configuration Registry
 * 
 * Canonical read authority for Settings.
 * Centralizes cache keys to prevent split-brain issues.
 */
class ConfigurationRegistry implements ConfigurationRegistryInterface
{
    private const CACHE_PREFIX = 'sab:setting:';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * @inheritDoc
     */
    public function getGroup(string $group): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . "group:{$group}", self::CACHE_TTL, function () use ($group) {
            return Setting::where('group', $group)->get();
        });
    }

    /**
     * @inheritDoc
     */
    public function getGroups(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'groups', self::CACHE_TTL, function () {
            return Setting::selectRaw('`group`, COUNT(*) as count')
                ->groupBy('group')
                ->orderBy('group') // context7-ignore
                ->get()
                ->pluck('count', 'group')
                ->toArray();
        });
    }

    /**
     * Helper to generate the canonical cache key.
     * Used by SettingsAuthorityService for invalidation.
     */
    public static function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Helper to generate the canonical group cache key.
     */
    public static function getGroupCacheKey(string $group): string
    {
        return self::CACHE_PREFIX . "group:{$group}";
    }
}
