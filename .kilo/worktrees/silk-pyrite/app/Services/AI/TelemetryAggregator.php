<?php

namespace App\Services\AI;

use App\Models\AiLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * AI Telemetry Aggregator Service (Phase 13 - Epic 2)
 *
 * Purpose: Aggregate raw ai_logs into hourly metrics for dashboard performance
 *
 * Context7 Compliance:
 * - Turkish naming conventions
 * - No forbidden fields
 * - Clean separation of concerns
 */
class TelemetryAggregator
{
    /**
     * Aggregate AI logs for the previous hour into ai_telemetry_hourly table.
     *
     * @param Carbon|null $targetHour Specific hour to aggregate (defaults to previous hour)
     * @return array Aggregation summary
     */
    public function aggregateHourly(?Carbon $targetHour = null): array
    {
        $targetHour = $targetHour ?? Carbon::now()->subHour()->startOfHour();
        $hourStart = $targetHour->copy();
        $hourEnd = $targetHour->copy()->addHour();

        Log::info("AI Telemetry: Starting hourly aggregation", [
            'target_hour' => $hourStart->toDateTimeString()
        ]);

        // Get all distinct provider+endpoint combinations for this hour
        $combinations = AiLog::whereBetween('olusturma_tarihi', [$hourStart, $hourEnd])
            ->select('provider', 'endpoint')
            ->distinct()
            ->get();

        $aggregated = 0;

        foreach ($combinations as $combo) {
            $this->aggregateProviderEndpoint(
                $hourStart,
                $combo->provider,
                $combo->endpoint
            );
            $aggregated++;
        }

        // Cache invalidation for dashboard
        $this->invalidateDashboardCache();

        return [
            'success' => true,
            'target_hour' => $hourStart->toDateTimeString(),
            'combinations_processed' => $aggregated,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Aggregate metrics for a specific provider+endpoint combination.
     */
    private function aggregateProviderEndpoint(Carbon $hourStart, string $provider, string $endpoint): void
    {
        $hourEnd = $hourStart->copy()->addHour();

        // Aggregate metrics from ai_logs
        $metrics = AiLog::whereBetween('olusturma_tarihi', [$hourStart, $hourEnd])
            ->where('provider', $provider)
            ->where('endpoint', $endpoint)
            ->selectRaw('
                COUNT(*) as toplam_istek,
                SUM(CASE WHEN aktiflik_kodu >= 200 AND aktiflik_kodu < 300 THEN 1 ELSE 0 END) as basarili_istek,
                SUM(CASE WHEN aktiflik_kodu >= 400 THEN 1 ELSE 0 END) as hatali_istek,
                COALESCE(SUM(total_tokens), 0) as toplam_token,
                COALESCE(AVG(duration_ms), 0) as ortalama_gecikme_ms,
                COALESCE(MIN(duration_ms), 0) as min_gecikme_ms,
                COALESCE(MAX(duration_ms), 0) as max_gecikme_ms
            ')
            ->first();

        // Calculate cost (using simple token-to-cost conversion)
        // This should eventually call a proper cost calculator service
        $costUsd = $this->calculateCost($provider, $metrics->toplam_token ?? 0);

        // Upsert into ai_telemetry_hourly
        DB::table('ai_telemetry_hourly')->updateOrInsert(
            [
                'tarih_saat' => $hourStart,
                'provider_adi' => $provider,
                'endpoint_adi' => $endpoint,
            ],
            [
                'toplam_istek' => $metrics->toplam_istek ?? 0,
                'basarili_istek' => $metrics->basarili_istek ?? 0,
                'hatali_istek' => $metrics->hatali_istek ?? 0,
                'toplam_token' => $metrics->toplam_token ?? 0,
                'toplam_maliyet_usd' => $costUsd,
                'ortalama_gecikme_ms' => round($metrics->ortalama_gecikme_ms ?? 0),
                'min_gecikme_ms' => $metrics->min_gecikme_ms ?? 0,
                'max_gecikme_ms' => $metrics->max_gecikme_ms ?? 0,
                'guncelleme_tarihi' => now(),
            ]
        );
    }

    /**
     * Calculate cost based on provider and token count.
     * Simplified version - should use actual pricing from config.
     */
    private function calculateCost(string $provider, int $tokens): float
    {
        // Simplified cost calculation (USD per 1K tokens)
        $costPer1k = match($provider) {
            'openai' => 0.002, // GPT-4 pricing
            'gemini' => 0.0005,
            'ollama' => 0.0, // Local, free
            default => 0.001
        };

        return ($tokens / 1000) * $costPer1k;
    }

    /**
     * Invalidate Redis cache for dashboard metrics.
     */
    private function invalidateDashboardCache(): void
    {
        // Clear dashboard metric caches
        Cache::tags(['ai_telemetry', 'dashboard'])->flush();
    }

    /**
     * Get cached aggregated metrics for dashboard (last 24 hours).
     *
     * @return array
     */
    /**
     * Get 24h dashboard metrics (optimized for high speed).
     * Cached for 5 minutes.
     */
    public function getDashboardMetrics(): array
    {
        return Cache::tags(['ai_telemetry', 'dashboard'])->remember('dashboard:ai_metrics:24h', 300, function () {
            $now = Carbon::now();
            $last24h = $now->copy()->subDay();

            // Aggregate metrics from hourly table (Single Query)
            $metrics = DB::table('ai_telemetry_hourly')
                ->where('tarih_saat', '>=', $last24h)
                ->selectRaw('SUM(toplam_istek) as total_requests')
                ->selectRaw('SUM(basarili_istek) as successful_requests')
                ->selectRaw('SUM(hatali_istek) as failed_requests')
                ->selectRaw('SUM(toplam_token) as total_tokens')
                ->selectRaw('SUM(toplam_maliyet_usd) as total_cost')
                ->selectRaw('AVG(ortalama_gecikme_ms) as avg_latency')
                ->first();

            // Calculate trends (compare vs previous 24h)
            $prev24hStart = $last24h->copy()->subDay();
            $prevMetrics = DB::table('ai_telemetry_hourly')
                ->whereBetween('tarih_saat', [$prev24hStart, $last24h])
                ->selectRaw('SUM(toplam_istek) as total_requests')
                ->selectRaw('SUM(toplam_maliyet_usd) as total_cost')
                ->first();

            $requestTrend = $this->calculateTrend($prevMetrics->total_requests ?? 0, $metrics->total_requests ?? 0);
            $costTrend = $this->calculateTrend($prevMetrics->total_cost ?? 0, $metrics->total_cost ?? 0);

            // Get Provider Breakdown
            $providerBreakdown = DB::table('ai_telemetry_hourly')
                ->where('tarih_saat', '>=', $last24h)
                ->select('provider_adi')
                ->selectRaw('SUM(toplam_istek) as requests')
                ->selectRaw('SUM(toplam_maliyet_usd) as cost')
                ->selectRaw('ROUND(AVG(ortalama_gecikme_ms)) as avg_latency')
                ->groupBy('provider_adi')
                ->get()
                ->toArray();

            return [
                'total_requests' => (int) ($metrics->total_requests ?? 0),
                'successful_requests' => (int) ($metrics->successful_requests ?? 0),
                'failed_requests' => (int) ($metrics->failed_requests ?? 0),
                'total_tokens' => (int) ($metrics->total_tokens ?? 0),
                'total_cost' => (float) ($metrics->total_cost ?? 0),
                'avg_latency' => (int) ($metrics->avg_latency ?? 0),
                'trends' => [
                    'requests' => $requestTrend,
                    'cost' => $costTrend,
                ],
                'provider_breakdown' => $providerBreakdown,
            ];
        });
    }

    private function calculateTrend($prev, $current): float
    {
        if ($prev == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $prev) / $prev) * 100, 1);
    }

    private function getTotalCost(Carbon $since): float
    {
        return DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', $since)
            ->sum('toplam_maliyet_usd') ?? 0.0;
    }

    private function getTotalRequests(Carbon $since): int
    {
        return DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', $since)
            ->sum('toplam_istek') ?? 0;
    }

    private function getSuccessRate(Carbon $since): float
    {
        $totals = DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', $since)
            ->selectRaw('SUM(basarili_istek) as success, SUM(toplam_istek) as total')
            ->first();

        if (!$totals || $totals->total == 0) {
            return 100.0;
        }

        return round(($totals->success / $totals->total) * 100, 2);
    }

    private function getAverageLatency(Carbon $since): int
    {
        return (int) DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', $since)
            ->avg('ortalama_gecikme_ms') ?? 0;
    }

    private function getProviderBreakdown(Carbon $since): array
    {
        return DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', $since)
            ->select('provider_adi')
            ->selectRaw('SUM(toplam_istek) as requests')
            ->selectRaw('SUM(toplam_maliyet_usd) as cost')
            ->selectRaw('ROUND(AVG(ortalama_gecikme_ms)) as avg_latency')
            ->groupBy('provider_adi')
            ->get()
            ->toArray();
    }

    /**
     * Get cost timeline data.
     */
    public function getCostTimeline(int $hours): \Illuminate\Support\Collection
    {
        return DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', Carbon::now()->subHours($hours))
            ->select('tarih_saat')
            ->selectRaw('SUM(toplam_maliyet_usd) as maliyet')
            ->groupBy('tarih_saat')
            ->orderBy('tarih_saat') // context7-ignore
            ->get();
    }

    /**
     * Get provider performance breakdown.
     */
    public function getProviderPerformanceBreakdown(): \Illuminate\Support\Collection
    {
        return DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', Carbon::now()->subDay())
            ->select('provider_adi')
            ->selectRaw('SUM(toplam_istek) as total_requests')
            ->selectRaw('SUM(basarili_istek) as successful_requests')
            ->selectRaw('ROUND(AVG(ortalama_gecikme_ms)) as avg_latency')
            ->selectRaw('SUM(toplam_maliyet_usd) as total_cost')
            ->groupBy('provider_adi')
            ->get();
    }

    /**
     * Get request volume timeline.
     */
    public function getVolumeTimeline(int $hours): \Illuminate\Support\Collection
    {
        return DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', Carbon::now()->subHours($hours))
            ->select('tarih_saat', 'provider_adi')
            ->selectRaw('SUM(toplam_istek) as requests')
            ->groupBy('tarih_saat', 'provider_adi')
            ->orderBy('tarih_saat') // context7-ignore
            ->get();
    }

    /**
     * Get error analytics from logs and hourly table.
     */
    public function getErrorAnalyticsData(): array
    {
        $last24h = Carbon::now()->subDay();

        $totals = DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', $last24h)
            ->selectRaw('SUM(toplam_istek) as total')
            ->selectRaw('SUM(hatali_istek) as errors')
            ->first();

        $recentErrors = DB::table('ai_logs')
            ->where('durum_kodu', '>=', 400)
            ->where('olusturma_tarihi', '>=', $last24h)
            ->select('provider', 'endpoint', 'aktiflik_kodu', 'hata_mesaji')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('provider', 'endpoint', 'aktiflik_kodu', 'hata_mesaji')
            ->orderByDesc('count') // context7-ignore
            ->limit(5)
            ->get();

        return [
            'total_requests' => $totals->total ?? 0,
            'total_errors' => $totals->errors ?? 0,
            'recent_errors' => $recentErrors,
        ];
    }

    /**
     * Get token consumption leaderboard.
     */
    public function getTokenLeaderboard(int $days = 7): \Illuminate\Support\Collection
    {
        return DB::table('ai_telemetry_hourly')
            ->where('tarih_saat', '>=', Carbon::now()->subDays($days))
            ->select('endpoint_adi', 'provider_adi')
            ->selectRaw('SUM(toplam_token) as total_tokens')
            ->selectRaw('SUM(toplam_maliyet_usd) as total_cost')
            ->selectRaw('SUM(toplam_istek) as request_count')
            ->groupBy('endpoint_adi', 'provider_adi')
            ->orderByDesc('total_tokens') // context7-ignore
            ->limit(10)
            ->get();
    }
}

