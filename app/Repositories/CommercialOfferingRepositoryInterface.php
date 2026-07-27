<?php

namespace App\Repositories;

use App\Domain\CommercialOffering\CommercialOfferingAggregate;
use App\Domain\CommercialOffering\Enums\OfferingState;
use App\Domain\CommercialOffering\Enums\OfferingType;

/**
 * Repository Interface for CommercialOffering Aggregate.
 */
interface CommercialOfferingRepositoryInterface
{
    /**
     * Find by idempotency key.
     */
    public function findByIdempotencyKey(string $key): ?CommercialOfferingAggregate;

    /**
     * Find by UUID.
     */
    public function findByUuid(string $uuid): ?CommercialOfferingAggregate;

    /**
     * Find by ID.
     */
    public function findById(int $id): ?CommercialOfferingAggregate;

    /**
     * Save aggregate (create or update).
     */
    public function save(CommercialOfferingAggregate $aggregate): CommercialOfferingAggregate;

    /**
     * Delete aggregate (soft delete).
     */
    public function delete(CommercialOfferingAggregate $aggregate): void;

    /**
     * Get all offerings for a property.
     *
     * @return CommercialOfferingAggregate[]
     */
    public function findByPropertyId(int $propertyId): array;

    /**
     * Get active offering for a property by type.
     * Business rule: Only one ACTIVE offering per type per property.
     */
    public function findActiveByPropertyAndType(int $propertyId, OfferingType $type): ?CommercialOfferingAggregate;

    /**
     * Get all active offerings for a property.
     *
     * @return CommercialOfferingAggregate[]
     */
    public function findActiveByPropertyId(int $propertyId): array;
}
