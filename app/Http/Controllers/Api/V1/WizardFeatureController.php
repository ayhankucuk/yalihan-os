<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Wizard\AiFieldSuggestionEngine;
use App\Services\Wizard\DynamicFieldValueMapper;
use App\Services\Wizard\EffectiveListingTypeResolver;
use App\Services\Wizard\FeatureTemplateResolver; // Wizard-scoped resolver; system SSOT = Ups\FeatureTemplateResolver
use App\Services\Wizard\FieldEngine\FieldResolver;
use App\Services\Wizard\YayinTipiSablonuResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WizardFeatureController — Production-grade features API endpoint.
 *
 * SAAB v8.0 Sprint 6.9: ID sözleşmesi düzeltildi.
 *
 * Artık iki tür ID kullanılıyor:
 *  - yayin_tipleri.id (1, 2, 3) — yayın tipi (Satılık, Kiralık, etc.)
 *  - yayin_tipi_sablonu.id (13, 14, 15) — junction/template ID
 *
 * YayinTipiSablonuResolver: yayin_tipi_id → sablon_id çözümler.
 * Negative sablon_id: yayin_tipi_id direkt kullanılabilir (veri gap durumu).
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
        protected YayinTipiSablonuResolver $sablonResolver,
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
        
        $rawPublicationTypeId = (int) $validated['yayin_tipi_id'];
        $publicationTypeId = $this->sablonResolver->resolvePublicationTypeId($rawPublicationTypeId);

        // SAAB v8.0 Sprint 6.9: YayinTipiSablonuResolver ile sablon_id çöz
        // Positive = junction/template ID → policy.isAllowed kontrolü yap
        // Negative = yayin_tipi_id direkt kullanılabilir → policy kontrolü atla
        try {
            $sablonId = $this->sablonResolver->resolveTemplateId(
                mainCategoryId: $mainCategoryId,
                subCategoryId: $subCategoryId,
                publicationTypeId: $publicationTypeId,
            );
        } catch (\InvalidArgumentException $e) {
            \Illuminate\Support\Facades\Log::warning('WizardFeatureController template resolution failed', [
                'error' => $e->getMessage(),
                'main_category_id' => $mainCategoryId,
                'sub_category_id' => $subCategoryId,
                'publication_type_id' => $publicationTypeId,
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TEMPLATE_NOT_FOUND',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }

        // SAAB v8.0 Sprint 6.10: YayinTipiSablonuResolver kombinasyonu zaten doğruladı.
        // Ek policy.isAllowed() çağrısına GEREK YOK.
        $fields = $this->resolveFieldsWithFallback($mainCategoryId, $subCategoryId, $publicationTypeId);

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
                    'listing_type_id' => $publicationTypeId,
                    'sablon_id' => $sablonId,
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
        
        $rawPublicationTypeId = (int) $validated['yayin_tipi_id'];
        $publicationTypeId = $this->sablonResolver->resolvePublicationTypeId($rawPublicationTypeId);

        $ilanId = (int) $validated['ilan_id'];

        // SAAB v8.0 Sprint 6.9: YayinTipiSablonuResolver kullan
        try {
            $sablonId = $this->sablonResolver->resolveTemplateId(
                mainCategoryId: $mainCategoryId,
                subCategoryId: $subCategoryId,
                publicationTypeId: $publicationTypeId,
            );
        } catch (\InvalidArgumentException $e) {
            \Illuminate\Support\Facades\Log::warning('WizardFeatureController featuresWithValues resolution failed', [
                'error' => $e->getMessage(),
                'main_category_id' => $mainCategoryId,
                'sub_category_id' => $subCategoryId,
                'publication_type_id' => $publicationTypeId,
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TEMPLATE_NOT_FOUND',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }

        if ($sablonId > 0) {
            abort_unless(
                $this->listingTypeResolver->isAllowed($mainCategoryId, $subCategoryId, $sablonId),
                422,
                'Seçilen yayın tipi bu kategori için geçerli değil.'
            );
        }

        $fields = $this->resolveFieldsWithFallback($mainCategoryId, $subCategoryId, $publicationTypeId, $sablonId);

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
                    'listing_type_id' => $publicationTypeId,
                    'ilan_id' => $ilanId,
                    'sablon_id' => $sablonId,
                ],
            ],
        ]);
    }

    /**
     * Resolve fields for Wizard Step 2.
     *
     * SAAB v8.0 Sprint 6.10: YayinTipiSablonuResolver başarılıysa kombinasyon geçerlidir.
     * YayinTipiSablonuCanonicalSeeder tüm eksik kayıtları oluşturdu.
     */
    private function resolveFields(int $mainCategoryId, ?int $subCategoryId, int $publicationTypeId): \Illuminate\Support\Collection
    {
        $kategoriSlug = $this->resolveKategoriSlug($mainCategoryId, $subCategoryId);
        $yayinTipiSlug = $this->resolveYayinTipiSlug($publicationTypeId);

        // FieldResolver slug tabanlı çözümleme
        $fieldDefs = $this->fieldResolver->resolveBySlug($kategoriSlug, $yayinTipiSlug, $publicationTypeId);

        if (!empty($fieldDefs)) {
            return collect($fieldDefs)->map(fn ($fd) => $fd->toArray());
        }

        return collect();
    }

    /**
     * Resolve fields with fallback.
     */
    private function resolveFieldsWithFallback(
        int $mainCategoryId,
        ?int $subCategoryId,
        int $publicationTypeId,
        ?int $sablonId = null
    ): \Illuminate\Support\Collection {
        return $this->resolveFields($mainCategoryId, $subCategoryId, $publicationTypeId);
    }

    /**
     * Kategori slug'ini çözümle.
     */
    private function resolveKategoriSlug(int $mainCategoryId, ?int $subCategoryId): string
    {
        $targetId = $subCategoryId ?? $mainCategoryId;

        // Zincir: önce alt kategori, sonra ana kategori, sonra parent
        $kategori = \App\Models\IlanKategori::find($targetId);
        if ($kategori) {
            return $kategori->slug;
        }

        $kategori = \App\Models\IlanKategori::find($mainCategoryId);
        return $kategori?->slug ?? 'genel';
    }

    /**
     * Yayın tipi slug'ini çözümle.
     */
    private function resolveYayinTipiSlug(int $publicationTypeId): string
    {
        return $this->sablonResolver->resolveYayinTipiSlug($publicationTypeId);
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

        $rawPublicationTypeId = (int) $validated['yayin_tipi_id'];
        $publicationTypeId = $this->sablonResolver->resolvePublicationTypeId($rawPublicationTypeId);

        $result = $this->suggestionEngine->suggest(
            (int) $validated['ana_kategori_id'],
            isset($validated['alt_kategori_id']) ? (int) $validated['alt_kategori_id'] : null,
            $publicationTypeId,
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
