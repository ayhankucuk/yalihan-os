<?php

namespace App\Services\AI\Copilot\Pipeline;

use App\Enums\PipelineAdimDurumu;
use App\Enums\PipelineDurumu;
use App\Jobs\Copilot\RunAuditStepJob;
use App\Jobs\Copilot\RunExecutionPlanStepJob;
use App\Jobs\Copilot\RunFixPlanStepJob;
use App\Jobs\Copilot\RunGovernanceStepJob;
use App\Jobs\Copilot\RunVerificationBatchJob;
use App\Jobs\Copilot\StartPipelineJob;
use App\Models\PipelineRun;
use App\Models\PipelineStep;
use App\Services\AI\Copilot\Support\OutputNormalizer;
use App\Services\AI\Copilot\Support\OutputContractValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Creates pipeline runs, provisions steps, and dispatches jobs.
 * The orchestrator no longer runs work — it dispatches it.
 */
class PipelineDispatcher
{
    /**
     * Sequential step chain for a full pipeline.
     * Order matters: each step dispatches the next on completion.
     */
    public const FULL_PIPELINE_STEPS = [
        'normalize',
        'audit',
        'fix',
        'execution',
        'verification',
        'govern',
    ];

    public function __construct(
        protected PipelineStateManager $stateManager,
        protected OutputNormalizer $normalizer,
        protected OutputContractValidator $validator,
    ) {}

    /**
     * Create a new pipeline run and dispatch it.
     * Returns run_uuid immediately — all work happens async.
     */
    public function dispatch(
        string $pipelineType,
        array $inputPayload,
        ?string $module = null,
        ?int $triggeredBy = null,
    ): string {
        $run = PipelineRun::create([
            'run_uuid' => (string) Str::uuid(),
            'pipeline_type' => $pipelineType,
            'module' => $module,
            'pipeline_durumu' => PipelineDurumu::QUEUED,
            'input_payload' => $inputPayload,
            'total_steps' => count(self::FULL_PIPELINE_STEPS),
            'triggered_by' => $triggeredBy,
        ]);

        // Pre-create all step records
        foreach (self::FULL_PIPELINE_STEPS as $stepName) {
            PipelineStep::create([
                'pipeline_run_id' => $run->id,
                'adim_adi' => $stepName,
                'adim_durumu' => PipelineAdimDurumu::PENDING,
                'queue_name' => $this->queueForStep($stepName),
            ]);
        }

        Log::info('PipelineDispatcher: run created', [
            'run_uuid' => $run->run_uuid,
            'type' => $pipelineType,
            'module' => $module,
        ]);

        // Dispatch the first job
        StartPipelineJob::dispatch($run->id)
            ->onQueue($this->queueForStep('normalize'));

        return $run->run_uuid;
    }

    /**
     * Dispatch the next step job after a step completes.
     * Sequential chain: each step triggers the next.
     */
    public function dispatchNextStep(PipelineRun $run, string $completedStep): void
    {
        $steps = self::FULL_PIPELINE_STEPS;
        $currentIndex = array_search($completedStep, $steps);

        if ($currentIndex === false || $currentIndex >= count($steps) - 1) {
            // Last step or unknown — nothing to dispatch
            return;
        }

        $nextStep = $steps[$currentIndex + 1];

        $jobClass = $this->jobForStep($nextStep);
        if (!$jobClass) {
            Log::error('PipelineDispatcher: no job class for step', ['step' => $nextStep]);
            return;
        }

        $jobClass::dispatch($run->id)
            ->onQueue($this->queueForStep($nextStep));
    }

    /**
     * Map step name → queue name.
     */
    public function queueForStep(string $step): string
    {
        $config = config('copilot-pipeline.queues', []);

        return match ($step) {
            'normalize'    => $config['high'] ?? 'copilot-high',
            'audit'        => $config['default'] ?? 'copilot-default',
            'fix'          => $config['default'] ?? 'copilot-default',
            'execution'    => $config['default'] ?? 'copilot-default',
            'verification' => $config['verification'] ?? 'copilot-verification',
            'govern'       => $config['governance'] ?? 'copilot-governance',
            default        => $config['default'] ?? 'copilot-default',
        };
    }

    /**
     * Map step name → job class.
     */
    protected function jobForStep(string $step): ?string
    {
        return match ($step) {
            'audit'        => RunAuditStepJob::class,
            'fix'          => RunFixPlanStepJob::class,
            'execution'    => RunExecutionPlanStepJob::class,
            'verification' => RunVerificationBatchJob::class,
            'govern'       => RunGovernanceStepJob::class,
            default        => null,
        };
    }

    /**
     * Re-dispatch a specific step for retry.
     * Used by RetryFailedStepListener after resetting step state.
     */
    public function redispatchStep(PipelineRun $run, PipelineStep $step): void
    {
        $jobClass = $this->jobForStep($step->adim_adi);

        if (!$jobClass) {
            Log::error('PipelineDispatcher: cannot redispatch unknown step', [
                'step' => $step->adim_adi,
                'run_uuid' => $run->run_uuid,
            ]);
            return;
        }

        Log::info('PipelineDispatcher: redispatching step', [
            'run_uuid' => $run->run_uuid,
            'step' => $step->adim_adi,
            'attempt' => $step->deneme_sayisi,
        ]);

        $jobClass::dispatch($run->id)
            ->onQueue($this->queueForStep($step->adim_adi));
    }
}
