<?php

namespace App\Listeners\ActionCenter;

use App\Events\LeadAgentAtandi;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * LeadAssignedActionListener — Action Center listener for LeadAgentAtandi event.
 *
 * Generates 1 Gorev: contact assigned lead (deadline +4h).
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.1
 */
class LeadAssignedActionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(LeadAgentAtandi $event): void
    {
        Log::info('LeadAssignedActionListener: generating actions', [
            'lead_id' => $event->lead->id,
            'agent_id' => $event->yeniAgentId,
        ]);

        $actions = $this->actionCenterService->generateActionsFromEvent($event);

        Log::info('LeadAssignedActionListener: actions generated', [
            'lead_id' => $event->lead->id,
            'count' => $actions->count(),
        ]);
    }

    public function failed(LeadAgentAtandi $event, \Throwable $exception): void
    {
        Log::error('LeadAssignedActionListener: failed', [
            'lead_id' => $event->lead->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
