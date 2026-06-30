<?php

namespace Tests\Unit;

use App\Services\AI\AiBudgetGuard;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AiBudgetGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    /** @test */
    public function it_checks_soft_cap_correctly()
    {
        $guard = new AiBudgetGuard();

        // Feature: ups_template_generate (50k budget, 80% soft cap = 40k)
        $result = $guard->checkSoftCap('ups_template_generate', 0);

        $this->assertEquals('ups_template_generate', $result['feature']);
        $this->assertEquals(50_000, $result['daily_budget']);
        $this->assertEquals(40_000, $result['soft_cap']);
        $this->assertEquals(0, $result['used']);
        $this->assertFalse($result['soft_cap_exceeded']);
    }

    /** @test */
    public function it_detects_soft_cap_exceeded()
    {
        $guard = new AiBudgetGuard();

        // Simulate 39k tokens already used
        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t0:ups_template_generate:{$dateKey}";
        Cache::put($cacheKey, 39_000, now()->addHours(25));

        // Check with 2k more tokens (total 41k > 40k soft cap)
        $result = $guard->checkSoftCap('ups_template_generate', 2_000);

        $this->assertEquals(39_000, $result['used']);
        $this->assertEquals(41_000, $result['next_used']);
        $this->assertTrue($result['soft_cap_exceeded']);
    }

    /** @test */
    public function it_commits_token_usage_to_cache()
    {
        $guard = new AiBudgetGuard();

        // Commit 1000 tokens
        $guard->commit('wizard_storytelling', 1_000);

        $dateKey = now()->format('Y-m-d');
        $cacheKey = "ai:budget:t0:wizard_storytelling:{$dateKey}";

        $this->assertEquals(1_000, Cache::get($cacheKey));

        // Commit 500 more
        $guard->commit('wizard_storytelling', 500);

        $this->assertEquals(1_500, Cache::get($cacheKey));
    }

    /** @test */
    public function it_merges_default_and_feature_config()
    {
        $guard = new AiBudgetGuard();

        // Test with a feature that doesn't exist (should use defaults)
        $result = $guard->checkSoftCap('unknown_feature', 0);

        $this->assertEquals(200_000, $result['daily_budget']); // default
        $this->assertEquals(160_000, $result['soft_cap']); // 200k * 0.80
    }
}
