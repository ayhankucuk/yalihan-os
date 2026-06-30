<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Services\CategoryFieldValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Ilan Validation Controller
 *
 * Context7 Standardı: C7-ILAN-VALIDATION-CONTROLLER-2025-12-23
 *
 * Handles validation rules and FormRequest validation for Ilan
 * Delegates to CategoryFieldValidator service
 *
 * @package App\Http\Controllers\Admin
 */
class IlanValidationController extends AdminController
{
    /**
     * Get validation rules for category and publication type
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getValidationRules(Request $request): JsonResponse
    {
        $request->validate([
            'kategori_slug' => 'required|string',
            'yayin_tipi_slug' => 'nullable|string',
        ]);

        $categoryValidator = new CategoryFieldValidator;
        $rules = $categoryValidator->getRules(
            $request->kategori_slug,
            $request->yayin_tipi_slug
        );

        $messages = $categoryValidator->getMessages();

        return response()->json([
            'success' => true,
            'rules' => $rules,
            'messages' => $messages,
        ]);
    }

    /**
     * Validate ilan data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateIlan(Request $request): JsonResponse
    {
        $kategoriSlug = $request->input('kategori_slug');
        $yayinTipiSlug = $request->input('yayin_tipi_slug');

        // Base rules
        $baseRules = [
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'fiyat' => 'required|numeric|min:0',
            'para_birimi' => 'required|string|in:TRY,USD,EUR,GBP',
            'ana_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'alt_kategori_id' => 'required|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
            'ilan_sahibi_id' => 'required|exists:kisiler,id',
            'yayin_durumu' => 'required|string|in:Taslak,Aktif,Pasif,Beklemede', // ✅ SAB: status → yayin_durumu
        ];

        // Get category-specific rules
        $categoryValidator = new CategoryFieldValidator;
        $categoryRules = $categoryValidator->getRules($kategoriSlug, $yayinTipiSlug);
        $allRules = array_merge($baseRules, $categoryRules);

        $validator = Validator::make($request->all(), $allRules, $categoryValidator->getMessages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Validation passed',
        ]);
    }
}

