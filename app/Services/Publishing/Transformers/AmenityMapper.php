<?php

namespace App\Services\Publishing\Transformers;

/**
 * Amenity Mapper — Sprint 6.5
 *
 * AI Vision detected_amenities'ı kanal-özgü özellik formatlarına dönüştürür.
 *
 * @rule Sadece mapping yapar — iş mantığı PublishingIntelligenceOrchestrator'da.
 */
class AmenityMapper
{
    /**
     * Airbnb amenity category mapping.
     * Returns Airbnb amenity ID'leri.
     *
     * @return string[]  Airbnb amenity ID'leri
     */
    public function toAirbnbAmenities(array $detectedAmenities): array
    {
        $airbnbAmenities = [];

        foreach ($detectedAmenities as $amenity) {
            $normalized = mb_strtolower(trim($amenity));
            $mapped = $this->airbnbAmenityMap()[$normalized] ?? null;
            if ($mapped && !in_array($mapped, $airbnbAmenities, true)) {
                $airbnbAmenities[] = $mapped;
            }
        }

        return $airbnbAmenities;
    }

    /**
     * Sahibinden özellik listesi.
     *
     * @return string[]
     */
    public function toSahibindenFeatures(array $detectedAmenities, array $detectedLuxury = []): array
    {
        $features = [];

        // Standart özellikler
        foreach ($detectedAmenities as $amenity) {
            $normalized = mb_strtolower(trim($amenity));
            $mapped = $this->sahibindenFeatureMap()[$normalized] ?? null;
            if ($mapped && !in_array($mapped, $features, true)) {
                $features[] = $mapped;
            }
        }

        // Lüks özellikler
        foreach ($detectedLuxury as $luxury) {
            $normalized = mb_strtolower(trim($luxury));
            $mapped = $this->sahibindenLuxuryMap()[$normalized] ?? null;
            if ($mapped && !in_array($mapped, $features, true)) {
                $features[] = $mapped;
            }
        }

        return $features;
    }

    /**
     * Hepsiemlak özellik listesi.
     *
     * @return string[]
     */
    public function toHepsiemlakFeatures(array $detectedAmenities, array $detectedLuxury = []): array
    {
        $features = [];

        foreach ($detectedAmenities as $amenity) {
            $normalized = mb_strtolower(trim($amenity));
            $mapped = $this->hepsiemlakFeatureMap()[$normalized] ?? null;
            if ($mapped && !in_array($mapped, $features, true)) {
                $features[] = $mapped;
            }
        }

        foreach ($detectedLuxury as $luxury) {
            $normalized = mb_strtolower(trim($luxury));
            $mapped = $this->hepsiemlakLuxuryMap()[$normalized] ?? null;
            if ($mapped && !in_array($mapped, $features, true)) {
                $features[] = $mapped;
            }
        }

        return $features;
    }

    // ─── Private maps ────────────────────────────────────────────────────────

    /** @return array<string, string> */
    private function airbnbAmenityMap(): array
    {
        return [
            // Havuz
            'havuz' => 'pool',
            'private pool' => 'pool',
            'swimming pool' => 'pool',
            // WiFi
            'wifi' => 'wifi',
            'kablosuz internet' => 'wifi',
            // Klima
            'klima' => 'air_conditioning',
            'air conditioning' => 'air_conditioning',
            // Otopark
            'otopark' => 'parking',
            'garaj' => 'parking',
            // Balkon
            'balkon' => 'balcony',
            // Deniz manzarası
            'deniz manzarası' => 'ocean_view',
            'sea view' => 'ocean_view',
            // Jakuzi
            'jakuzi' => 'hot_tub',
            // Güvenlik
            'güvenlik' => 'security',
            // Spor aletleri
            'spor aleti' => 'gym',
        ];
    }

    /** @return array<string, string> */
    private function sahibindenFeatureMap(): array
    {
        return [
            'havuz' => 'Havuz',
            'klima' => 'Klima',
            'otopark' => 'Otopark',
            'balkon' => 'Balkon',
            'deniz manzarası' => 'Deniz Manzarası',
            'güneşlenme terası' => 'Güneşlenme Terası',
            'güvenlik' => 'Güvenlik',
            'site içi' => 'Site İçi',
            'spor aleti' => 'Spor Salonu',
        ];
    }

    /** @return array<string, string> */
    private function sahibindenLuxuryMap(): array
    {
        return [
            'jakuzi' => 'Jakuzili',
            'hamam' => 'Hammamlı',
            'sauna' => 'Saunalı',
            'özel havuz' => 'Özel Havuz',
            'private pool' => 'Özel Havuz',
        ];
    }

    /** @return array<string, string> */
    private function hepsiemlakFeatureMap(): array
    {
        return [
            'havuz' => 'Havuz',
            'klima' => 'Klima',
            'otopark' => 'Otopark',
            'balkon' => 'Balkon',
            'deniz manzarası' => 'Deniz Manzarası',
            'site içi' => 'Site İçi',
            'güvenlik' => 'Güvenlik',
        ];
    }

    /** @return array<string, string> */
    private function hepsiemlakLuxuryMap(): array
    {
        return [
            'jakuzi' => 'Jakuzi',
            'hamam' => 'Hammam',
            'sauna' => 'Sauna',
            'özel havuz' => 'Özel Havuz',
        ];
    }
}
