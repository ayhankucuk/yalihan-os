<?php

namespace App\Listeners\Copilot;

use App\Events\Copilot\PipelineStepFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Alert on pipeline step failures via logging.
 * Extensible: add Slack / PagerDuty / webhook notifications.
 * Listens to: PipelineStepFailed
 */
class AlertOnPipelineFailureListener implements ShouldQueue
{
    public string $queue = 'copilot-default';

    public function handle(PipelineStepFailed $event): void
    {
        $step = $event->step;
        $run = $event->run;

        Log::channel('security')->error('Pipeline step failure alert', [
            'run_uuid' => $run->run_uuid,
            'step' => $step->adim_adi,
            'error' => $step->hata_mesaji,
            'pipeline_durumu' => $run->pipeline_durumu->value,
            'tetikleyen' => $run->tetikleyen,
            'started_at' => $run->started_at?->toIso8601String(),
        ]);
    }
}
