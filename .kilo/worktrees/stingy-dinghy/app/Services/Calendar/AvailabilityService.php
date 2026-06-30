<?php

namespace App\Services\Calendar;

// ❌ DEPRECATED: IlanReservation table deprecated (2026-01-29)
// use App\Models\Deprecated\IlanReservation;
use Carbon\Carbon;

/**
 * ❌ DEPRECATED SERVICE (2026-01-29)
 * IlanReservation table deprecated. Service returns stub values.
 */
class AvailabilityService
{
    /**
     * Returns true if there is any conflicting active reservation or block.
     * ❌ STUB: Always returns false (IlanReservation deprecated)
     */
    public function hasConflict(int $ilanId, Carbon $start, Carbon $end): bool
    {
        return false; // IlanReservation table deprecated
    }

    /**
     * Get conflicting reservations list for diagnostics.
     * ❌ STUB: Returns empty collection (IlanReservation deprecated)
     */
    public function getConflicts(int $ilanId, Carbon $start, Carbon $end)
    {
        return collect([]); // IlanReservation table deprecated
    }
}

