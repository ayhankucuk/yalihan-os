<?php

namespace Tests\Unit;

use App\Services\AI\OpenAIService;
use App\Services\AI\AiBudgetGuard;
use App\Exceptions\AiBudgetExceededException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Tests\AiGuardTestCase;
use Mockery;

/**
 * OpenAIService Hard Cap Interception Test
 */
class OpenAIServiceHardCapTest extends AiGuardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
        Cache::flush();

        // Mock services config
        Config::set('services.openai', [
            'api_key' => 'test-key',
            'base_url' => 'https://api.openai.com/v1',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_does_not_call_openai_api_when_hard_cap_exceeded()
    {
        // 1. Mock BudgetGuard to throw exception
        /** @var AiBudgetGuard|\Mockery\MockInterface $mockGuard */
        $mockGuard = Mockery::mock(AiBudgetGuard::class);
        $mockGuard->shouldReceive('checkSoftCap')
            ->once()
            ->andReturnUsing(fn () => ['soft_cap_exceeded' => false]);

        $mockGuard->shouldReceive('checkHardCap')
            ->once()
            ->andThrow(new AiBudgetExceededException(
                'test_feature', 50001, 50000, 55000, '2026-01-01 00:00:00'
            ));

        $this->app->instance(AiBudgetGuard::class, $mockGuard);

        // 2. Fake HTTP to ensure no requests use the network
        // and to verify if any request WAS made
        Http::fake();

        // 3. Instantiate Service with mocked dependencies
        $mockTelemetry = Mockery::mock(\App\Services\AI\Monitoring\AiTelemetryService::class);
        $mockCircuitBreaker = Mockery::mock(\App\Contracts\Resilience\CircuitBreakerInterface::class);
        $mockCircuitBreaker->shouldReceive('isOpen')->andReturn(false)->byDefault();
        $mockCircuitBreaker->shouldReceive('recordFailure')->byDefault();
        $mockCircuitBreaker->shouldReceive('recordSuccess')->byDefault();
        $mockSettingService = Mockery::mock(\App\Services\SettingService::class);
        $mockSettingService->shouldReceive('get')->andReturn(null)->byDefault();
        $service = new OpenAIService($mockTelemetry, $mockCircuitBreaker, $mockGuard, $mockSettingService);

        // 4. Expect Exception to propagate
        $this->expectException(AiBudgetExceededException::class);

        // 5. Call chat method
        try {
            $service->chat(
                messages: [['role' => 'user', 'content' => 'Hello']],
                featureKey: 'test_feature'
            );
        } catch (AiBudgetExceededException $e) {
            // 6. Verify HTTP was NOT called
            Http::assertNothingSent();
            throw $e;
        }
    }
}
