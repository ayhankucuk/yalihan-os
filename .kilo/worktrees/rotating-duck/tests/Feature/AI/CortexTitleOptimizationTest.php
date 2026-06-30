<?php

namespace Tests\Feature\AI;

use App\Models\IlanKategori;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\User;
use Tests\TestCase;

class CortexTitleOptimizationTest extends TestCase
{

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    /** @test */
    public function it_can_optimize_listing_title_via_api()
    {
        $kat = IlanKategori::factory()->create(['name' => 'Villa']);

        $payload = [
            'baslik' => 'Satılık ev',
            'ana_kategori_id' => $kat->id,
            'il_id' => 48, // Direct ID simulation
            'ilce_id' => 1,
            'ozellik_ids' => []
        ];

        // Mock YalihanCortex since we don't want real AI requests in Feature tests
        $cortexMock = \Mockery::mock(\App\Services\AI\YalihanCortex::class);
        $cortexMock->shouldReceive('optimizeIlanTitle')
            ->once()
            ->with($payload)
            ->andReturn([
                'success' => true,
                'original_title' => 'Satılık ev',
                'optimized_title' => 'Muğla Menteşe\'de Satılık Lüks Villa Kesinlikle Kaçırmayın',
                'improvements' => [
                    'seo_score' => 85,
                    'click_potential' => 90,
                    'execution_time_ms' => 150
                ]
            ]);
        $this->app->instance(\App\Services\AI\YalihanCortex::class, $cortexMock);

        // 2. Request
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/ai/optimize-title', $payload);

        // 3. Verify — Phase 36 contract: { success, data, meta, error }
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'original_title',
            'optimized_title',
            'improvements' => [
                'seo_score',
                'click_potential',
                'keywords_found'
            ]
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['optimized_title']);
        $this->assertGreaterThanOrEqual(1, $data['improvements']['click_potential']);
    }
}
