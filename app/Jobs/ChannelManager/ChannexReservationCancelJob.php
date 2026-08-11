<?php

namespace App\Jobs\ChannelManager;

use App\Services\ChannelManager\ChannexReservationIngestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ChannexReservationCancelJob — Async cancellation ingest.
 * CHANNEL_MANAGER_PROVIDER Wave 3 — ADR-008
 */
class ChannexReservationCancelJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string $externalReservationId,
        public readonly string $externalChannel,
        public readonly int    $tenantId,
    ) {}

    public function handle(ChannexReservationIngestService $ingestService): void
    {
        $ingestService->ingestCancellation(
            $this->externalReservationId,
            $this->externalChannel,
            $this->tenantId,
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ChannexReservationCancelJob: max retries exceeded', [
            'external_reservation_id' => $this->externalReservationId,
            'error'                   => $exception->getMessage(),
        ]);
    }
}
