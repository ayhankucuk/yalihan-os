<?php

namespace App\Services\AI\Providers;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\DTOs\CortexUsage;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\CortexCapability;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ SAB SEALED
 * OpenAI Cortex Provider (Bridge).
 * Ensures OpenAI fallback follows SAAB DTO contracts.
 */
class OpenAICortexProvider implements CortexServiceInterface
{
    public function __construct(
        protected OpenAIService $openAIService
    ) {}

    public function execute(CortexRequestData $request): CortexResponseData
    {
        $provider = $this->providerName();
        
        try {
            // Using existing OpenAIService::chat which already has budget and telemetry
            $response = $this->openAIService->chat(
                $request->getMessages(),
                $request->getModel() ?? config('services.openai.model', 'gpt-4o'),
                $request->getTemperature() ?? 0.7,
                $request->getFeatureKey(),
                $provider,
                $request->getTenantId()
            );

            // Mapping raw array response to CortexResponseData DTO
            $usage = new CortexUsage(
                promptTokens: $response['usage']['prompt_tokens'] ?? 0,
                completionTokens: $response['usage']['completion_tokens'] ?? 0,
                totalTokens: $response['usage']['total_tokens'] ?? 0
            );

            return new CortexResponseData(
                success: true,
                output: $response['choices'][0]['message'] ?? [],
                rawText: $response['choices'][0]['message']['content'] ?? '',
                usage: $usage,
                provider: $provider,
                model: $response['model'] ?? 'unknown',
                meta: ['is_fallback' => true]
            );

        } catch (\Exception $e) {
            Log::error("OpenAI Fallback Error: " . $e->getMessage());
            
            return new CortexResponseData(
                success: false,
                errorMessage: $e->getMessage(),
                errorCode: 'FALLBACK_FAILED',
                usage: new CortexUsage(0, 0, 0),
                provider: $provider
            );
        }
    }

    public function supports(CortexCapability $capability): bool
    {
        return true; // OpenAI supports most things
    }

    public function providerName(): string
    {
        return 'openai';
    }
}
