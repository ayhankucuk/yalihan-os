<?php

namespace Tests\Feature\AI;

use App\Models\AiFeatureUsage;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu; // Changed
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\User;
use Tests\TestCase;

class FeatureFeedbackContractTest extends TestCase
{

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role_id' => 1]);
    }

    /** @test */
    public function it_records_valid_feedback_and_creates_learning_signal()
    {
        $kategori = IlanKategori::factory()->create(['name' => 'Villa']);
        // Fixed: Use YayinTipiSablonu
        $yayinTipi = YayinTipiSablonu::factory()->create([
            'ad' => 'Satılık',
            'slug' => 'satilik-' . uniqid()
        ]);
        $feature = Feature::factory()->create(['slug' => 'ortak-havuz']);

        // Set up UPS Guard
        FeatureAssignment::create([
            'assignable_type' => YayinTipiSablonu::class, // Changed
            'assignable_id' => $yayinTipi->id,
            'feature_id' => $feature->id,
            'is_visible' => 1
        ]);

        $usage = AiFeatureUsage::create([
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'feature_slug' => 'ortak-havuz',
            'confidence' => 0.85,
            'source_tipi' => 'image',
            'aksiyon' => 'auto_applied',
            'neden_detay' => ['signals' => ['test'], 'provider' => 'mock'],
            'istek_id' => 'test-req'
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/wizard/feature-feedback', [
            'ai_feature_usage_id' => $usage->id,
            'slug' => 'ortak-havuz',
            'karar' => 'applied'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.skor', 1);

        $this->assertDatabaseHas('ai_ogrenme_sinyalleri', [
            'ai_feature_usage_id' => $usage->id,
            'karar_tipi' => 'applied',
            'skor' => 1
        ]);

        $this->assertEquals('applied', $usage->fresh()->aksiyon);
    }

    /** @test */
    public function it_blocks_feedback_for_slugs_not_in_ups_template()
    {
        $kategori = IlanKategori::factory()->create(['name' => 'Villa']);
        // Fixed: Use YayinTipiSablonu
        $yayinTipi = YayinTipiSablonu::factory()->create([
            'ad' => 'Satılık',
            'slug' => 'satilik-' . uniqid()
        ]);

        $usage = AiFeatureUsage::create([
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'feature_slug' => 'yasakli-ozellik',
            'confidence' => 0.90,
            'source_tipi' => 'image',
            'aksiyon' => 'suggested',
            'istek_id' => 'test-req'
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/wizard/feature-feedback', [
            'ai_feature_usage_id' => $usage->id,
            'slug' => 'yasakli-ozellik',
            'karar' => 'applied'
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['code' => 'VALIDATION_ERROR']);
    }
}
