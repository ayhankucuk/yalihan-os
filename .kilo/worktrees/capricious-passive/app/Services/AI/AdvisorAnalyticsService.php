<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;

/**
 * 📊 SAB SEALED
 * Advisor Analytics Service — Aggregates AI performance metrics.
 */
class AdvisorAnalyticsService
{
    /**
     * Get aggregated metrics for the AI Usage Dashboard.
     */
    public function getDashboardMetrics(): array
    {
        $successCount = DB::table('ai_query_telemetry')->count();
        $failureCount = DB::table('ai_query_failures')->count();

        return [
            'intentDistribution' => $this->getIntentDistribution(),
            'queryTrend'         => $this->getQueryTrend(),
            'topLocations'       => $this->getTopLocations(),
            'avgConfidence'      => $this->getAvgConfidence(),
            'successCount'       => $successCount,
            'failureCount'       => $failureCount,
        ];
    }

    private function getIntentDistribution()
    {
        return DB::table('ai_query_telemetry')
            ->select('intent_detected', DB::raw('count(*) as count'))
            ->groupBy('intent_detected')
            ->get();
    }

    private function getQueryTrend()
    {
        return DB::table('ai_query_telemetry')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getTopLocations()
    {
        return DB::table('ai_query_telemetry')
            ->select('location_ilce', 'location_mahalle', DB::raw('count(*) as count'))
            ->whereNotNull('location_ilce')
            ->groupBy('location_ilce', 'location_mahalle')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }

    private function getAvgConfidence()
    {
        return DB::table('ai_query_telemetry')
            ->where('intent_detected', 'MARKET_VALUATION')
            ->avg('confidence_score') ?? 0;
    }
}
