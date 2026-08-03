<?php

namespace App\Contracts\Property;

/**
 * PropertyAvailabilityContract — Canonical SSOT Availability Engine Interface
 */
interface PropertyAvailabilityContract
{
    /**
     * Priority Tiers
     */
    public const TIER_MAINTENANCE   = 1; // Highest (Safety/Legal/Repair)
    public const TIER_RESERVATION   = 2; // Confirmed Internal Guest Reservation
    public const TIER_OWNER_BLOCK   = 3; // Property Owner Personal Use / Hold
    public const TIER_EXTERNAL_SYNC = 4; // Imported iCal / Channel Sync Block
    public const TIER_HOLD_PENDING  = 5; // Temporary checkout hold (15 min)

    /**
     * Check if a property is available for a given date range.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate YYYY-MM-DD
     * @param string $endDate YYYY-MM-DD
     * @return array ['is_available' => bool, 'conflicts' => array, 'available_nights' => int]
     */
    public function checkAvailability(int $tenantId, int $propertyId, string $startDate, string $endDate): array;

    /**
     * Block a date range for a property.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate YYYY-MM-DD
     * @param string $endDate YYYY-MM-DD
     * @param string $reason
     * @param int $priorityTier
     * @param string|null $idempotencyKey
     * @param string|null $sourceSystem
     * @param string|null $externalRef
     * @return array Result metadata
     */
    public function blockDateRange(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        string $reason,
        int $priorityTier = self::TIER_OWNER_BLOCK,
        ?string $idempotencyKey = null,
        ?string $sourceSystem = 'internal',
        ?string $externalRef = null
    ): array;

    /**
     * Unblock a date range for a property.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate YYYY-MM-DD
     * @param string $endDate YYYY-MM-DD
     * @param string|null $idempotencyKey
     * @return array Result metadata
     */
    public function unblockDateRange(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?string $idempotencyKey = null
    ): array;

    /**
     * Deterministically rebuild daily availability timeline projection from active reservations and blocks.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate YYYY-MM-DD
     * @param string $endDate YYYY-MM-DD
     * @return int Count of re-projected daily records
     */
    public function rebuildAvailabilityProjection(int $tenantId, int $propertyId, string $startDate, string $endDate): int;
}
