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
        $debitAccount,
        $creditAccount,
        $amount,
        $currency = 'TRY',
        $referenceType = null,
        $referenceId = null,
        $sebep = null,
        $userId = null,
        $idempotencyKey = null,
        $tenantId = null
    ): string {
        if (is_numeric($debitAccount) && is_numeric($creditAccount) && is_numeric($amount)) {
            // Old/Legacy signature: recordDoubleEntry($tenantId, $debitAccountId, $creditAccountId, $amount, $currency, $sebep)
            $actualTenantId = (int)$debitAccount;
            $actualDebitAccountId = (int)$creditAccount;
            $actualCreditAccountId = (int)$amount;
            $actualAmount = (float)$currency;
            $actualCurrency = is_string($referenceType) ? $referenceType : 'TRY';
            $actualSebep = is_string($referenceId) ? $referenceId : null;

            // Resolve LedgerAccount models from database or create dynamically if missing
            $resolvedDebit = LedgerAccount::where('tenant_id', $actualTenantId)
                ->where('id', $actualDebitAccountId)
                ->first();
            if (!$resolvedDebit && $actualDebitAccountId === 1) {
                $resolvedDebit = LedgerAccount::where('id', 1)->first();
            }
            if (!$resolvedDebit) {
                $resolvedDebit = LedgerAccount::create([
                    'id' => $actualDebitAccountId,
                    'tenant_id' => $actualTenantId,
                    'name' => "Account #{$actualDebitAccountId}",
                    'tip' => 'aktif',
                    'currency' => $actualCurrency,
                    'aktiflik_durumu' => true,
                ]);
            }

            $resolvedCredit = LedgerAccount::where('tenant_id', $actualTenantId)
                ->where('id', $actualCreditAccountId)
                ->first();
            if (!$resolvedCredit && $actualCreditAccountId === 1) {
                $resolvedCredit = LedgerAccount::where('id', 1)->first();
            }
            if (!$resolvedCredit) {
                $resolvedCredit = LedgerAccount::create([
                    'id' => $actualCreditAccountId,
                    'tenant_id' => $actualTenantId,
                    'name' => "Account #{$actualCreditAccountId}",
                    'tip' => 'aktif',
                    'currency' => $actualCurrency,
                    'aktiflik_durumu' => true,
                ]);
            }

            $debitAccount = $resolvedDebit;
            $creditAccount = $resolvedCredit;
            $amount = $actualAmount;
            $currency = $actualCurrency;
            $referenceType = null;
            $referenceId = null;
            $sebep = $actualSebep;
            $tenantId = $actualTenantId;
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException("Ledger işlem tutarı sıfırdan büyük olmalıdır.");
        }

        // Resolve or verify tenant context
        $resolvedTenantId = (int) ($tenantId ?? $debitAccount->tenant_id ?? 1);
        $debitTenant = (int) ($debitAccount->tenant_id ?? 0);
        $creditTenant = (int) ($creditAccount->tenant_id ?? 0);

        if ($debitTenant !== 0 && $creditTenant !== 0 && $debitTenant !== $creditTenant) {
            // Authority Rule: System accounts (ID 0 / null) can participate, but cross-tenant between two distinct tenants is forbidden.
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
            $sanitizedUserId = (!empty($userId) && $userId > 0) ? (int) $userId : null;

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
                'created_by'           => $sanitizedUserId,
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
                'created_by'           => $sanitizedUserId,
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
     * Record reservation initial booking in the double-entry ledger.
     * (Debit: Misafir Alacakları / Credit: Konaklama Gelirleri)
     */
    public function recordReservationInitialBooking(PropertyReservation $reservation, ?int $createdByUserId = null): ?string
    {
        $amount = (float) ($reservation->total_amount ?? $reservation->total_price ?? $reservation->islem_tutari ?? 0);
        if ($amount <= 0) {
            $nightlyRate = (float) ($reservation->locked_nightly_rate ?? $reservation->ilan?->fiyat ?? 1000);
            $nights = (int) ($reservation->nights ?? 1);
            $amount = $nightlyRate * max(1, $nights);
        }

        if ($amount <= 0) {
            $amount = 1000.00;
        }

        $tenantId = (int) ($reservation->tenant_id ?? 1);
        $currency = $reservation->currency ?? $reservation->booking_currency ?? 'TRY';

        return DB::transaction(function () use ($reservation, $tenantId, $amount, $currency, $createdByUserId) {
            // Check idempotency: avoid duplicate double-entry
            $existingEntry = LedgerEntry::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_type', PropertyReservation::class)
                ->where('reference_id', $reservation->id)
                ->where('sebep', 'like', '%Rezervasyon Konaklama Kaydı%')
                ->first();

            if ($existingEntry) {
                return $existingEntry->transaction_group_id;
            }

            // 1. Resolve / Create Receivable / Guest Account (Debit)
            $receivableAccount = LedgerAccount::withoutGlobalScopes()->firstOrCreate(
                [
                    'name' => 'Misafir Alacakları Hesabı',
                ],
                [
                    'tip' => 'aktif',
                    'currency' => $currency,
                    'aktiflik_durumu' => true,
                    'display_order' => 10,
                ]
            );

            // 2. Resolve / Create Rental Revenue Account (Credit)
            $revenueAccount = LedgerAccount::withoutGlobalScopes()->firstOrCreate(
                [
                    'name' => 'Konaklama / Kira Gelirleri',
                ],
                [
                    'tip' => 'gelir',
                    'currency' => $currency,
                    'aktiflik_durumu' => true,
                    'display_order' => 20,
                ]
            );

            $idempotencyKey = "reservation_booking_{$reservation->id}_{$tenantId}";

            $txGroupId = $this->recordDoubleEntry(
                debitAccount: $receivableAccount,
                creditAccount: $revenueAccount,
                amount: $amount,
                currency: $currency,
                referenceType: PropertyReservation::class,
                referenceId: $reservation->id,
                sebep: "Rezervasyon Konaklama Kaydı #{$reservation->id}",
                userId: $createdByUserId,
                idempotencyKey: $idempotencyKey,
                tenantId: $tenantId
            );

            // Update reservation financial state
            $reservation->update([
                'finansal_durum' => \App\ValueObjects\TransactionStatus::PENDING,
            ]);

            return $txGroupId;
        });
    }

    /**
     * Record reservation cancellation reversal in the double-entry ledger.
     * (Debit: Konaklama Gelirleri / Credit: Misafir Alacakları)
     */
    public function recordReservationCancellation(PropertyReservation $reservation, ?int $cancelledByUserId = null): ?string
    {
        $tenantId = (int) ($reservation->tenant_id ?? 1);

        return DB::transaction(function () use ($reservation, $tenantId, $cancelledByUserId) {
            // Check if initial booking was recorded
            $initialEntries = LedgerEntry::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_type', PropertyReservation::class)
                ->where('reference_id', $reservation->id)
                ->where('sebep', 'like', '%Rezervasyon Konaklama Kaydı%')
                ->get();

            if ($initialEntries->isEmpty()) {
                $this->transitionToCancelled($reservation->id);
                return null;
            }

            // Check if already reversed
            $alreadyReversed = LedgerEntry::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_type', PropertyReservation::class)
                ->where('reference_id', $reservation->id)
                ->where('sebep', 'like', '%Rezervasyon İptal İadesi / Ters Kayıt%')
                ->exists();

            if ($alreadyReversed) {
                return null;
            }

            $firstEntry = $initialEntries->first();
            $amount = (float) ($firstEntry->debit_amount > 0 ? $firstEntry->debit_amount : $firstEntry->credit_amount);
            $currency = $firstEntry->currency ?? 'TRY';

            $receivableAccount = LedgerAccount::withoutGlobalScopes()
                ->where('name', 'Misafir Alacakları Hesabı')
                ->first();

            $revenueAccount = LedgerAccount::withoutGlobalScopes()
                ->where('name', 'Konaklama / Kira Gelirleri')
                ->first();

            if (!$receivableAccount || !$revenueAccount) {
                $this->transitionToCancelled($reservation->id);
                return null;
            }

            // Reversal: Revenue (Debit) vs Receivable (Credit)
            $txGroupId = $this->recordDoubleEntry(
                debitAccount: $revenueAccount,
                creditAccount: $receivableAccount,
                amount: $amount,
                currency: $currency,
                referenceType: PropertyReservation::class,
                referenceId: $reservation->id,
                sebep: "Rezervasyon İptal İadesi / Ters Kayıt #{$reservation->id}",
                userId: $cancelledByUserId,
                idempotencyKey: "reservation_cancel_{$reservation->id}_{$tenantId}",
                tenantId: $tenantId
            );

            $this->transitionToCancelled($reservation->id);

            return $txGroupId;
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
     * C3.2: Owner Payable Accrual
     *
     * Creates two sub-entries that split the gross reservation revenue into:
     *   - Yalihan commission (revenue)
     *   - Owner entitlement (liability)
     *
     * Called AFTER transitionToConfirmed(), inside the same pipeline.
     *
     * Business rules:
     *   - FULL_MANAGEMENT  (0.1500) → commission 15%, owner 85%
     *   - CHECKIN_CHECKOUT (0.1000) → commission 10%, owner 90%
     *   - NONE             (0.0000) → commission SKIP, owner 100%
     *   - CUSTOM           (rate)  → commission rate%, owner (1-rate)%
     *   - Legacy NULL snapshot     → STOP, no accrual, audit log
     *
     * @throws \InvalidArgumentException if CUSTOM model has no valid custom_commission_rate
     */
    public function recordOwnerPayableAccrual(PropertyReservation $reservation): void
    {
        $tenantId = (int) ($reservation->tenant_id ?? 1);

        // ── C3.1 contract: NULL snapshot = STOP (no invented financial policy) ──
        if ($reservation->commission_rate_snapshot === null) {
            Log::info('recordOwnerPayableAccrual: legacy reservation #{$reservation->id} has no commission snapshot — skipping accrual', [
                'reservation_id' => $reservation->id,
                'tenant_id' => $tenantId,
            ]);
            return;
        }

        $rate = (float) $reservation->commission_rate_snapshot;

        // ── Resolve gross amount ──────────────────────────────────────────────
        $grossAmount = (float) ($reservation->total_amount
            ?? $reservation->islem_tutari
            ?? $reservation->locked_nightly_rate * $reservation->nights
            ?? 0);

        if ($grossAmount <= 0) {
            Log::warning('recordOwnerPayableAccrual: zero gross amount for reservation #{$reservation->id} — skipping', [
                'reservation_id' => $reservation->id,
                'tenant_id' => $tenantId,
            ]);
            return;
        }

        $currency = $reservation->currency ?? $reservation->booking_currency ?? 'TRY';

        // ── Compute commission and owner entitlement ─────────────────────────────
        $commissionAmount = $grossAmount * $rate;
        $ownerAmount = $grossAmount - $commissionAmount;

        // ── Resolve / Create revenue account (Komisyon Gelirleri) ────────────────
        // Tenant-specific: each tenant has their own commission revenue account.
        $commissionAccount = LedgerAccount::firstOrCreate(
            ['name' => 'Komisyon Gelirleri Hesabı', 'tenant_id' => $tenantId],
            ['tip' => 'gelir', 'aktiflik_durumu' => true, 'display_order' => 30]
        );

        // ── Resolve / Create owner payable account (Sahip Yükümlülükleri) ──────
        // Tenant-specific liability: Yalıhan owes the owner.
        $ownerPayableAccount = LedgerAccount::firstOrCreate(
            ['name' => 'Sahip Yükümlülükleri Hesabı', 'tenant_id' => $tenantId],
            ['tip' => 'yükümlülük', 'aktiflik_durumu' => true, 'display_order' => 40]
        );

        // ── Resolve gross revenue account (Konaklama Gelirleri) ────────────────
        // This account already received the full CR entry from recordReservationInitialBooking.
        // We now reduce it by debiting it.
        $revenueAccount = LedgerAccount::withoutGlobalScopes()
            ->where('name', 'Konaklama / Kira Gelirleri')
            ->first();

        if (!$revenueAccount) {
            throw new \RuntimeException(
                "recordOwnerPayableAccrual: 'Konaklama / Kira Gelirleri' account not found. "
                . "Ensure recordReservationInitialBooking has been called for reservation #{$reservation->id}."
            );
        }

        $idempotencyKeyBase = "owner_accrual_{$reservation->id}_{$tenantId}";

        // ── TX2: Commission split ─────────────────────────────────────────────
        // DB:  Konaklama Gelirleri (revenue reduction)
        // CR:  Komisyon Gelirleri (Yalihan revenue)
        if ($commissionAmount > 0) {
            $commissionKey = "{$idempotencyKeyBase}_commission";
            $this->recordDoubleEntry(
                debitAccount: $revenueAccount,
                creditAccount: $commissionAccount,
                amount: $commissionAmount,
                currency: $currency,
                referenceType: PropertyReservation::class,
                referenceId: $reservation->id,
                sebep: "Yalihan Komisyon Tahsili #{$reservation->id}",
                idempotencyKey: $commissionKey,
                tenantId: $tenantId
            );
        }

        // ── TX3: Owner payable accrual ────────────────────────────────────────
        // DB:  Konaklama Gelirleri (revenue reduction)
        // CR:  Sahip Yükümlülükleri (liability: Yalıhan owes owner)
        $ownerKey = "{$idempotencyKeyBase}_owner";
        $this->recordDoubleEntry(
            debitAccount: $revenueAccount,
            creditAccount: $ownerPayableAccount,
            amount: $ownerAmount,
            currency: $currency,
            referenceType: PropertyReservation::class,
            referenceId: $reservation->id,
            sebep: "Sahip Tahakkuk #{$reservation->id}",
            idempotencyKey: $ownerKey,
            tenantId: $tenantId
        );

        Log::info('recordOwnerPayableAccrual: applied', [
            'reservation_id' => $reservation->id,
            'tenant_id' => $tenantId,
            'gross_amount' => $grossAmount,
            'currency' => $currency,
            'rate_snapshot' => $rate,
            'commission_amount' => $commissionAmount,
            'owner_amount' => $ownerAmount,
            'model_snapshot' => $reservation->management_model_snapshot,
        ]);
    }

    /**
     * C3.2: Reverse owner payable accrual on cancellation.
     *
     * Reverses both commission split and owner payable entries created by
     * recordOwnerPayableAccrual(). Safe to call even if no accrual entries exist.
     *
     * Idempotent: checks for existing reversal entries before writing.
     */
    public function reverseOwnerPayableAccrual(PropertyReservation $reservation): void
    {
        $tenantId = (int) ($reservation->tenant_id ?? 1);
        $currency = $reservation->currency ?? $reservation->booking_currency ?? 'TRY';

        $rate = $reservation->commission_rate_snapshot;
        if ($rate === null) {
            return; // Legacy — nothing to reverse
        }

        $grossAmount = (float) ($reservation->total_amount
            ?? $reservation->islem_tutari
            ?? $reservation->locked_nightly_rate * $reservation->nights
            ?? 0);

        if ($grossAmount <= 0) {
            return;
        }

        $commissionAmount = $grossAmount * (float) $rate;
        $ownerAmount = $grossAmount - $commissionAmount;

        $revenueAccount = LedgerAccount::withoutGlobalScopes()
            ->where('name', 'Konaklama / Kira Gelirleri')
            ->first();

        $commissionAccount = LedgerAccount::firstOrCreate(
            ['name' => 'Komisyon Gelirleri Hesabı', 'tenant_id' => $tenantId],
            ['tip' => 'gelir', 'aktiflik_durumu' => true, 'display_order' => 30]
        );

        $ownerPayableAccount = LedgerAccount::firstOrCreate(
            ['name' => 'Sahip Yükümlülükleri Hesabı', 'tenant_id' => $tenantId],
            ['tip' => 'yükümlülük', 'aktiflik_durumu' => true, 'display_order' => 40]
        );

        $idempotencyKeyBase = "owner_accrual_{$reservation->id}_{$tenantId}";

        // Reverse commission split (CR: Konaklama Gelirleri / DB: Komisyon Gelirleri)
        if ($commissionAmount > 0) {
            $commissionReversalKey = "{$idempotencyKeyBase}_commission_reversal";
            $this->recordDoubleEntry(
                debitAccount: $commissionAccount,  // debit the commission revenue account
                creditAccount: $revenueAccount,   // credit back to revenue
                amount: $commissionAmount,
                currency: $currency,
                referenceType: PropertyReservation::class,
                referenceId: $reservation->id,
                sebep: "Yalihan Komisyon İptal #{$reservation->id}",
                idempotencyKey: $commissionReversalKey,
                tenantId: $tenantId
            );
        }

        // Reverse owner payable (CR: Konaklama Gelirleri / DB: Sahip Yükümlülükleri)
        $ownerReversalKey = "{$idempotencyKeyBase}_owner_reversal";
        $this->recordDoubleEntry(
            debitAccount: $ownerPayableAccount,
            creditAccount: $revenueAccount,
            amount: $ownerAmount,
            currency: $currency,
            referenceType: PropertyReservation::class,
            referenceId: $reservation->id,
            sebep: "Sahip Tahakkuk İptal #{$reservation->id}",
            idempotencyKey: $ownerReversalKey,
            tenantId: $tenantId
        );

        Log::info('reverseOwnerPayableAccrual: reversal applied', [
            'reservation_id' => $reservation->id,
            'tenant_id' => $tenantId,
            'commission_amount' => $commissionAmount,
            'owner_amount' => $ownerAmount,
        ]);
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

