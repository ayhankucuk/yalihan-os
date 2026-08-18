<?php

namespace App\Contracts\ChannelManager;

use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;

/**
 * ChannelReservationContract — Canonical interface for reservation lifecycle.
 *
 * Sprint 4.10 — Booking.com Provider Wave 1 (interface only)
 * ADR-009 §3: separate from ChannelSyncContract
 *
 * OUT OF SCOPE FOR WAVE 1:
 * All methods are stub. Booking.com reservation retrieval semantics
 * (new/modified/cancelled via different OTA endpoints) will be mapped in Wave 2.
 *
 * Contract is provider-AGNOSTIC — future providers (Expedia, vrbo) may use
 * different protocols (webhook, streaming, polling) without changing this interface.
 *
 * DO NOT confuse with ChannelSyncContract — that handles availability/rates only.
 */
interface ChannelReservationContract
{
    /**
     * Retrieve new reservations from the channel.
     *
     * Wave 2: Booking.com GET /reservations (new)
     */
    public function retrieveNew(int $tenantId, int $propertyId, string $from, string $to): array;

    /**
     * Retrieve modified reservations from the channel.
     *
     * Wave 2: Booking.com GET /reservations/changes
     */
    public function retrieveModified(int $tenantId, int $propertyId, string $from, string $to): array;

    /**
     * Retrieve cancelled reservations from the channel.
     *
     * Wave 2: Booking.com GET /reservations/cancelled
     */
    public function retrieveCancelled(int $tenantId, int $propertyId, string $from, string $to): array;

    /**
     * Acknowledge a reservation to the channel.
     * MUST only be called AFTER canonical DB commit succeeds.
     *
     * Wave 2: Booking.com POST /reservations/{id}/ack
     * ADR-009 Invariant: ACK only after DB COMMIT SUCCESS
     */
    public function acknowledge(int $tenantId, string $reservationId, string $status): array;

    /**
     * Test connectivity to the channel reservation API.
     */
    public function testConnection(int $tenantId): ChannelSyncResponse;
}
