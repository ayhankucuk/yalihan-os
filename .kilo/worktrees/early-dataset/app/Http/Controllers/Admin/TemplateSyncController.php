<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Services\Admin\FeatureTemplateService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Template Sync Controller
 *
 * Handles administrative requests for bulk feature synchronization.
 */
class TemplateSyncController extends AdminController
{
    protected $templateService;

    public function __construct(FeatureTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Start a tree synchronization process
     *
     * POST /admin/ilan-kategorileri/sync-features
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncFeatures(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'required|integer|exists:ilan_kategoriler,id',
            'source_id' => 'required|integer|exists:ilan_kategoriler,id',
            'options'   => 'nullable|array'
        ]);

        $result = $this->templateService->syncTreeFromSource(
            (int) $validated['parent_id'],
            (int) $validated['source_id'],
            $validated['options'] ?? []
        );

        if (!$result['success']) {
            return ResponseService::error($result['message'] ?? 'Eşitleme sırasında bir hata oluştu.', 422, $result['errors'] ?? []);
        }

        return ResponseService::success($result, 'Kategori özellikleri başarıyla eşitlendi.');
    }
}
