<?php

namespace App\Services\Market;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Competitor Analysis & Price Intelligence Service
 *
 * Context7 Standard: C7-MARKET-COMPETITOR-2026-01-06
 *
 * Sorumluluk:
 * Bir ilanın rakiplerini (comps) bulur, piyasa konumunu analiz eder
 * ve fiyat istihbaratı sağlar.
 */
class MarketAnalysisService
{
    /**
     * İlan için detaylı piyasa analizi yapar.
     *
     * @param Ilan $ilan
     * @return array
     */
    public function analyze(Ilan $ilan): array
    {
        // Cache Strategy: Heavy query results cached for 12 hours
        $cacheKey = "market_analysis_{$ilan->id}_v1";

        return Cache::remember($cacheKey, 60 * 60 * 12, function () use ($ilan) {
            $comps = $this->findComparables($ilan);

            if ($comps->isEmpty()) {
                return $this->getEmptyAnalysis();
            }

            return $this->calculateMetrics($ilan, $comps);
        });
    }

    /**
     * Rakip İlanları Bulur (Comps)
     *
     * Algoritma:
     * - Aynı İl/İlçe/Mahalle
     * - Aynı Kategori (örn: Satılık Daire)
     * - Oda Sayısı +/- 1
     * - Net m² +/- %20
     * - Yayın Durumu: Aktif
     */
    protected function findComparables(Ilan $ilan): Collection
    {
        // Essential checks
        if (!$ilan->il_id || !$ilan->ilce_id || !$ilan->ana_kategori_id) {
            return collect();
        }

        $query = Ilan::query()
            ->where('id', '!=', $ilan->id) // Kendisi hariç
            ->where('il_id', $ilan->il_id)
            ->where('ilce_id', $ilan->ilce_id)
            ->where('ana_kategori_id', $ilan->ana_kategori_id)
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value); // Context7: yayin_durumu

        // Mahalle opsiyonel ama varsa kesinlikle filtrele (En önemli lokasyon verisi)
        if ($ilan->mahalle_id) {
            $query->where('mahalle_id', $ilan->mahalle_id);
        }

        // Features Filtering (JSON or Relation check)
        // Performans için Feature şemasını bilmemiz lazım.
        // Genelde 'oda_sayisi' ve 'metrekare' kolonlarda veya features tablosunda olur.
        // IlanResource'da $this->oda_sayisi ve $this->metrekare gördük, demek ki accessor veya column var.
        // Veritabanı şemasını tam bilmediğimiz için safe-guard ile ilerleyelim.
        // Ilan modelinde HasFeatures traiti var.

        // Context7: Oda sayısı ve m2 genellikle attribute olarak erişilebilir.
        // Ancak DB sorgusu için feature_index tablosu veya JSON sorgusu gerekebilir.
        // Basitlik adina: Collection filter yapacağız (Comps sayısı genelde azdır mahalle bazında).
        // Eğer mahallede 1000 ilan varsa bu yavaş olur, ama mahalle bazında 50-100 olur.
        // SQL Filter preferable.

        // Assuming columns exist based on IlanInternalResource usage
        if ($ilan->oda_sayisi) {
            // Oda sayısı string olabilir "3+1", parse etmek gerekebilir.
            // Basit integer varsayalım veya DB scope kullanalım.
            // SQL'de LIKE veya range zor. Collection filter'a bırakalım detayları.
        }

        // 1. Aşamada Location & Category bazlı çek
        $potentialComps = $query->with(['fotograflar' => function($q) {
            $q->orderBy('display_order')->limit(1); // context7-ignore
        }])->limit(100)->get();

        // 2. Aşamada Memory Filtering (Oda & M2)
        return $potentialComps->filter(function ($comp) use ($ilan) {
            // Oda Sayısı Toleransı: +/- 1
            // "3+1" -> 3 olarak almak lazım.
            $targetOda = $this->parseRoomCount($ilan->oda_sayisi);
            $compOda = $this->parseRoomCount($comp->oda_sayisi);

            if ($targetOda && $compOda) {
                if (abs($targetOda - $compOda) > 1) return false;
            }

            // M2 Toleransı: +/- 20%
            $targetM2 = (float) $ilan->metrekare;
            $compM2 = (float) $comp->metrekare;

            if ($targetM2 > 0 && $compM2 > 0) {
                $minM2 = $targetM2 * 0.8;
                $maxM2 = $targetM2 * 1.2;
                if ($compM2 < $minM2 || $compM2 > $maxM2) return false;
            }

            return true;
        })->take(10); // En yakın 10 rakip yeterli analiz için
    }

    protected function calculateMetrics(Ilan $ilan, Collection $comps): array
    {
        $avgPrice = $comps->avg('fiyat');
        $minPrice = $comps->min('fiyat');
        $maxPrice = $comps->max('fiyat');

        // M2 Birim Fiyat (Varsa)
        $avgUnitPrice = $comps->avg(function ($c) {
            return ($c->metrekare > 0) ? $c->fiyat / $c->metrekare : null;
        });

        // Konumlandırma
        $diff = $ilan->fiyat - $avgPrice;
        $diffPercentage = ($avgPrice > 0) ? ($diff / $avgPrice) * 100 : 0;

        $position = match (true) {
            $diffPercentage > 10 => 'expensive',   // %10+ pahalı
            $diffPercentage < -10 => 'cheap',      // %10+ ucuz
            default => 'fair'                      // Adil piyasa değeri
        };

        // Piyasa Nabzı (Son 30 günde eklenen/güncellenen comp sayısı)
        $recentActivity = $comps->where('updated_at', '>=', now()->subDays(30))->count();
        $marketPulse = match (true) {
            $recentActivity > 5 => 'high',
            $recentActivity > 2 => 'medium',
            default => 'low'
        };

        return [
            'has_data' => true,
            'avg_price' => $avgPrice,
            'avg_unit_price' => $avgUnitPrice,
            'position' => $position, // expensive, cheap, fair
            'diff_percentage' => round($diffPercentage, 1),
            'comps_count' => $comps->count(),
            'market_pulse' => $marketPulse,
            'top_competitors' => $comps->sortBy('fiyat')->take(5)->map(function ($c) {
                return [
                    'id' => $c->id,
                    'baslik' => $c->baslik,
                    'fiyat' => $c->fiyat,
                    'metrekare' => $c->metrekare,
                    'oda_sayisi' => $c->oda_sayisi,
                    'image' => $c->fotograflar->first()?->dosya_yolu ?? null
                ];
            })->values()->toArray()
        ];
    }

    protected function getEmptyAnalysis(): array
    {
        return [
            'has_data' => false,
            'avg_price' => 0,
            'position' => 'unknown',
            'diff_percentage' => 0,
            'comps_count' => 0,
            'market_pulse' => 'none',
            'top_competitors' => []
        ];
    }

    private function parseRoomCount($val): ?int
    {
        if (empty($val)) return null;
        // "3+1" -> 3
        if (preg_match('/^(\d+)/', (string)$val, $matches)) {
            return (int) $matches[1];
        }
        return (int) $val;
    }

    /**
     * Bölge bazlı arsa piyasa verilerini toplar.
     */
    public function collectLandPlotData(int $ilId, int $ilceId, ?int $mahalleId = null): array
    {
        $cacheKey = "land_market_data_{$ilId}_{$ilceId}_{$mahalleId}";

        return Cache::remember($cacheKey, 60 * 60 * 24, function () use ($ilId, $ilceId, $mahalleId) {
            $query = Ilan::query()
                ->where('il_id', $ilId)
                ->where('ilce_id', $ilceId)
                ->where('ana_kategori_id', 3) // 3 = Arsa (SmartFieldGenerationService reference)
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value);

            if ($mahalleId) {
                $query->where('mahalle_id', $mahalleId);
            }

            $plots = $query->get(['fiyat', 'metrekare']);

            if ($plots->isEmpty()) {
                return [
                    'avg_price' => 0,
                    'avg_m2_price' => 0,
                    'sample_count' => 0,
                    'confidence' => 'low'
                ];
            }

            $avgPrice = $plots->avg('fiyat');
            $avgM2Price = $plots->filter(fn($p) => $p->metrekare > 0)->avg(fn($p) => $p->fiyat / $p->metrekare);

            return [
                'avg_price' => round($avgPrice, 2),
                'avg_m2_price' => round($avgM2Price, 2),
                'sample_count' => $plots->count(),
                'confidence' => $plots->count() > 5 ? 'high' : 'medium'
            ];
        });
    }
}
