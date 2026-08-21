<?php

namespace App\Jobs\ChannelManager;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Services\ChannelManager\ChannexRevisionProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChannexReservationIngestJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly ChannexReservationPayload $payload,
        public readonly int                       $tenantId,
    ) {}

    public function handle(ChannexRevisionProcessor $processor): void
    {
        $processor->process($this->payload, $this->tenantId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ChannexReservationIngestJob: max retries exceeded', [
            'external_reservation_id' => $this->payload->externalReservationId,
            'tenant_id'               => $this->tenantId,
            'error'                   => $exception->getMessage(),
        ]);
    }
}
