<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GovTenantFreeze extends Command
{
    protected $signature = 'gov:tenant:freeze
                            {--tenant= : The tenant ID to freeze/unfreeze (REQUIRED)}
                            {--unfreeze : Unlock the tenant instead of freezing}
                            {--reason= : Reason for the action}';

    protected $description = 'Emergency Freeze/Unfreeze of a Tenant (Isolation Breach Protocol).';

    public function handle()
    {
        $tenantId = $this->option('tenant');
        $unfreeze = $this->option('unfreeze');
        $reason = $this->option('reason') ?? 'Manual Intervention';

        if (!$tenantId) {
            $this->error('❌ Tenant ID is required.');
            return 1;
        }

        $lockKey = "governance.compromised.{$tenantId}";

        if ($unfreeze) {
            if (!$this->confirm("⚠️  Are you sure you want to UNFREEZE tenant [{$tenantId}]? Ensure isolation breach is resolved.")) {
                return 1;
            }

            Cache::forget($lockKey);
            $this->info("✅ Tenant [{$tenantId}] has been UNENFROZEN.");
            Log::channel('governance_security')->info("MANUAL UNFREEZE: Tenant [{$tenantId}] unlocked by admin.");

            // Optional: Warm cache immediately
            if ($this->confirm('Warm cache for this tenant now?')) {
                $this->call('gov:cache:warm', ['--tenant' => $tenantId]);
            }

        } else {
            // FREEZE
            Cache::forever($lockKey, true);
            $this->error("🔒 Tenant [{$tenantId}] is now FROZEN.");
            $this->line("Reason: {$reason}");

            Log::channel('governance_security')->emergency("MANUAL FREEZE: Tenant [{$tenantId}] locked by admin. Reason: {$reason}");

            // Purge cache to ensure no stale data serves requests
            $this->call('gov:cache:purge', ['--tenant' => $tenantId, '--force' => true]);
        }

        return 0;
    }
}
