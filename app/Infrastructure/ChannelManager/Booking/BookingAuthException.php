<?php

namespace App\Infrastructure\ChannelManager\Booking;

/**
 * BookingAuthException — Thrown on auth failures.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 */
class BookingAuthException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        \Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function isRetryable(): bool
    {
        return $this->httpStatus === 0  // network error
            || $this->httpStatus >= 500;
    }
}
