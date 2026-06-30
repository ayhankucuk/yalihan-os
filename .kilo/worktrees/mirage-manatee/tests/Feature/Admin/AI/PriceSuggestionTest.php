<?php

namespace Tests\Feature\Admin\AI;

use Tests\TestCase;
use App\Models\User;
use App\Services\AI\YalihanCortex;
use Mockery;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class PriceSuggestionTest extends TestCase
{

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Database\Eloquent\Model::unguard();

        // Admin rolünü oluştur (Legacy & Spatie compatible table)
        // Use full path to avoid import conflicts if any
        $role = \App\Modules\Auth\Models\Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        // Admin kullanıcısı oluştur ve role_id ata
        $this->user = User::factory()->create([
            'email' => 'admin@yalihan.com',
            'role_id' => $role->id,
        ]);
    }

    /** @test */
    public function it_can_suggest_price_with_smart_features()
    {
        // Mock YalihanCortex
        $mockCortex = Mockery::mock(YalihanCortex::class);
        $mockCortex->shouldReceive('suggestPrice')
            ->once()
            ->andReturn([
                'success' => true,
                'suggestions' => [
                    [
                        'provider' => 'ollama',
                        'value' => 15000000,
                        'currency' => 'TRY',
                        'confidence' => 0.85, // Mocked response includes confidence
                        'piyasa_durumu' => 'Fair',
                    ]
                ],
                'model' => 'llama3'
            ]);

        $this->app->instance(YalihanCortex::class, $mockCortex);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.ai.suggest'), [
                'action' => 'price', // ACTION PARAM IS REQUIRED
                'fiyat' => 14500000,
                'kategori' => 'Konut',
                'metrekare' => 120,
                'il' => 'Bodrum',
                'ilce' => 'Yalıkavak',
                'mahalle' => 'Geriş',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('suggestions.0.value', 15000000)
            ->assertJsonPath('suggestions.0.confidence', 0.85)
            ->assertJsonPath('suggestions.0.piyasa_durumu', 'Fair');
    }

    /** @test */
    public function it_enriches_suggestions_when_smart_features_missing()
    {
        // Mock YalihanCortex returning basic data
        $mockCortex = Mockery::mock(YalihanCortex::class);
        $mockCortex->shouldReceive('suggestPrice')
            ->once()
            ->andReturn([
                'success' => true,
                'suggestions' => [
                    [
                        'provider' => 'ollama',
                        'value' => 20000000, // Significantly higher than input (High piyasa durumu)
                        'currency' => 'TRY'
                        // Missing confidence and piyasa_durumu
                    ]
                ],
                'model' => 'llama3'
            ]);

        $this->app->instance(YalihanCortex::class, $mockCortex);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.ai.suggest'), [
                'action' => 'price', // ACTION PARAM IS REQUIRED
                'fiyat' => 10000000, // Base price
                'kategori' => 'Konut',
                'metrekare' => 150,
                'il' => 'Bodrum',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify enrichment happened
        $json = $response->json();
        $suggestion = $json['suggestions'][0];

        $this->assertArrayHasKey('confidence', $suggestion);
        $this->assertArrayHasKey('piyasa_durumu', $suggestion);
        $this->assertEquals('High', $suggestion['piyasa_durumu']); // 20M > 10M * 1.05
    }

    /** @test */
    public function it_handles_api_failure_gracefully()
    {
        $mockCortex = Mockery::mock(YalihanCortex::class);
        $mockCortex->shouldReceive('suggestPrice')
            ->once()
            ->andReturn([
                'success' => false,
                'suggestions' => [],
                'error' => 'Service unavailable'
            ]);

        $this->app->instance(YalihanCortex::class, $mockCortex);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.ai.suggest'), [
                'action' => 'price', // ACTION PARAM IS REQUIRED
                'fiyat' => 5000000,
            ]);

        $response->assertStatus(200) // Controller returns 200 with success: false
            ->assertJsonPath('success', false);
    }
}
