<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ilan;
use App\Models\Projections\MarketTrendProjection;
use App\Models\Projections\ListingVelocityProjection;
use App\Models\Projections\TalepMatchProjection;
use App\Services\AI\SellerStrategyService;

/**
 * 🛡️ SAB Production Seal Compliant
 * Feature & Unit tests for AI Seller Strategy Engine
 */
class SellerStrategyEngineTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        // 🛡️ Ensure default location exists for fallback
        $this->ensureIl(34, ['il_adi' => 'İstanbul']);
    }

    /** @test */
    public function calculate_price_strategy_score_correctly()
    {
        $service = new SellerStrategyService();

        $signals = [
            'market_demand_score' => 80.0,
            'buyer_match_density' => 50.0,
            'listing_view_velocity' => 60.0,
            'price_advantage_score' => 70.0,
            'regional_price_median_alignment' => 90.0,
        ];

        /*
            80 * 0.25 = 20
            50 * 0.20 = 10
            60 * 0.15 = 9
            70 * 0.20 = 14
            90 * 0.20 = 18
            Sum = 20 + 10 + 9 + 14 + 18 = 71
        */

        $score = $service->calculatePriceStrategyScore($signals);
        $this->assertEquals(71.0, $score);
    }

    /** @test */
    public function determines_strategy_classification_boundaries()
    {
        $service = new SellerStrategyService();

        $this->assertEquals('UNDERPRICED_SIGNAL', $service->determinePricingStrategy(90));
        $this->assertEquals('AGGRESSIVE_PRICING', $service->determinePricingStrategy(80));
        $this->assertEquals('BALANCED_PRICING', $service->determinePricingStrategy(60));
        $this->assertEquals('MARKET_MATCH_PRICING', $service->determinePricingStrategy(45));
        $this->assertEquals('OVERPRICED_RISK', $service->determinePricingStrategy(20));
    }

    /** @test */
    public function thin_controller_contract_is_valid()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create(['role_id' => 1]); // Admin/Advisor Role
        $ilan = Ilan::factory()->create([
            'fiyat' => 1500000,
            'yayin_durumu' => \App\Enums\IlanDurumu::YAYINDA->value,
        ]);

        // Mock exact projections required
        MarketTrendProjection::create([
            'city' => $ilan->adres->il_id ?? 34,
            'district' => $ilan->adres->ilce_id ?? 1,
            'property_type' => 'Daire', // or basic default
            'avg_price' => 1550000,
            'median_price' => 1600000,
            'price_change_7d' => 1.5,
            'price_change_30d' => 2.5,
            'demand_index' => 85,
            'listing_count' => 100
        ]);

        ListingVelocityProjection::create([
            'listing_id' => $ilan->id,
            'view_count' => 120,
            'favorite_count' => 10,
            'inquiry_count' => 2,
            'share_count' => 5,
            'activity_score' => 70
        ]);

        // Payload Fetch Test
        $response = $this->actingAs($user)->getJson(route('advisor.seller-strategy.fetch', ['listing' => $ilan->id]));
        $response->assertSuccessful();

        $response->assertJsonStructure([
            'success',
            'data' => [
                'listing_id',
                'listing_title',
                'current_price',
                'price_strategy_score',
                'pricing_strategy',
                'recommended_price_range' => [
                    'min',
                    'max',
                    'target'
                ],
                'estimated_sale_velocity',
                'risk_signal',
                'advisor_recommendation',
                'signals'
            ]
        ]);

        // View Test
        $htmlResponse = $this->actingAs($user)->get(route('advisor.seller-strategy', ['listing' => $ilan->id]));
        $htmlResponse->assertSuccessful();
    }
}
