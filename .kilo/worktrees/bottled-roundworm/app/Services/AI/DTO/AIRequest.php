<?php

namespace App\Services\AI\DTO;

/**
 * 🛡️ SAB SEALED
 * AI Request DTO (Contract Layer).
 */
final readonly class AIRequest
{
    public function __construct(
        public string $purpose,
        public string $model,
        public array $messages,
        public string $systemPrompt,
        public \App\Application\Shared\DTOs\TenantContext $tenantContext,
        public int $maxTokens = 1024,
        public float $temperature = 0.2,
        public array $metadata = []
    ) {}
}
