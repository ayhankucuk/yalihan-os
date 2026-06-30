<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;

/**
 * @deprecated SAB S1 — AI-specific cache invalidation.
 * SettingsAuthorityService now handles canonical settings cache invalidation.
 * This class is kept as a non-breaking delegate for application-level AI cache keys.
 * New code MUST NOT add new methods here.
 *
 * Consumers to migrate:
 * - App\Http\Controllers\Admin\AISettingsController (constructor)
 * - App\Http\Controllers\AI\AdvancedAIController (constructor)
 * - App\Http\Controllers\Admin\IntegrationsController (constructor)
 * - App\Services\System\IntegrationConfigService (constructor)
 */
class AiSettingsCacheService
{
    // --- INVALIDATE — tekil ---

    public function invalidateModelSettings(): void
    {
        Cache::forget('ollama_model');
        Cache::forget('ai_current_model');
    }

    public function invalidateProviderConfig(): void
    {
        Cache::forget('ai_provider_config');
    }

    public function invalidateProvider(): void
    {
        Cache::forget('ai_provider');
    }

    public function invalidateOllamaUrl(): void
    {
        Cache::forget('ollama_url');
    }

    public function invalidateDashboardData(): void
    {
        Cache::forget('ai_dashboard_data');
    }

    public function invalidateVoiceSettings(): void
    {
        Cache::forget('settings.group.voice');
    }

    // --- INVALIDATE — toplu (controller methodlarına karşılık gelir) ---

    /**
     * updateModel sonrası: model + provider key'lerini temizle.
     */
    public function invalidateAfterModelUpdate(): void
    {
        $this->invalidateModelSettings();
        $this->invalidateProvider();
    }

    /**
     * updateApiKey sonrası: provider config'i temizle.
     */
    public function invalidateAfterApiKeyUpdate(): void
    {
        $this->invalidateProviderConfig();
    }

    /**
     * updateOllamaUrl sonrası: url + config temizle.
     */
    public function invalidateAfterOllamaUrlUpdate(): void
    {
        $this->invalidateOllamaUrl();
        $this->invalidateProviderConfig();
    }

    /**
     * updateProviderModel sonrası: model + config temizle.
     */
    public function invalidateAfterProviderModelUpdate(): void
    {
        $this->invalidateModelSettings();
        $this->invalidateProviderConfig();
    }

    /**
     * Provider/voice ayarı değiştiğinde.
     */
    public function invalidateProviderAndVoice(): void
    {
        $this->invalidateProvider();
        $this->invalidateVoiceSettings();
    }

    // --- Integration settings cache PUT ---

    /**
     * IntegrationsController::update — entegrasyon ayarlarını cache'e yaz.
     */
    public function putIntegrationSettings(string $integration, array $settings): void
    {
        Cache::put("integration_settings_{$integration}", $settings, now()->addDays(7));
    }
}
