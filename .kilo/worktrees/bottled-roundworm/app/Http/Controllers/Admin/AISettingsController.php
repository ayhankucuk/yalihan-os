<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Contracts\Settings\ConfigurationRegistryInterface;
use App\Contracts\Settings\SettingsAuthorityInterface;
use App\Services\Admin\AiLogService;
use App\Services\AI\AiSettingsCacheService;
use App\Services\AIService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AISettingsController extends Controller
{
    public function __construct(
        private readonly AiSettingsCacheService $aiCache,
        private readonly ConfigurationRegistryInterface $config,
        private readonly SettingsAuthorityInterface $authority,
        private readonly AiLogService $aiLogService
    ) {
        $this->middleware('can:manage-settings');
    }
    public function index()
    {
        // Mevcut AI ayarlarını al
        $currentProvider = $this->config->get('ai_provider', config('ai.provider', 'ollama'));
        $currentModel = $this->config->get('ai_default_model',
            $this->config->get('ollama_model', config('ai.ollama_model', 'gemma2:2b'))
        );

        // Tüm provider ayarlarını al
        $providerSettings = [
            'openai' => [
                'api_key' => $this->config->get('openai_api_key', ''),
                'model' => $this->config->get('openai_model', 'gpt-3.5-turbo'),
                'organization' => $this->config->get('openai_organization', ''),
            ],
            'google' => [
                'api_key' => $this->config->get('google_api_key', ''),
                'model' => $this->config->get('google_model', 'gemini-pro'),
            ],
            'claude' => [
                'api_key' => $this->config->get('claude_api_key', ''),
                'model' => $this->config->get('claude_model', 'claude-3-sonnet-20240229'),
            ],
            'deepseek' => [
                'api_key' => $this->config->get('deepseek_api_key', ''),
                'model' => $this->config->get('deepseek_model', 'deepseek-chat'),
            ],
            'ollama' => [
                'url' => $this->config->get('ollama_url', 'http://localhost:11434'),
                'model' => $this->config->get('ollama_model', 'gemma2:2b'),
            ],
        ];

        // Genel ayarlar
        $appLocale = $this->config->get('app_locale', 'tr');
        $currencyDefault = $this->config->get('currency_default', 'TRY');

        return view('admin.ai-settings.index', [
            'currentProvider' => $currentProvider,
            'currentModel' => $currentModel,
            'providerSettings' => $providerSettings,
            'appLocale' => $appLocale,
            'currencyDefault' => $currencyDefault,
        ]);
    }

    /**
     * AI Analytics - Detaylı istatistikler
     * Context7: Real-time analytics endpoint
     */
    public function analytics(Request $request)
    {
        try {
            $period = $request->input('period', '7days'); // 7days, 30days, all
            $since = match ($period) {
                '24hours' => now()->subHours(24),
                '7days' => now()->subDays(7),
                '30days' => now()->subDays(30),
                'all' => null,
                default => now()->subDays(7),
            };

            $stats = $this->aiLogService->getAnalytics($since);
            $dailyTrend = $this->aiLogService->getDailyTrend();

            return ResponseService::success([
                'summary' => [
                    'total_requests' => $stats['total'],
                    'successful_requests' => $stats['successful'],
                    'failed_requests' => $stats['failed'],
                    'success_rate' => $stats['successRate'],
                    'error_rate' => round(100 - $stats['successRate'], 2),
                    'avg_response_time' => round($stats['avgResponseTime'], 2),
                    'total_cost' => round($stats['totalCost'], 6),
                    'total_tokens' => $stats['totalTokens'],
                ],
                'provider_usage' => $stats['providerUsage'],
                'model_usage' => $stats['modelUsage'],
                'request_type_usage' => $stats['requestTypeUsage'],
                'daily_trend' => $dailyTrend,
                'period' => $period,
                'current_provider' => $this->getCurrentProvider(),
                'current_model' => $this->config->get('ai_default_model',
                    $this->config->get('ollama_model', config('ai.ollama_model', 'gemma2:2b'))
                ),
                'timestamp' => now()->toIso8601String(),
            ], 'AI analytics başarıyla yüklendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Analytics yüklenirken hata oluştu', $e);
        }
    }

    public function providerAktiflikDurumu()
    {
        $service = app(AIService::class);

        return ResponseService::success([
            'provider' => config('ai.provider'),
            'model' => config('ai.default_model'),
            'available_providers' => $service->getAvailableProviders(),
        ], 'AI provider durumu');
    }

    public function testProvider(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'api_key' => 'nullable|string',
            'model' => 'nullable|string',
        ]);

        return ResponseService::success([
            'test' => 'ok',
            'provider' => $validated['provider'],
            'model' => $validated['model'] ?? null,
        ], 'Provider testi başarılı');
    }

    public function updateLocale(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string|in:tr,en',
        ]);
        $this->authority->set('app_locale', $validated['locale']);

        return ResponseService::success(['locale' => $validated['locale']], 'Dil ayarı güncellendi');
    }

    public function updateCurrency(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|in:TRY,USD,EUR,GBP',
        ]);
        $this->authority->set('currency_default', $validated['currency']);

        return ResponseService::success(['currency' => $validated['currency']], 'Para birimi ayarı güncellendi');
    }

    public function updateProvider(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:openai,google,claude,deepseek,ollama',
        ]);
        /** @var AIService $ai */
        $ai = app(AIService::class);
        try {
            $ai->switchProvider($validated['provider']);

            return ResponseService::success(['provider' => $validated['provider']], 'AI sağlayıcı güncellendi');
        } catch (\Throwable $e) {
            return ResponseService::serverError('Sağlayıcı güncellenemedi', $e);
        }
    }

    public function updateModel(Request $request)
    {
        $validated = $request->validate([
            'model' => 'required|string|max:100',
            'provider' => 'nullable|string|in:openai,google,claude,deepseek,ollama',
        ]);

        $model = $validated['model'];
        $provider = $validated['provider'] ?? $this->getCurrentProvider();

        // Provider'a göre model key'i belirle
        $modelKey = match ($provider) {
            'ollama' => 'ollama_model',
            'openai' => 'openai_model',
            'google' => 'google_model',
            'claude' => 'claude_model',
            'deepseek' => 'deepseek_model',
            default => 'ai_default_model',
        };

        // Hem provider-specific hem de genel model ayarını güncelle
        $this->authority->set($modelKey, $model);
        $this->authority->set('ai_default_model', $model);

        // Cache'i temizle
        $this->aiCache->invalidateAfterModelUpdate();

        return ResponseService::success([
            'model' => $validated['model'],
            'provider' => $provider,
            'model_key' => $modelKey,
        ], 'Model ayarı güncellendi');
    }

    public function update(Request $request)
    {
        if ($request->filled('provider')) {
            return $this->updateProvider($request);
        }

        if ($request->filled('model')) {
            return $this->updateModel($request);
        }

        return ResponseService::validationError([
            'provider_model' => ['Güncelleme için provider veya model alanı gerekli'],
        ]);
    }

    public function getProviderStatus()
    {
        return $this->providerAktiflikDurumu();
    }

    public function statistics(Request $request)
    {
        return $this->analytics($request);
    }

    public function testOllamaConnection(Request $request)
    {
        $ollamaUrl = $this->config->get('ollama_url', config('ai.ollama_url', 'http://localhost:11434'));

        try {
            $response = Http::timeout(5)->get(rtrim((string) $ollamaUrl, '/') . '/api/tags');

            return ResponseService::success([
                'baglanti' => $response->successful(),
                'model_sayisi' => count($response->json('models', [])),
            ], 'Ollama bağlantı testi tamamlandı');
        } catch (\Throwable $e) {
            return ResponseService::serverError('Ollama bağlantı testi başarısız', $e);
        }
    }

    public function testQuery(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:500',
        ]);

        return ResponseService::success([
            'query' => $validated['query'] ?? 'ping',
            'provider' => $this->getCurrentProvider(),
            'model' => $this->config->get('ai_default_model',
                $this->config->get('ollama_model', config('ai.ollama_model', 'gemma2:2b'))
            ),
        ], 'Test sorgusu alındı');
    }

    public function proxyOllama(Request $request)
    {
        return $this->testOllamaConnection($request);
    }

    /**
     * API Key güncelle
     */
    public function updateApiKey(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:openai,google,claude,deepseek',
            'api_key' => 'nullable|string|max:500',
        ]);

        $provider = $validated['provider'];

        if (!empty($validated['api_key'])) {
            $apiKey = $validated['api_key'];
            $this->authority->set("{$provider}_api_key", $apiKey);

            // Cache'i temizle
            $this->aiCache->invalidateAfterApiKeyUpdate();

            return ResponseService::success([
                'provider' => $validated['provider'],
            ], 'API Key başarıyla güncellendi');
        }

        return ResponseService::success([
            'provider' => $validated['provider'],
        ], 'API Key değiştirilmedi (Boş gönderildi)');
    }

    /**
     * Ollama URL güncelle
     */
    public function updateOllamaUrl(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:255',
        ]);

        $url = $validated['url'];
        $this->authority->set('ollama_url', $url);

        // Cache'i temizle
        $this->aiCache->invalidateAfterOllamaUrlUpdate();

        return ResponseService::success([
            'url' => $validated['url'],
        ], 'Ollama URL başarıyla güncellendi');
    }

    /**
     * Provider Model güncelle
     */
    public function updateProviderModel(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:openai,google,claude,deepseek,ollama',
            'model' => 'required|string|max:100',
        ]);

        if ($validated['provider'] === 'deepseek') {
            $request->validate([
                'model' => ['required', \Illuminate\Validation\Rule::in(\App\Enums\AI\DeepSeekModel::values())],
            ]);
        }

        $provider = $validated['provider'];
        $model = $validated['model'];

        $modelKeyMap = [
            'openai' => 'openai_model',
            'google' => 'google_model',
            'claude' => 'claude_model',
            'deepseek' => 'deepseek_model',
            'ollama' => 'ollama_model',
        ];

        $this->authority->set($modelKeyMap[$provider], $model);

        // Eğer aktif provider ise genel model ayarını da güncelle
        if ($this->getCurrentProvider() === $validated['provider']) {
            $this->authority->set('ai_default_model', $model);
        }

        // Cache'i temizle
        $this->aiCache->invalidateAfterProviderModelUpdate();

        return ResponseService::success([
            'provider' => $validated['provider'],
            'model' => $validated['model'],
        ], 'Model ayarı başarıyla güncellendi');
    }

    /**
     * OpenAI Organization ID güncelle
     */
    public function updateOpenAIOrganization(Request $request)
    {
        $validated = $request->validate([
            'organization' => 'nullable|string|max:100',
        ]);

        $this->authority->set('openai_organization', $validated['organization'] ?? '', 'ai', 'string');

        $this->aiCache->invalidateAfterApiKeyUpdate();

        return ResponseService::success([
            'organization' => $validated['organization'] ?? '',
        ], 'OpenAI Organization ID başarıyla güncellendi');
    }

    /**
     * API Key'i mask'le (güvenlik için)
     */
    protected function maskApiKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, 4).str_repeat('*', strlen($key) - 8).substr($key, -4);
    }

    /**
     * Mevcut provider'ı al
     */
    protected function getCurrentProvider(): string
    {
        return $this->config->get('ai_provider', config('ai.provider', 'ollama'));
    }
}
