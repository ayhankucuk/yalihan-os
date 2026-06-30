<?php

namespace App\Jobs\Copilot\Concerns;

use App\Enums\PipelineAdimDurumu;
use App\Models\PipelineRun;
use App\Models\PipelineStep;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Shared logic for verification shard (fan-out) jobs.
 * Handles step creation, locking, completion, and failure.
 * Context7 field naming: adim_adi, adim_durumu, hata_mesaji, etc.
 */
trait HandlesVerificationShard
{
    protected function startShardStep(
        int $pipelineRunId,
        string $stepName,
        string $agentName,
        string $shardKey,
        array $inputPayload = []
    ): ?PipelineStep {
        $run = PipelineRun::query()->findOrFail($pipelineRunId);

        if ($run->pipeline_durumu->isTerminal()) {
            return null;
        }

        return DB::transaction(function () use ($pipelineRunId, $stepName, $agentName, $shardKey, $inputPayload) {
            $step = PipelineStep::query()->lockForUpdate()->firstOrCreate(
                [
                    'pipeline_run_id' => $pipelineRunId,
                    'adim_adi' => $stepName,
                    'shard_key' => $shardKey,
                ],
                [
                    'agent_adi' => $agentName,
                    'adim_durumu' => PipelineAdimDurumu::PENDING,
                    'queue_name' => 'copilot-verification',
                    'deneme_sayisi' => 0,
                ]
            );

            if ($step->adim_durumu === PipelineAdimDurumu::COMPLETED) {
                return null;
            }

            $step->update([
                'adim_durumu' => PipelineAdimDurumu::RUNNING,
                'started_at' => now(),
                'deneme_sayisi' => $step->deneme_sayisi + 1,
                'input_payload' => $inputPayload,
            ]);

            return $step->fresh();
        });
    }

    protected function completeShardStep(PipelineStep $step, array $outputPayload, array $meta = []): void
    {
        $step->update([
            'adim_durumu' => PipelineAdimDurumu::COMPLETED,
            'output_payload' => $outputPayload,
            'meta' => $meta,
            'finished_at' => now(),
            'duration_ms' => $step->started_at
                ? (int) $step->started_at->diffInMilliseconds(now())
                : null,
        ]);
    }

    protected function failShardStep(?PipelineStep $step, Throwable $e): void
    {
        if (!$step) {
            return;
        }

        $step->update([
            'adim_durumu' => PipelineAdimDurumu::FAILED,
            'hata_mesaji' => $e->getMessage(),
            'finished_at' => now(),
            'duration_ms' => $step->started_at
                ? (int) $step->started_at->diffInMilliseconds(now())
                : null,
        ]);
    }
}
