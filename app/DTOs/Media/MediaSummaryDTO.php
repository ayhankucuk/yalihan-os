<?php

namespace App\DTOs\Media;

/**
 * Media Summary DTO — Sprint 6.3
 *
 * Workspace payload için optimize edilmiş özet DTO.
 */
final class MediaSummaryDTO
{
    public function __construct(
        public readonly string $health,
        public readonly int $health_score,
        public readonly int $quality_score,
        public readonly float $coverage,
        public readonly ?string $hero_image_url,
        public readonly array $detected_rooms,
        public readonly array $missing_rooms,
        public readonly int $total_photos,
    ) {}

    public function toArray(): array
    {
        return [
            'health' => $this->health,
            'health_score' => $this->health_score,
            'quality_score' => $this->quality_score,
            'coverage' => round($this->coverage, 2),
            'hero_image_url' => $this->hero_image_url,
            'detected_rooms' => $this->detected_rooms,
            'missing_rooms' => $this->missing_rooms,
            'total_photos' => $this->total_photos,
        ];
    }

    public static function empty(): self
    {
        return new self(
            health: 'MISSING',
            health_score: 0,
            quality_score: 0,
            coverage: 0.0,
            hero_image_url: null,
            detected_rooms: [],
            missing_rooms: [],
            total_photos: 0,
        );
    }
}
