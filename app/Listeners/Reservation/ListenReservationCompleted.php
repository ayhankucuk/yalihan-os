<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCompletedEvent;
use App\Jobs\Reservation\ProcessReservationCompletedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ListenReservationCompleted — Thin listener boundary.
 *
 * Receives canonical ReservationCompletedEvent and dispatches the
 * queue-safe job for turnover task creation.
 *
 * Design rule: Listeners must NEVER contain business logic.
 * They only translate events into job dispatches.
 *
 * CHECKOUT-D1: ReservationCompletedEvent now wired
 * Baseline: 88ccfc8
 */
class ListenReservationCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ReservationCompletedEvent $event): void
    {
        ProcessReservationCompletedJob::dispatch($event);
    }
}
