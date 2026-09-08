<?php

namespace App\Listeners\ActionCenter;

use App\Events\Reservation\ReservationCancelledEvent;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * ReservationCancelledActionListener — Action Center listener for ReservationCancelledEvent.
 *
 * Generates 1 Gorev: cancel readiness (immediate, acil).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class ReservationCancelledActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(ReservationCancelledEvent $event): void
    {
        Log::info('ReservationCancelledActionListener: generating actions', [
            'reservation_id' => $event->reservationId,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('ReservationCancelledActionListener: actions generated', [
            'reservation_id' => $event->reservationId,
            'count' => $actions->count(),
        ]);
    }

    public function failed(ReservationCancelledEvent $event, \Throwable $exception): void
    {
        Log::error('ReservationCancelledActionListener: failed', [
            'reservation_id' => $event->reservationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
