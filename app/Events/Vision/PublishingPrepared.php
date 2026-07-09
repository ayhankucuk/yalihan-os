<?php

namespace App\Events\Vision;

use App\DTOs\Vision\PublishingMediaDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Publishing Prepared Event — Sprint 6.4
 *
 * AI Vision çıktısı publishing için hazırlandığında fırlatılır.
 * Immutable: event verisi değiştirilemez.
 *
 * NOT: Bu event publishing'i TETIKLAMAZ. Sadece hazırlık tamamlandığını işaretler.
 */
final class PublishingPrepared
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $ilan_id,
        public readonly PublishingMediaDTO $media,
        public readonly bool $is_ready,
        public readonly array $readiness_issues,
        public readonly ?string $trace_id = null,
    ) {}

    public function toArray(): array
    {
        return [
            'ilan_id'           => $this->ilan_id,
            'hero_fotograf_id'  => $this->media->hero_fotograf_id,
            'photo_count'       => count($this->media->photo_order),
            'is_ready'          => $this->is_ready,
            'readiness_issues'  => $this->readiness_issues,
            'vision_score'      => $this->media->vision_score,
            'avg_confidence'    => $this->media->avg_ai_confidence,
            'detected_rooms'    => $this->media->detected_rooms,
            'trace_id'          => $this->trace_id,
        ];
    }
}
