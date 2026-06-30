<?php

namespace App\Listeners;

use App\Events\LeadOlusturuldu;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Traits\ListenerTelemetry;

/**
 * Listener: NotifyAdminsOnNewLead
 * Phase 12 Event-Driven Architecture.
 * Sistemde webhook üzerinden yeni bir Lead oluştuğunda çalışır.
 * (Eski sistemdeki Telegram bildirimi yerini alır).
 */
class NotifyAdminsOnNewLead implements ShouldQueue
{
    use InteractsWithQueue, ListenerTelemetry;

    /**
     * The name of the connection the job should be sent to.
     */
    public string $connection = 'database';

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'default';

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
    public function handle(LeadOlusturuldu $event): void
    {
        $this->startTelemetry();
        $lead = $event->lead;

        try {
            // Adminlere / İlgili gruba notification gönder
            $this->notificationService->sendNotification(
                1, // Şimdilik system admin (ID: 1) veya telegram chat id
                'new_lead',
                [
                    'lead_id' => $lead->id,
                    'message' => $lead->first_message ?? 'Mesaj yok',
                    'platform' => $lead->platform,
                ],
                ['channels' => ['telegram', 'database']]
            );

            $this->finishTelemetry('LeadOlusturuldu', [
                'lead_id' => $lead->id,
            ]);

        } catch (\Exception $e) {
            // Re-throw for ShouldQueue retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(LeadOlusturuldu $event, \Throwable $exception): void
    {
        $this->recordFailure($exception, 'LeadOlusturuldu', [
            'lead_id' => $event->lead->id ?? null
        ]);
    }
}
