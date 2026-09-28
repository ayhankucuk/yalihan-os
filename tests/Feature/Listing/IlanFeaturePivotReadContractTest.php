<?php

namespace Tests\Feature\Listing;

use App\Enums\IlanDurumu;
use App\Models\Feature;
use App\Models\Ilan;
use App\Services\AIService;
use App\Services\Cortex\CortexPitchGenerator;
use Mockery;
use Tests\TestCase;

/**
 * IlanFeaturePivotReadContractTest
 *
 * Task: ILAN_FEATURE_PIVOT_READ_CONTRACT_REMEDIATION_01
 * Role: IMPLEMENTER
 * Decision Maker: Ayhan
 *
 * Verifies canonical Feature <-> ilan_feature pivot read contract:
 * - Table: ilan_feature
 * - Canonical Column: value
 * - Prohibits reliance on stale legacy 'deger' column
 */
class IlanFeaturePivotReadContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * CASE A — CANONICAL PIVOT:
     * Feature relation returns pivot->value === '1'
     *
     * @test
     */
    public function test_case_a_canonical_feature_pivot_returns_value(): void
    {
        $admin = $this->createAdminUser();
        $ilan = $this->createPublishableListing($admin, [
            'yayin_durumu' => IlanDurumu::YAYINDA,
        ]);

        $feature = Feature::create([
            'name' => 'Akıllı Ev Sistemi',
            'slug' => 'akilli-ev-sistemi-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        $ilan->ozellikler()->attach($feature->id, ['value' => '1']);

        $freshIlan = Ilan::with('ozellikler')->findOrFail($ilan->id);
        $attached = $freshIlan->ozellikler->firstWhere('id', $feature->id);

        $this->assertNotNull($attached, 'Feature should be attached to listing');
        $this->assertSame('1', $attached->pivot->value, 'Canonical pivot column "value" must be returned');
        $this->assertNull($attached->pivot->deger, 'Legacy "deger" attribute must be null on canonical pivot');
    }

    /**
     * CASE B — PUBLIC LISTING RENDERING:
     * Render the relevant public listing/detail surface through real test-safe entrypoint.
     * Require: canonical feature/value is rendered or consumed as intended.
     *
     * @test
     */
    public function test_case_b_public_listing_rendering_consumes_canonical_pivot_value(): void
    {
        $admin = $this->createAdminUser();
        $ilan = $this->createPublishableListing($admin, [
            'baslik' => 'Lüks Bodrum Villası ' . uniqid(),
            'yayin_durumu' => IlanDurumu::YAYINDA,
        ]);

        $activeFeature = Feature::create([
            'name' => 'Özel Havuz',
            'slug' => 'ozel-havuz-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        $inactiveFeature = Feature::create([
            'name' => 'Kış Bahçesi',
            'slug' => 'kis-bahcesi-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        // Attach active feature with value = '1' and inactive with value = '0'
        $ilan->ozellikler()->attach($activeFeature->id, ['value' => '1']);
        $ilan->ozellikler()->attach($inactiveFeature->id, ['value' => '0']);

        // 1. Direct blade view render verification
        $view = $this->view('frontend.ilanlar.show', [
            'ilan' => $ilan->fresh(['ozellikler', 'ilce', 'mahalle', 'fotograflar']),
            'similar' => collect(),
            'danismanDigerIlanlar' => collect(),
            'cortexHealth' => null,
            'cortexAnalysis' => null,
            'currency' => 'TRY',
            'seo' => [
                'title' => 'Test Title',
                'description' => 'Test Desc',
                'og_type' => 'og:product',
                'og_image' => '',
                'og_locale' => 'tr_TR',
                'canonical' => '',
                'robots' => '',
            ],
            'mainImage' => null,
        ]);

        $view->assertSee('Özellikler');
        $view->assertSee('Özel Havuz');
        $view->assertDontSee('Kış Bahçesi');

        // 2. HTTP surface detail route verification
        $response = $this->get(route('ilanlar.show', $ilan->id));
        $response->assertStatus(200);
        $response->assertSee('Özellikler');
        $response->assertSee('Özel Havuz');
        $response->assertDontSee('Kış Bahçesi');
    }

    /**
     * CASE C — CORTEX PITCH:
     * Execute real CortexPitchGenerator path.
     * Prove canonical pivot value is included in feature input/prompt/data produced by the service.
     *
     * @test
     */
    public function test_case_c_cortex_pitch_generator_consumes_canonical_pivot_value(): void
    {
        $admin = $this->createAdminUser();
        $ilan = $this->createPublishableListing($admin, [
            'baslik' => 'Yalıkavak Deniz Manzaralı Villa',
            'yayin_durumu' => IlanDurumu::YAYINDA,
            'fiyat' => 15000000,
            'para_birimi' => 'TRY',
        ]);

        $roiFeature = Feature::create([
            'name' => 'Sezonluk ROI',
            'slug' => 'sezonluk-roi-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        $yieldFeature = Feature::create([
            'name' => 'Getiri',
            'slug' => 'getiri-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        $benchmarkFeature = Feature::create([
            'name' => 'Benchmark',
            'slug' => 'benchmark-' . uniqid(),
            'aktiflik_durumu' => true,
        ]);

        $ilan->ozellikler()->attach($roiFeature->id, ['value' => '18.5%']);
        $ilan->ozellikler()->attach($yieldFeature->id, ['value' => '12.0%']);
        $ilan->ozellikler()->attach($benchmarkFeature->id, ['value' => '+4.2%']);

        // 1. Verify prompt payload sent to AI provider contains canonical pivot values
        $capturedPrompt = null;
        $aiServiceMock = Mockery::mock(AIService::class);
        $aiServiceMock->shouldReceive('generate')
            ->once()
            ->with(Mockery::on(function ($prompt) use (&$capturedPrompt) {
                $capturedPrompt = $prompt;
                return true;
            }), Mockery::any())
            ->andReturn([
                'success' => true,
                'data' => 'Persuasive AI Generated Pitch for Yalıkavak Villa',
            ]);

        $generator = new CortexPitchGenerator($aiServiceMock);
        $result = $generator->generatePitch($ilan->fresh(), 'telegram');

        $this->assertTrue($result['success']);
        $this->assertSame('Persuasive AI Generated Pitch for Yalıkavak Villa', $result['content']);
        $this->assertNotNull($capturedPrompt, 'Prompt should have been generated and passed to AI service');

        $this->assertStringContainsString('18.5%', $capturedPrompt, 'Prompt context must contain canonical ROI pivot value');
        $this->assertStringContainsString('12.0%', $capturedPrompt, 'Prompt context must contain canonical Yield pivot value');
        $this->assertStringContainsString('+4.2%', $capturedPrompt, 'Prompt context must contain canonical Benchmark pivot value');

        // 2. Verify fallback generation also contains canonical pivot values when AI provider fails
        $failingAiMock = Mockery::mock(AIService::class);
        $failingAiMock->shouldReceive('generate')
            ->once()
            ->andThrow(new \Exception('AI Provider Connection Timeout'));

        $fallbackGenerator = new CortexPitchGenerator($failingAiMock);
        $fallbackResult = $fallbackGenerator->generatePitch($ilan->fresh(), 'telegram');

        $this->assertTrue($fallbackResult['success']);
        $this->assertStringContainsString('18.5', $fallbackResult['content'], 'Fallback pitch must contain canonical ROI');
        $this->assertStringContainsString('12.0%', $fallbackResult['content'], 'Fallback pitch must contain canonical Yield');
    }
}
