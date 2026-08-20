<?php

namespace App\Jobs\Reservation;

use App\Events\Reservation\ReservationCreatedEvent;
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
 * CreateOperationalTasksJob — Wave 1: Pre-arrival readiness task.
 *
 * Dispatched from ProcessReservationCreated after availability sync.
 * Creates a 'hazirlik' (pre-arrival readiness) Gorev for the cleaner.
 *
 * Queue-safe: idempotent, tenant-scoped, retryable.
 * Idempotency: checks if task already exists for this reservation before creating.
 *
 * SAAB Decision CHECKOUT-D2
 * Baseline: 88ccfc8
 */
class CreateOperationalTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly ReservationCreatedEvent $event,
    ) {}

    public function handle(OperationalGorevService $gorevService): void
    {
        Log::info('CreateOperationalTasksJob: handling', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'ilan_id' => $this->event->ilanId,
        ]);

        // Load reservation and ilan — verify tenant isolation
        $reservation = PropertyReservation::withoutGlobalScopes()
            ->where('id', $this->event->reservationId)
            ->where('tenant_id', $this->event->tenantId)
            ->first();

        if (!$reservation) {
            Log::error('CreateOperationalTasksJob: reservation not found or tenant mismatch', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
            ]);
            return; // Non-retryable — reservation doesn't exist
        }

        $ilan = Ilan::withoutGlobalScopes()
            ->where('id', $this->event->ilanId)
            ->where('tenant_id', $this->event->tenantId)
            ->first();

        if (!$ilan) {
            Log::error('CreateOperationalTasksJob: ilan not found or tenant mismatch', [
                'ilan_id' => $this->event->ilanId,
                'tenant_id' => $this->event->tenantId,
            ]);
            return;
        }

        // Create pre-arrival readiness task
        // Idempotency: service returns null if task already exists
        $task = $gorevService->createPreArrivalTask(
            reservation: $reservation,
            ilan: $ilan,
            creatorUserId: $this->event->createdByUserId ?? 0,
        );

        if ($task) {
            Log::info('CreateOperationalTasksJob: hazirlik task created', [
                'gorev_id' => $task->id,
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
                'deadline' => $task->bitis_tarihi?->toDateString(),
            ]);
        } else {
            Log::info('CreateOperationalTasksJob: hazirlik task already exists (idempotent)', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CreateOperationalTasksJob: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'ilan_id' => $this->event->ilanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
