<?php

namespace Tests\Feature;

use App\Services\AI\DanismanAIService;
use App\Services\Wizard\CopilotListingGenerator;
use App\Services\Wizard\EffectiveWizardSchemaResolver;
use App\Services\Wizard\PricingSuggestionService;
use Mockery;
use Tests\TestCase;

/**
 * CopilotListingGenerator — unit-level acceptance tests.
 *
 * Covers: generate() routing logic, action contract shape,
 * shouldSuggest* guards, mode behaviour, and confidence calculation.
 *
 * AI service and pricing service are always mocked to make tests
 * deterministic and independent of external providers.
 */
class CopilotListingGeneratorTest extends TestCase
{

    private CopilotListingGenerator $generator;
    private DanismanAIService $mockAiService;
    private EffectiveWizardSchemaResolver $mockSchemaResolver;
    private PricingSuggestionService $mockPricingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAiService = Mockery::mock(DanismanAIService::class);
        $this->mockSchemaResolver = Mockery::mock(EffectiveWizardSchemaResolver::class);
        $this->mockPricingService = Mockery::mock(PricingSuggestionService::class);

        $this->generator = new CopilotListingGenerator(
            $this->mockSchemaResolver,
            $this->mockAiService,
            $this->mockPricingService,
        );
    }

    // ── RESPONSE CONTRACT ────────────────────────────────────────

    /** @test */
    public function generate_always_returns_the_required_contract_keys(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')->andReturn(['success' => false]);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $result = $this->generator->generate([]);

        $this->assertArrayHasKey('actions', $result);
        $this->assertArrayHasKey('mode', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('action_count', $result['meta']);
        $this->assertArrayHasKey('duration_ms', $result['meta']);
        $this->assertArrayHasKey('generated_at', $result['meta']);
    }

    /** @test */
    public function generate_returns_empty_actions_when_all_fields_are_filled_and_ai_fails(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')->andReturn(['success' => false]);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $filledState = [
            'baslik' => 'Bodrum Merkez Satılık Lüks Daire',      // ≥15 chars → no title suggestion
            'aciklama' => str_repeat('X', 100),                    // ≥50 chars → no desc suggestion
            'fiyat' => 500000,                                      // non-zero → no pricing suggestion
        ];

        $result = $this->generator->generate($filledState);

        $this->assertEmpty($result['actions']);
        $this->assertEquals(0, $result['meta']['action_count']);
    }

    /** @test */
    public function generate_suggests_title_when_baslik_is_empty(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')
            ->once()
            ->andReturn([
                'success' => true,
                'content' => "Satılık Daire Bodrum\nLüks Daire Satılık",
            ]);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $result = $this->generator->generate([
            'baslik' => '',
            'aciklama' => str_repeat('X', 100),
            'fiyat' => 500000,
        ]);

        $titleActions = array_filter($result['actions'], fn ($a) => $a['target'] === 'baslik');

        $this->assertNotEmpty($titleActions);
        $titleAction = array_values($titleActions)[0];
        $this->assertEquals('field_autofill', $titleAction['type']);
        $this->assertEquals('baslik', $titleAction['target']);
        $this->assertNotEmpty($titleAction['value']);
        $this->assertTrue($titleAction['requires_confirmation']); // Safety: never apply without confirmation
    }

    /** @test */
    public function generate_suggests_title_when_baslik_is_too_short(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')
            ->once()
            ->andReturn(['success' => true, 'content' => 'Satılık Daire Bodrum']);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $result = $this->generator->generate([
            'baslik' => 'Daire',   // < 15 chars
            'aciklama' => str_repeat('X', 100),
            'fiyat' => 500000,
        ]);

        $titleActions = array_filter($result['actions'], fn ($a) => $a['target'] === 'baslik');
        $this->assertNotEmpty($titleActions);
    }

    /** @test */
    public function generate_does_not_suggest_title_when_baslik_is_long_enough(): void
    {
        // No shouldReceive for generateListingTitle — it must NOT be called
        $this->mockAiService->shouldNotReceive('generateListingTitle');
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $result = $this->generator->generate([
            'baslik' => 'Bodrum Merkez Satılık Konut',   // ≥15 chars
            'aciklama' => str_repeat('X', 100),
            'fiyat' => 500000,
        ]);

        $titleActions = array_filter($result['actions'], fn ($a) => $a['target'] === 'baslik');
        $this->assertEmpty($titleActions);
    }

    /** @test */
    public function generate_suggests_description_when_aciklama_is_empty(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')->andReturn(['success' => false]);
        $this->mockAiService->shouldReceive('generateListingDescription')
            ->once()
            ->andReturn([
                'success' => true,
                'content' => 'Bodrum merkezinde, denize yakın satılık daire. Güneş gören katı, yeni tadilatlı.',
            ]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $result = $this->generator->generate([
            'baslik' => 'Bodrum Merkez Satılık Konut',
            'aciklama' => '',
            'fiyat' => 500000,
        ]);

        $descActions = array_filter($result['actions'], fn ($a) => $a['target'] === 'aciklama');
        $this->assertNotEmpty($descActions);
        $descAction = array_values($descActions)[0];
        $this->assertEquals('field_autofill', $descAction['type']);
        $this->assertTrue($descAction['requires_confirmation']);
    }

    /** @test */
    public function generate_does_not_suggest_pricing_when_fiyat_is_already_set(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')->andReturn(['success' => false]);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        // pricingService must NOT be called
        $this->mockPricingService->shouldNotReceive('suggest');

        $result = $this->generator->generate([
            'baslik' => 'Bodrum Merkez Satılık Konut',
            'aciklama' => str_repeat('X', 100),
            'fiyat' => 750000,   // non-zero → no pricing
        ]);

        $priceActions = array_filter($result['actions'], fn ($a) => $a['target'] === 'fiyat');
        $this->assertEmpty($priceActions);
    }

    /** @test */
    public function generate_suggests_pricing_when_fiyat_is_empty(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')->andReturn(['success' => false]);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')
            ->once()
            ->andReturn([
                'basarili' => true,
                'suggested_price' => 3500000,
                'min_price' => 3000000,
                'max_price' => 4000000,
                'median_price' => 3500000,
                'confidence' => 0.70,
                'comparable_count' => 18,
                'para_birimi' => 'TRY',
                'reason' => '18 benzer ilana dayalı öneri',
            ]);

        $result = $this->generator->generate([
            'baslik' => 'Bodrum Merkez Satılık Konut',
            'aciklama' => str_repeat('X', 100),
            'fiyat' => 0,
            'ana_kategori_id' => 1,
            'il_id' => 48,
        ]);

        $priceActions = array_filter($result['actions'], fn ($a) => $a['target'] === 'fiyat');
        $this->assertNotEmpty($priceActions);
        $priceAction = array_values($priceActions)[0];
        $this->assertEquals('pricing_apply', $priceAction['type']);
        $this->assertEquals(3500000, $priceAction['value']);
        $this->assertTrue($priceAction['requires_confirmation']); // Safety: pricing requires confirmation
    }

    /** @test */
    public function full_generate_mode_calls_generate_with_full_generate(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')->andReturn(['success' => false]);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);
        $this->mockSchemaResolver->shouldReceive('resolve')->andReturn(['fields' => [], 'meta' => []]);

        $result = $this->generator->generateFullListing([
            'ana_kategori_id' => 1,
            'yayin_tipi_id' => 1,
        ]);

        $this->assertEquals('full_generate', $result['mode']);
    }

    /** @test */
    public function confidence_is_zero_when_no_actions_generated(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')->andReturn(['success' => false]);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $result = $this->generator->generate([
            'baslik' => 'Bodrum Merkez Satılık Konut',
            'aciklama' => str_repeat('X', 100),
            'fiyat' => 500000,
        ]);

        $this->assertEquals(0.0, $result['confidence']);
        $this->assertEmpty($result['actions']);
    }

    /** @test */
    public function each_action_has_the_required_contract_fields(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')
            ->andReturn(['success' => true, 'content' => 'Satılık Daire Bodrum Merkez İyi Konum']);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $result = $this->generator->generate([
            'baslik' => '',
            'aciklama' => str_repeat('X', 100),
            'fiyat' => 500000,
        ]);

        foreach ($result['actions'] as $action) {
            $this->assertArrayHasKey('id', $action);
            $this->assertArrayHasKey('type', $action);
            $this->assertArrayHasKey('label', $action);
            $this->assertArrayHasKey('description', $action);
            $this->assertArrayHasKey('target', $action);
            $this->assertArrayHasKey('value', $action);
            $this->assertArrayHasKey('requires_confirmation', $action);
            $this->assertArrayHasKey('confidence', $action);
            $this->assertArrayHasKey('source', $action);
        }
    }

    /** @test */
    public function actions_are_sorted_by_priority_ascending(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')
            ->andReturn(['success' => true, 'content' => 'Satılık Daire Bodrum Merkez İyi Konum']);
        $this->mockAiService->shouldReceive('generateListingDescription')
            ->andReturn(['success' => true, 'content' => str_repeat('Bodrum açıklama ', 10)]);
        $this->mockPricingService->shouldReceive('suggest')
            ->andReturn([
                'basarili' => true,
                'suggested_price' => 3500000,
                'min_price' => 3000000,
                'max_price' => 4000000,
                'median_price' => 3500000,
                'confidence' => 0.70,
                'comparable_count' => 20,
                'para_birimi' => 'TRY',
                'reason' => 'piyasa önerisi',
            ]);

        $result = $this->generator->generate([
            'baslik' => '',
            'aciklama' => '',
            'fiyat' => 0,
            'ana_kategori_id' => 1,
            'il_id' => 48,
        ]);

        $priorities = array_column($result['actions'], 'priority');
        $sorted = $priorities;
        sort($sorted);

        $this->assertEquals($sorted, $priorities, 'Actions must be sorted by ascending priority');
    }

    /** @test */
    public function auto_run_defaults_to_on_request_when_price_data_is_missing(): void
    {
        $this->mockAiService->shouldReceive('generateListingTitle')->andReturn(['success' => false]);
        $this->mockAiService->shouldReceive('generateListingDescription')->andReturn(['success' => false]);
        $this->mockPricingService->shouldReceive('suggest')->andReturn(['basarili' => false]);

        $result = $this->generator->generate([
            'ana_kategori_id' => 1,
            'yayin_tipi_id' => 1,
            'fiyat' => 0,
            'il_id' => 48,
        ], 'auto_run');

        $modeActions = array_filter($result['actions'], fn ($a) => ($a['target'] ?? null) === 'fiyat_gosterim_modu');
        $this->assertNotEmpty($modeActions);
        $this->assertEquals('on_request', array_values($modeActions)[0]['value']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
