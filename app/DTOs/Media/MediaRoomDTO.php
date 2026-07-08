<?php

namespace App\DTOs\Media;

/**
 * Room Detection DTO — Sprint 6.3
 *
 * Bir oda türünün tespit sonucunu taşır.
 *
 * Oda türleri:
 *   living_room | kitchen | bedroom | bathroom | pool
 *   garden | terrace | exterior | view | other
 */
final class MediaRoomDTO
{
    public function __construct(
        public readonly string $oda_turu,
        public readonly string $label,
        public readonly int $guven_skoru,
        public readonly int $fotograf_sayisi,
        public readonly array $fotograf_ids,
    ) {}

    public function toArray(): array
    {
        return [
            'oda_turu' => $this->oda_turu,
            'label' => $this->label,
            'guven_skoru' => $this->guven_skoru,
            'fotograf_sayisi' => $this->fotograf_sayisi,
            'fotograf_ids' => $this->fotograf_ids,
        ];
    }
}
