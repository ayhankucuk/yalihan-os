<?php

namespace App\Infrastructure\AI\Providers;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\DTOs\CortexUsage;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\AITaskType;
use App\Domain\AI\Enums\CortexCapability;
use App\Services\AI\OllamaService;
use Exception;

/**
 * ️ OllamaCortexAdapter
 * 
 * Concrete adapter for Ollama provider. Implements the new CortexServiceInterface
 * by wrapping the existing OllamaService.
 */
final class OllamaCortexAdapter implements CortexServiceInterface
{
    public function __construct(
        private readonly OllamaService $ollama
    ) {}

    public function execute(CortexRequestData $request): CortexResponseData
    {
        try {
            $promptClass = match ($request->taskType) {
                AITaskType::ANALYZE_PROPERTY => \App\Application\AI\Prompts\AnalyzePropertyPrompt::class,
                AITaskType::EXTRACT_PROPERTY_FEATURES => \App\Application\AI\Prompts\ExtractFeaturesPrompt::class,
                AITaskType::SUGGEST_PROPERTY_TEMPLATE => \App\Application\AI\Prompts\SuggestTemplatePrompt::class,
                AITaskType::GENERATE_PROPERTY_TEMPLATE => \App\Application\AI\Prompts\GeneratePropertyTemplatePrompt::class,
                default => throw new Exception("Task type [{$request->taskType->value}] not implemented in Ollama adapter."),
            };

            /** @var \App\Domain\AI\Contracts\PromptInterface $prompt */
            $prompt = new $promptClass($request->input);
            $options = $prompt->getOptions();

            // Check if it's a legacy task that doesn't need AI transport
            if ($options['is_legacy'] ?? false) {
                return new CortexResponseData(
                    success: false,
                    traceId: $request->traceId,
                    errorCode: 'LEGACY_TASK',
                    errorMessage: 'This task should be handled by the Legacy Bridge.'
                );
            }

            $fullPrompt = $prompt->getSystemInstructions() . "\n\n" . $prompt->getUserPrompt();
            
            $response = $this->ollama->generateCompletion(
                $fullPrompt,
                $options['max_tokens'] ?? 1000
            );

            if (isset($response['error']) && $response['error']) {
                throw new Exception($response['message'] ?? 'Ollama communication error.');
            }

            // Parse JSON response if expected (all our new property tasks expect JSON)
            $output = $this->parseJsonResponse($response['response'] ?? '');

            return new CortexResponseData(
                success: true,
                output: $output,
                provider: 'ollama',
                model: $request->model ?? 'default',
                traceId: $request->traceId
            );
        } catch (Exception $e) {
            return new CortexResponseData(
                success: false,
                traceId: $request->traceId,
                errorCode: 'OLLAMA_ADAPTER_ERROR',
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Helper to parse JSON from AI response.
     */
    private function parseJsonResponse(string $response): array
    {
        $clean = $response;
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $clean = $matches[0];
        }

        $json = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse AI JSON response: " . json_last_error_msg());
        }

        return $json;
    }

    public function supports(CortexCapability $capability): bool
    {
        return match ($capability) {
            CortexCapability::TEXT_GENERATION, CortexCapability::STRUCTURED_EXTRACTION => true,
            default => false,
        };
    }

    public function providerName(): string
    {
        return 'ollama';
    }
}
