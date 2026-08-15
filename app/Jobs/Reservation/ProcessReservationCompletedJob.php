<?php

namespace App\Jobs\Reservation;

use App\Events\Reservation\ReservationCompletedEvent;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Services\Reservation\OperationalGorevService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessReservationCompletedJob — Wave 1: Turnover cleaning task.
 *
 * Receives ReservationCompletedEvent (fired by reservation:complete command).
 * Creates a 'temizlik' (post-checkout turnover) Gorev for the cleaner.
 *
 * Queue-safe: idempotent, tenant-scoped, retryable.
 * Idempotency: OperationalGorevService checks existence before creating.
 *
 * SAAB Decision CHECKOUT-D2
 * Baseline: 88ccfc8
 */
class ProcessReservationCompletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly ReservationCompletedEvent $event,
    ) {}

    public function handle(OperationalGorevService $gorevService): void
    {
        Log::info('ProcessReservationCompletedJob: handling', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'ilan_id' => $this->event->ilanId,
        ]);

        // Load reservation and ilan — verify tenant isolation
        $reservation = PropertyReservation::query()
            ->where('id', $this->event->reservationId)
            ->where('tenant_id', $this->event->tenantId)
            ->first();

        if (!$reservation) {
            Log::error('ProcessReservationCompletedJob: reservation not found or tenant mismatch', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
            ]);
            return; // Non-retryable
        }

        $ilan = Ilan::query()
            ->where('id', $this->event->ilanId)
            ->where('tenant_id', $this->event->tenantId)
            ->first();

        if (!$ilan) {
            Log::error('ProcessReservationCompletedJob: ilan not found or tenant mismatch', [
                'ilan_id' => $this->event->ilanId,
                'tenant_id' => $this->event->tenantId,
            ]);
            return;
        }

        // Create turnover cleaning task
        // Idempotency: service returns null if task already exists
        $task = $gorevService->createTurnoverTask(
            reservation: $reservation,
            ilan: $ilan,
            creatorUserId: 0,  // system-initiated
        );

        if ($task) {
            Log::info('ProcessReservationCompletedJob: turnover task created', [
                'gorev_id' => $task->id,
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
                'deadline' => $task->bitis_tarihi?->toDateString(),
            ]);
        } else {
            Log::info('ProcessReservationCompletedJob: turnover task already exists (idempotent)', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReservationCompletedJob: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'ilan_id' => $this->event->ilanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
