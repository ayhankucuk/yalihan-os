<?php

namespace Tests\Unit\DTOs;

use App\DTOs\MarketIntelligence\AdvisorInsightDTO;
use App\DTOs\MarketIntelligence\PricingInsightDTO;
use App\Enums\MarketIntelligence\PricingPosition;
use PHPUnit\Framework\TestCase;

/**
 * DTO Contract Tests — MIE Risk 5
 *
 * Bu testler DTO → toArray() → downstream chain'deki field isimlerinin
 * değişmediğini garanti eder. Field ekleme/çıkarma olursa test patlar.
 *
 * Sessiz field drift'i önler (silent bug prevention).
 */
class DtoContractTest extends TestCase
{
    /** PricingInsightDTO::toArray() field sözleşmesi */
    public function test_pricing_insight_dto_contract_fields(): void
    {
        $dto = new PricingInsightDTO(
            ilan_id: 1,
            current_price: 1000000,
            benchmark_price: 950000,
            benchmark_min: 800000,
            benchmark_max: 1200000,
            sample_size: 12,
            price_delta_percent: 5.3,
            pricing_position: PricingPosition::FAIR,
            pricing_score: 75,
            confidence: 'moderate',
            insufficient_data: false,
            reason: 'Test reason',
            confidence_score: 55,
            confidence_label: 'MODERATE',
            confidence_reason: 'Yeterli emsal sayısı',
            demand_score: 60,
            demand_label: 'ACTIVE',
            demand_reason: 'Orta talep',
            opportunity_score: 45,
            opportunity_action: 'WAIT',
            opportunity_reason: 'Bekle ve gör',
        );

        $array = $dto->toArray();

        $expectedKeys = [
            'ilan_id',
            'current_price',
            'benchmark_price',
            'benchmark_min',
            'benchmark_max',
            'sample_size',
            'price_delta_percent',
            'pricing_position',
            'pricing_position_label',
            'pricing_score',
            'confidence',
            'insufficient_data',
            'reason',
            'confidence_score',
            'confidence_label',
            'confidence_reason',
            'demand_score',
            'demand_label',
            'demand_reason',
            'opportunity_score',
            'opportunity_action',
            'opportunity_reason',
        ];

        $this->assertSame($expectedKeys, array_keys($array), 'PricingInsightDTO::toArray() field contract violated');
    }

    /** PricingInsightDTO pricing_position string çıktıyı doğrular */
    public function test_pricing_insight_dto_position_is_string_value(): void
    {
        $dto = new PricingInsightDTO(
            ilan_id: 1,
            current_price: 500000,
            benchmark_price: 500000,
            benchmark_min: 400000,
            benchmark_max: 600000,
            sample_size: 5,
            price_delta_percent: 0.0,
            pricing_position: PricingPosition::FAIR,
            pricing_score: 80,
            confidence: 'high',
            insufficient_data: false,
            reason: 'Test',
        );

        $array = $dto->toArray();
        $this->assertSame('fair', $array['pricing_position']);
        $this->assertIsString($array['pricing_position_label']);
    }

    /** AdvisorInsightDTO::toArray() field sözleşmesi */
    public function test_advisor_insight_dto_contract_fields(): void
    {
        $dto = new AdvisorInsightDTO(
            summary: 'Test özet',
            reasoning: 'Test gerekçe',
            recommended_action: 'Fiyat düşür',
            urgency: 'HIGH',
            risk_note: 'Risk test',
        );

        $array = $dto->toArray();

        $expectedKeys = [
            'summary',
            'reasoning',
            'recommended_action',
            'urgency',
            'risk_note',
        ];

        $this->assertSame($expectedKeys, array_keys($array), 'AdvisorInsightDTO::toArray() field contract violated');
    }

    /** AdvisorInsightDTO field tipleri sözleşmesi */
    public function test_advisor_insight_dto_field_types(): void
    {
        $dto = new AdvisorInsightDTO(
            summary: 'Test',
            reasoning: 'Reason',
            recommended_action: 'Action',
            urgency: 'LOW',
            risk_note: '',
        );

        $array = $dto->toArray();

        $this->assertIsString($array['summary']);
        $this->assertIsString($array['reasoning']);
        $this->assertIsString($array['recommended_action']);
        $this->assertContains($array['urgency'], ['LOW', 'MEDIUM', 'HIGH']);
        $this->assertIsString($array['risk_note']);
    }

    /** PricingInsightDTO → sanitizePayload() uyumluluk: 'current_price' key'i mevcut olmalı */
    public function test_pricing_insight_dto_has_current_price_key(): void
    {
        $dto = new PricingInsightDTO(
            ilan_id: 1,
            current_price: 750000,
            benchmark_price: 700000,
            benchmark_min: 600000,
            benchmark_max: 800000,
            sample_size: 8,
            price_delta_percent: 7.1,
            pricing_position: PricingPosition::OVERPRICED,
            pricing_score: 45,
            confidence: 'moderate',
            insufficient_data: false,
            reason: 'Test',
        );

        $array = $dto->toArray();

        // sanitizePayload() beklentisi: 'current_price' key'i mutlaka olmalı
        $this->assertArrayHasKey('current_price', $array);
        // 'price' key'i olmamalı (eski alias)
        $this->assertArrayNotHasKey('price', $array);
    }

    /** PricingInsightDTO varsayılan değerler sözleşmesi */
    public function test_pricing_insight_dto_defaults(): void
    {
        $dto = new PricingInsightDTO(
            ilan_id: 1,
            current_price: 500000,
            benchmark_price: null,
            benchmark_min: null,
            benchmark_max: null,
            sample_size: 0,
            price_delta_percent: null,
            pricing_position: PricingPosition::INSUFFICIENT_DATA,
            pricing_score: 0,
            confidence: 'very_low',
            insufficient_data: true,
            reason: 'Yetersiz veri',
        );

        $this->assertSame(0, $dto->confidence_score);
        $this->assertSame('VERY_LOW', $dto->confidence_label);
        $this->assertSame('', $dto->confidence_reason);
        $this->assertSame(0, $dto->demand_score);
        $this->assertSame('WEAK', $dto->demand_label);
        $this->assertSame('', $dto->demand_reason);
        $this->assertSame(0, $dto->opportunity_score);
        $this->assertSame('INSUFFICIENT_DATA', $dto->opportunity_action);
        $this->assertSame('', $dto->opportunity_reason);
    }
}
