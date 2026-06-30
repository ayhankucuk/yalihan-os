<?php

namespace App\Services\PropertyType;

use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Traits\GuardsAgentWrites;

/**
 * ⚡ PERFORMANCE: Feature Assignment Service with Caching
 *
 * Context7: C7-SERVICE-FEATURE-ASSIGNMENT-2025-12-23
 * Purpose: Centralized feature assignment loading with Redis cache
 *
 * Cache Strategy:
 * - TTL: 3600 seconds (1 hour)
 * - Key Pattern: feature_assignments:type:{type_ids_hash}
 * - Invalidation: On create/update/delete assignment
 *
 * Performance Targets:
 * - Cached queries: <10ms
 * - Uncached queries: <100ms (with composite indexes)
 * - Cache hit rate: >80%
 *
 * @see PropertyTypeManagerController Line 713-745 (usage)
 * @see docs/technical/UPS_DEEP_ANALYSIS_2025-12-23.md Section 8.2
 */
class FeatureAssignmentService
{
    use GuardsAgentWrites;
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'feature_assignments';

    /**
     * Get feature assignments for property types with caching
     *
     * @param array $typeIds Array of YayinTipiSablonu IDs
     * @return Collection<FeatureAssignment>
     */
    public function getAssignmentsForPropertyTypes(array $typeIds): Collection
    {
        if (empty($typeIds)) {
            return collect([]);
        }

        // Generate cache key based on type IDs
        $cacheKey = $this->getCacheKey($typeIds);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($typeIds) {
            Log::info('FeatureAssignmentService: Cache MISS', [
                'type_ids' => $typeIds, // context7-ignore
                'count' => count($typeIds)
            ]);

            return $this->loadAssignmentsFromDatabase($typeIds);
        });
    }

    /**
     * Get assignments grouped by property type ID
     *
     * @param array $typeIds
     * @return array ['counts' => [...], 'assignments' => [...]]
     */
    public function getAssignmentsGroupedByType(array $typeIds): array
    {
        $allAssignments = $this->getAssignmentsForPropertyTypes($typeIds);

        $assignmentCounts = [];
        $assignmentsByType = [];

        foreach ($typeIds as $tid) {
            $group = $allAssignments->where('assignable_id', $tid);
            $assignmentCounts[$tid] = $group->count();
            $assignmentsByType[$tid] = $group;
        }

        return [
            'counts' => $assignmentCounts,
            'assignments' => $assignmentsByType,
        ];
    }

    /**
     * Invalidate cache for specific property types
     *
     * @param array $typeIds
     * @return void
     */
    public function invalidateCache(array $typeIds = []): void
    {
        // ✅ SAB: Incremental versioning for file-based cache invalidation
        // This effectively invalidates all current caches by changing the master key suffix
        $this->incrementVersion();

        Log::info('FeatureAssignmentService: Cache invalidated via version increment');
    }

    /**
     * Increment the cache version
     */
    private function incrementVersion(): void
    {
        $versionKey = self::CACHE_PREFIX . ':version';
        $currentVersion = (int) Cache::get($versionKey, 1);
        Cache::put($versionKey, $currentVersion + 1, self::CACHE_TTL * 24); // Persistent for long time
    }

    /**
     * Get the current cache version
     */
    private function getVersion(): int
    {
        return (int) Cache::get(self::CACHE_PREFIX . ':version', 1);
    }

    /**
     * Load assignments from database with eager loading
     *
     * @param array $typeIds
     * @return Collection<FeatureAssignment>
     */
    private function loadAssignmentsFromDatabase(array $typeIds): Collection
    {
        return FeatureAssignment::whereIn('assignable_id', $typeIds)
            ->where('assignable_type', YayinTipiSablonu::class)
            ->with([
                'feature' => function ($query) {
                    // Only load active features with category
                    $query->where('aktiflik_durumu', true)
                          ->select(['id', 'name', 'slug', 'feature_category_id', 'display_order', 'aktiflik_durumu']);
                },
                'feature.category' => function ($query) {
                    // Load category with minimal columns
                    $query->select(['id', 'name', 'slug', 'aktiflik_durumu']);
                }
            ])
            ->select(['id', 'feature_id', 'assignable_id', 'assignable_type', 'display_order', 'is_visible', 'is_required', 'group_name'])
            ->orderBy('display_order') // context7-ignore
            ->get()
            ->filter(function ($assignment) {
                // Filter null features after eager loading
                return $assignment->feature !== null;
            });
    }

    /**
     * Generate cache key for type IDs
     *
     * @param array $typeIds
     * @return string
     */
    private function getCacheKey(array $typeIds): string
    {
        sort($typeIds); // Ensure consistent sequence
        $hash = md5(implode(',', $typeIds));
        $version = $this->getVersion();
        return self::CACHE_PREFIX . ':v' . $version . ':type:' . $hash;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        // This is a placeholder for cache stats
        // In production, you'd track hits/misses in Redis
        return [
            'cache_prefix' => self::CACHE_PREFIX,
            'cache_ttl' => self::CACHE_TTL,
            'driver' => config('cache.default'),
        ];
    }
    /**
     * Güncelleme (Atomic mutation)
     *
     * @param int $assignmentId
     * @param array $data
     * @return array
     */
    public function updateAssignment(int $assignmentId, array $data): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        try {
            $assignment = FeatureAssignment::findOrFail($assignmentId);
            $assignment->update($data);

            $this->invalidateCache();

            Log::info('✅ Feature assignment updated', [
                'assignment_id' => $assignmentId,
                'updates' => $data,
            ]);

            return [
                'success' => true,
                'message' => 'Özellik ayarları güncellendi',
                'data' => $assignment->fresh()->only(['id', 'is_required', 'is_visible', 'display_order', 'group_name', 'label_override']),
            ];
        } catch (\Exception $e) {
            Log::error('❌ Feature assignment update failed', [
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Güncelleme hatası: ' . $e->getMessage(),
            ];
        }
    }
}
