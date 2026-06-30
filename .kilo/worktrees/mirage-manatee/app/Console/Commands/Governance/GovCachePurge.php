<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GovCachePurge extends Command
{
    protected $signature = 'gov:cache:purge
                            {--tenant= : The tenant ID to purge cache for}
                            {--force : Force purge without confirmation}';

    protected $description = 'Purge Governance Cache for a specific tenant or globally.';

    public function handle()
    {
        $tenantId = $this->option('tenant');
        $force = $this->option('force');

        if (!$tenantId) {
            if (!$force && !$this->confirm('⚠️  This will purge Governance cache for ALL tenants. Continue?')) {
                return 1;
            }

            // Global Purge
            // Note: This is an approximation as we can't wildcard delete in standard Redis/Memcached easily without keys
            // In a real scenario, we'd iterate known tenants or use tags if supported.
            // For Context7, we assume tagged cache or known keys.
            $this->warn("Creating listing of all tenants to purge...");
            // Implementation depends on Cache driver. Assuming Redis tags "governance_v2"
            try {
                Cache::tags(['governance_v2'])->flush();
                $this->info("✅ Global Governance Cache Purged (Tagged: governance_v2)");
            } catch (\Exception $e) {
                 $this->warn("⚠️  Cache tags not supported. Attempting key-based purge for known tenants.");
                 // Fallback to iterating tenants
                 $tenants = \App\Models\PropertyConfigVersion::distinct()->pluck('tenant_id');
                 foreach($tenants as $t) {
                     $this->purgeTenant($t);
                 }
            }
            return 0;
        }

        return $this->purgeTenant($tenantId);
    }

    private function purgeTenant(string $tenantId): int
    {
        $keys = [
            "gov_v2:{$tenantId}:active_version",
            "gov_v2:{$tenantId}:rules",
            "gov_v2:{$tenantId}:templates",
            "governance.compromised.{$tenantId}"
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Also flush tags if supported
        try {
            Cache::tags(["tenant:{$tenantId}", 'governance'])->flush();
        } catch (\Exception $e) {
            // Ignore if tags not supported
        }

        $this->info("✅ Cache purged for tenant: [{$tenantId}]");
        return 0;
    }
}
