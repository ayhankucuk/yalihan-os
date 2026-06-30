<?php

namespace App\Services\Finance;

use App\Models\Ilan;
use App\Models\User;
use App\Models\Finance\Transaction;
use App\Application\Shared\Services\TenantContextResolver;
use App\Services\FinancialLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Transaction Service (Tahsilat Yönetimi)
 *
 * Handles payment recording, verification, and schedule management
 *
 * Context7 Compliance:
 * - ✅ Uses is_verified boolean (not forbidden keyword)
 * - ✅ Service layer pattern
 * - ✅ Proper decimal handling
 */
class TransactionService
{
    public function __construct(
        private readonly FinancialLedgerService $ledgerService,
        private readonly TenantContextResolver $tenantResolver
    ) {}
    /**
     * Record a payment transaction
     * (Orchestrated by Treasury)
     *
     * @param array $data
     * @return array
     */
    public function recordPayment(array $data): array
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        
        // Validate required fields
        $required = ['ilan_id', 'islem_turu', 'islem_tutari', 'currency', 'payment_method'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Zorunlu alan eksik: {$field}");
            }
        }

        return DB::transaction(function() use ($data, $tenantId) {
            // Prepare transaction data
            $transactionData = [
                'tenant_id' => $tenantId,
                'ilan_id' => $data['ilan_id'],
                'islem_turu' => $data['islem_turu'],
                'islem_tutari' => round($data['islem_tutari'], 2),
                'currency' => $data['currency'] ?? 'TRY',
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'receipt_number' => $data['receipt_number'] ?? null,
                'bank_reference' => $data['bank_reference'] ?? null,
                'is_verified' => false,
                'recorded_by' => auth()->id(),
            ];

            $transaction = Transaction::query()->create($transactionData);

            // 2. Double-Entry Integration (Financial Fortress)
            $this->ledgerService->recordDoubleEntry(
                $tenantId,
                1, // System Account (receiving)
                $data['ilan_id'], // Or Customer/Ilan account
                (float)$data['islem_tutari'],
                $data['currency'] ?? 'TRY',
                "Payment Recorded: #{$transaction->id}"
            );

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'message' => 'Tahsilat kaydı ve defter girişi başarıyla oluşturuldu'
            ];
        });
    }

    /**
     * Verify a payment transaction
     *
     * @param int $transactionId
     * @param int|null $verifierId
     * @return bool
     */
    public function verifyPayment(int $transactionId, ?int $verifierId = null): bool
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $transaction = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->find($transactionId);

        if (!$transaction) {
            return false;
        }

        return $transaction->update([
            'is_verified' => true,
            'verified_by' => $verifierId ?? auth()->id(),
            'verified_at' => now(),
        ]);
    }

    /**
     * Get all transactions for a listing
     *
     * @param int $ilanId
     * @return Collection
     */
    public function getListingTransactions(int $ilanId): Collection
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return Transaction::query()
            ->where('tenant_id', $tenantId)
            ->where('ilan_id', $ilanId)
            ->orderBy('payment_date', 'desc') // context7-ignore
            ->get();
    }

    /**
     * Calculate total paid amount for a listing
     *
     * @param int $ilanId
     * @param bool $verifiedOnly Only count verified payments
     * @return float
     */
    public function getTotalPaid(int $ilanId, bool $verifiedOnly = false): float
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $query = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->where('ilan_id', $ilanId)
            ->whereIn('islem_turu', ['kapora', 'pesinat', 'ara_odeme', 'kapanış']);

        if ($verifiedOnly) {
            $query->where('is_verified', true);
        }

        return (float) $query->sum('islem_tutari');
    }

    /**
     * Calculate outstanding balance (remaining payment)
     *
     * @param Ilan $ilan
     * @return float
     */
    public function getOutstandingBalance(Ilan $ilan): float
    {
        $salePrice = $ilan->fiyat ?? 0;
        $totalPaid = $this->getTotalPaid($ilan->id, true);

        return max(0, $salePrice - $totalPaid);
    }

    /**
     * Generate payment schedule for a listing
     *
     * @param Ilan $ilan
     * @param array $schedule [['type' => 'kapora', 'percentage' => 10, 'days_from_now' => 0], ...] // context7-ignore
     * @return array
     */
    public function generatePaymentSchedule(Ilan $ilan, array $schedule): array
    {
        $salePrice = $ilan->fiyat ?? 0;
        $paymentPlan = [];

        foreach ($schedule as $index => $item) {
            $amount = ($salePrice * $item['percentage']) / 100;
            $dueDate = now()->addDays($item['days_from_now'])->toDateString();

            $paymentPlan[] = [
                'sequence' => $index + 1,
                'transaction_type' => $item['type'], // context7-ignore
                'amount' => round($amount, 2),
                'percentage' => $item['percentage'],
                'due_date' => $dueDate,
                'is_paid' => false
            ];
        }

        return $paymentPlan;
    }

    /**
     * Get unverified transactions (pending verification)
     *
     * @return Collection
     */
    public function getPendingVerification(): Collection
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        return Transaction::query()
            ->where('tenant_id', $tenantId)
            ->where('is_verified', false)
            ->orderBy('payment_date', 'desc') // context7-ignore
            ->get();
    }

    /**
     * Get transaction statistics for date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStatistics(string $startDate, string $endDate): array
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $transactions = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();

        $stats = [
            'total_count' => $transactions->count(),
            'verified_count' => $transactions->where('is_verified', true)->count(),
            'islem_tutari' => $transactions->sum('islem_tutari'),
            'verified_amount' => $transactions->where('is_verified', true)->sum('islem_tutari'),
            'by_type' => [],
            'by_method' => []
        ];

        // Group by transaction type
        foreach ($transactions->groupBy('islem_turu') as $type => $group) {
            $stats['by_type'][$type] = [
                'count' => $group->count(),
                'islem_tutari' => $group->sum('islem_tutari')
            ];
        }

        // Group by payment method
        foreach ($transactions->groupBy('payment_method') as $method => $group) {
            $stats['by_method'][$method] = [
                'count' => $group->count(),
                'islem_tutari' => $group->sum('islem_tutari')
            ];
        }

        return $stats;
    }

    /**
     * Cancel/refund a transaction
     *
     * @param int $transactionId
     * @param string|null $reason
     * @return array
     */
    public function cancelTransaction(int $transactionId, ?string $reason = null): array
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $transaction = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->find($transactionId);

        if (!$transaction) {
            throw new \RuntimeException('İşlem bulunamadı');
        }

        return DB::transaction(function() use ($transaction, $reason, $tenantId) {
            // Create refund transaction
            if ($transaction->islem_turu !== 'iade') {
                $refund = Transaction::query()->create([
                    'tenant_id' => $tenantId,
                    'ilan_id' => $transaction->ilan_id,
                    'islem_turu' => 'iade',
                    'islem_tutari' => $transaction->islem_tutari,
                    'currency' => $transaction->currency,
                    'payment_method' => $transaction->payment_method,
                    'payment_date' => now()->toDateString(),
                    'description' => "İade: {$reason}",
                    'is_verified' => false,
                    'recorded_by' => auth()->id(),
                ]);

                // Double-Entry Integration for Refund
                $this->ledgerService->recordDoubleEntry(
                    $tenantId,
                    $transaction->ilan_id, // Refund back to user/ilan
                    1, // From system
                    (float)$transaction->islem_tutari,
                    $transaction->currency,
                    "Refund: #{$transaction->id}"
                );
            }

            // Soft delete original
            $transaction->delete();

            return [
                'success' => true,
                'message' => 'İşlem iptal edildi ve iade kaydı oluşturuldu'
            ];
        });
    }

    /**
     * Get unverified transactions count for dashboard.
     */
    public function getUnverifiedCount(): int
    {
        return Transaction::query()
            ->where('is_verified', false)
            ->count();
    }

    /**
     * Get paginated transactions for admin.
     */
    public function getPaginatedTransactions(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $tenantId = $this->tenantResolver->resolve()->tenantId;
        $query = Transaction::query()
            ->where('transactions.tenant_id', $tenantId)
            ->leftJoin('ilanlar', 'transactions.ilan_id', '=', 'ilanlar.id')
            ->leftJoin('users as recorders', 'transactions.recorded_by', '=', 'recorders.id')
            ->select('transactions.*', 'ilanlar.baslik as ilan_baslik', 'recorders.name as recorder_name');

        if (isset($filters['verified'])) {
            $query->where('transactions.is_verified', $filters['verified'] == '1');
        }
        if (isset($filters['transaction_type'])) {
            $query->where('transactions.transaction_type', $filters['transaction_type']);
        }

        return $query->orderBy('transactions.payment_date', 'desc')->paginate(20); // context7-ignore
    }
}
