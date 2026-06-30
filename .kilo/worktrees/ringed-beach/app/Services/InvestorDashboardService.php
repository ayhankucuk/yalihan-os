<?php

namespace App\Services;

use App\Models\Ilan;
use Illuminate\Support\Facades\Cache;

/**
 * InvestorDashboardService
 *
 * Aggregates all investor KPIs for the main dashboard.
 * Cache-backed to protect DB performance.
 */
class InvestorDashboardService
{
    public function __construct(
        private readonly InvestorAnalyticsService $investorService,
        private readonly CountryComparisonService $countryService
    ) {}

    /**
     * Get full investor dashboard data.
     */
    public function getDashboardKPIs(): array
    {
        // Cache standard: KPI / Dashboard: 5–15 dk (using 10 mins = 600s)
        return Cache::remember('investor_dashboard_kpis', 600, function () {
            // Enterprise Hardening: Limit full property loading
            $ilanlar = Ilan::where('aktiflik_durumu', true)
                ->select(['id', 'baslik', 'purchase_price', 'fiyat', 'para_birimi'])
                ->get();

            $totalPortfolioValue = $ilanlar->sum(function ($ilan) {
                return $ilan->purchase_price > 0 ? $ilan->purchase_price : $ilan->fiyat;
            });

            $roiSum = 0;
            $roiCount = 0;
            $mostProfitable = null;
            $bestYield = -1;

            foreach ($ilanlar as $ilan) {
                $roiData = $this->investorService->calculateROI($ilan->id);
                if ($roiData['roi'] > 0) {
                    $roiSum += $roiData['roi'];
                    $roiCount++;
                }

                $yieldData = $this->investorService->calculateYield($ilan->id);
                if ($yieldData['net_yield'] > $bestYield) {
                    $bestYield = $yieldData['net_yield'];
                    $mostProfitable = [
                        'id'    => $ilan->id,
                        'baslik'=> $ilan->baslik,
                        'yield' => $yieldData['net_yield'],
                    ];
                }
            }

            return [
                'total_portfolio_value' => round($totalPortfolioValue, 2),
                'average_roi'           => $roiCount > 0 ? round($roiSum / $roiCount, 2) : 0,
                'most_profitable_property' => $mostProfitable,
                'country_comparison'    => $this->countryService->compareCountries(),
                'updated_at'            => now()->toDateTimeString(),
            ];
        });
    }
}
