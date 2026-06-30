<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Lead;
use App\Models\Ilan;
use App\Models\LeadEmbedding;
use App\Services\CRMIntelligenceService;
use App\Services\AI\SemanticSearchService;
use App\Services\AI\EmbeddingService;
use Mockery;
use Illuminate\Support\Facades\DB;

class CRMIntelligenceServiceTest extends TestCase
{

    protected $crmIntelligenceService;
    protected $semanticSearchMock;
    protected $embeddingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semanticSearchMock = Mockery::mock(SemanticSearchService::class);
        $this->embeddingServiceMock = Mockery::mock(EmbeddingService::class);

        $this->crmIntelligenceService = new CRMIntelligenceService(
            $this->semanticSearchMock,
            Mockery::mock(EmbeddingService::class)
        );
    }

    /** @test */
    public function it_can_sync_a_lead_embedding()
    {
        $lead = Lead::create([
            'name' => 'John Doe',
            'phone' => '5551234567',
            'platform' => 'whatsapp',
            'platform_user_id' => 'wa_123',
            'intent' => 'Buy a luxury villa',
            'first_message' => 'Looking for Yalikavak area',
            'crm_durumu' => 0,
            'aktif' => true
        ]);

        $fakeEmbedding = array_fill(0, 768, 0.1);

        $this->semanticSearchMock
            ->shouldReceive('generateEmbedding')
            ->once()
            ->andReturn($fakeEmbedding);

        $result = $this->crmIntelligenceService->syncLead($lead);

        $this->assertTrue($result);
        $this->assertDatabaseHas('lead_embeddings', [
            'lead_id' => $lead->id,
            'dimensions' => 768
        ]);
    }

    /** @test */
    public function it_calculates_lead_priority_correctly()
    {
        $lead = Lead::create([
            'name' => 'Priority Test',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'budget_max' => 15000000,
            'interested_property_type' => 'Villa',
            'platform' => 'whatsapp',
            'platform_user_id' => 'wa_456',
            'crm_durumu' => 0,
            'aktif' => true
        ]);

        // Mock recommended listings to get match score
        $score = $this->crmIntelligenceService->calculateLeadPriority($lead);

        // email(10) + phone(10) + property_type(10) + budget(30) = 60
        $this->assertGreaterThanOrEqual(60, $score);
    }
}
