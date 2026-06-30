<?php

namespace App\Repositories\Governance;

use App\Contracts\Governance\GovernedEntityRepositoryInterface;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use DomainException;

/**
 * 🛡️ EloquentGovernedEntityRepository
 * Generic repository to bridge Governance Service with standard Eloquent models.
 * Used for soft launch: TestEntity and future RealEntities.
 */
class EloquentGovernedEntityRepository implements GovernedEntityRepositoryInterface
{
    private function resolveModel(string $entityType): string
    {
        return match($entityType) {
            'TestEntity' => \App\Models\TestEntity::class, // For verification
            'Property', 'Listing' => \App\Models\PropertyHub\Property::class,
            default => throw new DomainException("Unsupported Governed Entity Type: {$entityType}"),
        };
    }

    public function findOrFail(string $entityType, int|string $entityId): object
    {
        $modelClass = $this->resolveModel($entityType);
        return $modelClass::findOrFail($entityId);
    }

    public function updateState(string $entityType, int|string $entityId, string $newState): void
    {
        $entity = $this->findOrFail($entityType, $entityId);
        if ($entity instanceof Model) {
            $entity->update(['governance_state' => $newState]);
        }
    }

    public function createDraft(string $entityType, array $payload): int|string
    {
        $modelClass = $this->resolveModel($entityType);
        $entity = $modelClass::create([
            'payload' => $payload,
            'governance_state' => 'draft',
        ]);
        return $entity->id;
    }

    public function updatePayload(string $entityType, int|string $entityId, array $payload): void
    {
        $entity = $this->findOrFail($entityType, $entityId);
        if ($entity instanceof Model) {
            $entity->update(['payload' => $payload]);
        }
    }
}
