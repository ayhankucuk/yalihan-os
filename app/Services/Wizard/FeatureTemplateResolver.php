<?php

namespace App\Services\Wizard;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * FeatureTemplateResolver — Scoped feature resolution for Wizard Step 2.
 *
 * SSOT: This is the Wizard-scoped resolver. System-wide SSOT is
 * App\Services\Ups\FeatureTemplateResolver (9+ consumers).
 * This class is used ONLY by WizardFeatureController for Step 2 priority scoring.
 * TODO(P2-DS-01): Evaluate consolidation into Ups\FeatureTemplateResolver.
 *
 * Resolves effective feature set based on:
 *   main_category_id + sub_category_id + listing_type_id
 *
 * Scope priority (higher = wins):
 *   1. listing_type  (400)
 *   2. sub_category  (300)
 *   3. main_category (200)
 *   4. global        (100)
 *   5. ai_design     (500, additive)
 *
 * Same feature_id/slug at multiple scopes → most specific scope wins.
 * ai_design assignments are additive (merged, not replaced).
 * Rolled-back records (rolled_back_at NOT NULL) are excluded.
 */
class FeatureTemplateResolver
{
    /**
     * Resolve effective features for a category + listing type combination.
     *
     * @param int $mainCategoryId Ana kategori ID
     * @param int|null $subCategoryId Alt kategori ID (nullable)
     * @param int $listingTypeId Yayın tipi ID
     * @return Collection Collection of resolved feature arrays
     */
    public function resolveFeatures(
        int $mainCategoryId,
        ?int $subCategoryId,
        int $listingTypeId
    ): Collection {
        $rows = DB::table('feature_assignments as fa')
            ->join('features as f', 'f.id', '=', 'fa.feature_id')
            ->leftJoin('feature_categories as fc', 'fc.id', '=', 'f.feature_category_id')
            ->whereNull('fa.rolled_back_at')
            ->where('fa.aktiflik_durumu', true)
            ->where('fa.is_visible', true)
            ->where(function ($q) use ($mainCategoryId, $subCategoryId, $listingTypeId) {
                // Global scope (no category/listing type)
                $q->where(function ($q2) {
                    $q2->whereNull('fa.main_category_id')
                        ->whereNull('fa.sub_category_id')
                        ->whereNull('fa.listing_type_id');
                });

                // Main category scope
                $q->orWhere(function ($q2) use ($mainCategoryId) {
                    $q2->where('fa.main_category_id', $mainCategoryId)
                        ->whereNull('fa.sub_category_id')
                        ->whereNull('fa.listing_type_id');
                });

                // Sub category scope
                if ($subCategoryId) {
                    $q->orWhere(function ($q2) use ($mainCategoryId, $subCategoryId) {
                        $q2->where('fa.main_category_id', $mainCategoryId)
                            ->where('fa.sub_category_id', $subCategoryId)
                            ->whereNull('fa.listing_type_id');
                    });
                }

                // Listing type scope (global + category-specific)
                $q->orWhere(function ($q2) use ($mainCategoryId, $subCategoryId, $listingTypeId) {
                    $q2->where('fa.listing_type_id', $listingTypeId)
                        ->where(function ($inner) use ($mainCategoryId, $subCategoryId) {
                            // Global listing-type assignments (no category filter)
                            $inner->where(function ($g) {
                                $g->whereNull('fa.main_category_id')
                                    ->whereNull('fa.sub_category_id');
                            });
                            // Main-category scoped listing-type assignments
                            $inner->orWhere(function ($m) use ($mainCategoryId) {
                                $m->where('fa.main_category_id', $mainCategoryId)
                                    ->whereNull('fa.sub_category_id');
                            });
                            // Sub-category scoped listing-type assignments
                            if ($subCategoryId) {
                                $inner->orWhere(function ($s) use ($mainCategoryId, $subCategoryId) {
                                    $s->where('fa.main_category_id', $mainCategoryId)
                                        ->where('fa.sub_category_id', $subCategoryId);
                                });
                            }
                        });
                });
            })
            ->orderBy('fa.display_order')
            ->orderBy('f.id')
            ->get([
                'fa.id as assignment_id',
                'fa.feature_id',
                'fa.main_category_id',
                'fa.sub_category_id',
                'fa.listing_type_id',
                'fa.scope_type',
                'fa.source_type',
                'fa.label_override',
                'fa.field_slug',
                'fa.field_type',
                'fa.group_name',
                'fa.display_order',
                'fa.is_required',
                'fa.options_json',
                'fa.metadata',
                'fa.visible_if_json',
                'fa.required_if_json',
                'fa.enabled_if_json',
                'f.name as feature_name',
                'f.slug as feature_slug',
                'f.type as feature_base_type',
                'f.unit as feature_unit',
                'f.description as feature_description',
                'f.options as feature_options',
                'fc.name as category_name',
                'fc.slug as category_slug',
            ]);

        return $this->collapseScopedAssignments(
            $rows,
            $mainCategoryId,
            $subCategoryId,
            $listingTypeId
        );
    }

    /**
     * Resolve features grouped by UI group.
     */
    public function resolveFeaturesGrouped(
        int $mainCategoryId,
        ?int $subCategoryId,
        int $listingTypeId
    ): Collection {
        return $this->resolveFeatures($mainCategoryId, $subCategoryId, $listingTypeId)
            ->groupBy(fn ($item) => $item['group'] ?: 'Genel');
    }

    /**
     * Collapse multiple scope-level assignments for the same feature.
     *
     * When the same feature_id or field_slug exists at multiple scope levels,
     * the most specific scope wins. ai_design is additive (always merged).
     */
    protected function collapseScopedAssignments(
        Collection $rows,
        int $mainCategoryId,
        ?int $subCategoryId,
        int $listingTypeId
    ): Collection {
        $grouped = $rows->groupBy(function ($row) {
            return $row->field_slug ?: $row->feature_slug;
        });

        return $grouped->map(function (Collection $candidates) use ($mainCategoryId, $subCategoryId, $listingTypeId) {
            $winner = $candidates
                ->sortByDesc(fn ($row) => $this->scoreAssignment(
                    $row,
                    $mainCategoryId,
                    $subCategoryId,
                    $listingTypeId
                ))
                ->first();

            return [
                'assignment_id' => (int) $winner->assignment_id,
                'feature_id' => (int) $winner->feature_id,
                'slug' => (string) ($winner->field_slug ?: $winner->feature_slug),
                'label' => (string) ($winner->label_override ?: $winner->feature_name),
                'type' => (string) ($winner->field_type ?: $winner->feature_base_type ?: 'text'), // context7-ignore
                'group' => (string) ($winner->group_name ?: $winner->category_name ?: 'Genel'),
                'group_slug' => (string) ($winner->category_slug ?: 'genel'),
                'required' => (bool) $winner->is_required,
                'display_order' => (int) $winner->display_order,
                'unit' => $winner->feature_unit,
                'description' => $winner->feature_description,
                'scope_type' => (string) $winner->scope_type,
                'source_type' => (string) $winner->source_type,
                'options' => $this->resolveOptions($winner),
                'meta' => $this->decodeJson($winner->metadata),
                'visible_if' => $this->decodeDependencyRule($winner->visible_if_json),
                'required_if' => $this->decodeDependencyRule($winner->required_if_json),
                'enabled_if' => $this->decodeDependencyRule($winner->enabled_if_json),
            ];
        })
            ->sortBy('display_order')
            ->values();
    }

    /**
     * Score an assignment row for scope priority.
     *
     * Higher score = more specific = wins.
     */
    protected function scoreAssignment(
        object $row,
        int $mainCategoryId,
        ?int $subCategoryId,
        int $listingTypeId
    ): int {
        // ai_design always has highest priority (additive)
        if ($row->source_type === 'ai_design') {
            return 500;
        }

        if ($row->listing_type_id && (int) $row->listing_type_id === $listingTypeId) {
            return 400;
        }

        if ($subCategoryId && $row->sub_category_id && (int) $row->sub_category_id === $subCategoryId) {
            return 300;
        }

        if ($row->main_category_id && (int) $row->main_category_id === $mainCategoryId) {
            return 200;
        }

        // Global scope
        return 100;
    }

    /**
     * Resolve options — prefer assignment-level override, fallback to feature-level.
     */
    protected function resolveOptions(object $row): ?array
    {
        // Assignment-level options override
        if ($row->options_json) {
            $decoded = json_decode($row->options_json, true);
            if (is_array($decoded) && !empty($decoded)) {
                return array_values($decoded);
            }
        }

        // Feature-level options fallback
        if ($row->feature_options) {
            $decoded = json_decode($row->feature_options, true);
            if (is_array($decoded) && !empty($decoded)) {
                return array_values($decoded);
            }
        }

        return null;
    }

    /**
     * Decode a JSON column safely.
     */
    protected function decodeJson(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Decode and validate a dependency rule JSON column.
     *
     * Valid format: {"field": "slug", "operator": "=", "value": "x"}
     * Supported operators: =, !=, in, not_in, truthy, falsy
     *
     * Returns null if: empty, malformed, or invalid operator.
     *
     * @param string|null $json Raw JSON from DB
     * @return array|null Validated rule or null
     */
    protected function decodeDependencyRule(?string $json): ?array
    {
        if (!$json) {
            return null;
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return null;
        }

        // Must have 'field' key
        if (empty($decoded['field']) || !is_string($decoded['field'])) {
            return null;
        }

        // Must have valid operator
        $validOperators = ['=', '!=', 'in', 'not_in', 'truthy', 'falsy'];
        $operator = $decoded['operator'] ?? null;

        if (!$operator || !in_array($operator, $validOperators, true)) {
            return null;
        }

        // truthy/falsy don't need a value
        if (in_array($operator, ['truthy', 'falsy'], true)) {
            return [
                'field' => $decoded['field'],
                'operator' => $operator,
            ];
        }

        // in/not_in need an array value
        if (in_array($operator, ['in', 'not_in'], true)) {
            if (!isset($decoded['value']) || !is_array($decoded['value'])) {
                return null;
            }
            return [
                'field' => $decoded['field'],
                'operator' => $operator,
                'value' => $decoded['value'],
            ];
        }

        // = / != need a scalar value
        if (!array_key_exists('value', $decoded)) {
            return null;
        }

        return [
            'field' => $decoded['field'],
            'operator' => $operator,
            'value' => $decoded['value'],
        ];
    }
}
