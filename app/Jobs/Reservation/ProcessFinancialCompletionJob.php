<?php

namespace App\Jobs\Reservation;

use App\Enums\ChannelFeeBearer;
use App\Enums\ChannelFeeSource;
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
        try {
            $ledgerService->transitionToConfirmed($reservation->id);

            // ── C4.2: Channel Fee Triple-Entry Accrual ────────────────────
            // Replaces C3.2 (recordOwnerPayableAccrual) for OWNER_BORNE.
            //
            // OWNER_BORNE / COMMISSION_SHARE (channel fee required):
            //   gross = verified_channel_fee + yalihan_commission + owner_payable
            //   → TX1 (channel fee) + TX2 (commission) + TX3 (owner) all created here.
            //   C4.1 Trust Gate: PROVIDER_REPORTED required, amount must not be null.
            //
            // YALIHAN_BORNE (no channel fee on owner):
            //   gross = yalihan_commission + owner_payable
            //   → Only TX2 (commission) + TX3 (owner); TX1 is a no-op (0 amount).
            //   Identical economic outcome to C3.2 but within C4.2 atomic boundary.
            //
            // Idempotent: recordChannelFeeAccrual checks idempotency keys before writing.
            // All three legs share one outer DB::transaction() — any failure → full rollback.
            //
            // NOTE: recordOwnerPayableAccrual (C3.2) is NOT called here.
            // For OWNER_BORNE, C4.2 is the sole authoritative accrual.
            // For YALIHAN_BORNE, C4.2 produces the same result as C3.2.
            $ledgerService->recordChannelFeeAccrual($reservation);

            // ── C3.3: Payout Readiness event ─────────────────────────────
            // Signal that this reservation is now payout-ready for admin/operator review.
            // No automatic payment. Human approves before payout.
            // Skip if legacy NULL snapshot (C3.1 contract: no invented policy).
            if ($reservation->commission_rate_snapshot !== null) {
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
                // Model casts to enum. PHP 8 throws when ?? is applied to enum objects,
                // so we must check instanceof FIRST before any ?? fallback.
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

                // C4.2: ownerEntitlement for event = gross - commission (the event
                // will subtract channel fee internally via computeOwnerEntitlementAfterChannel)
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
