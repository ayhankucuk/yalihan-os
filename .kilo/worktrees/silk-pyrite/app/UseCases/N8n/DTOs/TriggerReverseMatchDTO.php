<?php

namespace App\UseCases\N8n\DTOs;

class TriggerReverseMatchDTO
{
    public function __construct(
        public readonly int $ilanId
    ) {
    }

    public static function fromRequest(array $validated): self
    {
        return new self(
            ilanId: $validated['ilan_id']
        );
    }
}
