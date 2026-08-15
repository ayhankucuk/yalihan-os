<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationModifiedEvent;
use App\Services\Reservation\GuestArrivalReadinessService;
use Illuminate\Support\Facades\Log;

/**
 * ListenReadinessOnDateChange — Wave 2: Invalidates readiness on reservation date change.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * Triggered by: ReservationModifiedEvent
 *
 * Logic:
 * - Only fires if start_date or end_date actually changed
 * - Calls GuestArrivalReadinessService::invalidateOnDateChange()
 * - INV-W2-D1, INV-W2-D2, INV-W2-D3
 */
class ListenReadinessOnDateChange
{
    public function handle(ReservationModifiedEvent $event): void
    {
        // Only handle if dates actually changed
        if ($event->previousStartDate === $event->newStartDate
            && $event->previousEndDate === $event->newEndDate
        ) {
            return;
        }

        Log::info('ListenReadinessOnDateChange: handling date change', [
            'reservation_id' => $event->reservationId,
            'tenant_id' => $event->tenantId,
            'previous_start' => $event->previousStartDate,
            'previous_end' => $event->previousEndDate,
            'new_start' => $event->newStartDate,
            'new_end' => $event->newEndDate,
        ]);

        try {
            $service = app(GuestArrivalReadinessService::class);

            $reservation = \App\Models\PropertyReservation::query()
                ->where('id', $event->reservationId)
                ->where('tenant_id', $event->tenantId)
                ->first();

            if ($reservation === null) {
                Log::warning('ListenReadinessOnDateChange: reservation not found', [
                    'reservation_id' => $event->reservationId,
                    'tenant_id' => $event->tenantId,
                ]);
                return;
            }

            $service->invalidateOnDateChange(
                $reservation,
                $event->previousStartDate,
                $event->previousEndDate
            );
        } catch (\Throwable $e) {
            Log::error('ListenReadinessOnDateChange: failed', [
                'reservation_id' => $event->reservationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
