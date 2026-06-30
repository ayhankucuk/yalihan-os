<?php

namespace App\Services\AI\Copilot\Pipeline;

use App\Enums\PipelineAdimDurumu;
use App\Enums\PipelineDurumu;
use App\Models\PipelineRun;
use App\Models\PipelineStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * State machine for pipeline runs and steps.
 * Enforces valid transitions, prevents race conditions, and guards cross-stage corruption.
 */
class PipelineStateManager
{
    /**
     * Allowed transitions: from → [to, to, ...]
     */
    private const RUN_TRANSITIONS = [
        'queued'                => ['normalizing', 'failed', 'cancelled'],
        'normalizing'           => ['validated', 'failed', 'halted'],
        'validated'             => ['audit_running', 'failed'],
        'audit_running'         => ['fix_running', 'governing', 'failed'],
        'fix_running'           => ['execution_running', 'governing', 'failed'],
        'execution_running'     => ['verification_running', 'governing', 'failed'],
        'verification_running'  => ['governing', 'failed'],
        'governing'             => ['completed', 'failed', 'halted'],
        // terminal states: no transitions out
        'completed'             => [],
        'failed'                => [],
        'halted'                => [],
        'cancelled'             => [],
    ];

    private const STEP_TRANSITIONS = [
        'pending'   => ['running', 'skipped', 'blocked'],
        'running'   => ['completed', 'failed'],
        'completed' => [],
        'failed'    => ['running'], // retry
        'skipped'   => [],
        'blocked'   => [],
    ];

    /**
     * Maps pipeline durumu to the corresponding step name.
     */
    public const STAGE_TO_STEP = [
        'audit_running'         => 'audit',
        'fix_running'           => 'fix',
        'execution_running'     => 'execution',
        'verification_running'  => 'verification',
        'governing'             => 'govern',
    ];

    /**
     * Expected pipeline stage when a step job runs.
     * Prevents cross-stage corruption (e.g. execution retry after verify started).
     */
    public const STEP_EXPECTED_STAGE = [
        'normalize'    => ['queued', 'normalizing'],
        'audit'        => ['validated', 'audit_running'],
        'fix'          => ['audit_running', 'fix_running'],
        'execution'    => ['fix_running', 'execution_running'],
        'verification' => ['execution_running', 'verification_running'],
        'govern'       => ['verification_running', 'governing'],
    ];

    /**
     * Transition a pipeline run with DB-level locking to prevent race conditions.
     */
    public function transitionRun(PipelineRun $run, PipelineDurumu $to): bool
    {
        return DB::transaction(function () use ($run, $to) {
            // Lock the row to prevent concurrent transitions
            $locked = PipelineRun::lockForUpdate()->find($run->id);

            if (!$locked) {
                return false;
            }

            $from = $locked->pipeline_durumu->value;
            $toValue = $to->value;

            if (!$this->canTransitionRun($from, $toValue)) {
                Log::warning('PipelineStateManager: invalid run transition', [
                    'run_id' => $locked->id,
                    'from' => $from,
                    'to' => $toValue,
                ]);
                return false;
            }

            $updates = ['pipeline_durumu' => $to];

            // Track current stage
            if (isset(self::STAGE_TO_STEP[$toValue])) {
                $updates['mevcut_asama'] = self::STAGE_TO_STEP[$toValue];
            }

            // Mark start/finish timestamps
            if ($from === 'queued' && !$locked->started_at) {
                $updates['started_at'] = now();
            }

            if ($to->isTerminal()) {
                $updates['finished_at'] = now();
            }

            $locked->update($updates);

            // Refresh the original model
            $run->refresh();

            return true;
        });
    }

    public function transitionStep(PipelineStep $step, PipelineAdimDurumu $to): bool
    {
        $from = $step->adim_durumu->value;
        $toValue = $to->value;

        if (!$this->canTransitionStep($from, $toValue)) {
            Log::warning('PipelineStateManager: invalid step transition', [
                'step_id' => $step->id,
                'step_name' => $step->adim_adi,
                'from' => $from,
                'to' => $toValue,
            ]);
            return false;
        }

        return true; // Caller uses step->markRunning() etc.
    }

    public function canTransitionRun(string $from, string $to): bool
    {
        return in_array($to, self::RUN_TRANSITIONS[$from] ?? []);
    }

    public function canTransitionStep(string $from, string $to): bool
    {
        return in_array($to, self::STEP_TRANSITIONS[$from] ?? []);
    }

    /**
     * Verify a step is running at the expected pipeline stage.
     * Prevents cross-stage corruption (e.g., execution retry after verify started).
     */
    public function assertStageFor(PipelineRun $run, string $stepName): bool
    {
        $allowed = self::STEP_EXPECTED_STAGE[$stepName] ?? [];
        $currentStage = $run->pipeline_durumu->value;

        if (!in_array($currentStage, $allowed)) {
            Log::error('PipelineStateManager: cross-stage corruption detected', [
                'run_id' => $run->id,
                'step' => $stepName,
                'expected_stages' => $allowed,
                'actual_stage' => $currentStage,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Fail a run with DB locking, skip all remaining pending steps.
     */
    public function failRun(PipelineRun $run, string $reason): void
    {
        $this->transitionRun($run, PipelineDurumu::FAILED);

        $run->update(['karar_gerekcesi' => $reason]);

        // Skip all pending steps
        $run->steps()
            ->where('adim_durumu', PipelineAdimDurumu::PENDING->value)
            ->each(fn (PipelineStep $step) => $step->markSkipped('Pipeline failed: ' . $reason));
    }

    /**
     * Acquire a step with DB lock — prevents duplicate execution from concurrent workers.
     * Returns the step only if it can be executed, null otherwise.
     */
    public function acquireStep(PipelineRun $run, string $stepName): ?PipelineStep
    {
        return DB::transaction(function () use ($run, $stepName) {
            $step = PipelineStep::lockForUpdate()
                ->where('pipeline_run_id', $run->id)
                ->where('adim_adi', $stepName)
                ->first();

            if (!$step) {
                return null;
            }

            // Already completed or running — another worker got it
            if ($step->adim_durumu === PipelineAdimDurumu::COMPLETED) {
                return null;
            }

            if ($step->adim_durumu === PipelineAdimDurumu::RUNNING) {
                return null;
            }

            return $step;
        });
    }

    /**
     * Check if a step already completed (idempotency guard).
     */
    public function isStepCompleted(PipelineRun $run, string $stepName): bool
    {
        return $run->steps()
            ->where('adim_adi', $stepName)
            ->where('adim_durumu', PipelineAdimDurumu::COMPLETED->value)
            ->exists();
    }

    /**
     * Get or create a step record (idempotent).
     */
    public function getOrCreateStep(PipelineRun $run, string $stepName, ?string $agentName = null): PipelineStep
    {
        return PipelineStep::firstOrCreate(
            [
                'pipeline_run_id' => $run->id,
                'adim_adi' => $stepName,
            ],
            [
                'agent_adi' => $agentName,
                'adim_durumu' => PipelineAdimDurumu::PENDING,
            ]
        );
    }
}
