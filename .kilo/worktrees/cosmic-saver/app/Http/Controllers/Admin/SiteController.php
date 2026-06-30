<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\SiteApartman;
use Illuminate\Http\Request;

class SiteController extends AdminController
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Site endpoint - to be implemented']);
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Site kaydetme isteği alındı',
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => ['id' => (int) $id],
        ]);
    }

    /**
     * Site/Apartman Live Search API
     * Context7: C7-SITE-LIVE-SEARCH-2025-10-17
     */
    public function search(Request $request)
    {
        // Context7: İzolasyon Sistemi - Dual parameter support (search_term OR q)
        $searchTerm = $request->get('q', $request->get('search_term', ''));
        $type = $request->get('type', ''); // site or apartman // context7-ignore

        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
                'results' => [], // Context7: Dual format for compatibility
                'message' => 'En az 2 karakter giriniz',
            ]);
        }

        try {
            $query = SiteApartman::query();

            // Type filter if provided
            if (! empty($type)) {
                $query->where('tip', $type);
            }

            $sites = $query->where('name', 'LIKE', "%{$searchTerm}%") // Context7: name kullan (NOT site_adi)
                ->orWhere('adres', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('il', function ($q) use ($searchTerm) {
                    $q->where('il_adi', 'LIKE', "%{$searchTerm}%"); // Context7: sehir → il
                })
                ->orWhereHas('ilce', function ($q) use ($searchTerm) {
                    $q->where('ilce_adi', 'LIKE', "%{$searchTerm}%");
                })
                ->with(['il', 'ilce', 'mahalle']) // Eager load ilişkiler
                ->limit($request->get('limit', 10))
                ->get()
                ->map(function ($site) {
                    return [
                        'id' => $site->id,
                        'text' => $site->name.' - '.$site->adres.($site->il ? ', '.$site->il->il_adi : ''),
                        'name' => $site->name,
                        'adres' => $site->adres,
                        'il' => $site->il ? $site->il->il_adi : null, // Context7: sehir → il
                        'ilce' => $site->ilce ? $site->ilce->ilce_adi : null,
                        'mahalle' => $site->mahalle ? $site->mahalle->mahalle_adi : null,
                        'toplam_daire_sayisi' => $site->toplam_daire_sayisi ?? 0, // Context7: Frontend expects this key
                        'daire_sayisi' => $site->toplam_daire_sayisi ?? 0, // Keep old key for compatibility
                    ];
                });

            $resultsArray = $sites->values()->toArray();

            return response()->json([
                'success' => true,
                'data' => $resultsArray, // Context7: Standard key
                'results' => $resultsArray, // Context7: Dual format for site-apartman-selection.blade.php
                'count' => $sites->count(),
                'message' => $sites->count().' sonuç bulundu',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Arama sırasında hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }
}
