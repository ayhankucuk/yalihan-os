<?php

namespace Tests\Unit\Services\Publishing;

use App\Contracts\Publishing\ChannelAdapterContract;
use App\DTOs\Publishing\PublishingDecisionDTO;
use PHPUnit\Framework\TestCase;

/**
 * Publishing DTO Unit Tests — Sprint 6.5
 *
 * Pure DTO testleri — Eloquent gerektirmez.
 */
class PublishingDTOTest extends TestCase
{
    // ─── PublishingDecisionDTO ──────────────────────────────────────────────

    public function test_publishing_decision_dto_approved(): void
    {
        $dto = new PublishingDecisionDTO(
            decision: 'approved',
            publishTargets: ['airbnb', 'sahibinden'],
            qualityTier: 'premium',
            overallScore: 0.85,
            confidence: 0.92,
        );

        $this->assertTrue($dto->isApproved());
        $this->assertFalse($dto->isRejected());
        $this->assertFalse($dto->needsReview());
        $this->assertTrue($dto->shouldPublishToChannel('airbnb'));
        $this->assertFalse($dto->shouldPublishToChannel('hepsiemlak'));
    }

    public function test_publishing_decision_dto_rejected(): void
    {
        $dto = new PublishingDecisionDTO(
            decision: 'rejected',
            publishTargets: [],
            qualityTier: 'low',
            overallScore: 0.30,
            confidence: 0.95,
            blockingIssues: [['type' => 'missing_photos', 'message' => 'Fotoğraf yok', 'severity' => 'error']],
        );

        $this->assertTrue($dto->isRejected());
        $this->assertFalse($dto->isApproved());
        $this->assertFalse($dto->shouldPublishToChannel('airbnb'));
        $this->assertCount(1, $dto->blockingIssues);
    }

    public function test_publishing_decision_dto_needs_review(): void
    {
        $dto = new PublishingDecisionDTO(
            decision: 'needs_review',
            publishTargets: ['sahibinden'],
            qualityTier: 'standard',
            overallScore: 0.65,
            confidence: 0.60,
        );

        $this->assertTrue($dto->needsReview());
        $this->assertFalse($dto->isApproved());
        $this->assertFalse($dto->shouldPublishToChannel('airbnb'));
        $this->assertFalse($dto->shouldPublishToChannel('sahibinden'));
    }

    public function test_publishing_decision_dto_to_array(): void
    {
        $dto = new PublishingDecisionDTO(
            decision: 'approved',
            publishTargets: ['airbnb'],
            qualityTier: 'premium_plus',
            overallScore: 0.95,
            confidence: 0.88,
        );

        $arr = $dto->toArray();

        $this->assertEquals('approved', $arr['decision']);
        $this->assertEquals(['airbnb'], $arr['publish_targets']);
        $this->assertEquals('premium_plus', $arr['quality_tier']);
        $this->assertEquals(0.95, $arr['overall_score']);
        $this->assertEquals(0.88, $arr['confidence']);
    }

    // ─── ChannelPayloadDTO ─────────────────────────────────────────────────

    public function test_channel_payload_dto_valid(): void
    {
        $dto = new \App\DTOs\Publishing\ChannelPayloadDTO(
            channel: 'airbnb',
            ilanId: 123,
            mappedFields: ['listing_name' => 'Bodrum Villa'],
            photos: [['url' => 'https://example.com/photo1.jpg', 'primary' => true]],
            pricing: ['amount' => 5000.0, 'currency' => 'TRY'],
        );

        $this->assertFalse($dto->hasErrors());
        $this->assertNull($dto->errorSummary());
        $this->assertEquals('airbnb', $dto->channel);
        $this->assertEquals(123, $dto->ilanId);
    }

    public function test_channel_payload_dto_with_errors(): void
    {
        $dto = new \App\DTOs\Publishing\ChannelPayloadDTO(
            channel: 'sahibinden',
            ilanId: 456,
            mappedFields: [],
            errors: ['Eksik zorunlu alanlar: baslik', 'Fiyat tanımlanmamış'],
        );

        $this->assertTrue($dto->hasErrors());
        $this->assertStringContainsString('baslik', $dto->errorSummary());
        $this->assertCount(2, $dto->errors);
    }

    public function test_channel_payload_dto_to_array(): void
    {
        $dto = new \App\DTOs\Publishing\ChannelPayloadDTO(
            channel: 'hepsiemlak',
            ilanId: 789,
            mappedFields: ['baslik' => 'Test'],
            seo: ['title' => 'Test', 'description' => 'Desc'],
            pricing: ['fiyat' => 2000],
            errors: [],
        );

        $arr = $dto->toArray();

        $this->assertEquals('hepsiemlak', $arr['channel']);
        $this->assertEquals(789, $arr['ilan_id']);
        $this->assertTrue($arr['is_valid']);
        $this->assertArrayHasKey('generated_at', $arr);
    }

    // ─── ChannelReadinessItem ───────────────────────────────────────────────

    public function test_channel_readiness_item_ready(): void
    {
        $item = new \App\DTOs\Publishing\ChannelReadinessItem(
            channel: 'airbnb',
            isReady: true,
            missingFields: [],
            score: 100,
        );

        $this->assertTrue($item->isReady());
        $this->assertEquals(100, $item->score);
        $this->assertEmpty($item->missingFields);
        $this->assertEquals('airbnb', $item->toArray()['channel']);
    }

    public function test_channel_readiness_item_not_ready(): void
    {
        $item = new \App\DTOs\Publishing\ChannelReadinessItem(
            channel: 'sahibinden',
            isReady: false,
            missingFields: ['baslik', 'fiyat'],
            warnings: ['Fotoğraf eksik'],
            score: 60,
        );

        $this->assertFalse($item->isReady());
        $this->assertCount(2, $item->missingFields);
        $this->assertCount(1, $item->warnings);
        $this->assertEquals(60, $item->score);
    }

    // ─── ChannelReadinessDTO ───────────────────────────────────────────────

    public function test_channel_readiness_dto(): void
    {
        $items = [
            'airbnb' => new \App\DTOs\Publishing\ChannelReadinessItem(channel: 'airbnb', isReady: true, score: 100),
            'sahibinden' => new \App\DTOs\Publishing\ChannelReadinessItem(channel: 'sahibinden', isReady: false, missingFields: ['baslik'], score: 50),
        ];

        $dto = new \App\DTOs\Publishing\ChannelReadinessDTO(
            ilanId: 123,
            channels: $items,
            globalIssues: ['Fiyat tanımlanmamış'],
            isGloballyReady: false,
        );

        $this->assertTrue($dto->isReady('airbnb'));
        $this->assertFalse($dto->isReady('sahibinden'));
        $this->assertFalse($dto->isReady('hepsiemlak'));
        $this->assertCount(1, $dto->readyChannels());
        $this->assertCount(1, $dto->globalIssues);
    }

    public function test_channel_readiness_dto_to_array(): void
    {
        $items = [
            'airbnb' => new \App\DTOs\Publishing\ChannelReadinessItem(channel: 'airbnb', isReady: true, score: 100),
        ];

        $dto = new \App\DTOs\Publishing\ChannelReadinessDTO(
            ilanId: 1,
            channels: $items,
            globalIssues: [],
            isGloballyReady: true,
        );

        $arr = $dto->toArray();

        $this->assertEquals(1, $arr['ilan_id']);
        $this->assertEquals(1, $arr['ready_channel_count']);
        $this->assertEquals(1, $arr['total_channel_count']);
        $this->assertTrue($arr['is_globally_ready']);
        $this->assertArrayHasKey('assessed_at', $arr);
    }
}
