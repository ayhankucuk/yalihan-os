<?php

namespace App\Contracts\Property;

/**
 * PropertyAvailabilityContract — Canonical SSOT Availability Engine Interface
 *
 * Sprint 22 E01 — SAAB Enhanced
 */
interface PropertyAvailabilityContract
{
    /**
     * Priority Tiers (lower number = higher priority)
     */
    public const TIER_MAINTENANCE   = 1; // Highest (Safety/Legal/Repair/Operational)
    public const TIER_RESERVATION   = 2; // Confirmed Internal Guest Reservation
    public const TIER_OWNER_BLOCK   = 3; // Property Owner Personal Use / Hold
    public const TIER_EXTERNAL_SYNC = 4; // Imported iCal / Channel Sync Block
    public const TIER_HOLD_PENDING  = 5; // Temporary checkout hold (15 min)

    /**
     * Origin sources — who produced a block or projection record
     */
    public const ORIGIN_RESERVATION  = 'reservation';  // Internal guest reservation
    public const ORIGIN_OWNER        = 'owner';         // Owner personal use
    public const ORIGIN_MAINTENANCE  = 'maintenance';   // Repair / maintenance block
    public const ORIGIN_ICAL         = 'ical';          // Generic iCal feed
    public const ORIGIN_BOOKING      = 'booking';       // Booking.com iCal
    public const ORIGIN_AIRBNB       = 'airbnb';        // Airbnb iCal
    public const ORIGIN_MANUAL       = 'manual';        // Admin manual block
    public const ORIGIN_SYSTEM       = 'system';        // Automated system block
    public const ORIGIN_YAZLIK       = 'yazlik';        // Legacy yazlik_rezervasyonlar

    /**
     * Detailed conflict reason codes (Enhancement 4)
     */
    public const CONFLICT_HIGHER_PRIORITY  = 'CONFLICT_HIGHER_PRIORITY';   // Blocked by higher-tier existing block
    public const CONFLICT_EQUAL_PRIORITY   = 'CONFLICT_EQUAL_PRIORITY';    // Same tier already holds block
    public const CONFLICT_OWNER_BLOCK      = 'CONFLICT_OWNER_BLOCK';       // Owner block prevents new block
    public const CONFLICT_MAINTENANCE      = 'CONFLICT_MAINTENANCE';       // Maintenance block is inviolable
    public const CONFLICT_DUPLICATE        = 'CONFLICT_DUPLICATE';         // Idempotent key already registered
    public const CONFLICT_IDEMPOTENT       = 'CONFLICT_IDEMPOTENT';        // Same idempotency key, same state
    public const CONFLICT_EXTERNAL_LOCK    = 'CONFLICT_EXTERNAL_LOCK';     // External channel block present

    /**
     * Projection sources — which engine produced the projection record
     */
    public const PROJECTION_SOURCE_REBUILD        = 'rebuild';
    public const PROJECTION_SOURCE_RESERVATION    = 'reservation';
    public const PROJECTION_SOURCE_BLOCK          = 'block';
    public const PROJECTION_SOURCE_EXTERNAL_SYNC  = 'external_sync';

    /**
     * Check if a property is available for a given date range [startDate, endDate).
     *
     * @param int    $tenantId
     * @param int    $propertyId
     * @param string $startDate  YYYY-MM-DD (inclusive)
     * @param string $endDate    YYYY-MM-DD (exclusive — checkout day is free)
     * @return array {
     *   is_available: bool,
     *   tenant_id: int,
     *   property_id: int,
     *   start_date: string,
     *   end_date: string,
     *   requested_nights: int,
     *   available_nights: int,
     *   conflicts: array
     * }
     */
    public function checkAvailability(int $tenantId, int $propertyId, string $startDate, string $endDate): array;

    /**
     * Block a date range for a property using the 5-tier priority matrix.
     *
     * @param int         $tenantId
     * @param int         $propertyId
     * @param string      $startDate       YYYY-MM-DD
     * @param string      $endDate         YYYY-MM-DD
     * @param string      $reason          Human-readable block reason
     * @param int         $priorityTier    One of TIER_* constants
     * @param string|null $idempotencyKey  Custom idempotency key (auto-generated if null)
     * @param string|null $sourceSystem    Source system identifier
     * @param string|null $externalRef     External system reference (iCal UID, etc.)
     * @param string|null $origin          One of ORIGIN_* constants
     * @return array {
     *   success: bool,
     *   status: string,
     *   blocked_days?: int,
     *   priority_tier?: int,
     *   idempotency_key?: string,
     *   collisions?: array,
     *   conflict_reason?: string
     * }
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
        ?string $externalRef = null,
        ?string $origin = null
    ): array;

    /**
     * Unblock a date range for a property.
     *
     * @param int         $tenantId
     * @param int         $propertyId
     * @param string      $startDate     YYYY-MM-DD
     * @param string      $endDate       YYYY-MM-DD
     * @param string|null $idempotencyKey
     * @return array { success: bool, status: string, cleared_records: int }
     */
    public function unblockDateRange(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?string $idempotencyKey = null
    ): array;

    /**
     * Deterministically rebuild daily availability projection from all active sources:
     * property_reservations, yazlik_rezervasyonlar, and any stored external blocks.
     *
     * @param int    $tenantId
     * @param int    $propertyId
     * @param string $startDate  YYYY-MM-DD
     * @param string $endDate    YYYY-MM-DD
     * @return int Count of re-projected daily records inserted
     */
    public function rebuildAvailabilityProjection(int $tenantId, int $propertyId, string $startDate, string $endDate): int;
}
