<?php

namespace App\Jobs;

use App\Services\Ilan\IlanLocationSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Ilan Location Job — Sprint 6.2
 *
 * Arka planda bir Ilan'ın konum analizini çalıştırır.
 * Rate limited: Nominatim 1 req/s koruması GeocodingService içinde.
 */
class SyncIlanLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        public readonly int $ilanId,
    ) {}

    public function handle(IlanLocationSyncService $syncService): void
    {
        try {
            $result = $syncService->sync($this->ilanId, includeAiSummary: false);

            Log::info('SyncIlanLocationJob: completed', [
                'ilan_id' => $this->ilanId,
                'score' => $result->score,
                'status' => $result->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('SyncIlanLocationJob: failed', [
                'ilan_id' => $this->ilanId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncIlanLocationJob: permanently failed', [
            'ilan_id' => $this->ilanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
