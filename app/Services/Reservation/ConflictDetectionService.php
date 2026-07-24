<?php

namespace App\Services\Reservation;

use App\Models\Property;
use App\Models\PropertyAvailabilityBlock;
use App\Models\PropertyReservation;
use App\Domain\Shared\ValueObjects\DateRange;
use DomainException;

class ConflictDetectionService
{
    /**
     * Checks if a requested DateRange intersects with existing active reservations or availability blocks for a property.
     * Uses half-open interval intersection [starts_at, ends_at):
     * existing.starts_at < requested.ends_at AND existing.ends_at > requested.starts_at
     */
    public function hasConflict(Property $property, DateRange $requestedRange, ?int $excludeReservationId = null): bool
    {
        $startsAt = $requestedRange->getStartsAtString();
        $endsAt = $requestedRange->getEndsAtString();

        // 1. Check active reservations half-open intersection
        $resQuery = PropertyReservation::where('property_id', $property->id)
            ->where('tenant_id', $property->tenant_id)
            ->whereNull('cancelled_at')
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where('start_date', '<', $endsAt)
                  ->where('end_date', '>', $startsAt);
            });

        if ($excludeReservationId !== null) {
            $resQuery->where('id', '!=', $excludeReservationId);
        }

        if ($resQuery->exists()) {
            return true;
        }

        // 2. Check active property availability blocks half-open intersection
        $blockQuery = PropertyAvailabilityBlock::where('property_id', $property->id)
            ->where('tenant_id', $property->tenant_id)
            ->where('status', 'ACTIVE')
            ->whereNull('released_at')
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where('starts_at', '<', $endsAt)
                  ->where('ends_at', '>', $startsAt);
            });

        if ($excludeReservationId !== null) {
            $blockQuery->where(function ($q) use ($excludeReservationId) {
                $q->whereNull('reservation_id')
                  ->orWhere('reservation_id', '!=', $excludeReservationId);
            });
        }

        return $blockQuery->exists();
    }
}
