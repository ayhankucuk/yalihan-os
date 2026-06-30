<?php

namespace App\Traits;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\Logging\LogService;

/**
 * Trait for managing feature display order
 * ✅ SAB: Unified feature ordering functionality
 *
 * @package App\Traits
 */
trait ManagesFeatureOrder
{
    /**
     * Update feature display order (general)
     * ✅ SAB: Uses display_order field
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFeatureOrder(Request $request): JsonResponse
    {
        $request->validate([
            'features' => 'required|array|min:1',
            'features.*.id' => 'required|exists:features,id',
            'features.*.display_order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->features as $featureData) {
                Feature::where('id', $featureData['id'])
                    ->update(['display_order' => $featureData['display_order']]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sıralama başarıyla güncellendi'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            LogService::error('Feature order update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Sıralama güncellenirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update feature assignment order (publication type based)
     * ✅ SAB: Publication type specific ordering
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAssignmentOrder(Request $request): JsonResponse
    {
        $request->validate([
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'assignments' => 'required|array|min:1',
            'assignments.*.id' => 'required|exists:feature_assignments,id',
            'assignments.*.display_order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->assignments as $assignmentData) {
                FeatureAssignment::where('id', $assignmentData['id'])
                    ->where('assignable_type', YayinTipiSablonu::class)
                    ->where('assignable_id', $request->yayin_tipi_id)
                    ->update(['display_order' => $assignmentData['display_order']]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Yayın tipi bazlı sıralama başarıyla güncellendi'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            LogService::error('Assignment order update failed', [
                'error' => $e->getMessage(),
                'yayin_tipi_id' => $request->yayin_tipi_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Sıralama güncellenirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
}
