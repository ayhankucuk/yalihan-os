<?php

namespace App\Domain\ChannelManager\DTOs;

use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;
use App\Domain\ChannelManager\Enums\SyncState;

/**
 * ChannelSyncResponse — Value Object for channel sync operation results
 *
 * CHANNEL_MANAGER Wave 1: Foundation
 *
 * Immutable response object that represents the outcome of a sync operation.
 * Contains all information needed for retry decisions, audit logging, and correlation.
 *
 * @property-read bool        $success
 * @property-read SyncState  $state
 * @property-read string     $errorCode
 * @property-read string|null $errorMessage
 * @property-read string|null $channelRef
 * @property-read array     $metadata
 * @property-read bool      $retryable
 * @property-read Channel   $channel
 * @property-read SyncDirection $direction
 * @property-read string    $correlationId
 * @property-read \DateTimeImmutable $executedAt
 */
final readonly class ChannelSyncResponse
{
    public function __construct(
        public bool $success,
        public SyncState $state,
        public string $errorCode,
        public ?string $errorMessage,
        public ?string $channelRef,
        public array $metadata,
        public bool $retryable,
        public Channel $channel,
        public SyncDirection $direction,
        public string $correlationId,
        public \DateTimeImmutable $executedAt,
    ) {}

    /**
     * Create a successful response
     */
    public static function success(
        Channel $channel,
        SyncDirection $direction,
        string $correlationId,
        string $channelRef,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            state: SyncState::SUCCESS,
            errorCode: 'SUCCESS',
            errorMessage: null,
            channelRef: $channelRef,
            metadata: $metadata,
            retryable: false,
            channel: $channel,
            direction: $direction,
            correlationId: $correlationId,
            executedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a failed response
     */
    public static function failure(
        Channel $channel,
        SyncDirection $direction,
        string $correlationId,
        string $errorCode,
        string $errorMessage,
        bool $retryable = false,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            state: SyncState::FAILED,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            channelRef: null,
            metadata: $metadata,
            retryable: $retryable,
            channel: $channel,
            direction: $direction,
            correlationId: $correlationId,
            executedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a partial success response
     */
    public static function partial(
        Channel $channel,
        SyncDirection $direction,
        string $correlationId,
        int $succeeded,
        int $failed,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            state: SyncState::PARTIAL,
            errorCode: 'PARTIAL_FAILURE',
            errorMessage: "{$succeeded} succeeded, {$failed} failed",
            channelRef: null,
            metadata: array_merge($metadata, [
                'succeeded' => $succeeded,
                'failed' => $failed,
            ]),
            retryable: $failed > 0,
            channel: $channel,
            direction: $direction,
            correlationId: $correlationId,
            executedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Check if response indicates success
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if response indicates failure
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Check if this operation can be retried
     */
    public function canRetry(): bool
    {
        return $this->retryable && $this->state !== SyncState::SUCCESS;
    }

    /**
     * Convert to array for logging/serialization
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'state' => $this->state->value,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'channel_ref' => $this->channelRef,
            'metadata' => $this->metadata,
            'retryable' => $this->retryable,
            'channel' => $this->channel->value,
            'direction' => $this->direction->value,
            'correlation_id' => $this->correlationId,
            'executed_at' => $this->executedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
