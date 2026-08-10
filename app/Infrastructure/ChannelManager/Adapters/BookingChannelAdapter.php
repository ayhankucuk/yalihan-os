<?php

namespace App\Infrastructure\ChannelManager\Adapters;

use App\Contracts\ChannelManager\ChannelSyncContract;
use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;

/**
 * BookingChannelAdapter — DISABLED STUB for Booking.com.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * ⚠️ STATUS: DISABLED — NOT PRODUCTION READY ⚠️
 *
 * This adapter exists as an architectural placeholder only.
 * All methods return NOT_IMPLEMENTED.
 *
 * ADR-006 Amendment (SAAB):
 * - This class is NOT bound in AppServiceProvider
 * - It will NOT be injected in production
 * - Its presence does NOT mean Booking.com is supported
 * - Production implementation is CHANNEL_MANAGER_BOOKING_DEBT-001
 *
 * @see ADR-006 §3
 * @see CHANNEL_MANAGER_BOOKING_DEBT-001
 */
class BookingChannelAdapter implements ChannelSyncContract
{
    // No constructor — disabled stub has no dependencies.
    // ADR-006: BookingChannelAdapter is NOT bound in AppServiceProvider.

    public function getChannel(): Channel
    {
        return Channel::BOOKING;
    }

    public function getChannelName(): string
    {
        return Channel::BOOKING->label();
    }

    public function supportsPush(): bool
    {
        return false; // disabled
    }

    public function supportsPull(): bool
    {
        return false; // disabled
    }

    public function pushAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        array  $availabilityData,
    ): ChannelSyncResponse {
        return ChannelSyncResponse::failure(
            channel: Channel::BOOKING,
            direction: SyncDirection::EXPORT,
            correlationId: $correlationId,
            errorCode: 'NOT_IMPLEMENTED',
            errorMessage: 'Booking.com adapter is not yet implemented. See CHANNEL_MANAGER_BOOKING_DEBT-001.',
            retryable: false,
        );
    }

    public function pullAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelSyncResponse {
        return ChannelSyncResponse::failure(
            channel: Channel::BOOKING,
            direction: SyncDirection::IMPORT,
            correlationId: $correlationId,
            errorCode: 'NOT_IMPLEMENTED',
            errorMessage: 'Booking.com adapter is not yet implemented. See CHANNEL_MANAGER_BOOKING_DEBT-001.',
            retryable: false,
        );
    }

    public function testConnection(int $tenantId): ChannelSyncResponse
    {
        return ChannelSyncResponse::failure(
            channel: Channel::BOOKING,
            direction: SyncDirection::EXPORT,
            correlationId: 'connection-test',
            errorCode: 'NOT_IMPLEMENTED',
            errorMessage: 'Booking.com adapter is not yet implemented. See CHANNEL_MANAGER_BOOKING_DEBT-001.',
            retryable: false,
        );
    }
}
