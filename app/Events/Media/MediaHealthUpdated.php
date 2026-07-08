<?php

namespace App\Events\Media;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MediaHealthUpdated Event — Sprint 6.3
 *
 * Media health skoru değiştiğinde tetiklenir.
 * Replay-safe: eski ve yeni skor kaydedilir.
 */
class MediaHealthUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $ilan_id,
        public readonly int $old_score,
        public readonly int $new_score,
    ) {}
}
