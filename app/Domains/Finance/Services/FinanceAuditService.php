<?php

namespace App\Domains\Finance\Services;

use App\Domains\Finance\Events\AirbnbPayoutImported;
use App\Domains\Finance\Events\OwnerPayoutPrepared;
use App\Domains\Finance\Events\PayoutReconciled;
use Illuminate\Support\Facades\Log;

/**
 * FinanceAuditService
 *
 * EX-002 Finance Agent — WAVE 3
 *
 * Finance Agent event'lerini audit log'a yazar.
 * EX-001'deki GuestDeliveryAuditService pattern'ını takip eder.
 */
class FinanceAuditService
{
    public function auditPayoutImported(AirbnbPayoutImported $event): void
    {
        Log::channel('finance_agent')->info('AUDIT: AirbnbPayoutImported', [
            'event'            => 'AirbnbPayoutImported',
            'import_id'        => $event->importId,
            'tenant_id'        => $event->tenantId,
            'airbnb_payout_id' => $event->airbnbPayoutId,
            'net_amount'       => $event->netAmount,
            'currency'         => $event->currency,
            'period_start'     => $event->periodStart,
            'period_end'       => $event->periodEnd,
            'imported_by'      => $event->importedBy,
            'occurred_at'      => now()->toIso8601String(),
        ]);
    }

    public function auditPayoutReconciled(PayoutReconciled $event): void
    {
        Log::channel('finance_agent')->info('AUDIT: PayoutReconciled', [
            'event'            => 'PayoutReconciled',
            'import_id'        => $event->importId,
            'tenant_id'        => $event->tenantId,
            'reconciled_count' => $event->reconciledCount,
            'unmatched_count'  => $event->unmatchedCount,
            'error_count'      => $event->errorCount,
            'period_start'     => $event->periodStart,
            'period_end'       => $event->periodEnd,
            'occurred_at'      => now()->toIso8601String(),
        ]);
    }

    public function auditOwnerPayoutPrepared(OwnerPayoutPrepared $event): void
    {
        Log::channel('finance_agent')->info('AUDIT: OwnerPayoutPrepared', [
            'event'                => 'OwnerPayoutPrepared',
            'payout_id'            => $event->payoutId,
            'tenant_id'            => $event->tenantId,
            'owner_kisi_id'        => $event->ownerKisiId,
            'ilan_id'              => $event->ilanId,
            'net_owner_payout'     => $event->netOwnerPayout,
            'currency'             => $event->currency,
            'period_start'         => $event->periodStart,
            'period_end'           => $event->periodEnd,
            'reconciliation_count' => $event->reconciliationCount,
            'prepared_by'          => $event->preparedBy,
            'occurred_at'          => now()->toIso8601String(),
        ]);
    }
}
