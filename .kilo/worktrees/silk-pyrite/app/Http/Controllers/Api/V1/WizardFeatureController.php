<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Wizard\AiFieldSuggestionEngine;
use App\Services\Wizard\DynamicFieldValueMapper;
use App\Services\Wizard\EffectiveListingTypeResolver;
use App\Services\Wizard\FeatureTemplateResolver; // Wizard-scoped resolver; system SSOT = Ups\FeatureTemplateResolver
use App\Services\Wizard\FieldEngine\FieldResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WizardFeatureController — Production-grade features API endpoint.
 *
 * Returns scoped feature schema for Wizard Step 2 based on:
 *   ana_kategori_id + alt_kategori_id + yayin_tipi_id
 *
 * Endpoints:
 *   GET  /api/v1/wizard/features              — CREATE mode (empty form)
 *   GET  /api/v1/wizard/features-with-values   — EDIT mode (hydrated form)
 *   POST /api/v1/wizard/field-suggestions       — AI field suggestion generation
 *   POST /api/v1/wizard/field-suggestions/approve — Approve a suggestion
 *   POST /api/v1/wizard/field-suggestions/rollback — Rollback an approved suggestion
 */
class WizardFeatureController extends Controller
{
    public function __construct(
        protected EffectiveListingTypeResolver $listingTypeResolver,
        protected FeatureTemplateResolver $featureTemplateResolver,
        protected DynamicFieldValueMapper $valueMapper,
        protected AiFieldSuggestionEngine $suggestionEngine,
        protected FieldResolver $fieldResolver,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ana_kategori_id' => ['required', 'integer', 'min:1'],
            'alt_kategori_id' => ['nullable', 'integer', 'min:1'],
            'yayin_tipi_id' => ['required', 'integer', 'min:1'],
        ]);

        $mainCategoryId = (int) $validated['ana_kategori_id'];
        $subCategoryId = isset($validated['alt_kategori_id']) ? (int) $validated['alt_kategori_id'] : null;
        $listingTypeId = (int) $validated['yayin_tipi_id'];

        abort_unless(
            $this->listingTypeResolver->isAllowed($mainCategoryId, $subCategoryId, $listingTypeId),
            422,
            'Seçilen yayın tipi bu kategori için geçerli değil.'
        );

        $fields = $this->resolveFieldsWithFallback($mainCategoryId, $subCategoryId, $listingTypeId);

        $grouped = $fields->groupBy('group')->map(fn ($items, $group) => [
            'group' => $group,
            'fields' => $items->values()->all(),
        ])->values()->all();

        return response()->json([
            'data' => [
                'fields' => $fields->values()->all(),
                'groups' => $grouped,
                'meta' => [
                    'field_count' => $fields->count(),
                    'required_count' => $fields->where('required', true)->count(),
                    'main_category_id' => $mainCategoryId,
                    'sub_category_id' => $subCategoryId,
                    'listing_type_id' => $listingTypeId,
                ],
            ],
        ]);
    }

    /**
     * Features + hydrated values for EDIT mode.
     *
     * GET /api/v1/wizard/features-with-values
     *
     * Returns scoped fields/groups + existing ilan_feature values,
     * type-cast for direct x-model binding in Alpine.js.
     */
    public function featuresWithValues(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ana_kategori_id' => ['required', 'integer', 'min:1'],
            'alt_kategori_id' => ['nullable', 'integer', 'min:1'],
            'yayin_tipi_id' => ['required', 'integer', 'min:1'],
            'ilan_id' => ['required', 'integer', 'min:1'],
        ]);

        $mainCategoryId = (int) $validated['ana_kategori_id'];
        $subCategoryId = isset($validated['alt_kategori_id']) ? (int) $validated['alt_kategori_id'] : null;
        $listingTypeId = (int) $validated['yayin_tipi_id'];
        $ilanId = (int) $validated['ilan_id'];

        abort_unless(
            $this->listingTypeResolver->isAllowed($mainCategoryId, $subCategoryId, $listingTypeId),
            422,
            'Seçilen yayın tipi bu kategori için geçerli değil.'
        );

        $fields = $this->resolveFieldsWithFallback($mainCategoryId, $subCategoryId, $listingTypeId);

        $grouped = $fields->groupBy('group')->map(fn ($items, $group) => [
            'group' => $group,
            'fields' => $items->values()->all(),
        ])->values()->all();

        $values = $this->valueMapper->loadCastValues($ilanId);

        return response()->json([
            'data' => [
                'fields' => $fields->values()->all(),
                'groups' => $grouped,
                'values' => $values,
                'meta' => [
                    'field_count' => $fields->count(),
                    'required_count' => $fields->where('required', true)->count(),
                    'main_category_id' => $mainCategoryId,
                    'sub_category_id' => $subCategoryId,
                    'listing_type_id' => $listingTypeId,
                    'ilan_id' => $ilanId,
                ],
            ],
        ]);
    }

    /**
     * Resolve fields with fallback from feature_assignments → kategori_yayin_tipi_field_dependencies.
     */
    private function resolveFieldsWithFallback(int $mainCategoryId, ?int $subCategoryId, int $listingTypeId): \Illuminate\Support\Collection
    {
        $fields = $this->featureTemplateResolver->resolveFeatures(
            $mainCategoryId,
            $subCategoryId,
            $listingTypeId
        );

        // Fallback to FieldEngine when feature_assignments table is empty
        if ($fields->isEmpty()) {
            $kategoriId = $subCategoryId ?? $mainCategoryId;
            $fieldDefs = $this->fieldResolver->resolveWithoutCache($kategoriId, $listingTypeId);

            if (!empty($fieldDefs)) {
                $fields = collect($fieldDefs)->map(function ($fd) {
                    $arr = $fd->toArray();
                    return [
                        'feature_id' => $arr['id'] ?? $arr['slug'],
                        'slug' => $arr['slug'],
                        'label' => $arr['name'],
                        'type' => $arr['type'],
                        'group' => $arr['category'] ?? 'Genel',
                        'required' => $arr['required'],
                        'options' => $arr['options'],
                        'unit' => $arr['unit'],
                        'icon' => $arr['icon'],
                        'description' => $arr['help_text'],
                        'visible_if' => $arr['visible_if'],
                        'required_if' => $arr['required_if'],
                        'display_order' => $arr['display_order'],
                    ];
                });
            }
        }

        return $fields;
    }

    /**
     * Generate AI field suggestions for a category + listing type.
     *
     * POST /api/v1/wizard/field-suggestions
     */
    public function fieldSuggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ana_kategori_id' => ['required', 'integer', 'min:1'],
            'alt_kategori_id' => ['nullable', 'integer', 'min:1'],
            'yayin_tipi_id' => ['required', 'integer', 'min:1'],
            'max_suggestions' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $result = $this->suggestionEngine->suggest(
            (int) $validated['ana_kategori_id'],
            isset($validated['alt_kategori_id']) ? (int) $validated['alt_kategori_id'] : null,
            (int) $validated['yayin_tipi_id'],
            [
                'max_suggestions' => $validated['max_suggestions'] ?? 15,
                'min_score' => $validated['min_score'] ?? 20,
            ]
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Approve an AI field suggestion — creates the assignment.
     *
     * POST /api/v1/wizard/field-suggestions/approve
     */
    public function approveSuggestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feature_id' => ['required', 'integer', 'min:1'],
            'ana_kategori_id' => ['required', 'integer', 'min:1'],
            'alt_kategori_id' => ['nullable', 'integer', 'min:1'],
            'yayin_tipi_id' => ['required', 'integer', 'min:1'],
            'label_override' => ['nullable', 'string', 'max:255'],
            'field_type' => ['nullable', 'string', 'max:50'],
            'group_name' => ['nullable', 'string', 'max:100'],
            'is_required' => ['nullable', 'boolean'],
            'options' => ['nullable', 'array'],
        ]);

        $result = $this->suggestionEngine->approveSuggestion(
            (int) $validated['feature_id'],
            (int) $validated['ana_kategori_id'],
            isset($validated['alt_kategori_id']) ? (int) $validated['alt_kategori_id'] : null,
            (int) $validated['yayin_tipi_id'],
            array_filter([
                'label_override' => $validated['label_override'] ?? null,
                'field_type' => $validated['field_type'] ?? null,
                'group_name' => $validated['group_name'] ?? null,
                'is_required' => $validated['is_required'] ?? false,
                'options' => $validated['options'] ?? null,
            ], fn ($v) => $v !== null)
        );

        $httpStatus = ($result['basarili'] ?? false) ? 201 : 422;

        return response()->json(['data' => $result], $httpStatus);
    }

    /**
     * Rollback an AI-approved field suggestion.
     *
     * POST /api/v1/wizard/field-suggestions/rollback
     */
    public function rollbackSuggestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_id' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->suggestionEngine->rollbackSuggestion(
            (int) $validated['assignment_id']
        );

        $httpStatus = ($result['basarili'] ?? false) ? 200 : 422;

        return response()->json(['data' => $result], $httpStatus);
    }
}
