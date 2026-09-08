<?php

namespace App\Listeners\ActionCenter;

use App\Events\TalepReceived;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * TalepReceivedActionListener — Action Center listener for TalepReceived event.
 *
 * Generates 1 Gorev: match demand to listings (deadline +4h).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class TalepReceivedActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(TalepReceived $event): void
    {
        Log::info('TalepReceivedActionListener: generating actions', [
            'talep_id' => $event->talep->id,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('TalepReceivedActionListener: actions generated', [
            'talep_id' => $event->talep->id,
            'count' => $actions->count(),
        ]);
    }

    public function failed(TalepReceived $event, \Throwable $exception): void
    {
        Log::error('TalepReceivedActionListener: failed', [
            'talep_id' => $event->talep->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
