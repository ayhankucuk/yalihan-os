<?php

namespace App\Contracts\ChannelManager;

use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;

/**
 * ChannelSyncContract — Provider-independent interface for channel adapters
 *
 * CHANNEL_MANAGER Wave 1: Foundation
 *
 * CONTRACT:
 * - provider-independent: all adapters implement this interface
 * - tenant-aware: all operations require tenant_id
 * - idempotent: same input → same result
 * - retry-safe: response indicates if operation is retryable
 * - deterministic: same call always returns same result for same input
 *
 * DO:
 * - Return explicit success/failure state via ChannelSyncResponse
 * - Include correlation ID for tracing
 * - Include external reference for audit
 * - Use immutable DTOs for data transfer
 *
 * DO NOT:
 * - Make domain decisions (conflict detection, priority resolution, override)
 * - Write directly to PropertyAvailability (use existing canonical write path)
 * - Store or log credentials
 * - Make availability decisions
 */
interface ChannelSyncContract
{
    /**
     * Get the channel identifier
     */
    public function getChannel(): Channel;

    /**
     * Get channel display name
     */
    public function getChannelName(): string;

    /**
     * Check if this adapter supports push (export) operations
     */
    public function supportsPush(): bool;

    /**
     * Check if this adapter supports pull (import) operations
     */
    public function supportsPull(): bool;

    /**
     * Push availability FROM YALIHAN TO channel
     *
     * @param int      $tenantId      Tenant context
     * @param int      $propertyId    Target property
     * @param string   $correlationId Correlation ID for tracing
     * @param array    $availabilityData ['date' => 'Y-m-d', 'available' => bool]
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
     * Pull availability FROM channel TO YALIHAN
     *
     * Returns normalized external availability data.
     * The caller is responsible for writing to PropertyAvailability via existing canonical write path.
     *
     * @param int      $tenantId      Tenant context
     * @param int      $propertyId    Target property
     * @param string   $correlationId Correlation ID for tracing
     * @param string   $fromDate     Inclusive start (YYYY-MM-DD)
     * @param string   $toDate       Exclusive end (YYYY-MM-DD)
     *
     * @return ChannelSyncResponse Contains parsed availability in metadata['events']
     */
    public function pullAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelSyncResponse;

    /**
     * Test connection to channel
     *
     * @param int $tenantId Tenant context
     *
     * @return ChannelSyncResponse
     */
    public function testConnection(int $tenantId): ChannelSyncResponse;
}
