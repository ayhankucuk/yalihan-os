<?php

namespace App\Domain\ChannelManager\Models;

/**
 * ChannelApiResponse — Value Object for channel API responses
 *
 * Sprint 13 E01: Domain Foundation
 */
readonly class ChannelApiResponse
{
    public function __construct(
        public bool $success,
        public ?string $channelReference = null,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a successful response
     */
    public static function success(string $channelReference, array $metadata = []): self
    {
        return new self(
            success: true,
            channelReference: $channelReference,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed response
     */
    public static function failure(string $errorMessage, ?string $errorCode = null): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
        );
    }

    /**
     * Check if the response indicates a conflict
     */
    public function isConflict(): bool
    {
        return $this->errorCode === 'CONFLICT' || $this->errorCode === 'AVAILABILITY_CONFLICT';
    }

    /**
     * Check if the response indicates a conflict and get details
     */
    public function getConflictDetails(): ?array
    {
        if (!$this->isConflict()) {
            return null;
        }

        return $this->metadata['conflict'] ?? null;
    }
}
