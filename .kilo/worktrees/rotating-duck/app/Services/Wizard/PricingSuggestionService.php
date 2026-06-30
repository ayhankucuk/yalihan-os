<?php

namespace App\Services\Wizard;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PricingSuggestionService
{
    /**
     * Generate a pricing suggestion based on wizard form state (pre-save).
     *
     * Uses comparable listings in the same category/location to suggest a price range.
     * This is a lightweight service — does NOT call AI providers.
     *
     * @param array $formState Wizard form data
     * @return array Pricing suggestion contract
     */
    public function suggest(array $formState): array
    {
        $categoryId = (int) ($formState['ana_kategori_id'] ?? $formState['kategori_id'] ?? 0);
        $ilId = (int) ($formState['il_id'] ?? 0);
        $ilceId = (int) ($formState['ilce_id'] ?? 0);
        $alanM2 = (float) ($formState['alan_m2'] ?? 0);
        $paraBirimi = $formState['para_birimi'] ?? 'TRY';

        if (!$categoryId || !$ilId) {
            return [
                'basarili' => false,
                'hata_mesaji' => 'Fiyat önerisi için kategori ve il bilgisi gerekli.',
            ];
        }

        // Query comparable listings
        $query = DB::table('ilanlar')
            ->where('ana_kategori_id', $categoryId)
            ->where('il_id', $ilId)
            ->where('yayin_durumu', 'yayinda')
            ->where('fiyat', '>', 0)
            ->whereNull('deleted_at');

        if ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        // If we have area, filter by similar sizes (±30%)
        if ($alanM2 > 0) {
            $minArea = $alanM2 * 0.7;
            $maxArea = $alanM2 * 1.3;
            $query->whereBetween('alan_m2', [$minArea, $maxArea]);
        }

        $comparables = $query
            ->select('fiyat', 'alan_m2')
            ->limit(100)
            ->get();

        if ($comparables->isEmpty()) {
            // Fallback: try broader query without ilce
            $comparables = DB::table('ilanlar')
                ->where('ana_kategori_id', $categoryId)
                ->where('il_id', $ilId)
                ->where('yayin_durumu', 'yayinda')
                ->where('fiyat', '>', 0)
                ->whereNull('deleted_at')
                ->select('fiyat', 'alan_m2')
                ->limit(100)
                ->get();
        }

        if ($comparables->isEmpty()) {
            return [
                'basarili' => false,
                'hata_mesaji' => 'Bu bölge ve kategoride karşılaştırılabilir ilan bulunamadı.',
            ];
        }

        $prices = $comparables->pluck('fiyat')->map(fn ($p) => (float) $p)->sort()->values();
        $count = $prices->count();

        $minPrice = $prices->first();
        $maxPrice = $prices->last();
        $medianPrice = $count % 2 === 0
            ? ($prices[$count / 2 - 1] + $prices[$count / 2]) / 2
            : $prices[intdiv($count, 2)];

        // If we have area, calculate m2 price and suggest based on that
        $suggestedPrice = $medianPrice;
        $reason = $count . ' benzer ilana göre medyan fiyat önerisi';

        if ($alanM2 > 0) {
            $m2Prices = $comparables
                ->filter(fn ($c) => $c->alan_m2 > 0)
                ->map(fn ($c) => (float) $c->fiyat / (float) $c->alan_m2);

            if ($m2Prices->isNotEmpty()) {
                $medianM2Price = $m2Prices->sort()->values();
                $m2Count = $medianM2Price->count();
                $m2Median = $m2Count % 2 === 0
                    ? ($medianM2Price[$m2Count / 2 - 1] + $medianM2Price[$m2Count / 2]) / 2
                    : $medianM2Price[intdiv($m2Count, 2)];

                $suggestedPrice = round($m2Median * $alanM2, -2); // Round to nearest 100
                $reason = $count . ' benzer ilana göre m² başına medyan fiyat × ' . $alanM2 . ' m²';
            }
        }

        // Confidence based on sample size
        $confidence = match (true) {
            $count >= 30 => 0.85,
            $count >= 15 => 0.70,
            $count >= 5 => 0.55,
            default => 0.35,
        };

        return [
            'basarili' => true,
            'suggested_price' => (int) $suggestedPrice,
            'min_price' => (int) $minPrice,
            'max_price' => (int) $maxPrice,
            'median_price' => (int) $medianPrice,
            'confidence' => $confidence,
            'comparable_count' => $count,
            'para_birimi' => $paraBirimi,
            'reason' => $reason,
        ];
    }
}
