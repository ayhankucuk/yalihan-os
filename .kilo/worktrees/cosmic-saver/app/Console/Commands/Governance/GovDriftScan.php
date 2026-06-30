<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Modules\GovernanceCore\Core\DriftDetectionService;
use App\Models\PropertyConfigVersion;

class GovDriftScan extends Command
{
    protected $signature = 'gov:drift:scan
                            {--tenant= : The tenant ID to scan}
                            {--detail : Show detailed diff}';

    protected $description = 'Scan for configuration drift between Active Version and Database State.';

    public function handle(DriftDetectionService $driftService)
    {
        $tenantId = $this->option('tenant');

        if (!$tenantId) {
            $tenants = PropertyConfigVersion::distinct()->pluck('tenant_id')->toArray();
            $this->info("🔍 Scanning all known tenants: " . implode(', ', $tenants));
            foreach ($tenants as $t) {
                $this->scanTenant($t, $driftService);
            }
            return 0;
        }

        return $this->scanTenant($tenantId, $driftService);
    }

    private function scanTenant(string $tenantId, DriftDetectionService $driftService): int
    {
        $this->line("------------------------------------------------");
        $this->info("📡 Scanning Tenant: [{$tenantId}]");

        try {
            // Set tenant context for the Registry (which is used by the service)
            config(['app.tenant_id' => $tenantId]);

            $report = $driftService->detect();

            if (!$report->hasDrift()) {
                $this->info("✅ System is CLEAN. " . $report->message);
                return 0;
            }

            $this->error("🚨 DRIFT DETECTED!");
            $this->line($report->message);

            if ($this->option('detail')) {
                if (!empty($report->drifts)) {
                    $this->warn("\n[Content Drifts]");
                    $this->table(['Key', 'Name', 'Expected', 'Actual'], $report->drifts);
                }
                if (!empty($report->shadowMissing)) {
                    $this->warn("\n[Shadow Missing]");
                    $this->table(['Key', 'Name'], $report->shadowMissing);
                }
                if (!empty($report->ungoverned)) {
                    $this->warn("\n[Ungoverned Items]");
                    $this->table(['Key', 'Name'], $report->ungoverned);
                }
            } else {
                $this->line("Use --detail to see exactly what changed.");
            }

            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Error scanning tenant [{$tenantId}]: " . $e->getMessage());
            return 1;
        }
    }
}
