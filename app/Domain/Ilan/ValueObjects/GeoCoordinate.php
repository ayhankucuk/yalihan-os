<?php

namespace App\Domain\Ilan\ValueObjects;

use InvalidArgumentException;

/**
 * 📍 GeoCoordinate Value Object
 *
 * Sorumluluk: Enlem ve boylam koordinatlarını doğrular ve Türkiye / Bodrum
 * coğrafi sınır denetimlerini domain invariantı olarak uygular.
 */
final class GeoCoordinate
{
    // Türkiye Coğrafi Sınırları (Yaklaşık)
    private const TR_LAT_MIN = 35.5;
    private const TR_LAT_MAX = 42.5;
    private const TR_LNG_MIN = 25.5;
    private const TR_LNG_MAX = 45.0;

    public function __construct(
        public readonly float $lat,
        public readonly float $lng
    ) {
        if ($this->lat < -90.0 || $this->lat > 90.0) {
            throw new InvalidArgumentException("Geçersiz enlem (lat) değeri: {$this->lat}");
        }

        if ($this->lng < -180.0 || $this->lng > 180.0) {
            throw new InvalidArgumentException("Geçersiz boylam (lng) değeri: {$this->lng}");
        }
    }

    public static function fromArray(array $data): ?self
    {
        if (!isset($data['lat']) || !isset($data['lng'])) {
            return null;
        }

        return new self((float) $data['lat'], (float) $data['lng']);
    }

    public function isWithinTurkey(): bool
    {
        return $this->lat >= self::TR_LAT_MIN
            && $this->lat <= self::TR_LAT_MAX
            && $this->lng >= self::TR_LNG_MIN
            && $this->lng <= self::TR_LNG_MAX;
    }

    public function toArray(): array
    {
        return [
            'lat' => $this->lat,
            'lng' => $this->lng,
            'within_turkey' => $this->isWithinTurkey(),
        ];
    }
}
