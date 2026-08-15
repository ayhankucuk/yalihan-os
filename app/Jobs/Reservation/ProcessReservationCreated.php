<?php

namespace App\Jobs\Reservation;

use App\Application\ChannelManager\DTOs\SynchronizeAvailabilityCommand;
use App\Application\ChannelManager\Services\AvailabilitySynchronizationService;
use App\Events\Reservation\ReservationCreatedEvent;
use App\Jobs\Reservation\SendGuestConfirmationJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessReservationCreated — Queue-safe listener boundary.
 *
 * This job is the canonical entry point for ALL downstream systems
 * that need to react to a new reservation:
 *   - Guest confirmation notification
 *   - Availability outbound sync
 *   - Financial recording
 *   - Stay operation task generation
 *
 * Queue-safe: idempotent, tenant-scoped, retryable.
 * Retries are handled at the queue level (Tries = 3, backoff = 60s).
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 * SAAB Decision 4.1–4.6 — E02: Availability Sync wired here
 */
class ProcessReservationCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ReservationCreatedEvent $event,
    ) {}

    public function handle(AvailabilitySynchronizationService $availabilityService): void
    {
        Log::info('ProcessReservationCreated: handling', [
            'reservation_id'   => $this->event->reservationId,
            'tenant_id'        => $this->event->tenantId,
            'ilan_id'          => $this->event->ilanId,
            'external_channel' => $this->event->externalChannel,
        ]);

        // ── E02: Availability Outbound Sync ──────────────────────────────────
        // SAAB Decision 4.1–4.6: Canonical availability already written by
        // ReservationService within the same transaction. This job triggers
        // channel push via the single materializer.
        $this->syncAvailability($availabilityService);

        // ── Wave 1: Guest Communication ──────────────────────────────────────
        SendGuestConfirmationJob::dispatch($this->event);

        // ── Future waves ─────────────────────────────────────────────────────
        // Financial Recording → FinancialTransaction record
        // Stay Operation Task Generation → cleaning, pool check, etc.
    }

    /**
     * Trigger channel sync for the new reservation's blocked dates.
     */
    private function syncAvailability(AvailabilitySynchronizationService $service): void
    {
        try {
            $command = new SynchronizeAvailabilityCommand(
                tenantId: $this->event->tenantId,
                propertyId: $this->event->ilanId,
                reservationId: $this->event->reservationId,
                operation: 'block',
                dateRange: [
                    'start' => $this->event->startDate,
                    'end'   => $this->event->endDate,
                ],
                available: false,
                blockReason: 'reservation',
            );

            $result = $service->synchronize($command, userId: $this->event->createdByUserId ?? 0);

            Log::info('ProcessReservationCreated: availability sync dispatched', [
                'reservation_id' => $this->event->reservationId,
                'success'       => $result->success,
                'synced_count'  => $result->syncedCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessReservationCreated: availability sync failed', [
                'reservation_id' => $this->event->reservationId,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReservationCreated: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
            'ilan_id'        => $this->event->ilanId,
            'error'         => $exception->getMessage(),
        ]);
    }
}
