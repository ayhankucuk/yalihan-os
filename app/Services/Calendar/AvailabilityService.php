<?php

namespace App\Services\Calendar;

use App\Enums\ReservationState;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Canonical availability conflict reader.
 *
 * This service deliberately reads PropertyReservation, the reservation SSOT.
 * Callers must supply the already-authorized property's tenant identifier;
 * availability must never be evaluated across tenant boundaries by ID alone.
 */
class AvailabilityService
{
    /**
     * Returns true when an active, confirmed, pending, or blocked reservation
     * overlaps the requested half-open interval [start, end).
     */
    public function hasConflict(int $ilanId, Carbon $start, Carbon $end, int $tenantId): bool
    {
        return $this->conflictQuery($ilanId, $start, $end, $tenantId)->exists();
    }

    /**
     * Returns non-sensitive conflict windows for internal diagnostics.
     *
     * Guest identity, contact details, payment fields, notes, and external
     * channel identifiers are intentionally never selected here.
     */
    public function getConflicts(int $ilanId, Carbon $start, Carbon $end, int $tenantId): Collection
    {
        return $this->conflictQuery($ilanId, $start, $end, $tenantId)
            ->orderBy('start_date')
            ->get(['start_date', 'end_date', 'reservation_state']);
    }

    private function conflictQuery(int $ilanId, Carbon $start, Carbon $end, int $tenantId): Builder
    {
        return PropertyReservation::withoutGlobalScopes()
            ->where('property_id', $ilanId)
            ->where('tenant_id', $tenantId)
            ->where('reservation_state', '!=', ReservationState::CANCELLED->value)
            ->whereNull('cancelled_at')
            ->where('start_date', '<', $end->toDateTimeString())
            ->where('end_date', '>', $start->toDateTimeString());
    }
}
