<?php

namespace App\Listeners;

use App\Events\LeadAgentAtandi;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Traits\ListenerTelemetry;

/**
 * Listener: NotifyAgentOnLeadAssignment
 * Phase 12 Event-Driven Architecture.
 * Lead'e yeni bir agent atandığında çalışır ve asenkron (ShouldQueue) bildirim atar.
 */
class NotifyAgentOnLeadAssignment implements ShouldQueue
{
    use InteractsWithQueue, ListenerTelemetry;

    /**
     * The name of the connection the job should be sent to.
     */
    public string $connection = 'database';

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'high'; // Öncelikli

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create the event listener.
     */
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(LeadAgentAtandi $event): void
    {
        $this->startTelemetry();
        $lead = $event->lead;
        $agentId = $event->yeniAgentId;

        try {
            // Atanan agent'a bildirim gönder
            $this->notificationService->sendNotification(
                $agentId, // Kime: Atanan Agent
                'lead_assigned', // Tip
                [
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->name ?? 'Yeni Müşteri',
                    'platform' => $lead->platform,
                ],
                ['channels' => ['database', 'mail']] // Sadece database (uygulama içi) ve mail
            );

            $this->finishTelemetry('LeadAgentAtandi', [
                'lead_id' => $lead->id,
                'agent_id' => $agentId,
            ]);

        } catch (\Exception $e) {
            // Rethrow for ShouldQueue retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(LeadAgentAtandi $event, \Throwable $exception): void
    {
        $this->recordFailure($exception, 'LeadAgentAtandi', [
            'lead_id' => $event->lead->id ?? null,
            'agent_id' => $event->yeniAgentId ?? null,
        ]);
    }
}
