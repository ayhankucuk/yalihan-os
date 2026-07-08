<?php

namespace Tests\Feature\Api;

use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Models\User;
use App\Models\SaaS\Tenant;
use App\Services\Media\MediaIntelligenceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Sprint 6.3 — Media Intelligence API Contract Tests
 *
 * API Contract: success | data | meta | error
 * Routes:
 *   POST /api/media/analyze  → api.media.analyze
 *   GET  /api/media/score/{ilanId}  → api.media.score
 */
class MediaIntelligenceApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['id' => 9001, 'name' => 'Test Tenant', 'aktiflik_durumu' => 1]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ─── POST /api/media/analyze ────────────────────────────────────────

    public function test_analyze_returns_success_contract_with_photo_data(): void
    {
        Sanctum::actingAs($this->user);

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Test İlan',
        ]);

        IlanFotografi::create([
            'ilan_id' => $ilan->id,
            'dosya_adi' => 'photo1.jpg',
            'dosya_yolu' => '/test/photo1.jpg',
            'url' => 'https://example.com/photo1.jpg',
            'sira' => 1,
        ]);

        $response = $this->postJson(route('api.media.analyze'), [
            'ilan_id' => $ilan->id,
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('error', $json);
        $this->assertTrue($json['success']);
        $this->assertNull($json['error']);
        $this->assertArrayHasKey('timestamp', $json['meta']);
        $this->assertNotEmpty($json['meta']['timestamp']);

        // Data assertions
        $this->assertEquals($ilan->id, $json['data']['ilan_id']);
        $this->assertArrayHasKey('toplam_fotograf', $json['data']);
        $this->assertArrayHasKey('media_health_score', $json['data']);
        $this->assertArrayHasKey('hero_fotograf_id', $json['data']);
        $this->assertArrayHasKey('eksik_odalar', $json['data']);
    }

    public function test_analyze_returns_404_when_ilan_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.media.analyze'), [
            'ilan_id' => 99999,
        ]);

        $response->assertStatus(404);
        $json = $response->json();

        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertNotNull($json['error']);
        $this->assertEquals('ilan_not_found', $json['error']['code']);
    }

    public function test_analyze_returns_422_when_ilan_id_missing(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.media.analyze'), []);

        // Validation failures return 422 with Laravel default structure
        $response->assertStatus(422);
    }

    public function test_analyze_async_dispatches_job_and_returns_202(): void
    {
        Sanctum::actingAs($this->user);

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Async Test İlan',
        ]);

        $response = $this->postJson(route('api.media.analyze'), [
            'ilan_id' => $ilan->id,
            'async' => true,
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals('queued', $json['data']['status']);
        $this->assertEquals($ilan->id, $json['data']['ilan_id']);
    }

    public function test_analyze_returns_error_contract_on_exception(): void
    {
        Sanctum::actingAs($this->user);

        // Mock the engine to throw
        $this->partialMock(MediaIntelligenceEngine::class, function ($mock) {
            $mock->shouldReceive('analyze')
                ->andThrow(new \RuntimeException('Engine failure'));
        });

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->postJson(route('api.media.analyze'), [
            'ilan_id' => $ilan->id,
        ]);

        $response->assertStatus(500);
        $json = $response->json();

        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertEquals('analyze_failed', $json['error']['code']);
    }

    // ─── GET /api/media/score/{ilanId} ───────────────────────────────────

    public function test_score_returns_success_contract_with_health_data(): void
    {
        Sanctum::actingAs($this->user);

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'media_health_score' => 75,
            'media_quality_score' => 68,
            'eksik_odalar' => ['pool', 'view'],
        ]);

        IlanFotografi::create([
            'ilan_id' => $ilan->id,
            'dosya_adi' => 'photo1.jpg',
            'dosya_yolu' => '/test/photo1.jpg',
            'url' => 'https://example.com/photo1.jpg',
            'sira' => 1,
        ]);

        $response = $this->getJson(route('api.media.score', ['ilanId' => $ilan->id]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertNull($json['error']);
        $this->assertEquals($ilan->id, $json['data']['ilan_id']);
        $this->assertEquals(75, $json['data']['media_health_score']);
        $this->assertEquals('GOOD', $json['data']['health']);
        $this->assertEquals(68, $json['data']['quality_score']);
        $this->assertEquals(1, $json['data']['total_photos']);
        $this->assertEquals(['pool', 'view'], $json['data']['missing_rooms']);
    }

    public function test_score_returns_excellent_label_at_80(): void
    {
        Sanctum::actingAs($this->user);

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'media_health_score' => 95,
        ]);

        $response = $this->getJson(route('api.media.score', ['ilanId' => $ilan->id]));

        $response->assertStatus(200);
        $this->assertEquals('EXCELLENT', $response->json('data.health'));
    }

    public function test_score_returns_poor_label_at_20(): void
    {
        Sanctum::actingAs($this->user);

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'media_health_score' => 20,
        ]);

        $response = $this->getJson(route('api.media.score', ['ilanId' => $ilan->id]));

        $response->assertStatus(200);
        $this->assertEquals('POOR', $response->json('data.health'));
    }

    public function test_score_returns_missing_label_when_null(): void
    {
        Sanctum::actingAs($this->user);

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'media_health_score' => null,
        ]);

        $response = $this->getJson(route('api.media.score', ['ilanId' => $ilan->id]));

        $response->assertStatus(200);
        $this->assertEquals('MISSING', $response->json('data.health'));
    }

    public function test_score_returns_404_when_ilan_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.media.score', ['ilanId' => 99999]));

        $response->assertStatus(404);
        $json = $response->json();

        $this->assertFalse($json['success']);
        $this->assertEquals('ilan_not_found', $json['error']['code']);
    }

    public function test_score_returns_empty_missing_rooms_when_null(): void
    {
        Sanctum::actingAs($this->user);

        $ilan = Ilan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'media_health_score' => null,
            'eksik_odalar' => null,
        ]);

        $response = $this->getJson(route('api.media.score', ['ilanId' => $ilan->id]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals([], $json['data']['missing_rooms']);
    }
}
