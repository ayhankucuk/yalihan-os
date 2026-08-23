<?php

namespace App\Jobs\Reservation;

use App\Enums\ChannelFeeBearer;
use App\Enums\ChannelFeeSource;
use App\Exceptions\Governance\ChannelFeeTrustException;
use App\Events\Reservation\ReservationCompletedEvent;
use App\Events\Reservation\ReservationPayoutReadyEvent;
use App\Models\PropertyReservation;
use App\Services\FinancialLedgerService;
use App\ValueObjects\TransactionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessFinancialCompletionJob — C1: Financial Completion + C2: Queue Safety + C4.2: Channel Fee Accrual
 *
 * Receives canonical ReservationCompletedEvent and transitions the reservation's
 * financial state to the canonical terminal state (CONFIRMED).
 *
 * C2: implements ShouldBeUnique — prevents concurrent duplicate execution.
 * The database queue driver uses a failed_jobs row as the uniqueness lock.
 * A second dispatch while the first is in-flight throws LockConflictException.
 *
 * afterCommit = true: job is dispatched only after the parent transaction
 * commits. This prevents the job from running against uncommitted reservation
 * data when the event is dispatched from within a DB transaction.
 *
 * CASE routing (SAAB C4.2 Certification Recovery):
 *   CASE A — Direct / zero-fee: use C3 flow (commission + owner payable)
 *   CASE B — OTA + verified fee: use C4.2 full triple split (TX1+TX2+TX3)
 *   CASE C — OTA + unresolved: fall back to C3 flow; payout BLOCKED
 *
 * This job is idempotent: calling it multiple times with the same event
 * produces exactly one economic outcome (no duplicate ledger impact).
 *
 * Cancellation guard: if the reservation has been cancelled, the job exits
 * silently without transitioning to CONFIRMED.
 *
 * Tenant isolation: all DB queries scope by tenantId carried in the event.
 *
 * Ledger integrity: all ledger entries are created through FinancialLedgerService.
 * transitionToConfirmed() creates only the finansal_durum UPDATE.
 * recordChannelFeeAccrual() (C4.2) creates all accrual double-entries.
 * No UPDATE, DELETE, or re-booking of existing ledger entries.
 *
 * Scope exclusion (C1): payout notification, bank transfer, reconciliation,
 * channel fee separation, tax/KDV ledger, commission architecture — all deferred.
 *
 * Baseline: 667c1b4 (C1), 33f9f50 (C2)
 * SAAB Decision: C1 + C2 Certification
 */
class ProcessFinancialCompletionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    /**
     * Lock timeout: if a job with this uniqueId is still running after 5 minutes,
     * the lock is considered stale and a new job may proceed.
     * Covers the case where a worker crashes mid-execution.
     */
    public int $uniqueFor = 300;

    public function __construct(
        public readonly ReservationCompletedEvent $event,
    ) {
        // afterCommit = true: job runs only after the parent transaction commits.
        //
        // Laravel dispatches queued jobs immediately when dispatched() is called,
        // even if the parent transaction has not yet committed. This means a job
        // can run against uncommitted data if the event is dispatched from within
        // a DB transaction. Setting afterCommit = true defers job execution until
        // the transaction commits, ensuring the job always sees committed state.
        //
        // SAAB C4.2 Certification Recovery: confirmed-after-commit is the
        // correct protection, not "queue is isolated".
        $this->afterCommit = true;
    }

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

        if (! $reservation) {
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
        $ledgerService->transitionToConfirmed($reservation->id);

        // ── CASE Routing (SAAB C4.2 Certification Recovery) ──────────
        // Determine CASE A/B/C before any financial accrual attempt.
        // CASE C throws ChannelFeeTrustException which must be caught here
        // and handled with C3 fallback — NOT allowed to escape the job.
        $case = $ledgerService->classifyChannelFeeCase($reservation);
        Log::info('ProcessFinancialCompletionJob: channel fee case', [
            'reservation_id' => $this->event->reservationId,
            'case' => $case['case'],
            'description' => $case['description'],
        ]);

        $c4Applied = false;

        try {
            // CASE B: OTA + verified → full C4.2 triple split (TX1+TX2+TX3)
            if ($case['case'] === 'B') {
                $ledgerService->recordChannelFeeAccrual($reservation);
                $c4Applied = true;
            }
            // CASE A: Direct / zero-fee → falls through to C3 fallback below
            // CASE C: OTA + unresolved → falls through to C3 fallback below
            //         (recordChannelFeeAccrual would throw ChannelFeeTrustException)
        } catch (ChannelFeeTrustException $e) {
            // CASE C: ChannelFeeTrustException MUST NOT escape the job.
            // Fall back to C3 flow: commission + owner payable without channel fee.
            // This preserves financial completion for OTA reservations where
            // channel fee is not yet known, while still blocking payout readiness.
            Log::warning('ProcessFinancialCompletionJob: CASE C detected — falling back to C3 flow', [
                'reservation_id' => $this->event->reservationId,
                'case' => $e->case,
                'bearer' => $e->channelFeeBearer,
                'source' => $e->channelFeeSource,
                'message' => $e->getMessage(),
            ]);
        }

        // CASE A + CASE C: C3 fallback — commission + owner payable (no channel fee deduction).
        // Also called for CASE B when C4.2 is not applicable (YALIHAN_BORNE).
        // recordOwnerPayableAccrual is idempotent: checks commission_rate_snapshot
        // before writing. Safe to call even if C4.2 already wrote commission entries
        // (idempotency keys prevent duplicate).
        if ($reservation->commission_rate_snapshot !== null) {
            $ledgerService->recordOwnerPayableAccrual($reservation);
        }

        // ── Payout Readiness event ─────────────────────────────────────
        // CASE B (C4.2 full): payout ready after C4.2 completes.
        // CASE A (Direct): payout ready after C3 completes.
        // CASE C (OTA unresolved): payout BLOCKED — do NOT emit PayoutReadyEvent.
        if ($case['case'] === 'C') {
            Log::info('ProcessFinancialCompletionJob: payout BLOCKED — CASE C awaiting channel fee reconciliation', [
                'reservation_id' => $this->event->reservationId,
                'tenant_id' => $this->event->tenantId,
            ]);
        } else {
            $this->dispatchPayoutReadyEvent($reservation);
        }

        Log::info('ProcessFinancialCompletionJob: financial completion applied', [
            'reservation_id' => $this->event->reservationId,
            'tenant_id' => $this->event->tenantId,
            'previous_finansal_durum' => $reservation->finansal_durum,
            'new_finansal_durum' => TransactionStatus::CONFIRMED,
            'c4_applied' => $c4Applied,
            'case' => $case['case'],
        ]);
    }

    /**
     * Dispatch ReservationPayoutReadyEvent if commission snapshot exists.
     *
     * Only called for CASE A (Direct) and CASE B (OTA + verified).
     * NOT called for CASE C (OTA + unresolved) — payout remains BLOCKED.
     */
    private function dispatchPayoutReadyEvent(PropertyReservation $reservation): void
    {
        if ($reservation->commission_rate_snapshot === null) {
            return;
        }

        $grossAmount = (float) ($reservation->total_amount
            ?? $reservation->islem_tutari
            ?? $reservation->locked_nightly_rate * $reservation->nights
            ?? 0);
        $rate = (float) $reservation->commission_rate_snapshot;
        $commissionAmount = $grossAmount * $rate;
        $ownerEntitlement = $grossAmount - $commissionAmount;

        $ilan = $reservation->ilan;
        $ownerKisiId = $ilan?->ilan_sahibi_id ?? null;
        $ownerName = $ilan?->ilanSahibi?->ad
            ? trim($ilan->ilanSahibi->ad.' '.($ilan->ilanSahibi->soyad ?? ''))
            : null;

        // C4.1: Channel fee fields from reservation snapshot.
        // PHP 8: check instanceof FIRST before ?? fallback to avoid ErrorException.
        $channelFeeBearerRaw = $reservation->channel_fee_bearer;
        if ($channelFeeBearerRaw instanceof ChannelFeeBearer) {
            $channelFeeBearer = $channelFeeBearerRaw->value;
        } elseif (is_string($channelFeeBearerRaw)) {
            $channelFeeBearer = $channelFeeBearerRaw;
        } else {
            $channelFeeBearer = $channelFeeBearerRaw !== null ? (string) $channelFeeBearerRaw : '';
        }
        $channelFeeSourceRaw = $reservation->channel_fee_source;
        if ($channelFeeSourceRaw instanceof ChannelFeeSource) {
            $channelFeeSource = $channelFeeSourceRaw->value;
        } elseif (is_string($channelFeeSourceRaw)) {
            $channelFeeSource = $channelFeeSourceRaw;
        } else {
            $channelFeeSource = $channelFeeSourceRaw !== null ? (string) $channelFeeSourceRaw : '';
        }
        $channelFeeAmount = $reservation->channel_fee_amount !== null
            ? (float) $reservation->channel_fee_amount
            : null;

        // C4.2: ownerEntitlement for event = gross - commission
        // (event will subtract channel fee internally via computeOwnerEntitlementAfterChannel)
        $ownerEntitlementAfterChannel = $grossAmount - $commissionAmount
            - ($channelFeeBearerRaw?->requiresChannelFeeKnown() ? ($channelFeeAmount ?? 0) : 0);

        event(ReservationPayoutReadyEvent::fromReservation(
            $reservation,
            $grossAmount,
            $commissionAmount,
            $ownerEntitlementAfterChannel,
            $ownerKisiId,
            $ownerName,
            $channelFeeAmount,
            $reservation->channel_fee_currency,
            $reservation->channel_fee_rate !== null ? (float) $reservation->channel_fee_rate : null,
            $channelFeeSource,
            $channelFeeBearer,
            (bool) $reservation->channel_fee_is_verified,
        ));
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
