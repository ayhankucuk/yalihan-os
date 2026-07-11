<?php

declare(strict_types=1);

namespace App\Services\Location;

use App\Exceptions\RealityCheckException;

/**
 * LocationValidationCapability
 *
 * Sprint 6.2: Validates that coordinate pairs fall within Muğla province geo-boundaries.
 */
class LocationValidationCapability
{
    // Muğla boundary constraints
    private const MIN_LAT = 36.1200;
    private const MAX_LAT = 37.3500;
    private const MIN_LNG = 26.2500;
    private const MAX_LNG = 29.7500;

    /**
     * Validate coordinate boundaries.
     *
     * @param float $lat
     * @param float $lng
     * @throws RealityCheckException
     */
    public function validate(float $lat, float $lng): void
    {
        if ($lat === 0.0 && $lng === 0.0) {
            throw new RealityCheckException('Geçersiz koordinat verisi (0, 0) tespit edildi.');
        }

        if ($lat < self::MIN_LAT || $lat > self::MAX_LAT || $lng < self::MIN_LNG || $lng > self::MAX_LNG) {
            throw new RealityCheckException(
                sprintf(
                    'Koordinat değerleri Muğla il sınırları dışındadır (Lat: %s, Lng: %s).',
                    $lat,
                    $lng
                )
            );
        }
    }

    /**
     * Check coordinates without throwing exceptions.
     */
    public function isValid(float $lat, float $lng): bool
    {
        /** @sab-ignore-catch */
        try {
            $this->validate($lat, $lng);
            return true;
        } catch (RealityCheckException) {
            return false;
        }
    }
}
