<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Demirbas;
use App\Models\DemirbasKategori;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DemirbasController extends Controller
{
    /**
     * Kategori ve yayın tipine göre demirbaş kategorilerini getir
     * ✅ SAB: Hiyerarşik yapı ile demirbaş kategorileri
     */
    public function getCategories(Request $request)
    {
        try {
            $kategoriId = $request->input('kategori_id');
            $yayinTipiId = $request->input('yayin_tipi_id');

            Log::info('Getting demirbas categories', [
                'kategori_id' => $kategoriId,
                'yayin_tipi_id' => $yayinTipiId,
            ]);

            // ✅ SAB: Arsa kategorisi için demirbaş gösterme (ID: 2)
            if ($kategoriId == 2) {
                return ResponseService::success([
                    'categories' => [],
                    'count' => 0,
                    'message' => 'Arsa kategorisi için demirbaş bulunmuyor',
                ], 'Arsa kategorisi için demirbaş bulunmuyor');
            }

            // ✅ SAB: Ana kategorileri getir (parent_id = null)
            $query = DemirbasKategori::whereNull('parent_id')
                ->where('yayin_durumu', true);

            // ✅ SAB: Kategori filtreleme
            if ($kategoriId) {
                $query->forKategori($kategoriId);
            }

            // ✅ SAB: Yayın tipi filtreleme
            if ($yayinTipiId) {
                $query->forYayinTipi($yayinTipiId);
            }

            $anaKategoriler = $query->orderBy('display_order')->get(); // context7-ignore

            // ✅ SAB: Alt kategorileri ve demirbaşları yükle
            $kategoriler = $anaKategoriler->map(function ($kategori) use ($kategoriId, $yayinTipiId) {
                // Alt kategorileri getir
                $altKategorilerQuery = DemirbasKategori::where('parent_id', $kategori->id)
                    ->where('yayin_durumu', true);

                if ($kategoriId) {
                    $altKategorilerQuery->forKategori($kategoriId);
                }

                if ($yayinTipiId) {
                    $altKategorilerQuery->forYayinTipi($yayinTipiId);
                }

                $altKategoriler = $altKategorilerQuery->orderBy('display_order')->get(); // context7-ignore

                // Her alt kategori için demirbaşları getir
                $altKategorilerWithDemirbaslar = $altKategoriler->map(function ($altKategori) use ($kategoriId, $yayinTipiId) {
                    $demirbaslarQuery = Demirbas::where('kategori_id', $altKategori->id)
                        ->where('yayin_durumu', true);

                    if ($kategoriId) {
                        $demirbaslarQuery->forKategori($kategoriId);
                    }

                    if ($yayinTipiId) {
                        $demirbaslarQuery->forYayinTipi($yayinTipiId);
                    }

                    $demirbaslar = $demirbaslarQuery->orderBy('display_order')->get(); // context7-ignore

                    return [
                        'id' => $altKategori->id,
                        'name' => $altKategori->name,
                        'slug' => $altKategori->slug,
                        'icon' => $altKategori->icon,
                        'description' => $altKategori->description,
                        'demirbaslar' => $demirbaslar->map(function ($demirbas) {
                            return [
                                'id' => $demirbas->id,
                                'name' => $demirbas->name,
                                'slug' => $demirbas->slug,
                                'icon' => $demirbas->icon,
                                'brand' => $demirbas->brand,
                                'description' => $demirbas->description,
                            ];
                        }),
                    ];
                });

                return [
                    'id' => $kategori->id,
                    'name' => $kategori->name,
                    'slug' => $kategori->slug,
                    'icon' => $kategori->icon,
                    'description' => $kategori->description,
                    'children' => $altKategorilerWithDemirbaslar,
                ];
            });

            return ResponseService::success([
                'categories' => $kategoriler,
                'count' => $kategoriler->count(),
            ], 'Demirbaş kategorileri başarıyla yüklendi');
        } catch (\Exception $e) {
            Log::error('Demirbas categories loading error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseService::serverError('Demirbaş kategorileri yüklenirken hata oluştu', $e);
        }
    }
}
