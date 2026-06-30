<?php

namespace App\Jobs\Copilot;

use App\Enums\PipelineAdimDurumu;
use App\Enums\PipelineDurumu;
use App\Events\Copilot\PipelineStepCompleted;
use App\Events\Copilot\PipelineStepFailed;
use App\Models\PipelineRun;
use App\Services\AI\Copilot\Pipeline\PipelineDispatcher;
use App\Services\AI\Copilot\Pipeline\PipelineStateManager;
use App\Services\AI\Copilot\Support\OutputContractValidator;
use App\Services\AI\Copilot\Support\OutputNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * First job in the pipeline chain.
 * Normalizes + validates input, then dispatches audit.
 */
class StartPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public readonly int $pipelineRunId,
    ) {}

    public function handle(
        PipelineStateManager $stateManager,
        PipelineDispatcher $dispatcher,
        OutputNormalizer $normalizer,
        OutputContractValidator $validator,
    ): void {
        $run = PipelineRun::findOrFail($this->pipelineRunId);

        // Idempotency: if already past queued, exit
        if ($run->pipeline_durumu !== PipelineDurumu::QUEUED) {
            return;
        }

        $step = $stateManager->getOrCreateStep($run, 'normalize', 'StartPipelineJob');

        if ($step->adim_durumu === PipelineAdimDurumu::COMPLETED) {
            return;
        }

        $step->markRunning();
        $stateManager->transitionRun($run, PipelineDurumu::NORMALIZING);

        try {
            $input = $run->input_payload ?? [];

            // Normalize
            $normalized = $normalizer->normalize($input);

            // Validate contract
            $validator->validate($normalized);

            $step->markCompleted(['normalized' => true, 'warnings' => []]);
            $run->update([
                'normalized_payload' => $normalized,
                'completed_steps' => $run->completed_steps + 1,
            ]);

            $stateManager->transitionRun($run, PipelineDurumu::VALIDATED);

            PipelineStepCompleted::dispatch($run, $step);

            // Chain: dispatch audit
            $dispatcher->dispatchNextStep($run, 'normalize');

        } catch (\Throwable $e) {
            $step->markFailed($e->getMessage());
            $stateManager->failRun($run, 'Normalization/validation failed: ' . $e->getMessage());
            PipelineStepFailed::dispatch($run, $step);

            Log::error('StartPipelineJob failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
