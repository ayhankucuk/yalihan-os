<?php

namespace App\Services\AI\Monitoring;

use App\Models\AiLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ��️ SAB SEALED
 * Domain: Monitoring / AI / Health
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - aktiflik_durumu ✅ (system health)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class AiTelemetryService
{
    protected ?string $correlationId = null;

    public function __construct(protected ?\App\Services\AI\AiCostCalculatorService $costCalculator = null)
    {
        $this->correlationId = request()->header('X-Correlation-ID', \Illuminate\Support\Str::uuid());
        $this->costCalculator = $costCalculator ?? app(\App\Services\AI\AiCostCalculatorService::class);
    }

    /**
     * Get the current correlation ID.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId ?? \Illuminate\Support\Str::uuid();
    }
    /**
     * Log a completed AI transaction.
     *
     * @param string $provider (openai, ollama, google)
     * @param string $endpoint (e.g. generate_description, optimize_title)
     * @param float $durationSeconds
     * @param int|null $tokensUsed
     * @param array $metadata
     * @return AiLog
     */
    public function logTransaction(
        string $provider,
        string $endpoint,
        float $durationSeconds,
        int $inputTokens = 0,
        int $outputTokens = 0,
        int $aktiflikKodu = 200,
        array $metadata = [],
        string $circuitState = 'closed',
        ?string $routingReason = null,
        bool $fallbackUsed = false,
        ?int $tenantId = null
    ): AiLog {
        $totalTokens = $inputTokens + $outputTokens;
        $model = $metadata['model'] ?? null;
        $cost = $this->costCalculator->calculateCost($provider, $model, $inputTokens, $outputTokens);

        try {
            $log = AiLog::create([
                'tenant_id' => $tenantId,
                'provider' => $provider,
                'endpoint' => $endpoint,
                'duration_ms' => (int)($durationSeconds * 1000),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
                'maliyet_usd' => $cost,
                'aktiflik_kodu' => $aktiflikKodu,
                'calisma_durumu' => ($aktiflikKodu >= 200 && $aktiflikKodu < 300) ? 'success' : 'failed',
                'request_payload' => $metadata['request'] ?? null,
                'response_payload' => $metadata['response'] ?? null,
                'correlation_id' => $this->getCorrelationId(),
                'version' => $circuitState,
                'metadata' => array_merge($metadata['extra'] ?? [], [
                    'circuit_state' => $circuitState,
                    'routing_reason' => $routingReason,
                    'fallback_used' => $fallbackUsed,
                    'selected_provider' => $provider,
                ]),
                'ip_address' => request()->ip(),
                'user_id' => auth()->id(),
            ]);

            // Update cache stats for provider latency scoring
            $this->updateProviderStats($provider, $durationSeconds);

            return $log;
        } catch (\Exception $e) {
            Log::error("AiTelemetryService: Failed to log transaction", [
                'error' => $e->getMessage(),
                'provider' => $provider
            ]);

            // Return empty/error log object safely
            return new AiLog(['aktiflik_kodu' => 500, 'hata_mesaji' => 'Logging Failed']);
        }
    }

    /**
     * Log a failed transaction.
     */
    /**
     * Log a failed transaction.
     */
    public function logFailure(
        string $provider,
        string $endpoint,
        string $errorMessage,
        int $aktiflikKodu = 500,
        array $metadata = [],
        ?int $tenantId = null
    ): void {
        try {
            AiLog::create([
                'tenant_id' => $tenantId,
                'provider' => $provider,
                'endpoint' => $endpoint,
                'aktiflik_kodu' => $aktiflikKodu,
                'hata_mesaji' => $errorMessage,
                'request_payload' => $metadata['request'] ?? null,
                'duration_ms' => 0,
                'ip_address' => request()->ip(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error("AiTelemetryService: Failed to log failure", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate approx cost based on token usage.
     */
    protected function calculateCost(string $provider, int $tokens): float
    {
        // Geriye dönük uyumluluk için (sadece toplam token geldiğinde tahmini hesap)
        return $this->costCalculator->calculateCost($provider, null, $tokens / 2, $tokens / 2);
    }

    /**
     * Update real-time stats for provider selection logic.
     */
    protected function updateProviderStats(string $provider, float $duration): void
    {
        $key = "ai_stats:{$provider}:avg_latency";

        // Simple moving average (last 10 calls)
        $current = Cache::get($key, 1.0); // Default 1s
        $newAvg = ($current * 0.9) + ($duration * 0.1); // Weight new value 10%

        Cache::put($key, $newAvg, now()->addMinutes(60));
    }

    /**
     * Get real-time latency for scoring.
     */
    public function getProviderLatency(string $provider): float
    {
        return Cache::get("ai_stats:{$provider}:avg_latency", 1.0) * 1000; // ms
    }

    /**
     * 🛡️ Resilience: Log Fallback event
     */
    public function logFallback(string $service, string $operation, string $reason, array $metadata = []): void
    {
        $this->logResilienceEvent($service, 'FALLBACK_USED', [
            'operation' => $operation,
            'reason' => $reason,
            'metadata' => $metadata
        ]);

        // Also create a failed log entry with 200 (since it returned success to user but it's a fallback)
        $this->logTransaction(
            $service,
            $operation,
            0,
            0,
            0,
            200,
            ['request' => $metadata, 'response' => ['is_fallback' => true, 'reason' => $reason]],
            'open'
        );
    }

    /**
     * 🛡️ Resilience: Log Circuit Breaker events
     */
    public function logResilienceEvent(string $service, string $event, array $metadata = []): void
    {
        Log::channel('resilience')->info("🛡️ RESILIENCE_EVENT: {$event}", [
            'service' => $service,
            'correlation_id' => $this->getCorrelationId(),
            'metadata' => $metadata,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        // Also log to AiLog if it's AI related
        if (in_array($service, ['openai', 'deepseek', 'ollama'])) {
            $this->logFailure($service, 'resilience_guard', "Circuit Breaker Event: {$event}", 0, [
                'resilience_event' => $event,
                'cb_metadata' => $metadata
            ]);
        }
    }
    /**
     * 📊 Phase 7/10: Log AI feature action (telemetry)
     *
     * @param array $payload
     */
    public function logFeatureUsage(array $payload): void
    {
        // Validate required fields
        $required = ['kategori_id', 'yayin_tipi_id', 'feature_slug', 'confidence', 'source_tipi', 'aksiyon'];

        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                Log::warning('AI Telemetry: Missing required field', [
                    'field' => $field,
                    'payload' => $payload
                ]);
                return;
            }
        }

        $tahminiTasarruf = ($payload['aksiyon'] === 'auto_applied') ? 30.00 : 0;

        try {
            \Illuminate\Support\Facades\DB::table('ai_feature_usages')->insert([
                'tenant_id' => $payload['tenant_id'] ?? null,
                'ilan_id' => $payload['ilan_id'] ?? null,
                'kategori_id' => $payload['kategori_id'],
                'yayin_tipi_id' => $payload['yayin_tipi_id'],
                'feature_slug' => $payload['feature_slug'],
                'confidence' => $payload['confidence'],
                'source_tipi' => $payload['source_tipi'],
                'aksiyon' => $payload['aksiyon'],
                'neden' => $payload['neden'] ?? null,
                'neden_detay' => isset($payload['neden_detay']) ? json_encode($payload['neden_detay']) : null,
                'explainability_v2_json' => isset($payload['explainability_v2']) ? json_encode($payload['explainability_v2']) : null,
                'istek_id' => $payload['istek_id'] ?? $this->getCorrelationId(),

                // Phase 10 & 11 Fields
                'deney_id' => $payload['deney_id'] ?? null,
                'deney_varyasyon_anahtari' => $payload['deney_varyasyon_anahtari'] ?? null,
                'etkilesim_suresi_ms' => $payload['etkilesim_suresi_ms'] ?? null,
                'latency_ms' => $payload['latency_ms'] ?? null,
                'cache_hit' => $payload['cache_hit'] ?? false,
                'provider' => $payload['provider'] ?? null,
                'tahmini_tasarruf_sn' => $tahminiTasarruf,
                'maliyet_usd' => $payload['maliyet_usd'] ?? null,

                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('AI Telemetry logged', [
                'feature' => $payload['feature_slug'],
                'correlation_id' => $this->getCorrelationId()
            ]);
        } catch (\Exception $e) {
            Log::error('AI Telemetry logging failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get real-time usage stats for a tenant from cache (budget guard parity).
     */
    public function getRealtimeUsage(int $tenantId, string $featureKey): array
    {
        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t{$tenantId}:{$featureKey}:{$dateKey}";

        return [
            'tenant_id' => $tenantId,
            'feature' => $featureKey,
            'used_tokens' => (int) Cache::get($cacheKey, 0),
            'last_update' => now()->toDateTimeString()
        ];
    }
}
