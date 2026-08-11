<?php

namespace App\Infrastructure\ChannelManager\Booking;

use App\Contracts\ChannelManager\ChannelReservationContract;
use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;

/**
 * BookingConnectivityAdapter — NOT IMPLEMENTED stub.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1
 *
 * ⚠️ STATUS: DISABLED — Wave 2 implementation pending
 *
 * ChannelReservationContract stub. All methods throw NOT_IMPLEMENTED.
 *
 * Production implementation maps:
 * - retrieveNew       → GET /reservations (new)
 * - retrieveModified  → GET /reservations/changes
 * - retrieveCancelled → GET /reservations/cancelled
 * - acknowledge       → POST /reservations/{id}/ack
 *
 * This stub allows the contract container binding to resolve without
 * breaking the DI container. It is NOT production ready.
 */
class BookingConnectivityAdapter implements ChannelReservationContract
{
    public function retrieveNew(int $tenantId, int $propertyId, string $from, string $to): array
    {
        return $this->notImplemented(__FUNCTION__);
    }

    public function retrieveModified(int $tenantId, int $propertyId, string $from, string $to): array
    {
        return $this->notImplemented(__FUNCTION__);
    }

    public function retrieveCancelled(int $tenantId, int $propertyId, string $from, string $to): array
    {
        return $this->notImplemented(__FUNCTION__);
    }

    public function acknowledge(int $tenantId, string $reservationId, string $status): array
    {
        return $this->notImplemented(__FUNCTION__);
    }

    public function testConnection(int $tenantId): ChannelSyncResponse
    {
        return ChannelSyncResponse::failure(
            channel: Channel::BOOKING,
            direction: SyncDirection::EXPORT,
            correlationId: 'booking-conn-test',
            errorCode: 'NOT_IMPLEMENTED',
            errorMessage: 'BookingConnectivityAdapter is not yet implemented. See Sprint 4.10 Wave 2.',
            retryable: false,
        );
    }

    private function notImplemented(string $method): array
    {
        throw new \RuntimeException("BookingConnectivityAdapter::{$method}() is not implemented. See Sprint 4.10 Wave 2.");
    }
}
