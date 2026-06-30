<?php

namespace App\Services\System;

use App\Models\Setting;
use App\Services\AI\AiSettingsCacheService;
use Illuminate\Support\Facades\DB;

class IntegrationConfigService
{
    public function __construct(
        private readonly AiSettingsCacheService $aiCache
    ) {}

    /**
     * Update voice search settings with atomic transaction wrapper.
     */
    public function updateVoiceSearch(array $validated, bool $isEnabled, bool $autoSubmit): void
    {
        DB::transaction(function () use ($validated, $isEnabled, $autoSubmit) {
            Setting::set('voice_search_enabled', $isEnabled, 'voice', 'boolean');
            Setting::set('voice_provider', $validated['voice_provider'], 'voice');
            Setting::set('voice_language', $validated['voice_language'], 'voice');

            if (!empty($validated['voice_api_key']) && $validated['voice_api_key'] !== '••••••••••••••••') {
                Setting::set('voice_api_key', $validated['voice_api_key'], 'voice');

                if ($validated['voice_provider'] === 'openai_whisper') {
                    Setting::set('openai_api_key', $validated['voice_api_key'], 'ai');
                }
            }

            Setting::set('voice_auto_submit', $autoSubmit, 'voice', 'boolean');
            Setting::set('voice_max_record_time', $validated['max_record_time'] ?? 30, 'voice', 'integer');
            Setting::set('voice_sensitivity', $validated['sensitivity'] ?? 75, 'voice', 'integer');
        });

        $this->aiCache->invalidateProviderAndVoice();
    }

    /**
     * Update notification settings.
     */
    public function updateNotifications(array $data): void
    {
        // Reserved for future DB transactions
        DB::transaction(function () use ($data) {
            // Context7: Ayarları kaydetme simülasyonu
        });
    }
}
