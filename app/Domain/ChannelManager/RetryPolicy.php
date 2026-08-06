<?php

namespace App\Domain\ChannelManager;

/**
 * RetryPolicy — Retry configuration for sync operations
 *
 * CHANNEL_MANAGER Wave 1: Retry Engine
 *
 * Defines retry behavior for failed sync operations.
 * Uses exponential backoff to avoid overwhelming external channels.
 */
final class RetryPolicy
{
    public const MAX_ATTEMPTS = 3;
    public const INITIAL_DELAY_MS = 1000;    // 1 second
    public const MAX_DELAY_MS = 60000;       // 1 minute
    public const BACKOFF_MULTIPLIER = 2.0;  // Exponential

    /**
     * Calculate delay for a given attempt number
     *
     * @param int $attempt Attempt number (1-based)
     * @return int Delay in milliseconds
     */
    public static function delay(int $attempt): int
    {
        if ($attempt < 1) {
            return 0;
        }

        $delay = self::INITIAL_DELAY_MS * pow(self::BACKOFF_MULTIPLIER, $attempt - 1);
        return (int) min($delay, self::MAX_DELAY_MS);
    }

    /**
     * Calculate delay in seconds
     */
    public static function delaySeconds(int $attempt): float
    {
        return self::delay($attempt) / 1000.0;
    }

    /**
     * Check if an attempt should be retried
     *
     * @param int $attempt Current attempt number (1-based)
     * @return bool
     */
    public static function shouldRetry(int $attempt): bool
    {
        return $attempt < self::MAX_ATTEMPTS;
    }

    /**
     * Get total wait time for all attempts up to a given number
     *
     * @param int $maxAttempts Number of attempts to calculate for
     * @return int Total delay in milliseconds
     */
    public static function totalWaitTime(int $maxAttempts): int
    {
        $total = 0;
        for ($i = 1; $i <= $maxAttempts; $i++) {
            $total += self::delay($i);
        }
        return $total;
    }

    /**
     * Generate a retry schedule with timestamps
     *
     * @param int $startTimestamp Unix timestamp to start from
     * @return array<int, int> [attempt => timestamp]
     */
    public static function schedule(int $startTimestamp): array
    {
        $schedule = [];
        $cursor = $startTimestamp;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $delayMs = self::delay($attempt);
            $cursor += (int) ($delayMs / 1000);
            $schedule[$attempt] = $cursor;
        }

        return $schedule;
    }

    /**
     * Get the attempt number from an idempotency key suffix
     *
     * @param string $idempotencyKey
     * @return int Attempt number (1-based), or 1 if not found
     */
    public static function extractAttempt(string $idempotencyKey): int
    {
        if (preg_match('/_attempt_(\d+)$/', $idempotencyKey, $matches)) {
            return (int) $matches[1];
        }
        return 1;
    }

    /**
     * Append attempt suffix to idempotency key
     *
     * @param string $idempotencyKey Base idempotency key
     * @param int $attempt Attempt number
     * @return string
     */
    public static function appendAttempt(string $idempotencyKey, int $attempt): string
    {
        // Remove existing attempt suffix if present
        $base = preg_replace('/_attempt_\d+$/', '', $idempotencyKey);
        return $base . '_attempt_' . $attempt;
    }
}
