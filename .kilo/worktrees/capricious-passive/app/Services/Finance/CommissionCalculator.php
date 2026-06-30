<?php

namespace App\Services\Finance;

use App\Models\Ilan;
use App\Models\User;
use App\Models\Finance\FinancialSetting;
use App\Models\Finance\Commission;
use App\Enums\Finance\PaymentStatus;
use App\Application\Shared\Services\TenantContextResolver;
use Illuminate\Support\Facades\DB;

/**
 * Commission Calculator Service
 *
 * Handles commission calculation and distribution logic
 * 🛡️ SAB §12: Finance Domain Hardening
 * - ✅ TenantContextResolver DI (SAB §12)
 */
class CommissionCalculator
{
    public function __construct(
        private readonly TenantContextResolver $tenantResolver
    ) {}

    /**
     * Calculate and PROJECT commission for a sale
     * (Pure Brain / No side-effects)
     *
     * @param Ilan $ilan
     * @param float|null $commissionRate
     * @return array Commission projection
     */
    public function calculateCommissionProjection(Ilan $ilan, ?float $commissionRate = null): array
    {
        $settings = $this->getFinancialSettings();
        $rate = $commissionRate ?? ($settings->default_commission_rate ?? 3.00);
        
        if ($settings) {
            $rate = max($settings->min_commission_rate, min($rate, $settings->max_commission_rate));
        }
        
        $salePrice = $ilan->fiyat ?? 0;

        if ($salePrice <= 0) {
            throw new \InvalidArgumentException('Satış fiyatı belirtilmemiş veya geçersiz');
        }

        $totalCommission = ($salePrice * $rate) / 100;
        $officeShare = $settings->office_share ?? 50.00;
        $agentShare = $settings->agent_share ?? 50.00;

        $officeAmount = ($totalCommission * $officeShare) / 100;
        $agentAmount = ($totalCommission * $agentShare) / 100;
        $agent = $ilan->ilanSahibi ?? $ilan->user;

        if (!$agent) {
            throw new \RuntimeException('İlan sahibi (danışman) bulunamadı');
        }

        $payoutDate = now()->addDays($settings->payment_delay_days ?? 30)->toDateString();

        return [
            'ilan_id' => $ilan->id,
            'agent_id' => $agent->id,
            'sale_price' => round($salePrice, 2),
            'commission_rate' => round($rate, 2),
            'total_commission' => round($totalCommission, 2),
            'office_share_percentage' => round($officeShare, 2),
            'agent_share_percentage' => round($agentShare, 2),
            'ofis_tutari' => round($officeAmount, 2),
            'danisman_tutari' => round($agentAmount, 2),
            'payment_state' => PaymentStatus::PENDING->value,
            'payout_date' => $payoutDate,
            'calculated_by' => auth()->id(),
        ];
    }

    /**
     * Mark commission as paid (PROJECTION DATA ONLY)
     */
    public function getPayoutProjection(?string $invoiceNumber = null): array
    {
        $updateData = [
            'payment_state' => PaymentStatus::PAID->value,
            'payout_date' => now()->toDateString(),
            'paid_by' => auth()->id(),
            'updated_at' => now()
        ];

        if ($invoiceNumber) {
            $updateData['invoice_number'] = $invoiceNumber;
        }

        return $updateData;
    }

    /**
     * Simulate commission (for frontend calculator)
     */
    public function simulateCommission(float $salePrice, ?float $commissionRate = null): array
    {
        $settings = $this->getFinancialSettings();

        $rate = $commissionRate ?? ($settings->default_commission_rate ?? 3.00);
        if ($settings) {
            $rate = max($settings->min_commission_rate, min($rate, $settings->max_commission_rate));
        }

        $totalCommission = ($salePrice * $rate) / 100;
        $officeAmount = ($totalCommission * ($settings->office_share ?? 50.00)) / 100;
        $agentAmount = ($totalCommission * ($settings->agent_share ?? 50.00)) / 100;

        return [
            'sale_price' => round($salePrice, 2),
            'commission_rate' => round($rate, 2),
            'total_commission' => round($totalCommission, 2),
            'ofis_tutari' => round($officeAmount, 2),
            'danisman_tutari' => round($agentAmount, 2),
            'office_share_percentage' => round($settings->office_share ?? 50.00, 2),
            'agent_share_percentage' => round($settings->agent_share ?? 50.00, 2),
        ];
    }

    /**
     * Get monthly earnings for an agent.
     */
    public function getMonthlyEarnings(int $agentId, string $start, string $end): float
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return (float) Commission::where('tenant_id', $tenantId)
            ->where('agent_id', $agentId)
            ->where('payment_state', PaymentStatus::PAID)
            ->whereBetween('payout_date', [$start, $end])
            ->sum('danisman_tutari');
    }

    /**
     * Get commissions list for agent.
     */
    public function getCommissionsList(int $agentId, ?PaymentStatus $state = null): \Illuminate\Support\Collection
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $query = Commission::with('ilan:id,baslik')
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $agentId);

        if ($state) {
            $query->where('payment_state', $state);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get current financial settings for the tenant.
     */
    public function getFinancialSettings(?int $tenantId = null): ?FinancialSetting
    {
        $id = $tenantId ?? $this->tenantResolver->resolve()->tenantId;

        return FinancialSetting::where('tenant_id', $id)->first()
            ?? FinancialSetting::where('tenant_id', 0)->first(); // Global default fallback
    }

    /**
     * Get total pending commissions for admin dashboard.
     */
    public function getPendingCommissionsTotal(): float
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return (float) Commission::where('tenant_id', $tenantId)
            ->where('payment_state', PaymentStatus::PENDING)
            ->sum('total_commission');
    }

    /**
     * Get approved but unpaid commissions total.
     */
    public function getApprovedUnpaidTotal(): float
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return (float) Commission::where('tenant_id', $tenantId)
            ->where('payment_state', PaymentStatus::APPROVED)
            ->sum('total_commission');
    }

    /**
     * Get paginated commissions for admin.
     */
    public function getPaginatedCommissions(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $query = Commission::with(['ilan:id,baslik', 'agent:id,name'])
            ->where('tenant_id', $tenantId);

        if (isset($filters['payment_state'])) {
            $query->where('payment_state', $filters['payment_state']);
        }
        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Calculates estimated commission for a given total sales amount.
     */
    public function calculateEstimatedCommission(float $totalSales): float
    {
        $settings = $this->getFinancialSettings();
        if (!$settings) {
            return 0.0;
        }
        // Calculation: (Sales * Rate * Share) / 10000
        return ($totalSales * ($settings->default_commission_rate ?? 0) * ($settings->agent_share ?? 0)) / 10000;
    }
}
