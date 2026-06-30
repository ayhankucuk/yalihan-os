<?php

namespace App\Actions\Admin\Reservation;

use App\Models\PropertyReservation;

class UpdateReservationAction
{
    public function handle(PropertyReservation $reservation, array $updateData): bool
    {
        return $reservation->update($updateData);
    }
}
