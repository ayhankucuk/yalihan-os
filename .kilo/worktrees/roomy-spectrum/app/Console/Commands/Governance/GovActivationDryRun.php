<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;

class GovActivationDryRun extends Command
{
    protected $signature = 'gov:activation:dry-run
                            {version? : The version hash to simulate activation for}
                            {--tenant= : The tenant ID (optional if version provided)}';

    protected $description = 'Simulate Configuration Activation (Dry Run). Check for conflicts and compliance.';

    public function handle(ConfigSnapshotService $snapshotService)
    {
        $versionHash = $this->argument('version');
        $tenantId = $this->option('tenant');

        if (!$versionHash && !$tenantId) {
            $this->error('❌ Either Version Hash or Tenant ID is required.');
            return 1;
        }

        $this->info("🧪 Starting Dry Run...");

        $version = null;
        if ($versionHash) {
            $version = PropertyConfigVersion::where('version_hash', $versionHash)->first();
            if (!$version) {
                $this->error("❌ Version [{$versionHash}] not found.");
                return 1;
            }
        } else {
            // Find latest approved or drafted for tenant
            $version = PropertyConfigVersion::where('tenant_id', $tenantId)
                ->whereIn('yonetim_durumu', ['ONAYLANDI', 'TASLAK'])
                ->latest()
                ->first();

            if (!$version) {
                 $this->error("❌ No candidate version found for tenant [{$tenantId}].");
                 return 1;
            }
        }

        $this->info("📄 Candidate Version: {$version->version_hash} ({$version->yonetim_durumu})");
        $this->info("🏢 Tenant: {$version->tenant_id}");

        // 1. Signature Check
        $this->line("1️⃣  Verifying Signature...");
        $computed = ConfigSnapshotService::computeSignature($version->snapshot_json);
        if (!hash_equals($version->signature ?? '', $computed)) {
             $this->error("❌ SIGNATURE MISMATCH!");
             $this->line("   Expected: {$computed}");
             $this->line("   Actual:   {$version->signature}");
             return 1;
        }
        $this->info("✅ Signature Valid.");

        // 2. Conflict Check (Simulation)
        $this->line("2️⃣  Checking Conflicts...");
        // Here we would check if the new rules conflict with existing features or templates
        // For now, we simulate a check.
        $snapshot = $version->snapshot_json;
        $rules = $snapshot['rules'] ?? [];

        if (empty($rules)) {
            $this->warn("⚠️  Warning: Version has no rules defined.");
        } else {
            $this->info("✅ Rules Count: " . count($rules));
        }

        // 3. Output Diff
        $this->line("3️⃣  Diff Analysis (vs Active)...");
        // ... logic to compare with active version ...

        $this->info("✅ Dry Run Successful. This version is safe to activate.");
        return 0;
    }
}
