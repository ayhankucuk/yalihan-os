<?php

namespace App\Domains\Finance\Listeners;

use App\Domains\Finance\Events\AirbnbPayoutImported;
use App\Domains\Finance\Events\OwnerPayoutPrepared;
use App\Domains\Finance\Events\PayoutReconciled;
use App\Domains\Finance\Services\FinanceAuditService;
use Illuminate\Support\Facades\Log;

/**
 * FinanceAuditListener
 *
 * EX-002 Finance Agent — WAVE 3
 *
 * Finance Agent event'lerini dinler ve audit log'a yazar.
 * EventServiceProvider'da kayıt edilmesi gerekir.
 */
class FinanceAuditListener
{
    public function __construct(
        private readonly FinanceAuditService $auditService,
    ) {}

    public function handlePayoutImported(AirbnbPayoutImported $event): void
    {
        try {
            $this->auditService->auditPayoutImported($event);
        } catch (\Throwable $e) {
            Log::error('FinanceAuditListener: Failed to audit AirbnbPayoutImported', [
                'import_id' => $event->importId,
                'error'     => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handlePayoutReconciled(PayoutReconciled $event): void
    {
        try {
            $this->auditService->auditPayoutReconciled($event);
        } catch (\Throwable $e) {
            Log::error('FinanceAuditListener: Failed to audit PayoutReconciled', [
                'import_id' => $event->importId,
                'error'     => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handleOwnerPayoutPrepared(OwnerPayoutPrepared $event): void
    {
        try {
            $this->auditService->auditOwnerPayoutPrepared($event);
        } catch (\Throwable $e) {
            Log::error('FinanceAuditListener: Failed to audit OwnerPayoutPrepared', [
                'payout_id' => $event->payoutId,
                'error'     => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
