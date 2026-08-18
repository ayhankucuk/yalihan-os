<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCancelledEvent;
use App\Jobs\Reservation\ProcessReservationCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ListenReservationCancelled — Thin listener boundary.
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ListenReservationCancelled implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ReservationCancelledEvent $event): void
    {
        ProcessReservationCancelled::dispatch($event);
    }
}
