<?php

namespace App\Services\Calendar;

use App\Enums\ReservationState;
use App\Models\Calendar\UnifiedCalendarProjection;
use App\Models\Property;
use App\Models\PropertyReservation;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UnifiedCalendarProjectionService
{
    /**
     * Deterministically rebuilds and replays the Unified Calendar Projection for a given property.
     */
    public function rebuildForProperty(Property $property): int
    {
        // 1. Clear existing reservation projections for property
        UnifiedCalendarProjection::where('tenant_id', $property->tenant_id)
            ->where('property_id', $property->id)
            ->where('source_type', 'RESERVATION')
            ->delete();

        // 2. Fetch all active non-cancelled/non-expired reservations
        $activeReservations = PropertyReservation::where('tenant_id', $property->tenant_id)
            ->where('property_id', $property->id)
            ->whereNotIn('reservation_state', [ReservationState::CANCELLED, ReservationState::EXPIRED])
            ->get();

        $projectedRows = 0;

        foreach ($activeReservations as $reservation) {
            $startDate = Carbon::parse($reservation->start_date);
            $endDate = Carbon::parse($reservation->end_date);
            $period = CarbonPeriod::create($startDate, $endDate);
            $replayEventId = (string) Str::uuid();

            foreach ($period as $date) {
                $dateStr = $date->toDateString();
                $isCheckin = $dateStr === $startDate->toDateString();
                $isCheckout = $dateStr === $endDate->toDateString();

                UnifiedCalendarProjection::create([
                    'tenant_id' => $reservation->tenant_id,
                    'property_id' => $reservation->property_id,
                    'commercial_offering_id' => $reservation->commercial_offering_id,
                    'reservation_id' => $reservation->id,
                    'calendar_date' => $dateStr,
                    'source_type' => 'RESERVATION',
                    'status' => $reservation->reservation_state === ReservationState::PENDING ? 'PENDING_APPROVAL' : 'BOOKED',
                    'nightly_rate' => $reservation->islem_tutari && $reservation->nights > 0 
                        ? round($reservation->islem_tutari / $reservation->nights, 2) 
                        : null,
                    'currency' => $reservation->currency ?? 'TRY',
                    'is_checkin_day' => $isCheckin,
                    'is_checkout_day' => $isCheckout,
                    'guest_name' => $reservation->guest_name,
                    'external_source' => 'DIRECT_BOOKING',
                    'source_event_id' => $replayEventId,
                    'last_projected_at' => now(),
                ]);

                $projectedRows++;
            }
        }

        return $projectedRows;
    }

    /**
     * Queries projected calendar entries for a date range.
     */
    public function getCalendarDaysForProperty(Property $property, string $startDate, string $endDate): Collection
    {
        return UnifiedCalendarProjection::where('tenant_id', $property->tenant_id)
            ->where('property_id', $property->id)
            ->whereBetween('calendar_date', [$startDate, $endDate])
            ->orderBy('calendar_date')
            ->get();
    }
}
