<?php

namespace App\Domain\Reservation\Events;

use App\Enums\ReservationState;
use App\Models\PropertyReservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ReservationStateTransitioned
{
    use Dispatchable;
    use SerializesModels;

    public string $eventId;

    public function __construct(
        public PropertyReservation $reservation,
        public ReservationState $fromState,
        public ReservationState $toState,
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?? (string) Str::uuid();
    }
}
