<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\PropertyHub;

use App\Domain\PropertyHub\Resiliency\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * CircuitBreaker Unit Tests — Yeni Resiliency API
 *
 * Shadow API tasfiye edildi. Bu test App\Domain\PropertyHub\Resiliency\CircuitBreaker'ı
 * doğrudan test eder: isAvailable(), report(), check(), reset() + Log::critical() Fail-Fast.
 *
 * @group property-hub-resiliency
 */
class CircuitBreakerTest extends TestCase
{
    private CircuitBreaker $cb;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // errorThreshold=0.5 (50%), windowSeconds=60, bucketSize=60
        $this->cb = new CircuitBreaker(
            errorThreshold: 0.5,
            windowSeconds: 60,
            bucketSize: 60,
        );
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_it_reports_as_available_initially(): void
    {
        $this->assertTrue(
            $this->cb->isAvailable(),
            'Yeni CircuitBreaker varsayılan olarak available (devre kapalı) olmalı.'
        );
    }

    public function test_it_reports_as_available_with_tenant_id(): void
    {
        $this->assertTrue($this->cb->isAvailable('tenant-abc'));
        $this->assertTrue($this->cb->isAvailable('tenant-xyz'));
    }

    public function test_it_trips_on_high_error_rate(): void
    {
        Log::shouldReceive('critical')
            ->once()
            ->with('PropertyHub V3 Circuit Breaker TRIPPED', \Mockery::on(function ($ctx) {
                return $ctx['reason'] === 'error_rate'
                    && $ctx['tenant_id'] === 'GLOBAL';
            }));

        // 10 istek, 6 hata → %60 > threshold %50
        for ($i = 0; $i < 10; $i++) {
            $this->cb->report(success: $i >= 6); // ilk 6 başarısız
        }

        $this->cb->check();

        $this->assertFalse(
            $this->cb->isAvailable(),
            'Yüksek hata oranı sonrası devre açık (unavailable) olmalı.'
        );
    }

    public function test_it_stays_available_below_threshold(): void
    {
        Log::shouldReceive('critical')->never();

        // 10 istek, 4 hata → %40 < threshold %50
        for ($i = 0; $i < 10; $i++) {
            $this->cb->report(success: $i >= 4); // ilk 4 başarısız
        }

        $this->cb->check();

        $this->assertTrue(
            $this->cb->isAvailable(),
            'Eşik altındaki hata oranında devre kapalı (available) kalmalı.'
        );
    }

    public function test_it_trips_per_tenant_independently(): void
    {
        Log::shouldReceive('critical')
            ->once()
            ->with('PropertyHub V3 Circuit Breaker TRIPPED', \Mockery::on(function ($ctx) {
                return $ctx['tenant_id'] === 'tenant-A';
            }));

        // tenant-A: %60 hata → trip
        for ($i = 0; $i < 10; $i++) {
            $this->cb->report(success: $i >= 6, tenantId: 'tenant-A');
        }
        $this->cb->check(tenantId: 'tenant-A');

        // tenant-B: hata yok
        for ($i = 0; $i < 10; $i++) {
            $this->cb->report(success: true, tenantId: 'tenant-B');
        }
        $this->cb->check(tenantId: 'tenant-B');

        $this->assertFalse($this->cb->isAvailable('tenant-A'), 'tenant-A tripped olmalı.');
        $this->assertTrue($this->cb->isAvailable('tenant-B'), 'tenant-B etkilenmemeli.');
    }

    public function test_check_skips_when_already_tripped(): void
    {
        // İlk trip
        Log::shouldReceive('critical')->once();
        for ($i = 0; $i < 10; $i++) {
            $this->cb->report(success: $i >= 6);
        }
        $this->cb->check();
        $this->assertFalse($this->cb->isAvailable());

        // check() tekrar çağırıldığında Log::critical bir daha tetiklenmemeli
        Log::shouldReceive('critical')->never();
        $this->cb->check();
    }

    public function test_it_can_be_reset(): void
    {
        Log::shouldReceive('critical')->once();
        Log::shouldReceive('info')
            ->once()
            ->with('PropertyHub V3 Circuit Breaker manually reset', \Mockery::any());

        // Trip et
        for ($i = 0; $i < 10; $i++) {
            $this->cb->report(success: $i >= 6);
        }
        $this->cb->check();
        $this->assertFalse($this->cb->isAvailable());

        // Reset
        $this->cb->reset();

        $this->assertTrue(
            $this->cb->isAvailable(),
            'Reset sonrası devre tekrar available olmalı.'
        );
    }

    public function test_reset_with_tenant_id(): void
    {
        Log::shouldReceive('critical')->once();
        Log::shouldReceive('info')->once();

        for ($i = 0; $i < 10; $i++) {
            $this->cb->report(success: $i >= 6, tenantId: 'tenant-reset');
        }
        $this->cb->check(tenantId: 'tenant-reset');
        $this->assertFalse($this->cb->isAvailable('tenant-reset'));

        $this->cb->reset('tenant-reset');
        $this->assertTrue($this->cb->isAvailable('tenant-reset'));
    }

    public function test_report_increments_buckets(): void
    {
        // 5 başarılı + 5 başarısız rapor et, bucket key'lerinin
        // Cache'de arttığını dolaylı olarak check() davranışıyla doğrula
        Log::shouldReceive('critical')->once();

        for ($i = 0; $i < 10; $i++) {
            $this->cb->report(success: $i >= 6);
        }

        // %60 hata — trip olmalı
        $this->cb->check();
        $this->assertFalse($this->cb->isAvailable());
    }

    public function test_zero_reports_returns_zero_rate(): void
    {
        // Hiç rapor yokken check() trip etmemeli (0/0 = 0.0 rate)
        Log::shouldReceive('critical')->never();

        $this->cb->check();

        $this->assertTrue(
            $this->cb->isAvailable(),
            'Hiç rapor olmadan devre trip edilmemeli.'
        );
    }
}
