<?php

namespace App\Services\AI\Monetization;

use App\Models\AI\AiCreditBalance;
use App\Models\AiLog;
use App\Application\Shared\Services\TenantContextResolver;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AI Usage Query Service
 * 🛡️ Phase 12 Sprint 3 + Sprint 12.5 Hotfix
 *
 * SAB §10.1: Thin Controller — All business logic moved to Service layer
 * SAB §12.4: Tenant Isolation — All queries scoped to current tenant
 * Context7: Kanonik isimlendirme (aktiflik_kodu, snake_case)
 */
class AiUsageQueryService
{
    public function __construct(
        private readonly TenantContextResolver $tenantResolver
    ) {}

    /**
     * Get comprehensive dashboard data for AI usage telemetry
     *
     * @return array{
     *   credit_balance: AiCreditBalance|null,
     *   feature_breakdown: \Illuminate\Support\Collection,
     *   daily_trend: \Illuminate\Support\Collection,
     *   top_consumers: \Illuminate\Support\Collection,
     *   monthly_usage: int,
     *   projected_monthly_usage: float,
     *   tenant: \App\Models\SaaS\Tenant
     * }
     */
    public function getDashboardData(): array
    {
        $tenant = $this->tenantResolver->getTenant();

        return [
            'credit_balance' => $this->getCreditBalance($tenant->id),
            'feature_breakdown' => $this->getFeatureBreakdown($tenant->id),
            'daily_trend' => $this->getDailyTrend($tenant->id),
            'top_consumers' => $this->getTopConsumers($tenant->id),
            'monthly_usage' => $this->getMonthlyUsage($tenant->id),
            'projected_monthly_usage' => $this->getProjectedMonthlyUsage($tenant->id),
            'tenant' => $tenant,
        ];
    }

    /**
     * Get or create credit balance for tenant
     * SAB §12.4: Tenant-scoped query with deterministic ordering
     */
    private function getCreditBalance(int $tenantId): AiCreditBalance
    {
        $creditBalance = AiCreditBalance::where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();

        if (!$creditBalance) {
            // Initialize default balance if not exists
            $creditBalance = AiCreditBalance::create([
                'tenant_id' => $tenantId,
                'available_credits' => 0,
                'used_credits' => 0,
                'monthly_limit' => 1000,
                'last_reset_at' => now(),
            ]);
        }

        return $creditBalance;
    }

    /**
     * Get feature usage breakdown (last 30 days)
     * Context7: feature_key (snake_case)
     */
    private function getFeatureBreakdown(int $tenantId)
    {
        return AiLog::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->select('feature_key', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('feature_key')
            ->orderBy('usage_count', 'desc')
            ->get();
    }

    /**
     * Get daily usage trend (last 30 days)
     */
    private function getDailyTrend(int $tenantId)
    {
        return AiLog::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top AI consumers (users with most usage)
     */
    private function getTopConsumers(int $tenantId)
    {
        return AiLog::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('user_id')
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->with('user:id,name,email')
            ->get();
    }

    /**
     * Get current month usage count
     */
    private function getMonthlyUsage(int $tenantId): int
    {
        $monthStart = now()->startOfMonth();

        return AiLog::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $monthStart)
            ->count();
    }

    /**
     * Calculate projected monthly usage based on current trend
     */
    private function getProjectedMonthlyUsage(int $tenantId): float
    {
        $monthlyUsage = $this->getMonthlyUsage($tenantId);
        $daysInMonth = now()->daysInMonth;
        $daysPassed = now()->day;

        return $daysPassed > 0
            ? ($monthlyUsage / $daysPassed) * $daysInMonth
            : 0;
    }

    /**
     * Get usage logs for export (date range)
     *
     * @param int $tenantId
     * @param string $startDate Format: Y-m-d
     * @param string $endDate Format: Y-m-d
     * @return \Illuminate\Support\Collection
     */
    public function getLogsForExport(int $tenantId, string $startDate, string $endDate)
    {
        return AiLog::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get real-time usage statistics
     * Context7: snake_case field names
     *
     * @return array{
     *   available_credits: int,
     *   used_credits: int,
     *   monthly_limit: int,
     *   today_usage: int,
     *   month_usage: int,
     *   last_updated: string
     * }
     */
    public function getRealtimeStats(): array
    {
        $tenant = $this->tenantResolver->getTenant();

        $creditBalance = AiCreditBalance::where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();

        $todayUsage = AiLog::where('tenant_id', $tenant->id)
            ->whereDate('created_at', today())
            ->count();

        $monthUsage = AiLog::where('tenant_id', $tenant->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'available_credits' => $creditBalance->available_credits ?? 0,
            'used_credits' => $creditBalance->used_credits ?? 0,
            'monthly_limit' => $creditBalance->monthly_limit ?? 0,
            'today_usage' => $todayUsage,
            'month_usage' => $monthUsage,
            'last_updated' => now()->toISOString(),
        ];
    }
}
