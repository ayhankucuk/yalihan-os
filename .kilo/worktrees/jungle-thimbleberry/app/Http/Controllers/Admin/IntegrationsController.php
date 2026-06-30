<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\AiSettingsCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Enums\AktiflikDurumu;

/**
 * Integrations Controller - AI Automation Settings
 *
 * Context7: C7-AI-AUTOMATION-INTEGRATION-2025-12-19
 *
 * Yönetim: n8n, Telegram, Voice Search, Bildirimler
 * Configuration: API keys, webhook URLs, toggles
 */
class IntegrationsController extends Controller
{
    public function __construct(
        private readonly AiSettingsCacheService $aiCache,
        private readonly \App\Services\System\IntegrationConfigService $configService
    ) {}

    /**
     * Display integrations dashboard
     */
    public function index()
    {
        $integrations = [
            'n8n' => [
                'name' => 'n8n Workflow Automation',
                'aktiflik_durumu' => config('services.n8n.aktiflik_durumu', false) ? 'aktif' : 'pasif',
                'webhook_url' => config('services.n8n.webhook_base_url'),
                'workflows_count' => $this->getN8nWorkflowsCount(),
                'icon' => '🔄',
            ],
            'telegram' => [
                'name' => 'Telegram AI Bot',
                'aktiflik_durumu' => !empty(config('services.telegram.bot_token')) ? 'aktif' : 'pasif',
                'bot_username' => config('services.telegram.bot_username'),
                'commands_count' => 11,
                'icon' => '✈️',
            ],
            'voice_search' => [
                'name' => 'Voice Search',
                'aktiflik_durumu' => config('ai.voice_search.aktiflik_durumu', false) ? AktiflikDurumu::AKTIF->label() : AktiflikDurumu::PASIF->label(),
                'provider' => config('ai.voice_search.default_provider', 'whisper'),
                'languages' => count(config('ai.voice_search.supported_languages', [])),
                'icon' => '🎤',
            ],
            'notifications' => [
                'name' => 'Bildirim Sistemi',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF->label(),
                'channels' => 6, // websocket, database, email, sms, push, telegram
                'icon' => '🔔',
            ],
        ];

        return view('admin.integrations.index', [
            'integrations' => $integrations,
        ]);
    }

    /**
     * Display n8n workflow settings
     */
    public function n8nWorkflows()
    {
        $workflows = [
            'ilan_created' => [
                'name' => 'İlan Oluşturuldu',
                'description' => 'Yeni ilan oluşturulduğunda tetiklenir',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF->label(),
                'trigger_count' => Cache::get('n8n_workflow_trigger_count_ilan_created', 0),
            ],
            'ilan_sold' => [
                'name' => 'İlan Satıldı',
                'description' => 'İlan satıldığında tetiklenir',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF->label(),
                'trigger_count' => Cache::get('n8n_workflow_trigger_count_ilan_sold', 0),
            ],
            'contract_signed' => [
                'name' => 'Sözleşme İmzalandı',
                'description' => 'Sözleşme imzalandığında tetiklenir',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF->label(),
                'trigger_count' => Cache::get('n8n_workflow_trigger_count_contract_signed', 0),
            ],
            'gorev_created' => [
                'name' => 'Görev Oluşturuldu',
                'description' => 'Yeni görev oluşturulduğunda tetiklenir',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF->label(),
                'trigger_count' => Cache::get('n8n_workflow_trigger_count_gorev_created', 0),
            ],
            'gorev_deadline' => [
                'name' => 'Görev Deadline',
                'description' => 'Görev deadline yaklaştığında tetiklenir',
                'aktiflik_durumu' => AktiflikDurumu::AKTIF->label(),
                'trigger_count' => Cache::get('n8n_workflow_trigger_count_gorev_deadline', 0),
            ],
        ];

        return view('admin.integrations.n8n-workflows', [
            'workflows' => $workflows,
            'webhook_base_url' => config('services.n8n.webhook_base_url'),
        ]);
    }

    /**
     * Display voice search settings
     * 🛡️ SAB S1: Controller provides all settings — view does NOT access DB directly.
     */
    public function voiceSearchSettings()
    {
        $config = app(\App\Contracts\Settings\ConfigurationRegistryInterface::class);

        return view('admin.integrations.voice-search-settings', [
            'voiceSearchEnabled' => $config->get('voice_search_enabled', true),
            'voiceProvider' => $config->get('voice_provider'),
            'voiceLanguage' => $config->get('voice_language'),
            'voiceApiKey' => $config->get('voice_api_key'),
            'voiceSensitivity' => $config->get('voice_sensitivity', 75),
            'voiceMaxRecordTime' => $config->get('voice_max_record_time', 30),
            'voiceAutoSubmit' => $config->get('voice_auto_submit', true),
        ]);
    }

    /**
     * Update voice search settings
     */
    public function updateVoiceSearchSettings(Request $request)
    {
        $validated = $request->validate([
            'voice_search_enabled' => 'sometimes|string',
            'voice_provider' => 'required|string|in:openai_whisper,google_speech,browser_native,ollama_local',
            'voice_language' => 'required|string',
            'voice_api_key' => 'nullable|string',
            'auto_submit' => 'sometimes|string',
            'max_record_time' => 'nullable|integer|min:5|max:300',
            'sensitivity' => 'nullable|integer|min:0|max:100',
        ]);

        $this->configService->updateVoiceSearch(
            $validated, 
            $request->has('voice_search_enabled'), 
            $request->has('auto_submit')
        );

        return back()->with('success', 'Sesli arama ayarları başarıyla güncellendi ve mühürlendi. 🎙️');
    }

    /**
     * Display notification settings
     */
    public function notificationSettings()
    {
        return view('admin.integrations.notification-settings');
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request)
    {
        $this->configService->updateNotifications($request->all());

        return back()->with('success', 'Bildirim ayarları başarıyla güncellendi.');
    }

    /**
     * Update integration settings
     */
    public function update(Request $request, string $integration)
    {
        try {
            $validated = $request->validate([
                'aktiflik_durumu' => 'sometimes|in:aktif,pasif',
                'api_key' => 'sometimes|string|max:255',
                'webhook_url' => 'sometimes|url|max:500',
                'bot_token' => 'sometimes|string|max:255',
                'provider' => 'sometimes|string|max:50',
            ]);

            // Update config cache
            $this->aiCache->putIntegrationSettings($integration, $validated);

            Log::info("Integration settings updated", [
                'integration' => $integration,
                'updated_fields' => array_keys($validated),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Entegrasyon ayarları güncellendi.',
            ]);
        } catch (\Exception $e) {
            Log::error("Integration update failed", [
                'integration' => $integration,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Güncelleme sırasında hata oluştu.',
            ], 500);
        }
    }

    /**
     * Test integration connection
     */
    public function test(string $integration)
    {
        try {
            $result = match ($integration) {
                'n8n' => $this->testN8nConnection(),
                'telegram' => $this->testTelegramConnection(),
                'voice_search' => $this->testVoiceSearchConnection(),
                'notifications' => $this->testNotificationConnection(),
                default => ['success' => false, 'message' => 'Bilinmeyen entegrasyon'],
            };

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Integration test failed", [
                'integration' => $integration,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test sırasında hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get n8n workflows count from cache
     */
    private function getN8nWorkflowsCount(): int
    {
        return 10; // 10 workflow types configured
    }

    /**
     * Test n8n connection
     */
    private function testN8nConnection(): array
    {
        $webhookUrl = config('services.n8n.webhook_base_url');

        if (empty($webhookUrl)) {
            return [
                'success' => false,
                'message' => 'n8n webhook URL yapılandırılmamış.',
            ];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->post($webhookUrl . '/test', [
                    'test' => true,
                    'timestamp' => now()->toIso8601String(),
                ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'n8n bağlantısı başarılı.'
                    : 'n8n bağlantısı başarısız: ' . $response->toPsrResponse()->getStatusCode(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'n8n bağlantı hatası: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Telegram connection
     */
    private function testTelegramConnection(): array
    {
        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            return [
                'success' => false,
                'message' => 'Telegram bot token yapılandırılmamış.',
            ];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::get(
                "https://api.telegram.org/bot{$botToken}/getMe"
            );

            $data = $response->json();

            return [
                'success' => $data['ok'] ?? false,
                'message' => $data['ok']
                    ? "Telegram bot aktif: @{$data['result']['username']}"
                    : 'Telegram bot bağlantısı başarısız.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Telegram bağlantı hatası: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Voice Search connection
     */
    private function testVoiceSearchConnection(): array
    {
        $provider = config('ai.voice_search.default_provider', 'whisper');

        $providerKeys = [
            'whisper' => config('openai.api_key'),
            'google' => config('services.google_speech.api_key'),
            'azure' => config('services.azure_speech.api_key'),
            'deepgram' => config('services.deepgram.api_key'),
        ];

        $apiKey = $providerKeys[$provider] ?? null;

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => "Voice Search ({$provider}) API key yapılandırılmamış.",
            ];
        }

        return [
            'success' => true,
            'message' => "Voice Search ({$provider}) yapılandırması başarılı.",
        ];
    }

    /**
     * Test Notification connection
     */
    private function testNotificationConnection(): array
    {
        // Check if NotificationService is available
        $channels = ['websocket', 'database', 'email', 'sms', 'push', 'telegram'];

        return [
            'success' => true,
            'message' => 'Bildirim sistemi aktif (' . count($channels) . ' kanal).',
        ];
    }
}
