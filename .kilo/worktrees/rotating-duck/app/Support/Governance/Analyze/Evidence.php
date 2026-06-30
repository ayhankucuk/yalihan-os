<?php

declare(strict_types=1);

namespace App\Support\Governance\Analyze;

/**
 * Single evidence item for a finding. Immutable value object.
 */
final class Evidence
{
    public function __construct(
        public readonly string $file,
        public readonly ?int $line = null,
        public readonly ?string $snippet = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter(
            [
                'file' => $this->file,
                'line' => $this->line,
                'snippet' => $this->snippet,
            ],
            static fn ($v) => $v !== null,
        );
    }
}
