<?php

namespace App\Events\Media;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * HeroImageSelected Event — Sprint 6.3
 *
 * Yeni bir kapak fotoğrafı seçildiğinde tetiklenir.
 * Replay-safe.
 */
class HeroImageSelected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $ilan_id,
        public readonly int $hero_fotograf_id,
        public readonly float $hero_skoru,
    ) {}
}
