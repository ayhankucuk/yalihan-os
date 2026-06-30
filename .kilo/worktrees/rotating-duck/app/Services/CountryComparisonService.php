<?php

namespace App\Services;

use App\DTO\CountryInvestmentReportDTO;
use App\Models\CountryFinancialRule;
use App\Models\Ilan;
use App\Models\PropertyGrowthProjection;
use Illuminate\Support\Facades\Cache;

/**
 * CountryComparisonService
 *
 * Compares investment metrics across TR, GR, UK.
 */
class CountryComparisonService
{
    public function __construct(
        private readonly InvestorAnalyticsService $investorService,
        private readonly CountryFinancialService $countryFinancialService
    ) {}

    /**
     * Compare all target countries.
     */
    public function compareCountries(): array
    {
        return Cache::remember('country_investment_comparison', 3600, function () {
            $report = [];
            $countries = ['TR', 'GR', 'UK'];

            foreach ($countries as $code) {
                $report[$code] = $this->generateCountryReport($code)->toArray();
            }

            return $report;
        });
    }

    /**
     * Generate report for a specific country.
     */
    public function generateCountryReport(string $countryCode): CountryInvestmentReportDTO
    {
        $rule = $this->countryFinancialService->getRule($countryCode);

        // Calculate average metrics for props in this country
        // 1. Average growth rate from projections
        $avgGrowth = PropertyGrowthProjection::whereHas('ilan', function ($q) use ($countryCode) {
            $q->where('country_code', $countryCode); // Assuming country_code exists or resolved
        })->avg('yearly_growth_rate') ?? 0.05;

        // 2. Average Net Yield (sampled for performance)
        $props = Ilan::where('rental_enabled', true)->take(10)->get();
        $yieldSum = 0;
        $count    = 0;
        $topProps = [];

        foreach ($props as $prop) {
            $yield = $this->investorService->calculateYield($prop->id);
            $yieldSum += $yield['net_yield'] ?? 0;
            $count++;

            $topProps[] = [
                'id'    => $prop->id,
                'yield' => $yield['net_yield'] ?? 0,
                'roi'   => $this->investorService->calculateROI($prop->id)['roi'] ?? 0,
            ];
        }

        usort($topProps, fn($a, $b) => $b['yield'] <=> $a['yield']);

        return new CountryInvestmentReportDTO(
            $countryCode,
            $rule->country_name,
            $count > 0 ? round($yieldSum / $count, 2) : 0,
            round($avgGrowth * 100, 2),
            round($rule->tax_rate * 100, 2),
            round($rule->rental_commission_rate * 100, 2),
            $rule->default_currency,
            array_slice($topProps, 0, 5)
        );
    }
}
