<?php

namespace Tests\Unit;

use App\Services\AI\AiBudgetGuard;
use App\Exceptions\AiBudgetExceededException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\AiGuardTestCase;

/**
 * Hard Cap Tests - DB Independent (Pure Cache)
 */
class AiBudgetGuardHardCapTest extends AiGuardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
        Cache::flush();

        // Defaults
        Config::set('ai-budgets.defaults', [
            'tokens_per_day' => 200_000,
            'soft_cap_ratio' => 0.80,
            'hard_cap_enabled' => false,
            'hard_cap_ratio' => 1.0,
            'grace_ratio' => 1.1,
            'allow_admin_override' => false,
        ]);

        Config::set('ai-budgets.features.ups_template_generate', [
            'tokens_per_day' => 50_000,
            'soft_cap_ratio' => 0.80,
            'hard_cap_enabled' => false,
        ]);
    }

    /** @test */
    public function it_allows_when_under_hard_cap()
    {
        $guard = new AiBudgetGuard();
        Config::set('ai-budgets.features.ups_template_generate.hard_cap_enabled', true);

        $guard->checkHardCap('ups_template_generate', 1000);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_blocks_when_hard_cap_exceeded_and_no_grace()
    {
        $this->expectException(AiBudgetExceededException::class);

        $guard = new AiBudgetGuard();

        // Enable hard cap AND disable grace window
        Config::set('ai-budgets.features.ups_template_generate.hard_cap_enabled', true);
        Config::set('ai-budgets.features.ups_template_generate.grace_ratio', 1.0);

        // Used = Hard Cap
        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t0:ups_template_generate:{$dateKey}";
        Cache::put($cacheKey, 50_000, now()->addHours(25));

        // Attempt to spend 1 more -> should throw immediately
        $guard->checkHardCap('ups_template_generate', 1);
    }

    /** @test */
    public function it_allows_grace_window_once()
    {
        $guard = new AiBudgetGuard();

        // Enable hard cap (default grace is 1.1)
        Config::set('ai-budgets.features.ups_template_generate.hard_cap_enabled', true);

        // Used > Hard Cap (50k) but < Grace (55k)
        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t0:ups_template_generate:{$dateKey}";
        Cache::put($cacheKey, 50_500, now()->addHours(25));

        // First call: should allow
        $guard->checkHardCap('ups_template_generate', 0);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_blocks_second_grace_attempt()
    {
        $this->expectException(AiBudgetExceededException::class);

        $guard = new AiBudgetGuard();
        Config::set('ai-budgets.features.ups_template_generate.hard_cap_enabled', true);

        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t0:ups_template_generate:{$dateKey}";
        $graceKey = "ai:budget:grace:t0:ups_template_generate:{$dateKey}";

        Cache::put($cacheKey, 50_500, now()->addHours(25));
        Cache::put($graceKey, true, now()->addHours(25)); // Grace used

        $guard->checkHardCap('ups_template_generate', 0);
    }

    /** @test */
    public function it_allows_admin_override_when_both_flags_true()
    {
        $guard = new AiBudgetGuard();
        Config::set('ai-budgets.features.ups_template_generate.hard_cap_enabled', true);
        Config::set('ai-budgets.features.ups_template_generate.allow_admin_override', true); // Config enable

        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t0:ups_template_generate:{$dateKey}";
        Cache::put($cacheKey, 60_000, now()->addHours(25));

        // Admin runtime context -> allow
        $guard->checkHardCap('ups_template_generate', 0, 0, ['isAdmin' => true, 'user_id' => 1]);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_blocks_admin_when_override_disabled()
    {
        $this->expectException(AiBudgetExceededException::class);

        $guard = new AiBudgetGuard();
        Config::set('ai-budgets.features.ups_template_generate.hard_cap_enabled', true);
        Config::set('ai-budgets.features.ups_template_generate.allow_admin_override', false); // Config disable

        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t0:ups_template_generate:{$dateKey}";
        Cache::put($cacheKey, 60_000, now()->addHours(25));

        $guard->checkHardCap('ups_template_generate', 0, 0, ['isAdmin' => true, 'user_id' => 1]);
    }

    /** @test */
    public function it_returns_hard_cap_status()
    {
        $guard = new AiBudgetGuard();
        Config::set('ai-budgets.features.ups_template_generate.hard_cap_enabled', true);

        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t0:ups_template_generate:{$dateKey}";
        Cache::put($cacheKey, 30_000, now()->addHours(25));

        $budgetStatus = $guard->getHardCapStatus('ups_template_generate');

        $this->assertEquals(30_000, $budgetStatus['used']);
        $this->assertEquals(50_000, $budgetStatus['hard_cap']);
        $this->assertEquals(55_000, $budgetStatus['grace_cap']);
        $this->assertTrue($budgetStatus['hard_cap_enabled']);
    }
}
