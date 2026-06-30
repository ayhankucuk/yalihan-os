<?php

namespace App\Services\Publication;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Models\IlanKategori;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service for handling Public Listing filtering logic.
 */
class PublicListingService
{
    public function __construct(
    ) {}

    /**
     * Get filtered public listings query for index
     */
    public function getFilteredQuery(array $filters): Builder
    {
        $query = Ilan::query()->byYayinDurumu(IlanDurumu::YAYINDA->value);

        // Kategori ID filtresi
        if (!empty($filters['kategori'])) {
            $catId = $filters['kategori'];
            $query->where(function($q) use ($catId) {
                $q->where('ana_kategori_id', $catId)
                  ->orWhere('alt_kategori_id', $catId);
            });
        }

        // Kategori slug filtresi
        if (!empty($filters['kategori_slug'])) {
            $kategoriSlug = $filters['kategori_slug'];
            $anaKategori = IlanKategori::where('slug', $kategoriSlug)
                ->where('seviye', 0)
                ->first();

            if ($anaKategori) {
                $query->where('ana_kategori_id', $anaKategori->id);
            } else {
                $query->whereHas('anaKategori', function ($q) use ($kategoriSlug) {
                    $q->where('slug', $kategoriSlug);
                });
            }
        }

        // İşlem tipi filtresi (Satılık/Kiralık)
        if (!empty($filters['islem_tipi'])) {
            $islemTipi = $filters['islem_tipi'];
            if ($islemTipi === 'satis' || $islemTipi === 'satılık') {
                $query->where(function ($q) {
                    $q->where('baslik', 'like', '%satılık%')
                        ->orWhere('baslik', 'like', '%satilik%')
                        ->orWhere('aciklama', 'like', '%satılık%')
                        ->orWhere('aciklama', 'like', '%satilik%');
                });
            } elseif ($islemTipi === 'kiralama' || $islemTipi === 'kiralik') {
                $query->where(function ($q) {
                    $q->where('baslik', 'like', '%kiralık%')
                        ->orWhere('baslik', 'like', '%kiralik%')
                        ->orWhere('aciklama', 'like', '%kiralık%')
                        ->orWhere('aciklama', 'like', '%kiralik%');
                });
            }
        }

        // Location filter — il → ilce → mahalle cascade (array multi-select)
        if (!empty($filters['il'])) {
            $ils = is_array($filters['il']) ? $filters['il'] : [$filters['il']];
            $ils = array_values(array_filter(array_map('intval', $ils)));
            if (!empty($ils)) {
                $query->whereIn('il_id', $ils);
            }
        }

        if (!empty($filters['ilce'])) {
            $ilceler = is_array($filters['ilce']) ? $filters['ilce'] : [$filters['ilce']];
            $ilceler = array_values(array_filter(array_map('intval', $ilceler)));
            if (!empty($ilceler)) {
                $query->whereIn('ilce_id', $ilceler);
            }
        }

        if (!empty($filters['mahalle'])) {
            $mahalleler = is_array($filters['mahalle']) ? $filters['mahalle'] : [$filters['mahalle']];
            $mahalleler = array_filter(array_map('intval', $mahalleler));
            if (!empty($mahalleler)) {
                $query->whereIn('mahalle_id', $mahalleler);
            }
        }

        // Price range filter
        $query->priceRange(
            !empty($filters['min_fiyat']) ? (float) $filters['min_fiyat'] : null,
            !empty($filters['max_fiyat']) ? (float) $filters['max_fiyat'] : null,
            'fiyat'
        );

        // Arama
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('baslik', 'like', "%{$search}%")
                    ->orWhere('aciklama', 'like', "%{$search}%");
            });
        }

        // İmar durumu filtresi (arsa ilanları için)
        if (!empty($filters['imar_durumu'])) {
            $query->where('imar_durumu', $filters['imar_durumu']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->sort($sortBy, $sortOrder, 'created_at');

        return $query;
    }

    /**
     * Get filtered public listings query for portfolio
     */
    public function getPortfolioQuery(array $filters): Builder
    {
        $query = Ilan::query()->byYayinDurumu(IlanDurumu::YAYINDA->value);

        // Kategori filtresi
        if (!empty($filters['kategori'])) {
            $query->where('ana_kategori_id', $filters['kategori']);
        }

        // İl filtresi (array multi-select)
        if (!empty($filters['il'])) {
            $ils = is_array($filters['il']) ? $filters['il'] : [$filters['il']];
            $ils = array_values(array_filter(array_map('intval', $ils)));
            if (!empty($ils)) {
                $query->whereIn('il_id', $ils);
            }
        }

        // Arama
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('baslik', 'like', "%{$search}%")
                    ->orWhere('aciklama', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
