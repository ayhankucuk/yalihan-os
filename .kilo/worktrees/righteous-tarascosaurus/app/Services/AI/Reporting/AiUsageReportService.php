<?php

namespace App\Services\AI\Reporting;

use Illuminate\Support\Facades\DB;
use App\Models\AiLog;

/**
 * 📊 AiUsageReportService
 * Aggregates AI telemetry data for SaaS reporting and dashboarding.
 */
class AiUsageReportService
{
    /**
     * Get daily token usage summary for a tenant.
     */
    public function getDailyUsage(int $tenantId, int $days = 30): array
    {
        return DB::table('ai_logs')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_tokens) as tokens'),
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(CASE WHEN aktiflik_kodu >= 200 AND aktiflik_kodu < 300 THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN aktiflik_kodu >= 400 THEN 1 ELSE 0 END) as failure_count')
            )
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get()
            ->toArray();
    }

    /**
     * Get usage breakdown by feature for a tenant.
     */
    public function getFeatureUsageBreakdown(int $tenantId, int $days = 30): array
    {
        return DB::table('ai_feature_usages')
            ->select(
                'feature_slug',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('AVG(confidence) as avg_confidence'),
                DB::raw('SUM(latency_ms) as total_latency_ms')
            )
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('feature_slug')
            ->get()
            ->toArray();
    }

    /**
     * Get provider-specific stats for a tenant.
     */
    public function getProviderStats(int $tenantId): array
    {
        return DB::table('ai_logs')
            ->select(
                'provider',
                DB::raw('COUNT(*) as request_count'),
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('AVG(duration_ms) as avg_latency_ms')
            )
            ->where('tenant_id', $tenantId)
            ->groupBy('provider')
            ->get()
            ->toArray();
    }
}
