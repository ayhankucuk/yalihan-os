<?php

declare(strict_types=1);

namespace App\Console\Commands\SaaS;

use App\Models\SaaS\FeatureFlag;
use App\Services\SaaS\FeatureFlagService;
use Illuminate\Console\Command;

class FeatureFlagCommand extends Command
{
    protected $signature = 'feature:toggle 
                            {flag? : The key of the feature flag} 
                            {--enable : Enable the feature flag} 
                            {--disable : Disable the feature flag} 
                            {--list : List all feature flags}';

    protected $description = 'Manage and toggle SaaS Feature Flags';

    public function handle(FeatureFlagService $flagService): int
    {
        if ($this->option('list') || !$this->argument('flag')) {
            return $this->listFlags();
        }

        $flagKey = $this->argument('flag');
        $enable = $this->option('enable');
        $disable = $this->option('disable');

        if (!$enable && !$disable) {
            $this->error('❌ You must specify either --enable or --disable option.');
            return 1;
        }

        if ($enable) {
            $flagService->enable($flagKey);
            $this->info("✅ Feature flag [{$flagKey}] has been ENABLED.");
        } else {
            $flagService->disable($flagKey);
            $this->info("✅ Feature flag [{$flagKey}] has been DISABLED.");
        }

        return 0;
    }

    protected function listFlags(): int
    {
        $flags = FeatureFlag::all();

        if ($flags->isEmpty()) {
            $this->info('ℹ️ No feature flags registered in the system.');
            return 0;
        }

        $this->info('🚩 Registered Feature Flags:');
        
        $headers = ['ID', 'Key', 'Status', 'Rules', 'Description'];
        $rows = [];

        foreach ($flags as $flag) {
            $rows[] = [
                $flag->id,
                $flag->key,
                $flag->is_enabled ? '🟢 ENABLED' : '🔴 DISABLED',
                json_encode($flag->rules),
                $flag->description ?? '-',
            ];
        }

        $this->table($headers, $rows);
        return 0;
    }
}
