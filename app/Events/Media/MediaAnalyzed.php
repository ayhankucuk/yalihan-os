<?php

namespace App\Events\Media;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MediaAnalyzed Event — Sprint 6.3
 *
 * Bir ilanın tüm fotoğrafları analiz edildiğinde tetiklenir.
 * Replay-safe: event her tetiklendiğinde yeniden kaydedilir.
 */
class MediaAnalyzed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $ilan_id,
        public readonly int $media_health_score,
        public readonly int $toplam_fotograf,
        public readonly ?int $hero_fotograf_id,
        public readonly array $eksik_odalar,
        public readonly int $media_quality_score,
    ) {}
}
