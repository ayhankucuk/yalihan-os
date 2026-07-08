<?php

namespace App\DTOs\Location;

/**
 * Geocoding Result DTO
 *
 * Nominatim veya AdresDB sonucunu taşır.
 */
final class GeocodingResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?float $lat,
        public readonly ?float $lng,
        public readonly string $source,        // 'nominatim' | 'adres_db' | 'manual' | 'none'
        public readonly ?string $displayName,  // Full formatted address
        public readonly ?string $rawData,      // JSON raw response
        public readonly ?string $error,
        public readonly bool $fromCache = false,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'source' => $this->source,
            'display_name' => $this->displayName,
            'raw_data' => $this->rawData,
            'error' => $this->error,
            'from_cache' => $this->fromCache,
        ];
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            lat: null,
            lng: null,
            source: 'none',
            displayName: null,
            rawData: null,
            error: $error,
        );
    }
}
