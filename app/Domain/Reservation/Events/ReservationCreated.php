<?php

namespace App\Domain\Reservation\Events;

use App\Models\PropertyReservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PropertyReservation $reservation)
    {
    }
}
