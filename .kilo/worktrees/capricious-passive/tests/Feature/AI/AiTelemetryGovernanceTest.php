<?php

namespace Tests\Feature\AI;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class AiTelemetryGovernanceTest extends TestCase
{

    private User $admin;
    private int $kategoriId = 1; // Konut
    private int $yayinTipiId = 1; // Satılık

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role_id' => 1 // Assuming 1 is admin in your roles table
        ]);

        // Ensure category exists
        DB::table('ilan_kategorileri')->insertOrIgnore([
            'id' => 1,
            'name' => 'Konut',
            'slug' => 'konut',
            'aktiflik_durumu' => 1,
            'display_order' => 1
        ]);

        DB::table('yayin_tipi_sablonlari')->insertOrIgnore([
            'id' => 1,
            'ad' => 'Satılık',
            'slug' => 'satilik',
            'aktiflik_durumu' => 1,
            'display_order' => 1
        ]);

        // Seed Feature and Template Assignment for UPS Guard
        DB::table('feature_categories')->insertOrIgnore([
            'id' => 1,
            'name' => 'İç Özellikler',
            'slug' => 'ic-ozellikler',
            'aktiflik_durumu' => 1,
            'display_order' => 1
        ]);

        DB::table('features')->insertOrIgnore([
            'id' => 1,
            'name' => 'Ortak Havuz',
            'slug' => 'ortak-havuz',
            'feature_category_id' => 1,
            'aktiflik_durumu' => 1,
            'display_order' => 1
        ]);

        // Assign to Konut-Satilik (id=1)
        DB::table('feature_assignments')->insertOrIgnore([
            'feature_id' => 1,
            'assignable_type' => 'App\Models\YayinTipiSablonu',
            'assignable_id' => 1,
            'is_visible' => 1,
            'is_required' => 0,
            'display_order' => 1
        ]);

        // Also seed for 'other' feature used in user actions test
        DB::table('features')->insertOrIgnore([
            'id' => 2,
            'name' => 'Deniz Manzaralı',
            'slug' => 'deniz-manzarali',
            'feature_category_id' => 1,
            'aktiflik_durumu' => 1,
            'display_order' => 2
        ]);

        // Mock ProviderOptimizationService to return 'mock' provider to avoid API key requirement
        $this->mock(\App\Services\AI\ProviderOptimizationService::class, function ($mock) {
            $mock->shouldReceive('chooseProvider')->andReturn('mock');
        });

        // Phase 15: AI Credits - Ensure workspace has enough for testing
        DB::table('ai_workspace_wallets')->insert([
            'tenant_id' => 1,
            'balance' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Enable AI Rollout
        Config::set('ai-runtime.ai_enabled', true);
        Config::set('ai-runtime.vision_enabled', true);
    }

    /** @test */
    public function it_logs_auto_applied_suggestions_during_visual_analysis()
    {
        // Removed dumps
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/wizard/analyze-images', [
            'images' => ['havuz.jpg'],
            'category_id' => 1,
            'yayin_tipi_id' => 1
        ]);


        $response->assertStatus(200);

        // Assert telemetry log exists
        $this->assertDatabaseHas('ai_feature_usages', [
            'feature_slug' => 'ortak-havuz',
            'aksiyon' => 'auto_applied',
            'source_tipi' => 'image',
            'kategori_id' => 1
        ]);
    }

    /** @test */
    public function it_logs_user_actions_via_telemetry_endpoint()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/wizard/telemetry/feature-action', [
            'kategori_id' => 1,
            'yayin_tipi_id' => 1,
            'feature_slug' => 'deniz-manzarali',
            'confidence' => 0.75,
            'source_tipi' => 'image',
            'aksiyon' => 'user_applied',
            'neden' => 'User clicked apply',
            'istek_id' => 'test_req_123'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ai_feature_usages', [
            'feature_slug' => 'deniz-manzarali',
            'aksiyon' => 'user_applied',
            'istek_id' => 'test_req_123'
        ]);
    }

    /** @test */
    public function it_enforces_governance_thresholds()
    {
        // Set a high threshold for auto-apply
        Config::set('ai-governance.global.auto_apply_min_confidence', 0.95);
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/wizard/analyze-images', [
            'images' => ['havuz.jpg'], // Havuz gives 0.85 in simulator
            'category_id' => 1,
            'yayin_tipi_id' => 1
        ]);

        $suggestions = $response->json('data.suggestions');
        $havuz = collect($suggestions)->firstWhere('slug', 'ortak-havuz');

        // Should be suggested, not auto-applied because 0.85 < 0.95
        $this->assertFalse($havuz['auto_apply']);
        $this->assertTrue($havuz['suggested']);
    }

    /** @test */
    public function it_respects_forbidden_auto_apply_list()
    {
        Config::set('ai-governance.forbidden_auto_apply', ['ortak-havuz']);
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/wizard/analyze-images', [
            'images' => ['havuz.jpg'], // confidence 0.85
            'category_id' => 1,
            'yayin_tipi_id' => 1
        ]);

        $suggestions = $response->json('data.suggestions');
        $havuz = collect($suggestions)->firstWhere('slug', 'ortak-havuz');

        // Should be forbidden from auto-apply even with 0.85 confidence
        $this->assertFalse($havuz['auto_apply']);
        $this->assertTrue($havuz['suggested']);
        $this->assertStringContainsString('Manuel onay gerekli', $havuz['reason']);
    }

    /** @test */
    public function it_includes_detailed_explainability_in_suggestions()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/wizard/analyze-images', [
            'images' => ['havuz.jpg'],
            'category_id' => 1,
            'yayin_tipi_id' => 1
        ]);

        $suggestions = $response->json('data.suggestions');
        $havuz = collect($suggestions)->firstWhere('slug', 'ortak-havuz');

        $this->assertArrayHasKey('explainability_detail', $havuz);
        $this->assertEquals('image', $havuz['explainability_detail']['source']);
        $this->assertNotEmpty($havuz['explainability_detail']['signals']);
        $this->assertNotEmpty($havuz['explainability_detail']['confidence_factors']);
    }

    /** @test */
    public function it_respects_category_specific_overrides()
    {
        // Yazlik (ID 5) has 0.85 threshold in config.
        // Simulator gives 0.85.
        // If we set override to 0.90 for test, 0.85 should NOT be auto-applied.
        Config::set('ai-governance.category_overrides.yazlik.auto_apply_min_confidence', 0.90);

        $this->actingAs($this->admin);

        // Seed Yazlik category (ID 5)
        DB::table('ilan_kategorileri')->insert([
            'id' => 5,
            'name' => 'Yazlık',
            'slug' => 'yazlik',
            'aktiflik_durumu' => 1,
            'display_order' => 5
        ]);

        DB::table('yayin_tipi_sablonlari')->insertOrIgnore([
            'id' => 5,
            'ad' => 'Kiralık',
            'slug' => 'kiralik',
            'aktiflik_durumu' => 1,
            'display_order' => 5
        ]);

        // Assign feature to Yazlik
        DB::table('feature_assignments')->insert([
            'feature_id' => 1, // ortak-havuz
            'assignable_type' => 'App\Models\YayinTipiSablonu',
            'assignable_id' => 5,
            'is_visible' => 1,
            'is_required' => 0,
            'display_order' => 1
        ]);

        $response = $this->postJson('/api/v1/wizard/analyze-images', [
            'images' => ['havuz.jpg'], // confidence 0.85
            'category_id' => 5,
            'yayin_tipi_id' => 5
        ]);

        $suggestions = $response->json('data.suggestions');
        $havuz = collect($suggestions)->firstWhere('slug', 'ortak-havuz');

        // Should NOT be auto-applied because 0.85 < 0.90 (override)
        $this->assertFalse($havuz['auto_apply']);
        $this->assertTrue($havuz['suggested']);
    }
}
