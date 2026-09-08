<?php

namespace App\Listeners\ActionCenter;

use App\Events\IlanCreated;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * IlanCreatedActionListener — Action Center listener for IlanCreated event.
 *
 * Generates 3 Gorev records: foto yükle, açıklama yaz, fiyatlandırma.
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class IlanCreatedActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(IlanCreated $event): void
    {
        Log::info('IlanCreatedActionListener: generating actions', [
            'ilan_id' => $event->ilan->id,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('IlanCreatedActionListener: actions generated', [
            'ilan_id' => $event->ilan->id,
            'count' => $actions->count(),
        ]);
    }

    public function failed(IlanCreated $event, \Throwable $exception): void
    {
        Log::error('IlanCreatedActionListener: failed', [
            'ilan_id' => $event->ilan->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
