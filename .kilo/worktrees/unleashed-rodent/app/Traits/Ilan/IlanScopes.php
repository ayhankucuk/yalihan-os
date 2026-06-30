<?php

namespace App\Traits\Ilan;

use App\Enums\IlanDurumu;
use Illuminate\Database\Eloquent\Builder;
use DB;

trait IlanScopes
{
    /**
     * Belirli tarih aralığında uygun olan ilanları getir
     */
    public function scopeAvailable(Builder $query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            // context7-ignore
            $q->whereHas('yazlikFiyatlandirma', function ($subQ) use ($startDate, $endDate) {
                // context7-ignore
                $subQ->where('is_active', true)
                    ->where('baslangic_tarihi', '<=', $endDate)
                    ->where('bitis_tarihi', '>=', $startDate);
            })
                // context7-ignore
                ->whereDoesntHave('yazlikFiyatlandirma', function ($subQ) use ($startDate, $endDate) {
                    // context7-ignore
                    $subQ->where('is_active', false)
                        ->where('baslangic_tarihi', '<=', $endDate)
                        ->where('bitis_tarihi', '>=', $startDate);
                });
        });
    }

    /**
     * Context7: scopeByYayinDurumu yayin_durumu string/int için
     */
    public function scopeByYayinDurumu(Builder $query, $yayinDurumu, string $column = 'yayin_durumu')
    {
        if (is_bool($yayinDurumu)) {
            $yayinDurumu = $yayinDurumu ? 'yayinda' : 'pasif';
        }

        if (is_string($yayinDurumu)) {
            $map = [
                'aktif' => 'yayinda',
                // context7-ignore
                'active' => 'yayinda',
                'yayinda' => 'yayinda',
                'taslak' => 'taslak',
                'draft' => 'taslak',
                'pasif' => 'pasif',
                'beklemede' => 'beklemede',
                'pending' => 'beklemede',
            ];
            $key = strtolower($yayinDurumu);
            $yayinDurumu = $map[$key] ?? $yayinDurumu;
        }

        if (is_numeric($yayinDurumu)) {
            $intValue = (int) $yayinDurumu;
            $map = [
                1 => 'yayinda',
                0 => 'pasif',
                2 => 'taslak',
                3 => 'beklemede',
            ];
            $yayinDurumu = $map[$intValue] ?? 'taslak';
        }

        return $query->where($column, $yayinDurumu);
    }

    public function scopeOrderByDisplayOrder(Builder $query, string $direction = 'asc')
    {
        return $query->orderBy('display_order', $direction); // context7-ignore
    }

    /**
     * Yayında olan ilanları getir
     */
    public function scopeWhereYayinda($query)
    {
        return $query->whereIn('yayin_durumu', ['yayinda', IlanDurumu::YAYINDA->value]);
    }



    /**
     * Onay bekleyen ilanlar
     */
    public function scopePending($query)
    {
        return $query->whereIn('yayin_durumu', ['beklemede', 'onay_bekliyor', 'Beklemede']);
    }

    /**
     * Public ilanlar (CRM-only olmayan, yayında)
     */
    public function scopePublic($query)
    {
        return $query->where('crm_only', false)
            ->whereIn('yayin_durumu', ['yayinda', IlanDurumu::YAYINDA->value]);
    }

    /**
     * Belirli bir kategoriye ait ilanları getiren scope.
     */
    public function scopeKategoriyeGore($query, $kategoriId)
    {
        return $query->where('ana_kategori_id', $kategoriId)
            ->orWhere('alt_kategori_id', $kategoriId);
    }

    /**
     * Ana kategoriye göre filtreleme scope'u
     */
    public function scopeAnaKategoriyeGore($query, $kategoriId)
    {
        return $query->where('ana_kategori_id', $kategoriId);
    }

    /**
     * Alt kategoriye göre filtreleme scope'u
     */
    public function scopeAltKategoriyeGore($query, $kategoriId)
    {
        return $query->where('alt_kategori_id', $kategoriId);
    }

    /**
     * Yayın tipine göre filtreleme scope'u
     */
    public function scopeYayinTipineGore($query, $yayinTipiId)
    {
        return $query->where('yayin_tipi_id', $yayinTipiId);
    }

    /**
     * Sadece fırsat mühru olan ilanları getir
     */
    public function scopeOnlyFirsatlar($query)
    {
        return $query->where('firsat_mühru', true);
    }

    /**
     * Ana ve alt kategoriye göre filtreleme scope'u
     */
    public function scopeKategoriHiyerarsisineGore($query, $anaKategoriId, $altKategoriId = null)
    {
        $query->where('ana_kategori_id', $anaKategoriId);

        if ($altKategoriId) {
            $query->where('alt_kategori_id', $altKategoriId);
        }

        return $query;
    }

    public function scopeSort(
        Builder $query,
        ?string $sortBy = null,
        string $sortDirection = 'desc',
        string $defaultSort = 'created_at'
    )
    {
        $sortBy = $sortBy ?: $defaultSort;
        $dir = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
        $query->reorder();
        if ($sortBy === 'fiyat') {
            /** @sab-ignore-catch */
            try {
                $driver = DB::getDriverName();
            } catch (\Throwable $e) {
                $driver = 'mysql';
            }
            if ($driver === 'sqlite') {
                if ($dir === 'desc') {
                    $query->orderByRaw('(0 + fiyat) DESC'); // context7-ignore
                } else {
                    $query->orderByRaw('(0 + fiyat) ASC'); // context7-ignore
                }
                $query->orderBy($defaultSort, $dir); // context7-ignore
                $query->orderBy('id', $dir); // context7-ignore
            } else {
                if ($dir === 'desc') {
                    $query->orderByRaw('(0 + fiyat) DESC'); // context7-ignore
                } else {
                    $query->orderByRaw('(0 + fiyat) ASC'); // context7-ignore
                }
                $query->orderBy($defaultSort, $dir); // context7-ignore
                $query->orderBy('id', $dir); // context7-ignore
            }

            return $query;
        }
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $sortBy)) {
            return $query->orderBy($sortBy, $dir); // context7-ignore
        }

        return $query->orderByDesc($defaultSort); // context7-ignore
    }

    /**
     * CONTEXT7: Visibility score ve display_order'a göre sırala
     */
    public function scopeRanked($query)
    {
        return $query->orderByDesc('visibility_score') // context7-ignore
            ->orderBy('display_order') // context7-ignore
            ->orderByDesc('id'); // context7-ignore
    }
}
