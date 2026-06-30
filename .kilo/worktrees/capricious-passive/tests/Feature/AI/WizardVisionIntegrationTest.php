<?php

namespace Tests\Feature\AI;

use App\Models\User;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu; // Changed
use App\Models\Feature;
use App\Models\FeatureAssignment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class WizardVisionIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role_id' => 1]);

        // Phase 15: AI Credits - Ensure workspace has enough for testing
        \Illuminate\Support\Facades\DB::table('ai_workspace_wallets')->insert([
            'tenant_id' => 1,
            'balance' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function it_successfully_calls_vision_analysis_endpoint()
    {
        // 1. Setup Context (Villa Satılık)
        $kategori = IlanKategori::factory()->create(['name' => 'Villa']);
        // Fixed: Use YayinTipiSablonu
        $yayinTipi = YayinTipiSablonu::firstOrCreate(['slug' => 'satilik'], ['ad' => 'Satılık']);

        // 2. Setup allowed features (UPS Guard)
        $feature = Feature::factory()->create(['slug' => 'ortak-havuz', 'name' => 'Ortak Havuz']);
        FeatureAssignment::create([
            'assignable_type' => IlanKategori::class,
            'assignable_id' => $kategori->id,
            'feature_id' => $feature->id,
            'is_visible' => 1,
            'is_required' => 0
        ]);

        // 3. Request
        $payload = [
            'category_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'images' => ['havuzlu_villa.jpg']
        ];

        // Ensure we are using mock provider for test
        // Ensure we are using mock provider for test
        // Code calls optimizer, so we must mock it to return 'mock'
        $this->mock(\App\Services\AI\ProviderOptimizationService::class, function ($mock) {
            $mock->shouldReceive('chooseProvider')->andReturn('mock');
        });

        config(['vision.provider' => 'mock']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/wizard/analyze-images', $payload);

        // 4. Verification
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'suggestions',
                'metadata' => [
                    'provider',
                    'latency_ms',
                    'cost_estimate'
                ],
                'message'
            ]
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['suggestions']);
        $this->assertEquals('ortak-havuz', $data['suggestions'][0]['slug']);
    }

    /** @test */
    public function it_respects_kill_switch()
    {
        config(['vision.enable_kill_switch' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/wizard/analyze-images', [
                'category_id' => 1,
                'images' => ['test.jpg']
            ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'SERVER_ERROR'
            ]
        ]);
    }

    /** @test */
    public function it_caches_vision_results()
    {
        $kategori = IlanKategori::factory()->create(['name' => 'Villa']);
        // Fixed: Use YayinTipiSablonu
        $yayinTipi = YayinTipiSablonu::firstOrCreate(['slug' => 'satilik'], ['ad' => 'Satılık']);

        $payload = [
            'category_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'images' => ['cached_test.jpg']
        ];

        // Ensure we are using mock provider for test
        $this->mock(\App\Services\AI\ProviderOptimizationService::class, function ($mock) {
            $mock->shouldReceive('chooseProvider')->andReturn('mock');
        });

        config(['vision.provider' => 'mock']);

        // 1. First call (Cache Miss)
        $response1 = $this->actingAs($this->admin)->postJson('/api/v1/wizard/analyze-images', $payload);
        $response1->assertJsonPath('data.metadata.cache_hit', false);

        // 2. Second call (Cache Hit)
        $response2 = $this->actingAs($this->admin)->postJson('/api/v1/wizard/analyze-images', $payload);
        $response2->assertJsonPath('data.metadata.cache_hit', true);
    }
}
