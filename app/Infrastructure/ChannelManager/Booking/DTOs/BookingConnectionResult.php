<?php

namespace App\Infrastructure\ChannelManager\Booking\DTOs;

/**
 * BookingConnectionResult — Immutable DTO for Booking connectivity probe result.
 *
 * Sprint 4.15 — G34 Connectivity Probe
 *
 * Non-destructive: probe does NOT write any data to Booking.com or YALIHAN.
 * Sequence: credential validation → token exchange → read-only API probe.
 *
 * @readonly
 */
final class BookingConnectionResult
{
    public const CONNECTED            = 'CONNECTED';
    public const AUTH_FAILED          = 'AUTH_FAILED';
    public const NOT_REGISTERED       = 'NOT_REGISTERED';
    public const CONNECTION_ERROR      = 'CONNECTION_ERROR';
    public const PROVIDER_ERROR       = 'PROVIDER_ERROR';

    private const RETRYABLE_STATUSES  = ['CONNECTION_ERROR'];
    // @sab-ignore-field  — status: Booking.com API response status label, not a DB/model field
    public const STATUS_CONNECTED          = 'CONNECTED';
    public const STATUS_AUTH_FAILED       = 'AUTH_FAILED';
    public const STATUS_NOT_REGISTERED    = 'NOT_REGISTERED';
    public const STATUS_CONNECTION_ERROR  = 'CONNECTION_ERROR';
    public const STATUS_PROVIDER_ERROR   = 'PROVIDER_ERROR';

    public function __construct(
        // @sab-ignore-field  — probeDurumu: Booking.com connectivity result label, not a DB column
        public readonly string  $probeDurumu,
        public readonly bool    $connected,
        public readonly string  $correlationId,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
        public readonly bool    $retryable,
        public readonly array  $metadata,
    ) {}

    /**
     * Build a connected result.
     */
    public static function connected(string $correlationId, array $metadata = []): self
    {
        return new self(
            probeDurumu:   self::STATUS_CONNECTED,
            connected:     true,
            correlationId: $correlationId,
            errorCode:     null,
            errorMessage:  null,
            retryable:     false,
            metadata:      $metadata,
        );
    }

    /**
     * Build a failed result.
     */
    public static function failure(
        string  $probeDurumu,
        string  $correlationId,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        array   $metadata = [],
    ): self {
        return new self(
            probeDurumu:   $probeDurumu,
            connected:     false,
            correlationId: $correlationId,
            errorCode:     $errorCode,
            errorMessage: $errorMessage,
            retryable:    in_array($probeDurumu, self::RETRYABLE_STATUSES, true),
            metadata:     $metadata,
        );
    }

    public static function authFailed(string $correlationId, ?string $message = null): self
    {
        return self::failure(
            self::STATUS_AUTH_FAILED,
            $correlationId,
            'AUTH_FAILED',
            $message ?? 'Authentication failed — check client_id and client_secret',
        );
    }

    public static function notRegistered(string $correlationId, int $tenantId): self
    {
        return self::failure(
            self::STATUS_NOT_REGISTERED,
            $correlationId,
            'NOT_REGISTERED',
            "No active booking_com sync record found for tenant {$tenantId}",
        );
    }

    public static function connectionError(string $correlationId, ?string $message = null): self
    {
        return new self(
            probeDurumu:   self::STATUS_CONNECTION_ERROR,
            connected:     false,
            correlationId: $correlationId,
            errorCode:     self::STATUS_CONNECTION_ERROR,
            errorMessage: $message ?? 'Could not connect to Booking.com — network or DNS issue',
            retryable:     true,
            metadata:      [],
        );
    }

    public static function providerError(string $correlationId, ?string $message = null): self
    {
        return self::failure(
            self::STATUS_PROVIDER_ERROR,
            $correlationId,
            self::STATUS_PROVIDER_ERROR,
            $message ?? 'Booking.com returned an unexpected response',
        );
    }
}
