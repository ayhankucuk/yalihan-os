<?php

namespace Tests\Feature\AI;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AI\ConversationalAdvisorService;
use App\Models\User;

class ConversationalAdvisorTest extends TestCase
{

    protected ConversationalAdvisorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolve the service with real/mocked dependencies
        $this->service = app(ConversationalAdvisorService::class);
    }

    public function test_service_detects_market_valuation_intent()
    {
        $query = "Bodrum yalıkavak 500m2 arsa kaç para eder?";
        $response = $this->service->processQuery($query);

        $this->assertTrue($response['is_success']);
        $this->assertEquals('MARKET_VALUATION', $response['intent_detected']);

        $this->assertArrayHasKey('entities_parsed', $response);
        // "Bodrum yalıkavak" is not mapped explicitly in the dummy NLP, but m2 is:
        $this->assertEquals(500, $response['entities_parsed']['m2_brut']);
    }

    public function test_service_detects_market_intelligence_intent()
    {
        $query = "Çeşme bölgesinde piyasa trendi nasıl?";
        $response = $this->service->processQuery($query);

        $this->assertTrue($response['is_success']);
        $this->assertEquals('MARKET_INTELLIGENCE', $response['intent_detected']);
    }

    public function test_service_detects_unknown_intent()
    {
        $query = "Merhaba naber?";
        $response = $this->service->processQuery($query);

        $this->assertTrue($response['is_success']);
        $this->assertEquals('UNKNOWN', $response['intent_detected']);
    }

    public function test_advisor_endpoint_resolves()
    {
        $advisor = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($advisor)->get('/advisor/conversational');

        $response->assertStatus(200);
        $response->assertSee('AI Danışman');
    }
}
