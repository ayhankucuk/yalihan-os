<?php

namespace App\Services\AI\Monitoring;

use App\Models\AiLog;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * AiTelemetryAggregator — Rolling Window Stats from ai_logs
 *
 * SAB v4.1 Kural 8: Telemetry-driven decisions
 * Konum: AI/Monitoring (infra layer — domain service limitini yormaz)
 *
 * Bu servis ai_logs tablosundan rolling window istatistikleri toplar:
 * - p50/p95 latency
 * - Success rate
 * - Token usage
 * - Estimated cost
 * - Call count
 *
 * Sonuclar 5 dk cache'lenir (DB'yi yorma).
 *
 * Fix #66 (2026-05-15): Cache key ve query'ye tenant_id eklendi.
 * Cross-tenant veri sızıntısı önlendi. TenantContextService inject edildi.
 */
class AiTelemetryAggregator
{
    private const CACHE_TTL_MINUTES = 5;

    public function __construct(
        private readonly TenantContextService $tenantContext,
    ) {}

    /**
     * Mevcut kiracının tenant_id'sini güvenle döndürür.
     * Bağlam kurulmamışsa null döner (örn: Artisan command, test).
     */
    private function currentTenantId(): ?int
    {
        if (!$this->tenantContext->hasTenant()) {
            return null;
        }

        return $this->tenantContext->getTenant()->id;
    }

    /**
     * Provider bazli rolling window istatistikleri
     *
     * @param string|null $taskType Filtre: endpoint (null = tum endpoint'ler)
     * @param int $windowHours Zaman penceresi (default: 24 saat)
     * @return array<string, array{
     *   call_count: int,
     *   success_rate: float,
     *   p50_ms: float,
     *   p95_ms: float,
     *   avg_tokens: float,
     *   estimated_cost: float,
     * }>
     */
    public function getProviderStats(?string $taskType = null, int $windowHours = 24): array
    {
        $tenantId = $this->currentTenantId();
        $cacheKey = "ai_provider_stats:{$tenantId}:{$taskType}:{$windowHours}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($taskType, $windowHours, $tenantId) {
            return $this->computeProviderStats($taskType, $windowHours, $tenantId);
        });
    }

    /**
     * Endpoint bazli istatistikler (baseline raporu icin)
     *
     * @return array<int, array{
     *   endpoint: string,
     *   call_count: int,
     *   p95_ms: float,
     *   avg_tokens: float,
     *   estimated_cost: float,
     *   error_rate: float,
     * }>
     */
    public function getEndpointStats(int $windowHours = 24): array
    {
        $tenantId = $this->currentTenantId();
        $cacheKey = "ai_endpoint_stats:{$tenantId}:{$windowHours}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($windowHours, $tenantId) {
            return $this->computeEndpointStats($windowHours, $tenantId);
        });
    }

    /**
     * Cache'i sifirla (test veya manual refresh icin)
     * Not: tenant-specific key'ler dinamik oldugundan Redis tag-based
     * invalidation gerekmektedir. Bu metod yalnizca bağlamsal tenant'i temizler.
     */
    public function flushCache(): void
    {
        $tenantId = $this->currentTenantId();
        Cache::forget("ai_provider_stats:{$tenantId}:");
        Cache::forget("ai_endpoint_stats:{$tenantId}:");
    }

    // ─── PRIVATE COMPUTATION ───

    private function computeProviderStats(?string $taskType, int $windowHours, ?int $tenantId): array
    {
        $query = AiLog::where('olusturma_tarihi', '>=', now()->subHours($windowHours));

        // Fix #66: Tenant izolasyonu — sadece mevcut kiracının logları
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($taskType) {
            $query->where('endpoint', $taskType);
        }

        $logs = $query->select(['provider', 'duration_ms', 'total_tokens', 'aktiflik_kodu'])
            ->get();

        if ($logs->isEmpty()) {
            return [];
        }

        $grouped = $logs->groupBy('provider');
        $results = [];

        foreach ($grouped as $provider => $providerLogs) {
            $total = $providerLogs->count();
            $successful = $providerLogs->where('aktiflik_kodu', 200)->count();

            // Latency percentiles (sadece basarili cagrilar)
            $durations = $providerLogs->where('aktiflik_kodu', 200)
                ->pluck('duration_ms')
                ->sort()
                ->values();

            $p50 = $this->percentile($durations, 50);
            $p95 = $this->percentile($durations, 95);

            // Token & cost
            $avgTokens = $providerLogs->avg('total_tokens') ?? 0;
            $estimatedCost = $this->estimateCost($provider, $providerLogs->sum('total_tokens'));

            $results[$provider] = [
                'call_count' => $total,
                'success_rate' => $total > 0 ? round($successful / $total, 4) : 0,
                'p50_ms' => round($p50, 2),
                'p95_ms' => round($p95, 2),
                'avg_tokens' => round($avgTokens, 0),
                'estimated_cost' => round($estimatedCost, 6),
            ];
        }

        return $results;
    }

    private function computeEndpointStats(int $windowHours, ?int $tenantId): array
    {
        $query = AiLog::where('olusturma_tarihi', '>=', now()->subHours($windowHours));

        // Fix #66: Tenant izolasyonu
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $logs = $query->select(['endpoint', 'provider', 'duration_ms', 'total_tokens', 'aktiflik_kodu'])
            ->get();

        if ($logs->isEmpty()) {
            return [];
        }

        $grouped = $logs->groupBy('endpoint');
        $results = [];

        foreach ($grouped as $endpoint => $endpointLogs) {
            $total = $endpointLogs->count();
            $failed = $endpointLogs->where('aktiflik_kodu', '>=', 400)->count();

            $durations = $endpointLogs->pluck('duration_ms')->sort()->values();

            $results[] = [
                'endpoint' => $endpoint,
                'call_count' => $total,
                'p95_ms' => round($this->percentile($durations, 95), 2),
                'avg_tokens' => round($endpointLogs->avg('total_tokens') ?? 0, 0),
                'estimated_cost' => round($this->estimateCost(
                    $endpointLogs->first()->provider ?? 'ollama',
                    $endpointLogs->sum('total_tokens')
                ), 6),
                'error_rate' => $total > 0 ? round($failed / $total, 4) : 0,
            ];
        }

        // Call count'a gore sirala (en yogun ustte)
        usort($results, fn($a, $b) => $b['call_count'] <=> $a['call_count']);

        return $results;
    }

    /**
     * Percentile hesapla (collection uzerinden)
     */
    private function percentile(\Illuminate\Support\Collection $sorted, int $percentile): float
    {
        if ($sorted->isEmpty()) {
            return 0.0;
        }

        $index = (int) ceil(($percentile / 100) * $sorted->count()) - 1;
        $index = max(0, min($index, $sorted->count() - 1));

        return (float) $sorted->get($index, 0);
    }

    /**
     * Tahmini maliyet (provider bazli token fiyatlari)
     */
    private function estimateCost(string $provider, int $totalTokens): float
    {
        $costPer1K = match ($provider) {
            'ollama' => 0.0,
            'deepseek' => 0.001,
            'openai' => 0.0015,
            'google' => 0.0010,
            default => 0.0,
        };

        return ($totalTokens / 1000) * $costPer1K;
    }
}
