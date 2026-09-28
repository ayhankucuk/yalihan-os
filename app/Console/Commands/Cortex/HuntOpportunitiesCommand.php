<?php

namespace App\Console\Commands\Cortex;

use App\Models\SaaS\Tenant;
use App\Services\Cortex\OpportunityHunter;
use App\Services\Logging\LogService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Console\Command;

class HuntOpportunitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cortex:hunt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cortex Avcı Modülü: Sıcak fırsatları tarar ve tebliğ eder (tenant-bounded).';

    /**
     * Execute the console command.
     */
    public function handle(OpportunityHunter $hunter, TenantContextService $tenantService): int
    {
        $this->info('🏹 Cortex Avcı Modülü başlatılıyor...');

        $startTime = microtime(true);

        // 1. Capture pre-existing tenant context
        $originalTenant = $tenantService->hasTenant() ? $tenantService->getTenant() : null;

        try {
            // 2. Discover active tenants deterministically
            $tenants = Tenant::where(function ($query) {
                $query->where('aktiflik_durumu', 1)
                    ->orWhere('aktiflik_durumu', 'active')
                    ->orWhere('aktiflik_durumu', 'aktif')
                    ->orWhere('status', 'active');
            })->orderBy('id')->get();

            if ($tenants->isEmpty()) {
                $this->warn('⚠️ Aktif tenant bulunamadı. Tarama yapılmadı.');
                return Command::SUCCESS;
            }

            $totalOpportunities = 0;
            $processedTenants = 0;
            $failedTenants = 0;

            // 3. Iterate tenants under their authoritative context
            foreach ($tenants as $tenant) {
                try {
                    $tenantService->setTenant($tenant);

                    $tenantOpportunities = $hunter->scanForOpportunities();
                    $count = count($tenantOpportunities);
                    $totalOpportunities += $count;
                    $processedTenants++;

                    $this->line("  ✓ Tenant #{$tenant->id} ({$tenant->name}): {$count} fırsat bulundu.");
                } catch (\Throwable $e) {
                    $failedTenants++;
                    LogService::error("HuntOpportunitiesCommand: Tenant #{$tenant->id} taraması başarısız", [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ], $e);
                    $this->error("  ❌ Tenant #{$tenant->id} ({$tenant->name}) hatası: {$e->getMessage()}");
                } finally {
                    $tenantService->clearTenant();
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            $this->info("✅ Tarama tamamlandı. Toplam {$totalOpportunities} yeni fırsat yakalandı ({$processedTenants} tenant işlendi, {$failedTenants} hata).");
            $this->info("⏱️ Süre: {$duration} saniye.");

            LogService::info('HuntOpportunitiesCommand: Tarama tamamlandı.', [
                'count' => $totalOpportunities,
                'duration' => $duration,
                'processed_tenants' => $processedTenants,
                'failed_tenants' => $failedTenants,
            ]);

            return Command::SUCCESS;
        } finally {
            // 4. Restore pre-existing context
            if ($originalTenant) {
                $tenantService->setTenant($originalTenant);
            } else {
                $tenantService->clearTenant();
            }
        }
    }
}
