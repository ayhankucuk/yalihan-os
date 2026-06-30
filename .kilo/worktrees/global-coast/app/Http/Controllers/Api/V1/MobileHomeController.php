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
use Illuminate\Support\Facades\DB;

class MobileHomeController extends Controller
{
    /**
     * Mobile Home Composite API
     * Cached for 30 minutes to ensure high performance
     */
    public function index()
    {
        $data = Cache::remember('mobile_home_index', now()->addMinutes(30), function () {
            // 1. Stories (Mock)
            $stories = [
                [
                    'id' => 1,
                    'title' => 'Bodrum',
                    'image' => 'https://images.unsplash.com/photo-1548663897-4cc3d2b2d287?q=80&w=200&auto=format&fit=crop',
                    'link' => 'yalihan://search?query=bodrum'
                ],
                [
                    'id' => 2,
                    'title' => 'Lüks Villalar',
                    'image' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?q=80&w=200&auto=format&fit=crop',
                    'link' => 'yalihan://search?category=villa'
                ],
                [
                    'id' => 3,
                    'title' => 'Fırsatlar',
                    'image' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=200&auto=format&fit=crop',
                    'link' => 'yalihan://search?sort=price_asc'
                ]
            ];

            // 2. Featured Listings (Öne Çıkanlar)
            $featured = Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar'])
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->where('one_cikan', true)
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($item) => $this->transformIlan($item));

            // 3. Recent Listings (Son Eklenenler)
            $recent = Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar'])
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($item) => $this->transformIlan($item));

            // 4. Popular Locations (Based on active listing count)
            $popularLocations = Ilan::query()
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->select('ilce_id', DB::raw('count(*) as total'))
                ->with('ilce')
                ->groupBy('ilce_id')
                ->orderByDesc('total') // context7-ignore
                ->take(6)
                ->get()
                ->map(function ($item) {
                     return [
                         'id' => $item->ilce_id,
                         'name' => $item->ilce->ilce_adi ?? 'Bilinmiyor',
                         'count' => $item->total,
                         'image' => 'https://source.unsplash.com/random/400x300/?' . ($item->ilce->ilce_adi ?? 'city'),
                     ];
                });

            return [
                'stories' => $stories,
                'featured_listings' => $featured,
                'recent_listings' => $recent,
                'popular_locations' => $popularLocations,
            ];
        });

        return ResponseService::success($data, 'Anasayfa verileri getirildi');
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
        ];
    }
}
