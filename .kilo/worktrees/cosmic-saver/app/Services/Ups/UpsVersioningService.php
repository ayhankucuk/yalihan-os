<?php

namespace App\Services\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeaturePack;
use App\Models\UpsVersion;
use App\Models\UpsVersionEvent;
use App\Services\Logging\LogService;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;

/**
 * UPS Versioning Service
 *
 * Context7 Compliance: Snapshot + Rollback system
 * - Creates snapshots before destructive operations
 * - Idempotent rollback
 * - NO content_type in logs
 */
class UpsVersioningService
{
    use GuardsAgentWrites;

    /**
     * Create version snapshot
     */
    public function createVersion(
        string $entityType,
        int $entityId,
        ?string $reason = null
    ): UpsVersion {
        $this->blockAgentWrite(__FUNCTION__);

        // Get entity data
        $snapshot = $this->captureSnapshot($entityType, $entityId);

        $version = UpsVersion::createSnapshot(
            $entityType,
            $entityId,
            $snapshot,
            $reason,
            auth()->id()
        );

        // Log event
        UpsVersionEvent::logEvent(
            $version->id,
            'create',
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'version_number' => $version->version,
                'reason' => $reason,
            ]
        );

        LogService::info('UPS Version created', [
            'version_id' => $version->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'version_number' => $version->version,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);

        return $version;
    }

    /**
     * Capture entity snapshot
     */
    private function captureSnapshot(string $entityType, int $entityId): array
    {
        switch ($entityType) {
            case Feature::class:
                $entity = Feature::with('assignments')->findOrFail($entityId);
                return [
                    'feature' => $entity->toArray(),
                    'assignments' => $entity->assignments->toArray(),
                ];

            case FeaturePack::class:
                $entity = FeaturePack::with('items.feature')->findOrFail($entityId);
                return [
                    'pack' => $entity->toArray(),
                    'items' => $entity->items->toArray(),
                ];

            case FeatureAssignment::class:
                $entity = FeatureAssignment::with('feature')->findOrFail($entityId);
                return [
                    'assignment' => $entity->toArray(),
                    'feature' => $entity->feature->toArray(),
                ];

            default:
                throw new \InvalidArgumentException("Unsupported entity type: {$entityType}");
        }
    }

    /**
     * Rollback to version
     */
    public function rollbackToVersion(int $versionId): array
    {
        $version = UpsVersion::with('events')->findOrFail($versionId);

        DB::beginTransaction();
        try {
            $result = $this->restoreSnapshot($version);

            // Log rollback event
            UpsVersionEvent::logEvent(
                $version->id,
                'rollback',
                [
                    'entity_type' => $version->entity_type,
                    'entity_id' => $version->entity_id,
                    'version_number' => $version->version,
                    'restored_count' => $result['restored_count'],
                ]
            );

            LogService::info('UPS Version rollback', [
                'version_id' => $version->id,
                'entity_type' => $version->entity_type,
                'entity_id' => $version->entity_id,
                'version_number' => $version->version,
                'restored_count' => $result['restored_count'],
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();

            LogService::error('UPS Version rollback failed', [
                'version_id' => $version->id,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Restore snapshot data
     */
    private function restoreSnapshot(UpsVersion $version): array
    {
        $snapshot = $version->snapshot_json;
        $entityType = $version->entity_type;
        $entityId = $version->entity_id;

        switch ($entityType) {
            case Feature::class:
                return $this->restoreFeature($entityId, $snapshot);

            case FeaturePack::class:
                return $this->restoreFeaturePack($entityId, $snapshot);

            case FeatureAssignment::class:
                return $this->restoreFeatureAssignment($entityId, $snapshot);

            default:
                throw new \InvalidArgumentException("Unsupported entity type: {$entityType}");
        }
    }

    /**
     * Restore feature from snapshot
     */
    private function restoreFeature(int $featureId, array $snapshot): array
    {
        $feature = Feature::findOrFail($featureId);
        $feature->update($snapshot['feature']);

        // Restore assignments (idempotent)
        $restored = 0;
        foreach ($snapshot['assignments'] ?? [] as $assignmentData) {
            $existing = FeatureAssignment::find($assignmentData['id']);
            if ($existing) {
                $existing->update($assignmentData);
            } else {
                FeatureAssignment::create($assignmentData);
            }
            $restored++;
        }

        return ['restored_count' => $restored, 'entity' => $feature];
    }

    /**
     * Restore feature pack from snapshot
     */
    private function restoreFeaturePack(int $packId, array $snapshot): array
    {
        $pack = FeaturePack::findOrFail($packId);
        $pack->update($snapshot['pack']);

        // Restore items (idempotent)
        $restored = 0;
        foreach ($snapshot['items'] ?? [] as $itemData) {
            $existing = $pack->items()->where('feature_id', $itemData['feature_id'])->first();
            if ($existing) {
                $existing->update($itemData);
            } else {
                $pack->items()->create($itemData);
            }
            $restored++;
        }

        return ['restored_count' => $restored, 'entity' => $pack];
    }

    /**
     * Restore feature assignment from snapshot
     */
    private function restoreFeatureAssignment(int $assignmentId, array $snapshot): array
    {
        $assignment = FeatureAssignment::findOrFail($assignmentId);
        $assignment->update($snapshot['assignment']);

        return ['restored_count' => 1, 'entity' => $assignment];
    }

    /**
     * Get version history for entity
     */
    public function getVersionHistory(string $entityType, int $entityId): array
    {
        $versions = UpsVersion::forEntity($entityType, $entityId)
            ->with('createdBy:id,name', 'events')
            ->get();

        return $versions->map(function ($version) {
            return [
                'id' => $version->id,
                'version' => $version->version,
                'reason' => $version->reason,
                'created_at' => $version->created_at->toDateTimeString(),
                'created_by' => $version->createdBy?->name ?? 'System',
                'event_count' => $version->events->count(),
                'latest_event' => $version->events->sortByDesc('event_at')->first()?->event_type,
            ];
        })->toArray();
    }
}
