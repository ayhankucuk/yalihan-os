<?php

namespace App\Services\Finance;

use App\Enums\Finance\PaymentStatus;
use App\Models\Ilan;
use App\Models\Finance\Commission;
use App\Models\Finance\Bonus;
use App\Models\LedgerBalance;
use App\Services\Finance\CommissionCalculator;
use App\Services\Finance\TransactionService;
use App\Services\Finance\BonusCalculator;
use App\Services\FinancialLedgerService;
use App\Application\Shared\Services\TenantContextResolver;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\DB;

/**
 * YalihanTreasury - Canonical Finance Authority
 * 🛡️ SAB §12: Finance Domain Hardening
 * - ✅ TenantContextResolver DI (SAB §12)
 */
class YalihanTreasury
{
    public function __construct(
        private readonly CommissionCalculator $commissionCalc,
        private readonly TransactionService $transactionService,
        private readonly BonusCalculator $bonusCalc,
        private readonly FinancialLedgerService $ledgerService,
        private readonly TenantContextResolver $tenantResolver
    ) {}

    /**
     * Get aggregate financial metrics for admin dashboard.
     */
    public function getAdminDashboardMetrics(): array
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $monthlySales = $this->bonusCalc->getSalesForPeriod(0, $monthStart, $monthEnd)->sum('fiyat');
        $settings = $this->commissionCalc->getFinancialSettings($tenantId);
        $monthlyRevenue = ($monthlySales * ($settings->default_commission_rate ?? 3.00)) / 100;

        return [
            'monthly_revenue' => $monthlyRevenue,
            'pending_commissions' => $this->commissionCalc->getPendingCommissionsTotal(),
            'approved_unpaid' => $this->commissionCalc->getApprovedUnpaidTotal(),
            'unverified_count' => $this->transactionService->getUnverifiedCount(),
            'unpaid_bonuses' => $this->bonusCalc->getTotalUnpaidBonuses(0),
        ];
    }

    /**
     * Get financial metrics for an agent's wallet.
     */
    public function getAgentWalletMetrics(int $agentId): array
    {
        $thisMonth = now()->format('Y-m');
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $monthlySales = $this->bonusCalc->getSalesForPeriod($agentId, $monthStart, $monthEnd);
        $thisMonthSales = $monthlySales->sum('fiyat');
        $monthlyTarget = $this->bonusCalc->getMonthlyTarget($agentId, $thisMonth);

        return [
            'monthly_earnings' => $this->commissionCalc->getMonthlyEarnings($agentId, $monthStart, $monthEnd),
            'pending_commissions' => $this->commissionCalc->getCommissionsList($agentId, PaymentStatus::PENDING)->count(),
            'total_earnings' => $this->commissionCalc->getMonthlyEarnings($agentId, '1970-01-01', now()->toDateString()),
            'this_month_sales' => $thisMonthSales,
            'sales_count' => $monthlySales->count(),
            'monthly_target' => $monthlyTarget,
            'achievement_percentage' => $monthlyTarget > 0 ? ($thisMonthSales / $monthlyTarget) * 100 : 0,
            'projected_bonus' => $monthlyTarget > 0 ? $this->bonusCalc->simulateBonus($monthlyTarget, $thisMonthSales) : null,
            'unpaid_bonuses' => $this->bonusCalc->getTotalUnpaidBonuses($agentId),
        ];
    }

    /**
     * ATOMIC PERSISTENCE: Request Commission Approval
     */
    public function requestCommissionApproval(int $id): bool
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return DB::transaction(function() use ($id, $tenantId) {
            $commission = Commission::where('tenant_id', $tenantId)->find($id);
            if (!$commission || $commission->payment_state !== PaymentStatus::PENDING) {
                return false;
            }

            return $commission->update([
                'payment_state' => PaymentStatus::APPROVED,
                'updated_at' => now()
            ]);
        });
    }

    /**
     * ATOMIC PERSISTENCE: Request Commission Payment
     */
    public function requestCommissionPayment(int $id, ?string $invoiceNumber = null): bool
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return DB::transaction(function() use ($id, $invoiceNumber, $tenantId) {
            $commission = Commission::where('tenant_id', $tenantId)->find($id);
            if (!$commission || $commission->payment_state === PaymentStatus::PAID) {
                return false;
            }

            $projection = $this->commissionCalc->getPayoutProjection($invoiceNumber);
            $commission->update($projection);

            // 2. Canonical Ledger Integration (Double-Entry)
            $this->ledgerService->recordDoubleEntry(
                $tenantId,
                $commission->agent_id,
                1, // System Account
                (float)$commission->danisman_tutari,
                'TRY',
                "Commission Payment: #{$commission->id}"
            );

            return true;
        });
    }

    /**
     * ATOMIC PERSISTENCE: Request Bonus Payment
     */
    public function requestBonusPayment(int $id): bool
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return DB::transaction(function() use ($id, $tenantId) {
            $bonus = Bonus::where('tenant_id', $tenantId)->find($id);
            if (!$bonus || $bonus->is_paid) {
                return false;
            }

            $projection = $this->bonusCalc->getPayoutProjection();
            $bonus->update($projection);

            // 2. Canonical Ledger Integration (Double-Entry)
            $this->ledgerService->recordDoubleEntry(
                $tenantId,
                $bonus->agent_id,
                1, // System Account
                (float)$bonus->prim_tutari,
                'TRY',
                "Bonus Payment: #{$bonus->id}"
            );

            return true;
        });
    }

    /**
     * ATOMIC LEDGER UPDATE (DEPRECATED: Use FinancialLedgerService)
     * @deprecated 24.2.0
     */
    protected function updateLedgerBalance(int $accountId, float $amount, string $currency): void
    {
        // 🛡️ REDIRECTING TO NEW CANONICAL LEDGER
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $this->ledgerService->recordDoubleEntry($tenantId, $accountId, 1, $amount, $currency, 'Legacy Ledger Update');
    }

    /**
     * BATCH PROCESS: Calculate Monthly Bonues
     */
    public function batchCalculateMonthlyBonuses(string $month): array
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return DB::transaction(function() use ($month, $tenantId) {
            [$year, $monthNum] = explode('-', $month);
            $startDate = "{$year}-{$monthNum}-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $agentsWithSales = Ilan::where('tenant_id', $tenantId)
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->where('yayin_durumu', IlanDurumu::SATILDI)
                ->distinct()
                ->pluck('danisman_id'); // 🛡️ Using correct Context7 field

            $results = ['total' => $agentsWithSales->count(), 'success' => 0, 'failed' => 0];

            foreach ($agentsWithSales as $agentId) {
                if (!$agentId) continue;

                $exists = Bonus::where('agent_id', $agentId)
                    ->where('target_month', $month)
                    ->exists();

                if ($exists) continue;

                $projection = $this->bonusCalc->calculateBonusProjection((int)$agentId, $month);
                Bonus::create($projection);

                $results['success']++;
            }

            return $results;
        });
    }

    /**
     * Thin adapter for controller compatibility.
     */
    public function getPaginatedTransactions(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->transactionService->getPaginatedTransactions($filters);
    }

    /**
     * Thin adapter for controller compatibility.
     */
    public function recordTransaction(array $data): array
    {
        return $this->transactionService->recordPayment($data);
    }

    /**
     * Thin adapter for controller compatibility.
     */
    public function requestPaymentVerification(int $transactionId): bool
    {
        return $this->transactionService->verifyPayment($transactionId);
    }

    /**
     * Thin adapter for controller compatibility.
     */
    public function getPaginatedBonuses(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->bonusCalc->getPaginatedBonuses($filters);
    }

    /**
     * Thin adapter for controller compatibility.
     */
    public function getCommissionsList(int $agentId, mixed $state = null): \Illuminate\Support\Collection
    {
        if ($state instanceof PaymentStatus || $state === null) {
            return $this->commissionCalc->getCommissionsList($agentId, $state);
        }

        return $this->commissionCalc->getCommissionsList($agentId, PaymentStatus::tryFrom((string) $state));
    }

    /**
     * Thin adapter for controller compatibility.
     */
    public function getAgentBonuses(int $agentId): \Illuminate\Support\Collection
    {
        return $this->bonusCalc->getAgentBonuses($agentId);
    }

    /**
     * Thin adapter for controller compatibility.
     */
    public function simulateCommission(float $salePrice, ?float $commissionRate = null): array
    {
        return $this->commissionCalc->simulateCommission($salePrice, $commissionRate);
    }

    /**
     * Thin adapter for controller compatibility.
     */
    public function simulateBonus(float $monthlyTarget, float $achievedAmount): array
    {
        return $this->bonusCalc->simulateBonus($monthlyTarget, $achievedAmount);
    }

    /**
     * Lightweight performance projection used by wallet UI.
     */
    public function calculateAgentPerformance(int $agentId, string $month): array
    {
        $projection = $this->bonusCalc->calculateBonusProjection($agentId, $month);

        return [
            'agent_id' => $agentId,
            'target_month' => $month,
            'monthly_target' => $projection['monthly_target'] ?? 0,
            'achieved_amount' => $projection['achieved_amount'] ?? 0,
            'achievement_percentage' => $projection['achievement_percentage'] ?? 0,
            'bonus_tier' => $projection['bonus_tier'] ?? 'none',
            'projected_bonus' => $projection['bonus_amount'] ?? 0,
        ];
    }
}
