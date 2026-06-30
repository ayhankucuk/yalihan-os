<?php

namespace App\Listeners;

use App\Events\LedgerDoubleEntryRecorded;
use App\Models\LedgerBalance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class UpdateLedgerBalanceProjection
{
    /**
     * Handle the event.
     * We purposefully do NOT implement ShouldQueue because we want this
     * synchronized strictly within the FinancialLedgerService DB::transaction.
     */
    public function handle(LedgerDoubleEntryRecorded $event): void
    {
        // 1. Update the Debit Account Balance Projection
        $this->updateProjection(
            $event->debitEntry->account_id,
            $event->debitEntry->currency,
            $event->debitEntry->debit_amount,
            0
        );

        // 2. Update the Credit Account Balance Projection
        $this->updateProjection(
            $event->creditEntry->account_id,
            $event->creditEntry->currency,
            0,
            $event->creditEntry->credit_amount
        );
    }

    /**
     * Upserts the projection table efficiently.
     */
    private function updateProjection(int $accountId, string $currency, float $addDebit, float $addCredit): void
    {
        // Use pessimistic lock to prevent concurrent update anomalies in the read model
        $balance = LedgerBalance::where('account_id', $accountId)
            ->where('currency', $currency)
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            // Because we lock for update, if it doesn't exist, we can safely create it
            // Assuming no other transaction just created it (race condition mitigated by the lock above).
            $balance = new LedgerBalance();
            $balance->account_id = $accountId;
            $balance->currency = $currency;
            $balance->total_debit = 0;
            $balance->total_credit = 0;
            $balance->net_balance = 0;
            $balance->version = 1;
        }

        $balance->total_debit += $addDebit;
        $balance->total_credit += $addCredit;

        // Net Balance = Debit - Credit
        $balance->net_balance = $balance->total_debit - $balance->total_credit;

        // Optimistic locking versioning (if needed by external consumers)
        $balance->version += 1;

        $balance->save();

        // Invalidate Cache for this specific account/currency to ensure fresh reads
        $cacheKey = "ledger_balance:{$accountId}:{$currency}";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }
}
