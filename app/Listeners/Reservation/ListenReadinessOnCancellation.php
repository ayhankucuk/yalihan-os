<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCancelledEvent;
use App\Services\Reservation\GuestArrivalReadinessService;
use Illuminate\Support\Facades\Log;

/**
 * ListenReadinessOnCancellation — Wave 2: Invalidates readiness on reservation cancellation.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * Triggered by: ReservationCancelledEvent
 *
 * Logic:
 * - Loads the reservation and calls GuestArrivalReadinessService::invalidateOnCancellation()
 * - INV-W2-C1, INV-W2-C2
 */
class ListenReadinessOnCancellation
{
    public function handle(ReservationCancelledEvent $event): void
    {
        Log::info('ListenReadinessOnCancellation: handling', [
            'reservation_id' => $event->reservationId,
            'tenant_id' => $event->tenantId,
        ]);

        try {
            $service = app(GuestArrivalReadinessService::class);

            // Load reservation without events (we already have the event data)
            $reservation = \App\Models\PropertyReservation::query()
                ->where('id', $event->reservationId)
                ->where('tenant_id', $event->tenantId)
                ->first();

            if ($reservation === null) {
                Log::warning('ListenReadinessOnCancellation: reservation not found', [
                    'reservation_id' => $event->reservationId,
                    'tenant_id' => $event->tenantId,
                ]);
                return;
            }

            $service->invalidateOnCancellation($reservation);
        } catch (\Throwable $e) {
            Log::error('ListenReadinessOnCancellation: failed', [
                'reservation_id' => $event->reservationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
