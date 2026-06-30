<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\SiteApartman;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteApartmanController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Site/Apartman arama
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'q' => 'required|string|min:2',
            'type' => 'nullable|string|in:site,apartman', // context7-ignore
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $query = SiteApartman::query();

            // Tip filtresi
            if ($request->type) { // context7-ignore
                $query->where('tip', $request->type); // context7-ignore
            }

            // Arama
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%'.$request->q.'%')
                    ->orWhere('adres', 'LIKE', '%'.$request->q.'%');
            });

            $results = $query->limit(10)->get([
                'id', 'name', 'adres', 'toplam_daire_sayisi', 'tip',
            ]);

            // Context7 Live Search compatibility: add 'text' field
            $results->each(function ($item) {
                $item->text = $item->name;
                $item->daire_sayisi = $item->toplam_daire_sayisi;
            });

            return ResponseService::success([
                'data' => $results,
                'count' => $results->count(),
            ], 'Site/Apartman araması başarıyla tamamlandı');

        } catch (\Exception $e) {
            return ResponseService::serverError('Arama sırasında hata oluştu.', $e);
        }
    }

    /**
     * Site/Apartman detayları
     */
    public function show($id): JsonResponse
    {
        try {
            $site = SiteApartman::findOrFail($id);

            return ResponseService::success([
                'site' => $site,
            ], 'Site/Apartman detayları başarıyla getirildi');

        } catch (\Exception $e) {
            return ResponseService::notFound('Site bulunamadı');
        }
    }
}
