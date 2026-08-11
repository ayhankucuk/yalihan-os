<?php

namespace App\Contracts\ChannelManager;

use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\Enums\Channel;

/**
 * ChannelSyncContract — Canonical interface for external channel integrations.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * Every OTA channel adapter (Airbnb, Booking, etc.) implements this contract.
 * The transport layer is injected via ChannelTransportContract.
 *
 * CONTRACT invariants:
 * - Channel identity: getChannel() returns the canonical Channel enum value
 * - Transport-agnostic: adapter does NOT couple to any specific transport provider
 * - Tenant isolation: every operation requires tenant_id
 * - Idempotent: correlationId prevents duplicate delivery
 * - Read-only availability: adapter NEVER writes to PropertyAvailability
 * - Delegating: transport calls go through ChannelTransportContract
 *
 * DOES NOT:
 * - Reference transport-provider-specific types (provider details are encapsulated in the transport layer)
 * - Write to PropertyAvailability (availability writes go through CanonicalAvailabilityService)
 * - Make conflict or priority decisions (domain layer owns this)
 * - Store credentials outside of IlanTakvimSync
 *
 * @see ADR-006 for full architecture rationale
 * @see AirbnbChannelAdapter for reference implementation
 */
interface ChannelSyncContract
{
    /**
     * Get the canonical channel identifier.
     */
    public function getChannel(): Channel;

    /**
     * Get the human-readable channel name for display.
     */
    public function getChannelName(): string;

    /**
     * Whether this channel supports push sync (Yalihan → channel).
     */
    public function supportsPush(): bool;

    /**
     * Whether this channel supports pull sync (channel → Yalihan).
     */
    public function supportsPull(): bool;

    /**
     * Push availability FROM Yalihan TO the external channel.
     *
     * @param int    $tenantId          Tenant context — must match property ownership
     * @param int    $propertyId        Yalihan property/ilan ID
     * @param string $correlationId     Idempotency key for this operation
     * @param array  $availabilityData  [['date' => 'Y-m-d', 'available' => bool], ...]
     *
     * @return ChannelSyncResponse
     */
    public function pushAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        array  $availabilityData,
    ): ChannelSyncResponse;

    /**
     * Pull availability FROM the external channel TO Yalihan.
     *
     * Returns raw events in response metadata.
     * The caller is responsible for mapping to canonical PropertyAvailability
     * via CanonicalAvailabilityService.
     *
     * @param int    $tenantId          Tenant context
     * @param int    $propertyId        Yalihan property/ilan ID
     * @param string $correlationId     Correlation ID for tracing
     * @param string $fromDate          Inclusive start (YYYY-MM-DD)
     * @param string $toDate            Exclusive end (YYYY-MM-DD)
     *
     * @return ChannelSyncResponse Contains raw availability in metadata['events']
     */
    public function pullAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelSyncResponse;

    /**
     * Test connection to the external channel.
     *
     * Used for health checks and credential validation.
     *
     * @param int $tenantId Tenant context
     *
     * @return ChannelSyncResponse
     */
    public function testConnection(int $tenantId): ChannelSyncResponse;
}
