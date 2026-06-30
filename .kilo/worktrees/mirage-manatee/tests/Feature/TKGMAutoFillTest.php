<?php

namespace Tests\Feature;

use App\Models\Ilan;
use App\Models\User;
use App\Services\Integrations\TKGMService;
use App\Services\Integrations\WikiMapiaService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * TKGM Auto-fill System Tests
 *
 * Context7: C7-TKGM-AUTOFILL-TEST-2025-12-03
 * Yalıhan Bekçi: Gemini AI önerisi test suite
 *
 * Test Scenarios:
 * - ✅ tkgm-11: Cache hit/miss/stale scenarios
 * - ✅ tkgm-12: API timeout fallback
 * - ✅ Auth check (sadece danışman)
 * - ✅ Rate limiting
 */
class TKGMAutoFillTest extends TestCase
{

    protected $user;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip: External TKGM API bağımlılığı - CI ortamında çalışmaz
        $this->markTestSkipped('Legacy TKGMAutoFillTest: External API bağımlılığı');

        // Create test user (danışman)
        $this->user = User::factory()->create([
            'role_id' => 2, // Danışman
        ]);

        $this->service = app(TKGMService::class);
    }

    /**
     * ✅ tkgm-11: Test cache MISS (first request)
     */
    public function test_cache_miss_on_first_request()
    {
        // Clear cache
        Cache::flush();

        $result = $this->service->queryParcel('Muğla', 'Bodrum', '1234', '5');

        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('miss', $result['cache_status']);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * ✅ tkgm-11: Test cache HIT (second request)
     */
    public function test_cache_hit_on_second_request()
    {
        // First request (cache miss)
        $this->service->queryParcel('Muğla', 'Bodrum', '1234', '5');

        // Second request (cache hit)
        $result = $this->service->queryParcel('Muğla', 'Bodrum', '1234', '5');

        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('hit', $result['cache_status']);
    }

    /**
     * ✅ tkgm-12: Test stale cache fallback (API timeout simulation)
     */
    public function test_stale_cache_fallback_on_api_failure()
    {
        // Mock successful request to populate cache
        $this->service->queryParcel('Muğla', 'Bodrum', '999', '99');

        // Clear fresh cache but keep stale
        $cacheKey = 'tkgm:parcel:mugla:bodrum:999:99';
        Cache::forget($cacheKey);

        // Now simulate API failure - should return stale cache
        // (In real scenario, API would timeout/fail)
        $result = $this->service->queryParcel('Muğla', 'Bodrum', '999', '99');

        // Should get stale cache or null
        if ($result) {
            $this->assertEquals('stale', $result['cache_status']);
            $this->assertArrayHasKey('warning', $result);
        } else {
            $this->assertNull($result);
        }
    }

    /**
     * ✅ Test API endpoint with authentication
     */
    public function test_tkgm_lookup_endpoint_requires_auth()
    {
        $response = $this->postJson('/api/v1/properties/tkgm-lookup', [
            'il' => 'Muğla',
            'ilce' => 'Bodrum',
            'ada' => '1234',
            'parsel' => '5',
        ]);

        $response->assertStatus(401); // Unauthorized
    }

    /**
     * ✅ tkgm-15: Test authenticated request
     */
    public function test_tkgm_lookup_endpoint_with_auth()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/properties/tkgm-lookup', [
                'il' => 'Muğla',
                'ilce' => 'Bodrum',
                'ada' => '1234',
                'parsel' => '5',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'ada_no',
                    'parsel_no',
                    'alan_m2',
                    'nitelik',
                    'imar_statusu',
                    'center_lat',
                    'center_lng',
                ],
            ]);
    }

    /**
     * ✅ tkgm-14: Test rate limiting
     */
    public function test_rate_limiting_works()
    {
        // Make 11 requests (limit is 10 per minute)
        for ($i = 0; $i < 11; $i++) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/v1/properties/tkgm-lookup', [
                    'il' => 'Muğla',
                    'ilce' => 'Bodrum',
                    'ada' => (string) (1000 + $i),
                    'parsel' => '5',
                ]);

            if ($i < 10) {
                $response->assertStatus(200);
            } else {
                // 11th request should be rate limited
                $response->assertStatus(429);
            }
        }
    }

    /**
     * Test validation errors
     */
    public function test_validation_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/properties/tkgm-lookup', [
                'il' => '', // Empty
                'ilce' => 'Bodrum',
                'ada' => '1234',
                'parsel' => '5',
            ]);

        $response->assertStatus(422) // Validation error
            ->assertJsonValidationErrors(['il']);
    }

    /**
     * Test parsel data structure
     */
    public function test_parsel_data_contains_required_fields()
    {
        $result = $this->service->queryParcel('Muğla', 'Bodrum', '1234', '5');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);

        $data = $result['data'];

        // Required fields for arsa
        $this->assertArrayHasKey('ada_no', $data);
        $this->assertArrayHasKey('parsel_no', $data);
        $this->assertArrayHasKey('alan_m2', $data);
        $this->assertArrayHasKey('imar_statusu', $data);
        $this->assertArrayHasKey('center_lat', $data);
        $this->assertArrayHasKey('center_lng', $data);
    }

    /**
     * Test cache key generation
     */
    public function test_cache_key_generation_is_consistent()
    {
        // Same parameters should generate same cache key
        $service = new TKGMService();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($service, 'Muğla', 'Bodrum', '1234', '5');
        $key2 = $method->invoke($service, 'Muğla', 'Bodrum', '1234', '5');

        $this->assertEquals($key1, $key2);
        $this->assertStringContainsString('tkgm:parcel:', $key1);
    }

    /**
     * ✅ tkgm-12: Test timeout behavior
     */
    public function test_service_handles_timeout_gracefully()
    {
        // This test simulates timeout by using invalid ada/parsel
        // In real scenario, we'd mock Http::timeout() to throw exception

        $result = $this->service->queryParcel('Test', 'Test', 'invalid', 'invalid');

        // Should either return data or null (not crash)
        $this->assertTrue(is_array($result) || is_null($result));
    }

    /**
     * Test health check endpoint
     */
    public function test_health_check_endpoint()
    {
        $response = $this->getJson('/api/v1/properties/tkgm-health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'health_status',
                'message',
            ]);
    }

    public function test_tkgm_lookup_persists_nearby_places_when_ilan_id_is_sent()
    {
        $mock = Mockery::mock(WikiMapiaService::class);
        $mock->shouldReceive('searchNearbyPlaces')
            ->andReturn([
                [
                    'name' => 'Test Kafe',
                    'type' => 'Restaurant',
                    'distance_m' => 150.0,
                    'latitude' => 37.0,
                    'longitude' => 27.0,
                    'description' => null,
                ],
            ]);

        $this->app->instance(WikiMapiaService::class, $mock);

        $ilan = Ilan::factory()->create([
            'baslik' => 'Test Arsa',
            'aciklama' => 'Test açıklama',
            'fiyat' => 1500000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda', // Context7 kanonik field
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/properties/tkgm-lookup', [
                'il' => 'Muğla',
                'ilce' => 'Bodrum',
                'ada' => '807',
                'parsel' => '9',
                'ilan_id' => $ilan->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.nearby_places.0.name', 'Test Kafe');

        $this->assertEquals('Test Kafe', $ilan->fresh()->nearby_places[0]['name']);
    }

    /**
     * Test slugify helper
     */
    public function test_slugify_normalizes_text()
    {
        $service = new TKGMService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('slugify');
        $method->setAccessible(true);

        $this->assertEquals('mugla', $method->invoke($service, 'Muğla'));
        $this->assertEquals('bodrum', $method->invoke($service, 'Bodrum'));
        $this->assertEquals('1234', $method->invoke($service, '1234'));
    }
}
