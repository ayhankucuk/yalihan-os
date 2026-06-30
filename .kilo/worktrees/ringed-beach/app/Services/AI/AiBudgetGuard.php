<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ��️ SAB SEALED
 * AI Token Budget Guard - Feature-level cost control
 */
class AiBudgetGuard
{
    /**
     * Soft cap kontrolü yapar
     */
    public function checkSoftCap(string $featureKey, int $tokensToSpend, int $tenantId = 0): array
    {
        $cfg = $this->featureConfig($featureKey);
        $dailyBudget = (int) $cfg['tokens_per_day'];
        $softRatio = (float) $cfg['soft_cap_ratio'];

        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t{$tenantId}:{$featureKey}:{$dateKey}";

        $used = (int) Cache::get($cacheKey, 0);
        $nextUsed = $used + max(0, $tokensToSpend);

        $softCap = (int) floor($dailyBudget * $softRatio);

        return [
            'feature' => $featureKey,
            'tenant_id' => $tenantId,
            'daily_budget' => $dailyBudget,
            'soft_cap' => $softCap,
            'used' => $used,
            'next_used' => $nextUsed,
            'soft_cap_exceeded' => $nextUsed >= $softCap,
            'hard_cap_enabled' => (bool) $cfg['hard_cap_enabled'],
        ];
    }

    /**
     * Token ve Opsiyonel USD kullanımını cache'e kaydet
     */
    public function commit(string $featureKey, int $tokensSpent, int $tenantId = 0, float $costSpent = 0.0): void
    {
        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t{$tenantId}:{$featureKey}:{$dateKey}";
        $usdCacheKey = "ai:budget:usd:t{$tenantId}:{$featureKey}:{$dateKey}";

        $current = (int) Cache::get($cacheKey, 0);
        $newValue = $current + max(0, $tokensSpent);

        $currentUsd = (float) Cache::get($usdCacheKey, 0.0);
        $newUsdValue = $currentUsd + max(0, $costSpent);

        Cache::put($cacheKey, $newValue, now()->addHours(25));
        Cache::put($usdCacheKey, $newUsdValue, now()->addHours(25));
    }

    /**
     * Hard cap kontrolü yapar (Token ve Opsiyonel USD için)
     */
    public function checkHardCap(string $featureKey, int $tokensToSpend = 0, int $tenantId = 0, ?array $userContext = null, float $costToSpend = 0.0): void
    {
        $cfg = $this->featureConfig($featureKey);

        if (!($cfg['hard_cap_enabled'] ?? false)) {
            return;
        }

        $allowOverride = (bool) config("ai-budgets.features.{$featureKey}.allow_admin_override", false);
        $isAdmin = (bool) ($userContext['isAdmin'] ?? false);

        if ($allowOverride && $isAdmin) {
            return;
        }

        $dailyBudget = (int) $cfg['tokens_per_day'];
        $usdDailyBudget = (float) ($cfg['usd_per_day'] ?? 0.0);

        $hardCapRatio = (float) ($cfg['hard_cap_ratio'] ?? 1.0);
        $graceRatio = (float) ($cfg['grace_ratio'] ?? 1.1);

        $hardCap = (int) floor($dailyBudget * $hardCapRatio);
        $graceCap = (int) floor($dailyBudget * $graceRatio);

        $usdHardCap = $usdDailyBudget * $hardCapRatio;

        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t{$tenantId}:{$featureKey}:{$dateKey}";
        $usdCacheKey = "ai:budget:usd:t{$tenantId}:{$featureKey}:{$dateKey}";
        $graceKey = "ai:budget:grace:t{$tenantId}:{$featureKey}:{$dateKey}";

        $used = (int) Cache::get($cacheKey, 0);
        $nextUsed = $used + max(0, $tokensToSpend);

        $usedUsd = (float) Cache::get($usdCacheKey, 0.0);
        $nextUsedUsd = $usedUsd + max(0, $costToSpend);

        $graceUsed = (bool) Cache::get($graceKey, false);

        if ($nextUsed >= $hardCap || ($usdHardCap > 0 && $nextUsedUsd >= $usdHardCap)) {
            // Token veya USD limitine takıldı
            if ($nextUsed < $graceCap && !$graceUsed) {
                Cache::put($graceKey, true, now()->addHours(25));
                return;
            }

            $resetTime = now()->addDay()->startOfDay()->format('Y-m-d H:i:s');

            throw new \App\Exceptions\AiBudgetExceededException(
                $featureKey,
                $nextUsed,
                $hardCap,
                $graceCap,
                $resetTime
            );
        }
    }

    /**
     * Hard cap durumunu getir (dashboard/debug için)
     */
    public function getHardCapStatus(string $featureKey, int $tenantId = 0): array
    {
        $cfg = $this->featureConfig($featureKey);
        $dailyBudget = (int) $cfg['tokens_per_day'];
        $hardCapRatio = (float) ($cfg['hard_cap_ratio'] ?? 1.0);
        $graceRatio = (float) ($cfg['grace_ratio'] ?? 1.1);

        $hardCap = (int) floor($dailyBudget * $hardCapRatio);
        $graceCap = (int) floor($dailyBudget * $graceRatio);

        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t{$tenantId}:{$featureKey}:{$dateKey}";
        $graceKey = "ai:budget:grace:t{$tenantId}:{$featureKey}:{$dateKey}";

        $used = (int) Cache::get($cacheKey, 0);
        $graceUsed = (bool) Cache::get($graceKey, false);

        return [
            'feature' => $featureKey,
            'used' => $used,
            'hard_cap' => $hardCap,
            'grace_cap' => $graceCap,
            'remaining' => max(0, $hardCap - $used),
            'grace_used' => $graceUsed,
            'hard_cap_enabled' => (bool) ($cfg['hard_cap_enabled'] ?? false),
        ];
    }

    /**
     * Feature config'ini al (defaults + feature-specific merge)
     */
    private function featureConfig(string $featureKey): array
    {
        $defaults = config('ai-budgets.defaults', []);
        $feature = config("ai-budgets.features.{$featureKey}", []);

        return array_merge($defaults, $feature);
    }

    /**
     * Günlük kullanımı sıfırla (test/debug için)
     */
    public function reset(string $featureKey, int $tenantId = 0): void
    {
        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t{$tenantId}:{$featureKey}:{$dateKey}";
        $usdCacheKey = "ai:budget:usd:t{$tenantId}:{$featureKey}:{$dateKey}";
        $graceKey = "ai:budget:grace:t{$tenantId}:{$featureKey}:{$dateKey}";

        Cache::forget($cacheKey);
        Cache::forget($usdCacheKey);
        Cache::forget($graceKey);
    }

    /**
     * Tüm feature'ların güncel durumunu getir (dashboard için)
     */
    public function getAllBudgets(int $tenantId = 0): array
    {
        $features = array_keys(config('ai-budgets.features', []));
        $dateKey = now()->format('Y-m-d');
        $result = [];

        foreach ($features as $featureKey) {
            $cacheKey = "ai:budget:t{$tenantId}:{$featureKey}:{$dateKey}";
            $used = (int) Cache::get($cacheKey, 0);
            $cfg = $this->featureConfig($featureKey);

            $result[$featureKey] = [
                'used' => $used,
                'daily_budget' => (int) $cfg['tokens_per_day'],
                'soft_cap' => (int) floor($cfg['tokens_per_day'] * $cfg['soft_cap_ratio']),
                'usage_percent' => $cfg['tokens_per_day'] > 0
                    ? round(($used / $cfg['tokens_per_day']) * 100, 1)
                    : 0,
            ];
        }

        return $result;
    }
}
