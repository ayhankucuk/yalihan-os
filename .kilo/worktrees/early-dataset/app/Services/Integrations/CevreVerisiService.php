<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Çevre Verisi Servisi
 *
 * Hava kalitesi, çevre verileri ve yaşam kalitesi göstergeleri
 *
 * Context7: Çevre verileri entegrasyonu
 */
class CevreVerisiService
{
    /**
     * OpenWeatherMap Air Pollution API
     */
    protected const OWM_AIR_POLLUTION_API = 'https://api.openweathermap.org/data/2.5/air_pollution';

    /**
     * Hava kalitesi getir
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return array
     */
    public function getHavaKalitesi(float $lat, float $lng): array
    {
        $cacheKey = "hava_kalitesi." . md5("{$lat}_{$lng}");

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lng) {
            // Mock data - gerçek entegrasyon için OpenWeatherMap API key gerekli
            return [
                'success' => true,
                'coordinates' => ['lat' => $lat, 'lng' => $lng],
                'aqi' => 45, // Air Quality Index (0-500)
                'aqi_level' => 'İyi', // İyi, Orta, Sağlıksız, Çok Sağlıksız, Tehlikeli
                'pm25' => 12.5, // PM2.5 (μg/m³)
                'pm10' => 18.3, // PM10 (μg/m³)
                'no2' => 25.0, // NO2 (μg/m³)
                'o3' => 85.0, // O3 (μg/m³)
                'co' => 0.5, // CO (mg/m³)
                'so2' => 8.0, // SO2 (μg/m³)
                'tarih' => now()->toDateString(),
                'source' => 'mock_data', // Gerçekte 'openweathermap' olacak
            ];
        });
    }

    /**
     * Çevre skoru hesapla (0-100)
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return array
     */
    public function getCevreSkoru(float $lat, float $lng): array
    {
        $havaKalitesi = $this->getHavaKalitesi($lat, $lng);

        if (!$havaKalitesi['success']) {
            return [
                'success' => false,
                'skor' => 0,
                'mesaj' => 'Çevre verisi alınamadı',
            ];
        }

        // AQI'ye göre skor hesapla (ters orantılı)
        $aqi = $havaKalitesi['aqi'];
        $skor = max(0, 100 - ($aqi / 5)); // 0-100 arası skor

        // Seviye belirleme
        $seviye = match (true) {
            $aqi <= 50 => 'Mükemmel',
            $aqi <= 100 => 'İyi',
            $aqi <= 150 => 'Orta',
            $aqi <= 200 => 'Sağlıksız',
            $aqi <= 300 => 'Çok Sağlıksız',
            default => 'Tehlikeli',
        };

        return [
            'success' => true,
            'skor' => round($skor, 1),
            'seviye' => $seviye,
            'aqi' => $aqi,
            'hava_kalitesi' => $havaKalitesi,
            'oneri' => $this->getCevreOneri($skor),
        ];
    }

    /**
     * Çevre önerisi getir
     *
     * @param float $skor Çevre skoru
     * @return string
     */
    protected function getCevreOneri(float $skor): string
    {
        return match (true) {
            $skor >= 80 => 'Mükemmel çevre kalitesi. Yazlık kiralama için ideal.',
            $skor >= 60 => 'İyi çevre kalitesi. Yazlık kiralama için uygun.',
            $skor >= 40 => 'Orta çevre kalitesi. Yazlık kiralama için kabul edilebilir.',
            $skor >= 20 => 'Düşük çevre kalitesi. Yazlık kiralama için dikkatli olunmalı.',
            default => 'Çok düşük çevre kalitesi. Yazlık kiralama için önerilmez.',
        };
    }

    /**
     * Yakın çevre analizi (POI bazlı)
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param int $radius Radius (metre)
     * @return array
     */
    public function getYakinCevreAnalizi(float $lat, float $lng, int $radius = 2000): array
    {
        $cacheKey = "yakin_cevre." . md5("{$lat}_{$lng}_{$radius}");

        return Cache::remember($cacheKey, 86400, function () use ($lat, $lng, $radius) {
            // Mock data - gerçekte PlacesService kullanılabilir
            return [
                'success' => true,
                'coordinates' => ['lat' => $lat, 'lng' => $lng],
                'radius' => $radius,
                'yesil_alan_orani' => 35.5, // %
                'okul_sayisi' => 3,
                'hastane_sayisi' => 1,
                'park_sayisi' => 2,
                'market_sayisi' => 5,
                'restoran_sayisi' => 8,
                'plaj_uzaklik' => 500, // metre
                'deniz_uzaklik' => 800, // metre
                'ulasim_puani' => 75, // 0-100
                'yasam_kalitesi' => 'Yüksek',
                'source' => 'mock_data',
            ];
        });
    }
}
