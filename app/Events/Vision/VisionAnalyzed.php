<?php

namespace App\Events\Vision;

use App\DTOs\Vision\VisionAnalysisDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Vision Analyzed Event — Sprint 6.4
 *
 * Bir fotoğraf AI Vision ile analiz edildiğinde fırlatılır.
 * Immutable: event verisi değiştirilemez.
 */
final class VisionAnalyzed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $ilan_id,
        public readonly int $fotograf_id,
        public readonly VisionAnalysisDTO $analysis,
        public readonly string $provider,
        public readonly float $latency_ms,
        public readonly ?string $trace_id = null,
    ) {}

    public function toArray(): array
    {
        return [
            'ilan_id'       => $this->ilan_id,
            'fotograf_id'   => $this->fotograf_id,
            'provider'      => $this->provider,
            'latency_ms'   => round($this->latency_ms, 2),
            'trace_id'      => $this->trace_id,
            'confidence'    => $this->analysis->overall_confidence,
            'ai_quality'    => $this->analysis->ai_quality_score,
            'final_room'    => $this->analysis->final_room_type,
            'rooms_found'   => count($this->analysis->rooms),
            'objects_found' => count($this->analysis->objects),
            'has_error'     => $this->analysis->hasError(),
        ];
    }
}
