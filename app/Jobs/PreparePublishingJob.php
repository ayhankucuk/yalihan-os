<?php

namespace App\Jobs;

use App\DTOs\Publishing\PublishingDecisionDTO;
use App\Models\Ilan;
use App\Services\Publishing\PublishingIntelligenceOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Prepare Publishing Job — Sprint 6.5
 *
 * Publishing Intelligence pipeline'ını async olarak çalıştırır.
 *
 * Hard boundaries:
 *   ✓ async
 *   ✓ idempotent (uniqueId per ilan)
 *   ✓ replay-safe (aynı ilan tekrar tetiklenirse aynı sonucu üretir)
 *   ✓ TenantScope korunur
 *   ✓ timeout: 60s
 *   ✓ Real API çağrısı YAPMAZ
 *   ✓ withoutGlobalScopes() KULLANILMAZ
 *
 * DOWNSTREAM EVENT: PublishingPackageReady
 *   → Channel handler'lar bu event'i dinler (Sprint 6.6)
 *   → Bu job sadece payload üretir, PUBLISH ETMEZ
 */
class PreparePublishingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(
        public readonly int $ilanId,
        public readonly ?array $decisionData = null,
        public readonly ?string $traceId = null,
    ) {}

    /**
     * Unique ID — aynı ilan için tek job çalışır (idempotent).
     * Replay-safe: job tekrar çalışsa da aynı sonucu üretir.
     */
    public function uniqueId(): string
    {
        return "prepare_publishing_{$this->ilanId}";
    }

    /**
     * Retry koşulu — sadece geçici hatalarda dene.
     */
    public function shouldRetry(\Throwable $e): bool
    {
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }
        if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate limit')) {
            return true;
        }
        return false;
    }

    /**
     * Job handle.
     *
     * @param  PublishingIntelligenceOrchestrator  $orchestrator
     */
    public function handle(PublishingIntelligenceOrchestrator $orchestrator): void
    {
        $start = microtime(true);

        Log::info('PreparePublishingJob: starting', [
            'ilan_id' => $this->ilanId,
            'trace_id' => $this->traceId,
        ]);

        try {
            // ─── Step 1: TenantScope korunur — Ilan::find sadece tenant verisini döner
            /** @var Ilan|null $ilan */
            $ilan = Ilan::find($this->ilanId);

            if (!$ilan) {
                Log::warning('PreparePublishingJob: ilan not found', [
                    'ilan_id' => $this->ilanId,
                ]);
                return;
            }

            // ─── Step 2: Vision data çek (vision_media JSON)
            $visionData = $ilan->vision_media ?? [];

            // ─── Step 3: Karar DTO oluştur (varsa)
            $decision = $this->buildDecisionDto();

            // ─── Step 4: Orchestrate — payload üret, event fırlat
            $package = $orchestrator->orchestrate(
                ilan: $ilan,
                visionData: $visionData,
                decision: $decision,
            );

            $elapsed = round((microtime(true) - $start) * 1000, 2);

            Log::info('PreparePublishingJob: completed', [
                'ilan_id' => $this->ilanId,
                'trace_id' => $this->traceId,
                'ready_channels' => $package->readyChannels(),
                'has_error_free' => $package->isErrorFree(),
                'elapsed_ms' => $elapsed,
            ]);
        } catch (\Throwable $e) {
            Log::error('PreparePublishingJob: failed', [
                'ilan_id' => $this->ilanId,
                'trace_id' => $this->traceId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Max attempts aşıldığında çağrılır.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PreparePublishingJob: permanently failed', [
            'ilan_id' => $this->ilanId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Karar DTO oluşturur (varsa).
     */
    private function buildDecisionDto(): ?PublishingDecisionDTO
    {
        if (empty($this->decisionData)) {
            return null;
        }

        return new PublishingDecisionDTO(
            decision: $this->decisionData['decision'] ?? 'needs_review',
            publishTargets: $this->decisionData['publish_targets'] ?? [],
            qualityTier: $this->decisionData['quality_tier'] ?? 'standard',
            overallScore: (float) ($this->decisionData['overall_score'] ?? 0.0),
            confidence: (float) ($this->decisionData['confidence'] ?? 0.0),
            blockingIssues: $this->decisionData['blocking_issues'] ?? [],
            decidedAt: $this->decisionData['decided_at'] ?? null,
        );
    }
}
