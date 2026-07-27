<?php

namespace App\Repositories;

use App\Domain\CommercialOffering\CommercialOfferingAggregate;
use App\Domain\CommercialOffering\Enums\OfferingState;
use App\Domain\CommercialOffering\Enums\OfferingType;
use App\Models\CommercialOffering;

/**
 * Eloquent Repository for CommercialOffering Aggregate.
 */
class EloquentCommercialOfferingRepository implements CommercialOfferingRepositoryInterface
{
    public function findByIdempotencyKey(string $key): ?CommercialOfferingAggregate
    {
        $model = CommercialOffering::where('idempotency_key', $key)->first();

        return $model ? CommercialOfferingAggregate::fromModel($model) : null;
    }

    public function findByUuid(string $uuid): ?CommercialOfferingAggregate
    {
        $model = CommercialOffering::where('uuid', $uuid)->first();

        return $model ? CommercialOfferingAggregate::fromModel($model) : null;
    }

    public function findById(int $id): ?CommercialOfferingAggregate
    {
        $model = CommercialOffering::find($id);

        return $model ? CommercialOfferingAggregate::fromModel($model) : null;
    }

    public function save(CommercialOfferingAggregate $aggregate): CommercialOfferingAggregate
    {
        return $aggregate->persist();
    }

    public function delete(CommercialOfferingAggregate $aggregate): void
    {
        $model = CommercialOffering::where('uuid', $aggregate->getUuid())->first();

        if ($model) {
            $model->delete();
        }
    }

    /**
     * @return CommercialOfferingAggregate[]
     */
    public function findByPropertyId(int $propertyId): array
    {
        return CommercialOffering::where('property_id', $propertyId)
            ->orderBy('id')
            ->get()
            ->map(fn(CommercialOffering $m) => CommercialOfferingAggregate::fromModel($m))
            ->all();
    }

    public function findActiveByPropertyAndType(int $propertyId, OfferingType $type): ?CommercialOfferingAggregate
    {
        $model = CommercialOffering::where('property_id', $propertyId)
            ->where('offering_type', $type->value)
            ->where('yayin_durumu', OfferingState::ACTIVE->value)
            ->orderBy('id')
            ->first();

        return $model ? CommercialOfferingAggregate::fromModel($model) : null;
    }

    /**
     * @return CommercialOfferingAggregate[]
     */
    public function findActiveByPropertyId(int $propertyId): array
    {
        return CommercialOffering::where('property_id', $propertyId)
            ->where('yayin_durumu', OfferingState::ACTIVE->value)
            ->orderBy('id')
            ->get()
            ->map(fn(CommercialOffering $m) => CommercialOfferingAggregate::fromModel($m))
            ->all();
    }
}
