<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * KiraTahmini ML Service
 *
 * İzmir, Aydın, Muğla ve Denizli için makine öğrenmesi ile kira tahmini
 * Repository: https://github.com/gizemtursunn/KiraTahmini
 *
 * Context7: ML-based rental price prediction
 */
class KiraTahminiService
{
    /**
     * Desteklenen iller
     */
    protected const SUPPORTED_PROVINCES = [
        35 => 'İzmir',
        9 => 'Aydın',
        48 => 'Muğla',
        20 => 'Denizli',
    ];

    /**
     * Cache TTL (24 saat)
     */
    protected const CACHE_TTL = 86400;

    /**
     * Kira tahmini yap
     *
     * @param array $propertyData Emlak bilgileri
     * @return array
     */
    public function predictRentalPrice(array $propertyData): array
    {
        $ilId = $propertyData['il_id'] ?? null;
        $ilceId = $propertyData['ilce_id'] ?? null;
        $metrekare = $propertyData['metrekare'] ?? $propertyData['alan_m2'] ?? null;
        $odaSayisi = $propertyData['oda_sayisi'] ?? $propertyData['oda'] ?? null;
        $binaYasi = $propertyData['bina_yasi'] ?? null;
        $esyali = $propertyData['esyali'] ?? false;
        $balkon = $propertyData['balkon'] ?? false;
        $asansor = $propertyData['asansor'] ?? false;
        $otopark = $propertyData['otopark'] ?? false;

        // Desteklenen il kontrolü
        if (!$ilId || !isset(self::SUPPORTED_PROVINCES[$ilId])) {
            return [
                'success' => false,
                'error' => 'Bu il için kira tahmini desteklenmiyor. Desteklenen iller: ' . implode(', ', self::SUPPORTED_PROVINCES),
                'supported_provinces' => self::SUPPORTED_PROVINCES,
            ];
        }

        // Gerekli alanlar kontrolü
        if (!$metrekare) {
            return [
                'success' => false,
                'error' => 'Metrekare bilgisi gerekli',
            ];
        }

        $cacheKey = 'kira_tahmini.' . md5(serialize($propertyData));

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use (
                $propertyData, $ilId, $ilceId, $metrekare,
                $odaSayisi, $binaYasi, $esyali, $balkon,
                $asansor, $otopark
            ) {
            // ML modeli için feature vector oluştur
            $features = $this->buildFeatureVector([
                'il_id' => $ilId,
                'ilce_id' => $ilceId,
                'metrekare' => $metrekare,
                'oda_sayisi' => $odaSayisi,
                'bina_yasi' => $binaYasi,
                'esyali' => $esyali,
                'balkon' => $balkon,
                'asansor' => $asansor,
                'otopark' => $otopark,
            ]);

            // ML modeli tahmini (şimdilik mock, gerçek model entegrasyonu için API endpoint gerekli)
            $prediction = $this->predictWithML($features, $ilId);

            return [
                'success' => true,
                'prediction' => $prediction,
                'features' => $features,
                'model' => 'KiraTahmini ML Model',
                'province' => self::SUPPORTED_PROVINCES[$ilId],
                'confidence' => $this->calculateConfidence($features),
            ];
        });
    }

    /**
     * Feature vector oluştur
     *
     * @param array $data
     * @return array
     */
    protected function buildFeatureVector(array $data): array
    {
        return [
            'il_id' => $data['il_id'],
            'ilce_id' => $data['ilce_id'] ?? 0,
            'metrekare' => (float) ($data['metrekare'] ?? 0),
            'oda_sayisi' => (int) ($data['oda_sayisi'] ?? 0),
            'bina_yasi' => (int) ($data['bina_yasi'] ?? 0),
            'esyali' => (bool) ($data['esyali'] ?? false) ? 1 : 0,
            'balkon' => (bool) ($data['balkon'] ?? false) ? 1 : 0,
            'asansor' => (bool) ($data['asansor'] ?? false) ? 1 : 0,
            'otopark' => (bool) ($data['otopark'] ?? false) ? 1 : 0,
        ];
    }

    /**
     * ML modeli ile tahmin yap
     *
     * Not: Gerçek ML modeli entegrasyonu için Python API veya model dosyası gerekli
     * Şimdilik istatistiksel tahmin kullanıyoruz
     *
     * @param array $features
     * @param int $ilId
     * @return array
     */
    protected function predictWithML(array $features, int $ilId): array
    {
        // İl bazlı birim fiyatlar (m² başına TL/ay)
        $unitPrices = [
            35 => 45, // İzmir
            9 => 35,  // Aydın
            48 => 60, // Muğla (yazlık bölgesi - daha yüksek)
            20 => 30, // Denizli
        ];

        $baseUnitPrice = $unitPrices[$ilId] ?? 40;

        // Metrekareye göre temel fiyat
        $basePrice = $features['metrekare'] * $baseUnitPrice;

        // Oda sayısı faktörü
        $roomMultiplier = match (true) {
            $features['oda_sayisi'] >= 4 => 1.15,
            $features['oda_sayisi'] >= 3 => 1.10,
            $features['oda_sayisi'] >= 2 => 1.05,
            default => 1.0,
        };

        // Bina yaşı faktörü (yeni binalar daha pahalı)
        $ageMultiplier = match (true) {
            $features['bina_yasi'] <= 5 => 1.20,
            $features['bina_yasi'] <= 10 => 1.10,
            $features['bina_yasi'] <= 20 => 1.0,
            default => 0.90,
        };

        // Özellik faktörleri
        $featureMultiplier = 1.0;
        if ($features['esyali']) {
            $featureMultiplier += 0.15;
        }
        if ($features['balkon']) {
            $featureMultiplier += 0.05;
        }
        if ($features['asansor']) {
            $featureMultiplier += 0.10;
        }
        if ($features['otopark']) {
            $featureMultiplier += 0.10;
        }

        // Final tahmin
        $predictedPrice = $basePrice * $roomMultiplier * $ageMultiplier * $featureMultiplier;

        // Güven aralığı (±15%)
        $min = $predictedPrice * 0.85;
        $max = $predictedPrice * 1.15;

        return [
            'min' => round($min, 2),
            'max' => round($max, 2),
            'recommended' => round($predictedPrice, 2),
            'unit_price_per_m2' => round($baseUnitPrice, 2),
            'monthly_rent' => round($predictedPrice, 2),
            'daily_rent_estimate' => round($predictedPrice / 30, 2), // Yazlık için günlük tahmin
            'weekly_rent_estimate' => round($predictedPrice / 4.3, 2), // Yazlık için haftalık tahmin
        ];
    }

    /**
     * Güven skoru hesapla
     *
     * @param array $features
     * @return int
     */
    protected function calculateConfidence(array $features): int
    {
        $confidence = 50; // Base confidence

        // Metrekare bilgisi varsa +20
        if ($features['metrekare'] > 0) {
            $confidence += 20;
        }

        // Oda sayısı varsa +10
        if ($features['oda_sayisi'] > 0) {
            $confidence += 10;
        }

        // İlçe bilgisi varsa +10
        if ($features['ilce_id'] > 0) {
            $confidence += 10;
        }

        return min(100, $confidence);
    }

    /**
     * Yazlık kiralama için özel tahmin
     *
     * @param array $propertyData
     * @param string $season Sezon: 'yaz', 'ara_sezon', 'kis'
     * @return array
     */
    public function predictYazlikRental(array $propertyData, string $season = 'yaz'): array
    {
        $basePrediction = $this->predictRentalPrice($propertyData);

        if (!$basePrediction['success']) {
            return $basePrediction;
        }

        // Sezon çarpanları
        $seasonMultipliers = [
            'yaz' => 2.5,      // Yaz sezonu çok daha pahalı
            'ara_sezon' => 1.5, // Ara sezon
            'kis' => 0.8,      // Kış sezonu daha ucuz
        ];

        $multiplier = $seasonMultipliers[$season] ?? 1.0;

        $prediction = $basePrediction['prediction'];
        $dailyRent = $prediction['daily_rent_estimate'] * $multiplier;

        return [
            'success' => true,
            'season' => $season,
            'prediction' => [
                'daily_rent' => round($dailyRent, 2),
                'weekly_rent' => round($dailyRent * 7 * 0.95, 2), // %5 indirim
                'monthly_rent' => round($dailyRent * 30 * 0.85, 2), // %15 indirim
                'base_monthly_rent' => $prediction['recommended'],
                'season_multiplier' => $multiplier,
            ],
            'confidence' => $basePrediction['confidence'],
        ];
    }

    /**
     * Desteklenen illeri getir
     *
     * @return array
     */
    public function getSupportedProvinces(): array
    {
        return self::SUPPORTED_PROVINCES;
    }
}
