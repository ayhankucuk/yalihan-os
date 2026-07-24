<?php

namespace App\Services\Property;

use App\Domain\Property\Events\PropertyActivated;
use App\Domain\Property\Events\PropertyArchived;
use App\Domain\Property\Events\PropertyCreated;
use App\Domain\Property\Events\PropertyVerified;
use App\Models\Property;
use App\Repositories\PropertyRepositoryInterface;
use App\Domain\Property\ValueObjects\Location;
use App\Domain\Property\ValueObjects\TapuInfo;
use App\Domain\Property\ValueObjects\PhysicalSpecs;
use Illuminate\Support\Facades\Log;

class PropertyCrudService
{
    protected PropertyRepositoryInterface $repository;
    protected PropertyStateMachine $stateMachine;

    public function __construct(PropertyRepositoryInterface $repository, PropertyStateMachine $stateMachine)
    {
        $this->repository = $repository;
        $this->stateMachine = $stateMachine;
    }

    /**
     * Create a new Property aggregate.
     *
     * Idempotency: If idempotency_key is provided and a Property with that key exists,
     * the existing Property is returned without creating a duplicate.
     *
     * @throws \DomainException If workspace_id is missing
     */
    public function create(array $data): Property
    {
        // Idempotency check (Invariant 6)
        if (! empty($data['idempotency_key'])) {
            $existing = Property::byIdempotencyKey($data['idempotency_key'])->first();
            if ($existing) {
                Log::info('Property idempotent read', [
                    'property_id' => $existing->id,
                    'idempotency_key' => $data['idempotency_key'],
                ]);
                return $existing;
            }
        }

        $property = new Property();
        $property->fill($data);

        if (isset($data['location']) && $data['location'] instanceof Location) {
            $property->setLocation($data['location']);
        }
        if (isset($data['tapu_info']) && $data['tapu_info'] instanceof TapuInfo) {
            $property->setTapuInfo($data['tapu_info']);
        }
        if (isset($data['physical_specs']) && $data['physical_specs'] instanceof PhysicalSpecs) {
            $property->setPhysicalSpecs($data['physical_specs']);
        }

        $saved = $this->repository->save($property);

        // Domain event (Invariant: no price, listing, CRM, or reservation in Property)
        event(new PropertyCreated(
            $saved->id,
            $saved->tenant_id,
            $saved->uuid,
            $saved->aktiflik_durumu,
            $saved->workspace_id ?? null,
        ));

        Log::info('PropertyCreated', [
            'property_id' => $saved->id,
            'tenant_id' => $saved->tenant_id,
            'workspace_id' => $saved->workspace_id,
            'aktiflik_durumu' => $saved->aktiflik_durumu,
        ]);

        return $saved;
    }

    /**
     * Update an existing Property aggregate.
     * TKGM identity (tkgm_id, ada, parsel) is protected by model invariants.
     */
    public function update(Property $property, array $data): Property
    {
        $property->fill($data);

        if (isset($data['location']) && $data['location'] instanceof Location) {
            $property->setLocation($data['location']);
        }
        if (isset($data['tapu_info']) && $data['tapu_info'] instanceof TapuInfo) {
            // TKGM immutability enforced by model booted hook
            $property->setTapuInfo($data['tapu_info']);
        }
        if (isset($data['physical_specs']) && $data['physical_specs'] instanceof PhysicalSpecs) {
            $property->setPhysicalSpecs($data['physical_specs']);
        }

        return $this->repository->save($property);
    }

    /**
     * Verify the physical property (Draft -> Verified).
     * Invariant: Location coordinates and Tapu ada/parsel must be set.
     */
    public function verify(Property $property): void
    {
        $previousState = $property->aktiflik_durumu;
        $this->stateMachine->transition($property, PropertyStateMachine::STATE_VERIFIED);
        $this->repository->save($property);

        event(new PropertyVerified(
            $property->id,
            $property->tenant_id,
            $property->uuid,
            $previousState,
        ));

        Log::info('PropertyVerified', [
            'property_id' => $property->id,
            'previous_state' => $previousState,
        ]);
    }

    /**
     * Activate the property for listings (Verified -> Active).
     */
    public function activate(Property $property): void
    {
        $previousState = $property->aktiflik_durumu;
        $this->stateMachine->transition($property, PropertyStateMachine::STATE_ACTIVE);
        $this->repository->save($property);

        event(new PropertyActivated(
            $property->id,
            $property->tenant_id,
            $property->uuid,
            $previousState,
        ));

        Log::info('PropertyActivated', [
            'property_id' => $property->id,
            'previous_state' => $previousState,
        ]);
    }

    /**
     * Archive the property (Any -> Archived).
     */
    public function archive(Property $property): void
    {
        $previousState = $property->aktiflik_durumu;
        $this->stateMachine->transition($property, PropertyStateMachine::STATE_ARCHIVED);
        $this->repository->save($property);

        event(new PropertyArchived(
            $property->id,
            $property->tenant_id,
            $property->uuid,
            $previousState,
        ));

        Log::info('PropertyArchived', [
            'property_id' => $property->id,
            'previous_state' => $previousState,
        ]);
    }
}
