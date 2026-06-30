<?php

namespace App\Services\Ups;

use App\Enums\IlanDurumu;

use App\Enums\UpsFeatureLifecycle;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;

/**
 * UPS Feature Governance Service
 *
 * Manages feature lifecycle transitions and governance rules
 *
 * Context7: Read-only metrics + lifecycle state machine
 * - No FeatureTemplateResolver mutations
 * - No AI layer interactions
 * - Structured logging via LogService
 */
class UpsFeatureGovernanceService
{
    /**
     * Get usage statistics for features
     *
     * @param array $filters Optional filters (lifecycle, aktiflik_durumu, orphaned)
     * @return array Feature usage metrics
     */
    public function getUsageStats(array $filters = []): array
    {
        $query = Feature::query()
            ->withCount('assignments')
            ->with('category');

        // Apply filters
        if (!empty($filters['lifecycle'])) {
            $query->where('lifecycle', $filters['lifecycle']);
        }

        if (isset($filters['aktiflik_durumu'])) {
            $query->where('aktiflik_durumu', (bool) $filters['aktiflik_durumu']);
        }

        if (!empty($filters['orphaned'])) {
            $query->orphaned();
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('slug', 'like', "%{$s}%");
            });
        }

        $features = $query->orderBy('slug')->get(); // context7-ignore

        return $features->map(function ($feature) {
            // Count unique templates (kategori + yayin_tipi combinations)
            $templatesCount = FeatureAssignment::where('feature_id', $feature->id)
                ->select('assignable_type', 'assignable_id')
                ->distinct()
                ->count();

            return [
                'id' => $feature->id,
                'slug' => $feature->slug,
                'name' => $feature->name,
                'type' => $feature->type, // context7-ignore
                'aktiflik_durumu' => $feature->aktiflik_durumu,
                'lifecycle' => $feature->lifecycle?->value ?? 'active', // context7-ignore
                'lifecycle_label' => $feature->lifecycle?->label() ?? IlanDurumu::YAYINDA->value,
                'lifecycle_badge_color' => $feature->lifecycle?->badgeColor() ?? 'green',
                'category' => $feature->category?->name,
                'assignments_count' => $feature->assignments_count,
                'templates_count' => $templatesCount,
                'is_orphaned' => $feature->assignments_count === 0,
                'is_inactive' => !$feature->aktiflik_durumu,
                'deprecated_at' => $feature->deprecated_at?->toDateTimeString(),
                'archived_at' => $feature->archived_at?->toDateTimeString(),
                'last_used_at' => $feature->last_used_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Validate if feature can be assigned
     *
     * @param Feature $feature
     * @throws \Exception
     */
    public function validateAssignable(Feature $feature): void
    {
        // Check active state first
        if (!$feature->aktiflik_durumu) {
            throw new \Exception("Feature '{$feature->slug}' is inactive (aktiflik_durumu=false) and cannot be assigned.");
        }

        // Check lifecycle
        if (!$feature->lifecycle) {
            return; // No lifecycle set, allow (backward compatibility)
        }

        if ($feature->lifecycle === UpsFeatureLifecycle::ARCHIVED) {
            throw new \Exception("Feature '{$feature->slug}' is archived and cannot be assigned.");
        }

        if ($feature->lifecycle === UpsFeatureLifecycle::DRAFT) {
            throw new \Exception("Feature '{$feature->slug}' is in draft state and cannot be assigned.");
        }

        // Deprecated is allowed (will trigger warning in caller)
    }

    /**
     * Transition feature lifecycle state
     *
     * @param Feature $feature
     * @param string $to Target lifecycle state
     * @param bool $confirm Confirmation required for destructive transitions
     * @param string|null $reason Optional reason for transition
     * @return Feature Updated feature
     * @throws \InvalidArgumentException
     */
    public function transition(Feature $feature, string $to, bool $confirm = false, ?string $reason = null): Feature
    {
        $from = $feature->lifecycle ?? UpsFeatureLifecycle::ACTIVE;
        $toEnum = UpsFeatureLifecycle::from($to);

        // Validate transition
        if (!$from->canTransitionTo($toEnum)) {
            throw new \InvalidArgumentException(
                "Invalid lifecycle transition from {$from->value} to {$toEnum->value}"
            );
        }

        // Check confirmation for destructive transitions
        if (($toEnum === UpsFeatureLifecycle::ARCHIVED) && !$confirm) {
            throw new \InvalidArgumentException(
                "Confirmation required for archiving feature"
            );
        }

        DB::beginTransaction();
        try {
            // Update lifecycle
            $feature->lifecycle = $toEnum;

            // Set timestamps based on target state
            switch ($toEnum) {
                case UpsFeatureLifecycle::DEPRECATED:
                    $feature->deprecated_at = now();
                    $feature->archived_at = null;
                    break;

                case UpsFeatureLifecycle::ARCHIVED:
                    $feature->archived_at = now();
                    if (!$feature->deprecated_at) {
                        $feature->deprecated_at = now();
                    }
                    break;

                case UpsFeatureLifecycle::ACTIVE:
                    // Clear deprecation when reactivating
                    if ($from === UpsFeatureLifecycle::DEPRECATED) {
                        $feature->deprecated_at = null;
                    }
                    break;

                default:
                    break;
            }

            $feature->save();

            // Log transition
            LogService::info('UPS Feature lifecycle transition', [
                'feature_id' => $feature->id,
                'feature_slug' => $feature->slug,
                'lifecycle_from' => $from->value,
                'lifecycle_to' => $toEnum->value,
                'user_id' => auth()->id(),
                'transition_reason' => $reason,
            ]);

            DB::commit();

            return $feature->fresh();
        } catch (\Exception $e) {
            DB::rollBack();

            LogService::error('UPS Feature lifecycle transition failed', [
                'feature_id' => $feature->id,
                'from' => $from->value,
                'to' => $to,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Get governance summary report
     *
     * @return array Summary metrics
     */
    public function getSummaryReport(): array
    {
        return [
            'archived_but_assigned' => Feature::lifecycle(UpsFeatureLifecycle::ARCHIVED)
                ->has('assignments')
                ->count(),

            'inactive_but_assigned' => Feature::where('aktiflik_durumu', false)
                ->has('assignments')
                ->count(),

            'deprecated_assigned' => Feature::lifecycle(UpsFeatureLifecycle::DEPRECATED)
                ->has('assignments')
                ->count(),

            'orphaned_count' => Feature::orphaned()->count(),

            'total_by_lifecycle' => [
                'draft' => Feature::lifecycle(UpsFeatureLifecycle::DRAFT)->count(),
                'aktif' => Feature::lifecycle(UpsFeatureLifecycle::ACTIVE)->count(), // ✅ SAB: 'aktif'
                'deprecated' => Feature::lifecycle(UpsFeatureLifecycle::DEPRECATED)->count(),
                'archived' => Feature::lifecycle(UpsFeatureLifecycle::ARCHIVED)->count(),
            ],
        ];
    }

    /**
     * Legacy-compatible feature list by feature categories
     * Context7: Read-only, filters by category slug and yayin tipi when provided
     *
     * @param string|null $appliesTo Ilan kategori slug (konut, arsa, isyeri)
     * @param string|null $featureCategorySlug Feature category slug (optional)
     * @param string|null $yayinTipiSlug Publication type slug (optional)
     * @return array
     */
    public function listFeaturesLegacy(?string $appliesTo = null, ?string $featureCategorySlug = null, ?string $yayinTipiSlug = null, bool $includeInactive = false): array
    {
        // ✅ Show all categories (including inactive) if requested
        $categoriesQuery = \App\Models\FeatureCategory::query();
        if (!$includeInactive) {
            $categoriesQuery->where('aktiflik_durumu', true);
        }
        if ($featureCategorySlug) {
            $categoriesQuery->where('slug', $featureCategorySlug);
        }
        $categories = $categoriesQuery->orderBy('display_order')->orderBy('name')->get(); // context7-ignore

        if ($appliesTo === 'arsa') {
            $excludeSlugs = ['ic-ozellikleri', 'dis-ozellikleri', 'muhit', 'ulasim', 'cephe', 'manzara'];
            $categories = $categories->reject(fn($c) => in_array($c->slug, $excludeSlugs, true));
        }

        $result = [];
        foreach ($categories as $category) {
            // ✅ Show all features (including inactive) if requested
            $featuresQuery = Feature::where('feature_category_id', $category->id);
            if (!$includeInactive) {
                $featuresQuery->where('aktiflik_durumu', true);
            }

            if ($yayinTipiSlug) {
                // Prefer assignment existence over embedded publication type hints
                $featuresQuery->where(function ($q) use ($category, $yayinTipiSlug) {
                    $q->whereExists(function ($sub) use ($category, $yayinTipiSlug) {
                        $sub->from('feature_assignments')
                            ->join('yayin_tipi_sablonlari', 'feature_assignments.assignable_id', '=', 'yayin_tipi_sablonlari.id')
                            ->whereColumn('feature_assignments.feature_id', 'features.id')
                            ->where('feature_assignments.assignable_type', \App\Models\YayinTipiSablonu::class)
                            ->where('yayin_tipi_sablonlari.slug', $yayinTipiSlug)
                            ->where('feature_assignments.is_visible', true);
                    });
                });
            }

            $features = $featuresQuery
                ->orderBy('display_order') // context7-ignore
                ->orderBy('name') // context7-ignore
                ->get([
                    'id',
                    'name',
                    'slug',
                    'type', // context7-ignore
                    'options',
                    'unit',
                    'is_required',
                    'is_filterable',
                    'display_order',
                    'description',
                ]);

            if ($features->isNotEmpty()) {
                $result[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon ?? 'fas fa-star',
                    'features' => $features->map(function ($feature) {
                        $options = null;
                        if ($feature->options) {
                            if (is_string($feature->options)) {
                                $decoded = json_decode($feature->options, true);
                                $options = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                            } elseif (is_array($feature->options)) {
                                $options = $feature->options;
                            }
                        }

                        return [
                            'id' => $feature->id,
                            'name' => $feature->name,
                            'slug' => $feature->slug,
                            'type' => $feature->type, // context7-ignore
                            'options' => $options,
                            'unit' => $feature->unit,
                            'is_required' => (bool) $feature->is_required,
                            'is_filterable' => (bool) $feature->is_filterable,
                            'description' => $feature->description,
                            'aktiflik_durumu' => (bool) $feature->aktiflik_durumu, // ✅ Include aktiflik_durumu for frontend
                        ];
                    }),
                ];
            }
        }

        return $result;
    }
}
