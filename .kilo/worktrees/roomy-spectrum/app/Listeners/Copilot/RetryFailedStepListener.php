<?php

namespace App\Listeners\Copilot;

use App\Events\Copilot\PipelineStepFailed;
use App\Services\AI\Copilot\Pipeline\PipelineDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Auto-retry failed steps that haven't exhausted their retry budget.
 * Listens to: PipelineStepFailed
 */
class RetryFailedStepListener implements ShouldQueue
{
    public string $queue = 'copilot-high';

    public function __construct(
        protected PipelineDispatcher $dispatcher,
    ) {}

    public function handle(PipelineStepFailed $event): void
    {
        $step = $event->step;
        $run = $event->run;

        // Don't retry if run is already terminal
        if ($run->pipeline_durumu->isTerminal()) {
            return;
        }

        $maxRetries = config("copilot-pipeline.retries.{$step->adim_adi}", 0);
        $currentAttempt = $step->deneme_sayisi ?? 1;

        if ($currentAttempt >= $maxRetries) {
            Log::warning('RetryFailedStepListener: retry budget exhausted', [
                'run_uuid' => $run->run_uuid,
                'step' => $step->adim_adi,
                'attempts' => $currentAttempt,
                'max_retries' => $maxRetries,
            ]);
            return;
        }

        Log::info('RetryFailedStepListener: scheduling retry', [
            'run_uuid' => $run->run_uuid,
            'step' => $step->adim_adi,
            'attempt' => $currentAttempt + 1,
            'max_retries' => $maxRetries,
        ]);

        // Reset step for retry
        $step->update([
            'adim_durumu' => \App\Enums\PipelineAdimDurumu::PENDING,
            'deneme_sayisi' => $currentAttempt + 1,
            'hata_mesaji' => null,
        ]);

        $this->dispatcher->redispatchStep($run, $step);
    }
}
