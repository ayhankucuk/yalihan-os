<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AIService;
use App\Services\Response\ResponseService;
use App\Services\AI\SmartFieldGenerationService; // SSOT: AI namespace
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;

class SmartFieldController extends Controller
{
    use ValidatesApiRequests;

    protected $smartFieldService;

    protected $aiService;

    public function __construct(SmartFieldGenerationService $smartFieldService, AIService $aiService)
    {
        $this->smartFieldService = $smartFieldService;
        $this->aiService = $aiService;
    }

    /**
     * Kategori bazlı akıllı field önerileri
     */
    public function getSmartFields(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'kategori_slug' => 'required|string',
            'yayin_tipi_id' => 'nullable|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $smartFields = $this->smartFieldService->getSmartFieldsForCategory(
                $request->kategori_slug,
                $request->yayin_tipi_id
            );

            return ResponseService::success([
                'data' => $smartFields,
            ], 'Akıllı field önerileri başarıyla alındı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Field önerileri alınırken hata oluştu.', $e);
        }
    }

    /**
     * Kategori bazlı özellik matrisi
     */
    public function getCategoryMatrix(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'kategori_slug' => 'required|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $matrix = $this->smartFieldService->generateCategoryMatrix($request->kategori_slug);

            return ResponseService::success([
                'data' => $matrix,
            ], 'Kategori matrisi başarıyla oluşturuldu');
        } catch (\Exception $e) {
            return ResponseService::serverError('Matris oluşturulurken hata oluştu.', $e);
        }
    }

    /**
     * Akıllı form oluştur
     */
    public function generateSmartForm(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'kategori_slug' => 'required|string',
            'yayin_tipi_id' => 'nullable|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $smartForm = $this->smartFieldService->generateSmartForm(
                $request->kategori_slug,
                $request->yayin_tipi_id
            );

            return ResponseService::success([
                'data' => $smartForm,
            ], 'Akıllı form başarıyla oluşturuldu');
        } catch (\Exception $e) {
            return ResponseService::serverError('Form oluşturulurken hata oluştu.', $e);
        }
    }

    /**
     * AI ile özellik analizi
     */
    public function analyzeProperty(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'property_data' => 'required|array',
            'context' => 'nullable|array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $analysis = $this->aiService->analyzePropertyFeatures(
                $request->property_data,
                $request->context ?? []
            );

            return ResponseService::success([
                'data' => $analysis,
            ], 'Özellik analizi başarıyla tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError('Analiz sırasında hata oluştu.', $e);
        }
    }

    /**
     * AI ile field önerileri
     */
    public function getAISuggestions(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'kategori_slug' => 'required|string',
            'yayin_tipi_id' => 'nullable|string',
            'context' => 'nullable|array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $suggestions = $this->aiService->suggestFieldsForCategory(
                $request->kategori_slug,
                $request->yayin_tipi_id,
                $request->context ?? []
            );

            return ResponseService::success([
                'data' => $suggestions,
            ], 'AI önerileri başarıyla alındı');
        } catch (\Exception $e) {
            return ResponseService::serverError('AI önerileri alınırken hata oluştu.', $e);
        }
    }
}
