<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\V2\Ilan;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class MobileSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar'])
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value);

        // Text Search
        if ($request->filled('q')) {
            $query->where(function($q) use ($request) {
                $q->where('baslik', 'like', '%' . $request->q . '%')
                  ->orWhere('aciklama', 'like', '%' . $request->q . '%');
            });
        }

        // Location Filters
        if ($request->filled('il_id')) $query->where('il_id', $request->il_id);
        if ($request->filled('ilce_id')) $query->where('ilce_id', $request->ilce_id);
        if ($request->filled('mahalle_id')) $query->where('mahalle_id', $request->mahalle_id);

        // Price Range
        if ($request->filled('min_price')) $query->where('fiyat', '>=', $request->min_price);
        if ($request->filled('max_price')) $query->where('fiyat', '<=', $request->max_price);

        // Category & Type
        if ($request->filled('category_id')) $query->where('alt_kategori_id', $request->category_id);
        if ($request->filled('type')) $query->where('islem_tipi', $request->type); // satis, kiralama // context7-ignore

        // Features (JSON or Pivot) - Simplified for MVP
        if ($request->filled('features') && is_array($request->features)) {
            // Implementation depends on feature storage.
            // For now assuming boolean columns or simple where checks if columns exist
            foreach ($request->features as $feature) {
               // Security check: only allow known safe columns if using dynamic where
               // Or use scope if available.
            }
        }

        // Sorting
        $sort = $request->input('sort', 'date_desc');
        switch ($sort) {
            case 'price_asc': $query->orderBy('fiyat', 'asc'); break; // context7-ignore
            case 'price_desc': $query->orderBy('fiyat', 'desc'); break; // context7-ignore
            case 'date_desc': default: $query->latest(); break;
        }

        $listings = $query->paginate(20);

        // Transformation
        /** @var \Illuminate\Pagination\LengthAwarePaginator $listings */
        $listings->getCollection()->transform(fn($item) => $this->transformIlan($item));

        return ResponseService::success($listings, 'Arama sonuçları getirildi');
    }

    private function transformIlan($ilan)
    {
        return [
            'id' => $ilan->id,
            'baslik' => $ilan->baslik,
            'fiyat' => $ilan->fiyat,
            'para_birimi' => $ilan->para_birimi,
            'location' => ($ilan->ilce->ilce_adi ?? '') . ', ' . ($ilan->il->il_adi ?? ''),
            'image' => $ilan->kapak_fotografi ?? null,
            'oda_sayisi' => $ilan->oda_sayisi,
            'm2' => $ilan->brut_m2,
            'created_at' => $ilan->created_at,
        ];
    }
}
