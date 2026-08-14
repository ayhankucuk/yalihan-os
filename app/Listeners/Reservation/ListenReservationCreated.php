<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCreatedEvent;
use App\Jobs\Reservation\ProcessReservationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ListenReservationCreated — Thin listener boundary.
 *
 * Receives canonical ReservationCreatedEvent and dispatches the
 * queue-safe job for downstream processing.
 *
 * Design rule: Listeners must NEVER contain business logic.
 * They only translate events into job dispatches.
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ListenReservationCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ReservationCreatedEvent $event): void
    {
        ProcessReservationCreated::dispatch($event);
    }
}
