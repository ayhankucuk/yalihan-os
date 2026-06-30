<?php

namespace App\Listeners\Copilot;

use App\Events\Copilot\PipelineGoverned;
use App\Events\Copilot\PipelineStepCompleted;
use App\Events\Copilot\PipelineStepFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Collects pipeline execution metrics for observability.
 * Logs step durations, outcomes, and governance decisions.
 * Listens to: PipelineStepCompleted, PipelineStepFailed, PipelineGoverned
 */
class PipelineMetricsListener implements ShouldQueue
{
    public string $queue = 'copilot-default';

    public function handleStepCompleted(PipelineStepCompleted $event): void
    {
        $this->logStepMetric($event->run->run_uuid, $event->step, 'completed');
    }

    public function handleStepFailed(PipelineStepFailed $event): void
    {
        $this->logStepMetric($event->run->run_uuid, $event->step, 'failed');
    }

    public function handleGoverned(PipelineGoverned $event): void
    {
        $run = $event->run;
        $decision = $event->decision;

        $totalDuration = $run->started_at && $run->finished_at
            ? $run->started_at->diffInMilliseconds($run->finished_at)
            : null;

        Log::channel('telemetry')->info('pipeline_governance_metric', [
            'run_uuid' => $run->run_uuid,
            'karar_aksiyonu' => $decision['action'] ?? 'unknown',
            'confidence' => $decision['confidence'] ?? 0,
            'signal_count' => count($decision['signals'] ?? []),
            'total_duration_ms' => $totalDuration,
            'pipeline_durumu' => $run->pipeline_durumu->value,
        ]);
    }

    protected function logStepMetric(string $runUuid, $step, string $outcome): void
    {
        $durationMs = $step->duration_ms ?? ($step->started_at && $step->finished_at
            ? $step->started_at->diffInMilliseconds($step->finished_at)
            : null);

        Log::channel('telemetry')->info('pipeline_step_metric', [
            'run_uuid' => $runUuid,
            'step' => $step->adim_adi,
            'outcome' => $outcome,
            'duration_ms' => $durationMs,
            'attempt' => $step->deneme_sayisi ?? 1,
        ]);
    }

    /**
     * Subscribe to multiple events.
     */
    public function subscribe($events): array
    {
        return [
            PipelineStepCompleted::class => 'handleStepCompleted',
            PipelineStepFailed::class => 'handleStepFailed',
            PipelineGoverned::class => 'handleGoverned',
        ];
    }
}
