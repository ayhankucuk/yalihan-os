<?php

namespace App\DTOs\Vision;

/**
 * Publishing Media DTO — Sprint 6.4
 *
 * AI Vision çıktısını publishing için hazırlanmış formata dönüştürür.
 * PUBLISH ETMEZ — sadece output üretir.
 */
final class PublishingMediaDTO
{
    /**
     * @param  array<int, array{baslik: string, aciklama: string}>  $photoCaptions  key = fotograf_id
     * @param  array<int, int>                                       $photoOrder     sıralı fotograf_id dizisi
     * @param  array<string, mixed>                                   $roomMetadata   oda bazlı AI metadata
     */
    public function __construct(
        public readonly int $ilan_id,
        // Hero suggestion
        public readonly ?int $hero_fotograf_id,
        public readonly ?string $hero_reason,
        // Photo ordering
        public readonly array $photo_order,          // [10, 3, 7, 1, ...] — sıralı fotograf_id
        // Title hints (future AI title generation için)
        public readonly array $title_hints = [],     // ["Bodrum'un kalbinde lüks villa", ...]
        // Captions per photo
        public readonly array $photo_captions = [],   // [fotograf_id => ['baslik' => '...', 'aciklama' => '...']]
        // Room metadata per photo
        public readonly array $room_metadata = [],    // [fotograf_id => [...metadata...]]
        // Publishing readiness
        public readonly bool $is_publishing_ready = false,
        public readonly array $readiness_issues = [],
        // Aggregated features
        public readonly array $detected_rooms = [],
        public readonly array $detected_amenities = [],
        public readonly array $detected_luxury_features = [],
        // Vision scores
        public readonly int $vision_score = 0,       // 0–100 aggregate
        public readonly float $avg_ai_confidence = 0.0,
    ) {}

    public function toArray(): array
    {
        return [
            'ilan_id'               => $this->ilan_id,
            'hero_fotograf_id'      => $this->hero_fotograf_id,
            'hero_reason'           => $this->hero_reason,
            'photo_order'           => $this->photo_order,
            'title_hints'           => $this->title_hints,
            'photo_captions'        => $this->photo_captions,
            'room_metadata'         => $this->room_metadata,
            'is_publishing_ready'   => $this->is_publishing_ready,
            'readiness_issues'      => $this->readiness_issues,
            'detected_rooms'        => $this->detected_rooms,
            'detected_amenities'    => $this->detected_amenities,
            'detected_luxury_features' => $this->detected_luxury_features,
            'vision_score'          => $this->vision_score,
            'avg_ai_confidence'     => round($this->avg_ai_confidence, 3),
        ];
    }

    /**
     * Belirli bir fotoğrafın publishing bilgilerini döner.
     */
    public function forPhoto(int $fotografId): array
    {
        return [
            'caption'      => $this->photo_captions[$fotografId] ?? null,
            'room_metadata' => $this->room_metadata[$fotografId] ?? null,
            'sira'        => array_search($fotografId, $this->photo_order) !== false // context7-ignore: DTO presentation key
                ? (int) array_search($fotografId, $this->photo_order)
                : null,
            'is_hero'      => $this->hero_fotograf_id === $fotografId,
        ];
    }

    /**
     * Array'den DTO oluşturur — Publishing Intelligence pipeline için.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ilan_id: $data['ilan_id'] ?? 0,
            hero_fotograf_id: $data['hero_fotograf_id'] ?? null,
            hero_reason: $data['hero_reason'] ?? null,
            photo_order: $data['photo_order'] ?? [],
            title_hints: $data['title_hints'] ?? [],
            photo_captions: $data['photo_captions'] ?? [],
            room_metadata: $data['room_metadata'] ?? [],
            is_publishing_ready: $data['is_publishing_ready'] ?? false,
            readiness_issues: $data['readiness_issues'] ?? [],
            detected_rooms: $data['detected_rooms'] ?? [],
            detected_amenities: $data['detected_amenities'] ?? [],
            detected_luxury_features: $data['detected_luxury_features'] ?? [],
            vision_score: $data['vision_score'] ?? 0,
            avg_ai_confidence: $data['avg_ai_confidence'] ?? 0.0,
        );
    }
}
