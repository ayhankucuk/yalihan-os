<?php

namespace App\Jobs\Reservation;

use App\Application\ChannelManager\Services\AvailabilitySynchronizationService;
use App\Events\Reservation\ReservationCancelledEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessReservationCancelled — Queue-safe listener boundary.
 *
 * Handles cancellation: release internal availability blocks,
 * trigger guest cancellation notification, financial reversal.
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 * SAAB Decision 4.1–4.6 — E02: Availability Sync wired here
 */
class ProcessReservationCancelled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ReservationCancelledEvent $event,
    ) {}

    public function handle(AvailabilitySynchronizationService $availabilityService): void
    {
        Log::info('ProcessReservationCancelled: handling', [
            'reservation_id'  => $this->event->reservationId,
            'tenant_id'       => $this->event->tenantId,
            'ilan_id'         => $this->event->ilanId,
            'dates_released'  => count($this->event->getDatesToRelease()),
            'cancelled_by'   => $this->event->cancelledBy,
        ]);

        // ── E02: Availability Outbound Sync (release) ───────────────────────
        // SAAB Decision 4.1–4.6: ReservationService already released internal
        // availability blocks within the transaction. This job triggers channel
        // push to propagate the release to external platforms.
        $this->syncRelease($availabilityService);

        // ── Future waves ─────────────────────────────────────────────────────
        // Guest cancellation notification → NotificationDispatcher
        // Financial reversal → FinancialTransaction reversal
    }

    /**
     * Trigger channel sync for the released dates.
     */
    private function syncRelease(AvailabilitySynchronizationService $service): void
    {
        try {
            $result = $service->release(
                tenantId: $this->event->tenantId,
                propertyId: $this->event->ilanId,
                reservationId: $this->event->reservationId,
                startDate: $this->event->startDate,
                endDate: $this->event->endDate,
                userId: 0,
            );

            Log::info('ProcessReservationCancelled: availability release dispatched', [
                'reservation_id' => $this->event->reservationId,
                'success'       => $result->success,
                'sync_record_id' => $result->metadata['sync_record_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessReservationCancelled: availability release failed', [
                'reservation_id' => $this->event->reservationId,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReservationCancelled: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
            'ilan_id'        => $this->event->ilanId,
            'error'         => $exception->getMessage(),
        ]);
    }
}
