<?php

namespace Tests\Unit\AI;

use App\Models\Feature;
use App\Services\AI\AdaptiveThresholdEngine;
use App\Services\AI\SmartFieldGenerationService;
use App\Services\Ups\FeatureTemplateResolver;
use Mockery;
use Tests\TestCase;

class SmartFieldConfidencePolicyTest extends TestCase
{
    private $service;
    private $mockResolver;
    private $mockThresholdEngine;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockResolver = Mockery::mock(FeatureTemplateResolver::class);
        $this->app->instance(FeatureTemplateResolver::class, $this->mockResolver);
        
        // Mock AdaptiveThreshold Engine with fixed thresholds
        $this->mockThresholdEngine = Mockery::mock(AdaptiveThresholdEngine::class);
        $this->mockThresholdEngine->shouldReceive('getActiveThresholds')
            ->once()
            ->andReturnUsing(fn() => [
                'auto_apply' => 0.80,
                'suggest' => 0.50,
                'is_adaptive' => false,
            ]);
        $this->app->instance(AdaptiveThresholdEngine::class, $this->mockThresholdEngine);
        
        // Phase 15: Guarantee credits for generic tests
        // Ensure tenant exists for foreign key integrity
        $tenantId = config('ai.defaults.tenant_id', 1);
        if (!\App\Models\SaaS\Tenant::find($tenantId)) {
            \App\Models\SaaS\Tenant::create([
                'id' => $tenantId,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'name' => 'Test Tenant',
                'status' => 'active'
            ]);
        }

        $wallet = app(\App\Services\AI\AiWalletService::class);
        $wallet->addCredits($tenantId, 1000, 'test_system', 'Test Kredisi');

        $this->service = app()->make(SmartFieldGenerationService::class);
    }

    /** @test */
    public function it_sets_auto_apply_true_for_high_confidence()
    {
        // Simulate no UPS filtering by mocking resolve to return our slug
        $this->mockResolver->shouldReceive('resolveFeatures')
            ->once()
            ->with(5, 2)
            ->andReturn(collect([
                ['slug' => 'ortak-havuz']
            ]));

        $input = [
            [
                'slug' => 'ortak-havuz',
                'confidence' => 0.85,
                'source' => 'text'
            ]
        ];

        $result = $this->service->generateSmartRecommendations($input, 5, 2);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]['auto_apply'], 'Confidence >= 0.80 should set auto_apply=true');
        $this->assertFalse($result[0]['suggested'], 'If auto_apply is true, suggested should be false');
    }

    /** @test */
    public function it_sets_auto_apply_false_for_medium_confidence()
    {
        $this->mockResolver->shouldReceive('resolveFeatures')
            ->once()
            ->andReturn(collect([
                ['slug' => 'balkon']
            ]));

        $input = [
            [
                'slug' => 'balkon',
                'confidence' => 0.75,
                'source' => 'text'
            ]
        ];

        $result = $this->service->generateSmartRecommendations($input, 5, 2);

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]['auto_apply'], 'Confidence < 0.80 should set auto_apply=false');
        $this->assertTrue($result[0]['suggested']);
    }

    /** @test */
    public function it_rejects_low_confidence()
    {
        $this->mockResolver->shouldReceive('resolveFeatures')
            ->once()
            ->andReturn(collect([
                ['slug' => 'klima']
            ]));

        $input = [
            [
                'slug' => 'klima',
                'confidence' => 0.40,
                'source' => 'text'
            ]
        ];

        $result = $this->service->generateSmartRecommendations($input, 5, 2);

        $this->assertCount(0, $result, 'Confidence < 0.50 should be removed');
    }

    /** @test */
    public function it_filters_out_features_not_in_ups()
    {
        // Mock resolver to return ONLY 'balkon', excluding 'havuz'
        $this->mockResolver->shouldReceive('resolveFeatures')
            ->once()
            ->andReturn(collect([
                ['slug' => 'balkon']
            ]));

        $input = [
            ['slug' => 'balkon', 'confidence' => 0.9],
            ['slug' => 'havuz', 'confidence' => 0.9]
        ];

        $result = $this->service->generateSmartRecommendations($input, 5, 2);

        $this->assertCount(1, $result);
        $this->assertEquals('balkon', $result[0]['slug']);
    }

    /** @test */
    public function it_preserves_explainability_fields()
    {
        $this->mockResolver->shouldReceive('resolveFeatures')
            ->andReturn(collect([['slug' => 'kombi']]));

        $input = [
            [
                'slug' => 'kombi',
                'confidence' => 0.82, 
                'reason' => 'test reason',
                'source' => 'text'
            ]
        ];

        $result = $this->service->generateSmartRecommendations($input, 1, 1);

        $this->assertEquals('test reason', $result[0]['reason']);
        $this->assertEquals('text', $result[0]['source']);
    }

    /** @test */
    public function it_returns_empty_when_ups_returns_empty()
    {
        $this->mockResolver->shouldReceive('resolveFeatures')
            ->once()
            ->andReturn(collect([])); // Empty UPS

        $input = [
            ['slug' => 'balkon', 'confidence' => 0.9]
        ];

        $result = $this->service->generateSmartRecommendations($input, 5, 2);

        $this->assertEmpty($result, 'Should return empty if UPS allows nothing');
    }
}
