<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Models\PropertyConfigVersion;
use App\Models\PropertyConfigRule;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use Illuminate\Support\Facades\DB;

class GovBaselineInit extends Command
{
    protected $signature = 'gov:baseline:init
                            {--tenant= : The tenant ID to initialize baseline for}
                            {--reason= : The reason for re-baselining (REQUIRED)}
                            {--force : Force overwrite existing active version}';

    protected $description = 'Initialize a secure, trusted configuration baseline for Governance.';

    public function handle(ConfigSnapshotService $snapshotService)
    {
        $tenantId = $this->option('tenant') ?? 'SYSTEM';
        $reason = $this->option('reason');
        $force = $this->option('force');

        if (!$reason) {
            $this->error('❌ Reason is required for audit trail. Use --reason="Initial Setup"');
            return 1;
        }

        $this->info("🛡️  Initializing Governance Baseline for Tenant: [{$tenantId}]");

        if ($tenantId === 'SYSTEM' && !$this->confirm('⚠️  You are initializing SYSTEM baseline. This affects global defaults. Continue?')) {
            return 1;
        }

        // Check existing active version
        $existing = PropertyConfigVersion::activeForTenant($tenantId)->first();
        if ($existing && !$force) {
            $this->error("❌ Active version exists for [{$tenantId}]. Use --force to overwrite.");
            return 1;
        }

        DB::transaction(function () use ($tenantId, $reason, $snapshotService, $existing) {
            // Deactivate existing
            if ($existing) {
                $existing->update([
                    'yonetim_durumu' => 'ARCHIVED',
                    'aktiflik_durumu' => false
                ]);
                $this->warn("⚠️  Archived previous version: {$existing->version_hash}");
            }

            // Set tenant context for snapshot (if applicable)
            config(['app.tenant_id' => $tenantId]);

            // Create Snapshot from current DB state
            $snapshot = $snapshotService->capture();

            // Create New Version
            $version = PropertyConfigVersion::create([
                'tenant_id' => $tenantId,
                'version_hash' => 'v_baseline_' . time(),
                'yonetim_durumu' => 'AKTIF',
                'aktiflik_durumu' => true,
                'snapshot_json' => $snapshot,
                'signature' => ConfigSnapshotService::computeSignature($snapshot),
                'parent_hash' => $existing?->version_hash,
                'author_id' => 1, // System Admin
                'change_summary' => "BASELINE INIT: {$reason}",
                'risk_score' => 0, // Baseline is trusted
            ]);

            $this->info("✅ Baseline Created: {$version->version_hash}");
            $this->info("🔒 Signature: {$version->signature}");
        });

        // Clear Cache
        $this->call('gov:cache:purge', ['--tenant' => $tenantId]);

        return 0;
    }
}
