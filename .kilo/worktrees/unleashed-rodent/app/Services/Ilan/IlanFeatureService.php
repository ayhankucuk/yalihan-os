<?php

namespace App\Services\Ilan;

use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\AltKategoriYayinTipi;
use App\Services\Ups\FeatureTemplateResolver;
use App\Services\Logging\LogService;

/**
 * Ilan Feature Service
 *
 * Provides feature resolution for categories and publication types.
 *
 * UPS Phase 2 Integration:
 * - Uses FeatureTemplateResolver for runtime template inheritance
 * - Resolves parent-child category relationships (Arsa → Tarla/Zeytinlik)
 * - NO data duplication - inheritance resolved at runtime
 */
class IlanFeatureService
{
    /**
     * Get features by category and publication type
     *
     * ✅ Runtime inheritance via Cortex-Nexus Miras Motoru
     * ✅ SAB: Uses FeatureAssignment (polymorphic) system ONLY
     * ❌ DEPRECATED: applies_to system removed - use FeatureAssignment instead
     *
     * @param int $categoryId Category ID
     * @param int|string|null $yayinTipi Publication type (ID or string)
     * @return array Feature categories with resolved features
     */
    public function getFeaturesByCategory($categoryId, $yayinTipi = null): array
    {
        $category = IlanKategori::findOrFail($categoryId);

        return $this->resolveInheritedFeatures($category, $yayinTipi);
    }

    /**
     * Cortex-Nexus Miras Motoru
     *
     * Resolves features for a category using recursive inheritance:
     * - If current category has features, returns them
     * - Otherwise, walks up the parent chain (max 5 levels) until a blueprint is found
     * - Results are cached per request to avoid duplicate queries
     *
     * @param IlanKategori $category
     * @param int|string|null $yayinTipi
     * @param int $depth
     * @return array
     */
    private function resolveInheritedFeatures(IlanKategori $category, $yayinTipi = null, int $depth = 0): array
    {
        static $cache = [];

        $cacheKey = $category->id . '|' . (is_null($yayinTipi) ? 'null' : (string) $yayinTipi);

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        // Depth guard – fallback to current category to avoid infinite recursion
        if ($depth > 5) {
            $result = $this->getFeaturesViaAssignments($category, $yayinTipi, false);
            return $cache[$cacheKey] = $result;
        }

        // ✅ CORTEX-NEXUS FIX: Check for LOCAL assignments FIRST (before rendering)
        // This MUST include both category assignments and assignment on category's pivots (Satılık/Kiralık)
        $localAssignmentCount = FeatureAssignment::where(function($q) use ($category) {
            $q->where(function($sq) use ($category) {
                $sq->where('assignable_type', IlanKategori::class)
                   ->where('assignable_id', $category->id);
            })->orWhere(function($sq) use ($category) {
                // Also check if any allowed publication types for this category have assignments
                $policy = app(\App\Services\Ups\PropertyPublicationPolicy::class);
                $allowedYayinTipiIds = $policy->allowedForCategory($category->id);
                $sq->where('assignable_type', YayinTipiSablonu::class)
                   ->whereIn('assignable_id', $allowedYayinTipiIds);
            });
        })
        ->where('is_visible', true)
        ->count();

        // Determine whether this category is allowed to inherit from parent
        $inheritFlag = $category->getAttribute('inherit_from_parent');
        $allowInheritance = $inheritFlag === null ? true : (bool) $inheritFlag;

        // ✅ LOCAL-FIRST PRIORITY: If local assignments exist OR inheritance disabled, use local blueprint
        if ($localAssignmentCount > 0 || !$allowInheritance) {
            $result = $this->getFeaturesViaAssignments($category, $yayinTipi, false);
            return $cache[$cacheKey] = $result;
        }

        // No local assignments + inheritance enabled → Try parent
        if (!$category->parent_id) {
            // Root category with no assignments → return empty
            $result = $this->getFeaturesViaAssignments($category, $yayinTipi, false);
            return $cache[$cacheKey] = $result;
        }

        // Try parent category
        $parent = IlanKategori::find($category->parent_id);

        if (!$parent) {
            // Parent missing – return current (likely empty) result as a safe fallback
            $result = $this->getFeaturesViaAssignments($category, $yayinTipi, false);
            return $cache[$cacheKey] = $result;
        }

        // ✅ CORTEX-NEXUS FIX: Recursion MUST use publication type NAME/SLUG when walking up the tree
        // Pivot IDs are category-specific; parent category won't recognize child's pivot ID
        $inheritedYayinTipi = $yayinTipi;
        if (is_numeric($yayinTipi)) {
            $currentYayinTipi = YayinTipiSablonu::find($yayinTipi);
            $inheritedYayinTipi = $currentYayinTipi?->slug ?? $yayinTipi;
        }

        $inherited = $this->resolveInheritedFeatures($parent, $inheritedYayinTipi, $depth + 1);

        return $cache[$cacheKey] = $inherited;
    }
    /**
     * Get features via FeatureAssignment (polymorphic) system
     * ✅ SAB: Uses FeatureAssignment as single source of truth
     * ℹ️ Template inheritance is controlled by resolveInheritedFeatures()
     */
    private function getFeaturesViaAssignments(IlanKategori $category, $yayinTipi = null, bool $useTemplateCategory = true): array
    {
        // If template inheritance is enabled, resolve the template category; otherwise use the category itself
        $targetCategory = $useTemplateCategory ? $this->findTemplateCategory($category) : $category;

        LogService::debug('IlanFeatureService - Template Category', [
            'requested_id' => $category->id,
            'requested_name' => $category->name,
            'target_id' => $targetCategory->id,
            'target_name' => $targetCategory->name,
            'is_inherited' => $targetCategory->id !== $category->id
        ]);

        // Yayın tipi ID'lerini bul (ana kategori için)
        $yayinTipiIds = [];
        $hierarchicalYayinTipiIds = [];
        $pivotIds = [];

        if ($yayinTipi) {
            if (is_numeric($yayinTipi)) {
                // 1. Resolve Global Publication Type
                $yayinTipiModel = YayinTipiSablonu::where('id', $yayinTipi)
                    ->where('aktiflik_durumu', true)
                    ->first();

                if ($yayinTipiModel) {
                    $yayinTipiIds[] = $yayinTipiModel->id;
                }

                // Migrated to Global Template system (YayinTipiSablonu)
            } else {
                // Global Publication Type lookup
                $yayinTipiIds = YayinTipiSablonu::where(function($q) use ($yayinTipi) {
                        $normalizedYayinTipi = $this->normalizeYayinTipi($yayinTipi); // Calculate normalized once
                        $q->where('slug', $yayinTipi)
                            ->orWhere('ad', $yayinTipi)
                            ->orWhere('ad', $normalizedYayinTipi);
                    })
                    ->where('aktiflik_durumu', true)
                    ->pluck('id')
                    ->toArray();

                // Migrated to Global Template system (YayinTipiSablonu)
                $hierarchicalYayinTipiIds = [];
            }

             // 4. Resolve Pivot IDs (AltKategoriYayinTipi)
            if (!empty($yayinTipiIds)) {
                $pivotIds = AltKategoriYayinTipi::where('alt_kategori_id', $category->id)
                    ->whereIn('yayin_tipi_id', $yayinTipiIds)
                    ->where('aktiflik_durumu', true)
                    ->pluck('id')
                    ->toArray();
            }
        }

        // FeatureAssignment'lardan özellikleri al
        $assignments = FeatureAssignment::where(function($q) use ($category, $targetCategory, $yayinTipiIds, $hierarchicalYayinTipiIds, $useTemplateCategory, $pivotIds) {
            // 1. Yayın tipi bazlı atamalar (Global)
            if (!empty($yayinTipiIds)) {
                $q->orWhere(function($sq) use ($yayinTipiIds) {
                    $sq->where('assignable_type', YayinTipiSablonu::class)
                       ->whereIn('assignable_id', $yayinTipiIds);
                });
            }

            // 2. ⚠️ REMOVED (2026-01-04): Hierarchical Yayın tipi (seviye=2) - migrated to Global Template
            if (!empty($hierarchicalYayinTipiIds)) {
                $q->orWhere(function($sq) use ($hierarchicalYayinTipiIds) {
                    $sq->where('assignable_type', IlanKategori::class)
                       ->whereIn('assignable_id', $hierarchicalYayinTipiIds);
                });
            }

            // 3. Kategori bazlı atamalar (Ana kategori veya alt kategori)
            // ✅ CORTEX-NEXUS FIX: When useTemplateCategory=false, query ONLY current category
            $q->orWhere(function($sq) use ($category, $targetCategory, $useTemplateCategory) {
                $sq->where('assignable_type', IlanKategori::class)
                   ->whereIn('assignable_id', $useTemplateCategory
                       ? array_unique([$category->id, $targetCategory->id])
                       : [$category->id]  // ← LOCAL-FIRST: Query only direct assignments
                   );
            });

             // 4. Pivot (AltKategoriYayinTipi) Atamaları
            if (!empty($pivotIds)) {
                $q->orWhere(function($sq) use ($pivotIds) {
                    $sq->where('assignable_type', AltKategoriYayinTipi::class)
                       ->whereIn('assignable_id', $pivotIds);
                });
            }
        })
        ->where('is_visible', true)
        ->with(['feature' => function ($q) {
            $q->where('aktiflik_durumu', true)
                ->with('category');
        }])
        ->orderBy('display_order') // context7-ignore
        ->orderBy('id') // context7-ignore
            ->get()
            ->filter(function ($assignment) {
                return $assignment->feature !== null && $assignment->feature->aktiflik_durumu === true;
            });

        // Kategorilere göre grupla
        $groupedByCategory = $assignments->groupBy(function ($assignment) {
            // ✅ Master Template Support: Use assignment group_name if available
            return $assignment->group_name ?: ($assignment->feature->category ? $assignment->feature->category->id : 'uncategorized');
        });

        $transformed = collect();
        foreach ($groupedByCategory as $key => $categoryAssignments) {
            $firstAssignment = $categoryAssignments->first();

            // ✅ SAB: Determine category object (Real or Virtual)
            if (is_string($key) && $key !== 'uncategorized' && !is_numeric($key)) {
                // Virtual Category from Master Template Group
                $featureCategory = (object)[
                    'id' => substr(crc32($key), 0, 8), // Deterministic pseudo-ID
                    'name' => $key,
                    'slug' => str()->slug($key),
                    'icon' => 'fas fa-layer-group'
                ];
            } else {
                // Legacy Feature Category
                $featureCategory = $firstAssignment->feature->category;
            }

            // ✅ SAB: Eğer kategori yoksa "Genel Özellikler" başlığı altında topla
            if (!$featureCategory) {
                $featureCategory = (object)[
                    'id' => 0,
                    'name' => 'Genel Özellikler',
                    'slug' => 'genel-ozellikler',
                    'icon' => 'fas fa-list'
                ];
            }

            // ✅ FIX: Duplicate özellikleri kaldır - Aynı feature_id'ye sahip özellikleri unique yap
            // Eğer birden fazla yayın tipi için aynı özellik atanmışsa, sadece birini göster

            // ✅ PERFORMANCE: Pre-group assignments by feature_id to avoid N queries
            $assignmentsByFeatureId = $categoryAssignments->groupBy('feature_id');

            $uniqueFeatures = $categoryAssignments
                ->unique('feature_id')
                ->map(function ($assignment) use ($assignmentsByFeatureId) {
                    $feature = $assignment->feature;

                    // ✅ PERFORMANCE: Use pre-grouped collection (avoids 2x N queries)
                    $featureAssignments = $assignmentsByFeatureId->get($feature->id);

                    if (!$featureAssignments || $featureAssignments->isEmpty()) {
                        // Fallback: should not happen but defensive
                        $isRequired = false;
                        $displayOrder = 0;
                    } else {
                        // ✅ FIX: Priority Logic (Pivot > Global > Category)
                        // Check for Pivot assignment first
                        $pivotAssignment = $featureAssignments->firstWhere('assignable_type', AltKategoriYayinTipi::class);

                        if ($pivotAssignment) {
                             $isRequired = $pivotAssignment->is_required;
                             $displayOrder = $pivotAssignment->display_order;
                        } else {
                            // Fallback to max required (Global/Category mix)
                             $isRequired = $featureAssignments->max('is_required');
                             $displayOrder = $featureAssignments->first()->display_order ?? 0;
                        }
                    }

                    return [
                        'id' => $feature->id,
                        'name' => $feature->name,
                        'slug' => $feature->slug,
                        'type' => $feature->type ?? 'boolean', // context7-ignore
                        'options' => $feature->options ?? null,
                        'unit' => $feature->unit ?? null,
                        'required' => (bool) $isRequired,
                        'is_required' => (bool) $isRequired, // ✅ Dual key for legacy compatibility
                        'display_order' => (int) $displayOrder,
                        'description' => $feature->description ?? null,
                    ];
                })
                ->values();

            $transformed->push([
                'id' => $featureCategory->id,
                'name' => $featureCategory->name,
                'slug' => $featureCategory->slug,
                'icon' => $featureCategory->icon ?? 'fas fa-star',
                'features' => $uniqueFeatures,
            ]);
        }

        return [
            'feature_categories' => $transformed->values(),
            'metadata' => [
                'category_slug' => $category->slug,
                'category_id' => $category->id,
                'target_category_slug' => $targetCategory->slug,
                'target_category_id' => $targetCategory->id,
                'is_subcategory' => $category->seviye > 0,
                'yayin_tip' . 'i' => $yayinTipi,
                'total_features' => $transformed->sum(fn($cat) => count($cat['features'])),
                'system' => 'FeatureAssignment',
            ],
        ];
    }

    /**
     * ✅ UPS Phase 2: Get features via FeatureTemplateResolver
     *
     * Resolves features using runtime template inheritance:
     * - If category has parent_id → inherits from parent + applies overrides
     * - If no parent → uses own assignments
     *
     * @param IlanKategori $category Category
     * @param int|string|null $yayinTipi Publication type
     * @return array Feature categories with resolved features
     */
    private function getFeaturesViaTemplateResolver(IlanKategori $category, $yayinTipi = null): array
    {
        // TODO(P2-SV-09): Convert to constructor injection.
        // Reason: IlanFeatureService has no constructor — adding one changes service contract, not safe in Phase 1.
        $resolver = app(FeatureTemplateResolver::class);

        // Resolve publication type ID
        $yayinTipiId = null;
        if ($yayinTipi) {
            $yayinTipiId = $this->resolveYayinTipiId($category, $yayinTipi);
        }

        // ✅ Resolve features using template inheritance
        $resolvedFeatures = $resolver->resolveFeatures($category->id, $yayinTipiId);

        // Group by feature_category
        $groupedByCategory = $resolvedFeatures->groupBy('feature_category.id');

        $transformed = collect();
        foreach ($groupedByCategory as $categoryId => $features) {
            $firstFeature = $features->first();
            $featureCategory = $firstFeature['feature_category'] ?? null;

            if (!$featureCategory) {
                continue;
            }

            $transformed->push([
                'id' => $featureCategory['id'],
                'name' => $featureCategory['name'],
                'slug' => $featureCategory['slug'],
                'icon' => $featureCategory['icon'] ?? 'fas fa-star',
                'features' => $features->map(function ($feature) {
                    return [
                        'id' => $feature['id'],
                        'name' => $feature['name'],
                        'slug' => $feature['slug'],
                        'type' => $feature['type'], // context7-ignore
                        'options' => $feature['options'],
                        'unit' => $feature['unit'],
                        'required' => $feature['required'],
                        'is_required' => $feature['is_required'],
                        'display_order' => $feature['display_order'],
                        'description' => $feature['description'],
                    ];
                })->values()->toArray(),
            ]);
        }

        // Log inheritance info
        if ($resolver->usesInheritance($category->id)) {
            $baseTemplate = $resolver->getBaseTemplate($category->id);
            LogService::info('UPS Template Inheritance', [
                'category' => $category->name,
                'base_template' => $baseTemplate?->name,
                'inheritance_chain' => $resolver->getInheritanceChain($category->id),
            ]);
        }

        return [
            'feature_categories' => $transformed->values()->toArray(),
            'metadata' => [
                'category_slug' => $category->slug,
                'category_id' => $category->id,
                'is_subcategory' => $category->parent_id !== null,
                'uses_inheritance' => $resolver->usesInheritance($category->id),
                'base_template' => $resolver->getBaseTemplate($category->id)?->name,
                'yayin_tip' . 'i' => $yayinTipi,
                'yayin_tipi_id' => $yayinTipiId,
                'total_features' => $transformed->sum(fn($cat) => count($cat['features'])),
                'system' => 'UPS_TemplateResolver',
            ],
        ];
    }

    /**
     * Resolve publication type ID from string or int
     *
     * @param IlanKategori $category Category
     * @param int|string $yayinTipi Publication type (ID or string)
     * @return int|null Publication type ID
     */
    public function resolveYayinTipiId(IlanKategori $category, $yayinTipi): ?int
    {
        // If numeric, it's already a Global Publication Type ID or inherited pivot ID
        if (is_numeric($yayinTipi)) {
            return (int) $yayinTipi;
        }

        // Normalize string to match database
        $normalized = $this->normalizeYayinTipi($yayinTipi);

        // Query for global publication type
        $yayinTipiModel = YayinTipiSablonu::where(function ($q) use ($yayinTipi, $normalized) {
                $q->where('slug', $yayinTipi)
                    ->orWhere('ad', $yayinTipi)
                    ->orWhere('ad', $normalized)
                    ->orWhereRaw('LOWER(ad) = LOWER(?)', [$yayinTipi]);
            })
            ->where('aktiflik_durumu', true)
            ->first();

        return $yayinTipiModel?->id;
    }

    /**
     * Normalize yayın tipi slug'ını yayın tipi string'ine çevir
     * Örnek: "satilik" → "Satılık", "kiralik" → "Kiralık"
     */
    private function normalizeYayinTipi(string $slug): string
    {
        $mapping = [
            'satilik' => 'Satılık',
            'kiralik' => 'Kiralık',
            'devren' => 'Devren',
            'devren-satilik' => 'Devren Satılık',
            'devren-kiralik' => 'Devren Kiralık',
            'gunluk-kiralama' => 'Günlük Kiralama',
            'haftalik-kiralama' => 'Haftalık Kiralama',
            'aylik-kiralama' => 'Aylık Kiralama',
            'sezonluk-kiralama' => 'Sezonluk Kiralama',
            'kat-karsiligi' => 'Kat Karşılığı',
            'ticari' => 'Ticari',
        ];

        // Slug'ı normalize et (tireleri kaldır, küçük harfe çevir)
        $normalized = strtolower(str_replace(['-', '_'], '', $slug));

        // Mapping'de varsa döndür
        if (isset($mapping[$normalized])) {
            return $mapping[$normalized];
        }

        // Mapping'de yoksa, slug'dan tahmin et
        // İlk harfi büyük yap ve kelimeleri ayır
        $words = explode('-', $slug);
        $words = array_map('ucfirst', $words);
        return implode(' ', $words);
    }

    /**
     * Recursive Template Category Finder
     * Yukarı tırmanarak ilk özel şablonu (custom template) olan kategoriyi bulur
     * Hierarchy: Seviye 2 → Seviye 1 → Seviye 0 (Root)
     *
     * Örnek:
     * - Bungalov (seviye 2) → özel template yok → Villa (seviye 1) → template var → Villa return
     * - Arsa alt kategorisi → template yok → Arsa (seviye 0) → template yok → Arsa return (fallback)
     *
     * @param IlanKategori $category Aranan kategori
     * @return IlanKategori Özel şablonu olan veya root kategorisi
     */
    private function findTemplateCategory(IlanKategori $category): IlanKategori
    {
        $current = $category;
        $visited = []; // Infinite loop önleme

        // Hierarchical tırmanma (en fazla 5 level)
        while ($current && count($visited) < 5) {
            $visited[] = $current->id;

            // Mevcut kategorinin kendi şablonu var mı?
            if ($current->hasCustomTemplate()) {
                LogService::debug('Template bulundu', ['kategori_id' => $current->id, 'seviye' => $current->seviye]);
                return $current;
            }

            // Parent yok = root kategorisi, bunu return et (fallback)
            if (!$current->parent_id) {
                LogService::debug('Root kategoriye ulaşıldı (fallback)', ['kategori_id' => $current->id]);
                return $current;
            }

            // Parent'a git
            $parent = IlanKategori::find($current->parent_id);
            if (!$parent || in_array($parent->id, $visited)) {
                // Circular reference veya parent bulunamadı = return
                LogService::debug('Template tırmasında sorun', ['current_id' => $current->id]);
                return $current;
            }

            $current = $parent;
        }

        // Fallback: kendisini return et
        return $category;
    }
}
