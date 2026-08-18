<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCreatedEvent;
use App\Services\Reservation\GuestArrivalReadinessService;
use Illuminate\Support\Facades\Log;

/**
 * ListenReservationCreatedReadiness — Wave 2: Creates readiness record on reservation creation.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * Triggered by: ReservationCreatedEvent (Wave 1 event)
 *
 * Logic:
 * - Attempts to create a property_readiness record for the new reservation
 * - If preconditions fail (rental disabled, wrong state), logs and continues
 * - Idempotent: service handles duplicate prevention
 */
class ListenReservationCreatedReadiness
{
    public function handle(ReservationCreatedEvent $event): void
    {
        Log::info('ListenReservationCreatedReadiness: handling', [
            'reservation_id' => $event->reservationId,
            'tenant_id' => $event->tenantId,
        ]);

        try {
            $service = app(GuestArrivalReadinessService::class);

            $reservation = \App\Models\PropertyReservation::query()
                ->where('id', $event->reservationId)
                ->where('tenant_id', $event->tenantId)
                ->first();

            if ($reservation === null) {
                Log::warning('ListenReservationCreatedReadiness: reservation not found', [
                    'reservation_id' => $event->reservationId,
                    'tenant_id' => $event->tenantId,
                ]);
                return;
            }

            // This is idempotent — service returns existing record if already created
            $service->getOrCreateReadiness($reservation);
        } catch (\Throwable $e) {
            Log::error('ListenReservationCreatedReadiness: failed to create readiness', [
                'reservation_id' => $event->reservationId,
                'tenant_id' => $event->tenantId,
                'error' => $e->getMessage(),
            ]);
            // Non-critical: don't fail the reservation creation workflow
        }
    }
}
