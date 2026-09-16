<?php

namespace App\Listeners\ActionCenter;

use App\Events\Workforce\PhotoAnalysisCompleted;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * PhotoAnalysisActionListener — Action Center listener for PhotoAnalysisCompleted event.
 *
 * Generates 1 Gorev: review AI description (deadline +48h).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class PhotoAnalysisActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(PhotoAnalysisCompleted $event): void
    {
        Log::info('PhotoAnalysisActionListener: generating actions', [
            'ilan_id' => $event->workspace->ilan_id,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('PhotoAnalysisActionListener: actions generated', [
            'ilan_id' => $event->workspace->ilan_id,
            'count' => $actions->count(),
        ]);
    }

    public function failed(PhotoAnalysisCompleted $event, \Throwable $exception): void
    {
        Log::error('PhotoAnalysisActionListener: failed', [
            'ilan_id' => $event->workspace->ilan_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
