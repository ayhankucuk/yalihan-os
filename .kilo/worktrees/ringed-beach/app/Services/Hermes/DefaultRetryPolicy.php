<?php

namespace App\Services\Hermes;

use App\Services\Hermes\Contracts\RetryPolicyInterface;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Default exponential backoff retry policy.
 * Configurable via config/hermes.php
 */
class DefaultRetryPolicy implements RetryPolicyInterface
{
    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly int $baseDelaySeconds = 10,
        private readonly float $multiplier = 2.0,
    ) {}

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getBaseDelaySeconds(): int
    {
        return $this->baseDelaySeconds;
    }

    public function getBackoffMultiplier(): float
    {
        return $this->multiplier;
    }

    /**
     * Exponential backoff: baseDelay * (multiplier ^ (attempt - 1))
     * Attempt 1: 10s, Attempt 2: 20s, Attempt 3: 40s
     */
    public function getDelaySeconds(int $attempt): int
    {
        if ($attempt < 1) {
            return 0;
        }
        return (int) ($this->baseDelaySeconds * pow($this->multiplier, $attempt - 1));
    }

    /**
     * Default: retry all exceptions
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        return true;
    }

    /**
     * Create from config
     */
    public static function fromConfig(): self
    {
        return new self(
            maxAttempts: config('hermes.retry.max_attempts', 3),
            baseDelaySeconds: config('hermes.retry.base_delay_seconds', 10),
            multiplier: config('hermes.retry.multiplier', 2.0),
        );
    }
}
