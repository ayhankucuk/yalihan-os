<?php

namespace App\Services\Intelligence;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Competitor Map Service
 * Context7: Pazar Hakimiyeti (Competitor Mapping) için rakip analizi servisi
 *
 * Verilen mülk etrafında rakip analizi yaparak fiyat önerisi sunar.
 */
class CompetitorMapService
{
    /**
     * Verilen mülk etrafında rakip analizi
     *
     * @param Ilan $ilan
     * @param float $radiusKm Yarıçap (km) - Şu an sadece il/ilce bazlı, gelecekte harita entegrasyonu için
     * @return array
     */
    public function analyzeCompetitors(Ilan $ilan, float $radiusKm = 2.0): array
    {
        $cacheKey = "competitors:ilan:{$ilan->id}:radius:{$radiusKm}";

        return Cache::remember($cacheKey, 60 * 60 * 24, function () use ($ilan, $radiusKm) {
            try {
                $competitors = $this->findCompetitors($ilan, $radiusKm);

                $analysis = [
                    'our_listing' => [
                        'id' => $ilan->id,
                        'title' => $ilan->baslik,
                        'price' => (float) $ilan->fiyat,
                        'location' => ($ilan->il ? $ilan->il->adi : '') . ($ilan->ilce ? ', ' . $ilan->ilce->adi : ''),
                        'category' => $ilan->anaKategori ? $ilan->anaKategori->adi : null,
                    ],
                    'top_competitors' => [],
                    'price_gap' => 0,
                    'price_gap_percent' => 0,
                    'recommendation' => '',
                    'confidence' => 0,
                    'total_competitors' => $competitors->count(),
                ];

                if ($competitors->isEmpty()) {
                    $analysis['recommendation'] = '🟡 Yeterli rakip bulunamadı. Daha geniş alan analizi önerilir.';
                    $analysis['confidence'] = 0;
                    return $analysis;
                }

                // En yakın 3 rakip (fiyat ve kategori uyumuna göre)
                $topCompetitors = $competitors
                    ->sortBy(function ($competitor) use ($ilan) {
                        // Fiyat farkına göre sırala (en yakın fiyatlar önce)
                        $priceDiff = abs($competitor->fiyat - $ilan->fiyat);
                        return $priceDiff;
                    })
                    ->take(3);

                foreach ($topCompetitors as $competitor) {
                    $priceGap = $ilan->fiyat - $competitor->fiyat;
                    $priceGapPercent = $competitor->fiyat > 0
                        ? round(($priceGap / $competitor->fiyat) * 100, 2)
                        : 0;

                    $analysis['top_competitors'][] = [
                        'id' => $competitor->id,
                        'title' => $competitor->baslik,
                        'price' => (float) $competitor->fiyat,
                        'price_gap' => round($priceGap, 2),
                        'price_gap_percent' => $priceGapPercent,
                        'location' => ($competitor->il ? $competitor->il->adi : '') . ($competitor->ilce ? ', ' . $competitor->ilce->adi : ''),
                        'url' => route('admin.ilanlar.show', $competitor->id),
                    ];
                }

                // Medyan fiyat hesaplama
                $competitorPrices = $topCompetitors->pluck('fiyat')->filter()->toArray();
                if (empty($competitorPrices)) {
                    $analysis['recommendation'] = '🟡 Fiyat karşılaştırması yapılamadı.';
                    return $analysis;
                }

                $medianPrice = $this->calculateMedian($competitorPrices);
                $ourPrice = (float) $ilan->fiyat;

                $priceGap = $ourPrice - $medianPrice;
                $priceGapPercent = $medianPrice > 0
                    ? round(($priceGap / $medianPrice) * 100, 2)
                    : 0;

                $analysis['price_gap'] = round($priceGap, 2);
                $analysis['price_gap_percent'] = $priceGapPercent;

                // Fiyat önerisi algoritması
                $analysis['recommendation'] = $this->generateRecommendation($ourPrice, $medianPrice, $priceGapPercent);
                $analysis['suggested_price'] = $this->calculateSuggestedPrice($ourPrice, $medianPrice, $priceGapPercent);
                $analysis['suggested_discount'] = $ourPrice - ($analysis['suggested_price'] ?? $ourPrice);

                // Güvenilirlik skoru (rakip sayısına göre)
                $analysis['confidence'] = min($topCompetitors->count() * 33, 100);

                return $analysis;
            } catch (\Exception $e) {
                Log::error('Competitor analysis error', [
                    'ilan_id' => $ilan->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'our_listing' => [
                        'id' => $ilan->id,
                        'title' => $ilan->baslik,
                        'price' => (float) $ilan->fiyat,
                    ],
                    'top_competitors' => [],
                    'price_gap' => 0,
                    'price_gap_percent' => 0,
                    'recommendation' => 'Hata: ' . $e->getMessage(),
                    'confidence' => 0,
                    'total_competitors' => 0,
                ];
            }
        });
    }

    /**
     * Rakip ilanları bul
     *
     * @param Ilan $ilan
     * @param float $radiusKm
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findCompetitors(Ilan $ilan, float $radiusKm): \Illuminate\Database\Eloquent\Collection
    {
        // Aynı kategori, aynı il/ilce, benzer fiyat aralığı
        $query = Ilan::where('ana_kategori_id', $ilan->ana_kategori_id)
            ->where('id', '!=', $ilan->id)
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->where('il_id', $ilan->il_id);

        // İlçe bazlı filtreleme (eğer ilçe varsa)
        if ($ilan->ilce_id) {
            $query->where('ilce_id', $ilan->ilce_id);
        }

        // Fiyat aralığı: %70 - %130 arası
        $minPrice = $ilan->fiyat * 0.7;
        $maxPrice = $ilan->fiyat * 1.3;

        $query->whereBetween('fiyat', [$minPrice, $maxPrice]);

        return $query->with(['il', 'ilce', 'anaKategori'])
            ->orderBy('fiyat', 'asc') // context7-ignore
            ->get();
    }

    /**
     * Medyan hesapla
     *
     * @param array $values
     * @return float
     */
    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 == 1) {
            return (float) $values[$middle];
        }

        return (float) (($values[$middle - 1] + $values[$middle]) / 2);
    }

    /**
     * Fiyat önerisi oluştur
     *
     * @param float $ourPrice
     * @param float $medianPrice
     * @param float $priceGapPercent
     * @return string
     */
    private function generateRecommendation(float $ourPrice, float $medianPrice, float $priceGapPercent): string
    {
        if ($priceGapPercent > 10) {
            $suggestedDiscount = round(($ourPrice - $medianPrice) * 0.8);
            $suggestedPrice = $ourPrice - $suggestedDiscount;
            return sprintf(
                "🔴 Piyasaya göre %%%.2f pahalısınız. ₺%s indirimle (₺%s) satılabilir.",
                abs($priceGapPercent),
                number_format($suggestedDiscount, 0, ',', '.'),
                number_format($suggestedPrice, 0, ',', '.')
            );
        } elseif ($priceGapPercent > 5) {
            $suggestedDiscount = round(($ourPrice - $medianPrice) * 0.6);
            $suggestedPrice = $ourPrice - $suggestedDiscount;
            return sprintf(
                "🟠 Piyasaya göre %%%.2f pahalısınız. ₺%s indirimle (₺%s) satış hızlanabilir.",
                abs($priceGapPercent),
                number_format($suggestedDiscount, 0, ',', '.'),
                number_format($suggestedPrice, 0, ',', '.')
            );
        } elseif ($priceGapPercent > 0) {
            $suggestedDiscount = round(($ourPrice - $medianPrice) * 0.3);
            $suggestedPrice = $ourPrice - $suggestedDiscount;
            return sprintf(
                "🟡 Piyasaya göre %%%.2f pahalısınız. Küçük indirim (₺%s) ile satış hızlanabilir.",
                $priceGapPercent,
                number_format($suggestedDiscount, 0, ',', '.')
            );
        } elseif ($priceGapPercent < -5) {
            return "🟢 Rekabetçi fiyatlandırma. Fiyat artırımı düşünülebilir.";
        } else {
            return "🟢 Rekabetçi fiyatlandırma. İyi satış potansiyeli.";
        }
    }

    /**
     * Önerilen fiyat hesapla
     *
     * @param float $ourPrice
     * @param float $medianPrice
     * @param float $priceGapPercent
     * @return float|null
     */
    private function calculateSuggestedPrice(float $ourPrice, float $medianPrice, float $priceGapPercent): ?float
    {
        if ($priceGapPercent <= 0) {
            return null; // Fiyat artırımı önerilmez
        }

        if ($priceGapPercent > 10) {
            // %80 indirim öner
            return round($ourPrice - (($ourPrice - $medianPrice) * 0.8), 2);
        } elseif ($priceGapPercent > 5) {
            // %60 indirim öner
            return round($ourPrice - (($ourPrice - $medianPrice) * 0.6), 2);
        } else {
            // %30 indirim öner
            return round($ourPrice - (($ourPrice - $medianPrice) * 0.3), 2);
        }
    }

    /**
     * Cache'i temizle
     *
     * @param int $ilanId
     * @return void
     */
    public function clearCache(int $ilanId): void
    {
        Cache::forget("competitors:ilan:{$ilanId}:radius:2.0");
    }
}

