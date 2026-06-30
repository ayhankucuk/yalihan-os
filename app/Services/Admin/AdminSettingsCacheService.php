<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;

/**
 * @deprecated SAB S1 — Admin-specific cache invalidation.
 * SettingsAuthorityService now handles canonical settings cache invalidation.
 * This class is kept as a non-breaking delegate for admin/blog/UPS cache keys.
 * New code MUST NOT add new methods here.
 *
 * Consumers to migrate:
 * - App\Http\Controllers\Admin\AdminController (clearSharedDataCache)
 * - App\Http\Controllers\Admin\BlogController (comment operations)
 * - App\Http\Controllers\Admin\UpsFeatureWhitelistController (CRUD)
 * - App\Actions\Admin\Ups\DeleteFeatureWhitelistAction
 * - App\Actions\Admin\Ups\UpdateFeatureWhitelistAction
 * - App\Actions\Admin\Ups\StoreFeatureWhitelistAction
 */
class AdminSettingsCacheService
{
    private const ADMIN_TTL = 3600;  // 1 saat
    private const BLOG_TTL  = 600;   // 10 dakika
    private const VOICE_TTL = 86400; // 1 gün

    // --- INVALIDATE — admin shared data ---

    /**
     * AdminController::clearSharedDataCache ile birebir eşleşir.
     */
    public function invalidateAdminSharedData(): void
    {
        Cache::forget('admin.etiketler');
        Cache::forget('admin.ulkeler');
        Cache::forget('admin.yayin_tipleri');
    }

    // --- INVALIDATE — blog ---

    public function invalidateBlogComments(): void
    {
        Cache::forget('blog_comments_stats');
    }

    // --- INVALIDATE — ups features ---

    /**
     * UpsFeatureWhitelistController — ups_features_* prefix'li key'leri temizle.
     */
    public function invalidateUpsFeatures(): void
    {
        Cache::forget('ups_features_all');
        Cache::forget('ups_features_active');
        Cache::forget('ups_features_whitelist');
    }

    // --- INVALIDATE — voice / integration ---

    public function invalidateVoiceSettings(): void
    {
        Cache::forget('settings.group.voice');
    }

    public function invalidateIntegrationSettings(string $integration): void
    {
        Cache::forget("integration_settings_{$integration}");
    }
}
