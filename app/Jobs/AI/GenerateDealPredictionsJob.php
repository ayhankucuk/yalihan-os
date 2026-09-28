<?php

namespace App\Jobs\AI;

use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use App\Queue\Contracts\TenantAwareJobInterface;
use App\Queue\Middleware\RestoreTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\SaaS\TenantContextService;

/**
 * SAB SEALED
 * Tenant-Aware Deal Prediction Generator
 *
 * TenantAwareJobInterface contract:
 * - getTenantId(): derives tenant from listing_id at runtime
 * - getUserId(): derives user from listing's danisman
 * - middleware(): RestoreTenantContext ensures tenant context is set before handle()
 *
 * SerializesModels: Ilan ID only (not full model) to avoid stale state.
 * Tenant context restored by RestoreTenantContext middleware BEFORE handle().
 */
class GenerateDealPredictionsJob implements ShouldQueue, TenantAwareJobInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    /**
     * Serialize Ilan ID only (not full model) to avoid stale state.
     * Tenant context will be restored from DB at job execution time.
     */
    public function __construct(
        public int $ilanId
    ) {}

    public function getTenantId(): ?int
    {
        $ilan = Ilan::withoutGlobalScopes()->find($this->ilanId);
        return $ilan?->tenant_id;
    }

    public function getUserId(): ?int
    {
        $ilan = Ilan::withoutGlobalScopes()->find($this->ilanId);
        return $ilan?->danisman_id;
    }

    public function middleware(): array
    {
        return [new RestoreTenantContext(app(TenantContextService::class))];
    }

    public function handle(YalihanCortex $cortex): void
    {
        try {
            // Re-fetch listing under current tenant scope (restored by middleware)
            $ilan = Ilan::find($this->ilanId);

            if (!$ilan) {
                Log::warning("DealPrediction: listing {$this->ilanId} not found at execution time");
                return;
            }

            $cortex->predictDeal($ilan, [
                'trigger' => 'job',
                'snapshot' => true,
            ]);

        } catch (\Exception $e) {
            Log::error("Job failed for listing {$this->ilanId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateDealPredictionsJob permanently failed for listing {$this->ilanId}: " . $exception->getMessage());
    }
}