<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 🛡️ AI Cost Guard Service
 * Phase 11: Budget enforcement and cost-based governance
 * Phase 12.2: Alert integration
 */
class AiCostGuardService
{
    protected AiAlertService $alertService;

    public function __construct(AiAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Check if a request is allowed based on budget constraints
     * 
     * @param string $provider
     * @param int|null $categoryId
     * @return array {
     *   allowed: bool,
     *   action: string (allow|downgrade|kill_switch),
     *   reason: string
     * }
     */
    public function checkBudget(?string $provider = null, ?int $categoryId = null): array
    {
        if (!config('ai-cost-guard.enabled')) {
            return ['allowed' => true, 'action' => 'allow', 'reason' => 'Cost Guard is disabled'];
        }

        $dailySpend = $this->getDailySpend();

        // 1. Global Kill Switch Check
        $globalLimit = config('ai-cost-guard.budgets.daily.global_limit_usd');
        if ($globalLimit > 0 && $dailySpend >= $globalLimit) {
            return [
                'allowed' => false,
                'action' => 'kill_switch',
                'reason' => "Global daily budget exceeded ($dailySpend / $globalLimit)",
                'level' => ($dailySpend / $globalLimit)
            ];
        }

        // 2. Threshold-based actions
        $usageRatio = $dailySpend / ($globalLimit ?: 1);

        // Phase 12.2: Trigger alerts
        $this->alertService->costGuardAlert($usageRatio, $dailySpend, $globalLimit);

        if ($usageRatio >= config('ai-cost-guard.thresholds.kill_switch')) {
            return [
                'allowed' => false,
                'action' => 'kill_switch',
                'reason' => 'Kill switch threshold reached',
                'level' => $usageRatio
            ];
        }

        if ($usageRatio >= config('ai-cost-guard.thresholds.downgrade')) {
            return [
                'allowed' => true,
                'action' => 'downgrade',
                'reason' => 'High budget usage - downgrading to cheaper provider',
                'level' => $usageRatio
            ];
        }

        if ($usageRatio >= config('ai-cost-guard.thresholds.warning')) {
            return [
                'allowed' => true,
                'action' => 'allow',
                'reason' => 'Warning threshold reached',
                'level' => $usageRatio
            ];
        }

        // 3. Provider/Category Specific Checks (Optional refinement)
        // For now, focusing on the core functional requirements

        return [
            'allowed' => true, 
            'action' => 'allow', 
            'reason' => 'Within budget',
            'level' => $usageRatio
        ];
    }

    /**
     * Get total USD spend for the current day
     */
    public function getDailySpend(): float
    {
        $cacheKey = 'ai_cost_guard_daily_spend_' . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 60, function () {
            // Sum from ai_feature_usages (Primary source for user-facing actions)
            $featureSpend = DB::table('ai_feature_usages')
                ->whereDate('created_at', now()->toDateString())
                ->sum('maliyet_usd') ?? 0;

            // Optional: Sum from ai_logs if they contain additional API costs not tracked in features
            $logSpend = DB::table('ai_logs')
                ->whereDate('created_at', now()->toDateString())
                ->sum('cost') ?? 0;

            // Prevent double counting if both track the same calls
            // For now, we take the MAX or SUM based on project reality.
            // Requirement says "Approximate cost", so SUM is safer but might be higher.
            // Let's go with SUM but normally these should be distinct or one should be SSOT.
            // In VisionAnalysisService, we record usage which might end up in one of these.
            
            return (float) ($featureSpend + $logSpend);
        });
    }

    /**
     * Clear spend cache (use after recording new usage)
     */
    public function clearSpendCache(): void
    {
        Cache::forget('ai_cost_guard_daily_spend_' . now()->format('Y-m-d'));
    }
}
