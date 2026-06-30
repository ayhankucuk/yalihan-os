<?php

namespace App\Services\AI\Copilot\Pipeline;

use App\Models\PipelineRun;
use App\Models\PipelineStep;
use App\Enums\PipelineAdimDurumu;

/**
 * Aggregates results from multiple pipeline steps into a unified output.
 * Used by GovernanceResolver to make final decisions.
 */
class PipelineResultAggregator
{
    /**
     * Collect all completed step outputs for a pipeline run.
     *
     * @return array<string, array> step_name => output_payload
     */
    public function collectStepOutputs(PipelineRun $run): array
    {
        return $run->steps()
            ->where('adim_durumu', PipelineAdimDurumu::COMPLETED->value)
            ->pluck('output_payload', 'adim_adi')
            ->toArray();
    }

    /**
     * Aggregate findings from audit step.
     */
    public function getAuditFindings(PipelineRun $run): array
    {
        $auditStep = $run->steps()->forStep('audit')->first();

        if (!$auditStep || !$auditStep->output_payload) {
            return [];
        }

        return $auditStep->output_payload['findings'] ?? [];
    }

    /**
     * Aggregate fix plans from fix step.
     */
    public function getFixPlans(PipelineRun $run): array
    {
        $fixStep = $run->steps()->forStep('fix')->first();

        if (!$fixStep || !$fixStep->output_payload) {
            return [];
        }

        return $fixStep->output_payload['fixes'] ?? [];
    }

    /**
     * Aggregate verification results.
     */
    public function getVerificationResults(PipelineRun $run): array
    {
        $verifyStep = $run->steps()->forStep('verification')->first();

        if (!$verifyStep || !$verifyStep->output_payload) {
            return [];
        }

        return $verifyStep->output_payload['verification'] ?? [];
    }

    /**
     * Count failures across all steps.
     */
    public function countFailedSteps(PipelineRun $run): int
    {
        return $run->steps()
            ->where('adim_durumu', PipelineAdimDurumu::FAILED->value)
            ->count();
    }

    /**
     * Get all warnings across all steps.
     */
    public function collectWarnings(PipelineRun $run): array
    {
        $warnings = [];

        $run->steps()
            ->where('adim_durumu', PipelineAdimDurumu::COMPLETED->value)
            ->each(function (PipelineStep $step) use (&$warnings) {
                $output = $step->output_payload;
                if (!empty($output['warnings'])) {
                    foreach ($output['warnings'] as $warning) {
                        $warnings[] = "[{$step->adim_adi}] {$warning}";
                    }
                }
            });

        return $warnings;
    }

    /**
     * Build the final aggregated output.
     */
    public function buildFinalOutput(PipelineRun $run): array
    {
        $outputs = $this->collectStepOutputs($run);

        return [
            'stage' => 'govern',
            'findings' => $this->getAuditFindings($run),
            'fixes' => $this->getFixPlans($run),
            'execution' => $outputs['execution'] ?? [],
            'verification' => $this->getVerificationResults($run),
            'warnings' => $this->collectWarnings($run),
            'meta' => [
                'run_uuid' => $run->run_uuid,
                'pipeline_type' => $run->pipeline_type,
                'module' => $run->module,
                'total_steps' => $run->total_steps,
                'completed_steps' => $run->completed_steps,
                'failed_steps' => $this->countFailedSteps($run),
                'duration_ms' => $run->durationMs(),
            ],
        ];
    }
}
