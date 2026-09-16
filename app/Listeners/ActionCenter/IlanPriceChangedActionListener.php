<?php

namespace App\Listeners\ActionCenter;

use App\Events\IlanPriceChanged;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * IlanPriceChangedActionListener — Action Center listener for IlanPriceChanged event.
 *
 * Generates 1 Gorev: re-evaluate matching (+4h).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class IlanPriceChangedActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(IlanPriceChanged $event): void
    {
        Log::info('IlanPriceChangedActionListener: generating actions', [
            'ilan_id' => $event->ilan->id,
            'old_price' => $event->oldPrice,
            'new_price' => $event->newPrice,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('IlanPriceChangedActionListener: actions generated', [
            'ilan_id' => $event->ilan->id,
            'count' => $actions->count(),
        ]);
    }

    public function failed(IlanPriceChanged $event, \Throwable $exception): void
    {
        Log::error('IlanPriceChangedActionListener: failed', [
            'ilan_id' => $event->ilan->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
