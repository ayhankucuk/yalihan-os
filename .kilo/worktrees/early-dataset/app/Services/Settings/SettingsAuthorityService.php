<?php

namespace App\Services\Settings;

use App\Contracts\Settings\SettingsAuthorityInterface;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use App\Services\Logging\LogService;
use App\Services\Cache\CacheService;
use Exception;

/**
 * 🛡️ SAB SEALED: Settings Authority Service
 * 
 * Canonical write authority for Settings.
 * Centralizes cache invalidation and database mutations to prevent split-brain issues.
 */
class SettingsAuthorityService implements SettingsAuthorityInterface
{
    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, string $group = 'general', ?string $type = null, ?string $description = null): void
    {
        // Auto-detect type if not provided
        if (! $type) {
            $type = is_bool($value) ? 'boolean'
                  : (is_array($value) ? 'json'
                  : (is_numeric($value) ? 'integer'
                  : 'string'));
        }

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'group' => $group,
                'type' => $type, // context7-ignore
                'description' => $description,
            ]
        );

        $this->invalidateKey($key, $group);
    }

    /**
     * @inheritDoc
     */
    public function bulkUpdate(array $settings): void
    {
        try {
            foreach ($settings as $key => $value) {
                // Determine type based on key
                $type = 'string';
                if (in_array($key, ['qrcode_default_size', 'navigation_similar_limit', 'max_upload_size', 'session_lifetime'])) {
                    $type = 'integer';
                } elseif (in_array($key, ['qrcode_durumu', 'qrcode_show_on_cards', 'qrcode_show_on_detail',
                    'navigation_durumu', 'navigation_show_similar',
                    'email_notifications', 'sms_notifications',
                    'ai_auto_description', 'ai_smart_tags',
                    'user_registration', 'password_strength', 'maintenance_mode'])) {
                    $type = 'boolean';
                }

                // Determine group based on key prefix
                $group = 'general';
                if (str_starts_with($key, 'qrcode_')) {
                    $group = 'qrcode';
                } elseif (str_starts_with($key, 'navigation_')) {
                    $group = 'navigation';
                } elseif (
                    str_starts_with($key, 'ai_')
                    || str_starts_with($key, 'openai_')
                    || str_starts_with($key, 'deepseek_')
                    || str_starts_with($key, 'google_')
                    || str_starts_with($key, 'anthropic_')
                    || str_starts_with($key, 'ollama_')
                ) {
                    $group = 'ai';
                } elseif (str_starts_with($key, 'email_') || str_starts_with($key, 'smtp_')) {
                    $group = 'email';
                } elseif (str_starts_with($key, 'social_')) {
                    $group = 'social';
                } elseif (str_starts_with($key, 'seo_') || str_starts_with($key, 'google_analytics')) {
                    $group = 'seo';
                } elseif (str_starts_with($key, 'currency_') || str_starts_with($key, 'default_currency') || str_starts_with($key, 'price_')) {
                    $group = 'currency';
                } elseif (str_starts_with($key, 'user_') || str_starts_with($key, 'password_')) {
                    $group = 'system';
                } elseif (str_starts_with($key, 'maintenance_') || str_starts_with($key, 'max_upload_') || str_starts_with($key, 'session_')) {
                    $group = 'system';
                }

                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => is_array($value) ? json_encode($value) : (string) $value,
                        'type' => $type, // context7-ignore
                        'group' => $group,
                    ]
                );

                $this->invalidateKey($key, $group);
            }

            // Clear secondary module caches
            try {
                $cacheStore = Cache::getStore();
                if (method_exists($cacheStore, 'tags')) {
                    $cacheService = app(CacheService::class);
                    $cacheService->flushByPrefix('qrcode');
                    $cacheService->flushByPrefix('navigation');
                } else {
                    app(CacheService::class)->flush();
                }
            } catch (Exception $e) { // @sab-ignore-catch — Legitimate fallback: tag flush failed → full flush
                LogService::error('Settings cache tag flush failed, falling back to full flush', [], $e);
                app(CacheService::class)->flush();
            }

        } catch (Exception $e) {
            LogService::error('Settings bulk update failed', [], $e);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function flushCache(): void
    {
        $keys = Setting::pluck('key');
        foreach ($keys as $key) {
            Cache::forget(ConfigurationRegistry::getCacheKey($key));
            Cache::forget('setting.'.$key); // Legacy cleanup
            Cache::forget('setting_'.$key); // Legacy cleanup
        }
        
        Cache::forget(ConfigurationRegistry::getCacheKey('groups'));
        Cache::forget('settings.groups'); // Legacy cleanup

        $groups = Setting::distinct()->pluck('group');
        foreach ($groups as $group) {
            Cache::forget(ConfigurationRegistry::getGroupCacheKey($group));
            Cache::forget('settings.group.'.$group); // Legacy cleanup
        }
    }

    /**
     * Invalidate specific keys safely.
     */
    protected function invalidateKey(string $key, string $group): void
    {
        Cache::forget(ConfigurationRegistry::getCacheKey($key));
        Cache::forget(ConfigurationRegistry::getGroupCacheKey($group));
        Cache::forget(ConfigurationRegistry::getCacheKey('groups'));

        // Legacy cleanup (temporarily kept to avoid disrupting older sessions)
        Cache::forget('setting.'.$key);
        Cache::forget('setting_'.$key);
        Cache::forget('settings.groups');
        Cache::forget('settings.group.'.$group);
    }
}
