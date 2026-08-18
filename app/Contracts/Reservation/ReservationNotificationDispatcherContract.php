<?php

namespace App\Contracts\Reservation;

/**
 * ReservationNotificationDispatcherContract — Reservation lifecycle notification boundary.
 *
 * Implemented by NullReservationNotificationDispatcher (Wave 1 placeholder)
 * and future GuestConfirmationDispatcher (Wave 1 actual implementation).
 */
interface ReservationNotificationDispatcherContract
{
    /**
     * Dispatch guest confirmation notification for a reservation.
     *
     * @param int        $reservationId
     * @param int        $tenantId
     * @param array      $context  reservation data from ReservationCreatedEvent
     * @return bool     true if dispatched or evidence created, false on fatal error
     */
    public function sendGuestConfirmation(int $reservationId, int $tenantId, array $context): bool;
}
