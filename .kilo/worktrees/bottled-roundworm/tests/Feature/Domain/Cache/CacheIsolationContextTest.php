<?php

namespace Tests\Feature\Domain\Cache;

use Tests\TestCase;
use App\Domain\Core\Cache\CacheIsolationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Database\Seeders\TenantBaselineSeeder;

/**
 * Class CacheIsolationContextTest
 * @package Tests\Feature\Domain\Cache
 * @description Phase 18: Multi-tenant cache isolation and probabilistic XFetch (Cache Stampede Barrier) tests.
 */
class CacheIsolationContextTest extends TestCase
{
    use RefreshDatabase;

    private CacheIsolationContext $context;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TenantBaselineSeeder::class);
        $this->context = new CacheIsolationContext();
    }

    /**
     * @test
     */
    public function it_generates_isolated_canonical_keys_per_tenant(): void
    {
        $keyTenant1 = $this->context->generateIsolatedKey(1, 'ilan_detay_501');
        $keyTenant2 = $this->context->generateIsolatedKey(2, 'ilan_detay_501');

        $this->assertEquals('{tenant:1}:hub:ilan_detay_501', $keyTenant1);
        $this->assertEquals('{tenant:2}:hub:ilan_detay_501', $keyTenant2);
        $this->assertNotEquals($keyTenant1, $keyTenant2);
    }

    /**
     * @test
     */
    public function it_returns_false_on_empty_cache_or_invalid_format(): void
    {
        $isolatedKey = $this->context->generateIsolatedKey($this->tenantId, 'empty_key');

        // Case 1: Key not in cache
        $this->assertFalse($this->context->shouldEarlyRefresh($isolatedKey, 3600));

        // Case 2: Key stored in raw format (no xfetch array metadata)
        Cache::put($isolatedKey, 'raw_data_value', 3600);
        $this->assertFalse($this->context->shouldEarlyRefresh($isolatedKey, 3600));
    }

    /**
     * @test
     */
    public function it_does_not_trigger_early_refresh_when_ttl_is_large_and_computation_is_fast(): void
    {
        $isolatedKey = $this->context->generateIsolatedKey($this->tenantId, 'fast_key');

        // Store with extremely small compute time delta (e.g. 0.0001 seconds) and long expiry time (1 hour from now)
        Cache::put($isolatedKey, [
            'value' => 'some_cached_listing_details',
            'delta' => 0.0001,
            'expires_at' => microtime(true) + 3600
        ], 3600);

        // Under 1 hour remaining TTL, a fast 0.0001s computation should NOT trigger early refresh
        $this->assertFalse($this->context->shouldEarlyRefresh($isolatedKey, 3600));
    }

    /**
     * @test
     */
    public function it_triggers_early_refresh_probabilistically_when_getting_close_to_expiry_and_computation_is_slow(): void
    {
        $isolatedKey = $this->context->generateIsolatedKey($this->tenantId, 'slow_key');

        // Store with extremely slow compute time delta (1000 seconds) and almost expired remaining TTL (0.01 seconds)
        Cache::put($isolatedKey, [
            'value' => 'some_expensive_roi_analysis_report',
            'delta' => 1000.0,
            'expires_at' => microtime(true) + 0.01
        ], 3600);

        // A slow 1000s computation with a near-zero remaining TTL should trigger early refresh
        $this->assertTrue($this->context->shouldEarlyRefresh($isolatedKey, 3600));
    }
}
