<?php

namespace App\Domain\ChannelManager\DTOs;

use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;

/**
 * ChannelSyncResponse — Domain-level result of a channel sync operation.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * Returned by ChannelSyncContract adapter methods.
 * This is the domain response — not the transport-level ChannelTransportResult.
 *
 * Immutable readonly DTO.
 */
readonly final class ChannelSyncResponse
{
    public function __construct(
        public bool            $success,
        public Channel         $channel,
        public SyncDirection   $direction,
        public string          $correlationId,
        public ?string         $channelRef,
        public ?string         $errorCode,
        public ?string         $errorMessage,
        public bool            $retryable,
        public array           $metadata,
    ) {}

    public static function success(
        Channel       $channel,
        SyncDirection $direction,
        string        $correlationId,
        ?string       $channelRef = null,
        array         $metadata = [],
    ): self {
        return new self(
            success: true,
            channel: $channel,
            direction: $direction,
            correlationId: $correlationId,
            channelRef: $channelRef,
            errorCode: null,
            errorMessage: null,
            retryable: false,
            metadata: $metadata,
        );
    }

    public static function failure(
        Channel       $channel,
        SyncDirection $direction,
        string        $correlationId,
        string        $errorCode,
        string        $errorMessage,
        bool          $retryable,
    ): self {
        return new self(
            success: false,
            channel: $channel,
            direction: $direction,
            correlationId: $correlationId,
            channelRef: null,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            retryable: $retryable,
            metadata: [],
        );
    }

    /**
     * Convenience check for successful response.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Convenience check for failed response.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function toArray(): array
    {
        return [
            'success'         => $this->success,
            'channel'         => $this->channel->value,
            'direction'       => $this->direction->value,
            'correlation_id'  => $this->correlationId,
            'channel_ref'    => $this->channelRef,
            'error_code'      => $this->errorCode,
            'error_message'   => $this->errorMessage,
            'retryable'      => $this->retryable,
            'metadata'       => $this->metadata,
        ];
    }
}
