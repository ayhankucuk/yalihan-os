<?php

namespace App\Jobs\Reservation;

use App\Application\ChannelManager\DTOs\SynchronizeAvailabilityCommand;
use App\Application\ChannelManager\Services\AvailabilitySynchronizationService;
use App\Events\Reservation\ReservationModifiedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessReservationModified — Queue-safe listener boundary.
 *
 * Handles date changes (availability release + re-block)
 * and guest data changes (notification).
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 * SAAB Decision 4.1–4.6 — E02: Availability Sync wired here
 */
class ProcessReservationModified implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ReservationModifiedEvent $event,
    ) {}

    public function handle(AvailabilitySynchronizationService $availabilityService): void
    {
        Log::info('ProcessReservationModified: handling', [
            'reservation_id'   => $this->event->reservationId,
            'tenant_id'        => $this->event->tenantId,
            'ilan_id'          => $this->event->ilanId,
            'prev_dates'       => $this->event->previousStartDate . '→' . $this->event->previousEndDate,
            'new_dates'        => $this->event->newStartDate . '→' . $this->event->newEndDate,
        ]);

        // ── E02: Availability Outbound Sync (release old + block new) ─────────
        // SAAB Decision 4.1–4.6: ReservationService already updated local
        // PropertyAvailability (released old dates, blocked new dates).
        // This job triggers channel push to propagate both changes.
        $this->syncRelease($availabilityService);
        $this->syncBlock($availabilityService);

        // ── Future waves ─────────────────────────────────────────────────────
        // Guest notification: inform of date change → NotificationDispatcher
    }

    private function syncRelease(AvailabilitySynchronizationService $service): void
    {
        try {
            $datesToRelease = $this->event->getDatesToRelease();
            if (empty($datesToRelease)) {
                return;
            }

            $result = $service->release(
                tenantId: $this->event->tenantId,
                propertyId: $this->event->ilanId,
                reservationId: $this->event->reservationId,
                startDate: $this->event->previousStartDate,
                endDate: $this->event->previousEndDate,
            );

            Log::info('ProcessReservationModified: availability release dispatched', [
                'reservation_id' => $this->event->reservationId,
                'released_count' => count($datesToRelease),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessReservationModified: availability release failed', [
                'reservation_id' => $this->event->reservationId,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function syncBlock(AvailabilitySynchronizationService $service): void
    {
        try {
            $datesToBlock = $this->event->getDatesToBlock();
            if (empty($datesToBlock)) {
                return;
            }

            $command = new SynchronizeAvailabilityCommand(
                tenantId: $this->event->tenantId,
                propertyId: $this->event->ilanId,
                reservationId: $this->event->reservationId,
                operation: 'block',
                dateRange: [
                    'start' => $this->event->newStartDate,
                    'end'   => $this->event->newEndDate,
                ],
                available: false,
                blockReason: 'reservation',
            );

            $result = $service->synchronize($command, userId: 0);

            Log::info('ProcessReservationModified: availability block dispatched', [
                'reservation_id' => $this->event->reservationId,
                'blocked_count' => count($datesToBlock),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessReservationModified: availability block failed', [
                'reservation_id' => $this->event->reservationId,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReservationModified: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id'      => $this->event->tenantId,
            'ilan_id'        => $this->event->ilanId,
            'error'         => $exception->getMessage(),
        ]);
    }
}
