<?php

namespace App\Infrastructure\ChannelManager\Channex\Exceptions;

/**
 * Thrown when rate limit is hit (429).
 */
class ChannexRateLimitException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfter = 60,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
