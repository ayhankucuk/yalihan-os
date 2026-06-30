<?php

namespace Tests\Feature\AI;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu; // Changed
use App\Services\AI\VisionAnalysisService;
use App\Services\AI\SmartFieldGenerationService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExplainabilityV2Test extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Phase 15: AI Credits - Ensure workspace has enough for testing
        \Illuminate\Support\Facades\DB::table('ai_workspace_wallets')->insert([
            'tenant_id' => 1,
            'balance' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function it_persists_explainability_v2_data_in_telemetry()
    {
        $kategori = IlanKategori::factory()->create(['name' => 'Villa']);
        // Fixed: Use YayinTipiSablonu
        $yayinTipi = YayinTipiSablonu::factory()->create(['ad' => 'Satılık', 'slug' => 'satilik-' . uniqid()]);

        // Add a feature assignment for UPS Guard
        $feature = \App\Models\Feature::factory()->create(['slug' => 'deniz-manzarasi']);
        \App\Models\FeatureAssignment::create([
            'assignable_type' => YayinTipiSablonu::class, // Changed
            'assignable_id' => $yayinTipi->id,
            'feature_id' => $feature->id,
            'is_visible' => 1
        ]);

        // Mock request context
        $request = new Request();
        $request->headers->set('X-Request-ID', 'test-req-123');

        $visionService = app(VisionAnalysisService::class);

        // We need to trigger analyzeImages in a way that it logs telemetry.
        // Actually, WizardController@analyzeImages does this.

        // Instead of calling protected methods, let's use the actual controller logic
        // through the service directly if we want to test the flow.

        // Let's test SmartFieldGenerationService directly first
        $smartService = app(SmartFieldGenerationService::class);
        $suggestions = [
            [
                'slug' => 'deniz-manzarasi',
                'confidence' => 0.95, // High confidence -> Auto Apply
                'source' => 'vision'
            ]
        ];

        $results = $smartService->generateSmartRecommendations($suggestions, $kategori->id, $yayinTipi->id);

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('explainability_v2', $results[0]);
        $this->assertStringContainsString('otomatik uygulandı', $results[0]['explainability_v2']['primary_reason']);

        // Now verify telemetry logging (integration)
        $telemetryService = app(\App\Services\AI\AiTelemetryService::class);
        $telemetryService->logFeatureUsage([
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'feature_slug' => $results[0]['slug'],
            'confidence' => $results[0]['confidence'],
            'source_tipi' => 'vision',
            'aksiyon' => 'auto_applied',
            'explainability_v2' => $results[0]['explainability_v2'],
            'istek_id' => 'test-req-v2'
        ]);

        $this->assertDatabaseHas('ai_feature_usages', [
            'feature_slug' => 'deniz-manzarasi',
            'aksiyon' => 'auto_applied'
        ]);

        $usage = \Illuminate\Support\Facades\DB::table('ai_feature_usages')->first();
        $this->assertNotNull($usage->explainability_v2_json);

        $v2Data = json_decode($usage->explainability_v2_json, true);
        $this->assertEquals('v2.0', $v2Data['logic_version']);
    }
}
