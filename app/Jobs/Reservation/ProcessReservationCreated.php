<?php

namespace App\Jobs\Reservation;

use App\Events\Reservation\ReservationCreatedEvent;
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
 */
class ProcessReservationCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ReservationCreatedEvent $event,
    ) {}

    public function handle(): void
    {
        Log::info('ProcessReservationCreated: dispatched', [
            'reservation_id'  => $this->event->reservationId,
            'tenant_id'       => $this->event->tenantId,
            'ilan_id'         => $this->event->ilanId,
            'external_channel'=> $this->event->externalChannel,
        ]);

        // ── Downstream systems to be wired here in subsequent sprints ──
        //
        // 1. Guest Communication
        //    → dispatch to NotificationDispatcher with confirmation template
        //    Ticket: Guest Communication Wave
        //
        // 2. Availability Outbound Sync
        //    → AvailabilitySynchronizationService.synchronize()
        //    Ticket: Availability Sync Wave
        //
        // 3. Financial Recording
        //    → Create pending FinancialTransaction record
        //    Ticket: Financial Automation Wave
        //
        // 4. Stay Operation Task Generation
        //    → Schedule cleaning, pool check, etc.
        //    Ticket: Stay Operations Wave
        //
        // ⚠️  DO NOT add implementation here — add in subsequent waves.
        // ⚠️  Each downstream system = one dedicated job class.
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
