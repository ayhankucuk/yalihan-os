<?php

namespace App\Services\Publishing\Transformers;

use App\Models\Ilan;
use App\DTOs\Vision\PublishingMediaDTO;

/**
 * Room Type Mapper — Sprint 6.5
 *
 * AI Vision detected_rooms + Ilan verilerini kanal kategori/konum eşleşmelerine dönüştürür.
 *
 * @rule Sadece mapping yapar — iş mantığı PublishingIntelligenceOrchestrator'da.
 */
class RoomTypeMapper
{
    /**
     * Airbnb room type mapping.
     *
     * @return array{category: string, space_type: string, property_type: string}
     */
    public function forAirbnb(Ilan $ilan, ?PublishingMediaDTO $media = null): array
    {
        $detectedRooms = $media?->detected_rooms ?? [];

        // AI Vision detected rooms → Airbnb space type
        $spaceType = $this->detectAirbnbSpaceType($ilan, $detectedRooms);

        // Kategori → Airbnb property type
        $propertyType = $this->mapToAirbnbPropertyType($ilan);

        // Oda sayısı → Airbnb rooms
        $bedrooms = $this->extractBedrooms($ilan);

        return [
            'space_type' => $spaceType,
            'property_type' => $propertyType,
            'bedrooms' => $bedrooms,
            'bathrooms' => $ilan->banyo_sayisi ?? null,
            'beds' => $ilan->yatak_odasi_sayisi ?? null,
        ];
    }

    /**
     * Sahibinden oda/konum eşleşmesi.
     *
     * @return array{kategori: string, oda: string, tip: string}
     */
    public function forSahibinden(Ilan $ilan, ?PublishingMediaDTO $media = null): array
    {
        $detectedRooms = $media?->detected_rooms ?? [];

        // AI Vision → Sahibinden kategori
        $kategori = $ilan->altKategori?->adi ?? $ilan->anaKategori?->adi ?? 'Konut';
        $oda = $this->mapToSahibindenRoom($ilan);
        $tip = $this->detectSahibindenType($ilan, $detectedRooms);

        return [
            'kategori' => $kategori,
            'oda' => $oda,
            'tip' => $tip,
        ];
    }

    /**
     * Hepsiemlak oda/konum eşleşmesi.
     *
     * @return array{kategori: string, oda: string, tip: string}
     */
    public function forHepsiemlak(Ilan $ilan, ?PublishingMediaDTO $media = null): array
    {
        $kategori = $ilan->altKategori?->adi ?? $ilan->anaKategori?->adi ?? 'Konut';
        $oda = $this->mapToHepsiemlakRoom($ilan);

        return [
            'kategori' => $kategori,
            'oda' => $oda,
            'tip' => $this->detectHepsiemlakType($ilan),
        ];
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function detectAirbnbSpaceType(Ilan $ilan, array $detectedRooms): string
    {
        $normalizedRooms = array_map(fn($r) => mb_strtolower($r), $detectedRooms);

        // AI Vision'da "villa" veya "müstakil" tespit edilmişse
        if ($this->arrayAny($normalizedRooms, fn($r) => str_contains($r, 'villa') || str_contains($r, 'müstakil'))) {
            return 'entire_villa';
        }

        // Havuzlu = entire house
        if ($this->arrayAny($normalizedRooms, fn($r) => str_contains($r, 'havuz') || str_contains($r, 'pool'))) {
            return 'entire_house';
        }

        // Oda tipi — mevcut verilerden
        if ($ilan->islem_tipi === 'kiralama') {
            return 'entire_house';
        }

        return 'entire_apartment';
    }

    private function mapToAirbnbPropertyType(Ilan $ilan): string
    {
        $kategori = mb_strtolower($ilan->altKategori?->adi ?? $ilan->anaKategori?->adi ?? '');

        return match (true) {
            str_contains($kategori, 'villa') => 'Villa',
            str_contains($kategori, 'daire') => 'House',
            str_contains($kategori, 'rezidans') => 'Condominium',
            str_contains($kategori, 'bungalov') => 'Bungalow',
            default => 'House',
        };
    }

    private function extractBedrooms(Ilan $ilan): ?int
    {
        // Bodrum emlak genelde oda+salon olarak gelir
        $kategori = $ilan->altKategori?->adi ?? '';

        if (preg_match('/(\d+)\s*\+/', $kategori, $matches)) {
            return (int) $matches[1];
        }

        return $ilan->yatak_odasi_sayisi ?? null;
    }

    private function detectSahibindenType(Ilan $ilan, array $detectedRooms): string
    {
        $normalizedRooms = array_map(fn($r) => mb_strtolower($r), $detectedRooms);

        if ($this->arrayAny($normalizedRooms, fn($r) => str_contains($r, 'villa') || str_contains($r, 'müstakil'))) {
            return 'Villa / Müstakil';
        }

        return 'Daire';
    }

    private function detectHepsiemlakType(Ilan $ilan): string
    {
        $kategori = mb_strtolower($ilan->altKategori?->adi ?? '');

        return match (true) {
            str_contains($kategori, 'villa') => 'Villa',
            str_contains($kategori, 'daire') => 'Daire',
            str_contains($kategori, 'bungalov') => 'Bungalow',
            default => 'Konut',
        };
    }

    private function mapToSahibindenRoom(Ilan $ilan): string
    {
        $kategori = $ilan->altKategori?->adi ?? $ilan->anaKategori?->adi ?? '';

        if (preg_match('/(\d+)\s*\+\s*(\d+)/', $kategori, $matches)) {
            return "{$matches[1]}+{$matches[2]}";
        }

        if (preg_match('/(\d+)/', $kategori, $matches)) {
            return $matches[1];
        }

        return 'Bilgi yok';
    }

    private function mapToHepsiemlakRoom(Ilan $ilan): string
    {
        return $this->mapToSahibindenRoom($ilan);
    }

    private function arrayAny(array $array, callable $callback): bool
    {
        foreach ($array as $value) {
            if ($callback($value)) {
                return true;
            }
        }
        return false;
    }
}
