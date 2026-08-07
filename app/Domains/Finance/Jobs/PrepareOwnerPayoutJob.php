<?php

namespace App\Domains\Finance\Jobs;

use App\Domains\Finance\Services\FinanceAgentFeatureFlags;
use App\Domains\Finance\Services\OwnerPayoutPreparationService;
use App\Domains\Finance\ValueObjects\PayoutPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * PrepareOwnerPayoutJob
 *
 * EX-002 Finance Agent — WAVE 4
 *
 * Bir ilan için belirtilen dönemdeki owner payout'ı kuyrukta hazırlar.
 * İş mantığı OwnerPayoutPreparationService'tedir — job sadece orchestrate eder.
 */
class PrepareOwnerPayoutJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    public function __construct(
        private readonly int    $tenantId,
        private readonly int    $ilanId,
        private readonly string $periodStart,
        private readonly string $periodEnd,
        private readonly int    $preparedBy,
    ) {}

    public function handle(
        OwnerPayoutPreparationService $preparationService,
        FinanceAgentFeatureFlags $featureFlags,
    ): void {
        try {
            if (!$featureFlags->isOwnerPayoutEnabled($this->tenantId)) {
                Log::info('PrepareOwnerPayoutJob: Skipped — owner payout disabled', [
                    'tenant_id' => $this->tenantId,
                    'ilan_id'   => $this->ilanId,
                ]);
                $this->delete();
                return;
            }

            $period = PayoutPeriod::of($this->periodStart, $this->periodEnd);

            $ownerPayout = $preparationService->prepare(
                $this->tenantId,
                $this->ilanId,
                $period,
                $this->preparedBy,
            );

            Log::info('PrepareOwnerPayoutJob: Complete', [
                'payout_id'   => $ownerPayout->id,
                'tenant_id'   => $this->tenantId,
                'ilan_id'     => $this->ilanId,
                'net_payout'  => $ownerPayout->net_owner_payout,
                'currency'    => $ownerPayout->currency,
            ]);

        } catch (\Throwable $e) {
            Log::error('PrepareOwnerPayoutJob: Failed', [
                'tenant_id'    => $this->tenantId,
                'ilan_id'      => $this->ilanId,
                'period_start' => $this->periodStart,
                'period_end'   => $this->periodEnd,
                'error'        => $e->getMessage(),
                'attempt'      => $this->attempts(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PrepareOwnerPayoutJob: Permanently failed', [
            'tenant_id'    => $this->tenantId,
            'ilan_id'      => $this->ilanId,
            'period_start' => $this->periodStart,
            'period_end'   => $this->periodEnd,
            'error'        => $exception->getMessage(),
        ]);
    }
}
