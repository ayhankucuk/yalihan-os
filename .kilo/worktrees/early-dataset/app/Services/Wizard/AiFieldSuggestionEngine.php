<?php

namespace App\Services\Wizard;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AiFieldSuggestionEngine — Orchestrates gap analysis + scoring for field suggestions.
 *
 * Pipeline:
 *   1. Resolve current schema (EffectiveWizardSchemaResolver)
 *   2. Run FieldGapAnalyzer (find gaps, cross-scope popular, low coverage, dependency candidates)
 *   3. Run FieldSuggestionScorer on each candidate
 *   4. Deduplicate, filter, sort
 *   5. Produce governance-aware response
 *
 * Governance rules:
 *   - No auto-apply: all suggestions require admin preview + explicit approve
 *   - Low confidence (score < 30): flagged as `auto_hidden: true`
 *   - Duplicate slug detection: rejected candidates listed in `rejected[]`
 *   - Existing assigned fields: never re-suggested
 *   - Rollback support: every approved suggestion must be reversible
 *
 * Response contract:
 * {
 *   suggestions: [{slug, name, type, score, priority, dimensions, source, ...}],
 *   remove_candidates: [{slug, label, fill_rate, recommendation}],
 *   dependency_suggestions: [{gate_field, target_field, suggested_rule, ...}],
 *   rejected: [{slug, reason}],
 *   summary: {total_candidates, after_filter, high_priority_count, ...},
 *   governance: {auto_apply: false, requires_approval: true, ...},
 *   meta: {analyzed_at, missing_signals, ...}
 * }
 */
class AiFieldSuggestionEngine
{
    public function __construct(
        private readonly EffectiveWizardSchemaResolver $schemaResolver,
        private readonly FieldGapAnalyzer $gapAnalyzer,
        private readonly FieldSuggestionScorer $scorer,
    ) {}

    /**
     * Generate field suggestions for a given category + listing type.
     *
     * @param int $mainCategoryId Ana kategori ID
     * @param int|null $subCategoryId Alt kategori ID
     * @param int $listingTypeId Yayın tipi ID
     * @param array $options Additional options (max_suggestions, min_score, etc.)
     * @return array Suggestion response contract
     */
    public function suggest(
        int $mainCategoryId,
        ?int $subCategoryId,
        int $listingTypeId,
        array $options = []
    ): array {
        $maxSuggestions = $options['max_suggestions'] ?? 15;
        $minScore = $options['min_score'] ?? 20;

        // Step 1: Resolve current schema
        $currentSchema = $this->schemaResolver->resolve($mainCategoryId, $listingTypeId);

        // Step 2: Run gap analysis
        $analysis = $this->gapAnalyzer->analyze(
            $mainCategoryId,
            $subCategoryId,
            $listingTypeId,
            $currentSchema
        );

        // Step 3: Merge candidate pools
        $allCandidates = array_merge(
            $analysis['missing_candidates'] ?? [],
            $analysis['cross_scope_popular'] ?? []
        );

        // Step 4: Deduplicate by slug
        $deduped = $this->deduplicateCandidates($allCandidates);

        // Step 5: Reject candidates that conflict with current schema
        [$validCandidates, $rejected] = $this->filterConflicts(
            $deduped,
            $currentSchema
        );

        // Step 6: Score all valid candidates
        $scoringContext = [
            'current_field_count' => $analysis['meta']['current_field_count'] ?? 0,
            'main_category_id' => $mainCategoryId,
            'sub_category_id' => $subCategoryId,
            'listing_type_id' => $listingTypeId,
        ];

        $scored = $this->scorer->scoreAll($validCandidates, $scoringContext);

        // Step 7: Filter by minimum score
        $suggestions = array_filter($scored, fn ($s) => $s['total_score'] >= $minScore);

        // Step 8: Mark low-confidence as auto_hidden
        $suggestions = array_map(function ($s) {
            $s['auto_hidden'] = $s['total_score'] < 30;
            return $s;
        }, $suggestions);

        // Step 9: Limit results
        $suggestions = array_slice(array_values($suggestions), 0, $maxSuggestions);

        // Step 10: Build response
        $highPriorityCount = count(array_filter($suggestions, fn ($s) => in_array($s['priority'], ['critical', 'high'])));

        return [
            'suggestions' => array_values($suggestions),
            'remove_candidates' => $analysis['low_coverage_fields'] ?? [],
            'dependency_suggestions' => $analysis['dependency_candidates'] ?? [],
            'rejected' => $rejected,
            'summary' => [
                'total_candidates_analyzed' => count($allCandidates),
                'after_dedup' => count($deduped),
                'after_conflict_filter' => count($validCandidates),
                'after_score_filter' => count($suggestions),
                'high_priority_count' => $highPriorityCount,
                'min_score_threshold' => $minScore,
                'max_suggestions' => $maxSuggestions,
            ],
            'governance' => [
                'auto_apply' => false,
                'requires_approval' => true,
                'approval_type' => 'admin_preview',
                'rollback_supported' => true,
            ],
            'meta' => $analysis['meta'] ?? [],
        ];
    }

    /**
     * Apply an approved suggestion — create a new FeatureAssignment.
     *
     * @param int $featureId Feature ID to assign
     * @param int $mainCategoryId Main category
     * @param int|null $subCategoryId Sub category
     * @param int $listingTypeId Listing type
     * @param array $overrides Optional overrides (label_override, field_type, etc.)
     * @return array Result with assignment_id or error
     */
    public function approveSuggestion(
        int $featureId,
        int $mainCategoryId,
        ?int $subCategoryId,
        int $listingTypeId,
        array $overrides = []
    ): array {
        // Verify feature exists
        $feature = DB::table('features')
            ->where('id', $featureId)
            ->whereNull('deleted_at')
            ->first();

        if (!$feature) {
            return [
                'basarili' => false,
                'hata_mesaji' => "Feature #{$featureId} not found or deleted.",
            ];
        }

        // Check for duplicate assignment (by unique constraint columns + scope check)
        $scopeType = $subCategoryId ? 'sub_category' : 'listing_type';

        $existing = DB::table('feature_assignments')
            ->where('feature_id', $featureId)
            ->where('assignable_type', 'App\Models\YayinTipiSablonu')
            ->where('assignable_id', $listingTypeId)
            ->where(function ($q) use ($scopeType) {
                $q->where('scope_type', $scopeType)
                    ->orWhere('scope_type', 'ai_design');
            })
            ->whereNull('rolled_back_at')
            ->first();

        if ($existing) {
            return [
                'basarili' => false,
                'hata_mesaji' => "Feature '{$feature->slug}' is already assigned to this scope.",
                'existing_assignment_id' => $existing->id,
            ];
        }

        // Determine display_order (append at end)
        $maxOrder = DB::table('feature_assignments')
            ->where('listing_type_id', $listingTypeId)
            ->where('main_category_id', $mainCategoryId)
            ->whereNull('rolled_back_at')
            ->max('display_order');

        $assignment = \App\Models\FeatureAssignment::create([
            'feature_id' => $featureId,
            'assignable_type' => 'App\Models\YayinTipiSablonu',
            'assignable_id' => $listingTypeId,
            'main_category_id' => $mainCategoryId,
            'sub_category_id' => $subCategoryId,
            'listing_type_id' => $listingTypeId,
            'scope_type' => 'ai_design',
            'source_type' => 'ai_design',
            'is_required' => $overrides['is_required'] ?? false,
            'is_visible' => true,
            'display_order' => ($maxOrder ?? 0) + 10,
            'aktiflik_durumu' => true,
            'label_override' => $overrides['label_override'] ?? null,
            'field_type' => $overrides['field_type'] ?? null,
            'group_name' => $overrides['group_name'] ?? null,
            'options_json' => isset($overrides['options']) ? json_encode($overrides['options']) : null,
        ]);

        Log::info('ai_field_suggestion_approved', [
            'assignment_id' => $assignment->id,
            'feature_id' => $featureId,
            'feature_slug' => $feature->slug,
            'main_category_id' => $mainCategoryId,
            'sub_category_id' => $subCategoryId,
            'listing_type_id' => $listingTypeId,
        ]);

        return [
            'basarili' => true,
            'assignment_id' => $assignment->id,
            'feature_slug' => $feature->slug,
        ];
    }

    /**
     * Rollback an AI-approved suggestion.
     *
     * Soft-deletes the assignment by setting rolled_back_at.
     *
     * @param int $assignmentId Assignment ID to rollback
     * @return array Result
     */
    public function rollbackSuggestion(int $assignmentId): array
    {
        $assignment = DB::table('feature_assignments')
            ->where('id', $assignmentId)
            ->where('source_type', 'ai_design')
            ->whereNull('rolled_back_at')
            ->first();

        if (!$assignment) {
            return [
                'basarili' => false,
                'hata_mesaji' => "Assignment #{$assignmentId} not found or not an AI suggestion.",
            ];
        }

        DB::table('feature_assignments')
            ->where('id', $assignmentId)
            ->update(['rolled_back_at' => now()]);

        Log::info('ai_field_suggestion_rolled_back', [
            'assignment_id' => $assignmentId,
            'feature_id' => $assignment->feature_id,
        ]);

        return [
            'basarili' => true,
            'assignment_id' => $assignmentId,
            'rolled_back_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Deduplicate candidates by slug (keep first occurrence).
     */
    private function deduplicateCandidates(array $candidates): array
    {
        $seen = [];
        $unique = [];

        foreach ($candidates as $candidate) {
            $slug = $candidate['slug'] ?? '';
            if (empty($slug) || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $unique[] = $candidate;
        }

        return $unique;
    }

    /**
     * Filter out candidates that conflict with current schema.
     *
     * @return array [validCandidates[], rejected[]]
     */
    private function filterConflicts(array $candidates, array $currentSchema): array
    {
        $currentSlugs = collect($currentSchema['fields'] ?? [])
            ->pluck('slug')
            ->flip()
            ->toArray();

        $currentFeatureIds = collect($currentSchema['fields'] ?? [])
            ->pluck('feature_id')
            ->flip()
            ->toArray();

        $valid = [];
        $rejected = [];

        foreach ($candidates as $candidate) {
            $slug = $candidate['slug'] ?? '';
            $featureId = $candidate['feature_id'] ?? null;

            // Reject: slug already in current schema
            if (isset($currentSlugs[$slug])) {
                $rejected[] = [
                    'slug' => $slug,
                    'feature_id' => $featureId,
                    'reason' => 'slug_already_assigned',
                ];
                continue;
            }

            // Reject: feature_id already in current schema
            if ($featureId && isset($currentFeatureIds[$featureId])) {
                $rejected[] = [
                    'slug' => $slug,
                    'feature_id' => $featureId,
                    'reason' => 'feature_id_already_assigned',
                ];
                continue;
            }

            $valid[] = $candidate;
        }

        return [$valid, $rejected];
    }
}
