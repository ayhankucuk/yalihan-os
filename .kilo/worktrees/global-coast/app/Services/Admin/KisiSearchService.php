<?php

namespace App\Services\Admin;

use App\Models\Kisi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * KisiSearchService
 * 
 * Context7: C7-KISI-SEARCH-2025-12-27
 * Kişi arama ve filtreleme operasyonları
 */
class KisiSearchService
{
    /**
     * Kişi arama ve filtreleme
     * 
     * @param Request $request
     * @return Builder
     */
    public function search(Request $request): Builder
    {
        $query = Kisi::query();

        // Search by name, phone, email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ad', 'like', "%{$search}%")
                    ->orWhere('soyad', 'like', "%{$search}%")
                    ->orWhere('telefon', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by type (Sahip/Kiracı)
        if ($request->filled('tip')) {
            $query->where('tip', $request->tip);
        }

        // Filter by active status (Context7: aktiflik_durumu)
        if ($request->filled('aktif')) {
            $query->where('aktiflik_durumu', (bool) $request->aktif);
        }

        // Default ordering
        $query->orderBy('created_at', 'desc'); // context7-ignore

        return $query;
    }
}
