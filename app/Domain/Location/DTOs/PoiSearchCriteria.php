<?php

namespace App\Domain\Location\DTOs;

use InvalidArgumentException;

/**
 * 📍 POI Arama Kriteri DTO
 *
 * Controller'dan gelen validated input'u domain katmanına taşıyan value object.
 * Immuttable: tüm setter'lar yeni instance döndürür.
 */
final class PoiSearchCriteria
{
    public const VALID_CATEGORIES = ['arsa', 'villa', 'apartman', 'isyeri'];
    public const VALID_RADIUS_MIN = 0.5;
    public const VALID_RADIUS_MAX = 5.0;

    /**
     * @param float             $lat        Enlem (-90..90)
     * @param float             $lng        Boylam (-180..180)
     * @param float             $radiusKm   Arama yarıçapı (km, 0.5..5)
     * @param string|null       $kategori   İlan kategorisi (arsa|villa|apartman|isyeri)
     * @param array<string>|null $poiTypes Manuel POI tipi filtresi (poi_turu değerleri)
     */
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
        public readonly float $radiusKm = 2.0,
        public readonly ?string $kategori = null,
        public readonly ?array $poiTypes = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->lat < -90 || $this->lat > 90) {
            throw new InvalidArgumentException("Lat must be between -90 and 90, got {$this->lat}");
        }
        if ($this->lng < -180 || $this->lng > 180) {
            throw new InvalidArgumentException("Lng must be between -180 and 180, got {$this->lng}");
        }
        if ($this->radiusKm < self::VALID_RADIUS_MIN || $this->radiusKm > self::VALID_RADIUS_MAX) {
            throw new InvalidArgumentException(
                "Radius must be between " . self::VALID_RADIUS_MIN . " and " . self::VALID_RADIUS_MAX . " km, got {$this->radiusKm}"
            );
        }
        if ($this->kategori !== null && !in_array($this->kategori, self::VALID_CATEGORIES, true)) {
            throw new InvalidArgumentException(
                "Kategori must be one of: " . implode(', ', self::VALID_CATEGORIES) . ", got '{$this->kategori}'"
            );
        }
    }

    /**
     * Factory: Controller validated array'inden oluştur.
     *
     * @param array{lat: float, lng: float, kategori?: string|null, radius_km?: float} $validated
     */
    public static function fromValidatedRequest(array $validated): self
    {
        return new self(
            lat: (float) $validated['lat'],
            lng: (float) $validated['lng'],
            radiusKm: (float) ($validated['radius_km'] ?? 2.0),
            kategori: $validated['kategori'] ?? null,
        );
    }

    /**
     * Kategori bazlı POI tipi filtrelerini döndür.
     *
     * Legacy davranış: her kategori hangi poi_turu değerlerini istiyor.
     * Bu mapping Strangler Fig sürecinde domain katmanına taşındı.
     *
     * @return array<string>
     */
    public function getPoiTypesByKategori(): array
    {
        return match ($this->kategori) {
            'arsa' => [
                'highway',
                'amenity.parking',
                'amenity.fuel',
                'railway.station',
            ],
            'villa' => [
                'amenity.school',
                'amenity.hospital',
                'amenity.restaurant',
                'shop',
                'park',
            ],
            'apartman' => [
                'amenity.school',
                'amenity.hospital',
                'shop',
                'amenity.parking',
                'park',
            ],
            'isyeri' => [
                'amenity.bank',
                'amenity.restaurant',
                'office',
                'shop',
                'highway',
            ],
            default => [
                'amenity',
                'shop',
                'park',
                'school',
                'hospital',
            ],
        };
    }
}
