<?php

namespace App\Services\Location;

use App\Models\PointOfInterest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 📍 POI Service - Haversine Distance Calculator
 *
 * Dünya yüzeyinde iki koordinat arasındaki mesafeyi
 * Haversine formülü kullanarak hesaplar.
 *
 * Formula: a = sin²(Δφ/2) + cos φ1 ⋅ cos φ2 ⋅ sin²(Δλ/2)
 *          c = 2 ⋅ atan2(√a, √(1−a))
 *          d = R ⋅ c (R = Dünya yarıçapı, 6371km)
 *
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 */
class PoiService
{
    const EARTH_RADIUS_KM = 6371;

    /**
     * 📍 Verilen Koordinatın Çevresindeki POI'leri Bul
     *
     * Raw SQL Haversine kullanarak en yakın POI'leri hesaplar
     *
     * @param float $lat    Enlem
     * @param float $lng    Boylam
     * @param float $radiusKm  Arama yarıçapı (km)
     * @param array $filters  POI tipleri filtreleri
     * @return Collection
     */
    public function findNearby(float $lat, float $lng, float $radiusKm = 2, array $filters = []): Collection
    {
        try {
            $query = PointOfInterest::query();

            // Haversine formülü ile mesafe hesaplaması
            $haversineFormula = "
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(lat)) *
                    cos(radians(lng) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(lat))
                )) AS distance_km
            ";

            $query->select('*')
                ->selectRaw($haversineFormula, [$lat, $lng, $lat])
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->having('distance_km', '<=', $radiusKm);

            // POI tipi filtreleri (Context7: poi_turu)
            if (!empty($filters['types'])) { // context7-ignore
                $query->whereIn('poi_turu', $filters['types']); // context7-ignore
            }

            // Aktif POI'ler (Context7: aktiflik_durumu)
            // ✅ Null-safe aktiflik kontrolü
            $query->where(function($q) {
                $q->where('aktiflik_durumu', true)
                  ->orWhere('aktiflik_durumu', 1)
                  ->orWhereNull('aktiflik_durumu'); // Null değerleri de kabul et
            });

            // Mesafeye göre sırala
            $query->orderBy('distance_km', 'asc') // context7-ignore
                ->limit(50);

            // Sonuçları transform et (Context7 compliant + backward compatibility)
            return $query->get()
                ->map(fn($poi) => [
                    'id' => $poi->id,
                    'poi_adi' => $poi->poi_adi, // ✅ SAB
                    'poi_turu' => $poi->poi_turu, // ✅ SAB
                    'poi_kategorisi' => $poi->poi_kategorisi, // ✅ SAB
                    'type' => $poi->poi_turu, // Backward compatibility // context7-ignore
                    'name' => $poi->poi_adi, // Backward compatibility
                    'distance_km' => round($poi->distance_km, 2), // km cinsinden
                    'distance' => round($poi->distance_km * 1000), // m cinsinden (backward compatibility)
                    'lat' => $poi->lat,
                    'lng' => $poi->lng,
                    'rating' => $poi->rating,
                    'ek_veri' => $poi->ek_veri, // ✅ SAB: ek_veri (JSON)
                    'address' => $poi->ek_veri['address'] ?? null, // Backward compatibility
                    'phone' => $poi->ek_veri['phone'] ?? null, // Backward compatibility
                    'url' => $poi->ek_veri['url'] ?? null, // Backward compatibility
                ])
                ->sortBy('distance_km'); // Mesafeye göre sırala
        } catch (\Exception $e) {
            // Log error but don't crash
            Log::error('POI findNearby error', [
                'lat' => $lat,
                'lng' => $lng,
                'radius' => $radiusKm,
                'error' => $e->getMessage(),
            ]);

            // Return empty collection
            return collect([]);
        }
    }

    /**
     * 🧮 Haversine Mesafe Hesapla (iki koordinat arası)
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Mesafe (km)
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = self::EARTH_RADIUS_KM * $c;

        return round($distance, 3);
    }

    /**
     * 🎯 En Yakın POI'i Bul
     *
     * @param float $lat
     * @param float $lng
     * @param string|null $poiType (isteğe bağlı)
     * @return array|null
     */
    public function findClosest(float $lat, float $lng, ?string $poiType = null): ?array
    {
        $nearest = $this->findNearby($lat, $lng, 5, [
            'types' => $poiType ? [$poiType] : [], // context7-ignore
        ])->first();

        return $nearest ? (array) $nearest : null;
    }

    /**
     * 📊 POI Yoğunluk Haritası (Grid Analizi)
     *
     * Bölgeyi 0.5km×0.5km grid'lere bölerek POI yoğunluğunu hesapla
     * (Heat map veya cluster analizi için)
     *
     * @param float $centerLat
     * @param float $centerLng
     * @param float $radiusKm
     * @return array Grid ve POI dağılımı
     */
    public function getPoiDensityGrid(float $centerLat, float $centerLng, float $radiusKm = 2): array
    {
        $pois = $this->findNearby($centerLat, $centerLng, $radiusKm);

        // Grid size: 0.5 km
        $gridSize = 0.005; // lat/lng fark olarak
        $grid = [];

        foreach ($pois as $poi) {
            $gridX = floor($poi['lat'] / $gridSize);
            $gridY = floor($poi['lng'] / $gridSize);
            $gridKey = "{$gridX},{$gridY}";

            if (!isset($grid[$gridKey])) {
                $grid[$gridKey] = [
                    'lat' => $gridX * $gridSize,
                    'lng' => $gridY * $gridSize,
                    'count' => 0,
                    'pois' => [],
                ];
            }

            $grid[$gridKey]['count']++;
            $grid[$gridKey]['pois'][] = $poi;
        }

        return array_values($grid);
    }

    /**
     * 🔍 Bölge Adını Reverse Geocoding ile Al
     *
     * @param float $lat
     * @param float $lng
     * @return string Bölge adı
     */
    public function getAreaName(float $lat, float $lng): string
    {
        // En yakın mahalleyi bulmaya çalış (Context7: poi_turu = 'neighborhood' veya 'region' varsayımıyla)
        $nearestArea = $this->findClosest($lat, $lng, 'neighborhood');

        return $nearestArea['poi_adi'] ?? 'Bodrum Geneli';
    }

    /**
     * 📈 POI İstatistikleri
     *
     * @param float $lat
     * @param float $lng
     * @param float $radiusKm
     * @return array
     */
    public function getStatistics(float $lat, float $lng, float $radiusKm = 2): array
    {
        $pois = $this->findNearby($lat, $lng, $radiusKm);
        $grouped = $pois->groupBy('poi_turu'); // ✅ SAB: poi_turu

        return [
            'total' => $pois->count(),
            'by_type' => $grouped->map(fn($items) => $items->count())->toArray(),
            'closest_distance_km' => $pois->first()['distance_km'] ?? null,
            'farthest_distance_km' => $pois->last()['distance_km'] ?? null,
            'closest_distance_m' => $pois->first()['distance'] ?? null, // Backward compatibility
            'farthest_distance_m' => $pois->last()['distance'] ?? null, // Backward compatibility
        ];
    }

    /**
     * 🟢 Bölge Özetini (Highlights) Al
     *
     * @param float $lat
     * @param float $lng
     * @param float $radiusKm
     * @return array
     */
    public function getHighlights(float $lat, float $lng, float $radiusKm = 2): array
    {
        $pois = $this->findNearby($lat, $lng, $radiusKm);

        $highlights = [];
        $uniqueTypes = [];

        foreach ($pois as $poi) {
            $type = $poi['poi_turu'] ?? 'Genel';
            if (!in_array($type, $uniqueTypes)) {
                $uniqueTypes[] = $type;
                $distance = $poi['distance_km'] < 1
                    ? ($poi['distance_km'] * 1000) . "m"
                    : $poi['distance_km'] . "km";

                $highlights[] = "{$poi['poi_adi']} ({$distance})";
            }

            if (count($highlights) >= 3) break;
        }

        return $highlights;
    }
}
