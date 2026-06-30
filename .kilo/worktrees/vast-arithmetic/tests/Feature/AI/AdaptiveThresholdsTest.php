<?php

namespace Tests\Feature\AI;

use App\Models\AiEsikProfili;
use App\Models\AiFeatureUsage;
use App\Models\AiOgrenmeSinyali;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu; // Changed
use App\Services\AI\AdaptiveThresholdEngine;
use App\Services\AI\SmartFieldGenerationService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdaptiveThresholdsTest extends TestCase
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
    public function it_recalculates_thresholds_based_on_learning_signals()
    {
        $kategori = IlanKategori::factory()->create(['name' => 'Villa']);
        // Fixed: Use YayinTipiSablonu
        $yayinTipi = YayinTipiSablonu::firstOrCreate(['slug' => 'satilik'], ['ad' => 'Satılık']);

        // Create a valid parent usage record
        $usage = AiFeatureUsage::create([
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'feature_slug' => 'ortak-havuz',
            'confidence' => 0.85,
            'source_tipi' => 'test',
            'aksiyon' => 'suggested',
            'istek_id' => 'test-req'
        ]);

        // 1. Create 60 successful learning signals (Acceptance Rate = 1.0)
        for ($i = 0; $i < 60; $i++) {
            AiOgrenmeSinyali::create([
                'kategori_id' => $kategori->id,
                'yayin_tipi_id' => $yayinTipi->id,
                'ai_feature_usage_id' => $usage->id,
                'feature_slug' => 'ortak-havuz',
                'confidence' => 0.85,
                'karar_tipi' => 'applied',
                'skor' => 1,
                'context_hash' => 'test-hash',
                'sinyaller_json' => []
            ]);
        }

        // 2. Run recalculation command
        Artisan::call('ai:recalculate-thresholds');

        // 3. Verify threshold profile was created and lowered due to high trust (0.80 -> 0.77)
        $profile = AiEsikProfili::where('kategori_id', $kategori->id)
            ->where('yayin_tipi_id' ,$yayinTipi->id)
            ->first();

        $this->assertNotNull($profile);
        $this->assertEquals(0.77, (float) $profile->auto_apply_esigi);
    }

    /** @test */
    public function it_increases_threshold_when_acceptance_rate_is_low()
    {
        $kategori = IlanKategori::factory()->create(['name' => 'Villa']);
        // Fixed: Use YayinTipiSablonu
        $yayinTipi = YayinTipiSablonu::firstOrCreate(['slug' => 'satilik'], ['ad' => 'Satılık']);

        $usage = AiFeatureUsage::create([
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'feature_slug' => 'yanlis-ozellik',
            'confidence' => 0.85,
            'source_tipi' => 'test',
            'aksiyon' => 'suggested',
            'istek_id' => 'test-req'
        ]);

        // Create 60 failed learning signals (Acceptance Rate = 0.0)
        for ($i = 0; $i < 60; $i++) {
            AiOgrenmeSinyali::create([
                'kategori_id' => $kategori->id,
                'yayin_tipi_id' => $yayinTipi->id,
                'ai_feature_usage_id' => $usage->id,
                'feature_slug' => 'yanlis-ozellik',
                'confidence' => 0.85,
                'karar_tipi' => 'dismissed', // Rejected
                'skor' => -1,
                'context_hash' => 'test-hash-bad',
                'sinyaller_json' => []
            ]);
        }

        Artisan::call('ai:recalculate-thresholds');

        $profile = AiEsikProfili::where('kategori_id', $kategori->id)->first();
        $this->assertEquals(0.85, (float) $profile->auto_apply_esigi);
    }

    /** @test */
    public function it_respects_adaptive_thresholds_in_smart_service()
    {
        $kategori = IlanKategori::factory()->create(['name' => 'Villa']);
        // Fixed: Use YayinTipiSablonu
        $yayinTipi = YayinTipiSablonu::firstOrCreate(['slug' => 'satilik'], ['ad' => 'Satılık']);

        $feature = \App\Models\Feature::factory()->create(['slug' => 'ortak-havuz']);

        // Set up UPS Guard Assignment
        \App\Models\FeatureAssignment::create([
            'assignable_type' => YayinTipiSablonu::class, // Changed
            'assignable_id' => $yayinTipi->id,
            'feature_id' => $feature->id,
            'is_visible' => 1
        ]);

        // Manually set a strict threshold profile
        AiEsikProfili::create([
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'saglayici' => 'global',
            'auto_apply_esigi' => 0.95,
            'suggest_esigi' => 0.70,
            'min_ornek_sayisi' => 50
        ]);

        $service = app(SmartFieldGenerationService::class);

        $suggestions = [
            [
                'slug' => 'ortak-havuz',
                'confidence' => 0.90, // Below auto_apply (0.95), but above suggest (0.70)
                'source' => 'test'
            ]
        ];

        $results = $service->generateSmartRecommendations($suggestions, $kategori->id, $yayinTipi->id);

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['auto_apply']);
        $this->assertTrue($results[0]['suggested']);
    }
}
