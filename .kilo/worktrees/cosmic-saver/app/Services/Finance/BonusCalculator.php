<?php

namespace App\Services\Finance;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ilan;
use App\Models\Finance\Bonus;
use App\Models\Finance\FinancialSetting;
use App\Application\Shared\Services\TenantContextResolver;
use Illuminate\Support\Collection;

/**
 * Bonus Calculator Service (Prim Hesaplama)
 *
 * Calculates performance-based bonuses based on sales targets
 *
 * Context7 Compliance:
 * - ✅ Uses is_paid boolean (not forbidden keyword)
 * - ✅ Service layer pattern
 * - ✅ bonus_tier enum (bronze/silver/gold)
 * - ✅ TenantContextResolver DI (SAB §12)
 */
class BonusCalculator
{
    public function __construct(
        private readonly TenantContextResolver $tenantResolver
    ) {}

    /**
     * Calculate monthly bonus for an agent (PURE PROJECTION)
     *
     * @param int $agentId
     * @param string $month Format: YYYY-MM
     * @param float|null $customTarget Override default target
     * @return array
     */
    public function calculateBonusProjection(int $agentId, string $month, ?float $customTarget = null): array
    {
        $settings = FinancialSetting::query()->first();
        $bonusTiers = json_decode($settings->bonus_tiers, true);

        $sales = $this->getMonthSales($agentId, $month);
        $monthlyTarget = $customTarget ?? $this->calculateDefaultTarget($agentId);

        $achievedAmount = $sales->sum('fiyat');
        $achievementPercentage = $monthlyTarget > 0 ? ($achievedAmount / $monthlyTarget) * 100 : 0;

        $tierInfo = $this->determineBonusTier($achievementPercentage, $bonusTiers);

        $bonusAmount = $tierInfo['tier'] !== 'none'
            ? ($achievedAmount * $tierInfo['rate']) / 100
            : 0;

        return [
            'tenant_id' => $this->tenantResolver->resolve()->tenantId,
            'agent_id' => $agentId,
            'target_month' => $month,
            'prim_tutari' => round($bonusAmount, 2),
            'monthly_target' => round($monthlyTarget, 2),
            'achieved_amount' => round($achievedAmount, 2),
            'achievement_percentage' => round($achievementPercentage, 2),
            'total_sales_count' => $sales->count(),
            'bonus_tier' => $tierInfo['tier'],
            'bonus_rate' => $tierInfo['rate'],
            'is_paid' => false,
        ];
    }

    /**
     * Mark bonus as paid (PROJECTION DATA ONLY)
     */
    public function getPayoutProjection(): array
    {
        return [
            'is_paid' => true,
            'payout_date' => now()->toDateString(),
            'paid_by' => auth()->id(),
            'updated_at' => now()
        ];
    }

    /**
     * Get agent's unpaid bonuses
     *
     * @param int $agentId
     * @return Collection
     */
    public function getUnpaidBonuses(int $agentId): Collection
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return Bonus::query()
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $agentId)
            ->where('is_paid', false)
            ->orderBy('target_month', 'desc') // context7-ignore
            ->get();
    }

    /**
     * Get agent's total unpaid bonus amount
     *
     * @param int $agentId
     * @return float
     */
    public function getTotalUnpaidBonuses(int $agentId): float
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return Bonus::query()
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $agentId)
            ->where('is_paid', false)
            ->sum('prim_tutari');
    }



    /**
     * Simulate bonus (for frontend calculator)
     *
     * @param float $monthlyTarget
     * @param float $achievedAmount
     * @return array
     */
    public function simulateBonus(float $monthlyTarget, float $achievedAmount): array
    {
        $settings = FinancialSetting::query()->first();
        $bonusTiers = json_decode($settings->bonus_tiers, true);

        $achievementPercentage = $monthlyTarget > 0
            ? ($achievedAmount / $monthlyTarget) * 100
            : 0;

        $tierInfo = $this->determineBonusTier($achievementPercentage, $bonusTiers);

        $bonusAmount = $tierInfo['tier'] !== 'none'
            ? ($achievedAmount * $tierInfo['rate']) / 100
            : 0;

        return [
            'monthly_target' => round($monthlyTarget, 2),
            'achieved_amount' => round($achievedAmount, 2),
            'achievement_percentage' => round($achievementPercentage, 2),
            'bonus_tier' => $tierInfo['tier'],
            'bonus_rate' => $tierInfo['rate'],
            'bonus_amount' => round($bonusAmount, 2),
        ];
    }

    /**
     * Get agent's bonuses.
     */
    public function getAgentBonuses(int $agentId): Collection
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return Bonus::query()
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $agentId)
            ->orderBy('target_month', 'desc') // context7-ignore
            ->get();
    }

    /**
     * Get monthly target for agent.
     */
    public function getMonthlyTarget(int $agentId, string $month): float
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $bonus = Bonus::query()
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $agentId)
            ->where('target_month', $month)
            ->first();
        return (float) ($bonus->monthly_target ?? 0.0);
    }

    /**
     * Get sales for a period.
     */
    public function getSalesForPeriod(int $agentId, string $start, string $end): Collection
    {
        return Ilan::query()
            ->where('kullanici_id', $agentId)
            ->where('yayin_durumu', 'Satıldı')
            ->whereBetween('updated_at', [$start, $end])
            ->get();
    }

    /**
     * Get paginated bonuses for admin.
     */
    public function getPaginatedBonuses(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $query = Bonus::query()
            ->where('bonuses.tenant_id', $tenantId)
            ->leftJoin('users as agents', 'bonuses.agent_id', '=', 'agents.id')
            ->select('bonuses.*', 'agents.name as agent_name');

        if (isset($filters['paid'])) {
            $query->where('bonuses.is_paid', $filters['paid'] == '1');
        }
        if (isset($filters['target_month'])) {
            $query->where('bonuses.target_month', $filters['target_month']);
        }

        return $query->orderBy('bonuses.target_month', 'desc')->paginate(20); // context7-ignore
    }

    /**
     * Resolve monthly sold listings for the given agent.
     */
    private function getMonthSales(int $agentId, string $month): Collection
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        return Ilan::query()
            ->where('kullanici_id', $agentId)
            ->where('yayin_durumu', 'Satıldı')
            ->whereBetween('updated_at', [$start, $end])
            ->get();
    }

    /**
     * Fallback monthly target projection when explicit target is unavailable.
     */
    private function calculateDefaultTarget(int $agentId): float
    {
        $start = now()->subMonths(3)->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $sales = $this->getSalesForPeriod($agentId, $start, $end);
        $total = (float) $sales->sum('fiyat');

        if ($total <= 0) {
            return 0.0;
        }

        return round($total / 3, 2);
    }

    /**
     * Determine best matching bonus tier from normalized tier definitions.
     */
    private function determineBonusTier(float $achievementPercentage, mixed $bonusTiers): array
    {
        $normalized = [];

        if (is_array($bonusTiers)) {
            foreach ($bonusTiers as $key => $value) {
                if (is_array($value) && isset($value['threshold'], $value['rate'])) {
                    $normalized[] = [
                        'tier' => (string) ($value['tier'] ?? $key),
                        'threshold' => (float) $value['threshold'],
                        'rate' => (float) $value['rate'],
                    ];
                    continue;
                }

                if (is_numeric($value)) {
                    $normalized[] = [
                        'tier' => (string) $key,
                        'threshold' => $this->defaultThresholdForTier((string) $key),
                        'rate' => (float) $value,
                    ];
                }
            }
        }

        usort($normalized, static fn (array $a, array $b): int => $b['threshold'] <=> $a['threshold']);

        foreach ($normalized as $tier) {
            if ($achievementPercentage >= $tier['threshold']) {
                return [
                    'tier' => $tier['tier'],
                    'rate' => $tier['rate'],
                ];
            }
        }

        return ['tier' => 'none', 'rate' => 0.0];
    }

    /**
     * Default tier thresholds when only rate map is provided.
     */
    private function defaultThresholdForTier(string $tier): float
    {
        return match (strtolower($tier)) {
            'gold' => 120.0,
            'silver' => 100.0,
            'bronze' => 80.0,
            default => 100.0,
        };
    }
}

