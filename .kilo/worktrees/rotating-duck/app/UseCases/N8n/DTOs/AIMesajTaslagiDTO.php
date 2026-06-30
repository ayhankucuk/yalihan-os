<?php

namespace App\UseCases\N8n\DTOs;

class AIMesajTaslagiDTO
{
    public function __construct(
        public readonly int $communicationId,
        public readonly string $channel,
        public readonly string $content,
        public readonly string $aiModelUsed
    ) {
    }

    public static function fromRequest(array $validated): self
    {
        return new self(
            communicationId: $validated['communication_id'],
            channel: $validated['channel'],
            content: $validated['content'],
            aiModelUsed: $validated['ai_model_used'] ?? 'anythingllm'
        );
    }
}
