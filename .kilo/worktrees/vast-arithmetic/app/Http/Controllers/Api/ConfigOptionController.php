<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Helpers\ConfigOptionHelper;
use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Config Option API Controller
 *
 * RESTful API endpoints for config options
 * Context7: C7-CONFIG-OPTIONS-API-2025-12-15
 */
class ConfigOptionController extends Controller
{
    /**
     * Get config option by key
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $request->validate([
            'option_key' => 'required|string',
            'kategori_id' => 'nullable|integer|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'nullable|integer|exists:yayin_tipi_sablonlari,id',
        ]);

        $optionKey = $request->input('option_key');
        $kategoriId = $request->input('kategori_id');
        $yayinTipiId = $request->input('yayin_tipi_id');

        // Fallback için eski config dosyasından al
        $default = config("yali_options.{$optionKey}", []);

        $value = ConfigOptionHelper::get($optionKey, $kategoriId, $yayinTipiId, $default);

        return ResponseService::success([
            'option_key' => $optionKey,
            'kategori_id' => $kategoriId,
            'yayin_tipi_id' => $yayinTipiId,
            'value' => $value,
        ], 'Config option başarıyla getirildi');
    }

    /**
     * Get multiple config options
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'option_keys' => 'required|array',
            'option_keys.*' => 'required|string',
            'kategori_id' => 'nullable|integer|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'nullable|integer|exists:yayin_tipi_sablonlari,id',
        ]);

        $optionKeys = $request->input('option_keys');
        $kategoriId = $request->input('kategori_id');
        $yayinTipiId = $request->input('yayin_tipi_id');

        $results = [];
        foreach ($optionKeys as $optionKey) {
            $default = config("yali_options.{$optionKey}", []);
            $results[$optionKey] = ConfigOptionHelper::get($optionKey, $kategoriId, $yayinTipiId, $default);
        }

        return ResponseService::success([
            'kategori_id' => $kategoriId,
            'yayin_tipi_id' => $yayinTipiId,
            'options' => $results,
        ], 'Config options başarıyla getirildi');
    }
}
