<?php

namespace App\Actions\Admin\Reservation;

use App\Models\PropertyReservation;

class UpdateReservationStateAction
{
    public function handle(PropertyReservation $reservation, string $state): bool
    {
        return $reservation->update(['reservation_state' => $state]);
    }
}
