<?php

namespace App\Listeners;

use App\Events\LeadOlusturuldu;
use App\Models\Lead;
use App\Services\AI\YalihanCortex;
use App\Traits\ListenerTelemetry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * AI Lead Değerlendirme Listener'ı
 * SAB v1.3: Asenkron (ShouldQueue), Idempotent, Telemetry ile izlenir.
 */
class EvaluateLeadWithCortex implements ShouldQueue
{
    use InteractsWithQueue, ListenerTelemetry;

    /**
     * The name of the queue the job should be sent to.
     */
    public $queue = 'default';

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 60, 120];

    protected YalihanCortex $cortex;

    /**
     * Create the event listener.
     */
    public function __construct(YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /**
     * Handle the event.
     */
    public function handle(LeadOlusturuldu $event): void
    {
        $this->startTelemetry();
        $lead = $event->lead;

        try {
            // Idempotency: Zaten analiz edilmiş veya kalifiye olmuşsa atla.
            if ($lead->crm_durumu >= Lead::CRM_QUALIFIED || $lead->confidence > 0.8) {
                $this->finishTelemetry('EvaluateLeadWithCortex', [
                    'lead_id' => $lead->id,
                    'islem_sonucu' => 'skipped_idempotent'
                ]);
                return;
            }

            $evaluation = $this->cortex->evaluateLead($lead);

            if ($evaluation['success'] ?? false) {
                $lead->confidence = $evaluation['confidence'] ?? $lead->confidence;

                if (!empty($evaluation['intent'])) {
                    $lead->intent = $evaluation['intent'];
                }

                // AI Kalifikasyonu
                if ($lead->confidence > 0.65 && !empty($lead->intent)) {
                    $lead->crm_durumu = Lead::CRM_QUALIFIED; // enum yerine int 2 (SAB Canonical)
                    $lead->notes = ($lead->notes ? $lead->notes . "\n" : '')
                        . 'Cortex AI: Lead otomatik olarak kalifiye edildi.';
                } else if ($lead->confidence > 0.0) {
                    $lead->crm_durumu = Lead::CRM_CONTACTED; // 1
                }

                $lead->save();
            }

            $this->finishTelemetry('EvaluateLeadWithCortex', [
                'lead_id' => $lead->id,
                'cortex_success' => $evaluation['success'] ?? false,
            ]);

        } catch (\Throwable $exception) {
            $this->recordFailure($exception, 'EvaluateLeadWithCortex', ['lead_id' => $lead->id]);
            // Retry (backoff) yapabilmesi için hatayı fırlat.
            // ShouldQueue Worker bu hatayı alır, limit aşılırsa failed() düşer.
            throw $exception;
        }
    }

    /**
     * Handle a job failure.
     * Silent catch yasağına uygun olarak fırlatılan exception'lar Max Tries aşımında buraya düşer.
     */
    public function failed(LeadOlusturuldu $event, \Throwable $exception): void
    {
        Log::error('[EvaluateLeadWithCortex] Max retries exhausted for AI Lead Evaluation.', [
            'lead_id' => $event->lead->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
