<?php

namespace App\Application\ChannelManager\Exceptions;

/**
 * ChannelSynchronizationException — Thrown when channel sync fails
 *
 * Sprint 13 E02: Availability Synchronization
 */
class ChannelSynchronizationException extends \Exception
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $channelId,
        public readonly string $errorMessage,
        public readonly ?string $errorCode = null,
        public readonly bool $retryable = true,
    ) {
        parent::__construct(
            "Channel sync failed for property {$propertyId} on {$channelId}: {$errorMessage}",
            $retryable ? 503 : 500
        );
    }

    /**
     * Check if this error is retryable
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * Get error details for logging
     */
    public function toLogContext(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'property_id' => $this->propertyId,
            'channel_id' => $this->channelId,
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
            'retryable' => $this->retryable,
        ];
    }
}
