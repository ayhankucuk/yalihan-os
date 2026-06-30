<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Exceptions\TemplateCategoryMismatchException;
use App\Exceptions\TemplateNotFoundException;
use App\Services\Wizard\WizardContextService;
use App\Services\Wizard\WizardGateService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Logging\LogService;

class WizardContextController extends Controller
{
    public function __construct(
        private WizardContextService $contextService,
        private WizardGateService $gateService
    ) {}

    /**
     * GET /api/v1/wizard/context
     *
     * Returns the full context for the listing wizard based on category and publication type.
     * SAB Phase 17B: Template mapping yoksa wizard açılmaz (hard block).
     */
    public function resolve(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => 'nullable|integer',
            'alt_kategori_id' => 'nullable|integer',
            'yayin_tipi_id' => 'nullable|integer',
            'junction_id' => 'nullable|integer',
        ]);

        $kategoriId = $validated['alt_kategori_id'] ?? $validated['kategori_id'];

        // Junction ID alias: junction_id is SSOT, yayin_tipi_id is deprecated
        if (!empty($validated['junction_id'])) {
            $yayinTipiId = $validated['junction_id'];
        } elseif (!empty($validated['yayin_tipi_id'])) {
            $yayinTipiId = $validated['yayin_tipi_id'];
            Log::notice('WIZARD_CONTEXT_DEPRECATED_PARAM', [
                'message' => 'yayin_tipi_id parametresi deprecated. junction_id kullanın.',
                'yayin_tipi_id' => $yayinTipiId,
                'ip' => $request->ip(),
            ]);
        } else {
            return response()->json([
                'success' => true,
                'context' => null,
                'state' => 'incomplete_selection',
                'message' => 'Yayın tipi seçimi bekleniyor.',
            ]);
        }

        if (!$kategoriId) {
            return response()->json([
                'success' => true,
                'context' => null,
                'state' => 'incomplete_selection',
                'message' => 'Kategori seçimi bekleniyor.',
            ]);
        }

        // ✅ SAB Phase 17B: Template Guard — mapping yoksa fail-soft
        try {
            $this->gateService->dogrulaWizardGirisi($yayinTipiId, $kategoriId);
        } catch (TemplateNotFoundException $e) {
            LogService::error('WizardContext: template not found', [
                'kategori_id' => $kategoriId,
                'junction_id' => $yayinTipiId,
            ], $e);

            return response()->json([
                'success' => true,
                'context' => null,
                'state' => 'template_not_found',
                'message' => 'Bu kategori/yayın tipi için geçerli bir şablon bulunamadı.',
            ]);
        } catch (TemplateCategoryMismatchException $e) {
            LogService::error('WizardContext: template mismatch', [
                'kategori_id' => $kategoriId,
                'junction_id' => $yayinTipiId,
            ], $e);

            return response()->json([
                'success' => true,
                'context' => null,
                'state' => 'template_mismatch',
                'message' => 'Seçilen şablon bu kategori ile uyuşmuyor.',
            ]);
        }

        $context = $this->contextService->resolve($kategoriId, $yayinTipiId, $request->all());

        return response()->json($context);
    }
}
