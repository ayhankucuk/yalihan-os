<?php

namespace App\Infrastructure\ChannelManager\Booking;

/**
 * BookingRatesException — Thrown when rates push to Booking.com fails.
 *
 * Sprint 4.14 — Booking.com Provider Wave 5
 * ADR-009 §5: Retryable 5xx → throw; Non-retryable 4xx → graceful failure.
 */
class BookingRatesException extends \RuntimeException
{
    public function __construct(
        public readonly int    $httpStatus,
        public readonly bool   $isRetryable,
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
