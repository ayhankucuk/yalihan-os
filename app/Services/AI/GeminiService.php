<?php

namespace App\Services\AI;

use App\Models\Setting;
use App\Services\AI\Monitoring\AiTelemetryService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 🛡️ Google Gemini AI Service
 * Standardized service for Google Gemini API interactions.
 * Modeled after OllamaService and OpenAIService for consistency.
 */
class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected AiTelemetryService $telemetryService;
    protected \App\Services\SettingService $settingService;

    public function __construct(
        AiTelemetryService $telemetryService,
        \App\Services\SettingService $settingService
    ) {
        $this->telemetryService = $telemetryService;
        $this->settingService = $settingService;
    }

    /**
     * Generate content using Gemini API
     */
    public function generate(string $prompt, array $options = []): array
    {
        $startTime = microtime(true);
        $model = $options['model'] ?? $this->getGeminiModel();
        $apiKey = $this->getGeminiApiKey();

        if (empty($apiKey)) {
            Log::error('GEMINI_API_KEY_MISSING');
            return [
                'success' => false,
                'error' => 'Google Gemini API key is not configured.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout($options['timeout'] ?? 30)
              ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                    'temperature' => $options['temperature'] ?? 0.7,
                ],
            ]);

            $duration = microtime(true) - $startTime;

            if (!$response->successful()) {
                $errorMessage = $response->json('error.message', 'Unknown Gemini API Error');
                
                $this->telemetryService->logFailure(
                    'google',
                    'generate',
                    $errorMessage,
                    $response->status(),
                    ['prompt' => substr($prompt, 0, 100)]
                );

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status' => $response->status(),
                ];
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Token count estimation (Gemini doesn't return exact usage in the same way as OpenAI)
            $promptTokens = (int) (strlen($prompt) / 4);
            $completionTokens = (int) (strlen($text) / 4);

            $this->telemetryService->logTransaction(
                'google',
                'generate',
                $duration,
                $promptTokens,
                $completionTokens,
                $response->status(),
                ['model' => $model]
            );

            return [
                'success' => true,
                'content' => $text,
                'model' => $model,
                'usage' => [
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens' => $promptTokens + $completionTokens,
                ],
            ];

        } catch (\Throwable $e) {
            Log::error('GEMINI_SERVICE_EXCEPTION', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Internal service error: ' . $e->getMessage(),
            ];
        }
    }

    protected function getGeminiApiKey(): string
    {
        return $this->settingService->get('google_api_key', config('ai.google_api_key', '')) ?? '';
    }

    protected function getGeminiModel(): string
    {
        return $this->settingService->get('google_model', config('ai.google_model', 'gemini-1.5-flash'));
    }
}
