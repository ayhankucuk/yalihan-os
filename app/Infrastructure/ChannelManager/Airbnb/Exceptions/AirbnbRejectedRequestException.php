<?php

namespace App\Infrastructure\ChannelManager\Airbnb\Exceptions;

/**
 * AirbnbRejectedRequestException — Non-retryable request rejected by Airbnb
 *
 * Sprint 13 E03: Airbnb Adapter
 *
 * Includes: validation errors, business rule rejections, invalid listings, etc.
 */
class AirbnbRejectedRequestException extends \Exception
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $channelId = 'airbnb',
        public readonly ?string $rejectionCode = null,
        public readonly array $rejectionDetails = [],
        string $message = 'Airbnb rejected the request',
    ) {
        parent::__construct($message, 422);
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
            'error_type' => 'rejected',
            'rejection_code' => $this->rejectionCode,
            'rejection_details' => $this->rejectionDetails,
            'retryable' => false,
        ];
    }
}
