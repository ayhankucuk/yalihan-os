<?php

namespace App\Services\Admin;

use App\Models\Ilan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * IlanSearchService
 *
 * Context7: C7-ILAN-SEARCH-2025-12-27
 * Filtreleme mantığını merkezileştirir
 */
class IlanSearchService
{
    /**
     * İlan arama ve filtreleme
     *
     * @param Request $request
     * @return Builder
     */
    public function search(Request $request): Builder
    {
        $query = Ilan::with([
            'anaKategori:id,name,slug',
            'altKategori:id,name,slug',
            'fotograflar:id,ilan_id,dosya_yolu,display_order', // Fix: actual column is display_order
            // Context7: Eager load pricing for vacation rentals if dates are present
            'yazlikFiyatlandirma' => function ($q) {
                $q->where('aktiflik_durumu', true);
            },
            // Load relationships for Smart Search
            'ilanSahibi', 'danisman', 'site'
        ]);

        // 1. Yayın Durumu (Context7: Standardized)
        if ($request->filled('yayin_durumu')) {
            $query->byYayinDurumu($request->yayin_durumu);
        } else {
            // If explicit "all" is requested via checking a flag, or simple default
            // For now, if parameter is missing, default to Active for safety,
            // BUT Admin usually wants to see everything?
            // Let's check request context or assume Active default unless specified.
            // Let's check request context or assume Active default unless specified.
            // If request has no filter, usually we show all?
            // In Admin context, we might show all.
            // Let's stick to explicit filter. If not set, NO FILTER on yayin_durumu (Show all).
            // PREVIOUS LOGIC was: query->aktif() if not set?
            // No, previous service logic had: if filled -> filter.
            // ListingSearchController didn't filter yayin_durumu by default.
            // So I will REMOVE the default active filter for broad compatibility.
        }

        // 2. Yayın Tipi (Slug or ID)
        if ($request->filled('yayin_tipi')) {
            $tip = $request->yayin_tipi;
            if (is_numeric($tip)) {
                $query->where('yayin_tipi_id', $tip);
            } else {
                $query->whereHas('yayinTipi', function ($q) use ($tip) {
                    $q->where('slug', $tip);
                });
            }
        }

        // 3. Vacation Logic (Tarih Aralığı)
        if ($request->filled('check_in') && $request->filled('check_out')) {
            $query->available($request->check_in, $request->check_out);
        }

        // 4. SMART SEARCH (Legacy + Keywords)
        if ($request->filled('q') || $request->filled('search')) {
            $q = $request->input('q') ?? $request->input('search');
            $type = $request->input('type', 'all'); // owner, phone, site, advisor, all // context7-ignore

            $query->where(function ($w) use ($q, $type) {
                if ($type === 'owner') {
                    $w->whereHas('ilanSahibi', function ($q2) use ($q) {
                        $q2->where('ad', 'like', "%{$q}%")
                            ->orWhere('soyad', 'like', "%{$q}%");
                    });
                } elseif ($type === 'phone') {
                    $digits = preg_replace('/\D+/', '', $q);
                    $w->whereHas('ilanSahibi', function ($q2) use ($digits) {
                        $q2->where('telefon', 'like', "%{$digits}%");
                    });
                } elseif ($type === 'site') {
                    $w->whereHas('site', function ($q2) use ($q) {
                        $q2->where('name', 'like', "%{$q}%");
                    });
                } elseif ($type === 'advisor') {
                    $w->whereHas('danisman', function ($q2) use ($q) {
                        $q2->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
                } else { // ALL (Smart Fallback)
                    $digits = preg_replace('/\D+/', '', $q);
                    $w->where(function ($fallback) use ($q, $digits) {
                        $fallback->where('baslik', 'LIKE', "%{$q}%")
                            ->orWhere('aciklama', 'LIKE', "%{$q}%")
                            ->orWhere('adres', 'LIKE', "%{$q}%")
                            // Owner Name
                            ->orWhereHas('ilanSahibi', function ($sq) use ($q) {
                               $sq->where('ad', 'like', "%{$q}%")
                                  ->orWhere('soyad', 'like', "%{$q}%");
                            })
                            // Owner Phone
                            ->orWhereHas('ilanSahibi', function ($sq) use ($digits) {
                                $sq->where('telefon', 'like', "%{$digits}%");
                            })
                            // Site Name
                            ->orWhereHas('site', function ($sq) use ($q) {
                                $sq->where('name', 'like', "%{$q}%");
                            })
                            // Advisor
                            ->orWhereHas('danisman', function ($sq) use ($q) {
                                $sq->where('name', 'like', "%{$q}%");
                            });
                    });
                }
            });
        }

        // Kategori
        if ($request->filled('ana_kategori_id')) $query->where('ana_kategori_id', $request->ana_kategori_id);
        if ($request->filled('alt_kategori_id')) $query->where('alt_kategori_id', $request->alt_kategori_id);

        // Lokasyon
        if ($request->filled('il_id')) $query->where('il_id', $request->il_id);
        if ($request->filled('ilce_id')) $query->where('ilce_id', $request->ilce_id);
        if ($request->filled('mahalle_id')) $query->where('mahalle_id', $request->mahalle_id);

        // Fiyat aralığı
        if ($request->filled('min_price')) $query->where('fiyat', '>=', $request->min_price);
        if ($request->filled('max_price')) $query->where('fiyat', '<=', $request->max_price);

        // Sıralama
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->sort($sortBy, $sortOrder);

        return $query;
    }
}
