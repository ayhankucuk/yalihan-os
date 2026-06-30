<?php

namespace App\Services\Intelligence;

use App\Models\TkgmQuery;
use App\Models\TkgmLearningPattern;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * 🧠 TKGM LEARNING ENGINE SERVICE
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 *
 * Her TKGM sorgusundan öğrenir, pattern'leri tespit eder.
 * Fiyat tahmini, pazar analizi, yatırım önerileri sunar.
 *
 * @author Yalihan AI Team
 * @version 1.0.0
 * @date 2025-12-05
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 */
class TKGMLearningService
{
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 🎯 CONSTANTS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private const MIN_SAMPLE_COUNT = 5; // Pattern oluşturmak için min veri sayısı
    private const CACHE_TTL = 3600; // 1 saat cache
    private const CONFIDENCE_THRESHOLD = 70.0; // %70 güven eşiği

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 📝 CORE METHOD: LEARN FROM QUERY
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * TKGM sorgusunu kaydet ve öğren
     *
     * @param array $tkgmData TKGM API'den gelen veri
     * @param array $context Bağlamsal bilgiler (ilan_id, user_id, vs.)
     * @return TkgmQuery
     */
    public function learn(array $tkgmData, array $context = []): TkgmQuery
    {
        try {
            // 1. Sorguyu kaydet
            $query = $this->storeQuery($tkgmData, $context);

            // 2. Pattern'leri güncelle (async olabilir)
            $this->updatePatternsAsync($query);

            return $query;
        } catch (\Exception $e) {
            Log::error('TKGM Learning failed', [
                'error' => $e->getMessage(),
                'tkgm_data' => $tkgmData,
            ]);

            throw $e;
        }
    }

    /**
     * TKGM sorgusunu veritabanına kaydet
     */
    private function storeQuery(array $tkgmData, array $context): TkgmQuery
    {
        return TkgmQuery::create([
            // Ada/Parsel
            'ada' => $tkgmData['ada'] ?? null,
            'parsel' => $tkgmData['parsel'] ?? null,

            // Lokasyon
            'il_id' => $context['il_id'] ?? null,
            'ilce_id' => $context['ilce_id'] ?? null,
            'mahalle_id' => $context['mahalle_id'] ?? null,
            'enlem' => $tkgmData['enlem'] ?? null,
            'boylam' => $tkgmData['boylam'] ?? null,

            // TKGM Verileri
            'alan_m2' => $tkgmData['yuzolcumu'] ?? $tkgmData['alan_m2'] ?? null,
            'kaks' => $tkgmData['kaks'] ?? null,
            'taks' => $tkgmData['taks'] ?? null,
            'imar_durumu' => $tkgmData['imar_durumu'] ?? $tkgmData['imar_durumu'] ?? null,
            'nitelik' => $tkgmData['nitelik'] ?? null,
            'gabari' => $tkgmData['gabari'] ?? null,

            // İlan & Satış (opsiyonel)
            'ilan_id' => $context['ilan_id'] ?? null,
            'satis_fiyati' => $context['satis_fiyati'] ?? null,
            'satis_tarihi' => $context['satis_tarihi'] ?? null,
            'satis_suresi_gun' => $context['satis_suresi_gun'] ?? null,

            // Meta
            'query_source' => $context['source'] ?? 'wizard',
            'user_id' => $context['user_id'] ?? auth()->id(),
            'queried_at' => now(),
            'tkgm_raw_data' => $tkgmData,
            'aktiflik_durumu' => 1,
        ]);
    }

    /**
     * Pattern'leri asenkron güncelle
     */
    private function updatePatternsAsync(TkgmQuery $query): void
    {
        // Lokasyon bazlı pattern güncelleme
        if ($query->il_id && $query->ilce_id) {
            $this->updateLocationPatterns($query);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 📊 PATTERN ANALYSIS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Belirli lokasyondaki pattern'leri güncelle
     */
    private function updateLocationPatterns(TkgmQuery $query): void
    {
        // Cache key
        $cacheKey = "tkgm_pattern_update_{$query->il_id}_{$query->ilce_id}";

        // Son 1 saat içinde güncellendiyse skip
        if (Cache::has($cacheKey)) {
            return;
        }

        // Pattern'leri güncelle
        $this->updatePriceKaksPattern($query->il_id, $query->ilce_id);
        $this->updateVelocityPattern($query->il_id, $query->ilce_id);

        // Cache'e ekle (1 saat)
        Cache::put($cacheKey, true, self::CACHE_TTL);
    }

    /**
     * KAKS-Fiyat korelasyon pattern'ini güncelle
     */
    private function updatePriceKaksPattern(int $ilId, int $ilceId): void
    {
        // Satılmış ilanları analiz et
        $data = TkgmQuery::where('il_id', $ilId)
            ->where('ilce_id', $ilceId)
            ->sold()
            ->active() // context7-ignore
            ->whereNotNull('kaks')
            ->whereNotNull('alan_m2')
            ->select([
                DB::raw('ROUND(kaks, 1) as kaks_rounded'),
                DB::raw('COUNT(*) as sample_count'),
                DB::raw('AVG(satis_fiyati / alan_m2) as avg_unit_price'),
                DB::raw('MIN(satis_fiyati / alan_m2) as min_unit_price'),
                DB::raw('MAX(satis_fiyati / alan_m2) as max_unit_price'),
                DB::raw('AVG(satis_suresi_gun) as avg_days_to_sell'),
            ])
            ->groupBy('kaks_rounded')
            ->having('sample_count', '>=', self::MIN_SAMPLE_COUNT)
            ->get();

        if ($data->isEmpty()) {
            return; // Yeterli veri yok
        }

        // Pattern data hazırla
        $patternData = [
            'kaks_values' => $data->pluck('kaks_rounded')->toArray(),
            'avg_prices' => $data->pluck('avg_unit_price')->map(fn($p) => round($p, 2))->toArray(),
            'min_prices' => $data->pluck('min_unit_price')->map(fn($p) => round($p, 2))->toArray(),
            'max_prices' => $data->pluck('max_unit_price')->map(fn($p) => round($p, 2))->toArray(),
            'sample_counts' => $data->pluck('sample_count')->toArray(),
            'velocity_days' => $data->pluck('avg_days_to_sell')->map(fn($d) => round($d))->toArray(),
        ];

        // Güven seviyesi hesapla (toplam sample count'a göre)
        $totalSamples = $data->sum('sample_count');
        $confidenceLevel = min(100, ($totalSamples / 50) * 100); // 50 sample = %100 güven

        // Pattern'i kaydet/güncelle
        TkgmLearningPattern::updateOrCreate(
            [
                'pattern_type' => TkgmLearningPattern::TYPE_PRICE_KAKS,
                'il_id' => $ilId,
                'ilce_id' => $ilceId,
            ],
            [
                'pattern_data' => $patternData,
                'sample_count' => $totalSamples,
                'confidence_level' => round($confidenceLevel, 2),
                'last_calculated_at' => now(),
                'last_updated_at' => now(),
                'aktiflik_durumu' => 1,
            ]
        );
    }

    /**
     * Satış hızı pattern'ini güncelle
     */
    private function updateVelocityPattern(int $ilId, int $ilceId): void
    {
        $data = TkgmQuery::where('il_id', $ilId)
            ->where('ilce_id', $ilceId)
            ->sold()
            ->active() // context7-ignore
            ->whereNotNull('satis_suresi_gun')
            ->whereNotNull('kaks')
            ->whereNotNull('imar_durumu')
            ->select([
                DB::raw('ROUND(kaks, 1) as kaks_rounded'),
                'imar_durumu',
                DB::raw('COUNT(*) as sample_count'),
                DB::raw('AVG(satis_suresi_gun) as avg_days'),
                DB::raw('MIN(satis_suresi_gun) as min_days'),
                DB::raw('MAX(satis_suresi_gun) as max_days'),
            ])
            ->groupBy('kaks_rounded', 'imar_durumu')
            ->having('sample_count', '>=', 3) // Velocity için 3 veri yeterli
            ->get();

        if ($data->isEmpty()) {
            return;
        }

        $patternData = [
            'segments' => $data->map(fn($item) => [
                'kaks' => $item->kaks_rounded,
                'imar_durumu' => $item->imar_durumu,
                'avg_days' => round($item->avg_days),
                'min_days' => $item->min_days,
                'max_days' => $item->max_days,
                'sample_count' => $item->sample_count,
            ])->toArray(),
            'fastest_segment' => $data->sortBy('avg_days')->first()?->toArray(),
            'slowest_segment' => $data->sortByDesc('avg_days')->first()?->toArray(),
        ];

        $totalSamples = $data->sum('sample_count');
        $confidenceLevel = min(100, ($totalSamples / 30) * 100); // 30 sample = %100

        TkgmLearningPattern::updateOrCreate(
            [
                'pattern_type' => TkgmLearningPattern::TYPE_VELOCITY,
                'il_id' => $ilId,
                'ilce_id' => $ilceId,
            ],
            [
                'pattern_data' => $patternData,
                'sample_count' => $totalSamples,
                'confidence_level' => round($confidenceLevel, 2),
                'last_calculated_at' => now(),
                'last_updated_at' => now(),
                'aktiflik_durumu' => 1,
            ]
        );
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 💰 PRICE PREDICTION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Fiyat tahmini yap
     *
     * @param array $tkgmData TKGM verisi
     * @return array ['min', 'max', 'recommended', 'confidence', 'based_on']
     */
    public function predictPrice(array $tkgmData): array
    {
        $ilId = $tkgmData['il_id'] ?? null;
        $ilceId = $tkgmData['ilce_id'] ?? null;
        $alanM2 = $tkgmData['alan_m2'] ?? $tkgmData['yuzolcumu'] ?? null;
        $kaks = $tkgmData['kaks'] ?? null;

        if (!$ilId || !$ilceId || !$alanM2) {
            return $this->getDefaultPrediction();
        }

        // Pattern'den birim fiyat al
        $unitPrice = $this->getUnitPriceFromPattern($ilId, $ilceId, $kaks);

        if (!$unitPrice) {
            // Pattern yoksa, benzer ilanlardan hesapla
            $unitPrice = $this->getUnitPriceFromSimilar($ilId, $ilceId, $kaks);
        }

        if (!$unitPrice) {
            return $this->getDefaultPrediction();
        }

        // Fiyat bandı hesapla (±10%)
        $recommended = $alanM2 * $unitPrice;
        $min = $recommended * 0.90;
        $max = $recommended * 1.10;

        return [
            'min' => round($min, 2),
            'max' => round($max, 2),
            'recommended' => round($recommended, 2),
            'unit_price' => round($unitPrice, 2),
            'confidence' => $this->getConfidenceLevel($ilId, $ilceId),
            'based_on' => $this->getSampleCount($ilId, $ilceId) . ' satış analizi',
        ];
    }

    /**
     * Pattern'den birim fiyat al
     */
    private function getUnitPriceFromPattern(?int $ilId, ?int $ilceId, ?float $kaks): ?float
    {
        if (!$ilId || !$ilceId || !$kaks) {
            return null;
        }

        $pattern = TkgmLearningPattern::where('pattern_type', TkgmLearningPattern::TYPE_PRICE_KAKS)
            ->where('il_id', $ilId)
            ->where('ilce_id', $ilceId)
            ->active() // context7-ignore
            ->highConfidence()
            ->first();

        if (!$pattern) {
            return null;
        }

        $data = $pattern->pattern_data;
        $kaksRounded = round($kaks, 1);

        // Tam eşleşme ara
        $index = array_search($kaksRounded, $data['kaks_values'] ?? []);

        if ($index !== false) {
            return $data['avg_prices'][$index] ?? null;
        }

        // En yakın KAKS değerini bul
        return $this->interpolatePrice($kaksRounded, $data);
    }

    /**
     * Benzer ilanlardan birim fiyat hesapla
     */
    private function getUnitPriceFromSimilar(?int $ilId, ?int $ilceId, ?float $kaks): ?float
    {
        if (!$ilId || !$ilceId) {
            return null;
        }

        $query = TkgmQuery::where('il_id', $ilId)
            ->where('ilce_id', $ilceId)
            ->sold()
            ->active() // context7-ignore
            ->whereNotNull('satis_fiyati')
            ->whereNotNull('alan_m2');

        if ($kaks) {
            // KAKS ±0.1 aralığında
            $query->whereBetween('kaks', [$kaks - 0.1, $kaks + 0.1]);
        }

        $avgUnitPrice = $query->selectRaw('AVG(satis_fiyati / alan_m2) as avg_unit_price')
            ->value('avg_unit_price');

        return $avgUnitPrice ? (float) $avgUnitPrice : null;
    }

    /**
     * KAKS değerleri arasında interpolasyon yap
     */
    private function interpolatePrice(float $targetKaks, array $data): ?float
    {
        $kaksValues = $data['kaks_values'] ?? [];
        $avgPrices = $data['avg_prices'] ?? [];

        if (count($kaksValues) < 2) {
            return null;
        }

        // En yakın iki KAKS değerini bul
        sort($kaksValues);

        for ($i = 0; $i < count($kaksValues) - 1; $i++) {
            if ($targetKaks >= $kaksValues[$i] && $targetKaks <= $kaksValues[$i + 1]) {
                // Linear interpolation
                $k1 = $kaksValues[$i];
                $k2 = $kaksValues[$i + 1];
                $p1 = $avgPrices[$i];
                $p2 = $avgPrices[$i + 1];

                return $p1 + (($targetKaks - $k1) / ($k2 - $k1)) * ($p2 - $p1);
            }
        }

        // Ortalama döndür
        return array_sum($avgPrices) / count($avgPrices);
    }

    /**
     * Güven seviyesi al
     */
    private function getConfidenceLevel(?int $ilId, ?int $ilceId): float
    {
        if (!$ilId || !$ilceId) {
            return 0.0;
        }

        $pattern = TkgmLearningPattern::where('pattern_type', TkgmLearningPattern::TYPE_PRICE_KAKS)
            ->where('il_id', $ilId)
            ->where('ilce_id', $ilceId)
            ->active() // context7-ignore
            ->first();

        return $pattern ? $pattern->confidence_level : 0.0;
    }

    /**
     * Sample sayısı al
     */
    private function getSampleCount(?int $ilId, ?int $ilceId): int
    {
        if (!$ilId || !$ilceId) {
            return 0;
        }

        return TkgmQuery::where('il_id', $ilId)
            ->where('ilce_id', $ilceId)
            ->sold()
            ->active() // context7-ignore
            ->count();
    }

    /**
     * Default prediction (veri yoksa)
     */
    private function getDefaultPrediction(): array
    {
        return [
            'min' => null,
            'max' => null,
            'recommended' => null,
            'unit_price' => null,
            'confidence' => 0,
            'based_on' => 'Yeterli veri yok',
        ];
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 🎯 MARKET ANALYSIS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * Pazar analizi raporu oluştur
     *
     * @param int $ilId
     * @param int|null $ilceId
     * @return array
     */
    public function getMarketAnalysis(int $ilId, ?int $ilceId = null): array
    {
        $cacheKey = "tkgm_market_analysis_{$ilId}_" . ($ilceId ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ilId, $ilceId) {
            return [
                'summary' => $this->getMarketSummary($ilId, $ilceId),
                'kaks_analysis' => $this->getKaksAnalysis($ilId, $ilceId),
                'velocity_analysis' => $this->getVelocityAnalysis($ilId, $ilceId),
                'trend_analysis' => $this->getTrendAnalysis($ilId, $ilceId),
            ];
        });
    }

    /**
     * Pazar özeti
     */
    private function getMarketSummary(int $ilId, ?int $ilceId): array
    {
        $query = TkgmQuery::where('il_id', $ilId)
            ->active() // context7-ignore
            ->where('queried_at', '>=', now()->subMonths(6));

        if ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        $totalQueries = $query->count();
        $soldCount = $query->clone()->sold()->count();
        $conversionRate = $totalQueries > 0 ? ($soldCount / $totalQueries) * 100 : 0;

        $avgUnitPrice = $query->clone()
            ->sold()
            ->whereNotNull('satis_fiyati')
            ->whereNotNull('alan_m2')
            ->selectRaw('AVG(satis_fiyati / alan_m2) as avg')
            ->value('avg');

        $avgDaysToSell = $query->clone()
            ->sold()
            ->whereNotNull('satis_suresi_gun')
            ->avg('satis_suresi_gun');

        return [
            'total_queries' => $totalQueries,
            'sold_count' => $soldCount,
            'conversion_rate' => round($conversionRate, 2),
            'avg_unit_price' => $avgUnitPrice ? round($avgUnitPrice, 2) : null,
            'avg_days_to_sell' => $avgDaysToSell ? round($avgDaysToSell) : null,
            'period' => 'Son 6 ay',
        ];
    }

    /**
     * KAKS analizi
     */
    private function getKaksAnalysis(int $ilId, ?int $ilceId): ?array
    {
        $query = TkgmLearningPattern::where('pattern_type', TkgmLearningPattern::TYPE_PRICE_KAKS)
            ->where('il_id', $ilId)
            ->active(); // context7-ignore

        if ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        $pattern = $query->first();

        return $pattern ? $pattern->pattern_data : null;
    }

    /**
     * Satış hızı analizi
     */
    private function getVelocityAnalysis(int $ilId, ?int $ilceId): ?array
    {
        $query = TkgmLearningPattern::where('pattern_type', TkgmLearningPattern::TYPE_VELOCITY)
            ->where('il_id', $ilId)
            ->active(); // context7-ignore

        if ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        $pattern = $query->first();

        return $pattern ? $pattern->pattern_data : null;
    }

    /**
     * Trend analizi (son 6 ay)
     */
    private function getTrendAnalysis(int $ilId, ?int $ilceId): array
    {
        $query = TkgmQuery::where('il_id', $ilId)
            ->sold()
            ->active() // context7-ignore
            ->whereNotNull('satis_fiyati')
            ->whereNotNull('alan_m2')
            ->where('satis_tarihi', '>=', now()->subMonths(6));

        if ($ilceId) {
            $query->where('ilce_id', $ilceId);
        }

        $monthlyData = $query
            ->selectRaw('DATE_FORMAT(satis_tarihi, "%Y-%m") as month')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(satis_fiyati / alan_m2) as avg_unit_price')
            ->groupBy('month')
            ->orderBy('month') // context7-ignore
            ->get();

        if ($monthlyData->count() < 2) {
            return [
                'trend' => 'neutral',
                'monthly_change' => 0,
                'data_points' => $monthlyData->count(),
            ];
        }

        // Basit trend hesaplama (ilk ve son ay karşılaştırması)
        $firstMonth = $monthlyData->first();
        $lastMonth = $monthlyData->last();

        $changePercent = (($lastMonth->avg_unit_price - $firstMonth->avg_unit_price) / $firstMonth->avg_unit_price) * 100;

        return [
            'trend' => $changePercent > 2 ? 'up' : ($changePercent < -2 ? 'down' : 'neutral'),
            'monthly_change' => round($changePercent / $monthlyData->count(), 2),
            'total_change' => round($changePercent, 2),
            'data_points' => $monthlyData->count(),
            'monthly_data' => $monthlyData->map(fn($item) => [
                'month' => $item->month,
                'count' => $item->count,
                'avg_unit_price' => round($item->avg_unit_price, 2),
            ])->toArray(),
        ];
    }
}
