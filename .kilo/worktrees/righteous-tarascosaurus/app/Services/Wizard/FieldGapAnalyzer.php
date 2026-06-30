<?php

namespace App\Services\Wizard;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FieldGapAnalyzer — Identifies missing, underutilized, and suspicious fields.
 *
 * Analyzes the gap between "what fields exist in the feature library"
 * and "what is currently assigned to this category/listing type scope."
 *
 * Sources (checked in order, gracefully degrades if unavailable):
 *   1. Feature library (features table) — canonical feature pool
 *   2. Feature assignments (cross-scope usage stats)
 *   3. Listing completion stats (ilan_features hit rate)
 *   4. AI design history (previously accepted/rejected suggestions)
 *
 * Missing sources → meta.missing_signals[] with explanation.
 *
 * Output:
 *   - missing_candidates: features in library but NOT assigned to this scope
 *   - low_coverage_fields: assigned but rarely filled by users (<20% fill rate)
 *   - dependency_candidates: fields that could benefit from conditional logic
 *   - meta: signal health, missing sources
 */
class FieldGapAnalyzer
{
    /**
     * Analyze field gaps for a given scope.
     *
     * @param int $mainCategoryId Ana kategori ID
     * @param int|null $subCategoryId Alt kategori ID (nullable)
     * @param int $listingTypeId Yayın tipi ID
     * @param array $currentSchema Current resolved schema (from EffectiveWizardSchemaResolver)
     * @return array Analysis result
     */
    public function analyze(
        int $mainCategoryId,
        ?int $subCategoryId,
        int $listingTypeId,
        array $currentSchema
    ): array {
        $currentSlugs = collect($currentSchema['fields'] ?? [])
            ->pluck('slug')
            ->filter()
            ->values()
            ->toArray();

        $currentFeatureIds = collect($currentSchema['fields'] ?? [])
            ->pluck('feature_id')
            ->filter()
            ->values()
            ->toArray();

        $missingSignals = [];

        // Source 1: Feature library — canonical feature pool
        $missingCandidates = $this->findMissingFromLibrary($currentFeatureIds, $currentSlugs);

        // Source 2: Cross-scope usage stats
        $crossScopeUsage = $this->getCrossScopeUsage(
            $mainCategoryId,
            $subCategoryId,
            $listingTypeId,
            $currentFeatureIds
        );

        // Source 3: Listing completion stats (fill rate)
        $lowCoverageFields = $this->getLowCoverageFields($currentSchema, $missingSignals);

        // Source 4: Dependency candidates
        $dependencyCandidates = $this->identifyDependencyCandidates($currentSchema);

        return [
            'missing_candidates' => $missingCandidates->toArray(),
            'cross_scope_popular' => $crossScopeUsage->toArray(),
            'low_coverage_fields' => $lowCoverageFields,
            'dependency_candidates' => $dependencyCandidates,
            'meta' => [
                'current_field_count' => count($currentSlugs),
                'library_total' => $this->getTotalFeatureCount(),
                'missing_candidate_count' => $missingCandidates->count(),
                'missing_signals' => $missingSignals,
                'analyzed_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Find features that exist in the library but are NOT assigned to current scope.
     *
     * @param array $currentFeatureIds Already-assigned feature IDs
     * @param array $currentSlugs Already-assigned slugs (for slug-based dedup)
     * @return Collection Missing candidate features
     */
    private function findMissingFromLibrary(array $currentFeatureIds, array $currentSlugs): Collection
    {
        $query = DB::table('features as f')
            ->leftJoin('feature_categories as fc', 'fc.id', '=', 'f.feature_category_id')
            ->whereNull('f.deleted_at')
            ->where('f.aktiflik_durumu', true);

        if (!empty($currentFeatureIds)) {
            $query->whereNotIn('f.id', $currentFeatureIds);
        }

        if (!empty($currentSlugs)) {
            $query->whereNotIn('f.slug', $currentSlugs);
        }

        return $query
            ->orderBy('fc.display_order')
            ->orderBy('f.display_order')
            ->get([
                'f.id as feature_id',
                'f.name',
                'f.slug',
                'f.type', // context7-ignore
                'f.unit',
                'f.is_required',
                'f.description',
                'fc.name as category_name',
                'fc.slug as category_slug',
            ])
            ->map(fn ($row) => [
                'feature_id' => (int) $row->feature_id,
                'name' => $row->name,
                'slug' => $row->slug,
                'type' => $row->type, // context7-ignore
                'unit' => $row->unit,
                'is_required' => (bool) $row->is_required,
                'description' => $row->description,
                'category_name' => $row->category_name,
                'category_slug' => $row->category_slug,
                'source' => 'feature_library',
            ]);
    }

    /**
     * Find features popular in other scopes but missing from current scope.
     *
     * "If Satılık Konut has 'oda-sayisi' and Kiralık Konut doesn't, suggest it."
     *
     * @param int $mainCategoryId Main category
     * @param int|null $subCategoryId Sub category
     * @param int $listingTypeId Current listing type
     * @param array $currentFeatureIds Already-assigned feature IDs
     * @return Collection Popular cross-scope features not in current scope
     */
    private function getCrossScopeUsage(
        int $mainCategoryId,
        ?int $subCategoryId,
        int $listingTypeId,
        array $currentFeatureIds
    ): Collection {
        // Find features assigned to the SAME category but DIFFERENT listing types
        $query = DB::table('feature_assignments as fa')
            ->join('features as f', 'f.id', '=', 'fa.feature_id')
            ->where('fa.main_category_id', $mainCategoryId)
            ->where('fa.listing_type_id', '!=', $listingTypeId)
            ->whereNull('fa.rolled_back_at')
            ->where('fa.aktiflik_durumu', true)
            ->where('fa.is_visible', true);

        if (!empty($currentFeatureIds)) {
            $query->whereNotIn('fa.feature_id', $currentFeatureIds);
        }

        return $query
            ->select([
                'f.id as feature_id',
                'f.name',
                'f.slug',
                'f.type', // context7-ignore
                DB::raw('COUNT(DISTINCT fa.listing_type_id) as scope_count'),
            ])
            ->groupBy('f.id', 'f.name', 'f.slug', 'f.type')
            ->orderByDesc('scope_count')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'feature_id' => (int) $row->feature_id,
                'name' => $row->name,
                'slug' => $row->slug,
                'type' => $row->type, // context7-ignore
                'scope_count' => (int) $row->scope_count,
                'source' => 'cross_scope_usage',
            ]);
    }

    /**
     * Find currently assigned fields with low fill rate.
     *
     * A field is "low coverage" if <20% of listings in this scope actually have a value.
     *
     * @param array $currentSchema Current schema
     * @param array &$missingSignals Accumulated missing signal list
     * @return array Low coverage field analysis
     */
    private function getLowCoverageFields(array $currentSchema, array &$missingSignals): array
    {
        // Guard: ilan_features table may not exist in all environments (e.g., SQLite test DB)
        if (!Schema::hasTable('ilan_features')) {
            $missingSignals[] = [
                'signal' => 'ilan_features_fill_rate',
                'reason' => 'ilan_features table does not exist in current database',
                'current_count' => 0,
            ];

            return [];
        }

        // Check if ilan_features table has enough data to analyze
        $totalIlanFeatures = DB::table('ilan_features')->count();

        if ($totalIlanFeatures < 10) {
            $missingSignals[] = [
                'signal' => 'ilan_features_fill_rate',
                'reason' => 'Insufficient ilan_features data for fill rate analysis (need >= 10 rows)',
                'current_count' => $totalIlanFeatures,
            ];

            return [];
        }

        $fields = $currentSchema['fields'] ?? [];
        if (empty($fields)) {
            return [];
        }

        $featureIds = collect($fields)->pluck('feature_id')->filter()->toArray();
        if (empty($featureIds)) {
            return [];
        }

        // Count how many ilanlar have values for each feature
        $fillCounts = DB::table('ilan_features')
            ->whereIn('feature_id', $featureIds)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->select('feature_id', DB::raw('COUNT(DISTINCT ilan_id) as fill_count'))
            ->groupBy('feature_id')
            ->pluck('fill_count', 'feature_id')
            ->toArray();

        // Total distinct ilanlar that have any ilan_features
        $totalIlanlar = DB::table('ilan_features')
            ->whereIn('feature_id', $featureIds)
            ->distinct('ilan_id')
            ->count('ilan_id');

        if ($totalIlanlar < 5) {
            $missingSignals[] = [
                'signal' => 'ilan_features_sample_size',
                'reason' => 'Too few listings for reliable fill rate (need >= 5)',
                'current_count' => $totalIlanlar,
            ];

            return [];
        }

        $lowCoverage = [];

        foreach ($fields as $field) {
            $featureId = $field['feature_id'] ?? null;
            if (!$featureId) {
                continue;
            }

            $fillCount = $fillCounts[$featureId] ?? 0;
            $fillRate = $totalIlanlar > 0 ? round($fillCount / $totalIlanlar, 4) : 0;

            if ($fillRate < 0.20) {
                $lowCoverage[] = [
                    'feature_id' => $featureId,
                    'slug' => $field['slug'],
                    'label' => $field['label'],
                    'fill_rate' => $fillRate,
                    'fill_count' => $fillCount,
                    'total_ilanlar' => $totalIlanlar,
                    'recommendation' => $fillRate < 0.05
                        ? 'consider_removing'
                        : 'review_ux',
                ];
            }
        }

        return $lowCoverage;
    }

    /**
     * Identify fields that could benefit from dependency (conditional) logic.
     *
     * Heuristic: If two fields share a logical group and one is a select/boolean
     * that could gate the other, suggest a dependency relationship.
     *
     * @param array $currentSchema Current schema
     * @return array Dependency suggestion candidates
     */
    private function identifyDependencyCandidates(array $currentSchema): array
    {
        $fields = $currentSchema['fields'] ?? [];
        if (count($fields) < 2) {
            return [];
        }

        $candidates = [];
        $gateFields = collect($fields)->filter(
            fn ($f) => in_array($f['type'], ['boolean', 'select']) // context7-ignore
                && empty($f['visible_if']) // not already a dependency target
                && empty($f['required_if'])
                && empty($f['enabled_if'])
        );

        $targetFields = collect($fields)->filter(
            fn ($f) => empty($f['visible_if']) // not already conditional
                && empty($f['required_if'])
                && empty($f['enabled_if'])
                && !in_array($f['slug'], $gateFields->pluck('slug')->toArray())
        );

        foreach ($gateFields as $gate) {
            // Find same-group fields that could be gated by this field
            $sameGroupTargets = $targetFields->filter(
                fn ($t) => $t['group'] === $gate['group']
                    && $t['slug'] !== $gate['slug']
            );

            if ($sameGroupTargets->isEmpty()) {
                continue;
            }

            foreach ($sameGroupTargets as $target) {
                $candidates[] = [
                    'gate_field' => $gate['slug'],
                    'gate_type' => $gate['type'], // context7-ignore
                    'target_field' => $target['slug'],
                    'suggested_rule' => $gate['type'] === 'boolean' // context7-ignore
                        ? ['field' => $gate['slug'], 'operator' => 'truthy']
                        : ['field' => $gate['slug'], 'operator' => '!=', 'value' => ''],
                    'suggestion_type' => 'visible_if',
                    'confidence' => 'low', // heuristic only
                ];
            }
        }

        // Limit to top 10 most relevant suggestions
        return array_slice($candidates, 0, 10);
    }

    /**
     * Get total active feature count in library.
     */
    private function getTotalFeatureCount(): int
    {
        return DB::table('features')
            ->whereNull('deleted_at')
            ->where('aktiflik_durumu', true)
            ->count();
    }
}
