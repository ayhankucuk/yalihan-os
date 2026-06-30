<?php

namespace Tests\Unit\Services\AI;

use App\Models\AI\AiFeaturePrice;
use App\Models\AI\AiPricingPlan;
use App\Services\AI\AiPricingService;
use Tests\TestCase;

class AiPricingServiceTest extends TestCase
{

    private AiPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AiPricingService();
    }

    /** @test */
    public function it_returns_default_cost_if_no_plan_exists()
    {
        // No pricing plans seeded
        $cost = $this->service->getPrice('some_feature');
        
        $this->assertEquals(10, $cost); // Default fallback
    }

    /** @test */
    public function it_fetches_configured_price_for_plan()
    {
        // Setup Plan & Price
        $plan = AiPricingPlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'aktiflik_durumu' => true
        ]);

        AiFeaturePrice::create([
            'plan_id' => $plan->id,
            'feature_slug' => 'smart_title',
            'base_cost_credits' => 50,
            'is_dynamic' => false
        ]);

        // Act
        $cost = $this->service->getPrice('smart_title', $plan->id);

        // Assert
        $this->assertEquals(50, $cost);
    }

    /** @test */
    public function it_applies_multiplier_if_dynamic()
    {
        $plan = AiPricingPlan::create(['name' => 'Premium', 'slug' => 'premium']);
        
        AiFeaturePrice::create([
            'plan_id' => $plan->id,
            'feature_slug' => 'heavy_task',
            'base_cost_credits' => 100,
            'is_dynamic' => true,
            'multiplier' => 1.5
        ]);

        $cost = $this->service->getPrice('heavy_task', $plan->id);
        
        // 100 * 1.5 = 150
        $this->assertEquals(150, $cost);
    }
}
