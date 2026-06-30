<?php

namespace App\Jobs\Copilot;

use App\Jobs\Copilot\Concerns\HandlesVerificationShard;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Verification shard: regression scan.
 * Runs full test suite with --stop-on-failure as a fast-feedback gate.
 */
class RunRegressionScanJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesVerificationShard;

    public int $timeout = 45;
    public int $tries = 2;

    public function __construct(
        public int $pipelineRunId
    ) {
        $this->onQueue(config('copilot-pipeline.queues.verification', 'copilot-verification'));
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("pipeline-run:{$this->pipelineRunId}:verify:regression"),
        ];
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $step = null;

        try {
            $step = $this->startShardStep(
                pipelineRunId: $this->pipelineRunId,
                stepName: 'verification',
                agentName: 'RegressionScan',
                shardKey: 'regression',
                inputPayload: ['type' => 'regression']
            );

            if (!$step) {
                return;
            }

            $process = new Process(['php', 'artisan', 'test', '--stop-on-failure']);
            $process->setTimeout(40);
            $process->run();

            $passed = $process->isSuccessful();

            $this->completeShardStep(
                $step,
                outputPayload: [
                    'type' => 'regression',
                    'result' => $passed ? 'passed' : 'failed',
                    'proof' => mb_substr(
                        trim($process->getOutput() . "\n" . $process->getErrorOutput()),
                        0,
                        5000
                    ),
                ],
                meta: [
                    'exit_code' => $process->getExitCode(),
                ]
            );
        } catch (Throwable $e) {
            $this->failShardStep($step, $e);
            throw $e;
        }
    }
}
