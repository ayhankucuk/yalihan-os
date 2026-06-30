<?php

namespace App\UseCases\N8n\DTOs;

class AIContractDraftDTO
{
    public function __construct(
        public readonly string $contractType,
        public readonly string $content,
        public readonly ?int $propertyId = null,
        public readonly ?int $kisiId = null,
        public readonly string $aiModelUsed = 'anythingllm'
    ) {
    }

    public static function fromRequest(array $validated): self
    {
        return new self(
            contractType: $validated['contract_type'],
            content: $validated['content'],
            propertyId: $validated['property_id'] ?? null,
            kisiId: $validated['kisi_id'] ?? null,
            aiModelUsed: $validated['ai_model_used'] ?? 'anythingllm'
        );
    }
}
