<?php

namespace App\Application\AI\DTOs;

final class CortexUsage
{
    public function __construct(
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly array $meta = [],
    ) {}
}
