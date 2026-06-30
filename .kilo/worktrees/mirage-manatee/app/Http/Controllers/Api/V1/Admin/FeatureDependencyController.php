<?php

namespace App\Http\Controllers\Api\V1\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\FeatureDependency;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

/**
 * Feature Dependency API Controller
 *
 * Context7 Compliance: Feature koşullu görünürlük yönetimi
 * - aktiflik_durumu: NOT enabled/is_active
 * - display_order: NOT order/sort_order
 */
class FeatureDependencyController extends Controller
{
    /**
     * List all feature dependencies
     */
    public function index(Request $request)
    {
        $query = FeatureDependency::query()
            ->with(['feature:id,name,slug', 'parentFeature:id,name,slug'])
            ->ordered(); // context7-ignore

        // Filter by feature_id
        if ($request->feature_id) {
            $query->forFeature($request->feature_id);
        }

        // Filter by parent_feature_id
        if ($request->parent_feature_id) {
            $query->forParent($request->parent_feature_id);
        }

        // Filter by aktiflik_durumu
        if ($request->has('aktiflik_durumu')) {
            $query->where('aktiflik_durumu', (bool) $request->aktiflik_durumu);
        }

        $dependencies = $query->get();

        return ResponseService::success([
            'data' => $dependencies,
            'total' => $dependencies->count(),
        ]);
    }

    /**
     * Store a new feature dependency
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'feature_id' => 'required|exists:features,id',
            'parent_feature_id' => 'required|exists:features,id|different:feature_id',
            'parent_value' => 'nullable|string|max:255',
            'operator' => 'required|in:=,!=,>,<,>=,<=,in,not_in',
            'condition_type' => 'required|in:show,hide',
            'aktiflik_durumu' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        // Circular dependency check
        if ($this->hasCircularDependency($validated['feature_id'], $validated['parent_feature_id'])) {
            return ResponseService::error('Circular dependency detected! This would create an infinite loop.', 422);
        }

        $dependency = FeatureDependency::create($validated);

        LogService::info('Feature dependency created', [
            'dependency_id' => $dependency->id,
            'feature_id' => $dependency->feature_id,
            'parent_feature_id' => $dependency->parent_feature_id,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::success([
            'data' => $dependency->load(['feature', 'parentFeature']),
        ], 'Feature dependency created successfully', 201);
    }

    /**
     * Update an existing feature dependency
     */
    public function update(Request $request, FeatureDependency $featureDependency)
    {
        $validated = $request->validate([
            'feature_id' => 'required|exists:features,id',
            'parent_feature_id' => 'required|exists:features,id|different:feature_id',
            'parent_value' => 'nullable|string|max:255',
            'operator' => 'required|in:=,!=,>,<,>=,<=,in,not_in',
            'condition_type' => 'required|in:show,hide',
            'aktiflik_durumu' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        // Circular dependency check (except self)
        if ($validated['feature_id'] != $featureDependency->feature_id ||
            $validated['parent_feature_id'] != $featureDependency->parent_feature_id) {
            if ($this->hasCircularDependency($validated['feature_id'], $validated['parent_feature_id'], $featureDependency->id)) {
                return ResponseService::error('Circular dependency detected! This would create an infinite loop.', 422);
            }
        }

        $featureDependency->update($validated);

        LogService::info('Feature dependency updated', [
            'dependency_id' => $featureDependency->id,
            'feature_id' => $featureDependency->feature_id,
            'parent_feature_id' => $featureDependency->parent_feature_id,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::success([
            'data' => $featureDependency->load(['feature', 'parentFeature']),
        ], 'Feature dependency updated successfully');
    }

    /**
     * Delete a feature dependency
     */
    public function destroy(FeatureDependency $featureDependency)
    {
        $dependencyId = $featureDependency->id;

        $featureDependency->delete();

        LogService::info('Feature dependency deleted', [
            'dependency_id' => $dependencyId,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::success(null, 'Feature dependency deleted successfully');
    }

    /**
     * Toggle aktiflik_durumu
     */
    public function toggle(FeatureDependency $featureDependency)
    {
        $featureDependency->aktiflik_durumu = !$featureDependency->aktiflik_durumu;
        $featureDependency->save();

        LogService::info('Feature dependency aktiflik_durumu toggled', [
            'dependency_id' => $featureDependency->id,
            'new_aktiflik_durumu' => $featureDependency->aktiflik_durumu,
            'user_id' => auth()->id(),
        ]);

        return ResponseService::success([
            'aktiflik_durumu' => $featureDependency->aktiflik_durumu,
        ]);
    }

    /**
     * Get dependencies for a specific feature (for frontend)
     */
    public function getForFeature(Request $request, int $featureId)
    {
        $dependencies = FeatureDependency::forFeature($featureId)
            ->aktif()
            ->with(['parentFeature:id,name,slug,type'])
            ->ordered() // context7-ignore
            ->get();

        return ResponseService::success([
            'data' => $dependencies,
        ]);
    }

    /**
     * Check for circular dependencies
     */
    private function hasCircularDependency(int $featureId, int $parentFeatureId, ?int $excludeId = null): bool
    {
        // Simple depth-first search for cycles
        $visited = [];
        return $this->dfsCircularCheck($featureId, $parentFeatureId, $visited, $excludeId);
    }

    /**
     * DFS helper for circular dependency check
     */
    private function dfsCircularCheck(int $currentFeatureId, int $targetParentId, array &$visited, ?int $excludeId): bool
    {
        // If we've reached the target parent, and it would depend back on the original feature, cycle detected
        if ($currentFeatureId === $targetParentId) {
            return true;
        }

        if (in_array($currentFeatureId, $visited)) {
            return false;
        }

        $visited[] = $currentFeatureId;

        // Check all parents of the current feature
        $query = FeatureDependency::where('feature_id', $currentFeatureId);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $parents = $query->pluck('parent_feature_id');

        foreach ($parents as $parentId) {
            if ($this->dfsCircularCheck($parentId, $targetParentId, $visited, $excludeId)) {
                return true;
            }
        }

        return false;
    }
}
