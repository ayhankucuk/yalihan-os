<?php

namespace App\Listeners\ActionCenter;

use App\Events\IlanYayinlandiEvent;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * IlanPublishedActionListener — Action Center listener for IlanYayinlandiEvent.
 *
 * Generates 1 Gorev: lead matching check (deadline +1h).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class IlanPublishedActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(IlanYayinlandiEvent $event): void
    {
        Log::info('IlanPublishedActionListener: generating actions', [
            'ilan_id' => $event->ilan->id,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('IlanPublishedActionListener: actions generated', [
            'ilan_id' => $event->ilan->id,
            'count' => $actions->count(),
        ]);
    }

    public function failed(IlanYayinlandiEvent $event, \Throwable $exception): void
    {
        Log::error('IlanPublishedActionListener: failed', [
            'ilan_id' => $event->ilan->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
