<?php

namespace App\Services\AI;

use App\Models\Ilan;
use App\Models\IlanPriceHistory;
use Illuminate\Support\Collection;

class CortexPriceForecastService
{
    /**
     * Analyze price history and forecast future trend.
     * Returns a signal: BUY, WAIT, or SELL (for sellers).
     *
     * @param Ilan $ilan
     * @return array
     */
    public function forecast(Ilan $ilan): array
    {
        // Get history sorted by date
        $history = $ilan->fiyatGecmisi()->orderBy('created_at', 'asc')->get(); // context7-ignore

        if ($history->isEmpty()) {
            return [
                'signal' => 'NEUTRAL',
                'confidence' => 0,
                'message' => 'Yeterli veri yok.',
                'trend_percent' => 0
            ];
        }

        // Calculate Trend
        $firstPrice = $history->first()->old_price > 0 ? $history->first()->old_price : $history->first()->new_price;
        $currentPrice = $ilan->fiyat;

        $trendPercent = 0;
        if ($firstPrice > 0) {
            $trendPercent = (($currentPrice - $firstPrice) / $firstPrice) * 100;
        }

        // Seasonality Logic (Bodrum Effect)
        // Prices tend to rise in Summer (May-Aug)
        $currentMonth = now()->month;
        $isSeason = ($currentMonth >= 5 && $currentMonth <= 8);
        $isPreSeason = ($currentMonth >= 3 && $currentMonth <= 4);

        $signal = 'NEUTRAL';
        $reason = '';

        if ($trendPercent < -10) {
            $signal = 'STRONG BUY'; // Price dropped significantly
            $reason = "Fiyat zirveden %" . abs(round($trendPercent, 1)) . " düştü. Alım fırsatı.";
        } elseif ($isPreSeason) {
            $signal = 'BUY'; // Buy before season starts
            $reason = "Sezon öncesi fiyatlar artabilir. Şimdi almak mantıklı.";
        } elseif ($isSeason && $trendPercent > 10) {
            $signal = 'WAIT'; // Prices are high in season
            $reason = "Sezon içi fiyatlar yüksek. Sonbaharı beklemek avantajlı olabilir.";
        } elseif ($trendPercent > 20) {
             $signal = 'WAIT'; // Overheated
             $reason = "Fiyat kısa sürede çok arttı. Düzeltme beklenebilir.";
        } else {
            $signal = 'BUY'; // General uptrend in a healthy market
            $reason = "İstikrarlı piyasa koşulları.";
        }

        return [
            'signal' => $signal,
            'confidence' => 85, // Mock confidence based on heuristic strength
            'current_price' => $currentPrice,
            'historical_low' => $history->min('new_price'),
            'historical_high' => $history->max('new_price'),
            'trend_percent' => round($trendPercent, 2),
            'reason' => $reason,
            'history_count' => $history->count()
        ];
    }
}
