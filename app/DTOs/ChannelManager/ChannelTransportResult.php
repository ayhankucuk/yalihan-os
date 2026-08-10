<?php

namespace App\DTOs\ChannelManager;

/**
 * ChannelTransportResult — Immutable transport-layer result DTO.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * Represents the outcome of a single transport operation.
 * This is a transport-level result, NOT a domain result.
 *
 * The caller (adapter) is responsible for mapping this to a
 * domain-level ChannelSyncResponse.
 */
final class ChannelTransportResult
{
    private function __construct(
        public readonly bool    $success,
        public readonly string  $errorCode,
        public readonly ?string $errorMessage,
        public readonly ?string $providerReference,  // External reference from provider
        public readonly bool    $retryable,
        public readonly array   $metadata,           // Provider-specific raw data
    ) {}

    /**
     * Create a successful transport result.
     *
     * @param string $providerReference External reference ID (e.g. Channex job ID)
     * @param array  $metadata          Provider-specific data (e.g. processed count)
     */
    public static function success(string $providerReference, array $metadata = []): self
    {
        return new self(
            success: true,
            errorCode: '',
            errorMessage: null,
            providerReference: $providerReference,
            retryable: false,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed transport result.
     *
     * @param string $errorCode    Machine-readable error code (e.g. 'RATE_LIMIT', 'AUTH_FAILED')
     * @param string $errorMessage Human-readable error message
     * @param bool   $retryable    Whether the operation can be retried safely
     * @param array  $metadata     Additional context (e.g. retry_after)
     */
    public static function failure(
        string $errorCode,
        string $errorMessage,
        bool   $retryable = false,
        array  $metadata = [],
    ): self {
        return new self(
            success: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            providerReference: null,
            retryable: $retryable,
            metadata: $metadata,
        );
    }

    /**
     * Create a not-implemented result (for disabled stubs).
     */
    public static function notImplemented(string $reason = 'Not implemented'): self
    {
        return new self(
            success: false,
            errorCode: 'NOT_IMPLEMENTED',
            errorMessage: $reason,
            providerReference: null,
            retryable: false,
            metadata: [],
        );
    }

    /**
     * Serialize to array for logging (safe — no credentials).
     */
    public function toArray(): array
    {
        return [
            'success'            => $this->success,
            'error_code'         => $this->errorCode,
            'error_message'      => $this->errorMessage,
            'provider_reference' => $this->providerReference,
            'retryable'          => $this->retryable,
            // metadata excluded from default serialization — may contain sensitive data
        ];
    }
}
