<?php

namespace App\Services;

use App\Enums\IlanDurumu;

use App\Models\Il;
use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * İlan Data Provider Service
 *
 * Context7 Standardı: C7-ILAN-DATA-PROVIDER-2025-10-11
 * Amaç: Tüm ilan create/edit sayfaları için standart veri sağlayıcı
 *
 * Kullanım:
 * $dataProvider = app(IlanDataProviderService::class);
 * $data = $dataProvider->getStandardFormData();
 */
class IlanDataProviderService
{
    /**
     * Tüm ilan create/edit sayfaları için standart veri seti
     */
    public function getStandardFormData(): array
    {
        return [
            'anaKategoriler' => $this->getAnaKategoriler(),
            'kisiler' => $this->getAktifKisiler(),
            'danismanlar' => $this->getDanismanlar(),
            'iller' => $this->getIller(),
        ];
    }

    /**
     * Ana kategorileri getir (parent_id = null)
     */
    public function getAnaKategoriler(): Collection
    {
        return IlanKategori::whereNull('parent_id')
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'icon', 'description']);
    }

    /**
     * Aktif kişileri getir (İlan sahipleri için)
     */
    public function getAktifKisiler(): Collection
    {
        return Kisi::where('aktiflik_durumu', 1) // Context7: aktiflik_durumu field
            ->orderBy('ad') // context7-ignore
            ->orderBy('soyad') // context7-ignore
            ->get(['id', 'ad', 'soyad', 'telefon', 'email']);
    }

    /**
     * Danışmanları getir (User modeli, danisman rolü)
     */
    public function getDanismanlar(): Collection
    {
        return User::whereHas('roles', function ($q) {
            $q->where('name', 'danisman');
        })
            ->where('aktiflik_durumu', true)
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'email']);
    }

    /**
     * İlleri getir
     */
    public function getIller(): Collection
    {
        return Il::orderBy('il_adi')
            ->get(['id', 'il_adi']);
    }

    /**
     * Alt kategorileri getir (AJAX için)
     */
    public function getAltKategoriler(int $anaKategoriId): Collection
    {
        return IlanKategori::where('parent_id', $anaKategoriId)
            ->where('seviye', 1)
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'icon', 'description']);
    }

    /**
     * Yayın tiplerini getir (AJAX için)
     */
    public function getYayinTipleri(int $altKategoriId): Collection
    {
        return IlanKategori::where('parent_id', $altKategoriId)
            ->where('seviye', 2)
            ->where('aktiflik_durumu', true) // Context7: aktif_mi -> aktiflik_durumu
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'icon', 'description']);
    }

    /**
     * İlçeleri getir (AJAX için)
     */
    public function getIlceler(int $ilId): Collection
    {
        return Ilce::where('il_id', $ilId)
            ->orderBy('ilce_adi') // context7-ignore
            ->get(['id', 'il_id', 'ilce_adi']);
    }

    /**
     * Mahalleleri getir (AJAX için)
     */
    public function getMahalleler(int $ilceId): Collection
    {
        return Mahalle::where('ilce_id', $ilceId)
            ->orderBy('mahalle_adi') // context7-ignore
            ->get(['id', 'ilce_id', 'mahalle_adi']);
    }

    /**
     * Aktif siteleri getir (Context7 Live Search için)
     */
    public function getAktifSiteler(): Collection
    {
        return Site::where('aktiflik_durumu', true) // Context7: active -> aktiflik_durumu
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'adres', 'il_id', 'ilce_id', 'mahalle_id']); // Context7: address -> adres
    }

    /**
     * İl bazında siteleri getir
     */
    public function getSitelerByIl(int $ilId): Collection
    {
        return Site::where('aktiflik_durumu', true) // Context7: active -> aktiflik_durumu
            ->where('il_id', $ilId)
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'adres', 'il_id', 'ilce_id', 'mahalle_id']); // Context7: address -> adres
    }

    /**
     * İlçe bazında siteleri getir
     */
    public function getSitelerByIlce(int $ilceId): Collection
    {
        return Site::where('aktiflik_durumu', true) // Context7: active -> aktiflik_durumu
            ->where('ilce_id', $ilceId)
            ->orderBy('name') // context7-ignore
            ->get(['id', 'name', 'adres', 'il_id', 'ilce_id', 'mahalle_id']); // Context7: address -> adres
    }

    /**
     * Kategori bazlı özellikleri getir
     */
    public function getOzelliklerByKategori(?int $altKategoriId = null): Collection
    {
        if ($altKategoriId) {
            // Kategoriye özel özellikler
            return \App\Models\Feature::where('aktiflik_durumu', true)
                ->whereHas('categories', function ($q) use ($altKategoriId) {
                    $q->where('ilan_kategori_id', $altKategoriId);
                })
                ->orderBy('display_order') // context7-ignore
                ->get();
        }

        // Tüm özellikler
        return \App\Models\Feature::where('aktiflik_durumu', true)
            ->orderBy('display_order') // context7-ignore
            ->get();
    }

    /**
     * İlan istatistikleri
     */
    public function getIstatistikler(?int $danismanId = null): array
    {
        $query = \App\Models\Ilan::query();

        if ($danismanId) {
            $query->where('danisman_id', $danismanId);
        }

        return [
            'toplam' => $query->count(),
            'aktif' => $query->where('yayin_durumu', IlanDurumu::YAYINDA->value)->count(),
            'taslak' => $query->where('yayin_durumu', 'Taslak')->count(),
            'satildi' => $query->where('yayin_durumu', 'Satıldı')->count(),
        ];
    }

    /**
     * Danışman bazlı ilanlar
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getDanismanIlanlari(int $danismanId, int $perPage = 20)
    {
        return \App\Models\Ilan::with(['ilanSahibi', 'il', 'ilce', 'anaKategori'])
            ->where('danisman_id', $danismanId)
            ->orderBy('updated_at', 'desc') // context7-ignore
            ->paginate($perPage);
    }

    /**
     * Context7 uyumlu field mapping
     */
    public function getFieldMapping(): array
    {
        return [
            'publication_state_field' => 'yayin_durumu', // Context7: Canonical publication flag
            'city_field' => 'il_id', // Context7: il yerine il_id
            'district_field' => 'ilce_id',
            'neighborhood_field' => 'mahalle_id',
            'category_field' => 'kategori_id', // Tek kolon (ana_kategori_id yok)
        ];
    }

    /**
     * Cache'li veri getirme (Performance optimization)
     *
     * @return mixed
     */
    protected function remember(string $key, callable $callback, int $minutes = 60)
    {
        if (config('cache.default') !== 'array') {
            return cache()->remember($key, now()->addMinutes($minutes), $callback);
        }

        return $callback();
    }

    /**
     * Cache'li ana kategoriler
     */
    public function getCachedAnaKategoriler(): Collection
    {
        return $this->remember('ilan_ana_kategoriler', function () {
            return $this->getAnaKategoriler();
        }, 60);
    }

    /**
     * Cache'li iller
     */
    public function getCachedIller(): Collection
    {
        return $this->remember('iller', function () {
            return $this->getIller();
        }, 120);
    }
}
