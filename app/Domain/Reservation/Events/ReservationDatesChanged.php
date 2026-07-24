<?php

namespace App\Domain\Reservation\Events;

use App\Domain\Shared\ValueObjects\DateRange;
use App\Models\PropertyReservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ReservationDatesChanged
{
    use Dispatchable;
    use SerializesModels;

    public string $eventId;

    public function __construct(
        public PropertyReservation $reservation,
        public DateRange $oldDateRange,
        public DateRange $newDateRange,
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?? (string) Str::uuid();
    }
}
