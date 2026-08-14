<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationModifiedEvent;
use App\Jobs\Reservation\ProcessReservationModified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ListenReservationModified — Thin listener boundary.
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ListenReservationModified implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ReservationModifiedEvent $event): void
    {
        ProcessReservationModified::dispatch($event);
    }
}
