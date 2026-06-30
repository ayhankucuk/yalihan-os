<?php

namespace App\Application\AI\Support;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Domain\AI\Contracts\AIProviderRouterInterface;
use App\Infrastructure\AI\Routing\ProviderRegistry;
use Throwable;

/**
 * 🛡️ RoutedCortexExecutor
 * Orchestrates AI task execution by trying ranked providers from the router.
 * Implements automatic fallback logic.
 */
final class RoutedCortexExecutor
{
    public function __construct(
        private readonly AIProviderRouterInterface $router,
        private readonly ProviderRegistry $registry,
    ) {}

    public function execute(CortexRequestData $request): CortexResponseData
    {
        // 1. Get the decision (ranked list of providers)
        $decision = $this->router->decide($request);

        $lastException = null;

        $attemptedProviders = [];
        
        // 2. Iterate through ranked providers until one succeeds
        $rankedProviders = is_array($decision->rankedProviders) ? $decision->rankedProviders : $decision->rankedProviders->toArray();
        foreach ($rankedProviders as $index => $providerScore) {
            try {
                $provider = $providerScore->provider;
                $attemptedProviders[] = $provider->value;
                
                $adapter = $this->registry->get($provider);

                // Prepare request (e.g., force deepseek-reasoner if needed)
                $modifiedRequest = $this->prepareRequest($request, $provider);

                $response = $adapter->execute($modifiedRequest);

                if ($response->success) {
                    // Enrich response with routing metadata
                    return new CortexResponseData(
                        success: true,
                        output: $response->output,
                        provider: $provider->value,
                        model: $response->model,
                        traceId: $request->traceId,
                        meta: array_merge($response->meta, [
                            'selected_provider' => $provider->value,
                            'fallback_used' => $index > 0,
                            'routing_reason' => $decision->reason,
                            'score_breakdown' => $providerScore->toArray(),
                            'attempted_providers' => $attemptedProviders,
                        ])
                    );
                }

                $lastException = new \Exception($response->errorMessage ?? "Provider [{$provider->value}] returned failure.");
            } catch (Throwable $e) {
                $lastException = $e;
                continue;
            }
        }

        return new CortexResponseData(
            success: false,
            traceId: $request->traceId,
            errorCode: 'ROUTED_EXECUTION_FAILURE',
            errorMessage: "All providers failed during routed execution. Last error: " . $lastException?->getMessage()
        );
    }

    private function prepareRequest(CortexRequestData $request, \App\Domain\AI\Enums\AIProvider $provider): CortexRequestData
    {
        // Force R1 for analysis if using DeepSeek
        if ($provider === \App\Domain\AI\Enums\AIProvider::DEEPSEEK 
            && $request->taskType === \App\Domain\AI\Enums\AITaskType::ANALYZE_PROPERTY) {
            return new CortexRequestData(
                taskType: $request->taskType,
                input: $request->input,
                context: $request->context,
                model: 'deepseek-reasoner',
                traceId: $request->traceId
            );
        }

        return $request;
    }
}
