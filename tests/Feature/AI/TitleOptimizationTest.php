<?php

namespace Tests\Feature\AI;

use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\AI\Domains\CortexContentService;
use App\Services\AI\YalihanCortex;
use App\Services\AIService;
use Tests\TestCase;

class TitleOptimizationTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['name' => 'Cortex Tenant', 'status' => 'active']);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);

        // Mock AIService to avoid external API calls
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->andReturn('Gümüşlük\'te Deniz Manzaralı Lüks Villa');
        });

        // Forget singletons to force re-resolution with mocked AIService
        $this->app->forgetInstance(YalihanCortex::class);
        $this->app->forgetInstance(CortexContentService::class);
    }

    /** @test */
    public function it_can_optimize_listing_title()
    {
        $payload = [
            'baslik' => 'Satılık Villa',
            'kategori' => 'Konut',
            'lokasyon' => 'Bodrum, Gümüşlük',
            'features' => ['Deniz Manzarası', 'Havuz', 'Müstakil'],
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/cortex/ai/optimize-title', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'success' => true,
                    'optimized_title' => 'Gümüşlük\'te Deniz Manzaralı Lüks Villa',
                ],
            ]);
    }

    /** @test */
    public function it_handles_ai_failure_gracefully()
    {
        // Force AI failure
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->andThrow(new \Exception('AI Service Unavailable'));
        });

        $payload = [
            'baslik' => 'Eski Başlık',
            'kategori' => 'Arsa',
            'lokasyon' => 'Yalıkavak',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/cortex/ai/optimize-title', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'success' => true,
                    'original_title' => 'Eski Başlık',
                    // Fallback title logic: "Lokasyon Kategori - Fırsat İlan"
                    'optimized_title' => 'Yalıkavak Arsa - Fırsat İlan',
                ],
            ]);
    }
}
