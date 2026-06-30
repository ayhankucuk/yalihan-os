<?php

declare(strict_types=1);

namespace App\Services\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\MasterTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Traits\GuardsAgentWrites;

/**
 * Master Template Service for bulk feature management
 *
 * Context7 Compliant:
 * - Uses aktiflik_durumu (Canonical)
 * - Uses display_order (Canonical)
 * - Wildcard cache pattern (NO Cache::tags)
 */
class UpsMasterTemplateService
{
    use GuardsAgentWrites;
    public function __construct(
        private readonly UpsCacheService $cacheService
    ) {}

    /**
     * Get all available master templates
     */
    public function getAllTemplates(): Collection
    {
        return MasterTemplate::active()
            ->ordered() // context7-ignore
            ->get();
    }

    /**
     * Get template by ID with features
     */
    public function getTemplateWithFeatures(int $templateId): ?array
    {
        $template = MasterTemplate::find($templateId);

        if (!$template) {
            return null;
        }

        $features = Feature::whereIn('id', $template->feature_ids ?? [])
            ->where('aktiflik_durumu', true)
            ->get();

        return [
            'template' => $template,
            'features' => $features,
            'feature_count' => $features->count(),
            'missing_features' => count($template->feature_ids ?? []) - $features->count(),
        ];
    }

    /**
     * Create a new master template
     */
    public function createTemplate(array $data): MasterTemplate
    {
        $this->blockAgentWrite('createTemplate');

        $template = MasterTemplate::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'feature_ids' => $data['feature_ids'] ?? [],
            'metadata' => $data['metadata'] ?? [],
            'aktiflik_durumu' => $data['aktiflik_durumu'] ?? true,
            'display_order' => $data['display_order'] ?? 0,
            'created_by' => auth()->id(),
        ]);

        Log::channel('daily')->info('Master template created', [
            'template_id' => $template->id,
            'name' => $template->name,
            'feature_count' => count($template->feature_ids ?? []),
        ]);

        return $template;
    }

    /**
     * Create template from existing category
     */
    public function createFromCategory(IlanKategori $kategori, ?string $name = null): MasterTemplate
    {
        // Get all features assigned to this category
        $featureIds = FeatureAssignment::where('assignable_type', IlanKategori::class)
            ->where('assignable_id', $kategori->id)
            ->pluck('feature_id')
            ->toArray();

        return $this->createTemplate([
            'name' => $name ?? "{$kategori->ad} Şablonu",
            'description' => "'{$kategori->ad}' kategorisinden oluşturuldu",
            'feature_ids' => $featureIds,
            'metadata' => [
                'source_category_id' => $kategori->id,
                'source_category_name' => $kategori->ad,
                'created_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update an existing template
     */
    public function updateTemplate(int $templateId, array $data): ?MasterTemplate
    {
        $this->blockAgentWrite('updateTemplate');

        $template = MasterTemplate::find($templateId);

        if (!$template) {
            return null;
        }

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = Str::slug($data['name']);
        }

        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['feature_ids'])) {
            $updateData['feature_ids'] = $data['feature_ids'];
        }

        if (isset($data['aktiflik_durumu'])) {
            $updateData['aktiflik_durumu'] = $data['aktiflik_durumu'];
        }

        if (isset($data['display_order'])) {
            $updateData['display_order'] = $data['display_order'];
        }

        $template->update($updateData);

        Log::channel('daily')->info('Master template updated', [
            'template_id' => $template->id,
            'changes' => array_keys($updateData),
        ]);

        return $template->fresh();
    }

    /**
     * Delete a template (soft delete)
     */
    public function deleteTemplate(int $templateId): bool
    {
        $this->blockAgentWrite('deleteTemplate');

        $template = MasterTemplate::find($templateId);

        if (!$template) {
            return false;
        }

        $template->delete();

        Log::channel('daily')->info('Master template deleted', [
            'template_id' => $templateId,
            'name' => $template->name,
        ]);

        return true;
    }

    /**
     * Apply template to an assignable entity (Category or Publication Type)
     */
    public function applyToEntity(int $templateId, string $entityType, int $entityId, array $options = []): array
    {
        $this->blockAgentWrite('applyToEntity');

        $template = MasterTemplate::find($templateId);

        if (!$template) {
            return [
                'success' => false,
                'message' => 'Şablon bulunamadı',
            ];
        }

        $mode = $options['mode'] ?? 'merge'; // merge, replace, diff
        $existingFeatureIds = FeatureAssignment::where('assignable_type', $entityType)
            ->where('assignable_id', $entityId)
            ->pluck('feature_id')
            ->toArray();

        $templateFeatureIds = $template->feature_ids ?? [];
        $added = 0;
        $removed = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            // If replace mode, remove existing assignments first
            if ($mode === 'replace') {
                $removed = FeatureAssignment::where('assignable_type', $entityType)
                    ->where('assignable_id', $entityId)
                    ->delete();
                $existingFeatureIds = [];
            }

            // Add new features
            foreach ($templateFeatureIds as $featureId) {
                if (in_array($featureId, $existingFeatureIds)) {
                    $skipped++;
                    continue;
                }

                FeatureAssignment::create([
                    'feature_id' => $featureId,
                    'assignable_type' => $entityType,
                    'assignable_id' => $entityId,
                    'display_order' => $added,
                    'aktiflik_durumu' => true,
                ]);

                $added++;
            }

            // If diff mode, remove features not in template
            if ($mode === 'diff') {
                $toRemove = array_diff($existingFeatureIds, $templateFeatureIds);
                $removed = FeatureAssignment::where('assignable_type', $entityType)
                    ->where('assignable_id', $entityId)
                    ->whereIn('feature_id', $toRemove)
                    ->delete();
            }

            DB::commit();

            // Invalidate cache
            $this->cacheService->invalidate('assignments', (string) $entityId);

            Log::channel('daily')->info('Master template applied', [
                'template_id' => $templateId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'mode' => $mode,
                'added' => $added,
                'removed' => $removed,
                'skipped' => $skipped,
            ]);

            return [
                'success' => true,
                'message' => "Şablon başarıyla uygulandı",
                'stats' => [
                    'added' => $added,
                    'removed' => $removed,
                    'skipped' => $skipped,
                    'total_features' => count($templateFeatureIds),
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('Master template application failed', [
                'template_id' => $templateId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Şablon uygulanırken hata oluştu: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Legacy wrapper for applyToEntity
     */
    public function applyToCategory(int $templateId, int $categoryId, array $options = []): array
    {
        return $this->applyToEntity($templateId, IlanKategori::class, $categoryId, $options);
    }

    /**
     * Apply template to multiple categories
     */
    public function applyToMultipleCategories(int $templateId, array $categoryIds, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total_added' => 0,
            'total_removed' => 0,
        ];

        foreach ($categoryIds as $categoryId) {
            $result = $this->applyToCategory($templateId, $categoryId, $options);

            if ($result['success']) {
                $results['success'][] = $categoryId;
                $results['total_added'] += $result['stats']['added'] ?? 0;
                $results['total_removed'] += $result['stats']['removed'] ?? 0;
            } else {
                $results['failed'][] = [
                    'category_id' => $categoryId,
                    'error' => $result['message'],
                ];
            }
        }

        return $results;
    }

    /**
     * Compare template with category
     */
    public function compareWithCategory(int $templateId, int $categoryId): array
    {
        $template = MasterTemplate::find($templateId);
        $kategori = IlanKategori::find($categoryId);

        if (!$template || !$kategori) {
            return ['error' => 'Şablon veya kategori bulunamadı'];
        }

        $templateFeatureIds = $template->feature_ids ?? [];
        $categoryFeatureIds = FeatureAssignment::where('assignable_type', IlanKategori::class)
            ->where('assignable_id', $categoryId)
            ->pluck('feature_id')
            ->toArray();

        $onlyInTemplate = array_diff($templateFeatureIds, $categoryFeatureIds);
        $onlyInCategory = array_diff($categoryFeatureIds, $templateFeatureIds);
        $inBoth = array_intersect($templateFeatureIds, $categoryFeatureIds);

        // Get feature details
        $allFeatureIds = array_unique(array_merge($templateFeatureIds, $categoryFeatureIds));
        $features = Feature::whereIn('id', $allFeatureIds)->get()->keyBy('id');

        return [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'feature_count' => count($templateFeatureIds),
            ],
            'category' => [
                'id' => $kategori->id,
                'name' => $kategori->ad,
                'feature_count' => count($categoryFeatureIds),
            ],
            'comparison' => [
                'only_in_template' => collect($onlyInTemplate)->map(fn($id) => [
                    'id' => $id,
                    'name' => $features[$id]->ad ?? 'Bilinmiyor',
                ])->values(),
                'only_in_category' => collect($onlyInCategory)->map(fn($id) => [
                    'id' => $id,
                    'name' => $features[$id]->ad ?? 'Bilinmiyor',
                ])->values(),
                'in_both' => collect($inBoth)->map(fn($id) => [
                    'id' => $id,
                    'name' => $features[$id]->ad ?? 'Bilinmiyor',
                ])->values(),
            ],
            'match_percentage' => count($templateFeatureIds) > 0
                ? round((count($inBoth) / count($templateFeatureIds)) * 100, 1)
                : 0,
        ];
    }

    /**
     * Duplicate a template
     */
    public function duplicateTemplate(int $templateId, ?string $newName = null): ?MasterTemplate
    {
        $original = MasterTemplate::find($templateId);

        if (!$original) {
            return null;
        }

        return $this->createTemplate([
            'name' => $newName ?? "{$original->name} (Kopya)",
            'description' => $original->description,
            'feature_ids' => $original->feature_ids,
            'metadata' => array_merge($original->metadata ?? [], [
                'duplicated_from' => $original->id,
                'duplicated_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Get template usage statistics
     */
    public function getTemplateStats(int $templateId): array
    {
        $template = MasterTemplate::find($templateId);

        if (!$template) {
            return ['error' => 'Şablon bulunamadı'];
        }

        $featureIds = $template->feature_ids ?? [];

        // Count how many categories have these features
        $categoryUsage = FeatureAssignment::where('assignable_type', IlanKategori::class)
            ->whereIn('feature_id', $featureIds)
            ->select('assignable_id')
            ->distinct()
            ->count();

        // Get feature details with usage
        $features = Feature::whereIn('id', $featureIds)
            ->withCount(['assignments'])
            ->get();

        return [
            'template' => $template,
            'feature_count' => count($featureIds),
            'categories_with_features' => $categoryUsage,
            'features' => $features->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->ad,
                'usage_count' => $f->assignments_count,
            ]),
            'average_feature_usage' => $features->avg('assignments_count') ?? 0,
        ];
    }

    /**
     * Suggest templates based on category's current features
     */
    public function suggestTemplatesForCategory(int $categoryId): Collection
    {
        $categoryFeatureIds = FeatureAssignment::where('assignable_type', IlanKategori::class)
            ->where('assignable_id', $categoryId)
            ->pluck('feature_id')
            ->toArray();

        if (empty($categoryFeatureIds)) {
            return collect();
        }

        return MasterTemplate::active()
            ->get()
            ->map(function ($template) use ($categoryFeatureIds) {
                $templateFeatureIds = $template->feature_ids ?? [];
                $overlap = count(array_intersect($templateFeatureIds, $categoryFeatureIds));
                $matchScore = count($templateFeatureIds) > 0
                    ? ($overlap / count($templateFeatureIds)) * 100
                    : 0;

                return [
                    'template' => $template,
                    'match_score' => round($matchScore, 1),
                    'overlapping_features' => $overlap,
                    'missing_features' => count($templateFeatureIds) - $overlap,
                ];
            })
            ->filter(fn($item) => $item['match_score'] > 0)
            ->sortByDesc('match_score')
            ->values();
    }
}
