<?php

namespace App\Jobs\Copilot;

use App\Jobs\Copilot\Concerns\HandlesVerificationShard;
use App\Models\PipelineRun;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verification shard: endpoint health check.
 * Pings a configurable URL and checks HTTP response.
 */
class RunEndpointVerificationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesVerificationShard;

    public int $timeout = 30;
    public int $tries = 2;

    public function __construct(
        public int $pipelineRunId
    ) {
        $this->onQueue(config('copilot-pipeline.queues.verification', 'copilot-verification'));
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("pipeline-run:{$this->pipelineRunId}:verify:endpoint"),
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
                agentName: 'EndpointVerification',
                shardKey: 'endpoint',
                inputPayload: ['type' => 'endpoint']
            );

            if (!$step) {
                return;
            }

            $run = PipelineRun::query()->findOrFail($this->pipelineRunId);

            $url = data_get(
                $run->normalized_payload,
                'verification.endpoint_url',
                config('app.url') . '/api/health'
            );

            $response = Http::timeout(10)->get($url);

            $this->completeShardStep(
                $step,
                outputPayload: [
                    'type' => 'endpoint',
                    'result' => $response->successful() ? 'passed' : 'failed',
                    'proof' => json_encode([
                        'http_durum_kodu' => $response->status(),
                        'body' => mb_substr($response->body(), 0, 2000),
                    ], JSON_UNESCAPED_UNICODE),
                ],
                meta: [
                    'http_durum_kodu' => $response->status(),
                    'istek_url' => $url,
                ]
            );
        } catch (Throwable $e) {
            $this->failShardStep($step, $e);
            throw $e;
        }
    }
}
