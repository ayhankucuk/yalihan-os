<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Kernel;
use Tests\TestCase;

/**
 * Regression test: EnsureDanismanOwnership middleware alias was removed.
 *
 * CF-2026-10-09-005: Kernel.php referenced EnsureDanismanOwnership middleware
 * but the class file was untracked in git. To eliminate the deployment risk
 * of a clean checkout causing ClassNotFoundException, the alias was removed.
 *
 * The middleware was never used by any route, so removing the alias has
 * zero impact on existing authorization behavior.
 */
class DanismanOwnershipAliasRemovedTest extends TestCase
{
    /**
     * Verify the danisman.ownership alias is NOT registered in the Kernel.
     * Requires Laravel bootstrapped (extends TestCase).
     *
     * This prevents a ClassNotFoundException on clean deployment.
     * The EnsureDanismanOwnership class was untracked in git while the
     * Kernel.php alias registration was committed (fb1f7230).
     *
     * @see CF-2026-10-09-005
     */
    protected function getMiddlewareAliases(): array
    {
        $kernel = app(Kernel::class);
        $reflection = new \ReflectionClass($kernel);
        $prop = $reflection->getProperty('middlewareAliases');
        $prop->setAccessible(true);
        return $prop->getValue($kernel);
    }

    /**
     * Verify the danisman.ownership alias is NOT registered in the Kernel.
     * Requires Laravel bootstrapped (extends TestCase).
     *
     * This prevents a ClassNotFoundException on clean deployment.
     * The EnsureDanismanOwnership class was untracked in git while the
     * Kernel.php alias registration was committed (fb1f7230).
     *
     * @see CF-2026-10-09-005
     */
    public function test_danisman_ownership_alias_not_registered(): void
    {
        $aliases = $this->getMiddlewareAliases();

        $this->assertArrayNotHasKey(
            'danisman.ownership',
            $aliases,
            'danisman.ownership alias must not exist — class EnsureDanismanOwnership was untracked in git and must not be referenced in Kernel.php'
        );
    }

    /**
     * Verify the EnsureDanismanOwnership class is not referenced in any middleware alias.
     */
    public function test_ensure_danisman_ownership_class_not_in_aliases(): void
    {
        $aliases = $this->getMiddlewareAliases();

        foreach ($aliases as $alias => $class) {
            $this->assertStringNotContainsString(
                'EnsureDanismanOwnership',
                $class,
                "Alias '{$alias}' references EnsureDanismanOwnership class which is untracked in git"
            );
        }
    }
}
