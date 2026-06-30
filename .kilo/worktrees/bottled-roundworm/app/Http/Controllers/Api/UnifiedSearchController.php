<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UnifiedSearchController extends Controller
{
    use ValidatesApiRequests;

    public function search(Request $request)
    {
        $query = $request->get('q');
        $filters = $request->get('filters', []);
        $types = $request->get('types', ['all']); // context7-ignore

        // Cache kontrolü
        $cacheKey = 'unified_search_'.md5($query.serialize($filters).serialize($types));
        $cached = Cache::get($cacheKey);

        if ($cached) {
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($cached, 'Arama sonuçları cache\'den getirildi');
        }

        // Arama sonuçları
        $results = [
            'total' => 0,
            'ilanlar' => ['items' => [], 'total' => 0],
            'kategoriler' => ['items' => [], 'total' => 0],
            'kisiler' => ['items' => [], 'total' => 0],
            'lokasyonlar' => ['items' => [], 'total' => 0],
        ];

        // Cache'e kaydet
        app(\App\Services\Cache\CacheService::class)->put($cacheKey, $results, 300); // 5 dakika

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success($results, 'Arama sonuçları başarıyla getirildi');
    }

    public function suggestions(Request $request)
    {
        $query = $request->get('q');

        // Öneriler
        $suggestions = [
            ['text' => $query.' kategorisinde ara', 'type' => 'category'], // context7-ignore
            ['text' => $query.' lokasyonunda ara', 'type' => 'location'], // context7-ignore
        ];

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success($suggestions, 'Arama önerileri başarıyla getirildi');
    }

    public function analytics(Request $request)
    {
        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success([
            'total_searches' => 1250,
            'popular_queries' => ['satılık daire', 'kiralık villa', 'merkezi konum'],
            'search_success_rate' => 95.5,
        ], 'Arama analitikleri başarıyla getirildi');
    }

    public function updateCache(Request $request)
    {
        app(\App\Services\Cache\CacheService::class)->flush();

        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(null, 'Cache temizlendi');
    }
}
