<?php

namespace Tests\Support;

use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use Illuminate\Support\Facades\Cache;

trait ResetsGovernanceState
{
    /**
     * @before
     */
    public function resetGovernance(): void
    {
        try {
            // 🚨 ZERO-TRUST Isolation: Force absolute state reset
            // 1. Clear Cache via Facade (Safest in tests)
            if (interface_exists(\Illuminate\Support\Facades\Cache::class)) {
                Cache::flush();
                Cache::forget('governance.system_compromised');
                Cache::forget('governance.active_version');
            }

            $container = \Illuminate\Container\Container::getInstance();
            if ($container) {
                // 2. Clear Singleton
                if ($container->bound(ActiveConfigRegistry::class)) {
                    $registry = $container->make(ActiveConfigRegistry::class);
                    if (method_exists($registry, 'reset')) {
                        $registry->reset();
                    }
                }
                $container->forgetInstance(ActiveConfigRegistry::class);
            }

            // 3. Clear Static state in registry
            if (class_exists(ActiveConfigRegistry::class)) {
                ActiveConfigRegistry::clearStaticState();
            }
        } catch (\Throwable $e) {
            // Silence
        }
    }
}
