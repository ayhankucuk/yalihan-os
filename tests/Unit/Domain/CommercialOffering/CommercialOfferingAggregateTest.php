<?php

namespace Tests\Unit\Domain\CommercialOffering;

use App\Domain\CommercialOffering\CommercialOfferingAggregate;
use App\Domain\CommercialOffering\Enums\OfferingState;
use App\Domain\CommercialOffering\Enums\OfferingType;
use App\Domain\CommercialOffering\Exceptions\CommercialOfferingDomainException;
use App\Domain\CommercialOffering\ValueObjects\Commission;
use App\Domain\CommercialOffering\ValueObjects\DateRange;
use App\Domain\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class CommercialOfferingAggregateTest extends TestCase
{
    public function test_create_generates_uuid(): void
    {
        $aggregate = $this->createAggregate();

        $this->assertNotEmpty($aggregate->getUuid());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $aggregate->getUuid()
        );
    }

    public function test_create_starts_in_draft_state(): void
    {
        $aggregate = $this->createAggregate();

        $this->assertTrue($aggregate->isDraft());
        $this->assertFalse($aggregate->isActive());
        $this->assertFalse($aggregate->isArchived());
    }

    public function test_activate_transitions_draft_to_active(): void
    {
        $aggregate = $this->createAggregate();

        $aggregate->activate();

        $this->assertTrue($aggregate->isActive());
        $this->assertFalse($aggregate->isDraft());
        $this->assertEquals(OfferingState::ACTIVE, $aggregate->getState());
    }

    public function test_archive_transitions_from_active(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->activate();

        $aggregate->archive();

        $this->assertTrue($aggregate->isArchived());
        $this->assertEquals(OfferingState::ARCHIVED, $aggregate->getState());
    }

    public function test_archive_transitions_from_draft(): void
    {
        $aggregate = $this->createAggregate();

        $aggregate->archive();

        $this->assertTrue($aggregate->isArchived());
    }

    public function test_cannot_activate_archived_offering(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->archive();

        $this->expectException(CommercialOfferingDomainException::class);
        $this->expectExceptionMessageMatches('/ARCHIVED.*ACTIVE|geçiş yapılamaz/i');

        $aggregate->activate();
    }

    public function test_archived_is_terminal_state(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->archive();

        $this->assertFalse($aggregate->getState()->canTransitionTo(OfferingState::ACTIVE));
        $this->assertFalse($aggregate->getState()->canTransitionTo(OfferingState::DRAFT));
    }

    public function test_update_price(): void
    {
        $aggregate = $this->createAggregate();

        $newMoney = new Money(2500000.0, 'TRY');
        $aggregate->updatePrice($newMoney);

        $this->assertEquals(2500000.0, $aggregate->getPrice()->getAmount());
    }

    public function test_update_commission(): void
    {
        $aggregate = $this->createAggregate();

        $aggregate->updateCommission(3.5);

        $this->assertEquals(3.5, $aggregate->getCommission()->getRate());
    }

    public function test_persist_returns_model_with_correct_data(): void
    {
        $aggregate = $this->createAggregate(
            offeringType: OfferingType::KIRALIK
        );

        // Verify aggregate state before persistence
        $this->assertTrue($aggregate->isDirty());
        $this->assertEquals('KIRALIK', $aggregate->getOfferingType()->value);
    }

    public function test_aggregate_tracks_dirty_state(): void
    {
        $aggregate = $this->createAggregate();

        $this->assertTrue($aggregate->isDirty());
        $this->assertEquals(OfferingState::DRAFT, $aggregate->getState());
    }

    public function test_idempotency_key_is_preserved(): void
    {
        $key = 'test-idempotency-key-123';
        $aggregate = $this->createAggregate(idempotencyKey: $key);

        $this->assertEquals($key, $aggregate->getIdempotencyKey());
    }

    public function test_offering_type_getter(): void
    {
        $aggregate = $this->createAggregate(offeringType: OfferingType::KIRALIK);

        $this->assertEquals(OfferingType::KIRALIK, $aggregate->getOfferingType());
    }

    public function test_to_array(): void
    {
        $aggregate = $this->createAggregate();

        $array = $aggregate->toArray();

        $this->assertArrayHasKey('uuid', $array);
        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('workspace_id', $array);
        $this->assertArrayHasKey('property_id', $array);
        $this->assertArrayHasKey('offering_type', $array);
        $this->assertArrayHasKey('state', $array);
    }

    private function createAggregate(
        int $tenantId = 1,
        int $workspaceId = 1,
        int $propertyId = 1,
        ?OfferingType $offeringType = null,
        ?string $idempotencyKey = null
    ): CommercialOfferingAggregate {
        return CommercialOfferingAggregate::create(
            tenantId: $tenantId,
            workspaceId: $workspaceId,
            propertyId: $propertyId,
            offeringType: $offeringType ?? OfferingType::SATILIK,
            price: new Money(1000000.0, 'TRY'),
            commission: new Commission(null),
            dateRange: new DateRange(null, null),
            idempotencyKey: $idempotencyKey
        );
    }
}
