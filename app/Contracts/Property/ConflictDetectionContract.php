<?php

namespace App\Contracts\Property;

use App\DTOs\Property\ConflictResult;

/**
 * ConflictDetectionContract
 *
 * CONFLICT_DETECTION Phase 3A — E01
 *
 * Read-only, deterministic conflict detection interface.
 *
 * CONTRACT:
 * - Never writes to the database (no INSERT, UPDATE, DELETE)
 * - Deterministic: same input → same ConflictResult
 * - Tenant-isolated: cross-tenant data invisible
 * - Uses inclusive-exclusive date semantics: [startDate, endDate)
 * - Operates on PropertyAvailability projection (Phase 2)
 *
 * SAAB ADR-003: Detection ≠ Rejection ≠ Override
 */
interface ConflictDetectionContract
{
    /**
     * Detect availability conflicts for a date range on a property.
     *
     * Checks PropertyAvailability projection for any blocked dates in [startDate, endDate).
     * All origins are respected: reservation, owner, maintenance, external, etc.
     *
     * @param int         $tenantId            Requesting tenant (enforces isolation)
     * @param int         $propertyId          Target property
     * @param string      $startDate           Inclusive start date (YYYY-MM-DD)
     * @param string      $endDate             Exclusive end date (YYYY-MM-DD)
     * @param int|null    $excludeReservationId Exclude this reservation's own blocks
     *                                          (for re-confirmation idempotency)
     *
     * @return ConflictResult
     *
     * @throws \InvalidArgumentException When startDate >= endDate
     */
    public function detect(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): ConflictResult;
}
