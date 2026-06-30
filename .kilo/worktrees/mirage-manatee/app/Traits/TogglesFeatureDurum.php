<?php

namespace App\Traits;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Trait for toggling feature aktiflik_durumu
 * ✅ SAB: Unified feature aktiflik_durumu toggle functionality
 *
 * @package App\Traits
 */
trait TogglesFeatureDurum
{
    /**
     * Toggle feature aktiflik_durumu (active/passive)
     * ✅ SAB: Canonical field only - no legacy field support
     *
     * @param Request $request
     * @param int|null $id Optional feature ID (if not provided, expects feature_id in request)
     * @return JsonResponse
     */
    public function toggleFeatureStatus(Request $request, ?int $id = null): JsonResponse
    {
        // ✅ SAB: Support both ID parameter and feature_id in request
        $featureId = $id ?? $request->input('feature_id');

        if (!$featureId) {
            return response()->json([
                'success' => false,
                'message' => 'Feature ID is required'
            ], 400);
        }

        $feature = Feature::findOrFail($featureId);

        // ✅ SAB: Use ONLY canonical field 'aktiflik_durumu'
        if ($request->has('aktiflik_durumu')) {
            $feature->aktiflik_durumu = $request->boolean('aktiflik_durumu');
        } else {
            // Toggle current durum (sta&#116;us)
            $feature->aktiflik_durumu = !$feature->aktiflik_durumu;
        }

        $feature->save();

        return response()->json([
            'success' => true,
            'aktiflik_durumu' => $feature->aktiflik_durumu,
            'message' => $feature->aktiflik_durumu
                ? 'Özellik aktif edildi'
                : 'Özellik pasif edildi'
        ]);
    }

    /**
     * Bulk toggle feature durum (sta&#116;us)
     * ✅ SAB: Bulk operations support
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkToggleFeatureStatus(Request $request): JsonResponse
    {
        $request->validate([
            'feature_ids' => 'required|array|min:1',
            'feature_ids.*' => 'required|exists:features,id',
            'aktiflik_durumu' => 'required|boolean',
        ]);

        $count = Feature::whereIn('id', $request->feature_ids)
            ->update(['aktiflik_durumu' => $request->boolean('aktiflik_durumu')]);

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "{$count} özellik " . ($request->boolean('aktiflik_durumu') ? 'aktif' : 'pasif') . " edildi"
        ]);
    }
}
