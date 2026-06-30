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
use Illuminate\Support\Facades\Cache;

class MobileMapController extends Controller
{
    /**
     * Lightweight Map Data Endpoint
     * Returns minimal data for client-side clustering
     * Cached for 15 minutes
     */
    public function index()
    {
        $pins = Cache::remember('mobile_map_pins', now()->addMinutes(15), function () {
            return Ilan::query()
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->select(['id', 'lat', 'lng', 'fiyat', 'para_birimi', 'islem_tipi', 'alt_kategori_id', 'baslik', 'slug'])
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'lat' => (float) $item->lat,
                        'lng' => (float) $item->lng,
                        'price' => $item->fiyat,
                        'currency' => $item->para_birimi,
                        'type' => $item->islem_tipi, // satis/kiralama // context7-ignore
                        'category_id' => $item->alt_kategori_id,
                        'title' => $item->baslik,
                    ];
                });
        });

        return ResponseService::success($pins, 'Harita verileri getirildi');
    }
}
