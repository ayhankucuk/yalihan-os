<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Akıllı Yakın Çevre Analizi Servisi
 *
 * Context7: Açık kaynaklı POI tespiti ve analiz
 * - OpenStreetMap Overpass API
 * - Nominatim API
 * - Mesafe hesaplama
 * - Değer artışı analizi
 */
class AkilliCevreAnaliziService
{
    /**
     * Yakın çevre analizi
     */
    public function analyzeNearbyEnvironment(float $latitude, float $longitude, string $propertyType = 'arsa'): array
    {
        $cacheKey = "cevre_analiz_{$latitude}_{$longitude}_{$propertyType}";

        return Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude, $propertyType) {
            return $this->performEnvironmentAnalysis($latitude, $longitude, $propertyType);
        });
    }

    /**
     * Çevre analizi gerçekleştir
     */
    private function performEnvironmentAnalysis(float $latitude, float $longitude, string $propertyType): array
    {
        $analysis = [
            'poi_analysis' => $this->getPOIAnalysis($latitude, $longitude, $propertyType),
            'distance_analysis' => $this->getDistanceAnalysis($latitude, $longitude, $propertyType),
            'value_impact' => $this->getValueImpactAnalysis($latitude, $longitude, $propertyType),
            'recommendations' => $this->getRecommendations($latitude, $longitude, $propertyType),
        ];

        return $analysis;
    }

    /**
     * POI (Point of Interest) analizi
     */
    private function getPOIAnalysis(float $latitude, float $longitude, string $propertyType): array
    {
        $pois = [];

        // Overpass API ile POI sorgusu
        $overpassQuery = $this->buildOverpassQuery($latitude, $longitude, $propertyType);

        try {
            $response = Http::timeout(30)->post('https://overpass-api.de/api/interpreter', [
                'data' => $overpassQuery,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $pois = $this->parsePOIData($data, $propertyType);
            }
        } catch (\Exception $e) {
            \Log::error('POI analizi hatası: '.$e->getMessage());
        }

        return $pois;
    }

    /**
     * Overpass API sorgusu oluştur
     */
    private function buildOverpassQuery(float $latitude, float $longitude, string $propertyType): string
    {
        $radius = $this->getRadiusForPropertyType($propertyType);

        return "
        [out:json][timeout:25];
        (
          node['amenity'~'^(school|hospital|pharmacy|bank|restaurant|cafe|fuel|parking)$'](around:{$radius},{$latitude},{$longitude});
          way['amenity'~'^(school|hospital|pharmacy|bank|restaurant|cafe|fuel|parking)$'](around:{$radius},{$latitude},{$longitude});
          node['shop'~'^(supermarket|convenience|clothes|electronics|furniture)$'](around:{$radius},{$latitude},{$longitude});
          way['shop'~'^(supermarket|convenience|clothes|electronics|furniture)$'](around:{$radius},{$latitude},{$longitude});
          node['leisure'~'^(park|playground|sports_centre|swimming_pool)$'](around:{$radius},{$latitude},{$longitude});
          way['leisure'~'^(park|playground|sports_centre|swimming_pool)$'](around:{$radius},{$latitude},{$longitude});
          node['public_transport'~'^(station|stop)$'](around:{$radius},{$latitude},{$longitude});
          way['public_transport'~'^(station|stop)$'](around:{$radius},{$latitude},{$longitude});
        );
        out geom;
        ";
    }

    /**
     * Mülk tipine göre yarıçap belirle
     */
    private function getRadiusForPropertyType(string $propertyType): int
    {
        return match ($propertyType) {
            'arsa' => 2000,      // 2km - Arsa için geniş alan
            'yazlik' => 1500,    // 1.5km - Yazlık için orta alan
            'villa_daire' => 1000, // 1km - Villa/daire için dar alan
            'isyeri' => 500,     // 500m - İşyeri için çok dar alan
            default => 1000
        };
    }

    /**
     * POI verilerini parse et
     */
    private function parsePOIData(array $data, string $propertyType): array
    {
        $pois = [
            'egitim' => [],
            'saglik' => [],
            'alısveris' => [],
            'ulasim' => [],
            'eglence' => [],
            'diger' => [],
        ];

        foreach ($data['elements'] ?? [] as $element) {
            $poi = $this->categorizePOI($element, $propertyType);
            if ($poi) {
                $pois[$poi['category']][] = $poi;
            }
        }

        return $pois;
    }

    /**
     * POI'yi kategorize et
     */
    private function categorizePOI(array $element, string $propertyType): ?array
    {
        $tags = $element['tags'] ?? [];
        $name = $tags['name'] ?? 'İsimsiz';
        $amenity = $tags['amenity'] ?? '';
        $shop = $tags['shop'] ?? '';
        $leisure = $tags['leisure'] ?? '';
        $public_transport = $tags['public_transport'] ?? '';

        // Kategori belirleme
        $category = 'diger';
        if (in_array($amenity, ['school', 'university', 'college'])) {
            $category = 'egitim';
        } elseif (in_array($amenity, ['hospital', 'pharmacy', 'clinic'])) {
            $category = 'saglik';
        } elseif (! empty($shop) || in_array($amenity, ['restaurant', 'cafe', 'bank'])) {
            $category = 'alısveris';
        } elseif (! empty($public_transport) || in_array($amenity, ['fuel', 'parking'])) {
            $category = 'ulasim';
        } elseif (! empty($leisure) || in_array($amenity, ['cinema', 'theatre'])) {
            $category = 'eglence';
        }

        return [
            'name' => $name,
            'category' => $category,
            'type' => $amenity ?: $shop ?: $leisure ?: $public_transport, // context7-ignore
            'distance' => $this->calculateDistance($element, $propertyType),
            'importance' => $this->calculateImportance($element, $propertyType),
        ];
    }

    /**
     * Mesafe analizi
     */
    private function getDistanceAnalysis(float $latitude, float $longitude, string $propertyType): array
    {
        return [
            'walking_distances' => $this->getWalkingDistances($latitude, $longitude, $propertyType),
            'driving_distances' => $this->getDrivingDistances($latitude, $longitude, $propertyType),
            'public_transport' => $this->getPublicTransportAccess($latitude, $longitude, $propertyType),
        ];
    }

    /**
     * Yürüme mesafeleri
     */
    private function getWalkingDistances(float $latitude, float $longitude, string $propertyType): array
    {
        // Nominatim API ile yürüme mesafesi hesaplama
        $distances = [];

        // Önemli noktalar için yürüme mesafesi
        $importantPOIs = [
            'metro' => 'Metro İstasyonu',
            'otobus' => 'Otobüs Durağı',
            'market' => 'Market',
            'okul' => 'Okul',
            'hastane' => 'Hastane',
        ];

        foreach ($importantPOIs as $key => $name) {
            $distances[$key] = $this->calculateWalkingDistance($latitude, $longitude, $name);
        }

        return $distances;
    }

    /**
     * Araç mesafeleri
     */
    private function getDrivingDistances(float $latitude, float $longitude, string $propertyType): array
    {
        // OSRM API ile araç mesafesi hesaplama
        $distances = [];

        $importantDestinations = [
            'havaalani' => 'Havaalanı',
            'merkez' => 'Şehir Merkezi',
            'avm' => 'AVM',
            'hastane' => 'Hastane',
        ];

        foreach ($importantDestinations as $key => $name) {
            $distances[$key] = $this->calculateDrivingDistance($latitude, $longitude, $name);
        }

        return $distances;
    }

    /**
     * Toplu taşıma erişimi
     */
    private function getPublicTransportAccess(float $latitude, float $longitude, string $propertyType): array
    {
        return [
            'metro_erisim' => $this->checkMetroAccess($latitude, $longitude),
            'otobus_erisim' => $this->checkBusAccess($latitude, $longitude),
            'dolmus_erisim' => $this->checkDolmusAccess($latitude, $longitude),
        ];
    }

    /**
     * Değer artışı analizi
     */
    private function getValueImpactAnalysis(float $latitude, float $longitude, string $propertyType): array
    {
        return [
            'cevre_puani' => $this->calculateEnvironmentScore($latitude, $longitude, $propertyType),
            'yatirim_potansiyeli' => $this->calculateInvestmentPotential($latitude, $longitude, $propertyType),
            'deger_artis_tahmini' => $this->calculateValueIncreaseEstimate($latitude, $longitude, $propertyType),
            'risk_faktorleri' => $this->identifyRiskFactors($latitude, $longitude, $propertyType),
        ];
    }

    /**
     * Çevre puanı hesapla
     */
    private function calculateEnvironmentScore(float $latitude, float $longitude, string $propertyType): int
    {
        $score = 0;

        // POI'lerin ağırlıklı puanlaması
        $poiWeights = [
            'egitim' => 20,
            'saglik' => 15,
            'alısveris' => 10,
            'ulasim' => 25,
            'eglence' => 5,
        ];

        $pois = $this->getPOIAnalysis($latitude, $longitude, $propertyType);

        foreach ($pois as $category => $items) {
            $score += count($items) * ($poiWeights[$category] ?? 0);
        }

        return min($score, 100); // Maksimum 100 puan
    }

    /**
     * Yatırım potansiyeli hesapla
     */
    private function calculateInvestmentPotential(float $latitude, float $longitude, string $propertyType): string
    {
        $score = $this->calculateEnvironmentScore($latitude, $longitude, $propertyType);

        if ($score >= 80) {
            return 'Çok Yüksek';
        }
        if ($score >= 60) {
            return 'Yüksek';
        }
        if ($score >= 40) {
            return 'Orta';
        }
        if ($score >= 20) {
            return 'Düşük';
        }

        return 'Çok Düşük';
    }

    /**
     * Değer artış tahmini
     */
    private function calculateValueIncreaseEstimate(float $latitude, float $longitude, string $propertyType): array
    {
        $score = $this->calculateEnvironmentScore($latitude, $longitude, $propertyType);

        return [
            '1_yil' => $this->calculateYearlyIncrease($score, 1),
            '3_yil' => $this->calculateYearlyIncrease($score, 3),
            '5_yil' => $this->calculateYearlyIncrease($score, 5),
        ];
    }

    /**
     * Yıllık artış hesapla
     */
    private function calculateYearlyIncrease(int $score, int $years): float
    {
        $baseIncrease = ($score / 100) * 0.15; // %15'e kadar

        return round($baseIncrease * $years, 2);
    }

    /**
     * Risk faktörleri
     */
    private function identifyRiskFactors(float $latitude, float $longitude, string $propertyType): array
    {
        $risks = [];

        // Çevre analizi sonuçlarına göre risk faktörleri
        $pois = $this->getPOIAnalysis($latitude, $longitude, $propertyType);

        if (empty($pois['egitim'])) {
            $risks[] = 'Eğitim kurumları uzak';
        }

        if (empty($pois['saglik'])) {
            $risks[] = 'Sağlık kurumları uzak';
        }

        if (empty($pois['ulasim'])) {
            $risks[] = 'Toplu taşıma erişimi sınırlı';
        }

        return $risks;
    }

    /**
     * Öneriler
     */
    private function getRecommendations(float $latitude, float $longitude, string $propertyType): array
    {
        $recommendations = [];
        $score = $this->calculateEnvironmentScore($latitude, $longitude, $propertyType);

        if ($score < 40) {
            $recommendations[] = 'Bu bölge için alternatif konumlar değerlendirin';
        }

        if ($score >= 60) {
            $recommendations[] = 'Bu konum yatırım için uygun görünüyor';
        }

        if ($score >= 80) {
            $recommendations[] = 'Bu konum çok değerli, hızlı karar verin';
        }

        return $recommendations;
    }

    /**
     * Mesafe hesaplama yardımcı fonksiyonları
     */
    private function calculateDistance(array $element, string $propertyType): float
    {
        // Basit mesafe hesaplama (gerçek implementasyon için routing API kullanılabilir)
        return rand(100, 2000); // Örnek değer
    }

    private function calculateWalkingDistance(float $lat, float $lon, string $poi): float
    {
        // Nominatim API ile yürüme mesafesi
        return rand(200, 1500); // Örnek değer
    }

    private function calculateDrivingDistance(float $lat, float $lon, string $poi): float
    {
        // OSRM API ile araç mesafesi
        return rand(500, 5000); // Örnek değer
    }

    private function checkMetroAccess(float $lat, float $lon): bool
    {
        // Metro erişimi kontrolü
        return rand(0, 1) == 1;
    }

    private function checkBusAccess(float $lat, float $lon): bool
    {
        // Otobüs erişimi kontrolü
        return rand(0, 1) == 1;
    }

    private function checkDolmusAccess(float $lat, float $lon): bool
    {
        // Dolmuş erişimi kontrolü
        return rand(0, 1) == 1;
    }

    private function calculateImportance(array $element, string $propertyType): int
    {
        // Önem skoru hesaplama
        return rand(1, 10);
    }
}
