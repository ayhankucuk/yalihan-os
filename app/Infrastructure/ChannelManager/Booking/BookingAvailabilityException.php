<?php

namespace App\Infrastructure\ChannelManager\Booking;

/**
 * BookingAvailabilityException — Thrown when availability push to Booking.com fails.
 *
 * Sprint 4.13 — Booking.com Provider Wave 4
 * ADR-009 §4: Retryable 5xx → throw; Non-retryable 4xx → graceful failure.
 */
class BookingAvailabilityException extends \RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly bool $isRetryable,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }
}
