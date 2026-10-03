<?php

namespace App\Jobs\ChannelManager;

use App\Queue\Contracts\TenantAwareJobInterface;
use App\Queue\Middleware\RestoreTenantContext;
use App\Services\ChannelManager\ChannexReservationIngestService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ChannexReservationModifyJob — Async modification ingest.
 * CHANNEL_MANAGER_PROVIDER Wave 3 — ADR-008
 */
class ChannexReservationModifyJob implements ShouldQueue, TenantAwareJobInterface
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string $externalReservationId,
        public readonly string $externalChannel,
        public readonly int    $tenantId,
        public readonly string $newStartDate,
        public readonly string $newEndDate,
        public readonly array  $guestData = [],
    ) {}

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getUserId(): ?int
    {
        return null;
    }

    public function middleware(): array
    {
        return [new RestoreTenantContext(app(TenantContextService::class))];
    }

    public function handle(ChannexReservationIngestService $ingestService): void
    {
        $ingestService->ingestModification(
            $this->externalReservationId,
            $this->externalChannel,
            $this->tenantId,
            $this->newStartDate,
            $this->newEndDate,
            $this->guestData,
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ChannexReservationModifyJob: max retries exceeded', [
            'external_reservation_id' => $this->externalReservationId,
            'error'                   => $exception->getMessage(),
        ]);
    }
}
