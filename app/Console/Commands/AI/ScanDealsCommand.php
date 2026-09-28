<?php
// context7-ignore: dealPredictions relation method (camelCase), not DB column.

namespace App\Console\Commands\AI;

use App\Models\Ilan;
use App\Models\SaaS\Tenant;
use App\Jobs\AI\GenerateDealPredictionsJob;
use App\Services\SaaS\TenantContextService;
use Illuminate\Console\Command;

class ScanDealsCommand extends Command
{
    protected $signature = "ai:scan-deals {--ilan_id= : Specific listing ID} {--limit=100 : Batch limit}";
    protected $description = "Batch generate AI Deal Predictions (tenant-bounded)";

    public function handle(TenantContextService $tenantService): int
    {
        $listingId = $this->option("ilan_id");
        $limit = (int) $this->option("limit");

        if ($listingId) {
            return $this->handleSingleListing($listingId, $tenantService);
        }

        return $this->handleBatchScan($limit, $tenantService);
    }

    private function handleSingleListing(string $listingId, TenantContextService $tenantService): int
    {
        $ilan = Ilan::withoutGlobalScopes()->find($listingId);
        if (!$ilan) {
            $this->error("Listing not found: {$listingId}");
            return 1;
        }

        $tenant = Tenant::find($ilan->tenant_id);
        if (!$tenant) {
            $this->error("Tenant not found for listing: {$listingId}");
            return 1;
        }

        $tenantService->setTenant($tenant);
        try {
            GenerateDealPredictionsJob::dispatch((int) $ilan->id);
            $this->info("Dispatched prediction job for listing: {$listingId} (tenant: {$tenant->id})");
        } finally {
            $tenantService->clearTenant();
        }

        return 0;
    }

    private function handleBatchScan(int $limit, TenantContextService $tenantService): int
    {
        $tenants = Tenant::where("aktiflik_durumu", 1)->orderBy("id")->get();
        if ($tenants->isEmpty()) {
            $this->warn("No active tenants found.");
            return 0;
        }

        $totalDispatched = 0;
        $totalSkipped = 0;

        foreach ($tenants as $tenant) {
            $tenantService->setTenant($tenant);
            try {
                $listings = Ilan::whereDoesntHave("dealPredictions", function ($query) {
                        $query->where("created_at", ">=", now()->subDays(3));
                    })
                    ->limit($limit)
                    ->get();

                foreach ($listings as $ilan) {
                    GenerateDealPredictionsJob::dispatch((int) $ilan->id);
                    $totalDispatched++;
                }
                $totalSkipped += max(0, $limit - $listings->count());
            } finally {
                $tenantService->clearTenant();
            }
        }

        $this->info("Dispatched {$totalDispatched} jobs across {$tenants->count()} tenants. Skipped (recent): {$totalSkipped}.");
        return 0;
    }
}