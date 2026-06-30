<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Template\TemplateService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 🎯 Template Controller - Phase 4 Integration
 * 
 * DynamicFormHandler.js ile iletişim kuran API endpoint'leri.
 * Kategori seçildiğinde optimal template'i çekip features'ı enjekte eder.
 * 
 * Context7 Compliance: %100
 * - kategori_id (NOT category_id)
 * - yayin_tipi_id (NOT publication_type_id)
 * - aktiflik_durumu (NOT status, enabled)
 * 
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 * @version 1.0.0
 */
class TemplateController extends Controller
{
    public function __construct(
        private TemplateService $templateService
    ) {}

    /**
     * 🎯 Auto-Select Template + Features
     * 
     * GET /api/v1/templates/auto-select?kategori_id={id}&yayin_tipi_id={id}
     * 
     * Danışman kategori seçtiğinde DynamicFormHandler bu endpoint'i çağırır.
     * Optimal template'i ve o kategori/yayın tipi kombinasyonuna ait features'ı döner.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function autoSelect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'nullable|integer',
            'force_refresh' => 'nullable|boolean',
        ]);

        try {
            $context = [
                'force_refresh' => $validated['force_refresh'] ?? false,
            ];

            $result = $this->templateService->autoSelectTemplate(
                $validated['kategori_id'],
                $validated['yayin_tipi_id'] ?? null,
                $context
            );

            // ✅ ResponseService kullanımı
            return ResponseService::success($result, 'Template ve features başarıyla yüklendi');

        } catch (\Exception $e) {
            return ResponseService::error('Template yükleme başarısız', 400, ['error' => $e->getMessage()]);
        }
    }

    /**
     * 🔐 Seal Publication Type Fields
     * 
     * POST /api/v1/templates/seal-publication-type
     * 
     * Yayın tipi değiştiğinde zorunlu alanları mühürle.
     * Örn: Kiralık seçilirse 'depozito' ve 'tahliye_taahhutnamesi' zorunlu hale gelir.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sealPublicationType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|integer',
        ]);

        try {
            $sealed = $this->templateService->sealPublicationTypeFields(
                $validated['kategori_id'],
                $validated['yayin_tipi_id']
            );

            // ✅ ResponseService kullanımı
            return ResponseService::success([
                'sealed_fields' => $sealed,
                'yayin_tipi_id' => $validated['yayin_tipi_id'],
                'timestamp' => now()->toIso8601String(),
            ], "Yayın tipi için zorunlu alanlar mühürlendi");

        } catch (\Exception $e) {
            return ResponseService::error('Publication type sealing başarısız', 400, ['error' => $e->getMessage()]);
        }
    }

    /**
     * 🧹 Clear Cache
     * 
     * POST /api/v1/templates/clear-cache
     * 
     * Template cache'ini temizle (admin ve dev için).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'nullable|integer',
        ]);

        $this->templateService->clearCache($validated['kategori_id'] ?? null);

        // ✅ ResponseService kullanımı
        return ResponseService::success(null, 'Template cache temizlendi');
    }
}
