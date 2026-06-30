<?php

namespace Tests\Unit\MarketIntelligence;

use App\DTOs\MarketIntelligence\AdvisorInsightDTO;
use App\Services\AIService;
use App\Services\MarketIntelligence\AdvisorAssistantService;
use PHPUnit\Framework\TestCase;

class AdvisorAssistantServiceTest extends TestCase
{
    private AdvisorAssistantService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AIService — hiçbir zaman gerçek AI çağrısı yapma
        $mockAI = $this->createMock(AIService::class);
        $mockAI->method('generate')->willReturn(json_encode([
            'summary' => 'Fiyat piyasa ortalamasının altında.',
            'reasoning' => 'Benchmark altı fiyat ve aktif talep bunu destekliyor.',
            'recommended_action' => 'İlanı takip listesine alın.',
            'urgency' => 'HIGH',
            'risk_note' => '',
        ]));

        $this->service = new AdvisorAssistantService($mockAI);
    }

    // ── OUTPUT JSON VALID ──

    public function test_generate_returns_advisor_insight_dto(): void
    {
        $result = $this->service->generate($this->makeBuyPayload());

        $this->assertInstanceOf(AdvisorInsightDTO::class, $result);
    }

    public function test_dto_to_array_is_valid_json_structure(): void
    {
        $result = $this->service->generate($this->makeBuyPayload());
        $arr = $result->toArray();

        $this->assertIsArray($arr);
        $json = json_encode($arr);
        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);
        $this->assertSame($arr, $decoded);
    }

    // ── FIELDS PRESENT ──

    public function test_all_required_fields_present_in_output(): void
    {
        $result = $this->service->generate($this->makeBuyPayload());
        $arr = $result->toArray();

        $required = ['summary', 'reasoning', 'recommended_action', 'urgency', 'risk_note'];
        foreach ($required as $field) {
            $this->assertArrayHasKey($field, $arr, "Missing field: {$field}");
        }
    }

    public function test_urgency_is_valid_value(): void
    {
        $result = $this->service->generate($this->makeBuyPayload());

        $this->assertContains($result->urgency, ['LOW', 'MEDIUM', 'HIGH']);
    }

    // ── NO HALLUCINATED FIELDS ──

    public function test_output_has_no_extra_fields(): void
    {
        $result = $this->service->generate($this->makeBuyPayload());
        $arr = $result->toArray();

        $allowed = ['summary', 'reasoning', 'recommended_action', 'urgency', 'risk_note'];
        $extra = array_diff(array_keys($arr), $allowed);

        $this->assertEmpty($extra, 'DTO has hallucinated fields: ' . implode(', ', $extra));
    }

    // ── VALIDATE OUTPUT ──

    public function test_validate_rejects_missing_field(): void
    {
        $incomplete = [
            'summary' => 'Özet',
            'reasoning' => 'Gerekçe',
            // missing recommended_action, urgency, risk_note
        ];

        $this->assertFalse($this->service->validateOutput($incomplete));
    }

    public function test_validate_rejects_empty_summary(): void
    {
        $parsed = [
            'summary' => '',
            'reasoning' => 'Gerekçe',
            'recommended_action' => 'Aksiyon',
            'urgency' => 'HIGH',
            'risk_note' => '',
        ];

        $this->assertFalse($this->service->validateOutput($parsed));
    }

    public function test_validate_rejects_invalid_urgency(): void
    {
        $parsed = [
            'summary' => 'Özet',
            'reasoning' => 'Gerekçe',
            'recommended_action' => 'Aksiyon',
            'urgency' => 'URGENT',  // invalid
            'risk_note' => '',
        ];

        $this->assertFalse($this->service->validateOutput($parsed));
    }

    public function test_validate_rejects_hallucinated_fields(): void
    {
        $parsed = [
            'summary' => 'Özet',
            'reasoning' => 'Gerekçe',
            'recommended_action' => 'Aksiyon',
            'urgency' => 'HIGH',
            'risk_note' => '',
            'score' => 85,  // hallucinated
        ];

        $this->assertFalse($this->service->validateOutput($parsed));
    }

    public function test_validate_accepts_valid_output(): void
    {
        $parsed = [
            'summary' => 'Fiyat piyasa ile uyumlu.',
            'reasoning' => 'Benchmark verisi yeterli.',
            'recommended_action' => 'İzlemeye alın.',
            'urgency' => 'LOW',
            'risk_note' => '',
        ];

        $this->assertTrue($this->service->validateOutput($parsed));
    }

    // ── PARSE RESPONSE ──

    public function test_parse_valid_json_string(): void
    {
        $json = '{"summary":"Test","reasoning":"R","recommended_action":"A","urgency":"LOW","risk_note":""}';
        $result = $this->service->parseResponse($json);

        $this->assertSame('Test', $result['summary']);
    }

    public function test_parse_json_with_markdown_fence(): void
    {
        $raw = "```json\n{\"summary\":\"Test\",\"reasoning\":\"R\",\"recommended_action\":\"A\",\"urgency\":\"LOW\",\"risk_note\":\"\"}\n```";
        $result = $this->service->parseResponse($raw);

        $this->assertSame('Test', $result['summary']);
    }

    public function test_parse_invalid_string_returns_empty(): void
    {
        $result = $this->service->parseResponse('not json at all');
        $this->assertEmpty($result);
    }

    public function test_parse_array_returns_as_is(): void
    {
        $input = ['summary' => 'Test'];
        $result = $this->service->parseResponse($input);
        $this->assertSame($input, $result);
    }

    // ── SANITIZE PAYLOAD ──

    public function test_sanitize_removes_unauthorized_fields(): void
    {
        $payload = [
            'pricing_position' => 'fair',
            'confidence_label' => 'HIGH',
            'secret_field' => 'hack',
            'db_password' => '123',
        ];

        $result = $this->service->sanitizePayload($payload);

        $this->assertArrayHasKey('pricing_position', $result);
        $this->assertArrayHasKey('confidence_label', $result);
        $this->assertArrayNotHasKey('secret_field', $result);
        $this->assertArrayNotHasKey('db_password', $result);
    }

    public function test_sanitize_preserves_all_allowed_fields(): void
    {
        $payload = [
            'pricing_position' => 'overpriced',
            'pricing_score' => 35,
            'confidence_label' => 'MEDIUM',
            'confidence_score' => 55,
            'demand_label' => 'SLOW',
            'opportunity_action' => 'SELL',
            'priority_label' => 'HIGH',
            'queue_type' => 'PRICE_REVIEW',
            'days_on_market' => 60,
            'price' => 5000000,
            'benchmark_price' => 4000000,
        ];

        $result = $this->service->sanitizePayload($payload);
        $this->assertCount(11, $result);
    }

    // ── DETERMINISTIC FALLBACK ──

    public function test_fallback_buy_action_returns_valid_dto(): void
    {
        $result = $this->service->buildFallback([
            'opportunity_action' => 'BUY',
            'pricing_position' => 'underpriced',
            'confidence_label' => 'HIGH',
            'demand_label' => 'HOT',
            'priority_label' => 'CRITICAL',
        ]);

        $this->assertInstanceOf(AdvisorInsightDTO::class, $result);
        $this->assertSame('HIGH', $result->urgency);
        $this->assertNotEmpty($result->summary);
        $this->assertNotEmpty($result->reasoning);
        $this->assertNotEmpty($result->recommended_action);
    }

    public function test_fallback_sell_action(): void
    {
        $result = $this->service->buildFallback([
            'opportunity_action' => 'SELL',
            'pricing_position' => 'overpriced',
            'confidence_label' => 'MEDIUM',
            'demand_label' => 'SLOW',
            'priority_label' => 'HIGH',
        ]);

        $this->assertStringContainsString('revizyon', mb_strtolower($result->recommended_action));
        $this->assertSame('HIGH', $result->urgency);
    }

    public function test_fallback_wait_action(): void
    {
        $result = $this->service->buildFallback([
            'opportunity_action' => 'WAIT',
            'pricing_position' => 'fair',
            'confidence_label' => 'HIGH',
            'demand_label' => 'ACTIVE',
            'priority_label' => 'MEDIUM',
        ]);

        $this->assertSame('MEDIUM', $result->urgency);
        $this->assertStringContainsString('izleme', mb_strtolower($result->recommended_action));
    }

    public function test_fallback_insufficient_data(): void
    {
        $result = $this->service->buildFallback([
            'opportunity_action' => 'INSUFFICIENT_DATA',
            'pricing_position' => 'insufficient_data',
            'confidence_label' => 'VERY_LOW',
            'demand_label' => 'WEAK',
            'priority_label' => 'LOW',
        ]);

        $this->assertSame('LOW', $result->urgency);
        $this->assertStringContainsString('yetersiz', mb_strtolower($result->reasoning));
    }

    public function test_fallback_risk_note_on_low_confidence(): void
    {
        $result = $this->service->buildFallback([
            'opportunity_action' => 'WAIT',
            'confidence_label' => 'LOW',
            'priority_label' => 'LOW',
        ]);

        $this->assertNotEmpty($result->risk_note);
        $this->assertStringContainsString('düşük', mb_strtolower($result->risk_note));
    }

    public function test_fallback_risk_note_on_long_days_on_market(): void
    {
        $result = $this->service->buildFallback([
            'opportunity_action' => 'SELL',
            'confidence_label' => 'HIGH',
            'priority_label' => 'HIGH',
            'days_on_market' => 120,
        ]);

        $this->assertStringContainsString('120', $result->risk_note);
    }

    public function test_fallback_deterministic_same_input_same_output(): void
    {
        $payload = [
            'opportunity_action' => 'BUY',
            'pricing_position' => 'underpriced',
            'confidence_label' => 'HIGH',
            'demand_label' => 'HOT',
            'priority_label' => 'CRITICAL',
        ];

        $r1 = $this->service->buildFallback($payload);
        $r2 = $this->service->buildFallback($payload);

        $this->assertSame($r1->toArray(), $r2->toArray());
    }

    // ── AI FAILURE → FALLBACK ──

    public function test_ai_failure_triggers_fallback(): void
    {
        $failingAI = $this->createMock(AIService::class);
        $failingAI->method('generate')->willThrowException(new \RuntimeException('API down'));

        $service = new AdvisorAssistantService($failingAI);

        $result = $service->generate([
            'opportunity_action' => 'BUY',
            'pricing_position' => 'underpriced',
            'confidence_label' => 'HIGH',
            'demand_label' => 'HOT',
            'priority_label' => 'CRITICAL',
        ]);

        $this->assertInstanceOf(AdvisorInsightDTO::class, $result);
        $this->assertNotEmpty($result->summary);
    }

    public function test_invalid_ai_response_triggers_fallback(): void
    {
        $badAI = $this->createMock(AIService::class);
        $badAI->method('generate')->willReturn('garbled nonsense {{{');

        $service = new AdvisorAssistantService($badAI);

        $result = $service->generate([
            'opportunity_action' => 'SELL',
            'pricing_position' => 'overpriced',
            'confidence_label' => 'MEDIUM',
            'demand_label' => 'SLOW',
            'priority_label' => 'HIGH',
        ]);

        $this->assertInstanceOf(AdvisorInsightDTO::class, $result);
        $this->assertNotEmpty($result->summary);
    }

    // ── PROMPT BUILDING ──

    public function test_system_prompt_contains_guard_rules(): void
    {
        $prompt = $this->service->buildSystemPrompt();

        $this->assertStringContainsString('DO NOT generate scores', $prompt);
        $this->assertStringContainsString('DO NOT override', $prompt);
        $this->assertStringContainsString('JSON', $prompt);
    }

    public function test_user_prompt_contains_payload_values(): void
    {
        $prompt = $this->service->buildUserPrompt([
            'pricing_position' => 'overpriced',
            'pricing_score' => 35,
            'confidence_label' => 'MEDIUM',
            'opportunity_action' => 'SELL',
        ]);

        $this->assertStringContainsString('overpriced', $prompt);
        $this->assertStringContainsString('35', $prompt);
        $this->assertStringContainsString('MEDIUM', $prompt);
        $this->assertStringContainsString('SELL', $prompt);
    }

    // ── EMPTY PAYLOAD ──

    public function test_empty_payload_still_returns_valid_dto(): void
    {
        $failingAI = $this->createMock(AIService::class);
        $failingAI->method('generate')->willReturn('invalid');

        $service = new AdvisorAssistantService($failingAI);
        $result = $service->generate([]);

        $this->assertInstanceOf(AdvisorInsightDTO::class, $result);
        $this->assertContains($result->urgency, ['LOW', 'MEDIUM', 'HIGH']);
    }

    // ── CURRENT_PRICE → PRICE MAPPING ──

    public function test_sanitize_maps_current_price_to_price(): void
    {
        $payload = [
            'current_price' => 5000000,
            'benchmark_price' => 4000000,
            'pricing_position' => 'overpriced',
        ];

        $result = $this->service->sanitizePayload($payload);

        $this->assertArrayHasKey('price', $result);
        $this->assertSame(5000000, $result['price']);
        $this->assertArrayNotHasKey('current_price', $result);
    }

    public function test_sanitize_does_not_override_explicit_price(): void
    {
        $payload = [
            'current_price' => 5000000,
            'price' => 3000000,
            'pricing_position' => 'fair',
        ];

        $result = $this->service->sanitizePayload($payload);

        $this->assertSame(3000000, $result['price']);
    }

    public function test_pricing_insight_dto_payload_gets_price_in_prompt(): void
    {
        // Simulates what IlanCrudController sends: PricingInsightDTO::toArray()
        $dtoPayload = [
            'ilan_id' => 1,
            'current_price' => 5000000,
            'benchmark_price' => 4000000,
            'pricing_position' => 'overpriced',
            'pricing_score' => 35,
            'confidence_label' => 'MEDIUM',
            'demand_label' => 'SLOW',
            'opportunity_action' => 'SELL',
        ];

        $sanitized = $this->service->sanitizePayload($dtoPayload);
        $prompt = $this->service->buildUserPrompt($sanitized);

        $this->assertStringContainsString('5000000', $prompt);
        $this->assertStringContainsString('Price: 5000000', $prompt); // price mapped from current_price
    }

    // ── CACHE KEY ──

    public function test_cache_key_deterministic(): void
    {
        $payload = ['pricing_position' => 'fair', 'confidence_label' => 'HIGH'];

        $key1 = $this->service->buildCacheKey($payload);
        $key2 = $this->service->buildCacheKey($payload);

        $this->assertSame($key1, $key2);
        $this->assertStringStartsWith('mie_advisor_', $key1);
    }

    public function test_cache_key_different_for_different_payloads(): void
    {
        $key1 = $this->service->buildCacheKey(['pricing_position' => 'fair']);
        $key2 = $this->service->buildCacheKey(['pricing_position' => 'overpriced']);

        $this->assertNotSame($key1, $key2);
    }

    public function test_cache_key_order_independent(): void
    {
        $key1 = $this->service->buildCacheKey(['pricing_position' => 'fair', 'demand_label' => 'HOT']);
        $key2 = $this->service->buildCacheKey(['demand_label' => 'HOT', 'pricing_position' => 'fair']);

        $this->assertSame($key1, $key2);
    }

    // ── Helper ──

    private function makeBuyPayload(): array
    {
        return [
            'pricing_position' => 'underpriced',
            'pricing_score' => 82,
            'confidence_label' => 'HIGH',
            'confidence_score' => 78,
            'demand_label' => 'HOT',
            'opportunity_action' => 'BUY',
            'priority_label' => 'CRITICAL',
            'queue_type' => 'OPPORTUNITY_FOLLOWUP',
            'days_on_market' => 15,
            'price' => 3500000,
            'benchmark_price' => 4200000,
        ];
    }
}
