<?php

namespace App\Infrastructure\AI\Providers;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\AITaskType;
use App\Domain\AI\Enums\CortexCapability;
use App\Services\AI\OpenAIService;
use Exception;

/**
 * ️ DeepSeekCortexAdapter
 * 
 * Adapter for DeepSeek provider. Uses OpenAIService since DeepSeek is
 * OpenAI-compatible, but ensures the 'deepseek' provider configuration is used.
 */
final class DeepSeekCortexAdapter implements CortexServiceInterface
{
    public function __construct(
        private readonly OpenAIService $openai
    ) {}

    public function execute(CortexRequestData $request): CortexResponseData
    {
        try {
            $promptClass = match ($request->taskType) {
                AITaskType::ANALYZE_PROPERTY => \App\Application\AI\Prompts\AnalyzePropertyPrompt::class,
                AITaskType::EXTRACT_PROPERTY_FEATURES => \App\Application\AI\Prompts\ExtractFeaturesPrompt::class,
                AITaskType::SUGGEST_PROPERTY_TEMPLATE => \App\Application\AI\Prompts\SuggestTemplatePrompt::class,
                AITaskType::GENERATE_PROPERTY_TEMPLATE => \App\Application\AI\Prompts\GeneratePropertyTemplatePrompt::class,
                default => throw new Exception("Task type [{$request->taskType->value}] not implemented in DeepSeek adapter."),
            };

            /** @var \App\Domain\AI\Contracts\PromptInterface $prompt */
            $prompt = new $promptClass($request->input);
            $options = $prompt->getOptions();

            $messages = [
                ['role' => 'system', 'content' => $prompt->getSystemInstructions()],
                ['role' => 'user', 'content' => $prompt->getUserPrompt()],
            ];

            // Primary model for DeepSeek is deepseek-chat or deepseek-reasoner
            // Defaulting to deepseek-chat if not provided
            $model = $request->model ?? $options['model'] ?? 'deepseek-chat';

            $response = $this->openai->chat(
                $messages,
                $model,
                $options['temperature'] ?? 0.7,
                $request->taskType->value,
                'deepseek' // Explicitly tell OpenAIService to use DeepSeek's base_url/api_key
            );

            if (isset($response['is_fallback']) && $response['is_fallback']) {
                 return new CortexResponseData(
                    success: false,
                    traceId: $request->traceId,
                    errorCode: 'DEEPSEEK_FALLBACK_TRIGGERED',
                    errorMessage: $response['fallback_reason'] ?? 'DeepSeek fallback triggered.'
                );
            }

            $output = $this->parseJsonResponse($response['content'] ?? '');

            return new CortexResponseData(
                success: true,
                output: $output,
                provider: 'deepseek',
                model: $response['model'] ?? $model,
                traceId: $request->traceId
            );
        } catch (Exception $e) {
            return new CortexResponseData(
                success: false,
                traceId: $request->traceId,
                errorCode: 'DEEPSEEK_ADAPTER_ERROR',
                errorMessage: $e->getMessage()
            );
        }
    }

    private function parseJsonResponse(string $response): array
    {
        $clean = $response;
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $clean = $matches[0];
        }

        $json = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse DeepSeek JSON response: " . json_last_error_msg());
        }

        return $json;
    }

    public function supports(CortexCapability $capability): bool
    {
        return match ($capability) {
            CortexCapability::TEXT_GENERATION, 
            CortexCapability::STRUCTURED_EXTRACTION => true,
            default => false,
        };
    }

    public function providerName(): string
    {
        return 'deepseek';
    }
}
