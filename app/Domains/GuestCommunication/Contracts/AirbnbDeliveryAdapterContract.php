<?php

namespace App\Domains\GuestCommunication\Contracts;

/**
 * AirbnbDeliveryAdapterContract
 *
 * EX-001 WAVE 2 — Airbnb Delivery
 *
 * Airbnb mesaj gönderimi için adapter contract.
 * Her channel (Airbnb, WhatsApp, Email) kendi adapter'ını implement eder.
 */
interface AirbnbDeliveryAdapterContract
{
    /**
     * Send a welcome message via Airbnb
     */
    public function sendWelcomeMessage(array $messageData): DeliveryResult;

    /**
     * Resolve credentials for a tenant
     */
    public function resolveCredentials(int $tenantId): AirbnbCredentials;

    /**
     * Create idempotency key for a reservation
     */
    public function createIdempotencyKey(int $reservationId, string $messageType): string;
}

/**
 * AirbnbCredentials
 *
 * Airbnb API credentials for a tenant
 */
class AirbnbCredentials
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $accessToken,
        public readonly string $listingId,
        public readonly ?string $refreshToken = null,
    ) {}

    public function isValid(): bool
    {
        return !empty($this->accessToken) && !empty($this->listingId);
    }
}

/**
 * DeliveryResult
 *
 * Mesaj gönderim sonucu
 */
class DeliveryResult
{
    public function __construct(
        public readonly DeliveryStatus $status,
        public readonly ?string $externalId = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $retryable = false,
        public readonly ?string $idempotencyKey = null,
    ) {}

    public static function sent(string $externalId, string $idempotencyKey): self
    {
        return new self(
            status: DeliveryStatus::SENT,
            externalId: $externalId,
            idempotencyKey: $idempotencyKey,
        );
    }

    public static function failed(string $errorMessage, bool $retryable = false): self
    {
        return new self(
            status: DeliveryStatus::FAILED,
            errorMessage: $errorMessage,
            retryable: $retryable,
        );
    }

    public static function duplicate(string $existingId): self
    {
        return new self(
            status: DeliveryStatus::DUPLICATE,
            externalId: $existingId,
        );
    }

    public static function rateLimited(string $retryAfter): self
    {
        return new self(
            status: DeliveryStatus::RATE_LIMITED,
            errorMessage: "Rate limited. Retry after: {$retryAfter}",
            retryable: true,
        );
    }

    public static function invalidCredentials(string $errorMessage): self
    {
        return new self(
            status: DeliveryStatus::INVALID_CREDENTIALS,
            errorMessage: $errorMessage,
            retryable: false,
        );
    }
}

/**
 * DeliveryStatus
 *
 * Mesaj gönderim durumları
 */
enum DeliveryStatus: string
{
    case SENT = 'sent';
    case FAILED = 'failed';
    case DUPLICATE = 'duplicate';
    case RATE_LIMITED = 'rate_limited';
    case INVALID_CREDENTIALS = 'invalid_credentials';

    public function isSuccess(): bool
    {
        return $this === self::SENT;
    }

    public function shouldRetry(): bool
    {
        return match($this) {
            self::RATE_LIMITED, self::FAILED => true,
            default => false,
        };
    }
}
