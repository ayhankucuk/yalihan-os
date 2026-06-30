<?php

namespace Tests\Feature\Governance;

use App\Services\PropertyType\LegacyGeneratorGuard;
use App\Services\PropertyType\PropertyTemplateGeneratorService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Legacy Generator Quarantine Tests
 *
 * SAB Kural 6: Legacy uretim hatti olcumsuz calislamaz.
 * - Default: flag kapali, RuntimeException firlat
 * - Acilirsa: telemetry log + allowlist uygulanir
 *
 * @see config/feature-flags.php
 * @see docs/adr/2026-02-22-legacy-generator-quarantine.md
 */
class LegacyGeneratorFlagTest extends TestCase
{
    /** @test */
    public function legacy_generator_is_disabled_by_default()
    {
        Config::set('feature-flags.legacy_generator_enabled', false);

        $guard = $this->makeGuard();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/LegacyGeneratorBlocked/');

        $guard->generate('Konut', 'Satilik', 'Daire');
    }

    /** @test */
    public function legacy_generator_is_disabled_flag_from_config()
    {
        // Default config: APP_LEGACY_GENERATOR_ENABLED not set => false
        Config::set('feature-flags.legacy_generator_enabled', false);

        $guard = $this->makeGuard();

        $this->assertFalse($guard->isEnabled());
    }

    /** @test */
    public function legacy_generator_assertAllowed_throws_when_flag_off()
    {
        Config::set('feature-flags.legacy_generator_enabled', false);

        $channelMock = \Mockery::mock(['warning' => null]);
        Log::shouldReceive('channel')->with('telemetry')->andReturn($channelMock);

        $guard = $this->makeGuard();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/LegacyGeneratorBlocked/');

        $guard->assertAllowed();
    }

    /** @test */
    public function legacy_generator_logs_blocked_event_to_telemetry()
    {
        Config::set('feature-flags.legacy_generator_enabled', false);

        // Log::channel('telemetry')->warning() zincirine izin ver
        Log::shouldReceive('channel')
            ->with('telemetry')
            ->once()
            ->andReturn(\Mockery::mock(['warning' => null]));

        $guard = $this->makeGuard();

        try {
            $guard->assertAllowed();
        } catch (\RuntimeException) {
            // expected — sadece log davranisini test ediyoruz
        }
    }

    /** @test */
    public function legacy_generator_enabled_allows_generate_call()
    {
        Config::set('feature-flags.legacy_generator_enabled', true);
        Config::set('feature-flags.legacy_generator_allowlist', ['routes' => [], 'tenant_ids' => []]);

        // Log kanalini stub'la — test odagi flag + return degeri
        $channelMock = \Mockery::mock(['info' => null, 'warning' => null]);
        Log::shouldReceive('channel')->with('telemetry')->andReturn($channelMock);

        $mockService = $this->createMock(PropertyTemplateGeneratorService::class);
        $mockService->method('generate')->willReturn(['mock' => 'result']);

        $guard = new LegacyGeneratorGuard($mockService);

        $result = $guard->generate('Konut', 'Satilik', 'Daire');

        $this->assertEquals(['mock' => 'result'], $result);
    }

    /** @test */
    public function legacy_generator_enabled_logs_call_and_success()
    {
        Config::set('feature-flags.legacy_generator_enabled', true);
        Config::set('feature-flags.legacy_generator_allowlist', ['routes' => [], 'tenant_ids' => []]);

        $mockService = $this->createMock(PropertyTemplateGeneratorService::class);
        $mockService->method('generate')->willReturn(['result' => true]);

        $guard = new LegacyGeneratorGuard($mockService);

        // Log kanalini spy yap — info 2 kez cagriliyor: call + success
        $channelMock = \Mockery::spy(['info' => null, 'warning' => null]);
        Log::shouldReceive('channel')->with('telemetry')->twice()->andReturn($channelMock);

        $guard->generate('Villa', 'Kiralik', 'Villa', ['trace_id' => 'test-123']);

        // channel 2x cagrildiysa telemetry calisiyor demektir
        $this->assertTrue(true);
    }

    /** @test */
    public function legacy_generator_enabled_logs_fail_on_exception()
    {
        Config::set('feature-flags.legacy_generator_enabled', true);
        Config::set('feature-flags.legacy_generator_allowlist', ['routes' => [], 'tenant_ids' => []]);

        $mockService = $this->createMock(PropertyTemplateGeneratorService::class);
        $mockService->method('generate')->willThrowException(new \RuntimeException('JSON load fail'));

        $channelMock = \Mockery::mock(['info' => null, 'warning' => null]);
        Log::shouldReceive('channel')->with('telemetry')->andReturn($channelMock);

        $guard = new LegacyGeneratorGuard($mockService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON load fail');

        $guard->generate('Tarla', 'Satilik', 'Tarla');
    }

    /** @test */
    public function legacy_generator_route_allowlist_blocks_unlisted_route()
    {
        Config::set('feature-flags.legacy_generator_enabled', true);
        Config::set('feature-flags.legacy_generator_allowlist', [
            'routes'     => ['admin.property-hub.ai-generate'],
            'tenant_ids' => [],
        ]);

        $channelMock = \Mockery::mock(['warning' => null]);
        Log::shouldReceive('channel')->with('telemetry')->andReturn($channelMock);

        $guard = $this->makeGuard();

        // Current request has no route (test context) -> not in allowlist -> blocked
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/route_not_in_allowlist|allowlist/');

        $guard->assertAllowed();
    }

    // -------------------------------------------------------------------------

    private function makeGuard(): LegacyGeneratorGuard
    {
        $mockService = $this->createMock(PropertyTemplateGeneratorService::class);
        return new LegacyGeneratorGuard($mockService);
    }
}
