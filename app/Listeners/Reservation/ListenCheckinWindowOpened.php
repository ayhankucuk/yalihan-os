<?php

namespace App\Listeners\Reservation;

use App\Events\Reservation\CheckinWindowOpenedEvent;
use App\Jobs\Reservation\ProcessCheckinWindowOpenedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * ListenCheckinWindowOpened — Thin listener boundary.
 *
 * CHECKIN_CHECKOUT Wave 3
 *
 * Receives canonical CheckinWindowOpenedEvent and dispatches the
 * queue-safe ProcessCheckinWindowOpenedJob for downstream processing.
 *
 * Design rule: Listeners must NEVER contain business logic.
 * They only translate events into job dispatches.
 */
class ListenCheckinWindowOpened implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CheckinWindowOpenedEvent $event): void
    {
        ProcessCheckinWindowOpenedJob::dispatch($event);
    }
}
