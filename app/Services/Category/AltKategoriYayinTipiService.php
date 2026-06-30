<?php

namespace App\Services\Category;

use App\Models\AltKategoriYayinTipi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Alt Kategori Yayın Tipi Service
 *
 * Context7 Standardı: C7-ALT-KATEGORI-YAYIN-TIPI-SERVICE-2025-12-06
 *
 * Alt kategori yayın tipi ilişkilerini merkezi olarak yönetir.
 *
 * @package App\Services\Category
 */
class AltKategoriYayinTipiService
{
    /**
     * Alt kategoriler için yayın tipleri getir
     *
     * @param Collection $altKategoriler Alt kategoriler collection
     * @return array [alt_kategori_id => Collection<yayin_tipi_id>]
     */
    public function getYayinTipleriForAltKategoriler(Collection $altKategoriler): array
    {
        $altKategoriYayinTipleri = [];
        $altKategoriIds = $altKategoriler->pluck('id')->toArray();

        if (Schema::hasTable('alt_kategori_yayin_tipi') && !empty($altKategoriIds)) {
            try {
                $altKategoriYayinTipleriRaw = AltKategoriYayinTipi::whereIn('alt_kategori_id', $altKategoriIds)
                    ->active() // context7-ignore
                    ->select(['id', 'alt_kategori_id', 'yayin_tipi_id'])
                    ->get()
                    ->groupBy('alt_kategori_id')
                    ->map(fn($items) => $items->pluck('yayin_tipi_id'));

                foreach ($altKategoriler as $altKat) {
                    $altKategoriYayinTipleri[$altKat->id] = $altKategoriYayinTipleriRaw->get($altKat->id, collect([]));
                }
            } catch (\Exception $e) {
                Log::warning('alt_kategori_yayin_tipi tablosu sorgulanamadı', ['error' => $e->getMessage()]);
                foreach ($altKategoriler as $altKat) {
                    $altKategoriYayinTipleri[$altKat->id] = collect([]);
                }
            }
        } else {
            foreach ($altKategoriler as $altKat) {
                $altKategoriYayinTipleri[$altKat->id] = collect([]);
            }
        }

        return $altKategoriYayinTipleri;
    }
}
