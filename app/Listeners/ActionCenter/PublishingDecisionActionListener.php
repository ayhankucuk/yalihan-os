<?php

namespace App\Listeners\ActionCenter;

use App\Events\Workforce\PublishingDecisionReady;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * PublishingDecisionActionListener — Action Center listener for PublishingDecisionReady event.
 *
 * Generates 1 Gorev: review publication decision (deadline +24h).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class PublishingDecisionActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(PublishingDecisionReady $event): void
    {
        Log::info('PublishingDecisionActionListener: generating actions', [
            'ilan_id' => $event->workspace->ilan_id,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('PublishingDecisionActionListener: actions generated', [
            'ilan_id' => $event->workspace->ilan_id,
            'count' => $actions->count(),
        ]);
    }

    public function failed(PublishingDecisionReady $event, \Throwable $exception): void
    {
        Log::error('PublishingDecisionActionListener: failed', [
            'ilan_id' => $event->workspace->ilan_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
