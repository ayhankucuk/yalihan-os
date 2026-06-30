<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\AICostService;
use App\Events\AI\AISoftCapReached; // Correct namespace
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AISoftCapTest extends TestCase
{
    protected AICostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AICostService();

        // Config: Limit 10, Soft Cap 80% (8.0)
        Config::set('services.ai.daily_limit_usd', 10.0);
        Config::set('services.ai.hourly_limit_usd', 100.0); // High enough to avoid hourly trigger
        Config::set('services.ai.soft_cap_percentage', 80);
        Config::set('services.ai.soft_cap_aktif', true);

        Event::fake([AISoftCapReached::class]);

        // Mock Cache::get for checkLimits (hard cap check)
        Cache::shouldReceive('get')->andReturn(0);
    }

    public function test_no_event_dispatched_under_soft_cap()
    {
        // 7.9 USD usage (79%)
        // Increment calls will be mocked to return values for limit calculations

        Cache::shouldReceive('increment')
            ->twice()
            ->andReturn(79000); // 7.90 USD

        // We also need Cache::add for lock check, but it shouldn't reach there if pct < 80
        // unless implementation checks lock before pct?
        // Based on implementation: if pct >= softCapPct -> triggerSoftCapEvent -> Cache::add
        // So here Cache::add shouldn't be called.

        $this->service->recordUsage('user_1', 7.90);

        Event::assertNotDispatched(AISoftCapReached::class);
    }

    public function test_event_dispatched_at_soft_cap()
    {
        // 8.0 USD usage (80%)
        Cache::shouldReceive('increment')
            ->twice()
            ->andReturn(80000); // 8.00 USD

        // Expect lock creation
        Cache::shouldReceive('add')
            ->once()
            ->withArgs(function($key) {
                return str_contains($key, 'ai_soft_cap_kilidi:gunluk');
            })
            ->andReturn(true); // Lock successful

        $this->service->recordUsage('user_1', 8.00);

        Event::assertDispatched(AISoftCapReached::class, function ($e) {
            return $e->kullanimOrani >= 0.8 && $e->pencere === 'gunluk';
        });
    }

    public function test_event_dispatched_only_once_due_to_lock()
    {
        // 8.5 USD usage (85%)
        Cache::shouldReceive('increment')
            ->times(4) // 2 calls * 2 times (we call recordUsage twice)
            ->andReturn(85000);

        // First Call: Lock succeeds
        // Second Call: Lock fails
        Cache::shouldReceive('add')
            ->twice()
            ->withArgs(function($key) {
                return str_contains($key, 'ai_soft_cap_kilidi');
            })
            ->andReturn(true, false);

        $this->service->recordUsage('user_1', 8.50);
        $this->service->recordUsage('user_1', 8.50);

        // Should dispatch only ONCE
        Event::assertDispatched(AISoftCapReached::class, 1);
    }

    public function test_lock_prevents_duplicate_events()
    {
         // 8.5 USD usage (85%)
        Cache::shouldReceive('increment')
            ->twice()
            ->andReturn(85000);

        // Lock fails (simulating second hit)
        Cache::shouldReceive('add')
            ->once()
            ->andReturn(false);

        $this->service->recordUsage('user_1', 0.10);

        Event::assertNotDispatched(AISoftCapReached::class);
    }
}
