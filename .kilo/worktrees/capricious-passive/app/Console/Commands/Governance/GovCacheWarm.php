<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Models\PropertyConfigVersion;

class GovCacheWarm extends Command
{
    protected $signature = 'gov:cache:warm
                            {--tenant= : The tenant ID to warm cache for}';

    protected $description = 'Warm Governance Cache for a specific tenant (Deterministic L2 Population).';

    public function handle(ActiveConfigRegistry $registry)
    {
        $tenantId = $this->option('tenant');

        if (!$tenantId) {
            $tenants = PropertyConfigVersion::distinct()->pluck('tenant_id');
            foreach ($tenants as $t) {
                $this->warmTenant($t, $registry);
            }
            return 0;
        }

        return $this->warmTenant($tenantId, $registry);
    }

    private function warmTenant(string $tenantId, ActiveConfigRegistry $registry): int
    {
        $this->line("🔥 Warming cache for tenant: [{$tenantId}]");

        try {
            // Temporarily set tenant context if needed, but registry usually takes tenant from config or args.
            // Since Registry resolves tenant from config('app.tenant_id'), we might need to mock or force it if running in console for specific tenant.
            // However, ActiveConfigRegistry::getActiveVersion uses config().
            // For CLI tool correctness, we might need a way to pass tenant to registry or set config.

            config(['app.tenant_id' => $tenantId]); // Hack for CLI context

            // Force reload
            $registry->clear();
            $version = $registry->getActiveVersion();

            $this->info("✅ Warmed: {$version->version_hash} (Signature: " . substr($version->signature, 0, 8) . "...)");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Failed to warm tenant [{$tenantId}]: " . $e->getMessage());
            return 1;
        }
    }
}
