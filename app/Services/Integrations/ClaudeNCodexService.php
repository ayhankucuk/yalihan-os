<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Claude-N-Codex API Entegrasyon Servisi
 *
 * OpenAI-compatible API: https://claude-n-codex.com:8443/v1/chat/completions
 *
 * Kullanım:
 *   $response = app(ClaudeNCodexService::class)->chat('Merhaba!');
 *
 * @sab-ignore-catch
 */
class ClaudeNCodexService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.claude_n_codex.base_url'), '/');
        $this->apiKey   = config('services.claude_n_codex.api_key', '');
        $this->model    = config('services.claude_n_codex.model', 'gpt-5.5');
        $this->timeout  = (int) config('services.claude_n_codex.timeout', 30);
    }

    /**
     * Basit chat yanıtı al
     *
     * @param  string $message  Kullanıcı mesajı
     * @param  array  $options Ek parametreler (temperature, max_tokens vs.)
     * @return string|null      AI yanıtı veya null
     */
    public function chat(string $message, array $options = []): ?string
    {
        if (! $this->isEnabled()) {
            Log::warning('ClaudeNCodex: Servis devre dışı.');
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", array_merge([
                    'model'    => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $message],
                    ],
                    'max_tokens' => $options['max_tokens'] ?? 1024,
                ], $options));

            if (! $response->successful()) {
                Log::error('ClaudeNCodex: HTTP ' . $response->status(), [
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            return $data['choices'][0]['message']['content'] ?? null;
        } catch (\Throwable $e) {
            Log::error('ClaudeNCodex: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Chat completion (full response)
     *
     * @param  array  $messages  [['role' => 'user', 'content' => '...'], ...]
     * @param  array  $options   Ek parametreler
     * @return array|null        Tüm API yanıtı veya null
     */
    public function chatComplete(array $messages, array $options = []): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", array_merge([
                    'model'    => $this->model,
                    'messages' => $messages,
                    'max_tokens' => $options['max_tokens'] ?? 1024,
                ], $options));

            if (! $response->successful()) {
                Log::error('ClaudeNCodex: HTTP ' . $response->status(), [
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('ClaudeNCodex: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sistem sağlığı kontrol et
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get("{$this->baseUrl}/models");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('ClaudeNCodex health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Servis aktif mi?
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) config('services.claude_n_codex.enabled', false)
            && ! empty($this->apiKey);
    }

    /**
     * Mevcut model adı
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
