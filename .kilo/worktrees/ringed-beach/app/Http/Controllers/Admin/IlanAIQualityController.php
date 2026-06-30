<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\YalihanCortex;
use App\Services\Response\ResponseService;
use App\Services\Logging\LogService;
use Illuminate\Http\Request;

/**
 * Ilan AI Quality Controller
 *
 * Phase C: AI quality check endpoint for pre-publish soft-gate
 *
 * Kurallar:
 * - UPS SSOT: Feature context via UpsFeatureContextService + FeatureTemplateResolver
 * - Cortex observer mode: Sadece okur + orkestre eder
 * - Hallucination guard: Sadece UPS context kullanılır
 * - Context7/MCP logging zorunlu
 *
 * Endpoint: POST /admin/ilanlar/ai/quality-check
 */
class IlanAIQualityController extends Controller
{
    public function __construct(
        private YalihanCortex $cortex
    ) {
        $this->middleware('can:manage-ilanlar');
    }

    /**
     * AI quality check for listing (Phase C)
     *
     * Request payload:
     * {
     *   "kategori_slug": "yazlik-kiralik",
     *   "yayin_tipi_slug": "gunluk",
     *   "ilan": {
     *     "baslik": "string|null",
     *     "aciklama": "string|null",
     *     "fiyat": 123,
     *     "para_birimi": "TRY",
     *     "il_id": 34,
     *     "ilce_id": 123,
     *     "mahalle_id": 456
     *   },
     *   "draft_features": {
     *     "oda_sayisi": "3+1",
     *     "net_metrekare": 120,
     *     "gunluk_fiyat": 5000,
     *     "minimum_konaklama": 3,
     *     ...
     *   }
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "quality_score": 82,
     *     "recommendation": "needs_review",
     *     "issues": [
     *       {"code":"DESC_TOO_SHORT","message":"Açıklama çok kısa (min 300 karakter önerilir)."}
     *     ],
     *     "suggested_fixes": [
     *       {"code":"ADD_LOCATION_CONTEXT","message":"Konum (ilçe/mahalle) bilgisini açıklamaya ekle."}
     *     ],
     *     "meta": {
     *       "provider_used": "gemini|openai|ollama|deepseek",
     *       "duration_ms": 412
     *     }
     *   }
     * }
     */
    public function qualityCheck(Request $request)
    {
        try {
            // Validation
            $validated = $request->validate([
                'ilan_id' => 'nullable|integer',
                'kategori_slug' => 'required|string',
                'yayin_tipi_slug' => 'required|string',
                'ilan' => 'nullable|array',
                'ilan.baslik' => 'nullable|string',
                'ilan.aciklama' => 'nullable|string',
                'ilan.fiyat' => 'nullable|numeric',
                'ilan.para_birimi' => 'nullable|string',
                'ilan.il_id' => 'nullable|integer',
                'ilan.ilce_id' => 'nullable|integer',
                'ilan.mahalle_id' => 'nullable|integer',
                'draft_features' => 'nullable|array',
            ]);

            LogService::info('AI quality check started', [
                'kategori_slug' => $validated['kategori_slug'],
                'yayin_tipi_slug' => $validated['yayin_tipi_slug'],
                'has_baslik' => !empty($validated['ilan']['baslik'] ?? null),
                'has_aciklama' => !empty($validated['ilan']['aciklama'] ?? null),
            ]);

            // Cortex quality check (UPS SSOT + observer mode)
            $result = $this->cortex->evaluateListingQuality($validated);

            if (!$result['success']) {
                return ResponseService::error(
                    $result['message'] ?? 'Kalite kontrolü başarısız',
                    500,
                    ['quality_check' => $result['data']['issues'] ?? []]
                );
            }

            return ResponseService::success(
                data: $result['data'],
                message: 'Kalite kontrolü tamamlandı'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError(
                errors: $e->errors(),
                message: 'Validasyon hatası'
            );
        } catch (\Exception $e) {
            LogService::error('AI quality check failed', [
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError(
                message: 'Kalite kontrolü şu an çalışmadı, tekrar deneyin.',
                exception: $e
            );
        }
    }
}
