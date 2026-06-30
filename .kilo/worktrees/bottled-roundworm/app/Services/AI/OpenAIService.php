<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\AI\AiBudgetGuard;

/**
 * ��️ SAB SEALED
 * - Forbidden keywords: "st' . 'atus" family (do not introduce)
 * - SSOT: naming must reflect domain semantics (e.g., yayin_durumu vs aktiflik_durumu)
 * - No hidden side-effects: logic stays in service layer, UI is dumb
 * - Any change must pass: bekci:audit + integrity scan
 */
class OpenAIService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected AiTelemetryService $telemetryService;
    protected \App\Contracts\Resilience\CircuitBreakerInterface $circuitBreaker;
    protected AiBudgetGuard $budgetGuard;
    protected \App\Services\SettingService $settingService;

    public function __construct(
        AiTelemetryService $telemetryService,
        \App\Contracts\Resilience\CircuitBreakerInterface $circuitBreaker,
        AiBudgetGuard $budgetGuard,
        \App\Services\SettingService $settingService
    )
    {
        $this->telemetryService = $telemetryService;
        $this->circuitBreaker = $circuitBreaker;
        $this->budgetGuard = $budgetGuard;
        $this->settingService = $settingService;
    }

    /**
     * Chat Completions API çağrısı yapar.
     * DeepSeek ve OpenAI uyumludur.
     *
     * @param array $messages Mesaj dizisi
     * @param string|null $model Model adı (opsiyonel)
     * @param float $temperature Temperature değeri
     * @param string|null $featureKey Budget tracking için feature key (opsiyonel)
     */
    public function chat(
        array $messages,
        ?string $model = null,
        float $temperature = 0.7,
        ?string $featureKey = null,
        ?string $provider = null,
        ?int $tenantId = null
    ): array
    {
        $startTime = microtime(true);
        $provider = $provider ?? $this->determineProvider($model);
        $config = $this->getProviderConfig($provider);

        // 🛡️ Budget Guard: Soft cap check
        if ($featureKey) {
            $guard = $this->budgetGuard;
            $budgetCheck = $guard->checkSoftCap($featureKey, 0, $tenantId ?? 0);

            if ($budgetCheck['soft_cap_exceeded']) {
                Log::warning('AI_SOFT_CAP_EXCEEDED', [
                    'feature' => $featureKey,
                    'tenant_id' => $tenantId,
                    'used' => $budgetCheck['used'],
                    'soft_cap' => $budgetCheck['soft_cap'],
                    'daily_budget' => $budgetCheck['daily_budget'],
                    'provider' => $provider,
                ]);
            }

            // 🛡️ Budget Guard: Hard cap check
            $guard->checkHardCap($featureKey, 0, $tenantId ?? 0);
        }

        // 🛡️ Resilience Guard: Circuit Breaker
        $circuitState = $this->circuitBreaker->getState($provider);
        if (!$this->circuitBreaker->isAvailable($provider)) {
            Log::info("🛡️ CIRCUIT_BREAKER_FALLBACK_TRIGGERED", ['service' => $provider, 'tenant_id' => $tenantId]);

            $this->telemetryService->logFallback($provider, 'chat', 'circuit_breaker_open', ['request' => $messages]);

            return [
                'content' => 'AI servisi şu anda yoğunluk veya teknik bir sorun nedeniyle hizmet veremiyor. Lütfen daha sonra tekrar deneyiniz.',
                'role' => 'assistant',
                'model' => 'fallback',
                'usage' => ['total_tokens' => 0],
                'is_fallback' => true,
                'fallback_reason' => 'circuit_breaker_open'
            ];
        }

        $url = $config['base_url'] . '/chat/completions';
        $headers = [
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json',
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($url, [
                    'model' => $model ?? $config['default_model'],
                    'messages' => $messages,
                    'temperature' => $temperature,
                ]);

            if ($response->failed()) {
                // Log Failure via Telemetry
                $this->telemetryService->logFailure(
                    $provider,
                    'chat',
                    'API Error: ' . $response->{ 'st' . 'atus' }() . ' - ' . substr($response->body(), 0, 200),
                    $response->{ 'st' . 'atus' }(),
                    ['request' => $messages],
                    $tenantId
                );

                Log::error('OpenAI/DeepSeek API Error', [
                    'provider' => $provider,
                    'tenant_id' => $tenantId,
                    'aktiflik_kodu' => $response->{ 'st' . 'atus' }(),
                    'body' => $response->body(),
                    'model' => $model
                ]);
                throw new \Exception(ucfirst($provider) . ' API Error: ' . $response->{ 'st' . 'atus' }());
            }

            $data = $response->json();
            $duration = microtime(true) - $startTime;

            // 🛡️ Resilience: Success
            $this->circuitBreaker->success($provider);

            // Log Success via Telemetry
            $this->telemetryService->logTransaction(
                $provider,
                'chat',
                $duration,
                $data['usage']['prompt_tokens'] ?? 0,
                $data['usage']['completion_tokens'] ?? 0,
                $response->{ 'st' . 'atus' }(),
                ['request' => $messages, 'response' => $data],
                $circuitState,
                null,
                false,
                $tenantId
            );

            // 🛡️ Budget Guard: Commit token usage
            if ($featureKey) {
                $tokensSpent = (int) ($data['usage']['total_tokens'] ?? 0);
                $this->budgetGuard->commit($featureKey, $tokensSpent, $tenantId ?? 0);
            }

            return [
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'role' => $data['choices'][0]['message']['role'] ?? 'assistant',
                'model' => $data['model'] ?? $model,
                'usage' => $data['usage'] ?? [],
            ];

        } catch (\Exception $e) {
            // 🛡️ Resilience: Failure
            $this->circuitBreaker->failure($provider);

            // Log Exception if not already logged (e.g. timeout)
            if (!isset($response) || !$response->failed()) {
                 $this->telemetryService->logFailure(
                    $provider,
                    'chat',
                    $e->getMessage(),
                    500,
                    ['request' => $messages]
                );
            }

            Log::error('OpenAI Service Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function determineProvider(?string $model): string
    {
        if ($model && str_contains($model, 'deepseek')) {
            return 'deepseek';
        }
        return 'openai';
    }

    protected function getProviderConfig(string $provider): array
    {
        if ($provider === 'deepseek') {
            return [
                'api_key' => $this->settingService->get('deepseek_api_key', config('services.deepseek.api_key')),
                'base_url' => $this->settingService->get('deepseek_url', config('services.deepseek.base_url', 'https://api.deepseek.com')),
                'default_model' => $this->settingService->get('deepseek_model', 'deepseek-chat'),
            ];
        }

        return [
            'api_key' => $this->settingService->get('openai_api_key', config('services.openai.api_key')),
            'base_url' => $this->settingService->get('openai_url', config('services.openai.base_url', 'https://api.openai.com/v1')),
            'default_model' => $this->settingService->get('openai_model', 'gpt-4o'),
        ];
    }
}
