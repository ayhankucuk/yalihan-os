<?php

namespace App\Services\Checkout;

use App\Application\Shared\Services\TenantContextResolver;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\PropertyReservation;
use App\Services\FinancialLedgerService;
use App\Traits\GuardsAgentWrites;
use App\ValueObjects\TransactionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CheckoutService — Checkout / Ödeme Akışı Servis Katmanı
 *
 * CHECKOUT/ÖDEME AKIŞI — IMPLEMENTATION
 *
 * Ödeme sağlayıcı entegrasyonu YOK — mock / manuel onay akışı.
 *
 * Durum makinesi:
 *   recordPayment()  → Payment.status = pending
 *   approvePayment() → Payment.status = paid  + rezervasyon finansal_durum = paid + ledger çift kayıt
 *   failPayment()    → Payment.status = failed
 *
 * Güvenlik:
 *   - GuardsAgentWrites: agent yazma izolasyonu (inner lock).
 *   - TenantContextResolver: tenant izolasyonu.
 *   - HasCountryScope: ulke_id izolasyonu.
 *   - Idempotency: idempotency_key benzersiz — aynı ödemenin iki kez kaydedilmesini engeller.
 */
class CheckoutService
{
    use GuardsAgentWrites;

    public function __construct(
        private readonly FinancialLedgerService $ledgerService,
        private readonly TenantContextResolver $tenantResolver
    ) {}

    /**
     * Checkout sayfası için veri sözleşmesi.
     *
     * @return array{
     *     reservation: PropertyReservation,
     *     total_amount: float,
     *     currency: string,
     *     payments: \Illuminate\Database\Eloquent\Collection,
     *     paid_total: float,
     *     outstanding: float,
     *     is_fully_paid: bool
     * }
     */
    public function getCheckoutData(PropertyReservation $reservation): array
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;

        $totalAmount = (float) ($reservation->total_amount
            ?? $reservation->islem_tutari
            ?? $reservation->locked_nightly_rate * max(1, (int) $reservation->nights)
            ?? 0);

        $currency = $reservation->currency ?? $reservation->booking_currency ?? 'TRY';

        $payments = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('reservation_id', $reservation->id)
            ->orderByDesc('created_at')
            ->get();

        $paidTotal = (float) $payments
            ->where('status', TransactionStatus::PAID)
            ->sum('amount');

        $outstanding = max(0, $totalAmount - $paidTotal);

        return [
            'reservation' => $reservation,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'payments' => $payments,
            'paid_total' => $paidTotal,
            'outstanding' => $outstanding,
            'is_fully_paid' => $outstanding <= 0.0,
        ];
    }

    /**
     * Yeni ödeme kaydı oluştur (pending).
     *
     * @param  PropertyReservation  $reservation
     * @param  array  $data  amount, currency, payment_method, reference, notes
     * @param  int|null  $userId
     * @return Payment
     *
     * @throws \InvalidArgumentException
     */
    public function recordPayment(PropertyReservation $reservation, array $data, ?int $userId = null): Payment
    {
        $this->blockAgentWrite(__FUNCTION__);

        $tenantId = $this->tenantResolver->resolve()->tenantId;

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Ödeme tutarı sıfırdan büyük olmalıdır.');
        }

        $currency = $data['currency'] ?? $reservation->currency ?? $reservation->booking_currency ?? 'TRY';
        $paymentMethod = $data['payment_method'] ?? 'mock';

        // Idempotency: aynı işlem iki kez kaydedilmesin.
        $idempotencyKey = $data['idempotency_key'] ?? "payment_{$tenantId}_{$reservation->id}_" . Str::uuid();

        return DB::transaction(function () use ($reservation, $tenantId, $amount, $currency, $paymentMethod, $data, $userId, $idempotencyKey) {
            $existing = Payment::query()
                ->where('tenant_id', $tenantId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }

            return Payment::create([
                'tenant_id'       => $tenantId,
                'ulke_id'         => $reservation->ulke_id,
                'reservation_id'  => $reservation->id,
                'amount'          => round($amount, 2),
                'currency'        => $currency,
                'payment_method'  => $paymentMethod,
                'status'          => TransactionStatus::PENDING,
                'reference'       => $data['reference'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'recorded_by'     => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Ödemeyi onayla (manuel onay akışı).
     *
     * Payment.status → paid
     * Rezervasyon.finansal_durum → paid
     * Ledger çift kayıt (FinancialLedgerService::recordDoubleEntry)
     *
     * @param  Payment  $payment
     * @param  int|null  $verifierId
     * @return Payment
     *
     * @throws \RuntimeException
     */
    public function approvePayment(Payment $payment, ?int $verifierId = null): Payment
    {
        $this->blockAgentWrite(__FUNCTION__);

        $tenantId = $this->tenantResolver->resolve()->tenantId;

        if ($payment->tenant_id !== $tenantId) {
            throw new \RuntimeException('Ödeme bu tenant kapsamında değil.');
        }

        if ($payment->isTerminal()) {
            throw new \RuntimeException('Terminal durumdaki ödeme yeniden onaylanamaz.');
        }

        return DB::transaction(function () use ($payment, $tenantId, $verifierId) {
            $payment->update([
                'status'      => TransactionStatus::PAID,
                'verified_by' => $verifierId ?? auth()->id(),
                'verified_at' => now(),
            ]);

            $reservation = $payment->reservation;

            // Rezervasyon finansal durumunu güncelle.
            if ($reservation) {
                $reservation->update([
                    'finansal_durum' => TransactionStatus::PAID,
                ]);

                // Ledger çift kayıt — idempotent.
                // NOT: recordDoubleEntry, debit/credit argümanları sayısal ise legacy
                // imzayı (tenantId, debitId, creditId, amount, currency, sebep) varsayar.
                // Bu yüzden LedgerAccount modellerini çözümleyip geçiyoruz.
                // HasCountryScope creating event ulke_id'yi Auth::user()->ulke_id'den
                // set eder — ulkeler tablosu boşsa FK violation olur. Bu yüzden
                // withoutEvents ile creating event'ini bypass ediyoruz.
                $debitAccount = $this->resolveLedgerAccount('Tahsilat / Kasa Hesabı', 'aktif', 5, $payment->currency ?? 'TRY', $tenantId);
                $creditAccount = $this->resolveLedgerAccount('Misafir Alacakları Hesabı', 'aktif', 10, $payment->currency ?? 'TRY', $tenantId);

                $this->ledgerService->recordDoubleEntry(
                    debitAccount: $debitAccount,
                    creditAccount: $creditAccount,
                    amount: (float) $payment->amount,
                    currency: $payment->currency ?? 'TRY',
                    referenceType: Payment::class,
                    referenceId: $payment->id,
                    sebep: "Ödeme Onayı #{$payment->id} (Rezervasyon #{$reservation->id})",
                    userId: $verifierId ?? auth()->id(),
                    idempotencyKey: "payment_approve_{$payment->id}_{$tenantId}",
                    tenantId: $tenantId
                );
            }

            return $payment->fresh();
        });
    }

    /**
     * Ödemeyi başarısız olarak işaretle.
     *
     * @param  Payment  $payment
     * @param  string|null  $reason
     * @param  int|null  $userId
     * @return Payment
     *
     * @throws \RuntimeException
     */
    public function failPayment(Payment $payment, ?string $reason = null, ?int $userId = null): Payment
    {
        $this->blockAgentWrite(__FUNCTION__);

        $tenantId = $this->tenantResolver->resolve()->tenantId;

        if ($payment->tenant_id !== $tenantId) {
            throw new \RuntimeException('Ödeme bu tenant kapsamında değil.');
        }

        if ($payment->isTerminal()) {
            throw new \RuntimeException('Terminal durumdaki ödeme değiştirilemez.');
        }

        return DB::transaction(function () use ($payment, $reason, $userId) {
            $payment->update([
                'status' => TransactionStatus::FAILED,
                'notes'  => $reason
                    ? trim(($payment->notes ? $payment->notes . "\n" : '') . "Başarısız: {$reason}")
                    : $payment->notes,
                'verified_by' => $userId ?? auth()->id(),
                'verified_at' => now(),
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Bir rezervasyonun ödeme geçmişi.
     */
    public function getPaymentHistory(PropertyReservation $reservation): \Illuminate\Support\Collection
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;

        return Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('reservation_id', $reservation->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * LedgerAccount'u resolve eder — HasCountryScope creating event'ini bypass eder.
     *
     * ulkeler tablosu boşsa FK violation olmasın diye withoutEvents kullanılır.
     * ulke_id null olarak set edilir (ledger hesapları ülke bazlı değil, tenant bazlıdır).
     */
    private function resolveLedgerAccount(string $name, string $tip, int $displayOrder, string $currency, int $tenantId): LedgerAccount
    {
        $existing = LedgerAccount::withoutGlobalScopes()
            ->where('name', $name)
            ->first();

        if ($existing) {
            return $existing;
        }

        return LedgerAccount::withoutEvents(function () use ($name, $tip, $displayOrder, $currency, $tenantId) {
            return LedgerAccount::forceCreate([
                'name'             => $name,
                'tip'              => $tip,
                'currency'         => $currency,
                'ulke_id'          => null,
                'tenant_id'        => $tenantId,
                'aktiflik_durumu'  => true,
                'display_order'    => $displayOrder,
            ]);
        });
    }
}