<?php

namespace App\Contracts\ChannelManager;

use App\DTOs\ChannelManager\ChannelTransportResult;

/**
 * ChannelTransportContract — Transport-layer abstraction for channel providers.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * Separates OTA identity (Airbnb, Booking) from transport provider (Channex, direct).
 *
 * CONTRACT:
 * - Transport-only: no domain decisions, no availability state changes
 * - Tenant-aware: every operation requires tenant_id
 * - Idempotent: correlationId prevents duplicate delivery
 * - Retryable: ChannelTransportResult.retryable signals retry eligibility
 * - Credential-safe: implementations MUST NOT log API keys or tokens
 *
 * DOES NOT:
 * - Write to PropertyAvailability (use CanonicalAvailabilityService)
 * - Make conflict or priority decisions
 * - Store credentials outside of IlanTakvimSync
 * - Reference Channel enum values (transport is channel-agnostic)
 *
 * @see ADR-006 for full architecture rationale
 */
interface ChannelTransportContract
{
    /**
     * Push availability data to the external provider endpoint.
     *
     * @param int    $tenantId          Tenant context — must match property ownership
     * @param string $externalListingId Provider-specific listing/property identifier
     * @param string $correlationId     Idempotency key for this operation
     * @param array  $availabilityData  [['date' => 'Y-m-d', 'available' => bool], ...]
     *
     * @return ChannelTransportResult
     */
    public function pushAvailability(
        int    $tenantId,
        string $externalListingId,
        string $correlationId,
        array  $availabilityData,
    ): ChannelTransportResult;

    /**
     * Pull availability data from the external provider endpoint.
     *
     * Returns raw provider data. The caller is responsible for mapping
     * to canonical PropertyAvailability format via CanonicalAvailabilityService.
     *
     * @param int    $tenantId          Tenant context
     * @param string $externalListingId Provider-specific listing identifier
     * @param string $correlationId     Correlation ID for tracing
     * @param string $fromDate          Inclusive start (YYYY-MM-DD)
     * @param string $toDate            Exclusive end (YYYY-MM-DD)
     *
     * @return ChannelTransportResult Contains raw availability in metadata['events']
     */
    public function pullAvailability(
        int    $tenantId,
        string $externalListingId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelTransportResult;

    /**
     * Test transport connection to provider.
     *
     * Used for health checks and credential validation.
     * MUST NOT log credentials on failure — log only error code.
     *
     * @param int $tenantId Tenant context
     *
     * @return ChannelTransportResult
     */
    public function testConnection(int $tenantId): ChannelTransportResult;
}
