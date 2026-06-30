<?php

namespace App\Services\Ilan;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

/**
 * 🔍 ILAN SEARCH SERVICE (SAB v6.0)
 *
 * Handles dynamic listing searches, filtering, and data exports.
 * Adheres to Zero-Trust layer isolation principles.
 */
class IlanSearchService
{
    /**
     * Search listings with dynamic column filtering.
     *
     * @param array $params
     * @return array
     */
    public function search(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $kategoriId = (int) ($params['kategoriId'] ?? 0);
        $yayinTipiId = (int) ($params['yayinTipiId'] ?? 0);
        $yayinDurumu = (string) ($params['yayin_durumu'] ?? '');
        $q = (string) ($params['q'] ?? '');
        $minFiyat = $params['minFiyat'] ?? null;
        $maxFiyat = $params['maxFiyat'] ?? null;
        $sort = (string) ($params['sort'] ?? 'id:desc');
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['perPage'] ?? 25)));

        $builder = DB::table('ilanlar');

        if ($id) {
            $builder->where('id', $id);
        }

        if ($kategoriId && Schema::hasColumn('ilanlar', 'kategori_id')) {
            $builder->where('kategori_id', $kategoriId);
        }

        if ($yayinTipiId && Schema::hasColumn('ilanlar', 'yayin_tipi_id')) {
            $builder->where('yayin_tipi_id', $yayinTipiId);
        }

        if ($yayinDurumu && Schema::hasColumn('ilanlar', 'yayin_durumu')) {
            $builder->where('yayin_durumu', $yayinDurumu);
        }

        if ($q && Schema::hasColumn('ilanlar', 'baslik')) {
            $builder->where('baslik', 'like', '%' . $q . '%');
        }

        if (Schema::hasColumn('ilanlar', 'fiyat')) {
            if ($minFiyat !== null && $minFiyat !== '') {
                $builder->where('fiyat', '>=', (float) $minFiyat);
            }
            if ($maxFiyat !== null && $maxFiyat !== '') {
                $builder->where('fiyat', '<=', (float) $maxFiyat);
            }
        }

        $total = (clone $builder)->count();

        $allowedSelect = ['id', 'baslik', 'fiyat', 'para_birimi', 'yayin_durumu', 'kategori_id', 'yayin_tipi_id', 'created_at'];
        $select = [];
        foreach ($allowedSelect as $c) {
            if (Schema::hasColumn('ilanlar', $c)) {
                $select[] = $c;
            }
        }
        if (!empty($select)) {
            $builder->select($select);
        }

        $allowedSorts = ['id', 'fiyat', 'created_at'];
        $parts = explode(':', $sort);
        $sortCol = $parts[0] ?? 'id';
        $sortDir = strtolower($parts[1] ?? 'desc');
        if (!in_array($sortCol, $allowedSorts, true)) {
            $sortCol = 'id';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $items = $builder->orderBy($sortCol, $sortDir)->forPage($page, $perPage)->get(); // context7-ignore

        return [
            'data' => $items,
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $perPage ? (int) ceil($total / $perPage) : 1,
            ],
        ];
    }

    /**
     * Export listings as raw data for CSV.
     *
     * @param array $filters
     * @param int $limit
     * @return Collection
     */
    public function exportData(array $filters, int $limit = 10000): Collection
    {
        $kategoriId = (int) ($filters['kategoriId'] ?? 0);
        $yayinTipiId = (int) ($filters['yayinTipiId'] ?? 0);

        $q = DB::table('ilanlar');

        if ($kategoriId && Schema::hasColumn('ilanlar', 'kategori_id')) {
            $q->where('kategori_id', $kategoriId);
        }
        if ($yayinTipiId && Schema::hasColumn('ilanlar', 'yayin_tipi_id')) {
            $q->where('yayin_tipi_id', $yayinTipiId);
        }

        $cols = [];
        foreach (['id', 'baslik', 'fiyat', 'para_birimi', 'yayin_durumu', 'kategori_id', 'yayin_tipi_id', 'created_at'] as $c) {
            if (Schema::hasColumn('ilanlar', $c)) {
                $cols[] = $c;
            }
        }
        if (!empty($cols)) {
            $q->select($cols);
        }

        return $q->limit($limit)->get();
    }

    /**
     * Get top viewed listings for a period.
     *
     * @param string $startDate
     * @param int $limit
     * @return Collection
     */
    public function getTopViewedListings(string $startDate, int $limit = 10): Collection
    {
        return DB::table('ilan_goruntulenme_gunluk')
            ->join(
                'ilanlar',
                'ilan_goruntulenme_gunluk.ilan_id',
                '=',
                'ilanlar.id'
            )
            ->where(
                'ilan_goruntulenme_gunluk.tarih',
                '>=',
                $startDate
            )
            ->selectRaw(
                'ilan_goruntulenme_gunluk.ilan_id, ' .
                'SUM(ilan_goruntulenme_gunluk.adet) as views, ' .
                'ilanlar.baslik, ilanlar.fiyat, ilanlar.para_birimi, ilanlar.slug'
            )
            ->groupBy(
                'ilan_goruntulenme_gunluk.ilan_id',
                'ilanlar.baslik',
                'ilanlar.fiyat',
                'ilanlar.para_birimi',
                'ilanlar.slug'
            )
            ->orderByDesc('views') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Base query for consultant listings (Common for search, index, export)
     *
     * @param int $consultantId
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseMyListingsQuery(int $consultantId, array $params)
    {
        $sortBy = (string) ($params['sort_by'] ?? 'updated_at');
        $sortOrder = (string) ($params['sort_order'] ?? 'desc');
        $status = $params['yayin_durumu'] ?? $params['aktiflik_durumu'] ?? null;
        $categoryId = $params['category'] ?? null;
        $searchTerm = $params['search'] ?? null;

        $query = \App\Models\Ilan::where('danisman_id', $consultantId);

        // Filter: Status
        if ($status) {
            $query->where('yayin_durumu', $status);
        }

        // Filter: Category
        if ($categoryId) {
            $query->where('alt_kategori_id', $categoryId);
        }

        // Filter: Search (Reference number search)
        if ($searchTerm) {
            $referansService = app(\App\Services\IlanReferansService::class);
            $searchSubquery = $referansService->searchQuery($searchTerm)
                ->where('danisman_id', $consultantId);

            $query->whereIn('id', $searchSubquery->select('id'));
        }

        // Sorting (Enforce allowed columns)
        $allowedSorts = ['id', 'fiyat', 'created_at', 'updated_at', 'goruntulenme'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'updated_at';
        }
        $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');

        return $query;
    }

    /**
     * Consultant specific listings search (P1 Query Authority Lock)
     *
     * @param int $consultantId
     * @param array $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function searchMyListings(int $consultantId, array $params)
    {
        $perPage = min(100, max(1, (int) ($params['perPage'] ?? 20)));
        
        return $this->baseMyListingsQuery($consultantId, $params)
            ->select([
                'id', 'baslik', 'fiyat', 'para_birimi', 'yayin_durumu', 
                'goruntulenme', 'alt_kategori_id', 'ana_kategori_id', 
                'il_id', 'ilce_id', 'referans_no', 'dosya_adi', 'created_at', 'updated_at',
            ])
            ->with([
                'altKategori:id,name,icon',
                'anaKategori:id,name',
                'il:id,il_adi',
                'ilce:id,ilce_adi',
                'fotograflar' => function ($q) {
                    $q->select('id', 'ilan_id', 'dosya_yolu', 'display_order')
                        ->orderBy('display_order')
                        ->limit(1);
                },
            ])
            ->paginate($perPage);
    }

    /**
     * Get all consultant listings for export (P1 Query Authority Lock)
     *
     * @param int $consultantId
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllMyListingsForExport(int $consultantId, array $params)
    {
        return $this->baseMyListingsQuery($consultantId, $params)
            ->select([
                'id', 'baslik', 'fiyat', 'para_birimi', 'yayin_durumu', 
                'goruntulenme', 'alt_kategori_id', 'ana_kategori_id', 
                'il_id', 'ilce_id', 'referans_no', 'created_at', 'updated_at',
            ])
            ->with([
                'altKategori:id,name',
                'anaKategori:id,name',
                'il:id,il_adi',
                'ilce:id,ilce_adi',
            ])
            ->get();
    }
}

