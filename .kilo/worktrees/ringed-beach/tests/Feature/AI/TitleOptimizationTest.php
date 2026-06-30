<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Services\AIService;
use Mockery;
use Illuminate\Support\Facades\Log;

class TitleOptimizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock AIService to avoid external API calls
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->andReturn('Gümüşlük\'te Deniz Manzaralı Lüks Villa');
        });
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

        $response = $this->postJson('/api/v1/cortex/ai/optimize-title', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'success' => true,
                    'optimized_title' => 'Gümüşlük\'te Deniz Manzaralı Lüks Villa',
                ]
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

        $response = $this->postJson('/api/v1/cortex/ai/optimize-title', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'success' => true,
                    'original_title' => 'Eski Başlık',
                    // Fallback title logic: "Lokasyon Kategori - Fırsat İlan"
                    'optimized_title' => 'Yalıkavak Arsa - Fırsat İlan',
                ]
            ]);
    }
}
