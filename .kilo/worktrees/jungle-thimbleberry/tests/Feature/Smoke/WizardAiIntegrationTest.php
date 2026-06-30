<?php

namespace Tests\Feature\Smoke;

use App\Models\IlanKategori;
use App\Models\User;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class WizardAiIntegrationTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai-cost-guard.enabled' => false]);
    }

    /**
     * @test
     * @group smoke
     * @group ai
     */
    public function suggest_endpoint_returns_smart_recommendations_with_governance(): void
    {
        // Arrange
        $admin = User::factory()->create(['role_id' => 1]);
        $kategoriId = 6; // Konut
        $yayinTipiId = 3;

        IlanKategori::factory()->create(['id' => $kategoriId]);

        // Fixed: Use YayinTipiSablonu (ignoring kategori_id as it's not in YTS)
        $yayinTipi = \App\Models\YayinTipiSablonu::firstOrCreate(
            ['id' => $yayinTipiId],
            [
                'ad' => 'Satılık',
                'slug' => 'satilik',
                'aktiflik_durumu' => true,
                'display_order' => 1
            ]
        );

        // Allowed Feature: Ortak Havuz
        $featureHavuz = \App\Models\Feature::factory()->create(['slug' => 'ortak-havuz', 'name' => 'Ortak Havuz', 'type' => 'boolean']);
        // Allowed Feature: Balkon
        $featureBalkon = \App\Models\Feature::factory()->create(['slug' => 'balkon', 'name' => 'Balkon', 'type' => 'boolean']);
        // NOT Allowed Feature: Sauna (Simulate it being detected in text but not assigned in UPS)
        $featureSauna = \App\Models\Feature::factory()->create(['slug' => 'sauna', 'name' => 'Sauna', 'type' => 'boolean']);

        // Assign only Havuz and Balkon to this category
        \App\Models\FeatureAssignment::factory()->create([
            'assignable_type' => 'App\Models\YayinTipiSablonu', // Changed
            'assignable_id' => $yayinTipi->id,
            'feature_id' => $featureHavuz->id,
            'is_visible' => true,
        ]);
        \App\Models\FeatureAssignment::factory()->create([
            'assignable_type' => 'App\Models\YayinTipiSablonu', // Changed
            'assignable_id' => $yayinTipi->id,
            'feature_id' => $featureBalkon->id,
            'is_visible' => true,
        ]);

        // Act
        // Text contains 'havuzlu', 'balkon', 'sauna'
        $text = "Harika bir havuzlu villa, geniş balkon ve sauna keyfi.";

        $response = $this->actingAs($admin, 'web')->postJson('/api/v1/wizard/suggest', [
            'description' => $text,
            'category_id' => $kategoriId,
            'yayin_tipi_id' => $yayinTipiId
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'suggestions' => [
                    '*' => ['slug', 'confidence', 'suggested', 'auto_apply', 'reason']
                ],
                'ai_confidence_threshold'
            ]
        ]);

        $suggestions = collect($response->json('data.suggestions'));

        // 1. Check UPS Guard: 'sauna' should be FILTERED OUT even though text has it
        $this->assertFalse(
            $suggestions->contains('slug', 'sauna'),
            'UPS Guard failed: Sauna should be filtered out as it is not assigned to this category.'
        );

        // 2. Check Detection
        $havuzItem = $suggestions->firstWhere('slug', 'ortak-havuz');
        $this->assertNotNull($havuzItem, 'Havuz should be suggested');
    }

    /**
     * @test
     * @group smoke
     * @group ai
     */
    public function analyze_images_endpoint_returns_explained_results(): void
    {
        // Arrange
        $admin = User::factory()->create(['role_id' => 1]);

        // Act
        $images = ['havuzlu_villa.jpg'];

        // Pass context to simulated endpoint
        $response = $this->actingAs($admin, 'web')->postJson('/api/v1/wizard/analyze-images', [
            'images' => $images
        ]);

        // Assert
        $response->assertStatus(200);

        $suggestions = collect($response->json('data.suggestions'));
        $item = $suggestions->firstWhere('slug', 'ortak-havuz');

        $this->assertNotNull($item);
        // Explainability check
        $this->assertEquals("Dosya adında 'havuzlu_villa.jpg' geçtiği için önerildi (simülasyon)", $item['reason']);
        $this->assertEquals('image', $item['source']);
        $this->assertTrue($item['auto_apply'] ?? false, 'Visual items should be high confidence');
    }
}
