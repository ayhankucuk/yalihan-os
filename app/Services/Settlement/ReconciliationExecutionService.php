<?php

namespace App\Services\Settlement;

use App\Models\Settlement\BankTransaction;
use App\Models\Settlement\ProviderSettlement;
use App\Models\Settlement\ReconciliationExecution;
use App\Models\Settlement\SettlementAllocation;
use App\Models\PropertyReservation;
use App\Enums\ReconciliationResult;
use App\Traits\HasReconciliationScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ReconciliationExecutionService — C5.1: Replay-safe reconciliation engine
 *
 * SAAB C5.1 Invariants:
 *   1. RECONCILED ≠ PAYOUT_SETTLED (reconciliation ≠ payout release)
 *   2. Executions are APPEND-ONLY (replays create new records, never mutate old ones)
 *   3. Tenant isolation on every query
 *   4. RAW evidence (provider_settlements, bank_transactions) is never mutated
 *
 * C5.1 scope exclusions:
 *   - NO ledger posting (C5.5)
 *   - NO payout release (C5.6)
 *   - NO actual bank API ingest (C5.3)
 *   - NO channel fee snapshot mutation
 *
 * Baseline: 35b4e6c (C4.2 Certified)
 */
class ReconciliationExecutionService
{
    /**
     * Execute a reconciliation attempt for a reservation.
     *
     * REPLAY RULE (APPEND-ONLY invariant):
     *   Always creates a new ReconciliationExecution record.
     *   Never updates or deletes existing execution records.
     *   attempt_number reflects the count of replays.
     *
     * @throws \InvalidArgumentException if reservation not found or tenant mismatch
     */
    public function reconcile(int $reservationId, int $tenantId): ReconciliationExecution
    {
        return DB::transaction(function () use ($reservationId, $tenantId) {
            // ── Tenant + reservation guard ─────────────────────────────────
            $reservation = PropertyReservation::query()
                ->where('id', $reservationId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$reservation) {
                throw new \InvalidArgumentException(
                    "ReconciliationExecutionService: reservation #{$reservationId} not found or tenant mismatch."
                );
            }

            // ── Load RAW evidence (immutable) ─────────────────────────────────
            $settlement = ProviderSettlement::query()
                ->where('tenant_id', $tenantId)
                ->where('reservation_id', $reservationId)
                ->orderBy('id', 'desc')
                ->first();

            $bankTx = BankTransaction::query()
                ->where('tenant_id', $tenantId)
                ->where('matched_settlement_id', $settlement?->id)
                ->orderBy('id', 'desc')
                ->first();

            // ── Determine reconciliation result ─────────────────────────────────
            $result = $this->determineResult($settlement, $bankTx, $reservation);

            // ── Compute attempt_number (APPEND-ONLY replay safety) ───────────
            $previousAttempts = ReconciliationExecution::query()
                ->where('tenant_id', $tenantId)
                ->where('reservation_id', $reservationId)
                ->count();

            $attemptNumber = $previousAttempts + 1;

            // ── APPEND-ONLY: never update old execution ──────────────────────
            $execution = ReconciliationExecution::create([
                'tenant_id' => $tenantId,
                'execution_type' => 'auto',
                'reservation_id' => $reservationId,
                'settlement_allocation_id' => null,
                'bank_transaction_id' => $bankTx?->id,
                'result' => $result->value,
                'result_status' => $this->resultStatusFor($result),
                'expected_amount' => $settlement?->net_amount,
                'actual_amount' => $bankTx?->amount,
                'discrepancy_amount' => $this->computeDiscrepancy($settlement, $bankTx),
                'discrepancy_reason' => $this->discrepancyReason($result, $settlement, $bankTx),
                'execution_trigger' => 'system',
                'execution_context' => [
                    'provider' => $settlement?->provider,
                    'external_settlement_id' => $settlement?->external_settlement_id,
                    'raw_source' => $settlement?->raw_source,
                    'bank_source' => $bankTx?->source,
                    'previous_attempts' => $previousAttempts,
                ],
                'attempt_number' => $attemptNumber,
            ]);

            Log::info('ReconciliationExecutionService: execution recorded', [
                'execution_id' => $execution->id,
                'reservation_id' => $reservationId,
                'tenant_id' => $tenantId,
                'result' => $result->value,
                'attempt_number' => $attemptNumber,
            ]);

            return $execution;
        });
    }

    /**
     * Get all reconciliation executions for a reservation.
     */
    public function getExecutionHistory(int $reservationId, int $tenantId): \Illuminate\Support\Collection
    {
        return ReconciliationExecution::query()
            ->where('tenant_id', $tenantId)
            ->where('reservation_id', $reservationId)
            ->orderBy('attempt_number', 'asc')
            ->get();
    }

    /**
     * Get the latest execution for a reservation.
     */
    public function getLatestExecution(int $reservationId, int $tenantId): ?ReconciliationExecution
    {
        return ReconciliationExecution::query()
            ->where('tenant_id', $tenantId)
            ->where('reservation_id', $reservationId)
            ->orderByDesc('attempt_number')
            ->orderByDesc('id')
            ->first();
    }

    // ───────────────────────────────────────────────────────────────
    // Private helpers
    // ───────────────────────────────────────────────────────────────

    private function determineResult(
        ?ProviderSettlement $settlement,
        ?BankTransaction $bankTx,
        PropertyReservation $reservation,
    ): ReconciliationResult {
        // No settlement evidence → pending
        if (!$settlement || $settlement->settlement_status === 'pending') {
            return ReconciliationResult::PENDING;
        }

        // No bank transaction matched yet
        if (!$bankTx) {
            return ReconciliationResult::NO_MATCH;
        }

        // Both settlement and bank transaction present → compare amounts
        $expected = (float) $settlement->net_amount;
        $actual = (float) $bankTx->amount;
        $delta = abs($expected - $actual);
        $tolerance = $this->getTolerance($settlement->currency ?? 'TRY');

        if ($delta <= 0.01) {
            return ReconciliationResult::EXACT_MATCH;
        }

        if ($delta <= $tolerance) {
            return ReconciliationResult::WITHIN_TOLERANCE;
        }

        return ReconciliationResult::DISCREPANCY;
    }

    private function computeDiscrepancy(?ProviderSettlement $settlement, ?BankTransaction $bankTx): ?float
    {
        if (!$settlement || !$bankTx) {
            return null;
        }

        return abs((float) $settlement->net_amount - (float) $bankTx->amount);
    }

    private function discrepancyReason(
        ReconciliationResult $result,
        ?ProviderSettlement $settlement,
        ?BankTransaction $bankTx,
    ): ?string {
        return match ($result) {
            ReconciliationResult::EXACT_MATCH => 'Exact match: settlement and bank transaction amounts align.',
            ReconciliationResult::WITHIN_TOLERANCE => 'Within tolerance: small FX/rounding difference.',
            ReconciliationResult::DISCREPANCY => $settlement && $bankTx
                ? sprintf(
                    'Discrepancy: settlement net=%.4f, bank amount=%.4f, Δ=%.4f',
                    $settlement->net_amount,
                    $bankTx->amount,
                    abs((float) $settlement->net_amount - (float) $bankTx->amount)
                )
                : 'Insufficient evidence to determine discrepancy.',
            default => null,
        };
    }

    private function resultStatusFor(ReconciliationResult $result): string
    {
        return match ($result) {
            ReconciliationResult::EXACT_MATCH,
            ReconciliationResult::WITHIN_TOLERANCE => 'completed',
            ReconciliationResult::DISCREPANCY => 'discrepancy_held',
            ReconciliationResult::NO_MATCH => 'pending',
            default => 'pending',
        };
    }

    private function getTolerance(string $currency): float
    {
        // C5.1: tolerance is POLICY_UNDECIDED — use conservative threshold.
        // Real tolerance is set by C5.4 policy decision.
        return match (strtoupper($currency)) {
            'TRY' => 25.00,
            'EUR' => 1.00,
            'USD' => 1.00,
            default => 25.00,
        };
    }
}
