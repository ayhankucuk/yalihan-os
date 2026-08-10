<?php

namespace App\Infrastructure\ChannelManager\Channex\Exceptions;

/**
 * Thrown for transport-level errors (network, HTTP 5xx, timeout).
 */
class ChannexTransportException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = false,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
