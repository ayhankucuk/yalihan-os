<?php

namespace App\Jobs\Reservation;

use App\Events\Reservation\ReservationCompletedEvent;
use App\Models\PropertyReservation;
use App\Services\FinancialLedgerService;
use App\ValueObjects\TransactionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessFinancialCompletionJob — C1: Financial Completion
 *
 * Receives canonical ReservationCompletedEvent and transitions the reservation's
 * financial state to the canonical terminal state (CONFIRMED).
 *
 * This job is idempotent: calling it multiple times with the same event
 * produces exactly one economic outcome (no duplicate ledger impact).
 *
 * Cancellation guard: if the reservation has been cancelled, the job exits
 * silently without transitioning to CONFIRMED.
 *
 * Tenant isolation: all DB queries scope by tenantId carried in the event.
 *
 * Ledger integrity: this job does NOT create ledger entries.
 * It only transitions the financial state column (finansal_durum).
 * No UPDATE, DELETE, or re-booking of existing ledger entries.
 *
 * Scope exclusion (C1): payout notification, bank transfer, reconciliation,
 * channel fee separation, tax/KDV ledger, commission architecture — all deferred.
 *
 * Baseline: 667c1b4
 * SAAB Decision: C1 Certification
 */
class ProcessFinancialCompletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly ReservationCompletedEvent $event,
    ) {}

    /**
     * Idempotency key prevents duplicate processing across queue retries.
     */
    public function uniqueId(): string
    {
        return "reservation_financial_completion_{$this->event->reservationId}_{$this->event->tenantId}";
    }

    public function handle(FinancialLedgerService $ledgerService): void
    {
        Log::info('ProcessFinancialCompletionJob: handling', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'ilan_id' => $this->event->ilanId,
        ]);

        // ── Tenant-scoped reservation load ─────────────────────────────────
        // If reservation is not found or tenantId mismatches → non-retryable.
        $reservation = PropertyReservation::query()
            ->where('id', $this->event->reservationId)
            ->where('tenant_id', $this->event->tenantId)
            ->first();

        if (!$reservation) {
            Log::error('ProcessFinancialCompletionJob: reservation not found or tenant mismatch — skipping', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
            ]);
            return;
        }

        // ── C7: Cancellation guard ───────────────────────────────────────
        // A cancelled reservation must never transition to financial completion.
        // If the reservation was cancelled after the event was emitted (e.g. stale
        // scheduler run, replay, or duplicate event), we skip silently.
        if ($reservation->finansal_durum === TransactionStatus::CANCELLED) {
            Log::info('ProcessFinancialCompletionJob: reservation is CANCELLED — skipping financial completion', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
                'finansal_durum' => $reservation->finansal_durum,
            ]);
            return;
        }

        // ── Idempotency: already confirmed = no-op ───────────────────────
        // If the reservation already reached financial completion, this is a
        // no-operation replay. We do NOT re-call transitionToConfirmed() because
        // it would re-run the UPDATE even though the result is the same.
        // The idempotency key on the job itself prevents duplicate dispatch;
        // this guard protects against upstream replay (e.g. event re-published).
        if ($reservation->finansal_durum === TransactionStatus::CONFIRMED) {
            Log::info('ProcessFinancialCompletionJob: already CONFIRMED — idempotent no-op', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
            ]);
            return;
        }

        // ── Idempotency: PAID is also terminal success ───────────────────
        // Some code paths may set PAID as the terminal state. Treat it the same.
        if ($reservation->finansal_durum === TransactionStatus::PAID) {
            Log::info('ProcessFinancialCompletionJob: already PAID — idempotent no-op', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
            ]);
            return;
        }

        // ── Financial completion: transition to CONFIRMED ───────────────
        // Only reachable for: PENDING, REFUNDED, FAILED states.
        // transitionToConfirmed() wraps the UPDATE in a DB::transaction and
        // re-checks the current state inside that transaction (race-condition guard).
        try {
            $ledgerService->transitionToConfirmed($reservation->id);

            Log::info('ProcessFinancialCompletionJob: financial completion applied', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
                'previous_finansal_durum' => $reservation->finansal_durum,
                'new_finansal_durum' => TransactionStatus::CONFIRMED,
            ]);
        } catch (\Throwable $e) {
            // If another process already set CONFIRMED between our read and write,
            // the UPDATE will affect 0 rows. The transaction in transitionToConfirmed
            // does not throw in that case, so this catch handles unexpected errors only.
            Log::error('ProcessFinancialCompletionJob: failed to transition', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessFinancialCompletionJob: all retries exhausted', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'ilan_id' => $this->event->ilanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
