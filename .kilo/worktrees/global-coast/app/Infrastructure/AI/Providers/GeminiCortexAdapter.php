<?php

namespace App\Infrastructure\AI\Providers;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\AITaskType;
use App\Domain\AI\Enums\CortexCapability;
use App\Services\AI\GeminiService;
use Exception;

/**
 * ️ GeminiCortexAdapter
 * 
 * Adapter for Google Gemini provider. Implements CortexServiceInterface
 * by wrapping the new GeminiService.
 */
final class GeminiCortexAdapter implements CortexServiceInterface
{
    public function __construct(
        private readonly GeminiService $gemini
    ) {}

    public function execute(CortexRequestData $request): CortexResponseData
    {
        try {
            $promptClass = match ($request->taskType) {
                AITaskType::ANALYZE_PROPERTY => \App\Application\AI\Prompts\AnalyzePropertyPrompt::class,
                AITaskType::EXTRACT_PROPERTY_FEATURES => \App\Application\AI\Prompts\ExtractFeaturesPrompt::class,
                AITaskType::SUGGEST_PROPERTY_TEMPLATE => \App\Application\AI\Prompts\SuggestTemplatePrompt::class,
                AITaskType::GENERATE_PROPERTY_TEMPLATE => \App\Application\AI\Prompts\GeneratePropertyTemplatePrompt::class,
                default => throw new Exception("Task type [{$request->taskType->value}] not implemented in Gemini adapter."),
            };

            /** @var \App\Domain\AI\Contracts\PromptInterface $prompt */
            $prompt = new $promptClass($request->input);
            $options = $prompt->getOptions();

            // Gemini works best with a concatenated prompt or specific content structure
            $fullPrompt = $prompt->getSystemInstructions() . "\n\n" . $prompt->getUserPrompt();

            $response = $this->gemini->generate(
                $fullPrompt,
                [
                    'model' => $request->model ?? $options['model'] ?? null,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                ]
            );

            if (!$response['success']) {
                throw new Exception($response['error'] ?? 'Gemini communication error.');
            }

            $output = $this->parseJsonResponse($response['content'] ?? '');

            return new CortexResponseData(
                success: true,
                output: $output,
                provider: 'google',
                model: $response['model'] ?? 'unknown',
                traceId: $request->traceId
            );
        } catch (Exception $e) {
            return new CortexResponseData(
                success: false,
                traceId: $request->traceId,
                errorCode: 'GEMINI_ADAPTER_ERROR',
                errorMessage: $e->getMessage()
            );
        }
    }

    private function parseJsonResponse(string $response): array
    {
        $clean = $response;
        // Gemini sometimes includes markdown fences
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $clean = $matches[1];
        } elseif (preg_match('/\{.*\}/s', $response, $matches)) {
            $clean = $matches[0];
        }

        $json = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse Gemini JSON response: " . json_last_error_msg());
        }

        return $json;
    }

    public function supports(CortexCapability $capability): bool
    {
        return match ($capability) {
            CortexCapability::TEXT_GENERATION, 
            CortexCapability::STRUCTURED_EXTRACTION,
            CortexCapability::VISION => true,
            default => false,
        };
    }

    public function providerName(): string
    {
        return 'google';
    }
}
