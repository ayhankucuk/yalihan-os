<?php

namespace App\Services\Finance;

use App\Models\Ilan;
use App\Models\LedgerEntry;
use App\Models\LedgerAccount;
use App\Enums\CategoryType;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * 💰 Listing Finance Service
 * 🛡️ SAB §12: Finance Domain Hardening
 */
class ListingFinanceService
{
    private \App\Services\Finance\PricingService $pricingService;

    public function __construct(
        private LogService $logService,
        \App\Services\Finance\PricingService $pricingService
    ) {
        $this->pricingService = $pricingService;
    }

    /**
     * 🎯 Calculate all financial metrics for a listing.
     */
    public function calculateFinancials(Ilan $ilan): array
    {
        $timer = $this->logService->startTimer('listing_finance_calc');

        try {
            $metrics = [];

            if ($this->isCommercialProperty($ilan)) {
                $metrics = $this->calculateCommercialMetrics($ilan);
            } elseif ($this->isRentalProperty($ilan)) {
                $metrics = $this->calculateRentalMetrics($ilan);
            }

            if (!empty($metrics)) {
                // 🛡️ Explicit mutation via Eloquent
                $ilan->update([
                    'roi_expected_return' => $metrics['annual_income'] ?? 0,
                    'roi_payback_period' => $metrics['roi_years'] ?? 0,
                    'roi_gross_yield' => $metrics['gross_yield'] ?? 0,
                    'roi_net_yield' => $metrics['net_yield'] ?? 0,
                    'roi_calculation_date' => now(),
                ]);
            }

            $this->logService->stopTimer($timer, [
                'ilan_id' => $ilan->id,
                'metrics' => $metrics,
            ]);

            return $metrics;
        } catch (\Exception $e) {
            $this->logService->stopTimer($timer, [
                'error' => $e->getMessage(),
                'ilan_id' => $ilan->id,
            ]);
            throw $e;
        }
    }

    private function calculateCommercialMetrics(Ilan $ilan): array
    {
        $monthlyRent = $ilan->aylik_kira ?? 0;
        $price = $ilan->fiyat ?? 0;

        if (!$monthlyRent || !$price) {
            return [];
        }

        $annualIncome = $monthlyRent * 12;
        $opexRate = $this->pricingService->getFinanceConfig('commercial_opex_rate', 0.20);
        $operationalCosts = $annualIncome * $opexRate;
        $netIncome = $annualIncome - $operationalCosts;

        $roiYears = $price / $annualIncome;
        $grossYield = ($annualIncome / $price) * 100;
        $netYield = ($netIncome / $price) * 100;

        return [
            'property_type' => 'commercial',
            'monthly_rent' => $monthlyRent,
            'annual_income' => $annualIncome,
            'operational_costs' => $operationalCosts,
            'net_income' => $netIncome,
            'roi_years' => round($roiYears, 2),
            'gross_yield' => round($grossYield, 2),
            'net_yield' => round($netYield, 2),
            'breakeven_months' => round($roiYears * 12, 0),
        ];
    }

    private function calculateRentalMetrics(Ilan $ilan): array
    {
        $monthlyRent = $ilan->aylik_kira ?? 0;
        $price = $ilan->fiyat ?? 0;

        if (!$monthlyRent || !$price) {
            return [];
        }

        // Seasonal check
        if ($ilan->gunluk_fiyat_yaz || $ilan->gunluk_fiyat_kis) {
            return $this->calculateSeasonalRentalMetrics($ilan, $price);
        }

        $annualIncome = $monthlyRent * 12;
        $maintenanceRate = $this->pricingService->getFinanceConfig('residential_maintenance_rate', 0.25);
        $maintenanceCosts = $annualIncome * $maintenanceRate;
        $netIncome = $annualIncome - $maintenanceCosts;

        $roiYears = $price / $annualIncome;
        $grossYield = ($annualIncome / $price) * 100;
        $netYield = ($netIncome / $price) * 100;

        return [
            'property_type' => 'residential_rental',
            'monthly_rent' => $monthlyRent,
            'annual_income' => $annualIncome,
            'maintenance_costs' => $maintenanceCosts,
            'net_income' => $netIncome,
            'roi_years' => round($roiYears, 2),
            'gross_yield' => round($grossYield, 2),
            'net_yield' => round($netYield, 2),
        ];
    }

    private function calculateSeasonalRentalMetrics(Ilan $ilan, float $price): array
    {
        $summerNights = $this->pricingService->getSeasonalConfig('summer_days', 90);
        $winterNights = $this->pricingService->getSeasonalConfig('winter_days', 90);
        $midseasonNights = $this->pricingService->getSeasonalConfig('midseason_days', 92);

        $summerPrice = $ilan->gunluk_fiyat_yaz ?? 0;
        $winterPrice = $ilan->gunluk_fiyat_kis ?? 0;
        $midseasonPrice = $ilan->gunluk_fiyat_ara ?? 0;

        $occupancyRate = $this->pricingService->getFinanceConfig('occupancy_rate', 0.70);

        $summerIncome = $summerNights * $summerPrice * $occupancyRate;
        $winterIncome = $winterNights * $winterPrice * $occupancyRate;
        $midseasonIncome = $midseasonNights * $midseasonPrice * $occupancyRate;

        $annualIncome = $summerIncome + $winterIncome + $midseasonIncome;
        $managementRate = $this->pricingService->getFinanceConfig('seasonal_management_rate', 0.30);
        $managementCosts = $annualIncome * $managementRate;
        $netIncome = $annualIncome - $managementCosts;

        $roiYears = $price / $annualIncome;
        $grossYield = ($annualIncome / $price) * 100;
        $netYield = ($netIncome / $price) * 100;

        return [
            'property_type' => 'seasonal_rental',
            'annual_income' => round($annualIncome, 2),
            'net_income' => round($netIncome, 2),
            'roi_years' => round($roiYears, 2),
            'gross_yield' => round($grossYield, 2),
            'net_yield' => round($netYield, 2),
        ];
    }

    /**
     * 💰 Calculate Actual Net Profit using Ledger entries.
     */
    public function calculateActualNetProfit(Ilan $ilan, int $months = 12): array
    {
        $startDate = now()->subMonths($months);

        // 🛡️ Using LedgerEntry model instead of raw DB
        $income = LedgerEntry::whereHas('account', function($q) {
                $q->where('type', 'revenue'); // context7-ignore: external ledger schema
            })
            ->where('reference_type', Ilan::class)
            ->where('reference_id', $ilan->id)
            ->where('entry_date', '>=', $startDate)
            ->sum('credit_amount');

        $expenses = LedgerEntry::whereHas('account', function($q) {
                $q->where('type', 'expense'); // context7-ignore: external ledger schema
            })
            ->where('reference_type', Ilan::class)
            ->where('reference_id', $ilan->id)
            ->where('entry_date', '>=', $startDate)
            ->sum('debit_amount');

        $netIncome = $income - $expenses;
        $roiYears = $ilan->fiyat > 0 ? ($ilan->fiyat / max($netIncome ?: 1, 1)) : 0;

        return [
            'period_months' => $months,
            'total_income' => round($income, 2),
            'total_expenses' => round($expenses, 2),
            'net_profit' => round($netIncome, 2),
            'roi_years_actual' => round($roiYears, 2),
        ];
    }

    private function isCommercialProperty(Ilan $ilan): bool
    {
        // 🛡️ Using CategoryType enum instead of magic numbers
        return (int) $ilan->ana_kategori_id === CategoryType::COMMERCIAL->value;
    }

    private function isRentalProperty(Ilan $ilan): bool
    {
        // Assume yayin_tipi_id 2,3,4 are rentals (Should use PublicationType Enum in future)
        return in_array((int) $ilan->yayin_tipi_id, [2, 3, 4]);
    }
}
