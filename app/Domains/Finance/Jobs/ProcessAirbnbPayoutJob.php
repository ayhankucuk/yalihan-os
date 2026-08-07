<?php

namespace App\Domains\Finance\Jobs;

use App\Domains\Finance\Models\AirbnbPayoutImport;
use App\Domains\Finance\Services\AirbnbPayoutImportService;
use App\Domains\Finance\Services\FinanceAgentFeatureFlags;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessAirbnbPayoutJob
 *
 * EX-002 Finance Agent — WAVE 4
 *
 * Bir AirbnbPayoutImport kaydını kuyrukta işler.
 * İş mantığı service katmanındadır — job sadece orchestrate eder.
 */
class ProcessAirbnbPayoutJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    public function __construct(
        private readonly int   $importId,
        private readonly int   $tenantId,
        private readonly float $commissionRate,
    ) {}

    public function handle(
        AirbnbPayoutImportService $importService,
        FinanceAgentFeatureFlags $featureFlags,
    ): void {
        try {
            if (!$featureFlags->isImportEnabled($this->tenantId)) {
                Log::info('ProcessAirbnbPayoutJob: Skipped — import disabled', [
                    'import_id' => $this->importId,
                    'tenant_id' => $this->tenantId,
                ]);
                $this->delete();
                return;
            }

            $import = AirbnbPayoutImport::where('id', $this->importId)
                ->where('tenant_id', $this->tenantId)
                ->orderBy('id')
                ->first();

            if (!$import) {
                Log::warning('ProcessAirbnbPayoutJob: Import not found', [
                    'import_id' => $this->importId,
                    'tenant_id' => $this->tenantId,
                ]);
                $this->delete();
                return;
            }

            $stats = $importService->reconcileManually($import, $this->commissionRate);

            Log::info('ProcessAirbnbPayoutJob: Complete', [
                'import_id' => $this->importId,
                'tenant_id' => $this->tenantId,
                'stats'     => $stats,
            ]);

        } catch (\Throwable $e) {
            Log::error('ProcessAirbnbPayoutJob: Failed', [
                'import_id' => $this->importId,
                'tenant_id' => $this->tenantId,
                'error'     => $e->getMessage(),
                'attempt'   => $this->attempts(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAirbnbPayoutJob: Permanently failed', [
            'import_id' => $this->importId,
            'tenant_id' => $this->tenantId,
            'error'     => $exception->getMessage(),
        ]);
    }
}
