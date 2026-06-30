<?php

namespace App\Services\Market;

use App\Models\Ilan;
use App\Models\IlanKategorisi;
use App\Models\Mahalle;
use App\Models\Ilce;
use App\Models\Il;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Market Intelligence Service
 * 
 * Piyasa DNA'sını analiz eden merkezi servis.
 * TKGM verilerinden ortalama birim fiyat, trend analizi ve ROI hesaplama yaparak
 * İlan Sihirbazı'nda doğrulanmış ilan değeri hesaplanmasını sağlar.
 * 
 * Context7 Sealed Standards (31 Ocak 2025):
 * - Tüm coğrafi alanlar: il_id, ilce_id, mahalle_id
 * - Koordinatlar: lat, lng (enlem/boylam yasak)
 * - Kategori: kategori_id
 */
class MarketIntelligenceService
{
    /**
     * Belirli bir konum ve kategori için pazar değeri hesapla
     * 
     * @param array $locationData [
     *     'il_id' => int,
     *     'ilce_id' => int|null,
     *     'mahalle_id' => int|null,
     *     'kategori_id' => int,
     *     'lat' => float,
     *     'lng' => float,
     *     'alan_m2' => float (opsiyonel, Bosch GLM verisi)
     * ]
     * @param int $kategoriId Kategori ID
     * @return array ['ortalama', 'min', 'max', 'trend', 'roi', 'toplam_veri_sayisi']
     */
    public function calculateMarketValue(array $locationData, int $kategoriId = null): array
    {
        // Kategori belirtilmediyse location data'dan al
        if ($kategoriId === null) {
            $kategoriId = $locationData['kategori_id'] ?? null;
        }

        if (!$kategoriId) {
            return $this->defaultMarketValue();
        }

        // Cache key: Smart caching (mahalle düzeyinden başla, üstüne çık)
        $cacheKey = $this->getCacheKey($locationData, $kategoriId);
        
        // 24 saatlik cache (piyasa günde bir kez güncellenir)
        return Cache::remember($cacheKey, 86400, function () use ($locationData, $kategoriId) {
            return $this->analyzeMarketData($locationData, $kategoriId);
        });
    }

    /**
     * Piyasa verisi analizi (Core Intelligence)
     * 
     * Algoritma:
     * 1. Mahalle > İlçe > İl sırasında veri ara
     * 2. En az 5 veri noktası iste (güvenilirlik)
     * 3. Outlier'ları (±2σ) kaldır
     * 4. Trend analiz et (son 3 ay vs 3-6 ay öncesi)
     * 5. ROI hesapla
     */
    private function analyzeMarketData(array $locationData, int $kategoriId): array
    {
        $ilId = $locationData['il_id'];
        $ilceId = $locationData['ilce_id'] ?? null;
        $mahalleId = $locationData['mahalle_id'] ?? null;

        // Adım 1: Mahalle düzeyinde veri ara
        if ($mahalleId) {
            $data = $this->fetchMarketDataByLocation(
                il_id: $ilId,
                ilce_id: $ilceId,
                mahalle_id: $mahalleId,
                kategori_id: $kategoriId
            );

            if ($data['toplam_sorgu_sayisi'] >= 5) {
                return $data; // Yeterli veri
            }
        }

        // Adım 2: İlçe düzeyine çık
        if ($ilceId) {
            $data = $this->fetchMarketDataByLocation(
                il_id: $ilId,
                ilce_id: $ilceId,
                mahalle_id: null,
                kategori_id: $kategoriId
            );

            if ($data['toplam_sorgu_sayisi'] >= 5) {
                return $data;
            }
        }

        // Adım 3: İl düzeyine çık (fallback)
        return $this->fetchMarketDataByLocation(
            il_id: $ilId,
            ilce_id: null,
            mahalle_id: null,
            kategori_id: $kategoriId
        );
    }

    /**
     * Veritabanından pazar verisi çek
     * 
     * TKGM analiz tablosundan (market_trends) veri çeker.
     * Eğer tablo boşsa, satılan ilanlardan realtime hesaplar.
     */
    private function fetchMarketDataByLocation(
        int $ilId,
        ?int $ilceId = null,
        ?int $mahalleId = null,
        int $kategoriId = null
    ): array {
        // Öncelik 1: market_trends tablosundan çek (mühürlü veri)
        $query = DB::table('market_trends')
            ->where('il_id', $ilId)
            ->where('kategori_id', $kategoriId);

        if ($mahalleId) {
            $query->where('mahalle_id', $mahalleId);
        } elseif ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        $trend = $query->latest('analiz_tarihi')->first();

        if ($trend) {
            return [
                'ortalama_m2_fiyat' => (float) $trend->ortalama_m2_fiyat,
                'min_m2_fiyat' => (float) $trend->min_m2_fiyat,
                'max_m2_fiyat' => (float) $trend->max_m2_fiyat,
                'trend_yonu' => $trend->trend_yonu,
                'aylik_degisim_yuzde' => (float) $trend->aylik_degisim_yuzde,
                'roi_yuzde' => (float) $trend->roi_yuzde,
                'ortalama_satis_suresi_gun' => (float) $trend->ortalama_satis_suresi_gun,
                'toplam_sorgu_sayisi' => $trend->toplam_sorgu_sayisi,
                'sealed_poi_data' => $trend->sealed_poi_data ? json_decode($trend->sealed_poi_data, true) : null,
                'kaynak' => 'market_trends',
            ];
        }

        // Fallback: Satılan ilanlardan realtime hesapla
        return $this->calculateFromSoldListings($ilId, $ilceId, $mahalleId, $kategoriId);
    }

    /**
     * Satılan ilanlardan realtime pazar değeri hesapla
     * 
     * Kullanım: market_trends tablosu boşsa, son 90 günün satılan verilerini analiz et
     */
    private function calculateFromSoldListings(
        int $ilId,
        ?int $ilceId = null,
        ?int $mahalleId = null,
        int $kategoriId = null
    ): array {
        // Sorgu: Son 90 günde satılan ilanlar (yayin_durumu = sold)
        $query = Ilan::query()
            ->whereHas('konum', function ($q) use ($ilId, $ilceId, $mahalleId) {
                $q->where('il_id', $ilId);
                if ($ilceId) {
                    $q->where('ilce_id', $ilceId);
                }
                if ($mahalleId) {
                    $q->where('mahalle_id', $mahalleId);
                }
            });

        if ($kategoriId) {
            $query->where('kategori_id', $kategoriId);
        }

        // Son 90 günde satılan ilanlar
        $soldListings = $query
            ->where('yayin_durumu', 'satildi')
            ->where('updated_at', '>=', now()->subDays(90))
            ->select('satis_fiyati', 'alan_m2', 'updated_at')
            ->get();

        if ($soldListings->isEmpty()) {
            return $this->defaultMarketValue();
        }

        // Birim fiyat hesapla (TL/m²)
        $unitPrices = $soldListings
            ->filter(fn($i) => $i->alan_m2 > 0)
            ->map(fn($i) => $i->satis_fiyati / $i->alan_m2)
            ->sort()
            ->values();

        if ($unitPrices->isEmpty()) {
            return $this->defaultMarketValue();
        }

        // Outlier kaldırma (±2 standart sapma)
        $cleanedPrices = $this->removeOutliers($unitPrices->toArray());

        // İstatistik hesapla
        $average = collect($cleanedPrices)->average();
        $min = collect($cleanedPrices)->min();
        $max = collect($cleanedPrices)->max();
        $stdDev = $this->calculateStandardDeviation($cleanedPrices);

        // Trend analizi (son 30 gün vs 30-90 gün)
        $trend = $this->analyzeTrend($soldListings, $kategoriId, $ilId, $ilceId, $mahalleId);

        return [
            'ortalama_m2_fiyat' => round($average, 2),
            'min_m2_fiyat' => round($min, 2),
            'max_m2_fiyat' => round($max, 2),
            'std_sapma_m2' => round($stdDev, 2),
            'trend_yonu' => $trend['yonu'],
            'aylik_degisim_yuzde' => round($trend['aylık_değişim'], 2),
            'roi_yuzde' => round($trend['roi'], 2),
            'ortalama_satis_suresi_gun' => round($this->calculateAverageSalesTime($soldListings), 2),
            'toplam_sorgu_sayisi' => count($soldListings),
            'kaynak' => 'realtime_calculation',
        ];
    }

    /**
     * Trend analizi: Fiyat eğilimi ve ROI hesapla
     */
    private function analyzeTrend($soldListings, int $kategoriId, int $ilId, ?int $ilceId, ?int $mahalleId): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        $ninetyDaysAgo = now()->subDays(90);

        // Son 30 gün
        $recent = $soldListings->filter(fn($i) => $i->updated_at >= $thirtyDaysAgo);
        $recentAvg = $recent->isNotEmpty()
            ? $recent->filter(fn($i) => $i->alan_m2 > 0)->average(fn($i) => $i->satis_fiyati / $i->alan_m2)
            : 0;

        // 30-90 gün arası
        $older = $soldListings->filter(fn($i) => $i->updated_at >= $ninetyDaysAgo && $i->updated_at < $thirtyDaysAgo);
        $olderAvg = $older->isNotEmpty()
            ? $older->filter(fn($i) => $i->alan_m2 > 0)->average(fn($i) => $i->satis_fiyati / $i->alan_m2)
            : $recentAvg;

        // % Değişim
        $percentChange = $olderAvg > 0 ? (($recentAvg - $olderAvg) / $olderAvg) * 100 : 0;

        // Trend yönü
        $trend = match (true) {
            $percentChange > 2 => 'yukselme',
            $percentChange < -2 => 'dusuş',
            default => 'stabil',
        };

        // ROI tahmini (30 günde fiyat değişimi + yatırım getirisi)
        $roi = max(0, $percentChange);

        return [
            'yonu' => $trend,
            'aylık_değişim' => round($percentChange, 2),
            'roi' => $roi,
        ];
    }

    /**
     * Outlier kaldırma (±2 standart sapma)
     */
    private function removeOutliers(array $values): array
    {
        if (count($values) < 3) {
            return $values;
        }

        $mean = array_sum($values) / count($values);
        $stdDev = $this->calculateStandardDeviation($values);

        if ($stdDev == 0) {
            return $values;
        }

        return array_filter($values, function ($value) use ($mean, $stdDev) {
            $zScore = abs(($value - $mean) / $stdDev);
            return $zScore <= 2;
        });
    }

    /**
     * Standart sapma hesaplama
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($values));
    }

    /**
     * Ortalama satış süresi (gün)
     */
    private function calculateAverageSalesTime($soldListings): float
    {
        if ($soldListings->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($soldListings as $listing) {
            if ($listing->created_at && $listing->updated_at) {
                $totalDays += $listing->created_at->diffInDays($listing->updated_at);
                $count++;
            }
        }

        return $count > 0 ? $totalDays / $count : 0;
    }

    /**
     * Bosch GLM ölçüm verisi ile doğrulanmış ilan değeri
     * 
     * Alan (m²) × Ortalama Birim Fiyat = Doğrulanmış Değer
     * 
     * @param float $alanM2 Bosch GLM 50-27 CG'den gelen kesin ölçüm
     * @param float $ortalamaM2Fiyat calculateMarketValue() sonucu
     * @return array ['degerli_ilan_fiyati', 'min_tahmin', 'max_tahmin']
     */
    public function calculateVerifiedListingValue(float $alanM2, float $ortalamaM2Fiyat): array
    {
        if ($alanM2 <= 0 || $ortalamaM2Fiyat <= 0) {
            return [
                'degerli_ilan_fiyati' => 0,
                'min_tahmin' => 0,
                'max_tahmin' => 0,
            ];
        }

        $verified = $alanM2 * $ortalamaM2Fiyat;

        return [
            'degerli_ilan_fiyati' => round($verified, 0), // TL
            'min_tahmin' => round($verified * 0.85, 0),   // -15% konservatif
            'max_tahmin' => round($verified * 1.15, 0),   // +15% optimist
        ];
    }

    /**
     * Cache key builder
     */
    private function getCacheKey(array $locationData, int $kategoriId): string
    {
        $mahalleId = $locationData['mahalle_id'] ?? 0;
        $ilceId = $locationData['ilce_id'] ?? 0;
        $ilId = $locationData['il_id'] ?? 0;

        return "market:intelligence:il{$ilId}:ilce{$ilceId}:mahalle{$mahalleId}:kategori{$kategoriId}";
    }

    /**
     * Default (boş) pazar değeri
     */
    private function defaultMarketValue(): array
    {
        return [
            'ortalama_m2_fiyat' => 0,
            'min_m2_fiyat' => 0,
            'max_m2_fiyat' => 0,
            'trend_yonu' => 'stabil',
            'aylik_degisim_yuzde' => 0,
            'roi_yuzde' => 0,
            'ortalama_satis_suresi_gun' => 0,
            'toplam_sorgu_sayisi' => 0,
            'kaynak' => 'default',
        ];
    }
}
