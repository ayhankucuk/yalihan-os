<?php

namespace App\UseCases\N8n\DTOs;

class AnalyzeMarketDTO
{
    public function __construct(
        public readonly string $location,
        public readonly float $m2,
        public readonly string $tip
    ) {
    }

    public static function fromRequest(array $validated): self
    {
        return new self(
            location: $validated['location'],
            m2: (float) $validated['m2'],
            tip: $validated['tip']
        );
    }
}
