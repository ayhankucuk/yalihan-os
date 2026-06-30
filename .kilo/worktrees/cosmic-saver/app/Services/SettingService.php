<?php

namespace App\Services;

use App\Contracts\Settings\ConfigurationRegistryInterface;
use App\Contracts\Settings\SettingsAuthorityInterface;

/**
 * @deprecated SAB S1 — Use ConfigurationRegistryInterface (reads) and SettingsAuthorityInterface (writes).
 * This class is kept as a non-breaking delegate for existing consumers.
 * New code MUST NOT depend on this class.
 *
 * Consumers to migrate:
 * - App\Services\AIService (constructor)
 * - App\Services\AI\GeminiService (constructor)
 * - App\Services\AI\OpenAIService (constructor)
 * - App\Providers\AppServiceProvider (AIService singleton factory)
 */
class SettingService
{
    /**
     * @deprecated Use ConfigurationRegistryInterface::get()
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return app(ConfigurationRegistryInterface::class)->get($key, $default);
    }

    /**
     * @deprecated Use SettingsAuthorityInterface::set()
     */
    public function set(string $key, mixed $value): void
    {
        app(SettingsAuthorityInterface::class)->set($key, $value);
    }
}
