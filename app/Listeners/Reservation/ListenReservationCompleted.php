<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\ReservationCompletedEvent;
use App\Jobs\Reservation\ProcessFinancialCompletionJob;
use App\Jobs\Reservation\ProcessReservationCompletedJob;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ListenReservationCompleted — Thin listener boundary.
 *
 * Receives canonical ReservationCompletedEvent and dispatches downstream jobs:
 *  1. ProcessFinancialCompletionJob — financial lifecycle closure (C1)
 *  2. ProcessReservationCompletedJob — turnover cleaning task (pre-existing)
 *
 * Design rule: Listeners must NEVER contain business logic.
 * They only translate events into job dispatches.
 *
 * C2: ShouldQueueAfterCommit — listener dispatches jobs only after the
 * parent transaction commits. This ensures that if the transaction rolls back,
 * neither the financial completion nor the turnover task is queued.
 *
 * CHECKOUT-D1: ReservationCompletedEvent now wired
 * Baseline: 88ccfc8
 *
 * C1: Financial Completion — 667c1b4
 * C2: Queue / Transaction Safety — 33f9f50
 */
class ListenReservationCompleted implements ShouldQueueAfterCommit
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
