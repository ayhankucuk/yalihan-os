<?php

namespace App\Events\Vision;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Metadata Extracted Event — Sprint 6.4
 *
 * Tüm fotoğraflardan metadata çıkarıldığında fırlatılır.
 * Immutable: event verisi değiştirilemez.
 */
final class MetadataExtracted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $ilan_id,
        public readonly int $analyzed_photo_count,
        public readonly array $aggregated_metadata,
        public readonly array $room_distribution,
        public readonly array $feature_summary,
        public readonly ?string $trace_id = null,
    ) {}

    public function toArray(): array
    {
        return [
            'ilan_id'              => $this->ilan_id,
            'analyzed_photo_count' => $this->analyzed_photo_count,
            'room_distribution'    => $this->room_distribution,
            'feature_summary'      => $this->feature_summary,
            'trace_id'             => $this->trace_id,
        ];
    }
}
