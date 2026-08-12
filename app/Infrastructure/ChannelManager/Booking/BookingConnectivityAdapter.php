<?php

namespace App\Infrastructure\ChannelManager\Booking;

use App\Contracts\ChannelManager\ChannelReservationContract;
use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;

/**
 * BookingConnectivityAdapter — Production-ready connectivity probe.
 *
 * Sprint 4.15 — G34 Connectivity Probe
 *
 * Implements ChannelReservationContract.
 * All retrieval methods remain NOT_IMPLEMENTED (Wave 2 scope).
 * testConnection() IS NOW IMPLEMENTED — non-destructive connectivity probe.
 *
 * CONNECTION TEST INVARIANTS (non-destructive):
 * - Never POSTs / PUTs / PATCHes / DELETEs to Booking.com
 * - Uses GET /reservations with narrow date window (read-only)
 * - Never writes to YALIHAN database
 * - Never logs credentials or tokens
 */
class BookingConnectivityAdapter implements ChannelReservationContract
{
    public function __construct(
        private readonly BookingConnectionProbeService $probeService,
    ) {}

    /**
     * Retrieve new reservations.
     *
     * NOT IMPLEMENTED — Wave 2 scope.
     */
    public function retrieveNew(int $tenantId, int $propertyId, string $from, string $to): array
    {
        return $this->notImplemented(__FUNCTION__);
    }

    /**
     * Retrieve modified reservations.
     *
     * NOT IMPLEMENTED — Wave 2 scope.
     */
    public function retrieveModified(int $tenantId, int $propertyId, string $from, string $to): array
    {
        return $this->notImplemented(__FUNCTION__);
    }

    /**
     * Retrieve cancelled reservations.
     *
     * NOT IMPLEMENTED — Wave 2 scope.
     */
    public function retrieveCancelled(int $tenantId, int $propertyId, string $from, string $to): array
    {
        return $this->notImplemented(__FUNCTION__);
    }

    /**
     * Acknowledge a reservation.
     *
     * NOT IMPLEMENTED — Wave 2 scope.
     */
    public function acknowledge(int $tenantId, string $reservationId, string $status): array
    {
        return $this->notImplemented(__FUNCTION__);
    }

    /**
     * G34: Test Booking.com connectivity for a tenant.
     *
     * Non-destructive probe:
     *   1. Find first active booking_com sync record for tenant
     *   2. Attempt token exchange (validates credentials)
     *   3. GET /reservations (today ± 1 day — read-only)
     *   4. Classify → ChannelSyncResponse
     *
     * @param int $tenantId Tenant to probe
     *
     * @return ChannelSyncResponse
     */
    public function testConnection(int $tenantId): ChannelSyncResponse
    {
        $result = $this->probeService->probe($tenantId);

        if ($result->connected) {
            return ChannelSyncResponse::success(
                channel:       Channel::BOOKING,
                direction:     SyncDirection::EXPORT,
                correlationId: $result->correlationId,
                channelRef:    'connected',
                metadata:      $result->metadata,
            );
        }

        return ChannelSyncResponse::failure(
            channel:       Channel::BOOKING,
            direction:     SyncDirection::EXPORT,
            correlationId: $result->correlationId,
            errorCode:    $result->errorCode ?? $result->probeDurumu,
            errorMessage: $result->errorMessage ?? "Connection test failed: {$result->probeDurumu}",
            retryable:    $result->retryable,
        );
    }

    // ─── Private ─────────────────────────────────────────────────────

    private function notImplemented(string $method): array
    {
        throw new \RuntimeException("BookingConnectivityAdapter::{$method}() is not implemented. See Sprint 4.10 Wave 2.");
    }
}
