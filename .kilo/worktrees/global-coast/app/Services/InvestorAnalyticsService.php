<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\Ilan;
use App\Models\PropertyGrowthProjection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * InvestorAnalyticsService
 *
 * Strategic intelligence for investors. Calculates ROI, Yield, and Cash Flow.
 *
 * Rules:
 * - ROI = (Net Annual Income / Purchase Price) * 100
 * - Yield = (Annual Rent / Purchase Price) * 100
 * - All calculations are derived from FinancialLedger (actuals) or deterministic projection.
 * - Cache:backed for dashboard performance.
 */
class InvestorAnalyticsService
{
    private const CACHE_TTL_ROI   = 3600; // 1 hour
    private const CACHE_TTL_YIELD = 3600;
    private const CACHE_PREFIX    = 'investor.perf';

    public function __construct(
        private readonly FxService $fxService,
        private readonly CountryFinancialService $countryService
    ) {}

    /**
     * ROI (Return on Investment)
     */
    public function calculateROI(int $propertyId): array
    {
        $cacheKey = self::CACHE_PREFIX . ".roi.{$propertyId}." . app()->getLocale();

        return Cache::remember($cacheKey, self::CACHE_TTL_ROI, function () use ($propertyId) {
            $ilan = Ilan::findOrFail($propertyId);

            if (!$ilan->purchase_price || $ilan->purchase_price <= 0) {
                return ['roi' => 0, 'analiz_durumu' => 'missing_purchase_price'];
            }

            $netAnnualIncome = $this->calculateNetAnnualIncome($propertyId);
            $roi = ($netAnnualIncome / $ilan->purchase_price) * 100;

            return [
                'roi'               => round($roi, 2),
                'net_annual_income' => $netAnnualIncome,
                'purchase_price'    => $ilan->purchase_price,
                'currency'          => $ilan->para_birimi ?? 'TRY',
            ];
        });
    }

    /**
     * Rental Yield (Gross & Net)
     */
    public function calculateYield(int $propertyId): array
    {
        $cacheKey = self::CACHE_PREFIX . ".yield.{$propertyId}." . app()->getLocale();

        return Cache::remember($cacheKey, self::CACHE_TTL_YIELD, function () use ($propertyId) {
            $ilan = Ilan::findOrFail($propertyId);

        if (!$ilan->purchase_price || $ilan->purchase_price <= 0) {
            return ['gross_yield' => 0, 'net_yield' => 0];
        }

        // Gross Yield = Annual Rent / Purchase Price
        $annualRent = $this->calculateAnnualRent($propertyId);
        $grossYield = ($annualRent / $ilan->purchase_price) * 100;

        // Net Yield = Net Annual Income / Purchase Price
        $netAnnualIncome = $this->calculateNetAnnualIncome($propertyId);
        $netYield = ($netAnnualIncome / $ilan->purchase_price) * 100;

            return [
                'gross_yield' => round($grossYield, 2),
                'net_yield'   => round($netYield, 2),
                'annual_rent' => $annualRent,
            ];
        });
    }

    /**
     * Clear property analytics cache.
     * Required after booking/financial change.
     */
    public function purgeCache(int $propertyId): void
    {
        $locale = app()->getLocale();
        Cache::forget(self::CACHE_PREFIX . ".roi.{$propertyId}.{$locale}");
        Cache::forget(self::CACHE_PREFIX . ".yield.{$propertyId}.{$locale}");
        Cache::forget('investor_dashboard_kpis'); // Invalidate global dashboard cache
    }

    /**
     * Capital Gain (Future Value Simulation)
     */
    public function simulateCapitalGain(int $propertyId, ?int $years = null): array
    {
        $ilan       = Ilan::findOrFail($propertyId);
        $projection = PropertyGrowthProjection::where('property_id', $propertyId)
            ->where('aktiflik_durumu', true)
            ->first();

        $price      = $ilan->purchase_price ?? $ilan->fiyat;
        $growthRate = $projection->yearly_growth_rate ?? 0.05; // Default 5%
        $years      = $years ?? ($projection->projection_years ?? 5);

        // Future Value = Price * (1 + rate)^years
        $futureValue = $price * pow((1 + $growthRate), $years);

        return [
            'current_value' => $price,
            'future_value'  => round($futureValue, 2),
            'total_gain'    => round($futureValue - $price, 2),
            'growth_rate'   => $growthRate,
            'years'         => $years,
        ];
    }

    /**
     * Monthly Cash Flow (12-month projection)
     */
    public function getCashFlowProjection(int $propertyId): array
    {
        $ilan = Ilan::findOrFail($propertyId);
        $monthlyRent = $this->calculateAnnualRent($propertyId) / 12;
        $monthlyExp  = ($ilan->operating_expenses_annual ?? 0) / 12;

        $projection = [];
        for ($i = 1; $i <= 12; $i++) {
            $projection[] = [
                'month'    => $i,
                'income'   => round($monthlyRent, 2),
                'expense'  => round($monthlyExp, 2),
                'net'      => round($monthlyRent - $monthlyExp, 2),
            ];
        }

        return $projection;
    }

    /**
     * PRIVATE: Calculate Annual Rent from Ledger
     */
    private function calculateAnnualRent(int $propertyId): float
    {
        // Get actual 'kira' transactions from last 365 days
        $actualRent = FinancialTransaction::where('property_id', $propertyId)
            ->where('islem_tipi', 'kira')
            ->where('islem_durumu', 'settled')
            ->where('created_at', '>=', now()->subYear())
            ->sum('base_amount');

        // If no actual data yet, use base price projection as estimate
        if ($actualRent <= 0) {
            $ilan = Ilan::find($propertyId);
            // Rough estimate: Price * occupancy (e.g. 50%)
            return ($ilan->fiyat ?? 0) * 0.5 * 30 * 12;
        }

        return (float) $actualRent;
    }

    /**
     * PRIVATE: Calculate Net Annual Income (Actuals)
     */
    private function calculateNetAnnualIncome(int $propertyId): float
    {
        $ilan = Ilan::find($propertyId);

        // Income (Rent)
        $actualRent = $this->calculateAnnualRent($propertyId);

        // Deductions (Commission + Tax + Advisory + Expenses)
        $deductions = FinancialTransaction::where('property_id', $propertyId)
            ->whereIn('islem_tipi', ['komisyon', 'iade'])
            ->where('islem_durumu', 'settled')
            ->where('created_at', '>=', now()->subYear())
            ->sum('base_amount');

        // Static operating expenses
        $expenses = $ilan->operating_expenses_annual ?? 0;

        return round($actualRent - $deductions - $expenses, 2);
    }
}
