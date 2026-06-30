<?php

namespace App\Http\Controllers\Admin\Traits;

/**
 * @sab-ignore-thin
 */

use App\Models\FeatureCategory;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Category\AltKategoriYayinTipiService;
use App\Services\Category\FeatureCategoryService;
use App\Services\Category\FieldDependencyService;
use App\Services\Logging\LogService;
use App\Services\Schema\SchemaHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared helper methods for Property Type controllers
 *
 * Context7: Ortak helper methodlar 3 controller tarafından kullanılır
 * - PropertyTypeController
 * - FieldDependencyController
 * - FeatureAssignmentController
 */
trait ManagesPropertyTypes
{
    /**
     * ✅ UPS Phase 1: Return ALL active feature categories
     * No hard-coded usage-based filtering
     */
    protected function allowedFeatureCategoryNames(string $slug): array
    {
        // ✅ SAB: Category-based White List matching
        $mapping = [
            'konut' => [
                'Genel Özellikler', 'Konut Özellikleri', 'Bina Özellikleri',
                'Site Özellikleri', 'İç Özellikler', 'Dış Özellikler',
                'Banyo', 'Mutfak', 'Otopark', 'Güvenlik', 'Muhit', 'Ulaşım', 'Altyapı'
            ],
            'arsa' => [
                'Genel Özellikler', 'Arsa Özellikleri', 'Tapu & Hukuki',
                'Altyapı', 'Muhit', 'Ulaşım'
            ],
            'isyeri' => [
                'Genel Özellikler', 'İşyeri Özellikleri', 'Bina Özellikleri',
                'İç Özellikler', 'Dış Özellikler', 'Güvenlik', 'Altyapı', 'Muhit'
            ],
            'yazlik-kiralama' => [
                'Genel Özellikler', 'Konut Özellikleri', 'Konaklama ve Tarih Bilgileri',
                'İç Özellikler', 'Dış Özellikler', 'Banyo', 'Mutfak', 'Havuz Özellikleri'
            ],
            'proje' => [
                'Genel Özellikler', 'Proje Tipi', 'İnşaat Teknikleri',
                'Site Özellikleri', 'Bina Özellikleri'
            ]
        ];

        // Find the most appropriate mapping (partial match for nested categories)
        $allowed = null;
        foreach ($mapping as $key => $categories) {
            if (str_contains($slug, $key)) {
                $allowed = $categories;
                break;
            }
        }

        // Default: If no mapping found, return all active categories to prevent total breakdown
        if (!$allowed) {
            return FeatureCategory::where('aktiflik_durumu', true)
                ->pluck('name')
                ->toArray();
        }

        return $allowed;
    }

    /**
     * Ensure default publication types exist for category
     */
    protected function ensureDefaultYayinTipleri(int $kategoriId): void
    {
        // V2 Global Template sisteminde yayın tipleri sabittir.
        return;
    }

    /**
     * Kategori bul veya 404 döndür
     */
    protected function findKategoriOrFail(int $kategoriId): IlanKategori
    {
        $kategori = IlanKategori::find($kategoriId);
        if (!$kategori) {
            abort(404);
        }
        return $kategori;
    }

    /**
     * Ana kategori değilse yönlendir
     */
    protected function redirectIfNotMainCategory(IlanKategori $kategori): ?\Illuminate\Http\RedirectResponse
    {
        if ($kategori->seviye !== 0 && $kategori->parent_id) {
            $anaKategori = IlanKategori::find($kategori->parent_id);
            if ($anaKategori && $anaKategori->seviye === 0) {
                return redirect()->route('admin.property_types.show', $anaKategori->id)
                    ->with('info', 'Ana kategori sayfasına yönlendirildiniz.');
            }
        }
        return null;
    }

    /**
     * Alt kategorileri yükle
     *
     * ⚠️ UPDATED (2026-01-04): children() eager loading removed
     * Seviye=2 categories migrated to yayin_tipi_sablonlari table
     */
    protected function loadAltKategoriler(int $kategoriId): Collection
    {
        $query = IlanKategori::query()
            ->where('parent_id', $kategoriId)
            ->where('seviye', 1) // ✅ Alt kategoriler seviye=1
            ->where('seviye', '!=', 2); // ✅ Yayın tipleri (seviye=2) ASLA alt kategori olarak listelenmemeli

        SchemaHelper::applyStatusFilter($query, 'ilan_kategorileri');

        $baseColumns = ['id', 'name', 'slug', 'parent_id', 'seviye', 'aktiflik_durumu'];
        $selectColumns = SchemaHelper::getSelectColumns('ilan_kategorileri', $baseColumns);

        // ⚠️ REMOVED (2026-01-04): children() eager loading
        // Seviye=2 categories migrated to flat yayin_tipleri table
        return $query->select($selectColumns)
            ->when(SchemaHelper::hasDisplayOrderColumn('ilan_kategorileri'), function ($q) {
                $q->orderByRaw('COALESCE(display_order, 999999) ASC'); // context7-ignore
            })
            ->orderBy('name', 'ASC') // context7-ignore
            ->get();
    }

    /**
     * Yayın tiplerini yükle
     */
    protected function loadYayinTipleri(int $kategoriId): Collection
    {
        $policy = app(\App\Services\Ups\PropertyPublicationPolicy::class);
        $allowedIds = $policy->allowedForCategory($kategoriId);

        return YayinTipiSablonu::whereIn('id', $allowedIds)
            ->where('aktiflik_durumu', true)
            ->orderBy('display_order', 'ASC') // context7-ignore
            ->orderBy('ad', 'ASC') // context7-ignore
            ->get();
    }

    /**
     * Alt kategori yayın tipi ilişkilerini yükle
     */
    protected function loadAltKategoriYayinTipleri(Collection $altKategoriler): array
    {
        return app(AltKategoriYayinTipiService::class)
            ->getYayinTipleriForAltKategoriler($altKategoriler);
    }

    /**
     * Field dependencies yükle (As Array - Legacy View Support)
     */
    protected function loadFieldDependencies(IlanKategori $kategori): array
    {
        return app(FieldDependencyService::class)
            ->getFieldDependenciesForCategory($kategori->slug, $kategori->id);
    }

    /**
     * Field dependencies yükle (As Collection - Raw Management Support)
     */
    protected function loadFieldDependenciesCollection(IlanKategori $kategori): Collection
    {
        return app(FieldDependencyService::class)
            ->getRawFieldDependenciesForCategory($kategori->slug);
    }

    /**
     * Feature categories yükle
     */
    protected function loadFeatureCategories(string $kategoriSlug): Collection
    {
        $allowed = $this->allowedFeatureCategoryNames($kategoriSlug);
        return app(FeatureCategoryService::class)
            ->getCategoriesForKategori($kategoriSlug, $allowed);
    }

    /**
     * Yanlış eklenen yayın tiplerini yükle
     */
    protected function loadYanlisEklenenYayinTipleri(int $kategoriId): Collection
    {
        $query = IlanKategori::query()
            ->where('parent_id', $kategoriId)
            ->where('seviye', 1);

        SchemaHelper::applyStatusFilter($query, 'ilan_kategorileri');

        return $query->select(['id', 'name', 'slug', 'parent_id', 'seviye', 'aktiflik_durumu'])
            ->whereIn('name', ['Satılık', 'Kiralık', 'Kat Karşılığı', 'Günlük', 'Haftalık', 'Aylık'])
            ->whereNotIn('name', ['Günlük Kiralama', 'Haftalık Kiralama', 'Aylık Kiralama'])
            ->get();
    }

    /**
     * Show error handler
     */
    protected function handleShowError(\Throwable $e, int $kategoriId)
    {
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            throw $e;
        }
        LogService::error('PropertyTypeManager show error', [
            'event' => 'property_type_manager_show_error',
            'kategori_id' => $kategoriId,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        abort(500);
    }
}
