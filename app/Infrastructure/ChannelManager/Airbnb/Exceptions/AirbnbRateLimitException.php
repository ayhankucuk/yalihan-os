<?php

namespace App\Infrastructure\ChannelManager\Airbnb\Exceptions;

/**
 * AirbnbRateLimitException — Retryable rate limit exceeded
 *
 * Sprint 13 E03: Airbnb Adapter
 */
class AirbnbRateLimitException extends \Exception
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $channelId = 'airbnb',
        public readonly ?int $retryAfterSeconds = null,
        string $message = 'Airbnb rate limit exceeded',
    ) {
        parent::__construct($message, 429);
    }

    public function isRetryable(): bool
    {
        return true;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfterSeconds ?? 60;
    }

    public function toLogContext(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'channel_id' => $this->channelId,
            'error_type' => 'rate_limit',
            'retryable' => true,
            'retry_after_seconds' => $this->getRetryAfter(),
        ];
    }
}
