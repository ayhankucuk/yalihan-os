<?php

namespace App\Services\Cortex;

use App\Models\Ilan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Enums\IlanDurumu;

/**
 * Cortex ROI Engine
 * 
 * Calculates Return on Investment (ROI) and Payback Period (Amortisman Süresi)
 * for real estate assets.
 * 
 * Context7: Financial Intelligence Layer
 */
class CortexROIEngine
{
    /**
     * Calculate ROI for a specific listing
     * 
     * @param Ilan $ilan
     * @return array
     */
    public function calculateROI(Ilan $ilan): array
    {
        $price = (float) $ilan->fiyat;
        if ($price <= 0) {
            return $this->emptyResult();
        }

        // Get regional rental average
        $monthlyRent = $this->getRegionalRentalAverage($ilan);
        
        if ($monthlyRent <= 0) {
            return $this->emptyResult();
        }

        $annualRent = $monthlyRent * 12;
        
        // Payback Period (Years) = Price / Annual Rent
        $paybackPeriod = $price / $annualRent;
        
        // ROI Score (0-100)
        // Standard payback in Turkey is ~20 years. 
        // < 15 years = Excellent (90-100)
        // 15-20 years = Good (70-90)
        // 20-25 years = Average (50-70)
        // > 25 years = Poor (< 50)
        $roiScore = $this->calculateScoreFromPayback($paybackPeriod);

        $result = [
            'roi_score' => round($roiScore, 2),
            'payback_period_years' => round($paybackPeriod, 2),
            'monthly_rental_income' => round($monthlyRent, 2),
            'annual_rental_income' => round($annualRent, 2),
            'is_high_yield' => $paybackPeriod < 18,
            'market_comparison' => $this->getMarketComparison($paybackPeriod),
        ];

        // Cache the results in the model
        $ilan->update([
            'roi_skoru' => $result['roi_score'],
            'amortisman_suresi_yil' => $result['payback_period_years'],
            'bolge_kira_ortalamasi' => $result['monthly_rental_income'],
        ]);

        return $result;
    }

    /**
     * Get regional rental average based on location and category
     */
    private function getRegionalRentalAverage(Ilan $ilan): float
    {
        // If the listing already has rental info, use it as a primary source
        if ($ilan->kira_bilgisi > 0) {
            return (float) $ilan->kira_bilgisi;
        }

        // Otherwise, calculate from similar rental listings in the same area
        $average = DB::table('ilanlar')
            ->where('il_id', $ilan->il_id)
            ->where('ilce_id', $ilan->ilce_id)
            ->where('ana_kategori_id', $ilan->ana_kategori_id)
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereIn('yayin_tipi_id', [2, 8, 10]) // Kiralık types
            ->avg('fiyat');

        if ($average) {
            return (float) $average;
        }

        // Fallback: Use a rough estimate based on price (e.g., 0.4% of price per month)
        return $ilan->fiyat * 0.004;
    }

    /**
     * Calculate ROI score from payback period
     */
    private function calculateScoreFromPayback(float $years): float
    {
        if ($years <= 10) return 100;
        if ($years >= 40) return 10;

        // Linear interpolation between 10 and 40 years
        // 10 years -> 100
        // 40 years -> 10
        return 100 - (($years - 10) * (90 / 30));
    }

    /**
     * Get market comparison text
     */
    private function getMarketComparison(float $years): string
    {
        if ($years < 15) return "Bölge ortalamasından %25 daha hızlı amortisman.";
        if ($years < 18) return "Bölge ortalamasından %15 daha hızlı amortisman.";
        if ($years < 22) return "Bölge ortalamasında seyrediyor.";
        return "Uzun vadeli yatırım profiline uygun.";
    }

    /**
     * Empty result structure
     */
    private function emptyResult(): array
    {
        return [
            'roi_score' => 0,
            'payback_period_years' => 0,
            'monthly_rental_income' => 0,
            'annual_rental_income' => 0,
            'is_high_yield' => false,
            'market_comparison' => 'Veri yetersiz.',
        ];
    }
}
