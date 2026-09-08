<?php

namespace App\Listeners\ActionCenter;

use App\Events\LeadOlusturuldu;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * LeadCreatedActionListener — Action Center listener for LeadOlusturuldu event.
 *
 * Generates 1 Gorev: contact lead SLA (deadline +2h, acil).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class LeadCreatedActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(LeadOlusturuldu $event): void
    {
        Log::info('LeadCreatedActionListener: generating actions', [
            'lead_id' => $event->lead->id,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('LeadCreatedActionListener: actions generated', [
            'lead_id' => $event->lead->id,
            'count' => $actions->count(),
        ]);
    }

    public function failed(LeadOlusturuldu $event, \Throwable $exception): void
    {
        Log::error('LeadCreatedActionListener: failed', [
            'lead_id' => $event->lead->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
