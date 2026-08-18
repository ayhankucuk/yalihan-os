<?php

namespace App\Services\Concierge;

/**
 * AuthorityResult — Result of an authority policy evaluation.
 */
final readonly class AuthorityResult
{
    private function __construct(
        public bool $allowed,
        public ?string $denialReason = null,
        public ?string $denialCode = null,
    ) {}

    public static function allow(): self
    {
        return new self(allowed: true);
    }

    public static function deny(string $code, string $reason): self
    {
        return new self(
            allowed: false,
            denialCode: $code,
            denialReason: $reason,
        );
    }

    public function isDenied(): bool
    {
        return !$this->allowed;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function getEscalationReason(): string
    {
        return "[{$this->denialCode}] {$this->denialReason}";
    }
}
