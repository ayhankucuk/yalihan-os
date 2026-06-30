<?php

namespace Tests\Feature;

use App\Services\AI\OwnerDiscoveryService;
use Tests\TestCase;

class OwnerDiscoveryTest extends TestCase
{
    protected OwnerDiscoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OwnerDiscoveryService();
    }

    /**
     * Test: owner profile classification
     */
    public function test_owner_profile_classification()
    {
        // INDIVIDUAL_SELLER
        $profile = $this->service->generateOwnerProfile(['listing_count' => 1, 'ilan_sahibi_type' => 'sahibinden']);
        $this->assertEquals('INDIVIDUAL_SELLER', $profile);

        // INVESTOR
        $profile = $this->service->generateOwnerProfile(['listing_count' => 3, 'ilan_sahibi_type' => 'sahibinden']);
        $this->assertEquals('INVESTOR', $profile);

        // DEVELOPER
        $profile = $this->service->generateOwnerProfile(['listing_count' => 5, 'ilan_sahibi_type' => 'sahibinden']);
        $this->assertEquals('DEVELOPER', $profile);

        // AGENT_LIKE
        $profile = $this->service->generateOwnerProfile(['listing_count' => 2, 'ilan_sahibi_type' => 'emlakci']);
        $this->assertEquals('AGENT_LIKE', $profile);
    }

    /**
     * Test: owner acquisition score calculation
     */
    public function test_calculate_acquisition_score()
    {
        // Score = (25 * 0.25) + (30*2 * 0.25) + (2*20 * 0.20) + (10 * 0.15) + (80 * 0.15)
        // Score = 6.25 + 15 + 8 + 1.5 + 12 = 42.75
        $signals = [
            'listing_count' => 1,
            'average_days_on_market' => 30, // * 2 = 60
            'price_drop_behavior' => 2, // * 20 = 40
            'unsold_ratio' => 0.10, // * 100 = 10
            'market_demand_overlap' => 0.80 // * 100 = 80
        ];

        $score = $this->service->calculateOwnerAcquisitionScore($signals);
        $this->assertEquals(42.75, $score);
    }

    /**
     * Test: owner tier classification
     */
    public function test_determine_owner_tier()
    {
        $this->assertEquals('PRIME_OWNER_TARGET', $this->service->determineOwnerTier(95));
        $this->assertEquals('HIGH_VALUE_OWNER', $this->service->determineOwnerTier(80));
        $this->assertEquals('MEDIUM_OPPORTUNITY', $this->service->determineOwnerTier(65));
        $this->assertEquals('LOW_PRIORITY', $this->service->determineOwnerTier(50));
    }
}
