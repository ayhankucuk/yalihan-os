<?php

namespace App\Services;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\PropertyReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FinancialLedgerService
 *
 * SAB Phase 15 - Double Entry Ledger Accounting System
 *
 * Rules:
 * - All state changes MUST be wrapped in DB::transaction()
 * - Debits MUST equal Credits for every transaction_group_id
 * - Financial records are IMMUTABLE => Reversals are required for fixes.
 * - Base amounts are converted to and stored in TRY.
 */
class FinancialLedgerService
{
    public function __construct(
        private readonly FxService $fxService
    ) {}

    /**
     * ✅ SAB Phase 12: Core Atomic Reusable Double-Entry Function (Tenant Aware)
     *
     * @param LedgerAccount $debitAccount  Paranın girdiği / borçlanan hesap
     * @param LedgerAccount $creditAccount Paranın çıktığı / alacaklanan hesap
     * @param float $amount İşlem tutarı
     * @param string $currency İşlem para birimi
     * @param string|null $referenceType İliskili model
     * @param int|null $referenceId İlişkili ID
     * @param string|null $sebep İşlem açıklaması
     * @param int|null $userId Yapan kullanıcı
     * @param string|null $idempotencyKey Tekrar önleme anahtarı
     * @param int|null $tenantId Kiracı ID
     * @return string transaction_group_id
     */
    public function recordDoubleEntry(
        LedgerAccount $debitAccount,
        LedgerAccount $creditAccount,
        float         $amount,
        string        $currency = 'TRY',
        ?string       $referenceType = null,
        ?int          $referenceId = null,
        ?string       $sebep = null,
        ?int          $userId = null,
        ?string       $idempotencyKey = null,
        ?int          $tenantId = null
    ): string {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Ledger işlem tutarı sıfırdan büyük olmalıdır.");
        }

        // Resolve or verify tenant context
        $resolvedTenantId = $tenantId ?? $debitAccount->tenant_id;
        if ($resolvedTenantId !== $creditAccount->tenant_id && $creditAccount->tenant_id !== 0) {
            // Authority Rule: System accounts (ID 0) can participate, but cross-tenant is forbidden.
            throw new \App\Exceptions\Governance\AuthorityLeakageException("Cross-tenant financial transaction detected and blocked.");
        }

        return DB::transaction(function () use (
            $debitAccount, $creditAccount, $amount, $currency, $referenceType, $referenceId, $sebep, $userId, $idempotencyKey, $resolvedTenantId
        ) {
            // Idempotency check
            if ($idempotencyKey) {
                $existing = DB::table('ledger_transactions')->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing->id;
                }
            }

            // Lock accounts for update
            $accountIds = [$debitAccount->id, $creditAccount->id];
            sort($accountIds);
            LedgerAccount::whereIn('id', $accountIds)->lockForUpdate()->get();

            $transactionGroupId = (string) Str::uuid();

            // Record transaction group for idempotency
            DB::table('ledger_transactions')->insert([
                'id' => $transactionGroupId,
                'idempotency_key' => $idempotencyKey,
                'created_at' => now(),
            ]);

            // FX Kilit mekanizması
            $fxRate = $this->fxService->lockRate($currency);
            $baseAmountTRY = $currency === 'TRY' ? $amount : $this->fxService->convertToTRY($amount, $currency, $fxRate);

            // 1. Borç (Debit) Kaydı -> Giren Hesap
            $debitEntry = LedgerEntry::create([
                'tenant_id'            => $resolvedTenantId,
                'transaction_group_id' => $transactionGroupId,
                'account_id'           => $debitAccount->id,
                'debit_amount'         => $amount,
                'credit_amount'        => 0,
                'currency'             => $currency,
                'fx_rate_locked'       => $fxRate,
                'base_amount'          => $baseAmountTRY,
                'reference_type'       => $referenceType,
                'reference_id'         => $referenceId,
                'sebep'                => $sebep,
                'kaynak'               => 'internal',
                'created_by'           => $userId,
            ]);

            // 2. Alacak (Credit) Kaydı -> Çıkan Hesap
            $creditEntry = LedgerEntry::create([
                'tenant_id'            => $resolvedTenantId,
                'transaction_group_id' => $transactionGroupId,
                'account_id'           => $creditAccount->id,
                'debit_amount'         => 0,
                'credit_amount'        => $amount,
                'currency'             => $currency,
                'fx_rate_locked'       => $fxRate,
                'base_amount'          => $baseAmountTRY,
                'reference_type'       => $referenceType,
                'reference_id'         => $referenceId,
                'sebep'                => $sebep,
                'kaynak'               => 'internal',
                'created_by'           => $userId,
            ]);

            // Emit Event for CQRS Projection Sync and Analytics
            event(new \App\Events\LedgerDoubleEntryRecorded($debitEntry, $creditEntry));

            Log::info("Ledger Double-Entry Recorded [{$transactionGroupId}] for Tenant [{$resolvedTenantId}]", [
                'debit_account' => $debitAccount->name,
                'credit_account' => $creditAccount->name,
                'amount' => "{$amount} {$currency}"
            ]);

            return $transactionGroupId;
        });
    }

    /**
     * Record deposit payment (Double Entry: Kasa/Banka DB vs Depozito Yükümlülüğü CR)
     */
    public function recordDepositTransaction(
        int    $propertyId,
        int    $reservationId,
        float  $depositAmountTRY,
        LedgerAccount $cashAccount,
        LedgerAccount $depositLiabilityAccount,
        ?int   $createdBy = null
    ): string {
        return DB::transaction(function () use ($reservationId, $depositAmountTRY, $cashAccount, $depositLiabilityAccount, $createdBy) {
            // Update reservation deposit state
            PropertyReservation::where('id', $reservationId)->update([
                'depozito_durumu' => \App\ValueObjects\TransactionStatus::PAID,
            ]);

            return $this->recordDoubleEntry(
                debitAccount: $cashAccount,
                creditAccount: $depositLiabilityAccount,
                amount: $depositAmountTRY,
                currency: 'TRY',
                referenceType: PropertyReservation::class,
                referenceId: $reservationId,
                sebep: 'Depozito alınması',
                userId: $createdBy,
                tenantId: $cashAccount->tenant_id
            );
        });
    }

    /**
     * Record deposit refund (Double Entry: Depozito Yükümlülüğü DB vs Kasa CR)
     */
    public function recordDepositRefund(
        int    $propertyId,
        int    $reservationId,
        float  $refundAmountTRY,
        LedgerAccount $cashAccount,
        LedgerAccount $depositLiabilityAccount,
        string $sebep = 'Depozito iadesi',
        ?int   $createdBy = null
    ): string {
        return DB::transaction(function () use ($reservationId, $refundAmountTRY, $cashAccount, $depositLiabilityAccount, $sebep, $createdBy) {
            PropertyReservation::where('id', $reservationId)->update([
                'depozito_durumu' => \App\ValueObjects\TransactionStatus::REFUNDED,
            ]);

            return $this->recordDoubleEntry(
                debitAccount: $depositLiabilityAccount,
                creditAccount: $cashAccount,
                amount: $refundAmountTRY,
                currency: 'TRY',
                referenceType: PropertyReservation::class,
                referenceId: $reservationId,
                sebep: $sebep,
                userId: $createdBy,
                tenantId: $cashAccount->tenant_id
            );
        });
    }

    /**
     * Transition financial state on cancellation.
     */
    public function transitionToCancelled(int $reservationId): void
    {
        DB::transaction(function () use ($reservationId) {
            PropertyReservation::where('id', $reservationId)->update([
                'finansal_durum' => \App\ValueObjects\TransactionStatus::CANCELLED,
            ]);
        });
    }

    /**
     * Transition financial state to confirmed (after payment).
     */
    public function transitionToConfirmed(int $reservationId): void
    {
        DB::transaction(function () use ($reservationId) {
            PropertyReservation::where('id', $reservationId)->update([
                'finansal_durum' => \App\ValueObjects\TransactionStatus::CONFIRMED,
            ]);
        });
    }

    /**
     * Get all transactions for a reservation using morph relationship.
     */
    public function getReservationLedger(int $reservationId): \Illuminate\Support\Collection
    {
        return LedgerEntry::where('reference_type', PropertyReservation::class)
            ->where('reference_id', $reservationId)
            ->orderBy('created_at') // context7-ignore
            ->get();
    }

    /**
     * ✅ SAB Phase 15.2: Concurrency Hardening (v6.2)
     * Calculate Account Balance using Row-Level Pessimistic Locking.
     * Prevents write skews during simultaneous balance requests.
     */
    public function getBalance(int $accountId, string $currency = 'TRY'): float
    {
        $cacheKey = "ledger_balance:{$accountId}:{$currency}";

        // Feature: CQRS Read-Model Caching
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return (float) \Illuminate\Support\Facades\Cache::get($cacheKey);
        }

        $balance = DB::transaction(function () use ($accountId, $currency) {
            // Lock the account row to ensure we read the latest committed state
            $account = LedgerAccount::where('id', $accountId)->lockForUpdate()->firstOrFail();
            // Optimization (Read Model): Use Materialized LedgerBalance instead of views
            // This complies with SAB Concurrency & CQRS Read Model guidelines.
            $projection = \App\Models\LedgerBalance::where('account_id', $accountId)
                ->where('tenant_id', $account->tenant_id)
                ->where('currency', $currency)
                ->first();

            return $projection ? (float) $projection->net_balance : 0.0;
        });

        // Store forever, it will be invalidated by the UpdateLedgerBalanceProjection Listener
        \Illuminate\Support\Facades\Cache::forever($cacheKey, $balance);

        return $balance;
    }

    /**
     * ✅ SAB Architecture: Get full balance projection for an account.
     */
    public function getProjection(int $accountId, string $currency = 'TRY'): ?\App\Models\LedgerBalance
    {
        return \App\Models\LedgerBalance::where('account_id', $accountId)
            ->where('currency', $currency)
            ->first();
    }

    /**
     * ✅ Thin Controller compliance: Find account through service.
     */
    public function findAccount(int $id): ?LedgerAccount
    {
        return LedgerAccount::where('id', $id)
            ->where('aktiflik_durumu', true)
            ->first();
    }

    /**
     * ✅ SAB Architecture: Get all accounts with their balances using eager loading.
     * Prevents N+1 query violations in controllers.
     */
    public function getAccountsWithBalances(): \Illuminate\Support\Collection
    {
        return LedgerAccount::with('balances')
            ->where('aktiflik_durumu', true)
            ->orderBy('display_order')
            ->get();
    }

    /**
     * ✅ Thin Controller compliance: Move data shaping logic to service.
     */
    public function getAccountsSummary(): \Illuminate\Support\Collection
    {
        return $this->getAccountsWithBalances()->map(function ($account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'tip' => $account->tip,
                'currency' => $account->currency,
                'aktiflik_durumu' => (bool) $account->aktiflik_durumu,
                'balances' => $account->balances->map(fn ($b) => [
                    'currency' => $b->currency,
                    'net_balance' => (float) $b->net_balance,
                    'version' => $b->version,
                ])->toArray(),
            ];
        });
    }

    /**
     * ✅ Thin Controller compliance: Format projection data for response.
     */
    public function formatBalanceProjection(LedgerAccount $account, string $currency, ?\App\Models\LedgerBalance $projection): array
    {
        return [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'currency' => $currency,
            'total_debit' => $projection ? (float) $projection->total_debit : 0.0,
            'total_credit' => $projection ? (float) $projection->total_credit : 0.0,
            'net_balance' => $projection ? (float) $projection->net_balance : 0.0,
            'version' => $projection ? $projection->version : 0,
            'source' => 'ledger_balances_projection',
        ];
    }
}

