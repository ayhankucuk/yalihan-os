<?php

namespace App\DTOs\Media;

/**
 * Media Photo Quality DTO — Sprint 6.3
 *
 * Tek bir fotoğrafın kalite + oda tespit sonucunu taşır.
 */
final class MediaPhotoDTO
{
    public function __construct(
        public readonly int $fotograf_id,
        public readonly ?string $oda_turu,
        public readonly int $oda_guven_skoru,
        public readonly int $kalite_puani,
        public readonly float $hero_skoru,
        public readonly array $kalite_ayrinti,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->fotograf_id,
            'oda_turu' => $this->oda_turu,
            'oda_guven_skoru' => $this->oda_guven_skoru,
            'kalite_puani' => $this->kalite_puani,
            'hero_skoru' => $this->hero_skoru,
            'kalite_ayrinti' => $this->kalite_ayrinti,
        ];
    }
}
