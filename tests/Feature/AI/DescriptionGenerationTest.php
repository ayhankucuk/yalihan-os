<?php

namespace Tests\Feature\AI;

use App\Models\AiLog;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\AI\OllamaService;
use App\Services\AI\YalihanCortex;
use Mockery\MockInterface;
use Tests\TestCase;

class DescriptionGenerationTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['name' => 'Description Tenant', 'status' => 'active']);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($this->user);
    }

    public function test_description_generation_works_with_valid_input()
    {
        $this->withoutExceptionHandling();

        // Mock OllamaService
        $this->mock(OllamaService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generateDescription')
                ->once()
                ->andReturn("Bu muhteşem villa, Bodrum'un kalbinde yer alıyor. Deniz manzarası ve özel havuzuyla size eşsiz bir yaşam sunuyor.");
        });

        $this->app->forgetInstance(YalihanCortex::class);

        $payload = [
            'kategori' => 'Konut',
            'il' => 'Muğla',
            'ilce' => 'Bodrum',
            'mahalle' => 'Yalıkavak',
            'features' => ['Deniz Manzaralı', 'Havuzlu', '5+1'],
            'tone' => 'luks',
        ];

        $response = $this->postJson('/api/ai/generate-description', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', true)
            ->assertJsonPath('data.description', "Bu muhteşem villa, Bodrum'un kalbinde yer alıyor. Deniz manzarası ve özel havuzuyla size eşsiz bir yaşam sunuyor.");

        // Verify AiLog
        $this->assertDatabaseHas('ai_logs', [
            'endpoint' => 'generate_ilan_description',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_description_generation_handles_empty_response()
    {
        // Mock OllamaService to return empty
        $this->mock(OllamaService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generateDescription')
                ->once()
                ->andReturn('');
        });

        $this->app->forgetInstance(YalihanCortex::class);

        $payload = [
            'kategori' => 'Konut',
            'il' => 'Muğla',
        ];

        $response = $this->postJson('/api/ai/generate-description', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', false);
    }
}
