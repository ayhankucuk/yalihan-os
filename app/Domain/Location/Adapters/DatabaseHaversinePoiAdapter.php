<?php

namespace App\Domain\Location\Adapters;

use App\Domain\Location\Contracts\PoiProviderInterface;
use App\Domain\Location\DTOs\PoiSearchCriteria;
use App\Models\PointOfInterest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 📍 Database Haversine POI Adapter
 *
 * MySQL + SQLite uyumlu POI Provider.
 *
 * SQLite bug koruması: SQLite < 3.38.0, HAVING yan tümcesinde
 * non-aggregate sütun kullandığında sessizce row'u atlar.
 * Çözüm: WHERE içinde pre-calculate edilmiş subquery yerine,
 * tüm kayıtları çekip PHP tarafında bellek-içi filtreleme yapılır
 * (POI tablosu ~10k satır, 50 sonuç limiti — bu yaklaşım performanslı).
 *
 * Fail-Closed: Eğer SQLite math fonksiyonları mevcut değilse
 * adapter çalışmaz — sessiz bozulma yok.
 */
class DatabaseHaversinePoiAdapter implements PoiProviderInterface
{
    private const EARTH_RADIUS_KM = 6371;
    private const DEFAULT_LIMIT = 50;

    public function getProviderName(): string
    {
        return 'database_haversine';
    }

    public function findNearby(PoiSearchCriteria $criteria): Collection
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return $this->findNearbySqlite($criteria);
        }

        return $this->findNearbyMysql($criteria);
    }

    /**
     * MySQL/MariaDB: RAW SQL Haversine + HAVING (sorunsuz)
     */
    private function findNearbyMysql(PoiSearchCriteria $criteria): Collection
    {
        $poiTypes = $this->resolvePoiTypes($criteria);

        $haversineFormula = <<<SQL
            ({$this->getEarthRadius()} * acos(
                cos(radians(?)) *
                cos(radians(lat)) *
                cos(radians(lng) - radians(?)) +
                sin(radians(?)) *
                sin(radians(lat))
            )) AS distance_km
        SQL;

        $query = PointOfInterest::query()
            ->select('*')
            ->selectRaw($haversineFormula, [$criteria->lat, $criteria->lng, $criteria->lat])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->having('distance_km', '<=', $criteria->radiusKm)
            ->orderBy('distance_km', 'asc')
            ->limit(self::DEFAULT_LIMIT);

        if ($poiTypes !== null) {
            $query->whereIn('poi_turu', $poiTypes);
        }

        $this->applyAktiflikFilter($query);

        return $this->transformCollection($query->get(), $criteria->lat, $criteria->lng);
    }

    /**
     * SQLite: Pre-check + PHP-side filtering (HAVING bug workaround)
     *
     * Fail-Closed: Eğer SQLite math fonksiyonları yüklü değilse Exception atar.
     */
    private function findNearbySqlite(PoiSearchCriteria $criteria): Collection
    {
        $this->assertSqliteMathFunctionsAvailable();

        $poiTypes = $this->resolvePoiTypes($criteria);

        // SQLite: HAVING yerine WHERE subquery + PHP-side filter
        // Tüm aktif POI'leri çek, sonra PHP'de filtrele
        $query = PointOfInterest::query()
            ->select('*')
            ->selectRaw("
                ({$this->getEarthRadius()} * acos(
                    cos(radians(?)) *
                    cos(radians(lat)) *
                    cos(radians(lng) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(lat))
                )) AS distance_km
            ", [$criteria->lat, $criteria->lng, $criteria->lat])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('distance_km', 'asc')
            ->limit(200); // Pre-limit: SQLite'a aşırı yük bindirmemek için

        if ($poiTypes !== null) {
            $query->whereIn('poi_turu', $poiTypes);
        }

        $this->applyAktiflikFilter($query);

        // PHP-side filtering — SQLite HAVING bug workaround
        $all = $query->get();

        $filtered = $all->filter(
            fn($poi) => $poi->distance_km !== null && (float) $poi->distance_km <= $criteria->radiusKm
        );

        return $this->transformCollection($filtered->take(self::DEFAULT_LIMIT), $criteria->lat, $criteria->lng);
    }

    /**
     * SQLite math fonksiyonlarının mevcut olduğunu doğrula.
     * Yoksa: Fail-Closed — Exception at.
     *
     * @throws \RuntimeException SQLite math fonksiyonları mevcut değil
     */
    private function assertSqliteMathFunctionsAvailable(): void
    {
        $pdo = DB::connection()->getPdo();

        if (!method_exists($pdo, 'sqliteCreateFunction')) {
            return; // PDO SQLite değil
        }

        try {
            // radians fonksiyonunun çalışıp çalışmadığını test et
            $result = DB::selectOne("SELECT radians(90.0) as r");
            if ($result === null || ($result->r ?? -1) < 1.55 || ($result->r ?? -1) > 1.58) {
                throw new \RuntimeException(
                    'SqliteMathFunctionsNotAvailable: radians(90) returned unexpected value. ' .
                    'SQLite math functions (radians, acos, cos, sin) must be registered via ' .
                    'sqliteCreateFunction() before using DatabaseHaversinePoiAdapter. ' .
                    'See LocationPoiCharacterizationTest::setUp() for registration example.'
                );
            }
        } catch (\RuntimeException $e) {
            throw $e; // Zaten RuntimeException
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'SqliteMathFunctionsNotAvailable: ' . $e->getMessage() . '. ' .
                'SQLite math functions must be registered before using this adapter.'
            );
        }
    }

    /**
     * Aktif POI filtresi — Null-safe (Context7: aktiflik_durumu)
     */
    private function applyAktiflikFilter($query): void
    {
        $query->where(function ($q) {
            $q->where('aktiflik_durumu', true)
              ->orWhere('aktiflik_durumu', 1)
              ->orWhereNull('aktiflik_durumu');
        });
    }

    /**
     * POI tipi filtrelerini çöz: kategori üzerinden veya doğrudan.
     */
    private function resolvePoiTypes(PoiSearchCriteria $criteria): ?array
    {
        if ($criteria->poiTypes !== null) {
            return $criteria->poiTypes;
        }

        if ($criteria->kategori !== null) {
            return $criteria->getPoiTypesByKategori();
        }

        return null;
    }

    /**
     * Sonuç Collection'ını API contract formatına dönüştür.
     *
     * @param Collection $collection
     * @param float $originLat
     * @param float $originLng
     * @return Collection<int, array{
     *     id: int,
     *     poi_adi: string,
     *     poi_turu: string,
     *     poi_kategorisi: string|null,
     *     type: string,
     *     name: string,
     *     distance_km: float,
     *     distance: int,
     *     lat: float,
     *     lng: float,
     *     rating: float|null,
     *     ek_veri: array|null,
     *     address: string|null
     * }>
     */
    private function transformCollection(Collection $collection, float $originLat, float $originLng): Collection
    {
        return $collection->map(function ($poi) {
            $ekVeri = is_array($poi->ek_veri) ? $poi->ek_veri : [];

            return [
                'id' => (int) $poi->id,
                'poi_adi' => $poi->poi_adi,
                'poi_turu' => $poi->poi_turu,
                'poi_kategorisi' => $poi->poi_kategorisi,
                'type' => $poi->poi_turu,
                'name' => $poi->poi_adi,
                'distance_km' => $poi->distance_km !== null ? round((float) $poi->distance_km, 2) : null,
                'distance' => $poi->distance_km !== null ? (int) round((float) $poi->distance_km * 1000) : null,
                'lat' => (float) $poi->lat,
                'lng' => (float) $poi->lng,
                'rating' => $poi->rating !== null ? (float) $poi->rating : null,
                'ek_veri' => $ekVeri,
                'address' => $ekVeri['address'] ?? null,
            ];
        });
    }

    private function getEarthRadius(): int
    {
        return self::EARTH_RADIUS_KM;
    }
}
