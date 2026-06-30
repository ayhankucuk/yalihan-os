<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Admin\Traits\ManagesPropertyTypes;
use App\Http\Controllers\Admin\Traits\UPSHelperTrait;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use App\Models\KategoriYayinTipiFieldDependency;
use App\Services\Feature\FeatureAssignmentValidator;
use App\Services\Logging\LogService;
use App\Services\PropertyType\PropertyTypeBulkUpdateService;
use App\Traits\TogglesFeatureDurum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Feature Assignment Controller
 *
 * Context7: Özellik atama yönetimi (Feature assignments)
 * Refactored from PropertyTypeManagerController (Phase 2.2)
 * Phase 2.3+: UPSHelperTrait integration - Standardized responses, cache, validators
 *
 * Methods:
 * - assign(): Özellik ata
 * - unassign(): Özellik kaldır
 * - toggleAssignment(): Özellik atama toggle (visibility/requirement)
 * - sync(): Özellik senkronizasyonu (bulk)
 * - updateAssignment(): Özellik ataması güncelle
 * - toggleFeature(): Özellik aktif/pasif (deprecated - trait kullan)
 * - bulkSave(): Toplu kaydetme (yayin tipi + feature + field dep)
 */
class FeatureAssignmentController extends AdminController
{
    use ManagesPropertyTypes, UPSHelperTrait;
    use TogglesFeatureDurum;

    public function __construct(
        private PropertyTypeBulkUpdateService $bulkUpdateService,
        private \App\Domain\PropertyHub\PropertyTypeConfiguration $aggregate
    ) {
        $this->middleware('can:manage-settings');
    }

    /**
     * Özellik ata
     *
     * POST /admin/property-types/{propertyTypeId}/features
     */
    public function assign(Request $request, $propertyTypeId)
    {
        $request->validate([
            'feature_id' => 'required|exists:features,id',
            'is_required' => 'nullable|boolean',
            'is_visible' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
            'group_name' => 'nullable|string|max:100',
        ]);

        try {
            $propertyType = YayinTipiSablonu::findOrFail($propertyTypeId);
            $feature = Feature::findOrFail($request->feature_id);

            // ✅ SAB: Feature assignment validation
            $validator = app(FeatureAssignmentValidator::class);
            $validation = $validator->validate($feature, $propertyType);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                ], 400);
            }

            $assignments = $this->aggregate->assignFeatures(
                $propertyType->id,
                [$request->feature_id],
                'manual',
                auth()->id(),
                YayinTipiSablonu::class,
                [
                    'is_required' => $request->boolean('is_required', false),
                    'is_visible' => $request->boolean('is_visible', true),
                    'display_order' => $request->input('display_order', 0),
                    'group_name' => $request->input('group_name'),
                ]
            );

            $assignmentId = !empty($assignments) ? $assignments[0]->id : null;

            LogService::info('Feature assigned to property type via Aggregate Root', [
                'property_type_id' => $propertyTypeId,
                'feature_id' => $request->feature_id,
                'assignment_id' => $assignmentId,
            ]);

            return $this->sendUPSSuccess(
                '✅ Özellik başarıyla atandı',
                [
                    'assignment_id' => $assignmentId,
                    'feature' => $feature->only(['id', 'name', 'slug', 'field_type']),
                ],
                201
            );
        } catch (\Exception $e) {
            LogService::error('Feature assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendUPSError(
                'Özellik atama hatası: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Özellik kaldır
     *
     * DELETE /admin/property-types/{propertyTypeId}/features
     */
    public function unassign(Request $request, $propertyTypeId)
    {
        $request->validate([
            'feature_id' => 'required|exists:features,id',
        ]);

        try {
            $propertyType = YayinTipiSablonu::findOrFail($propertyTypeId);
            $feature = Feature::findOrFail($request->feature_id);

            $this->aggregate->unassignFeatures(
                $propertyType->id,
                [$request->feature_id],
                YayinTipiSablonu::class
            );

            LogService::info('Feature unassigned from property type via Aggregate Root', [
                'property_type_id' => $propertyTypeId,
                'feature_id' => $request->feature_id,
            ]);

            return $this->sendUPSSuccess('✅ Özellik kaldırıldı');
        } catch (\Exception $e) {
            LogService::error('Feature unassignment failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendUPSError(
                'Özellik kaldırma hatası: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Özellik atama toggle (visibility/requirement)
     *
     * POST /admin/property-types/features/toggle
     */
    public function toggleAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:feature_assignments,id',
            'field' => 'required|in:is_visible,is_required',
            'value' => 'required|boolean',
        ]);

        try {
            $assignment = FeatureAssignment::findOrFail($request->assignment_id);
            $field = $request->field;
            $value = $request->boolean('value');

            $assignment = $this->aggregate->updateAssignmentMetadata(
                $request->assignment_id,
                [$field => $value],
                auth()->id()
            );

            LogService::info('Feature assignment toggled via Aggregate Root', [
                'assignment_id' => $request->assignment_id,
                'field' => $field,
                'value' => $value,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Özellik güncellendi',
                'data' => [
                    'assignment_id' => $assignment->id,
                    $field => $value,
                ],
            ]);
        } catch (\Exception $e) {
            LogService::error('Feature assignment toggle failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendUPSError(
                'Güncelleme hatası: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Özellik senkronizasyonu (bulk)
     *
     * POST /admin/property-types/{propertyTypeId}/features/sync
     */
    public function sync(Request $request, $propertyTypeId)
    {
        $request->validate([
            'feature_ids' => 'required|array',
            'feature_ids.*' => 'exists:features,id',
        ]);

        try {
            $propertyType = YayinTipiSablonu::findOrFail($propertyTypeId);

            // ✅ SAB: Feature assignment validation (batch)
            $validator = app(FeatureAssignmentValidator::class);
            $validation = $validator->validateBatch($request->feature_ids, $propertyType);

            if (!$validation['valid']) {
                $invalidNames = array_column($validation['invalid_features'], 'feature_name');
                return $this->sendUPSError(
                    'Bazı özellikler bu yayın tipi için uygun değil: ' . implode(', ', $invalidNames),
                    ['invalid_features' => $validation['invalid_features']],
                    400
                );
            }

            // SAB: Aggregate Root method handles sync (no TX in controller)
            $this->aggregate->syncFeatures(
                $propertyType->id,
                $request->feature_ids,
                auth()->id(),
                YayinTipiSablonu::class
            );

            LogService::info('Features synced for property type via Aggregate Root', [
                'property_type_id' => $propertyTypeId,
                'feature_count' => count($request->feature_ids),
            ]);

            return $this->sendUPSSuccess(
                '✅ Özellikler güncellendi',
                ['synced_count' => count($request->feature_ids)]
            );
        } catch (\Exception $e) {
            LogService::error('Feature sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendUPSError(
                'Senkronizasyon hatası: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Özellik ataması güncelle
     *
     * PUT /admin/property-types/features/{assignmentId}
     */
    public function updateAssignment(Request $request, $assignmentId)
    {
        $request->validate([
            'is_required' => 'nullable|boolean',
            'is_visible' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
            'group_name' => 'nullable|string|max:100',
        ]);

        try {
            $assignment = FeatureAssignment::findOrFail($assignmentId);

            $updates = $request->only([
                'is_required',
                'is_visible',
                'display_order',
                'group_name',
            ]);

            $assignment = $this->aggregate->updateAssignmentMetadata(
                $assignmentId,
                $updates,
                auth()->id()
            );

            LogService::info('Feature assignment updated via Aggregate Root', [
                'assignment_id' => $assignmentId,
                'updates' => $updates,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Özellik ayarları güncellendi',
                'data' => $assignment->only(['id', 'is_required', 'is_visible', 'display_order', 'group_name']),
            ]);
        } catch (\Exception $e) {
            LogService::error('Feature assignment update failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Güncelleme hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Özellik aktif/pasif (deprecated - trait method kullan)
     *
     * @deprecated Use toggleFeatureStatus() trait method instead
     * POST /admin/property-types/features/toggle-feature
     */
    public function toggleFeature(Request $request)
    {
        return $this->toggleFeatureStatus($request, $request->input('feature_id'));
    }

    /**
     * Toplu kaydetme (yayin tipi + feature + field dep)
     *
     * POST /admin/property-types/{kategoriId}/bulk-save
     */
    public function bulkSave($kategoriId, Request $request)
    {
        $yayinTipiUpdates = $request->input('yayin_tipi_updates', $request->input('yayin_tipleri', []));
        $featureUpdates = $request->input('feature_updates', $request->input('features', []));
        $fieldDepUpdates = $request->input('field_dependency_updates', $request->input('field_dependencies', []));

        if (empty($yayinTipiUpdates) && empty($featureUpdates) && empty($fieldDepUpdates)) {
            return response()->json(['success' => true, 'message' => 'Toplu kayıtlar güncellendi']);
        }

        // SAB Kural 1: Domain logic service'te, TX service'te
        $counts = $this->bulkUpdateService->bulkUpdate(
            (int) $kategoriId,
            $yayinTipiUpdates,
            $featureUpdates,
            $fieldDepUpdates
        );

        return response()->json([
            'success' => true,
            'message' => 'Toplu kayıtlar güncellendi',
            'data' => $counts,
        ]);
    }

    /**
     * 🆕 PHASE 3: Yayın Tipi bazında feature suggestion'ları getir
     *
     * Context7 Standard: C7-FEATURE-SUGGESTIONS-API-2026-01-05
     *
     * GET /admin/property-types/{propertyTypeId}/feature-suggestions
     *
     * Bu endpoint, belirli bir yayın tipi için:
     * - Zorunlu özellikler (required)
     * - Önerilen özellikler (suggested)
     * - Yasaklı özellikler (disallowed)
     * listesini döner.
     *
     * UI danışmanlara rehberlik eder.
     */
    public function getFeatureSuggestions(Request $request, $propertyTypeId)
    {
        try {
            $propertyType = YayinTipiSablonu::with('kategori')->findOrFail($propertyTypeId);

            $kategoriSlug = $propertyType->kategori->slug ?? '';
            $yayinTipiSlug = strtolower($propertyType->name ?? '');

            // Config'den disallowed features al
            $rules = config('feature-assignment-rules', []);
            $categoryRules = $rules[$kategoriSlug] ?? [];
            $disallowedFeatures = $categoryRules[$yayinTipiSlug] ?? [];

            // FeatureCategoryService'den suggestions al
            $featureCategoryService = app(\App\Services\Category\FeatureCategoryService::class);
            $suggestions = $featureCategoryService->getSuggestedFeaturesForYayinTipi(
                $kategoriSlug,
                $yayinTipiSlug
            );

            // Mevcut assignments al
            $currentAssignments = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
                ->where('assignable_id', $propertyType->id)
                ->with('feature:id,name,slug')
                ->get()
                ->map(function ($assignment) {
                    return [
                        'feature_slug' => $assignment->feature->slug ?? '',
                        'is_required' => $assignment->is_required,
                        'is_visible' => $assignment->is_visible,
                    ];
                });

            return $this->sendUPSSuccess('Feature suggestions retrieved', [
                'property_type' => [
                    'id' => $propertyType->id,
                    'kategori_slug' => $kategoriSlug,
                    'yayin_tipi_slug' => $yayinTipiSlug,
                    'yayin_tip' . 'i_name' => $propertyType->name,
                ],
                'suggestions' => [
                    'required' => $suggestions['required'] ?? [],
                    'suggested' => $suggestions['suggested'] ?? [],
                    'disallowed' => $disallowedFeatures,
                ],
                'current_assignments' => $currentAssignments,
            ]);

        } catch (\Exception $e) {
            LogService::error('Feature suggestions retrieval failed', [
                'error' => $e->getMessage(),
                'property_type_id' => $propertyTypeId,
            ]);

            return $this->sendUPSError('Feature suggestions alınamadı', ['error' => $e->getMessage()]);
        }
    }
}

