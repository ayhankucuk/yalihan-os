<?php

namespace App\Services\Hermes\Contracts;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Interface for retry policies.
 * Exponential backoff-ready structure.
 */
interface RetryPolicyInterface
{
    /**
     * Maximum number of retry attempts
     */
    public function getMaxAttempts(): int;

    /**
     * Calculate delay in seconds for a given attempt number (1-based)
     *
     * @param int $attempt 1-based attempt number
     * @return int seconds to wait before next retry
     */
    public function getDelaySeconds(int $attempt): int;

    /**
     * Check if should retry based on exception
     */
    public function shouldRetry(\Throwable $exception): bool;

    /**
     * Get backoff multiplier for exponential backoff
     */
    public function getBackoffMultiplier(): float;

    /**
     * Get base delay in seconds
     */
    public function getBaseDelaySeconds(): int;
}
