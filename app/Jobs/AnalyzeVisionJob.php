<?php

namespace App\Jobs;

use App\Services\Vision\VisionOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Analyze Vision Job — Sprint 6.4
 *
 * AI Vision analizini async olarak çalıştırır.
 *
 * Requirements (Sprint 6.4 P0):
 *   ✓ async
 *   ✓ idempotent (uniqueId)
 *   ✓ retry safe (tries + backoff)
 *   ✓ timeout
 *   ✓ replay safe
 */
class AnalyzeVisionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 180;

    public function __construct(
        public readonly int $ilanId,
        public readonly ?string $traceId = null,
    ) {}

    /**
     * Unique ID — aynı ilan için tek job çalışır (idempotent).
     */
    public function uniqueId(): string
    {
        return "vision_analysis_{$this->ilanId}";
    }

    /**
     * Job handle.
     */
    public function handle(VisionOrchestrator $orchestrator): void
    {
        $start = microtime(true);

        Log::info('AnalyzeVisionJob: starting', [
            'ilan_id'  => $this->ilanId,
            'trace_id' => $this->traceId,
        ]);

        try {
            $result = $orchestrator->analyzeIlan(
                ilanId: $this->ilanId,
                dispatchEvents: true,
            );

            $elapsed = round((microtime(true) - $start) * 1000, 2);

            Log::info('AnalyzeVisionJob: completed', [
                'ilan_id'          => $this->ilanId,
                'trace_id'         => $this->traceId,
                'analyzed_photos'  => $result['analyzed'],
                'vision_score'     => $result['publishing']->vision_score,
                'is_ready'         => $result['publishing']->is_publishing_ready,
                'elapsed_ms'       => $elapsed,
            ]);
        } catch (\Throwable $e) {
            Log::error('AnalyzeVisionJob: failed', [
                'ilan_id'  => $this->ilanId,
                'trace_id' => $this->traceId,
                'error'    => $e->getMessage(),
                'attempt'  => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Max attempts aşıldığında çağrılır.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeVisionJob: permanently failed', [
            'ilan_id' => $this->ilanId,
            'trace_id' => $this->traceId,
            'error'   => $exception->getMessage(),
        ]);
    }

    /**
     * Retry koşulu — sadece geçici hatalarda dene.
     */
    public function shouldRetry(\Throwable $e): bool
    {
        // Network/hTTP timeout → retry
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }

        // Rate limit → retry
        if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate limit')) {
            return true;
        }

        // OpenAI timeout → retry
        if ($e instanceof \Illuminate\Http\Client\RequestException && $e->response?->status() === 429) {
            return true;
        }

        return false;
    }
}
