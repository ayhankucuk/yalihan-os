<?php

namespace App\Listeners\ActionCenter;

use App\Events\Reservation\ReservationPayoutReadyEvent;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * PayoutReadyActionListener — Action Center listener for ReservationPayoutReadyEvent.
 *
 * Generates 1 Gorev: process payout (deadline +24h, acil).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class PayoutReadyActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(ReservationPayoutReadyEvent $event): void
    {
        Log::info('PayoutReadyActionListener: generating actions', [
            'reservation_id' => $event->reservationId,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('PayoutReadyActionListener: actions generated', [
            'reservation_id' => $event->reservationId,
            'count' => $actions->count(),
        ]);
    }

    public function failed(ReservationPayoutReadyEvent $event, \Throwable $exception): void
    {
        Log::error('PayoutReadyActionListener: failed', [
            'reservation_id' => $event->reservationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
