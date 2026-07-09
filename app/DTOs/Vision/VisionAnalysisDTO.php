<?php

namespace App\DTOs\Vision;

/**
 * Vision Analysis DTO — Sprint 6.4
 *
 * Bir fotoğrafın AI Vision analiz sonucunu taşır.
 */
final class VisionAnalysisDTO
{
    /**
     * @param  VisionObjectDTO[]  $objects
     * @param  VisionObjectDTO[]  $rooms
     * @param  VisionObjectDTO[]  $furniture
     * @param  VisionObjectDTO[]  $amenities
     * @param  VisionObjectDTO[]  $luxuryFeatures
     * @param  VisionObjectDTO[]  $views
     * @param  VisionObjectDTO[]  $architecturalStyles
     */
    public function __construct(
        public readonly int $fotograf_id,
        public readonly array $objects,
        public readonly array $rooms,
        public readonly array $furniture,
        public readonly array $amenities,
        public readonly array $luxuryFeatures,
        public readonly array $views,
        public readonly array $architecturalStyles,
        // AI Quality
        public readonly int $ai_quality_score,     // 0–100
        public readonly array $ai_quality_breakdown,
        // Confidence fusion
        public readonly float $overall_confidence, // 0.0–1.0
        public readonly string $provider,
        // Rule engine override
        public readonly ?string $final_room_type,  // null = AI belirsiz
        public readonly float $fusion_confidence,  // AI + Rule birleşik
        // Metadata
        public readonly array $raw_response = [],
        public readonly ?string $error = null,
    ) {}

    public function toArray(): array
    {
        return [
            'fotograf_id'           => $this->fotograf_id,
            'ai_quality_score'      => $this->ai_quality_score,
            'ai_quality_breakdown'  => $this->ai_quality_breakdown,
            'overall_confidence'    => round($this->overall_confidence, 3),
            'provider'              => $this->provider,
            'final_room_type'       => $this->final_room_type,
            'fusion_confidence'     => round($this->fusion_confidence, 3),
            'objects'               => array_map(fn($o) => $o->toArray(), $this->objects),
            'rooms'                 => array_map(fn($r) => $r->toArray(), $this->rooms),
            'furniture'             => array_map(fn($f) => $f->toArray(), $this->furniture),
            'amenities'            => array_map(fn($a) => $a->toArray(), $this->amenities),
            'luxury_features'      => array_map(fn($l) => $l->toArray(), $this->luxuryFeatures),
            'views'                 => array_map(fn($v) => $v->toArray(), $this->views),
            'architectural_styles'  => array_map(fn($s) => $s->toArray(), $this->architecturalStyles),
            'raw_response'          => $this->raw_response,
            'error'                 => $this->error,
        ];
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Belirli bir tipteki tüm nesneleri döner.
     *
     * @return VisionObjectDTO[]
     */
    public function getByType(string $type): array
    {
        return match ($type) {
            'oda'          => $this->rooms,
            'mobilya'      => $this->furniture,
            'amenity'     => $this->amenities,
            'ozellik'     => $this->objects,
            'stil'        => $this->architecturalStyles,
            'manzara'     => $this->views,
            'lüks'        => $this->luxuryFeatures,
            default        => [],
        };
    }

    /**
     * En yüksek güvenilirlikli oda türünü döner.
     */
    public function topRoom(): ?VisionObjectDTO
    {
        if (empty($this->rooms)) {
            return null;
        }

        $top = $this->rooms[0];
        foreach ($this->rooms as $room) {
            if ($room->confidence > $top->confidence) {
                $top = $room;
            }
        }

        return $top;
    }
}
