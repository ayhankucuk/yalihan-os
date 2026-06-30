<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Feature\FeatureBulkService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;

/**
 * Bulk Operations API Controller
 *
 * PHASE 2.3: Toplu işlemler için API endpoints
 * Context7 Compliant
 *
 * @author Yalıhan Emlak - Context7 Team
 *
 * @date 2025-11-04
 */
class BulkOperationsController extends Controller
{
    use ValidatesApiRequests;

    public function __construct(
        private readonly FeatureBulkService $featureBulk,
    ) {}

    /**
     * Toplu kategori atama
     * POST /api/admin/bulk/assign-category
     */
    public function assignCategory(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'items' => 'required|array|min:1',
            'items.*' => 'required|exists:features,id',
            'category_id' => 'required|exists:feature_categories,id',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $result = $this->featureBulk->assignCategory(
                $request->items,
                (int) $request->category_id,
            );

            return ResponseService::success($result, "{$result['updated_count']} özellik kategoriye atandı");
        } catch (\Exception $e) {
            return ResponseService::serverError('Toplu kategori atama başarısız.', $e);
        }
    }

    /**
     * Toplu aktiflik durumu değiştirme
     * POST /api/admin/bulk/toggle-yayin-durumu
     */
    public function toggleYayinDurumu(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'items' => 'required|array|min:1',
            'items.*' => 'required|exists:features,id',
            'aktiflik_durumu' => 'nullable|boolean',
            'toplu_islem_durumu' => 'nullable|boolean',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        if (! $request->hasAny(['aktiflik_durumu', 'toplu_islem_durumu'])) {
            return ResponseService::error('Aktiflik durumu eksik', 422, [], 'MISSING_STATE');
        }

        $hedefDurum = $request->has('aktiflik_durumu')
            ? $request->boolean('aktiflik_durumu')
            : $request->boolean('toplu_islem_durumu');

        try {
            $result = $this->featureBulk->toggleAktiflikDurumu($request->items, $hedefDurum);
            $action = $hedefDurum ? 'yayinlanacak' : 'kaldirilacak';

            return ResponseService::success($result, "{$result['updated_count']} özellik yayini {$action} yapıldı");
        } catch (\Exception $e) {
            return ResponseService::serverError('Toplu aktiflik durumu değiştirme başarısız.', $e);
        }
    }

    /**
     * Toplu silme
     * POST /api/admin/bulk/delete
     */
    public function bulkDelete(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'items' => 'required|array|min:1',
            'items.*' => 'required|exists:features,id',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $result = $this->featureBulk->bulkDelete($request->items);

            return ResponseService::success($result, "{$result['deleted_count']} özellik silindi");
        } catch (\Exception $e) {
            return ResponseService::serverError('Toplu silme başarısız.', $e);
        }
    }

    /**
     * Toplu sıralama güncelleme
     * POST /api/admin/bulk/sirala
     */
    public function sirala(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:features,id',
            'items.*.display_order' => 'required|integer|min:0',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $result = $this->featureBulk->sirala($request->items);

            return ResponseService::success([], 'Sıralama güncellendi');
        } catch (\Exception $e) {
            return ResponseService::serverError('Sıralama güncellenemedi.', $e);
        }
    }
}
