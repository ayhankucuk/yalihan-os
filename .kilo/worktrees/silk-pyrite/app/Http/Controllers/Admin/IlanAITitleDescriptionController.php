<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AiFeatureUsage;
use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use App\Services\AI\DataDrivenAIContentService;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use App\Actions\Admin\AI\GenerateIlanTitleAction;
use App\Actions\Admin\AI\GenerateIlanDescriptionAction;

/**
 * Phase B + Phase I: AI Title/Description with Feature Telemetry
 *
 * Context7: UPS SSOT, Cortex observer mode, MCP logging
 * Data-Driven: Structured data only, no assumptions
 */
class IlanAITitleDescriptionController extends Controller
{
    protected \App\Services\AI\DanismanAIService $danismanAI;
    protected DataDrivenAIContentService $dataDrivenAI;
    protected \App\Services\AI\AiCostGuardService $costGuard;
    protected GenerateIlanTitleAction $generateTitleAction;
    protected GenerateIlanDescriptionAction $generateDescriptionAction;

    public function __construct(
        \App\Services\AI\DanismanAIService $danismanAI,
        DataDrivenAIContentService $dataDrivenAI,
        \App\Services\AI\AiCostGuardService $costGuard,
        GenerateIlanTitleAction $generateTitleAction,
        GenerateIlanDescriptionAction $generateDescriptionAction
    ) {
        $this->danismanAI = $danismanAI;
        $this->dataDrivenAI = $dataDrivenAI;
        $this->costGuard = $costGuard;
        $this->generateTitleAction = $generateTitleAction;
        $this->generateDescriptionAction = $generateDescriptionAction;
    }

    /**
     * 🛡️ Phase 23: Budget Check for Frontend
     */
    public function checkBudget(Request $request)
    {
        $check = $this->costGuard->checkBudget();
        return ResponseService::success($check);
    }

    /**
     * ✅ Phase B: Generate AI title with UPS context
     * ✅ SAB: Step 1 Wizard Support - Basit format desteği
     */
    public function generateTitle(Request $request)
    {
        $result = $this->generateTitleAction->handle(
            $request->all(),
            [
                'tone' => $request->input('ai_tone', 'seo'),
                'provider' => $request->input('provider', config('ai.default_provider', 'ollama')),
            ]
        );

        if (!$result['success']) {
            return ResponseService::error($result['error'], $result['code'] ?? 500);
        }

        return ResponseService::success([
            'text' => $result['text'],
            'alternatives' => $result['alternatives'],
            'variants' => $result['variants'],
            'count' => count($result['alternatives']),
            'provider' => $result['provider'],
            'model' => $result['model'],
        ], 'Başlık önerileri oluşturuldu');
    }

    /**
     * ✅ Phase B: Generate AI description
     * ✅ Data-Driven: Uses structured data only, no assumptions
     * POST /admin/ilan-ai/description
     */
    public function generateDescription(Request $request)
    {
        $result = $this->generateDescriptionAction->handle(
            $request->input('structured_data', []),
            $request->input('options', []),
            'description'
        );

        if (!$result['success']) {
            return ResponseService::error($result['error'], $result['code'] ?? 500);
        }

        return ResponseService::success($result['data'], 'Açıklama oluşturuldu', 200, [
            'provider' => $result['provider'],
            'metadata' => $result['metadata'],
        ]);
    }

    /**
     * ✅ Data-Driven: Generate title from structured data
     * POST /admin/ilan-ai/title-data-driven
     */
    public function generateTitleDataDriven(Request $request)
    {
        $result = $this->generateDescriptionAction->handle(
            $request->input('structured_data', []),
            $request->input('options', []),
            'title'
        );

        if (!$result['success']) {
            return ResponseService::error($result['error'], $result['code'] ?? 500);
        }

        return ResponseService::success($result['data'], 'Başlık oluşturuldu', 200, [
            'provider' => $result['provider'],
            'metadata' => $result['metadata'],
        ]);
    }

    /**
     * ✅ Data-Driven: Generate summary from structured data
     * POST /admin/ilan-ai/summary
     */
    public function generateSummary(Request $request)
    {
        $result = $this->generateDescriptionAction->handle(
            $request->input('structured_data', []),
            $request->input('options', []),
            'summary'
        );

        if (!$result['success']) {
            return ResponseService::error($result['error'], $result['code'] ?? 500);
        }

        return ResponseService::success($result['data'], 'Özet oluşturuldu', 200, [
            'provider' => $result['provider'],
            'metadata' => $result['metadata'],
        ]);
    }

    /**
     * ✅ Data-Driven: Generate SEO meta from structured data
     * POST /admin/ilan-ai/seo-meta
     */
    public function generateSeoMeta(Request $request)
    {
        $result = $this->generateDescriptionAction->handle(
            $request->input('structured_data', []),
            $request->input('options', []),
            'seo_meta'
        );

        if (!$result['success']) {
            return ResponseService::error($result['error'], $result['code'] ?? 500);
        }

        return ResponseService::success($result['data'], 'SEO meta oluşturuldu', 200, [
            'provider' => $result['provider'],
            'metadata' => $result['metadata'],
        ]);
    }

    public function generateAiTitle(Request $request)
    {
        $response = $this->generateTitle($request);
        return response()->json($response->original, $response->getStatusCode());
    }

    public function generateAiDescription(Request $request)
    {
        $response = $this->generateDescription($request);
        return response()->json($response->original, $response->getStatusCode());
    }

    public function generateAiCopy(Request $request, Ilan $ilan)
    {
        $request->merge([
            'kategori_slug' => (string) ($ilan->kategori->slug ?? $ilan->kategori_id ?? 'genel'),
            'ilan' => [
                'baslik' => $ilan->baslik,
                'fiyat' => $ilan->fiyat,
                'para_birimi' => $ilan->para_birimi,
                'il' => optional($ilan->il)->il_adi,
                'ilce' => optional($ilan->ilce)->ilce_adi,
                'mahalle' => optional($ilan->mahalle)->mahalle_adi,
            ],
        ]);

        $response = $this->generateDescription($request);
        return response()->json($response->original, $response->getStatusCode());
    }

    public function generatePropertyTypeAiDescription(Request $request)
    {
        $response = $this->generateDescription($request);
        return response()->json($response->original, $response->getStatusCode());
    }

    public function generateMultiLanguageAiDescription(Request $request)
    {
        $response = $this->generateDescription($request);
        return response()->json($response->original, $response->getStatusCode());
    }

    public function generateImageBasedAiDescription(Request $request)
    {
        $response = $this->generateDescription($request);
        return response()->json($response->original, $response->getStatusCode());
    }

    public function getLocationBasedAiSuggestions(Request $request)
    {
        return ResponseService::success([
            'suggestions' => [],
            'context' => [
                'il' => $request->input('il'),
                'ilce' => $request->input('ilce'),
                'mahalle' => $request->input('mahalle'),
            ],
        ]);
    }

    public function optimizePriceWithAi(Request $request)
    {
        $request->validate([
            'fiyat' => 'nullable|numeric|min:0',
        ]);

        return ResponseService::success([
            'onerilen_fiyat' => $request->input('fiyat'),
            'aciklama' => 'Fiyat optimizasyonu için yeterli veri bekleniyor.',
        ]);
    }
}
