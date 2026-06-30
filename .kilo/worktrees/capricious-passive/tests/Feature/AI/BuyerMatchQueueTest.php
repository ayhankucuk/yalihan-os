<?php

namespace Tests\Feature\AI;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Services\AI\BuyerMatchQueueService;
use Exception;

class BuyerMatchQueueTest extends TestCase
{

    protected BuyerMatchQueueService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // This validates that the module logic does not crash and passes basic mock rules.
        // We mock actual ML models to simulate expected CQRS projection queries.
        $this->service = app(BuyerMatchQueueService::class);
    }

    public function test_service_returns_structured_matches()
    {
        // Use an actual Ilan factory
        $ilan = \App\Models\Ilan::factory()->create();

        $matches = $this->service->getMatchesForQueue($ilan);

        // Assert structure returns a valid queue array
        $this->assertIsArray($matches);

        // Assert we get the base keys according to BuyerMatchQueueService structure
        $this->assertArrayHasKey('total_matches', $matches);
        $this->assertArrayHasKey('matches', $matches);
        $this->assertArrayHasKey('listing', $matches);
    }

    public function test_advisor_endpoint_returns_json_or_view()
    {
        $advisor = User::factory()->create(['role_id' => 2]); // Using role_id 2 for advisor
        $ilan = \App\Models\Ilan::factory()->create(['danisman_id' => $advisor->id]);

        $response = $this->actingAs($advisor)->get("/advisor/listings/{$ilan->id}/buyer-matches");

        // Verify the interface is mounted without 500 crashes
        $response->assertStatus(200);
    }
}
