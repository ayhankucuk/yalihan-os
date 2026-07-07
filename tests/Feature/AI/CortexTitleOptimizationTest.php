<?php

namespace Tests\Feature\AI;

use App\Models\IlanKategori;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\AI\Domains\CortexContentService;
use App\Services\AI\YalihanCortex;
use Tests\TestCase;

/**
 * Cortex Title Optimization Feature Tests
 *
 * Tests the /api/v1/cortex/ai/optimize-title endpoint.
 */
class CortexTitleOptimizationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['name' => 'Cortex Optimize Tenant', 'status' => 'active']);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);

        // Create required kategori record with ID=1 so CortexContentService lookup succeeds
        IlanKategori::factory()->create(['id' => 1, 'name' => 'Villa']);

        // Mock AIService to avoid real AI calls
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->andReturn('Muğla Menteşe\'de Satılık Lüks Villa — Kesinlikle Kaçırmayın');
        });

        // Forget singletons so mocked AIService is used
        $this->app->forgetInstance(YalihanCortex::class);
        $this->app->forgetInstance(CortexContentService::class);
    }

    /** @test */
    public function it_can_optimize_listing_title_via_api()
    {
        $payload = [
            'baslik' => 'Satılık ev',
            'kategori' => 'Villa',
            'ana_kategori_id' => 1,
            'il_id' => 48,
            'ilce_id' => 1,
            'ozellik_ids' => [],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cortex/ai/optimize-title', $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'success',
                'optimized_title',
            ],
            'meta',
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['data']['optimized_title']);
    }
}
