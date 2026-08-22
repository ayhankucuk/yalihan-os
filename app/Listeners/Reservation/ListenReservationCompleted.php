<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCompletedEvent;
use App\Jobs\Reservation\ProcessFinancialCompletionJob;
use App\Jobs\Reservation\ProcessReservationCompletedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ListenReservationCompleted — Thin listener boundary.
 *
 * Receives canonical ReservationCompletedEvent and dispatches downstream jobs:
 *  1. ProcessReservationCompletedJob — turnover cleaning task (pre-existing)
 *  2. ProcessFinancialCompletionJob — financial lifecycle closure (C1)
 *
 * Design rule: Listeners must NEVER contain business logic.
 * They only translate events into job dispatches.
 *
 * CHECKOUT-D1: ReservationCompletedEvent now wired
 * Baseline: 88ccfc8
 *
 * C1: Financial Completion — 667c1b4
 */
class ListenReservationCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ReservationCompletedEvent $event): void
    {
        // C1: Financial completion consumer
        ProcessFinancialCompletionJob::dispatch($event);

        // Pre-existing: turnover cleaning task
        ProcessReservationCompletedJob::dispatch($event);
    }
}
