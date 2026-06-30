<?php

namespace App\Services\MarketIntelligence;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\Logging\LogService;

/**
 * Benchmark Service — MIE v1 Alpha
 *
 * Mahalle bazlı comparable ilanlardan medyan m2 fiyat benchmark'ı hesaplar.
 * Hierarchical fallback: mahalle → ilçe → il (en az 5 comp gerekli).
 *
 * Tamamen deterministik — rand() sıfır, AI sıfır.
 */
class BenchmarkService
{
    /**
     * Minimum comp sayısı. Altında "insufficient_data" döner.
     */
    private const MIN_COMP_COUNT = 5;

    /**
     * Cache TTL: 6 saat (saniye).
     */
    private const CACHE_TTL = 6 * 60 * 60;

    /**
     * Benchmark hesapla.
     *
     * @return array{
     *   median_m2_price: float|null,
     *   min_price: float|null,
     *   max_price: float|null,
     *   p25_price: float|null,
     *   p75_price: float|null,
     *   sample_size: int,
     *   confidence: string,
     *   fallback_level: string,
     * }
     */
    public function calculate(Ilan $ilan): array
    {
        if (!$ilan->il_id || !$ilan->ana_kategori_id || !$ilan->fiyat || $ilan->fiyat <= 0) {
            return $this->emptyBenchmark('missing_input');
        }

        $effectiveM2 = $this->getEffectiveM2($ilan);

        if (!$effectiveM2 || $effectiveM2 <= 0) {
            return $this->emptyBenchmark('missing_m2');
        }

        $cacheKey = $this->buildCacheKey($ilan);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ilan, $effectiveM2) {
            return $this->computeBenchmark($ilan, $effectiveM2);
        });
    }

    /**
     * Hierarchical fallback: mahalle → ilçe → il.
     */
    private function computeBenchmark(Ilan $ilan, float $effectiveM2): array
    {
        // Level 1: Mahalle
        if ($ilan->mahalle_id) {
            $comps = $this->queryComps($ilan, 'mahalle');
            if ($comps->count() >= self::MIN_COMP_COUNT) {
                $totalCount = $this->queryTotalCount($ilan, 'mahalle');
                $demandData = $this->queryDemandData($ilan, 'mahalle');
                return $this->buildBenchmarkFromComps($comps, 'mahalle', $totalCount, $demandData);
            }
        }

        // Level 2: İlçe
        if ($ilan->ilce_id) {
            $comps = $this->queryComps($ilan, 'ilce');
            if ($comps->count() >= self::MIN_COMP_COUNT) {
                $totalCount = $this->queryTotalCount($ilan, 'ilce');
                $demandData = $this->queryDemandData($ilan, 'ilce');
                return $this->buildBenchmarkFromComps($comps, 'ilce', $totalCount, $demandData);
            }
        }

        // Level 3: İl
        $comps = $this->queryComps($ilan, 'il');
        if ($comps->count() >= self::MIN_COMP_COUNT) {
            $totalCount = $this->queryTotalCount($ilan, 'il');
            $demandData = $this->queryDemandData($ilan, 'il');
            return $this->buildBenchmarkFromComps($comps, 'il', $totalCount, $demandData);
        }

        // Insufficient data at all levels
        return $this->emptyBenchmark('insufficient_comps', $comps->count());
    }

    /**
     * Query comparable listings at the given location scope.
     */
    private function queryComps(Ilan $ilan, string $level): \Illuminate\Support\Collection
    {
        $query = DB::table('ilanlar')
            ->where('id', '!=', $ilan->id)
            ->where('ana_kategori_id', $ilan->ana_kategori_id)
            ->where('il_id', $ilan->il_id)
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('fiyat', '>', 0);

        if ($level === 'mahalle') {
            $query->where('ilce_id', $ilan->ilce_id)
                  ->where('mahalle_id', $ilan->mahalle_id);
        } elseif ($level === 'ilce') {
            $query->where('ilce_id', $ilan->ilce_id);
        }

        // Compute m2_price inline: fiyat / COALESCE(alan_m2, brut_m2, net_m2)
        // Only include rows where at least one m2 field is positive
        $query->whereRaw('COALESCE(alan_m2, brut_m2, net_m2) > 0')
              ->selectRaw('fiyat / COALESCE(alan_m2, brut_m2, net_m2) as m2_price, fiyat');

        return $query->get();
    }

    /**
     * Compute median, p25, p75, min, max from comp m2 prices.
     */
    private function buildBenchmarkFromComps(\Illuminate\Support\Collection $comps, string $fallbackLevel, int $totalCount = 0, array $demandData = []): array
    {
        $m2Prices = $comps->pluck('m2_price')->sort()->values();
        $count = $m2Prices->count();

        $median = $this->percentile($m2Prices, 50);
        $p25 = $this->percentile($m2Prices, 25);
        $p75 = $this->percentile($m2Prices, 75);

        // Avg ve stddev for confidence layer
        $avg = $m2Prices->avg();
        $stdDev = $this->standardDeviation($m2Prices, $avg);

        // Valid ratio: comps with valid m2 / total comps in scope
        $validRatio = ($totalCount > 0) ? ($count / $totalCount) : 1.0;

        $confidence = match (true) {
            $count >= 20 => 'yuksek',
            $count >= 10 => 'orta',
            default => 'dusuk',
        };

        return [
            'median_m2_price' => round($median, 2),
            'min_price' => round($m2Prices->first(), 2),
            'max_price' => round($m2Prices->last(), 2),
            'p25_price' => round($p25, 2),
            'p75_price' => round($p75, 2),
            'sample_size' => $count,
            'confidence' => $confidence,
            'fallback_level' => $fallbackLevel,
            'avg_price_m2' => round($avg, 2),
            'std_dev' => round($stdDev, 2),
            'valid_ratio' => round($validRatio, 4),
            'avg_days_on_market' => $demandData['avg_days_on_market'] ?? null,
            'trend_ratio' => $demandData['trend_ratio'] ?? null,
            'drop_ratio' => $demandData['drop_ratio'] ?? null,
        ];
    }

    /**
     * Calculate percentile from a sorted collection.
     */
    private function percentile(\Illuminate\Support\Collection $sorted, int $pct): float
    {
        $count = $sorted->count();

        if ($count === 0) {
            return 0;
        }

        if ($count === 1) {
            return (float) $sorted->first();
        }

        $index = ($pct / 100) * ($count - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);
        $fraction = $index - $lower;

        if ($lower === $upper) {
            return (float) $sorted[$lower];
        }

        return (float) ($sorted[$lower] * (1 - $fraction) + $sorted[$upper] * $fraction);
    }

    /**
     * Effective m2: alan_m2 → brut_m2 → net_m2 fallback.
     */
    private function getEffectiveM2(Ilan $ilan): ?float
    {
        $val = $ilan->alan_m2 ?? $ilan->brut_m2 ?? $ilan->net_m2 ?? null;

        return ($val && $val > 0) ? (float) $val : null;
    }

    private function buildCacheKey(Ilan $ilan): string
    {
        return sprintf(
            'mie_benchmark_%d_%d_%d_%d_v1',
            $ilan->il_id,
            $ilan->ilce_id ?? 0,
            $ilan->mahalle_id ?? 0,
            $ilan->ana_kategori_id
        );
    }

    /**
     * Standard deviation hesapla.
     */
    private function standardDeviation(\Illuminate\Support\Collection $values, float $mean): float
    {
        $count = $values->count();

        if ($count <= 1) {
            return 0.0;
        }

        $sumSquaredDiffs = $values->reduce(function (float $carry, $value) use ($mean) {
            return $carry + (((float) $value - $mean) ** 2);
        }, 0.0);

        return sqrt($sumSquaredDiffs / ($count - 1));
    }

    /**
     * Total listing count at scope (including those without valid m2).
     * Used for data quality ratio calculation.
     */
    private function queryTotalCount(Ilan $ilan, string $level): int
    {
        $query = DB::table('ilanlar')
            ->where('id', '!=', $ilan->id)
            ->where('ana_kategori_id', $ilan->ana_kategori_id)
            ->where('il_id', $ilan->il_id)
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('fiyat', '>', 0);

        if ($level === 'mahalle') {
            $query->where('ilce_id', $ilan->ilce_id)
                  ->where('mahalle_id', $ilan->mahalle_id);
        } elseif ($level === 'ilce') {
            $query->where('ilce_id', $ilan->ilce_id);
        }

        return $query->count();
    }

    /**
     * Query demand signals for the given location scope.
     *
     * Returns: avg_days_on_market, trend_ratio, drop_ratio
     */
    private function queryDemandData(Ilan $ilan, string $level): array
    {
        return [
            'avg_days_on_market' => $this->queryAvgDaysOnMarket($ilan, $level),
            'trend_ratio' => $this->queryTrendRatio($ilan, $level),
            'drop_ratio' => $this->queryDropRatio($ilan, $level),
        ];
    }

    /**
     * Average days on market for closed listings (arsiv/pasif).
     * Uses updated_at - created_at as proxy (no closed_at column).
     * Cross-DB compatible: MySQL uses DATEDIFF, SQLite uses julianday.
     */
    private function queryAvgDaysOnMarket(Ilan $ilan, string $level): ?float
    {
        $query = DB::table('ilanlar')
            ->where('ana_kategori_id', $ilan->ana_kategori_id)
            ->where('il_id', $ilan->il_id)
            ->whereIn('yayin_durumu', [IlanDurumu::ARSIV->value, IlanDurumu::PASIF->value])
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at');

        if ($level === 'mahalle') {
            $query->where('ilce_id', $ilan->ilce_id)
                  ->where('mahalle_id', $ilan->mahalle_id);
        } elseif ($level === 'ilce') {
            $query->where('ilce_id', $ilan->ilce_id);
        }

        $driver = DB::getDriverName();
        $expr = $driver === 'sqlite'
            ? 'AVG(julianday(updated_at) - julianday(created_at)) as avg_days'
            : 'AVG(DATEDIFF(updated_at, created_at)) as avg_days';

        $avg = $query->selectRaw($expr)->value('avg_days');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    /**
     * Listing trend ratio: new listings in last 30 days / previous 30 days.
     */
    private function queryTrendRatio(Ilan $ilan, string $level): ?float
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        $baseQuery = fn () => DB::table('ilanlar')
            ->where('ana_kategori_id', $ilan->ana_kategori_id)
            ->where('il_id', $ilan->il_id)
            ->where('fiyat', '>', 0)
            ->when($level === 'mahalle', fn ($q) => $q->where('ilce_id', $ilan->ilce_id)->where('mahalle_id', $ilan->mahalle_id))
            ->when($level === 'ilce', fn ($q) => $q->where('ilce_id', $ilan->ilce_id));

        $currentCount = $baseQuery()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $previousCount = $baseQuery()
            ->where('created_at', '>=', $sixtyDaysAgo)
            ->where('created_at', '<', $thirtyDaysAgo)
            ->count();

        if ($previousCount === 0) {
            return $currentCount > 0 ? 1.5 : null;
        }

        return round($currentCount / $previousCount, 2);
    }

    /**
     * Price drop ratio: listings with price decrease / total listings in scope.
     * Uses ilan_price_history table (new_price < old_price).
     * Returns null if the price history table doesn't exist.
     */
    private function queryDropRatio(Ilan $ilan, string $level): ?float
    {
        $baseQuery = DB::table('ilanlar')
            ->where('ana_kategori_id', $ilan->ana_kategori_id)
            ->where('il_id', $ilan->il_id)
            ->where('fiyat', '>', 0)
            ->where('created_at', '>=', now()->subDays(90));

        if ($level === 'mahalle') {
            $baseQuery->where('ilce_id', $ilan->ilce_id)
                      ->where('mahalle_id', $ilan->mahalle_id);
        } elseif ($level === 'ilce') {
            $baseQuery->where('ilce_id', $ilan->ilce_id);
        }

        $totalCount = $baseQuery->count();

        if ($totalCount === 0) {
            return null;
        }

        try {
            $droppedCount = DB::table('ilan_price_history')
                ->whereIn('ilan_id', (clone $baseQuery)->select('id'))
                ->whereRaw('new_price < old_price')
                ->distinct('ilan_id')
                ->count('ilan_id');
        } catch (\Illuminate\Database\QueryException $e) {
            // Table may not exist in test environment
            LogService::error('BenchmarkService: queryDropRatio failed', [], $e);
            return null;
        }

        return round($droppedCount / $totalCount, 4);
    }

    private function emptyBenchmark(string $reason, int $sampleSize = 0): array
    {
        return [
            'median_m2_price' => null,
            'min_price' => null,
            'max_price' => null,
            'p25_price' => null,
            'p75_price' => null,
            'sample_size' => $sampleSize,
            'confidence' => 'yetersiz',
            'fallback_level' => $reason,
            'avg_price_m2' => null,
            'std_dev' => null,
            'valid_ratio' => 0.0,
            'avg_days_on_market' => null,
            'trend_ratio' => null,
            'drop_ratio' => null,
        ];
    }
}
