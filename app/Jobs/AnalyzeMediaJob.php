<?php

namespace App\Jobs;

use App\Services\Media\MediaIntelligenceEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Analyze Media Job — Sprint 6.3
 *
 * Arka planda fotoğraf analizi çalıştırır.
 * Idempotent: Aynı ilan için tek job çalışır.
 * Retry: 2 deneme, 30 saniye backoff.
 */
class AnalyzeMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(
        public readonly int $ilanId,
        public readonly ?string $jobId = null,
    ) {}

    public function uniqueId(): string
    {
        return "media_analysis_{$this->ilanId}";
    }

    public function handle(MediaIntelligenceEngine $engine): void
    {
        try {
            Log::info('AnalyzeMediaJob: starting', ['ilan_id' => $this->ilanId]);

            $result = $engine->analyze($this->ilanId, dispatchEvents: true);

            Log::info('AnalyzeMediaJob: completed', [
                'ilan_id' => $this->ilanId,
                'health_score' => $result->media_health_score,
                'total_photos' => $result->toplam_fotograf,
                'hero_fotograf_id' => $result->hero_fotograf_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('AnalyzeMediaJob: failed', [
                'ilan_id' => $this->ilanId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeMediaJob: permanently failed', [
            'ilan_id' => $this->ilanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
