<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AnythingLLMService
{
    private Client $http;

    private string $baseUrl;

    private string $apiKey;

    private int $timeout;

    public function __construct()
    {
        $config = config('services.anythingllm');
        $baseUrl = (string) Arr::get($config, 'base_url', '');
        $apiKey = (string) Arr::get($config, 'api_key', '');
        $timeout = (int) Arr::get($config, 'timeout', 20);

        // Optional: override from Settings table if available
        try {
            if (class_exists(\App\Models\Setting::class)) {
                $settings = \App\Models\Setting::query()
                    ->whereIn('key', ['ai_anythingllm_url', 'ai_anythingllm_api_key', 'ai_anythingllm_timeout'])
                    ->pluck('value', 'key');
                $baseUrl = (string) ($settings['ai_anythingllm_url'] ?? $baseUrl);
                $apiKey = (string) ($settings['ai_anythingllm_api_key'] ?? $apiKey);
                $timeout = (int) ($settings['ai_anythingllm_timeout'] ?? $timeout);
            }
        } catch (\Throwable $e) {
            // Non-fatal: fallback to config
            Log::notice('AnythingLLM settings override skipped', ['error' => $e->getMessage()]);
        }

        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;

        $this->http = new Client([
            'base_uri' => rtrim($this->baseUrl, '/').'/',
            'timeout' => $this->timeout,
        ]);
    }

    public function health(): array
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            return ['ok' => false, 'message' => 'Missing ANYTHINGLLM config'];
        }
        try {
            $res = $this->http->get('api/health', [
                'headers' => $this->headers(),
            ]);

            return ['ok' => $res->getStatusCode() === 200];
        } catch (\Throwable $e) {
            Log::warning('AnythingLLM health error', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function completions(string $prompt, array $options = []): array
    {
        $payload = array_merge([
            'prompt' => $prompt,
            'max_tokens' => 512,
            'temperature' => 0.7,
        ], $options);

        try {
            $res = $this->http->post('api/completions', [
                'headers' => $this->headers(),
                'json' => $payload,
            ]);
            $json = json_decode((string) $res->getBody(), true) ?: [];

            return ['ok' => true, 'data' => $json];
        } catch (\Throwable $e) {
            Log::error('AnythingLLM completions error', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function embeddings(string $text, array $options = []): array
    {
        $payload = array_merge([
            'text' => $text,
        ], $options);

        try {
            $res = $this->http->post('api/embeddings', [
                'headers' => $this->headers(),
                'json' => $payload,
            ]);
            $json = json_decode((string) $res->getBody(), true) ?: [];

            return ['ok' => true, 'data' => $json];
        } catch (\Throwable $e) {
            Log::error('AnythingLLM embeddings error', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiKey,
        ];
    }
}
