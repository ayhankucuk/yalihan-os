<?php

namespace Tests\Feature\AI;

use App\Models\Ilan;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\PortfolioDoctorService;
use Tests\TestCase;

class PortfolioDoctorEngineTest extends TestCase
{
    /** @test */
    public function it_calculates_listing_health_score_correctly()
    {
        $service = new PortfolioDoctorService();

        $signals = [
            'seo_visibility_score' => 80,
            'image_quality_score' => 90,
            'regional_demand_score' => 70,
            'buyer_match_density' => 60,
            'inquiry_conversion_rate' => 50,
            'price_position_index' => 40,
            'listing_view_velocity' => 30,
        ];

        $score = $service->calculateListingHealthScore($signals);

        $expected = (80 * 0.15) + (90 * 0.10) + (70 * 0.20) + (60 * 0.15) + (50 * 0.15) + (40 * 0.15) + (30 * 0.10);

        $this->assertEquals(round($expected, 1), $score);
    }

    /** @test */
    public function it_detects_problem_categories_correctly()
    {
        $service = new PortfolioDoctorService();

        // High score = HEALTHY
        $this->assertEquals('HEALTHY', $service->detectPrimaryProblem([
            'listing_age_days' => 10, 'price_position_index' => 60, 'buyer_match_density' => 50,
            'inquiry_conversion_rate' => 50, 'regional_demand_score' => 50, 'seo_visibility_score' => 50, 'image_quality_score' => 50
        ], 85));

        // STALE_LISTING
        $this->assertEquals('STALE_LISTING', $service->detectPrimaryProblem([
            'listing_age_days' => 65, 'price_position_index' => 60, 'buyer_match_density' => 50,
            'inquiry_conversion_rate' => 50, 'regional_demand_score' => 50, 'seo_visibility_score' => 50, 'image_quality_score' => 50
        ], 50));

        // OVERPRICED
        $this->assertEquals('OVERPRICED', $service->detectPrimaryProblem([
            'listing_age_days' => 10, 'price_position_index' => 20, 'buyer_match_density' => 50,
            'inquiry_conversion_rate' => 50, 'regional_demand_score' => 50, 'seo_visibility_score' => 50, 'image_quality_score' => 50
        ], 50));
    }

    /** @test */
    public function it_generates_optimization_suggestions()
    {
        $service = new PortfolioDoctorService();

        $action = $service->generateOptimizationActions('OVERPRICED', []);
        $this->assertEquals('PRICE_ADJUSTMENT', $action['action_type']);
        $this->assertEquals('HIGH', $action['impact']);
    }

    /** @test */
    public function it_has_a_valid_thin_controller_contract()
    {
        Role::firstOrCreate(['id' => 1], ['name' => 'admin']);
        $user = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $ilan = Ilan::factory()->create(['yayin_durumu' => 'yayinda']);

        $response = $this->actingAs($user)->getJson(route('advisor.portfolio-doctor.fetch', ['listing_id' => $ilan->id]));

        $response->assertSuccessful();
    }
}
