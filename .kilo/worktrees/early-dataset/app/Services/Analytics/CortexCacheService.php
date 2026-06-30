<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 🛡️ SAB Sprint 1 — Cortex Analytics Cache Extraction
 * CortexAnalyticsDashboardController içindeki 5 Cache:: mutation → burada.
 *
 * Authority: .sab/authority.json §RULE-C2
 * Covered keys: cortex_dashboard_analytics, cortex_performance_metrics,
 *               cortex_spatial_{ilanId}
 */
class CortexCacheService
{
    private const DASHBOARD_TTL  = 600;   // 10 dakika
    private const PERFORMANCE_TTL = 300;  // 5 dakika

    // --- WRITE ---

    public function putDashboard(array $data): void
    {
        Cache::put('cortex_dashboard_analytics', $data, self::DASHBOARD_TTL);
    }

    public function putPerformance(array $data): void
    {
        Cache::put('cortex_performance_metrics', $data, self::PERFORMANCE_TTL);
    }

    // --- READ ---

    public function getDashboard(): mixed
    {
        return Cache::get('cortex_dashboard_analytics');
    }

    public function getPerformance(): mixed
    {
        return Cache::get('cortex_performance_metrics');
    }

    // --- INVALIDATE ---

    public function invalidateDashboard(): void
    {
        Cache::forget('cortex_dashboard_analytics');
        Cache::forget('cortex_performance_metrics');
    }

    public function invalidateSpatial(int $ilanId): void
    {
        Cache::forget("cortex_spatial_{$ilanId}");
    }

    /**
     * clearCache() — dashboard + performance + tüm spatial key'leri temizle.
     * CortexAnalyticsDashboardController::clearCache ile birebir eşleşen toplu invalidasyon.
     */
    public function invalidateAll(): void
    {
        $this->invalidateDashboard();

        DB::table('ilanlar')
            ->pluck('id')
            ->each(fn ($id) => $this->invalidateSpatial((int) $id));
    }
}
