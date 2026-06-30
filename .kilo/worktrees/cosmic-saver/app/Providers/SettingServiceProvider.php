<?php

namespace App\Providers;

use App\Contracts\Settings\ConfigurationRegistryInterface;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * 🛡️ SAB SEALED: Setting Service Provider
 * 
 * Hardened config injection system.
 * - Uses ConfigurationRegistry for cached, authoritative reads.
 * - Protects runtime config from masked/placeholder values (••••••••).
 * - Centralizes DB-to-Config mapping.
 */
class SettingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only run if not in console and table exists
        try {
            if (!app()->runningInConsole() && Schema::hasTable('settings')) {
                $this->loadSettingsIntoConfig();
            }
        } catch (\Throwable $e) {
            // Log once but don't break the bootstrap process
            LogService::debug('SettingServiceProvider: Failed to bootstrap settings into config', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Load settings from authoritative registry into Laravel config
     */
    protected function loadSettingsIntoConfig(): void
    {
        $registry = $this->app->make(ConfigurationRegistryInterface::class);

        // 1. Voice Search Injection
        $this->injectGroup($registry, 'voice', 'ai.voice_search.', [
            'voice_provider' => 'default_provider',
            'voice_search_enabled' => 'aktiflik_durumu',
            'voice_language' => 'language',
            'voice_api_key' => 'api_key',
            'voice_auto_submit' => 'auto_submit',
            'voice_max_record_time' => 'max_record_time',
            'voice_sensitivity' => 'sensitivity',
        ]);

        // Sync voice API key to services if not masked
        $voiceApiKey = $registry->get('voice_api_key');
        if ($this->isValidSecret($voiceApiKey)) {
            Config::set('services.openai.api_key', $voiceApiKey);
        }

        // 2. Global AI Injection
        $this->injectGroup($registry, 'ai', 'ai.', [], 'ai_');

        // Sync OpenAI API key to services if not masked
        $openaiKey = $registry->get('openai_api_key');
        if ($this->isValidSecret($openaiKey)) {
            Config::set('services.openai.api_key', $openaiKey);
        }

        // Sync Google/Gemini API key
        $googleKey = $registry->get('google_api_key');
        if ($this->isValidSecret($googleKey)) {
            Config::set('services.google_speech.api_key', $googleKey);
        }
    }

    /**
     * Inject a settings group into config with mapping and prefix handling.
     */
    protected function injectGroup(ConfigurationRegistryInterface $registry, string $group, string $configPrefix, array $mapping = [], string $keyPrefixToRemove = ''): void
    {
        $settings = $registry->getGroup($group);

        foreach ($settings as $key => $value) {
            // Guard: Never inject masked secrets into runtime config
            if (!$this->isValidSecret($value)) {
                continue;
            }

            $configKey = $mapping[$key] ?? ($keyPrefixToRemove ? str_replace($keyPrefixToRemove, '', $key) : $key);
            Config::set($configPrefix . $configKey, $value);
        }
    }

    /**
     * Verify if a value is a valid secret (not masked/placeholder).
     */
    protected function isValidSecret(mixed $value): bool
    {
        if (is_string($value) && (str_contains($value, '••••') || $value === 'masked')) {
            return false;
        }

        return true;
    }
}
