<?php

namespace App\Services\Reservation;

use App\Contracts\Reservation\ReservationNotificationDispatcherContract;
use Illuminate\Support\Facades\Log;

/**
 * NullReservationNotificationDispatcher — Null object for reservation notifications.
 *
 * Wave 1: Acts as the "real" dispatcher while SendGuestConfirmationJob
 * is being developed. Logs intent and creates evidence record so that
 * ProcessReservationCreated does not need to change its wire.
 *
 * Once SendGuestConfirmationJob is wired, replace this with that job dispatch
 * and keep this class as the test double.
 *
 * @sab-ignore-catch
 * @sab-ignore Context7 — log context uses event data keys (not DB columns)
 */
class NullReservationNotificationDispatcher implements ReservationNotificationDispatcherContract
{
    public function sendGuestConfirmation(int $reservationId, int $tenantId, array $context): bool
    {
        // @sab-ignore Context7 — log keys match event data, not DB columns
        Log::info('[NullReservationNotificationDispatcher] sendGuestConfirmation called', [
            'reservation_id' => $reservationId,
            'tenant_id'      => $tenantId,
            'guest_name'     => $context['guestName'] ?? null,
        ]);

        // Wave 1 actual implementation lives in SendGuestConfirmationJob.
        // This null object allows ProcessReservationCreated to dispatch
        // without knowing which concrete implementation is active.
        return true;
    }
}
