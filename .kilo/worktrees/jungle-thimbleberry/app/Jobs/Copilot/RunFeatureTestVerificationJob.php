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
 * Verification shard: runs Feature test suite.
 * Executes `php artisan test --testsuite=Feature` in a subprocess.
 */
class RunFeatureTestVerificationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesVerificationShard;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(
        public int $pipelineRunId
    ) {
        $this->onQueue(config('copilot-pipeline.queues.verification', 'copilot-verification'));
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("pipeline-run:{$this->pipelineRunId}:verify:feature_tests"),
        ];
    }

    public function handle(): void
    {
        // Batch cancellation guard
        if ($this->batch()?->cancelled()) {
            return;
        }

        $step = null;

        try {
            $step = $this->startShardStep(
                pipelineRunId: $this->pipelineRunId,
                stepName: 'verification',
                agentName: 'FeatureTestVerification',
                shardKey: 'feature_tests',
                inputPayload: ['type' => 'feature_tests']
            );

            if (!$step) {
                return;
            }

            $process = new Process(['php', 'artisan', 'test', '--testsuite=Feature']);
            $process->setTimeout(110);
            $process->run();

            $passed = $process->isSuccessful();

            $this->completeShardStep(
                $step,
                outputPayload: [
                    'type' => 'feature_tests',
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
