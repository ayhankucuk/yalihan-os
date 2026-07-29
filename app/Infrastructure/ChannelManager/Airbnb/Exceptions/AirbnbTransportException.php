<?php

namespace App\Infrastructure\ChannelManager\Airbnb\Exceptions;

/**
 * AirbnbTransportException — Retryable transport/network failure
 *
 * Sprint 13 E03: Airbnb Adapter
 */
class AirbnbTransportException extends \Exception
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $channelId = 'airbnb',
        public readonly bool $retryable = true,
        public readonly ?string $host = null,
        public readonly ?int $port = null,
        string $message = 'Airbnb transport error',
    ) {
        parent::__construct($message, 503);
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public function toLogContext(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'channel_id' => $this->channelId,
            'error_type' => 'transport',
            'host' => $this->host,
            'port' => $this->port,
            'retryable' => $this->retryable,
        ];
    }
}
