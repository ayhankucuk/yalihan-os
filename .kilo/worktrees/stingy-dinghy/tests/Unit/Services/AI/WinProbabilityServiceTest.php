<?php

namespace Tests\Unit\Services\AI;

use App\Models\Lead;
use App\Services\AI\WinProbabilityService;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost class: App\Services\AI\WinProbabilityService henüz implement edilmedi.
 */
class WinProbabilityServiceTest extends TestCase
{
    private WinProbabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('WinProbabilityService is not implemented yet.');
        $this->service = new WinProbabilityService();
    }

    /** @test */
    public function it_calculates_probability_based_on_factors()
    {
        $lead = new Lead([
            'budget_max' => 10000000,
            'interested_location_id' => 1,
            'email' => 'test@example.com',
            'phone' => '5551234567',
            'platform' => 'whatsapp',
            'intent' => 'buy',
        ]);

        // Sentiment 8/10 -> (8/10)*40 = 32
        // Profile (Budget+Loc+Contact) -> 15+10+5 = 30
        // Activity (Whatsapp+Buy) -> 15+15 = 30
        // Total = 92
        $probability = $this->service->calculate($lead, 8);

        $this->assertEquals(92, $probability);
    }

    /** @test */
    public function it_handles_missing_data_gracefully()
    {
        $lead = new Lead([
            'platform' => 'instagram', // not in fast response list
             // intent missing
        ]);

        // Sentiment Default 5 -> 20
        // Profile 0
        // Activity 0
        // Total 20
        $probability = $this->service->calculate($lead);

        $this->assertEquals(20, $probability);
    }
}
