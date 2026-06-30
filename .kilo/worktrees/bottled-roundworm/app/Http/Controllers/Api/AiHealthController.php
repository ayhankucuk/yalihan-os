<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

/**
 * AI Provider Health Check Controller
 * Context7 Compliant - Vanilla JS Standard
 */
class AiHealthController extends Controller
{
    /**
     * Check health/availability of all AI providers
     */
    public function health(): JsonResponse
    {
        $providers = [
            'openai' => $this->checkOpenAI(),
            'anthropic' => $this->checkAnthropic(),
            'google' => $this->checkGoogle(),
            'local' => $this->checkLocal(),
        ];

        $availableCount = collect($providers)->filter(fn ($p) => $p['available'])->count();

        return ResponseService::success([
            'providers' => $providers,
            'available_count' => $availableCount,
            'total_count' => count($providers),
            'timestamp' => now()->toIso8601String(),
        ], 'AI sağlayıcı durumu başarıyla kontrol edildi');
    }

    /**
     * Check OpenAI provider
     */
    private function checkOpenAI(): array
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return [
                'name' => 'OpenAI GPT-4',
                'available' => false,
                'reason' => 'API key not configured',
            ];
        }

        // Quick health check (without actual API call to save costs)
        return [
            'name' => 'OpenAI GPT-4',
            'available' => true,
            'reason' => 'API key configured',
        ];
    }

    /**
     * Check Anthropic Claude provider
     */
    private function checkAnthropic(): array
    {
        $apiKey = config('services.anthropic.api_key');

        if (empty($apiKey)) {
            return [
                'name' => 'Anthropic Claude',
                'available' => false,
                'reason' => 'API key not configured',
            ];
        }

        return [
            'name' => 'Anthropic Claude',
            'available' => true,
            'reason' => 'API key configured',
        ];
    }

    /**
     * Check Google Gemini provider
     */
    private function checkGoogle(): array
    {
        $apiKey = config('services.google.api_key');

        if (empty($apiKey)) {
            return [
                'name' => 'Google Gemini',
                'available' => false,
                'reason' => 'API key not configured',
            ];
        }

        return [
            'name' => 'Google Gemini',
            'available' => true,
            'reason' => 'API key configured',
        ];
    }

    /**
     * Check Local AI provider
     */
    private function checkLocal(): array
    {
        // Check if local AI service is running
        $localUrl = config('ai.local_url', 'http://localhost:11434');

        try {
            // Try to connect to local AI (e.g., Ollama)
            $response = Http::timeout(2)->get($localUrl);

            return [
                'name' => 'Local AI',
                'available' => $response->successful(),
                'reason' => $response->successful() ? 'Service running' : 'Service not responding',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Local AI',
                'available' => false,
                'reason' => 'Service not running',
            ];
        }
    }
}
