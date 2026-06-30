<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\AICostService;
use App\Exceptions\AI\AIHardCapException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Mockery;

class AIHardCapTest extends TestCase
{
    protected AICostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AICostService();

        Config::set('services.ai.daily_limit_usd', 10.0);
        Config::set('services.ai.hourly_limit_usd', 10.0);
        Config::set('services.ai.hard_cap_aktif', true);
    }

    public function test_normal_flow_under_limit()
    {
        Cache::shouldReceive('get')->andReturn(50000); // 5 USD
        Cache::shouldReceive('increment')->twice()->andReturn(51000);

        // Soft cap checks etc mocked implicitly or ignored if no event assertion

        $result = $this->service->recordUsage('user_1', 0.10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('gunluk_kullanim_usd', $result);
    }

    public function test_hard_cap_throws_exception()
    {
        $this->expectException(AIHardCapException::class);

        // Simulate Limit Reached AFTER increment
        Cache::shouldReceive('increment')
             ->twice()
             ->andReturn(100000); // 10 USD (at limit)

        Cache::shouldReceive('get')
             ->andReturn(100000, 0); // Daily at limit, hourly ok

        $this->service->recordUsage('user_1', 0.10);
    }

    public function test_hard_cap_check_method_returns_false_when_exceeded()
    {
        Cache::shouldReceive('get')
             ->withArgs(function($key) { return str_contains($key, 'gunluk'); })
             ->andReturn(110000); // 11 USD

        Cache::shouldReceive('get')->andReturn(0);

        $this->assertFalse($this->service->checkLimits('user_1'));
    }
}
