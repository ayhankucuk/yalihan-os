<?php

namespace App\Jobs\Reservation;

use App\Application\ChannelManager\Services\AvailabilitySynchronizationService;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Models\PropertyReservation;
use App\Services\FinancialLedgerService;
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

    public function handle(
        AvailabilitySynchronizationService $availabilityService,
        FinancialLedgerService $financialLedgerService
    ): void {
        Log::info('ProcessReservationCancelled: handling', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'ilan_id' => $this->event->ilanId,
            'dates_released' => count($this->event->getDatesToRelease()),
            'cancelled_by' => $this->event->cancelledBy,
        ]);

        // ── E02: Availability Outbound Sync (release) ───────────────────────
        // SAAB Decision 4.1–4.6: ReservationService already released internal
        // availability blocks within the transaction. This job triggers channel
        // push to propagate the release to external platforms.
        $this->syncRelease($availabilityService);

        // ── Financial Reversal (Double-Entry Ledger) ────────────────────────
        $this->reverseFinancials($financialLedgerService);

        // ── Future waves ─────────────────────────────────────────────────────
        // Guest cancellation notification → NotificationDispatcher
    }

    /**
     * Reverse reservation financial entry in double-entry ledger.
     */
    private function reverseFinancials(FinancialLedgerService $ledgerService): void
    {
        try {
            $reservation = PropertyReservation::withoutGlobalScopes()
                ->where('id', $this->event->reservationId)
                ->where('tenant_id', $this->event->tenantId)
                ->first();

            if (! $reservation) {
                Log::warning('ProcessReservationCancelled: reservation not found for financial reversal', [
                    'reservation_id' => $this->event->reservationId,
                    'tenant_id' => $this->event->tenantId,
                ]);

                return;
            }

            $txGroupId = $ledgerService->recordReservationCancellation(
                $reservation,
                null
            );

            // ── C4.2: Reverse channel fee accrual ─────────────────────────────
            // Reverses all three legs (channel fee, commission, owner payable)
            // created by recordChannelFeeAccrual(). Idempotent: checks for existing
            // reversal entries before writing. Atomic: outer transaction rollback.
            //
            // NOTE: reverseOwnerPayableAccrual (C3.2) is NOT called here.
            // For OWNER_BORNE, C4.2 is the sole accrual authority and must be the
            // sole reversal authority. For YALIHAN_BORNE, C4.2 reversal produces
            // the same result as C3.2 reversal would have.
            $ledgerService->reverseChannelFeeAccrual($reservation);

            Log::info('ProcessReservationCancelled: financial ledger reversal recorded', [
                'reservation_id' => $this->event->reservationId,
                'transaction_group_id' => $txGroupId,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessReservationCancelled: financial reversal failed', [
                'reservation_id' => $this->event->reservationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger channel sync for the released dates.
     */
    private function syncRelease(AvailabilitySynchronizationService $service): void
    {
        try {
            // Unified release(): releases internal blocks + dispatches channel sync job
            $result = $service->release(
                tenantId: $this->event->tenantId,
                propertyId: $this->event->ilanId,
                reservationId: $this->event->reservationId,
                startDate: $this->event->startDate,
                endDate: $this->event->endDate,
            );

            Log::info('ProcessReservationCancelled: availability release dispatched', [
                'reservation_id' => $this->event->reservationId,
                'success' => $result->success,
                'sync_record_id' => $result->metadata['sync_record_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessReservationCancelled: availability release failed', [
                'reservation_id' => $this->event->reservationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReservationCancelled: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'ilan_id' => $this->event->ilanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
