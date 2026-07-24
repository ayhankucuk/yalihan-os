<?php

namespace App\Services\Reservation;

use App\Models\Property;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use DomainException;

class ConflictDetectionService
{
    /**
     * Checks if a date range intersects with blocked dates or existing reservations for a property.
     * Returns true if a conflict exists, false if dates are available.
     */
    public function hasConflict(Property $property, string $startDate, string $endDate, ?int $excludeReservationId = null): bool
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lessThanOrEqualTo($start)) {
            throw new DomainException('Reservation end date must be after start date.');
        }

        // 1. Check existing overlapping reservations
        $query = PropertyReservation::where('property_id', $property->id)
            ->where('tenant_id', $property->tenant_id)
            ->whereNull('cancelled_at')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start->toDateString())
                          ->where('end_date', '>=', $end->toDateString());
                  });
            });

        if ($excludeReservationId !== null) {
            $query->where('id', '!=', $excludeReservationId);
        }

        if ($query->exists()) {
            return true;
        }

        // 2. Check blocked availability entries
        $blockedCount = PropertyAvailability::where('property_id', $property->id)
            ->whereBetween('date', [$start->toDateString(), $end->copy()->subDay()->toDateString()])
            ->where('is_available', false)
            ->count();

        return $blockedCount > 0;
    }
}
