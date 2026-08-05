<?php

namespace App\Actions\Admin\Reservation;

use App\Enums\ReservationState;
use App\Models\PropertyReservation;
use App\Services\ReservationService;
use Exception;

/**
 * UpdateReservationStateAction
 *
 * RESERVATION_CORE Phase 1 (P0 fix):
 * All state transitions now route through ReservationService to guarantee
 * availability blocks are correctly created or released.
 *
 * Direct ->update(['reservation_state' => $state]) was removed because it
 * bypassed availability projection, causing availability leak on cancellation.
 */
class UpdateReservationStateAction
{
    public function __construct(
        private readonly ReservationService $reservationService
    ) {}

    /**
     * Handle state transition through the canonical ReservationService.
     *
     * @throws Exception
     */
    public function handle(PropertyReservation $reservation, string $state): bool
    {
        $newState = ReservationState::from($state);

        match ($newState) {
            ReservationState::CONFIRMED  => $this->reservationService->confirmReservation(
                $reservation->id,
                $reservation->tenant_id
            ),
            ReservationState::CANCELLED  => $this->reservationService->cancelReservation(
                $reservation->id,
                $reservation->tenant_id
            ),
            ReservationState::COMPLETED  => $this->reservationService->completeReservation(
                $reservation->id,
                $reservation->tenant_id
            ),
            ReservationState::NO_SHOW    => $this->reservationService->markNoShow(
                $reservation->id,
                $reservation->tenant_id
            ),
            default => throw new Exception(
                "Unsupported state transition to '{$state}' via UpdateReservationStateAction."
            ),
        };

        return true;
    }
}
