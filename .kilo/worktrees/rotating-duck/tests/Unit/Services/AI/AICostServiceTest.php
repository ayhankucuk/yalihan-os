<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\AICostService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AICostServiceTest extends TestCase
{
    protected AICostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AICostService();

        // Mock config
        Config::set('services.ai.daily_limit_usd', 10.0);
        Config::set('services.ai.hourly_limit_usd', 2.0);
        Config::set('services.ai.hard_cap_aktif', true);
    }

    public function test_it_tracks_usage_correctly()
    {
        // Mock get calls for Hard Cap check (called after increment)
        Cache::shouldReceive('get')->andReturn(5000); // Return value below limit

        Cache::shouldReceive('increment')
            ->twice() // Once for daily, once for hourly
            ->andReturn(5000); // 0.50 USD

        $stats = $this->service->recordUsage('user_1', 0.50);

        $this->assertEquals(0.50, $stats['gunluk_kullanim_usd']);
        $this->assertEquals(0.50, $stats['saatlik_kullanim_usd']);
    }

    public function test_check_limits_returns_true_when_under_limit()
    {
        // 1.0 USD usage (Under both 10 USD daily and 2 USD hourly limits)
        Cache::shouldReceive('get')->andReturn(10000); // 1.0 USD

        $this->assertTrue($this->service->checkLimits('user_1'));
    }

    public function test_check_limits_returns_false_when_daily_limit_exceeded()
    {
        // 11 USD usage (Over 10 USD limit)
        Cache::shouldReceive('get')
             ->withArgs(function($key) {
                 return str_contains($key, 'gunluk');
             })
             ->andReturn(110000);

        Cache::shouldReceive('get')
             ->withArgs(function($key) {
                 return str_contains($key, 'saatlik');
             })
             ->andReturn(100);

        $this->assertFalse($this->service->checkLimits('user_1'));
    }

    public function test_context7_compliant_keys()
    {
        // Reflection to test private method or just rely on implementation
        // Here we test strictly the resulting increment calls

        // Mock get calls for Hard Cap check
        Cache::shouldReceive('get')->andReturn(1000); // Under limit

        Cache::shouldReceive('increment')
            ->withArgs(function($key) {
                return str_contains($key, 'ai_maliyet_sayaci:gunluk') && !str_contains($key, 'daily_usage');
            })
            ->once()
            ->andReturn(1000); // Return incremented value

        Cache::shouldReceive('increment')
            ->withArgs(function($key) {
                return str_contains($key, 'ai_maliyet_sayaci:saatlik');
            })
            ->once()
            ->andReturn(1000); // Return incremented value

        $this->service->recordUsage('user_test', 0.10);
    }
}
