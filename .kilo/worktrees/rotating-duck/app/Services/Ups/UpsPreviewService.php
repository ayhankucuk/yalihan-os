<?php

namespace App\Services\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;

/**
 * UPS Preview Service
 *
 * Context7 Compliance: Read-only diff/preview BEFORE mutations
 * - Shows what WILL change (not what changed)
 * - Used by pack apply and template edit operations
 */
class UpsPreviewService
{
    private array $categoryNameCache = [];

    /**
     * Preview pack apply operation
     *
     * @param FeaturePack|int $pack Pack model or ID
     * @param int $kategoriId
     * @param array $yayinTipiIds
     * @param string $mode 'merge' | 'replace'
     * @return array Diff preview
     */
    public function previewPackApply(
        FeaturePack|int $pack,
        int $kategoriId,
        array $yayinTipiIds,
        string $mode = 'merge'
    ): array {
        if (is_int($pack)) {
            $pack = FeaturePack::findOrFail($pack);
        }

        $packFeatures = $pack->features()->get(['id', 'slug', 'name', 'feature_category_id']);
        $preview = [
            'pack_name' => $pack->name,
            'kategori_id' => $kategoriId,
            'yayin_tipi_ids' => $yayinTipiIds,
            'mode' => $mode,
            'templates' => [],
        ];

        foreach ($yayinTipiIds as $yayinTipiId) {
            // V2: YayinTipiSablonu is global, not per-category
            $template = YayinTipiSablonu::find($yayinTipiId);

            if (!$template) {
                continue;
            }

            $currentAssignments = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $template->id)
                ->with('feature:id,slug,name,feature_category_id')
                ->get();

            $currentFeatureIds = $currentAssignments->pluck('feature_id')->toArray();
            $packFeatureIds = $packFeatures->pluck('id')->toArray();

            // Calculate diff
            $toAdd = array_diff($packFeatureIds, $currentFeatureIds);
            $toRemove = $mode === 'replace' ? array_diff($currentFeatureIds, $packFeatureIds) : [];
            $unchanged = array_intersect($currentFeatureIds, $packFeatureIds);

            $preview['templates'][] = [
                'yayin_tipi_id' => $yayinTipiId,
                'current_count' => count($currentFeatureIds),
                'after_count' => $mode === 'merge'
                    ? count($currentFeatureIds) + count($toAdd)
                    : count($packFeatureIds),
                'to_add' => array_values(array_map(function ($id) use ($packFeatures, $kategoriId) {
                    $feature = $packFeatures->firstWhere('id', $id);
                    $inheritance = $feature ? $this->computeInheritance($feature, $kategoriId) : ['is_inherited' => false, 'origin_category_name' => null];

                    return [
                        'id' => $id,
                        'slug' => $feature?->slug ?? 'unknown',
                        'is_inherited' => $inheritance['is_inherited'],
                        'origin_category_name' => $inheritance['origin_category_name'],
                    ];
                }, $toAdd)),
                'to_remove' => array_values(array_map(function ($id) use ($currentAssignments, $kategoriId) {
                    $assignment = $currentAssignments->firstWhere('feature_id', $id);
                    $inheritance = $assignment && $assignment->feature
                        ? $this->computeInheritance($assignment->feature, $kategoriId)
                        : ['is_inherited' => false, 'origin_category_name' => null];

                    return [
                        'id' => $id,
                        'slug' => $assignment?->feature->slug ?? 'unknown',
                        'is_inherited' => $inheritance['is_inherited'],
                        'origin_category_name' => $inheritance['origin_category_name'],
                    ];
                }, $toRemove)),
                'unchanged_count' => count($unchanged),
            ];
        }

        return $preview;
    }

    /**
     * Preview template feature changes
     *
     * @param int $kategoriId
     * @param int $yayinTipiId
     * @param array $plannedFeatureSlugs Array of feature slugs to be assigned
     * @return array Diff preview
     */
    public function previewTemplateChanges(
        int $kategoriId,
        int $yayinTipiId,
        array $plannedFeatureSlugs
    ): array {
        // V2: YayinTipiSablonu is global
        $template = YayinTipiSablonu::findOrFail($yayinTipiId);

        $currentAssignments = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $template->id)
            ->with('feature:id,slug,name,feature_category_id')
            ->get();

        $currentSlugs = $currentAssignments->pluck('feature.slug')->filter()->toArray();
        $plannedFeatures = Feature::whereIn('slug', $plannedFeatureSlugs)->get();
        $plannedSlugsNormalized = $plannedFeatures->pluck('slug')->toArray();

        $toAdd = array_diff($plannedSlugsNormalized, $currentSlugs);
        $toRemove = array_diff($currentSlugs, $plannedSlugsNormalized);
        $unchanged = array_intersect($currentSlugs, $plannedSlugsNormalized);

        return [
            'kategori_id' => $kategoriId,
            'yayin_tipi_id' => $yayinTipiId,
            'current_count' => count($currentSlugs),
            'planned_count' => count($plannedSlugsNormalized),
            'to_add' => array_values($toAdd),
            'to_remove' => array_values($toRemove),
            'unchanged_count' => count($unchanged),
        ];
    }

    private function computeInheritance(Feature $feature, int $kategoriId): array
    {
        $originId = $feature->feature_category_id;
        $originName = $originId ? $this->categoryName($originId) : null;

        return [
            'is_inherited' => $originId ? $originId !== $kategoriId : false,
            'origin_category_name' => $originName,
        ];
    }

    private function categoryName(int $kategoriId): ?string
    {
        if (!array_key_exists($kategoriId, $this->categoryNameCache)) {
            $this->categoryNameCache[$kategoriId] = IlanKategori::find($kategoriId)?->name;
        }

        return $this->categoryNameCache[$kategoriId];
    }
}
