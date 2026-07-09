<?php

namespace App\DTOs\Publishing;

/**
 * Channel Readiness Item — tek kanal değerlendirmesi.
 *
 * @final Bu sınıf ayrı bir dosyada tanımlanmalıdır.
 */
final class ChannelReadinessItem
{
    /**
     * @param  string[]  $missingFields  Eksik zorunlu alanlar
     * @param  string[]  $warnings       Uyarılar (kritik değil)
     */
    public function __construct(
        public readonly string $channel,
        public readonly bool $isReady,
        public readonly array $missingFields = [],
        public readonly array $warnings = [],
        public readonly int $score = 0,  // 0–100
    ) {}

    public function toArray(): array
    {
        return [
            'channel' => $this->channel,
            'is_ready' => $this->isReady,
            'missing_fields' => $this->missingFields,
            'warnings' => $this->warnings,
            'score' => $this->score,
        ];
    }

    public function isReady(): bool
    {
        return $this->isReady;
    }
}
