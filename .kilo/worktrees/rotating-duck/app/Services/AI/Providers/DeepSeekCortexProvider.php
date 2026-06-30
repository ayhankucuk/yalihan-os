<?php

namespace App\Services\AI\Providers;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\DTOs\CortexUsage;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\CortexCapability;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Services\AI\AiBudgetGuard;
use App\Contracts\Resilience\CircuitBreakerInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 🛡️ SAB SEALED
 * DeepSeekCortexProvider (Cortex Level Provider).
 * Implements SAAB Resilience Layer.
 */
class DeepSeekCortexProvider implements CortexServiceInterface
{
    public function __construct(
        protected AiTelemetryService $telemetry,
        protected CircuitBreakerInterface $circuitBreaker,
        protected AiBudgetGuard $budgetGuard,
        protected ?\App\Services\AI\AiCostCalculatorService $costCalculator = null
    ) {
        $this->costCalculator = $costCalculator ?? app(\App\Services\AI\AiCostCalculatorService::class);
    }

    public function execute(CortexRequestData $request): CortexResponseData
    {
        $provider = $this->providerName();
        $startTime = microtime(true);
        $retryCount = 0;

        // 1. Rate Limit Check
        if (!$this->checkRateLimit($request)) {
            return $this->errorResponse('AI_RATE_LIMIT_EXCEEDED', 'Rate limit exceeded');
        }

        // 2. Circuit Breaker Check
        if (!$this->circuitBreaker->isAvailable($provider)) {
            return $this->errorResponse('CIRCUIT_OPEN', 'Provider circuit is open', true);
        }

        // 3. Budget Pre-approval
        $this->budgetGuard->checkHardCap($request->getFeatureKey(), 0, $request->getTenantId());

        // 4. Model Guard
        $expectedModel = config('services.deepseek.model', 'deepseek-chat');
        $actualModel = $request->getModel() ?? $expectedModel;

        if ($actualModel !== $expectedModel) {
            throw new \App\Exceptions\AI\AIModelMismatchException($expectedModel, $actualModel);
        }

        try {
            $response = Http::withToken(config('services.deepseek.api_key'))
                ->baseUrl(config('services.deepseek.base_url', 'https://api.deepseek.com'))
                ->timeout(config('services.deepseek.timeout', 30))
                ->retry(3, 100, function ($exception, $push) use (&$retryCount) {
                    $retryCount++;
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException || 
                           ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->failed());
                })
                ->post('/chat/completions', [
                    'model' => $actualModel,
                    'messages' => $request->getMessages(),
                    'max_tokens' => (int) config('services.deepseek.max_tokens', 2048),
                    'temperature' => (float) ($request->getTemperature() ?? 0.7),
                ]);

            if ($response->failed()) {
                $this->circuitBreaker->failure($provider);
                $this->telemetry->logFailure(
                    $provider, 
                    $request->getCapability()->value, 
                    // context7-ignore: HTTP status is required here
                    $response->status(), 
                    [], 
                    $request->getTenantId()
                );
                return $this->errorResponse('AI_PROVIDER_ERROR', 'HTTP Error: ' . $response->reason());
            }

            $this->circuitBreaker->success($provider);
            $data = $response->json();

            // 4. Model Guard (CRITICAL)
            $expectedModel = $request->getModel() ?? config('services.deepseek.model');
            $actualModel = $data['model'] ?? null;
            if ($actualModel && $expectedModel && strpos($actualModel, $expectedModel) === false) {
                $this->telemetry->logFailure($provider, 'execute', 'Model Mismatch', 422, ['expected' => $expectedModel, 'actual' => $actualModel], $request->getTenantId());
                return $this->errorResponse('AI_MODEL_MISMATCH', "Expected {$expectedModel}, got {$actualModel}");
            }

            $duration = (int)((microtime(true) - $startTime) * 1000);
            $usage = $this->parseUsage($data);
            $cost = $this->costCalculator->calculateCost($provider, $actualModel, $usage->promptTokens, $usage->completionTokens);

            // 5. Budget Commit
            $this->budgetGuard->commit($request->getFeatureKey(), $usage->totalTokens, $request->getTenantId(), $cost);

            // 6. Telemetry
            $this->telemetry->logTransaction(
                $provider,
                $request->getCapability()->value,
                $duration / 1000,
                $usage->promptTokens,
                $usage->completionTokens,
                200,
                [
                    'retry_count' => $retryCount,
                    'model' => $actualModel,
                    'fallback_used' => false
                ],
                'closed',
                null,
                false,
                $request->getTenantId()
            );

            return new CortexResponseData(
                success: true,
                output: $data['choices'][0]['message'] ?? [],
                rawText: $data['choices'][0]['message']['content'] ?? '',
                usage: $usage,
                provider: $provider,
                model: $actualModel,
                meta: ['latency_ms' => $duration, 'retry_count' => $retryCount]
            );

        } catch (\Exception $e) {
            $this->circuitBreaker->failure($provider);
            Log::error("DeepSeek Provider Error: " . $e->getMessage());
            return $this->errorResponse('AI_EXCEPTION', $e->getMessage());
        }
    }

    public function supports(CortexCapability $capability): bool
    {
        return in_array($capability, [CortexCapability::TEXT_GENERATION, CortexCapability::STRUCTURED_EXTRACTION]);
    }

    public function providerName(): string
    {
        return 'deepseek';
    }

    protected function checkRateLimit(CortexRequestData $request): bool
    {
        $key = 'ai_limit_deepseek_' . ($request->getUserId() ?? 'global');
        return RateLimiter::attempt($key, 60, function() {}, 60);
    }

    protected function parseUsage(array $data): CortexUsage
    {
        $u = $data['usage'] ?? [];
        return new CortexUsage(
            promptTokens: $u['prompt_tokens'] ?? 0,
            completionTokens: $u['completion_tokens'] ?? 0,
            totalTokens: $u['total_tokens'] ?? 0
        );
    }

    protected function errorResponse(string $code, string $message, bool $isCircuitOpen = false): CortexResponseData
    {
        return new CortexResponseData(
            success: false,
            errorMessage: $message,
            errorCode: $code,
            usage: new CortexUsage(0, 0, 0),
            provider: $this->providerName(),
            meta: ['is_circuit_open' => $isCircuitOpen]
        );
    }
}
