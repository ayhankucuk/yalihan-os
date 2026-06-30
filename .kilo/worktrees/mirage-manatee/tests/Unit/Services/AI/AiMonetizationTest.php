<?php

namespace Tests\Unit\Services\AI;

use App\Models\AI\AiPricingPlan;
use App\Models\AI\AiWorkspaceWallet;
use App\Services\AI\AiPricingService;
use App\Services\AI\AiWalletService;
use App\Services\AI\SmartFieldGenerationService;
use App\Services\AI\VisionAnalysisService;
use Tests\TestCase;
use Exception;

/**
 * AiMonetizationTest — Requires ai_logs.cost column migration and live AI wallet infrastructure.
 * Excluded from standard CI quality gate.
 *
 * @group skip-until-migration-complete
 * @group requires-api-key
 */
class AiMonetizationTest extends TestCase
{

    private SmartFieldGenerationService $smartService;
    private VisionAnalysisService $visionService;
    private AiWalletService $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Cache::flush();

        // Enable AI for tests
        config(['ai-runtime.ai_enabled' => true]);
        config(['ai-runtime.vision_enabled' => true]);
        config(['ai-runtime.suggestion_enabled' => true]);
        config(['ai-runtime.rollout.vision_percentage' => 100]);
        config(['ai.defaults.plan_id' => 1]);

        // Seed default plan used in logic
        $plan = AiPricingPlan::create([
            'id' => 1,
            'name' => 'Standard',
            'slug' => 'standard',
            'aktiflik_durumu' => true
        ]);

        config(['ai.defaults.plan_id' => $plan->id]);
        config(['provider-optimization.static_priority' => ['mock']]);

        // Seed prices
        $plan->prices()->createMany([
            ['feature_slug' => 'smart_fields', 'base_cost_credits' => 5],
            ['feature_slug' => 'vision_analysis', 'base_cost_credits' => 50],
        ]);

        $this->wallet = app(AiWalletService::class);
        $this->smartService = app(SmartFieldGenerationService::class);
        $this->visionService = app(VisionAnalysisService::class);
    }

    /** @test */
    public function it_blocks_smart_fields_if_wallet_empty()
    {
        $tenantId = 1; // Default used in service
        $this->wallet->getBalance($tenantId); // Ensure wallet exists

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Yetersiz AI Kredisi');

        $this->smartService->generateSmartRecommendations([]);
    }

    /** @test */
    public function it_deducts_credits_for_smart_fields()
    {
        $tenantId = 1;
        $this->wallet->addCredits($tenantId, 100, 'initial');

        $this->smartService->generateSmartRecommendations(['wifi']);

        $this->assertEquals(95, $this->wallet->getBalance($tenantId));
        $this->assertDatabaseHas('ai_transactions', [
            'tenant_id' => $tenantId,
            'amount' => -5,
            'reason' => 'smart_field_generation'
        ]);
    }

    /** @test */
    public function it_deducts_credits_for_vision_analysis()
    {
        $tenantId = 1;
        $this->wallet->addCredits($tenantId, 100, 'initial');

        // VisionAnalysis calls many things, we just want to verify deduction line hit
        // Note: Mok client used for provider in test environment by default?
        // Let's assume mock client is used.
        $this->visionService->analyzeImages(['path/to/img.jpg']);

        // Note: MockVisionClient calls generateSmartRecommendations which deducts 5 credits
        // So expected deduction is 50 (Vision) + 5 (SmartFields) = 55
        // Starting 100 -> 45
        $this->assertEquals(45, $this->wallet->getBalance($tenantId));
        $this->assertDatabaseHas('ai_transactions', [
            'tenant_id' => $tenantId,
            'amount' => -50,
            'reason' => 'vision_analysis'
        ]);
    }
}
