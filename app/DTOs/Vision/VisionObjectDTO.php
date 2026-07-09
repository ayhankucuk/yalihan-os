<?php

namespace App\DTOs\Vision;

/**
 * Vision Object DTO — Sprint 6.4
 *
 * AI tarafından tespit edilen tek bir nesne/özellik.
 */
final class VisionObjectDTO
{
    public function __construct(
        public readonly string $type,         // oda, mobilya, amenity, ozellik, stil, manzara
        public readonly string $label,        // "Havuz", "Modern Mutfak"
        public readonly float $confidence,    // 0.0 – 1.0
        public readonly string $provider,      // "openai", "mock", "gemini"
        public readonly string $reason,        // Kullanıcıya gösterilebilir açıklama
        public readonly array $metadata = [], // Ek veriler (boyut, renk, malzeme vb.)
    ) {}

    public function toArray(): array
    {
        return [
            'type'       => $this->type,
            'label'      => $this->label,
            'confidence' => round($this->confidence, 3),
            'provider'   => $this->provider,
            'reason'     => $this->reason,
            'metadata'   => $this->metadata,
        ];
    }
}
