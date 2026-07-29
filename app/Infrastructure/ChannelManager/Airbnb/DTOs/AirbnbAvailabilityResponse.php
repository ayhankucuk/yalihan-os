<?php

namespace App\Infrastructure\ChannelManager\Airbnb\DTOs;

/**
 * AirbnbAvailabilityResponse — Parsed Airbnb API response
 *
 * Sprint 13 E03: Airbnb Adapter
 *
 * Normalizes Airbnb API responses into a consistent internal format.
 */
readonly class AirbnbAvailabilityResponse
{
    public function __construct(
        public bool $success,
        public ?string $airbnbReference = null,
        public ?string $status = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {}

    /**
     * Parse a raw Airbnb API response array
     */
    public static function fromAirbnbApi(array $response): self
    {
        // Airbnb returns { "status": "success", "confirmation": "..." } or { "error": "..." }
        if (isset($response['error'])) {
            return new self(
                success: false,
                errorCode: $response['error_code'] ?? 'UNKNOWN_ERROR',
                errorMessage: $response['error'] ?? 'Unknown error',
                metadata: $response,
            );
        }

        return new self(
            success: true,
            airbnbReference: $response['confirmation'] ?? $response['idempotency_key'] ?? null,
            status: $response['status'] ?? 'success',
            metadata: $response,
        );
    }

    /**
     * Check if this is a conflict response
     */
    public function isConflict(): bool
    {
        return $this->errorCode === 'LISTING_CONFLICT'
            || $this->errorCode === 'AVAILABILITY_CONFLICT'
            || $this->errorCode === 'DUPLICATE_REQUEST';
    }

    /**
     * Check if this is a rate limit response
     */
    public function isRateLimit(): bool
    {
        return $this->errorCode === 'RATE_LIMIT_EXCEEDED'
            || $this->errorCode === '429';
    }

    /**
     * Check if this is an auth error
     */
    public function isAuthError(): bool
    {
        return $this->errorCode === 'UNAUTHORIZED'
            || $this->errorCode === 'INVALID_TOKEN'
            || $this->errorCode === 'TOKEN_EXPIRED';
    }
}
