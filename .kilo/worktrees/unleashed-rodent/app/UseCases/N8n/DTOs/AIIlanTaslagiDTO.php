<?php

namespace App\UseCases\N8n\DTOs;

class AIIlanTaslagiDTO
{
    public function __construct(
        public readonly int $danismanId,
        public readonly array $data,
        public readonly array $aiResponse,
        public readonly string $aiModelUsed,
        public readonly string $aiPromptVersion
    ) {
    }

    public static function fromRequest(array $validated): self
    {
        return new self(
            danismanId: $validated['danisman_id'],
            data: $validated['data'],
            aiResponse: $validated['ai_response'],
            aiModelUsed: $validated['ai_model_used'] ?? 'anythingllm',
            aiPromptVersion: $validated['ai_prompt_version'] ?? '1.0'
        );
    }
}
