<?php

namespace App\Infrastructure\ChannelManager\Channex;

/**
 * ChannexAcknowledgementException — Thrown when ACK to Channex API fails.
 *
 * WAVE 7 Phase B1.1R — Channex Reliability Recovery
 * Invariant: ACK failure does NOT rollback committed PropertyReservation.
 */
class ChannexAcknowledgementException extends \RuntimeException
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
