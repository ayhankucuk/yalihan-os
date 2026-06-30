<?php

namespace App\Services\Ups;

/**
 * @sab-ignore-catch
 */

use App\Models\IlanKategori;
use App\Models\FeatureAssignment;
use App\Models\Feature;
use App\Models\CategoryFeatureWhitelist;
use App\Services\Logging\LogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Yalıhan Unified Property Schema (UPS) - Feature Template Resolver
 *
 * SSOT: This is the authoritative system-wide template resolver (9+ consumers).
 * Wizard has a scoped variant at App\Services\Wizard\FeatureTemplateResolver
 * (used ONLY by WizardFeatureController for Step 2 priority scoring).
 *
 * This service implements runtime template inheritance for property categories.
 * Golden Template Logic:
 * - Base categories (parent_id = NULL) serve as templates
 * - Child categories (parent_id != NULL) inherit from their parent
 * - Inheritance is resolved at RUNTIME (no data duplication)
 * - Override mechanism: child's own assignments take precedence
 *
 * Example:
 * - Arsa (id=2, parent_id=NULL) → Base template
 *   - Tarla (id=15, parent_id=2) → Inherits from Arsa
 *   - Zeytinlik (id=17, parent_id=2) → Inherits from Arsa
 *
 * When Arsa gets new features, Tarla & Zeytinlik automatically gain them.
 *
 * UPS Rule: NO data copying. Template resolution happens at:
 * - Step2 API render
 * - Form builders
 * - Validation pipelines
 *
 * @package App\Services\Ups
 */
class FeatureTemplateResolver
{
    public function __construct(
        private UpsCacheService $cacheService
    ) {}
    /**
     * Resolve feature assignments for a category + publication type
     *
     * Algorithm:
     * 1. Check if category has parent
     * 2. If yes: Load parent assignments + merge with child overrides
     * 3. If no: Load own assignments
     * 4. Filter by publication type policy
     * 5. Return final merged assignments
     *
     * @param int $kategoriId Category ID
     * @param int|null $yayinTipiId Publication Type ID (optional)
     * @return Collection Collection of resolved FeatureAssignment models
     */
    public function resolve(int $kategoriId, ?int $yayinTipiId = null): Collection
    {
        // ✅ Performance Monitoring (2026-01-11)
        $timer = $this->startMonitoring('resolve', compact('kategoriId', 'yayinTipiId'));

        // V2 Simplified Logic: Assignments come from Global Master Template (YayinTipiSablonu)
        // Inheritance is handled via Category Whitelisting in resolveFeaturesGrouped
        $result = $this->getAssignments($kategoriId, $yayinTipiId);

        $this->stopMonitoring($timer, 'resolve');
        return $result;
    }

    /**
     * Resolve with inheritance (parent → child)
     * ⚠️ DEPRECATED in V2: Assignments are global. Kept for safeguard but redirects to getAssignments.
     *
     * @param IlanKategori $kategori Child category
     * @param int|null $yayinTipiId Publication Type ID
     * @return Collection Merged assignments
     */
    private function resolveWithInheritance(IlanKategori $kategori, ?int $yayinTipiId): Collection
    {
        return $this->getAssignments($kategori->id, $yayinTipiId);
    }

    /**
     * Get feature assignments for a publication type (Master Template)
     *
     * @param int $kategoriId Category ID (Unused for fetching in V2, kept for compatibility)
     * @param int|null $yayinTipiId Publication Type Template ID
     * @return Collection Collection of FeatureAssignment
     */
    private function getAssignments(int $kategoriId, ?int $yayinTipiId = null): Collection
    {
        if (!$yayinTipiId) {
            return collect();
        }

        // V2: Query YayinTipiSablonu directly
        return FeatureAssignment::where('assignable_type', 'App\Models\YayinTipiSablonu')
            ->where('assignable_id', $yayinTipiId)
            ->with(['feature', 'feature.category'])
            ->where('is_visible', true)
            ->orderBy('display_order') // context7-ignore
            ->orderBy('feature_id') // context7-ignore
            ->get();
    }

    /**
     * Merge parent and child assignments
     * ⚠️ DEPRECATED in V2
     */
    private function merge(Collection $parentAssignments, Collection $childOverrides): Collection
    {
        return $childOverrides;
    }

    /**
     * Resolve features (not assignments) for a category
     *
     * Returns simplified feature list with resolved visibility/requirement.
     * Used by Step2 API endpoint.
     *
     * @param int $kategoriId Category ID
     * @param int $yayinTipiId Publication Type ID
     * @return Collection Collection of feature arrays
     */
    public function resolveFeatures(int $kategoriId, int $yayinTipiId): Collection
    {
        // ✅ Performance Monitoring (2026-01-11)
        $timer = $this->startMonitoring('resolveFeatures', compact('kategoriId', 'yayinTipiId'));

        $cacheKey = $this->cacheService->buildFeatureGroupedKey($kategoriId, $yayinTipiId, 'flat');

        // UpsCacheService.rememberFeatureGrouped — atomik, registry takipli
        $result_cached = $this->cacheService->rememberFeatureGrouped($cacheKey, 600, function () use (
            $kategoriId, $yayinTipiId
        ) {
            $assignments = $this->resolve($kategoriId, $yayinTipiId);

            return $assignments->map(function ($assignment) {
                $feature = $assignment->feature;

                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'type' => $feature->type ?? 'boolean', // context7-ignore
                    'options' => $feature->options ?? null,
                    'unit' => $feature->unit ?? null,
                    'required' => (bool) $assignment->is_required,
                    'is_required' => (bool) $assignment->is_required,
                    'display_order' => (int) $assignment->display_order,
                    'description' => $feature->description ?? null,
                    'feature_category' => [
                        'id' => $feature->category->id ?? null,
                        'name' => $feature->category->name ?? 'Genel',
                        'slug' => $feature->category->slug ?? 'genel',
                    ],
                ];
            });
        });

        $this->stopMonitoring($timer, 'resolveFeatures', ['features' => $result_cached->count()]);
        return $result_cached;
    }

    /**
     * Resolve features grouped by ui_group for wizard
     *
     * Phase 6: Smart API - Returns features grouped by ui_group
     * Phase 6.6: Category-specific feature category whitelist filtering
     * Phase 6.7: Dynamic Airbnb/Seasonal hiding for Satılık publication type
     * Format: {"İç Özellikler": [...], "Dış Özellikler": [...], "Muhit": [...]}
     *
     * @param int $kategoriId Category ID
     * @param int $yayinTipiId Publication Type ID
     * @return Collection Collection grouped by ui_group
     */
    public function resolveFeaturesGrouped(int $kategoriId, int $yayinTipiId): Collection
    {
        // ✅ Performance Monitoring (2026-01-11)
        $timer = $this->startMonitoring('resolveFeaturesGrouped', compact('kategoriId', 'yayinTipiId'));

        $cacheKey = $this->cacheService->buildFeatureGroupedKey($kategoriId, $yayinTipiId, 'ui');

        // UpsCacheService.rememberFeatureGrouped — atomik, registry takipli
        $grouped = $this->cacheService->rememberFeatureGrouped($cacheKey, 600, function () use (
            $kategoriId, $yayinTipiId
        ) {
            $assignments = $this->resolve($kategoriId, $yayinTipiId);

            // Phase 6.6: Apply category-specific feature category whitelist
            $assignments = $this->applyFeatureCategoryWhitelist($assignments, $kategoriId);

            // Phase 6.7: Apply publication-type-specific filtering
            $assignments = $this->applyPublicationTypeFilter($assignments, $yayinTipiId);

            $features = $assignments->map(function ($assignment) {
                $feature = $assignment->feature;

                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'slug' => $feature->slug ?: str()->slug($feature->name),
                    'type' => $feature->type ?? 'boolean', // context7-ignore
                    'options' => $feature->options ?? null,
                    'unit' => $feature->unit ?? null,
                    'required' => (bool) $assignment->is_required,
                    'is_required' => (bool) $assignment->is_required,
                    'display_order' => (int) $assignment->display_order,
                    'description' => $feature->description ?? null,
                    'ui_group' => $assignment->group_name ?: $this->getUiGroup($feature),
                    'feature_category' => [
                        'id' => $feature->category->id ?? null,
                        'name' => $feature->category->name ?? 'Genel',
                        'slug' => $feature->category->slug ?? 'genel',
                    ],
                ];
            });

            $grouped = $features->groupBy('ui_group')->map(function ($groupFeatures) {
                return $groupFeatures->sortBy('display_order')->values();
            });

            // Phase 6.8: Prioritize groups
            return $this->prioritizeGroupsForPublicationType($grouped, $yayinTipiId);
        });

        $this->stopMonitoring($timer, 'resolveFeaturesGrouped', ['groups' => $grouped->count()]);
        return $grouped;
    }

    /**
     * Get UI group for a feature
     *
     * Phase 6: Classify features into ui_group based on feature_category
     * Mapping: feature_category.slug → ui_group label
     *
     * @param Feature $feature Feature model
     * @return string UI group name
     */
    private function getUiGroup(Feature $feature): string
    {
        $categorySlug = $feature->category->slug ?? 'genel';

        // ✅ Config-based UI grouping (moved from hard-coded - 2026-01-11)
        // See: config/ups.php - ui_groups
        $uiGroupMap = config('ups.ui_groups', []);

        return $uiGroupMap[$categorySlug] ?? 'Genel Özellikler';
    }

    /**
     * Check if category uses inheritance
     *
     * @param int $kategoriId Category ID
     * @return bool True if category has parent (uses inheritance)
     */
    public function usesInheritance(int $kategoriId): bool
    {
        $kategori = IlanKategori::find($kategoriId);

        return $kategori && $kategori->parent_id !== null;
    }

    /**
     * Get inheritance chain for a category
     *
     * Returns: [grandparent_id, parent_id, kategori_id]
     *
     * @param int $kategoriId Category ID
     * @return array Array of category IDs (root → leaf)
     */
    public function getInheritanceChain(int $kategoriId): array
    {
        $chain = [];
        $current = IlanKategori::find($kategoriId);

        // Traverse upwards to root
        while ($current) {
            array_unshift($chain, $current->id); // Prepend to array
            $current = $current->parent;
        }

        return $chain;
    }

    /**
     * Get base template category for a child
     *
     * @param int $kategoriId Category ID
     * @return IlanKategori|null Base template category (root parent)
     */
    public function getBaseTemplate(int $kategoriId): ?IlanKategori
    {
        $chain = $this->getInheritanceChain($kategoriId);

        if (empty($chain)) {
            return null;
        }

        // First element is the root
        return IlanKategori::find($chain[0]);
    }

    /**
     * Get all child categories that inherit from a base template
     *
     * @param int $baseKategoriId Base template category ID
     * @return Collection Collection of child categories
     */
    public function getInheritingCategories(int $baseKategoriId): Collection
    {
        // Get all categories with this parent_id
        $directChildren = IlanKategori::where('parent_id', $baseKategoriId)->get();

        // Recursively get grandchildren (if any)
        $allChildren = collect();

        foreach ($directChildren as $child) {
            $allChildren->push($child);
            $grandchildren = $this->getInheritingCategories($child->id);
            $allChildren = $allChildren->merge($grandchildren);
        }

        return $allChildren;
    }

    /**
     * Validate inheritance integrity
     *
     * Checks:
     * - Circular references
     * - Orphaned children (parent doesn't exist)
     * - Max depth exceeded
     *
     * @param int $kategoriId Category ID
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateInheritance(int $kategoriId): array
    {
        $errors = [];

        // Check circular reference
        $visited = [];
        $current = IlanKategori::find($kategoriId);

        while ($current && $current->parent_id) {
            if (in_array($current->parent_id, $visited)) {
                $errors[] = "Circular reference detected: Category {$kategoriId} references itself";
                break;
            }

            $visited[] = $current->id;
            $current = $current->parent;
        }

        // Check orphaned children
        $kategori = IlanKategori::find($kategoriId);
        if ($kategori && $kategori->parent_id) {
            $parent = IlanKategori::find($kategori->parent_id);
            if (!$parent) {
                $errors[] = "Orphaned child: Category {$kategoriId} has non-existent parent {$kategori->parent_id}";
            }
        }

        // Check max depth (3 levels should be enough)
        $chain = $this->getInheritanceChain($kategoriId);
        if (count($chain) > 3) {
            $errors[] = "Inheritance depth exceeded: Category {$kategoriId} has {count($chain)} levels (max: 3)";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Apply category-specific feature category whitelist
     *
     * Phase 6.6: Filter feature assignments by allowed feature categories
     * for specific property categories (e.g., Land category excludes hospitality features)
     *
     * @param Collection $assignments FeatureAssignment collection
     * @param int $kategoriId Category ID
     * @return Collection Filtered assignments
     */
    private function applyFeatureCategoryWhitelist(Collection $assignments, int $kategoriId): Collection
    {
        // Phase 6.6: Category-specific feature category whitelist
        $whitelist = $this->getFeatureCategoryWhitelist($kategoriId);

        // If no whitelist defined, return all assignments
        if (empty($whitelist)) {
            return $assignments;
        }

        // Filter by allowed feature category slugs
        return $assignments->filter(function ($assignment) use ($whitelist) {
            $categorySlug = $assignment->feature->category->slug ?? null;
            return $categorySlug && in_array($categorySlug, $whitelist, true);
        });
    }

    /**
     * Get feature category whitelist for a property category
     *
     * Phase 6.6: Define allowed feature categories per property category
     * Example: Land category only shows Land Features, General, Transportation, Outdoor
     *
     * @param int $kategoriId Category ID
     * @return array Array of allowed feature_category slugs (empty = no filtering)
     */
    private function getFeatureCategoryWhitelist(int $kategoriId): array
    {
        $kategori = IlanKategori::find($kategoriId);
        if (!$kategori) {
            return [];
        }

        // ✅ DB whitelist (category_feature_whitelist) — highest priority
        // Check category-specific whitelist first
        $dbWhitelist = CategoryFeatureWhitelist::active()
            ->where('kategori_id', $kategoriId)
            ->pluck('feature_category_slug')
            ->toArray();

        if (!empty($dbWhitelist)) {
            return $dbWhitelist;
        }

        // Check parent whitelist (if this is a child category)
        if ($kategori->parent_id) {
            $parentWhitelist = CategoryFeatureWhitelist::active()
                ->where('kategori_id', $kategori->parent_id)
                ->pluck('feature_category_slug')
                ->toArray();

            if (!empty($parentWhitelist)) {
                return $parentWhitelist;
            }
        }

        // ✅ Config fallback (config/ups.php)
        // Get root category (if this is a child category)
        $rootKategori = $kategori->parent_id ? IlanKategori::find($kategori->parent_id) : $kategori;
        $rootSlug = $rootKategori?->slug;

        $whitelists = config('ups.category_whitelist', []);

        return $whitelists[$rootSlug] ?? [];
    }

    /**
     * Apply publication-type-specific feature filtering
     *
     * Phase 6.7: Hide rental-specific features for sale listings
     * Example: Hide Airbnb Features and Seasonal Features when publication type is Satılık
     *
     * @param Collection $assignments FeatureAssignment collection
     * @param int $yayinTipiId Publication Type ID
     * @return Collection Filtered assignments
     */
    private function applyPublicationTypeFilter(Collection $assignments, int $yayinTipiId): Collection
    {
        // Get publication type slug
        $yayinTipi = \App\Models\YayinTipiSablonu::find($yayinTipiId);
        if (!$yayinTipi) {
            return $assignments;
        }

        // Normalize slug using canonical rules
        try {
            $canonicalSlug = \App\Support\YayinTipiRules::canonicalizeSlug($yayinTipi->slug);
        } catch (\InvalidArgumentException $e) {
            return $assignments;
        }

        // Phase 6.7: Exclude rental-specific feature categories for sale listings
        // Get excluded categories from config (or hardcoded fallback)
        $excludedCategoriesForSale = config('ups.yayin_tipi_filters.satilik_excluded_feature_categories', ['airbnb', 'yazlik']);

        if ($canonicalSlug === 'satilik') {
            return $assignments->filter(function ($assignment) use ($excludedCategoriesForSale) {
                $categorySlug = $assignment->feature->category->slug ?? null;
                return $categorySlug && !in_array($categorySlug, $excludedCategoriesForSale, true);
            });
        }

        return $assignments;
    }

    /**
     * Prioritize feature groups based on publication type
     *
     * Phase 6.8: For seasonal rentals, move Airbnb and Seasonal groups to top
     * For sale listings, groups are already filtered (airbnb/yazlik excluded)
     *
     * @param Collection $grouped Grouped features collection
     * @param int $yayinTipiId Publication Type ID
     * @return Collection Reordered grouped collection
     */
    private function prioritizeGroupsForPublicationType(Collection $grouped, int $yayinTipiId): Collection
    {
        // Get publication type
        $yayinTipi = \App\Models\YayinTipiSablonu::find($yayinTipiId);
        if (!$yayinTipi) {
            return $grouped;
        }

        // Canonicalize slug using YayinTipiRules
        try {
            $canonicalSlug = \App\Support\YayinTipiRules::canonicalizeSlug($yayinTipi->slug);
        } catch (\InvalidArgumentException $e) {
            return $grouped;
        }

        // Phase 6.8: For seasonal rentals, prioritize Airbnb and seasonal feature groups
        $seasonalSlugs = ['gunluk', 'haftalik', 'aylik', 'sezonluk'];

        if (in_array($canonicalSlug, $seasonalSlugs, true)) {
            // Define priority groups (will be moved to top)
            $priorityGroups = [
                'Airbnb Özellikleri',
                'Yazlık Özellikleri',
                'Rezervasyon Kuralları',
                'Fiyatlandırma',
            ];

            // Separate priority and regular groups
            $priority = collect();
            $regular = collect();

            foreach ($grouped as $groupName => $features) {
                if (in_array($groupName, $priorityGroups, true)) {
                    $priority->put($groupName, $features);
                } else {
                    $regular->put($groupName, $features);
                }
            }

            // Merge: priority groups first, then regular groups
            return $priority->merge($regular);
        }

        return $grouped;
    }

    /**
     * Start performance monitoring timer
     *
     * @param string $method Method name
     * @param array $context Context data (kategoriId, yayinTipiId, etc.)
     * @return array Timer data [start_time, method, context]
     */
    private function startMonitoring(string $method, array $context = []): array
    {
        if (!config('ups.monitoring.enabled', true)) {
            return [];
        }

        return [
            'start_time' => microtime(true),
            'method' => $method,
            'context' => $context,
        ];
    }

    /**
     * Stop performance monitoring and log if needed
     *
     * @param array $timer Timer data from startMonitoring()
     * @param string $method Method name
     * @param array $extra Extra context (cache_hit, features count, etc.)
     * @return void
     */
    private function stopMonitoring(array $timer, string $method, array $extra = []): void
    {
        if (!config('ups.monitoring.enabled', true) || empty($timer)) {
            return;
        }

        $duration = (microtime(true) - ($timer['start_time'] ?? 0)) * 1000; // ms
        $threshold = config('ups.monitoring.slow_query_threshold_ms', 100);

        $logData = array_merge($timer['context'] ?? [], $extra, [
            'method' => $method,
            'duration_ms' => round($duration, 2),
        ]);

        // Log all UPS calls (Info level)
        Log::info("UPS: {$method}", $logData);

        // Warn if slow
        if ($duration > $threshold) {
            Log::warning("UPS SLOW QUERY: {$method} took {$logData['duration_ms']}ms (threshold: {$threshold}ms)", $logData);
        }
    }
}
