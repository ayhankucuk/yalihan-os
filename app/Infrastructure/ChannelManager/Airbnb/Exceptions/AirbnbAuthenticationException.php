<?php

namespace App\Infrastructure\ChannelManager\Airbnb\Exceptions;

/**
 * AirbnbAuthenticationException — Non-retryable auth failure
 *
 * Sprint 13 E03: Airbnb Adapter
 */
class AirbnbAuthenticationException extends \Exception
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $channelId = 'airbnb',
        string $message = 'Airbnb authentication failed',
    ) {
        parent::__construct($message, 401);
    }

    public function isRetryable(): bool
    {
        return false;
    }

    public function toLogContext(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'channel_id' => $this->channelId,
            'error_type' => 'authentication',
            'retryable' => false,
        ];
    }
}
