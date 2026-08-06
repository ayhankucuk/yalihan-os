<?php

namespace App\Contracts\Property;

use App\DTOs\Property\CalendarView;

/**
 * OperationalCalendarContract
 *
 * OPERATIONAL_CALENDAR — Implementation Sprint
 *
 * Read-only, deterministic operational calendar interface.
 *
 * CONTRACT (ADR-004):
 * - Never writes to the database (no INSERT, UPDATE, DELETE)
 * - Reads ONLY from PropertyAvailability projection
 * - Deterministic: same input → same CalendarView
 * - Tenant-isolated: cross-tenant data invisible
 * - Inclusive-exclusive date semantics: [startDate, endDate)
 * - NO business rule ownership — presents canonical projection as-is
 * - NO conflict resolution — that is ConflictDetectionService's responsibility
 * - NO priority recalculation — priority_tier comes from PropertyAvailability
 */
interface OperationalCalendarContract
{
    /**
     * Get the operational calendar for a property within a date range.
     *
     * Returns a unified timeline view of all availability states:
     * reservations, owner blocks, maintenance, external channel blocks, etc.
     *
     * @param int    $tenantId    Tenant context (enforces isolation)
     * @param int    $propertyId  Target property
     * @param string $startDate   Inclusive start (YYYY-MM-DD)
     * @param string $endDate     Exclusive end (YYYY-MM-DD)
     *
     * @return CalendarView
     *
     * @throws \InvalidArgumentException When startDate >= endDate
     */
    public function getCalendar(
        int    $tenantId,
        int    $propertyId,
        string $startDate,
        string $endDate
    ): CalendarView;
}
