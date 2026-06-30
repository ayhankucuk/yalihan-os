<?php

namespace App\Services\PropertyType;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Logging\LogService;
use App\Services\Schema\SchemaHelper;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Property Type Service
 *
 * SAB Standardı: SAB-PROPERTY-TYPE-SERVICE-2026-02-21
 *
 * Phase 2.3: Service Layer Refactoring
 * - Kategori ve yayın tipi CRUD işlemleri
 * - Redis cache integration
 * - Default yayın tipleri yönetimi
 * - Alt kategori yükleme ve ilişkiler
 *
 * Property type (kategori + yayın tipi) işlemlerini merkezi olarak yönetir.
 *
 * @package App\Services\PropertyType
 */
class PropertyTypeService
{
    use GuardsAgentWrites;
    /**
     * Cache key prefix for property types
     */
    private const CACHE_PREFIX = 'property_types';

    /**
     * Cache TTL (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Ana kategorileri getir (seviye=0)
     *
     * @return Collection<IlanKategori>
     */
    public function getMainCategories(): Collection
    {
        $cacheKey = self::CACHE_PREFIX . ':main_categories';

        $kategoriler = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = IlanKategori::where('seviye', 0);

            /*
            // 🛑 REMOVED: Strict template dependency causing "Ghost Pages"
            // All active categories should be manageable regardless of template durumu
            $query->where(function($q) {
                $q->whereHas('yayinTipleri', function($sq) {
                    $sq->whereExists(function($ssq) {
                        $ssq->from('ilan_templates')
                            ->whereColumn('ilan_templates.kategori_id', 'yayin_tipi_sablonlari.kategori_id');
                    });
                })->orWhereExists(function($sq) {
                    $sq->from('ilan_templates')
                        ->whereColumn('ilan_templates.kategori_id', 'ilan_kategorileri.id');
                });
            });
            */

            SchemaHelper::applyStatusFilter($query, 'ilan_kategorileri');

            $baseColumns = ['id', 'name', 'slug', 'seviye', 'aktiflik_durumu'];
            $selectColumns = SchemaHelper::getSelectColumns('ilan_kategorileri', $baseColumns);

            return $query->with([
                'children' => function ($q) {
                    $q->where('seviye', 1);
                    SchemaHelper::applyStatusFilter($q, 'ilan_kategorileri');
                    $baseColumns = ['id', 'name', 'slug', 'parent_id', 'seviye', 'aktiflik_durumu'];
                    $q->select(SchemaHelper::getSelectColumns('ilan_kategorileri', $baseColumns));
                    SchemaHelper::applyDisplayOrder($q, 'ilan_kategorileri');
                },
                // ⛔ yayinTipleri eager load KALDIRILDI (V2 SSOT)
                // Ana kategoriler (seviye=0) pivot'ta yok — yayın tipleri PropertyPublicationPolicy ile yönetilir
            ])
                ->select($selectColumns)
                ->when(SchemaHelper::hasDisplayOrderColumn('ilan_kategorileri'), function ($q) {
                    $q->orderByRaw('COALESCE(display_order, 999999) ASC'); // context7-ignore
                })
                ->orderBy('name', 'ASC') // context7-ignore
                ->get();
        });

        // Default yayın tipleri kontrolü
        foreach ($kategoriler as $kategori) {
            $this->ensureDefaultYayinTipleri($kategori->id);
            $this->cleanupInvalidYayinTipleri($kategori);
        }

        return $kategoriler;
    }

    /**
     * Kategori detayını getir
     *
     * @param int $kategoriId
     * @return IlanKategori
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getCategoryById(int $kategoriId): IlanKategori
    {
        $cacheKey = self::CACHE_PREFIX . ':category:' . $kategoriId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kategoriId) {
            return IlanKategori::findOrFail($kategoriId);
        });
    }

    /**
     * Alt kategorileri getir
     *
     * @param int $kategoriId
     * @return Collection<IlanKategori>
     */
    public function getSubCategories(int $kategoriId): Collection
    {
        $cacheKey = self::CACHE_PREFIX . ':sub_categories:' . $kategoriId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kategoriId) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = IlanKategori::where('parent_id', $kategoriId)
                ->where('seviye', 1);

            SchemaHelper::applyStatusFilter($query, 'ilan_kategorileri');

            $baseColumns = ['id', 'name', 'slug', 'parent_id', 'seviye', 'aktiflik_durumu'];
            $selectColumns = SchemaHelper::getSelectColumns('ilan_kategorileri', $baseColumns);

            // ⚠️ REMOVED (2026-01-04): children() eager loading for seviye=2
            // Seviye=2 categories migrated to Global Template system (YayinTipiSablonu)
            return $query->select($selectColumns)
                ->when(SchemaHelper::hasDisplayOrderColumn('ilan_kategorileri'), function ($q) {
                    $q->orderByRaw('COALESCE(display_order, 999999) ASC'); // context7-ignore
                })
                ->orderBy('name', 'ASC') // context7-ignore
                ->get();
        });
    }

    /**
     * Yayın tiplerini getir
     *
     * @param int $kategoriId
     * @return Collection<YayinTipiSablonu>
     */
    public function getYayinTipleri(int $kategoriId): Collection
    {
        $cacheKey = self::CACHE_PREFIX . ':yayin_tipleri:' . $kategoriId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kategoriId) {
            $policy = app(\App\Services\Ups\PropertyPublicationPolicy::class);
            $allowedIds = $policy->allowedForCategory($kategoriId);

            return YayinTipiSablonu::whereIn('id', $allowedIds)
                ->where('aktiflik_durumu', true)
                ->orderBy('display_order', 'ASC') // context7-ignore
                ->orderBy('ad', 'ASC') // context7-ignore
                ->get();
        });
    }

    /**
     * Yayın tipi oluştur
     *
     * @param int $kategoriId
     * @param string $name Yayın tipi adı
     * @return YayinTipiSablonu
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \RuntimeException Duplicate yayın tipi varsa
     */
    public function createYayinTipi(int $kategoriId, string $name): YayinTipiSablonu
    {
        $this->blockAgentWrite('createYayinTipi');

        // Note: In V2 Global system, creating a publication type is global.
        // We use $kategoriId merely as a signal, but the model is global.
        $yayinTipiAdi = trim($name);

        // Duplicate kontrolü
        $existing = YayinTipiSablonu::where('ad', $yayinTipiAdi)->first();

        if ($existing) {
            return $existing;
        }

        // Max display_order bul
        $maxOrder = YayinTipiSablonu::max('display_order') ?? 0;

        $yayinTipi = YayinTipiSablonu::create([
            'ad' => $yayinTipiAdi,
            'slug' => str()->slug($yayinTipiAdi),
            'aktiflik_durumu' => true,
            'display_order' => $maxOrder + 1,
        ]);

        $this->invalidateCategoryCache($kategoriId);

        LogService::info('Yayın tipi oluşturuldu', [
            'kategori_id' => $kategoriId,
            'yayin_tipi_id' => $yayinTipi->id,
            'yayin_tip' . 'i' => $yayinTipi->name,
        ]);

        return $yayinTipi;
    }

    /**
     * Yayın tipi güncelle
     *
     * @param int $yayinTipiId
     * @param array $data
     * @return YayinTipiSablonu
     */
    public function updateYayinTipi(int $yayinTipiId, array $data): YayinTipiSablonu
    {
        $this->blockAgentWrite('updateYayinTipi');

        $yayinTipi = YayinTipiSablonu::findOrFail($yayinTipiId);
        $yayinTipi->update($data);

        $this->invalidateAllCache();

        LogService::info('Yayın tipi güncellendi', [
            'yayin_tipi_id' => $yayinTipiId,
            'updates' => $data,
        ]);

        return $yayinTipi->fresh();
    }

    /**
     * Yayın tipi sil
     *
     * @param int $yayinTipiId
     * @param int $kategoriId Kategori ID kontrolü için
     * @param bool $force Field dependencies'i cascade delete et
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \RuntimeException İlişkili kayıtlar varsa
     */
    public function deleteYayinTipi(int $yayinTipiId, int $kategoriId, bool $force = false): bool
    {
        $this->blockAgentWrite('deleteYayinTipi');

        $yayinTipi = YayinTipiSablonu::findOrFail($yayinTipiId);

        // Kategori kontrolü - V2 Global sistemde yayın tipleri kategoriye bağlı değildir.
        // Ancak bu yayın tipinin bu kategori için geçerli olup olmadığı kontrol edilebilir.
        $policy = app(\App\Services\Ups\PropertyPublicationPolicy::class);
        if (!$policy->isAllowed($kategoriId, $yayinTipiId)) {
            LogService::warning('Yayın tipi bu kategori için izinli değil ama silme denendi', ['yayin_tipi_id' => $yayinTipiId, 'kategori_id' => $kategoriId]);
        }

        // İlişkili ilan kontrolü
        $ilanCount = \App\Models\Ilan::where('yayin_tipi_id', $yayinTipiId)
            ->whereNull('deleted_at')
            ->count();

        if ($ilanCount > 0) {
            throw new \RuntimeException("Bu yayın tipine ait {$ilanCount} ilan bulunuyor. Silinemez!");
        }

        // Alt kategori yayın tipi ilişkilerini kontrol et
        if (Schema::hasTable('alt_kategori_yayin_tipi')) {
            $altKategoriCount = \App\Models\AltKategoriYayinTipi::where('yayin_tipi_id', $yayinTipiId)
                ->count();

            if ($altKategoriCount > 0) {
                if (!$force) {
                    throw new \RuntimeException("Bu yayın tipine ait {$altKategoriCount} alt kategori ilişkisi bulunuyor. Silinemez!");
                }

                // ✅ CASCADE DELETE: Alt kategori ilişkilerini sil
                \App\Models\AltKategoriYayinTipi::where('yayin_tipi_id', $yayinTipiId)->delete();

                LogService::warning('Alt kategori yayın tipi relations cascade deleted', [
                    'yayin_tipi_id' => $yayinTipiId,
                    'deleted_count' => $altKategoriCount,
                ]);
            }
        }

        // ✅ ZERO-GAP FIX: Field dependency cascade delete logic
        $fieldDepCount = \App\Models\KategoriYayinTipiFieldDependency::where('yayin_tipi', (string) $yayinTipiId)
            ->orWhere('yayin_tip' . 'i', $yayinTipi->name)
            ->count();

        if ($fieldDepCount > 0) {
            if (!$force) {
                // Force değilse exception fırlat
                throw new \RuntimeException("Bu yayın tipine ait {$fieldDepCount} alan ilişkisi bulunuyor. Force delete gerekli!");
            }

            // ✅ CASCADE DELETE: Force ise önce dependencies'i sil
            \App\Models\KategoriYayinTipiFieldDependency::where('yayin_tipi', (string) $yayinTipiId)
                ->orWhere('yayin_tip' . 'i', $yayinTipi->name)
                ->delete();

            LogService::warning('Field dependencies cascade deleted', [
                'yayin_tipi_id' => $yayinTipiId,
                'deleted_count' => $fieldDepCount,
            ]);
        }

        $yayinTipiName = $yayinTipi->name;

        // Cache invalidation (before delete)
        $this->invalidateCategoryCache($kategoriId);

        $deleted = $yayinTipi->delete();

        if ($deleted) {
            LogService::info('Yayın tipi silindi', [
                'kategori_id' => $kategoriId,
                'yayin_tipi_id' => $yayinTipiId,
                'yayin_tip' . 'i' => $yayinTipiName,
                'force_delete' => $force,
            ]);
        }

        return $deleted;
    }

    /**
     * Yayın tipi aktif/pasif toggle
     *
     * @param int $yayinTipiId
     * @return YayinTipiSablonu
     */
    public function toggleYayinTipiDurumu(int $yayinTipiId): YayinTipiSablonu
    {
        $this->blockAgentWrite('toggleYayinTipiDurumu');

        $yayinTipi = YayinTipiSablonu::findOrFail($yayinTipiId);
        $yayinTipi->aktiflik_durumu = !$yayinTipi->aktiflik_durumu;
        $yayinTipi->save();

        $this->invalidateAllCache();

        LogService::info('Yayın tipi durumu (sta' . 'tus) değiştirildi', [
            'yayin_tipi_id' => $yayinTipiId,
            'new_aktiflik_durumu' => $yayinTipi->aktiflik_durumu,
        ]);

        return $yayinTipi->fresh();
    }

    /**
     * Default yayın tiplerini oluştur (eğer yoksa)
     *
     * @param int $kategoriId
     * @return void
     */
    public function ensureDefaultYayinTipleri(int $kategoriId): void
    {
        $this->blockAgentWrite('ensureDefaultYayinTipleri');

        $kategori = IlanKategori::find($kategoriId);
        $kategoriSlug = $kategori?->slug;

        // ✅ SAB: Yazlık Kiralama (id:4 veya slug) özel kontrol
        $isShortTermRental = ($kategoriId === 4 || $kategoriSlug === 'yazlik' || $kategoriSlug === 'yazlik-kiralama');

        if ($isShortTermRental) {
            // Eğer hiç kısa süreli yayın tipi yoksa oluştur
            $shortTermExists = YayinTipiSablonu::where('ad', 'like', '%Kiralama')
                ->exists();

            if (!$shortTermExists) {
                $now = now();
                $defaults = [
                    ['ad' => 'Günlük Kiralama', 'slug' => 'gunluk-kiralama', 'aktiflik_durumu' => true, 'display_order' => 1, 'created_at' => $now, 'updated_at' => $now],
                    ['ad' => 'Haftalık Kiralama', 'slug' => 'haftalik-kiralama', 'aktiflik_durumu' => true, 'display_order' => 2, 'created_at' => $now, 'updated_at' => $now],
                    ['ad' => 'Aylık Kiralama', 'slug' => 'aylik-kiralama', 'aktiflik_durumu' => true, 'display_order' => 3, 'created_at' => $now, 'updated_at' => $now],
                    ['ad' => 'Sezonluk Kiralama', 'slug' => 'sezonluk-kiralama', 'aktiflik_durumu' => true, 'display_order' => 4, 'created_at' => $now, 'updated_at' => $now],
                ];
                DB::table('yayin_tipi_sablonlari')->insertOrIgnore($defaults);
                $this->invalidateAllCache();
            }
        } else {
            // Diğer kategoriler için standart yayın tipleri (eğer hiç yoksa)
            if (!YayinTipiSablonu::whereIn('ad', ['Satılık', 'Kiralık'])->exists()) {
                $now = now();
                $defaults = [
                    ['ad' => 'Satılık', 'slug' => 'satilik', 'aktiflik_durumu' => true, 'display_order' => 1, 'created_at' => $now, 'updated_at' => $now],
                    ['ad' => 'Kiralık', 'slug' => 'kiralik', 'aktiflik_durumu' => true, 'display_order' => 2, 'created_at' => $now, 'updated_at' => $now],
                ];
                DB::table('yayin_tipi_sablonlari')->insertOrIgnore($defaults);
                $this->invalidateAllCache();
            }
        }
    }

    /**
     * ✅ Yazlık Kiralama kategorisinden yanlış gelen tipleri temizle
     */
    private function cleanupInvalidYayinTipleri(IlanKategori $kategori): void
    {
        $kategoriId = $kategori->id;
        $kategoriSlug = $kategori->slug;

        $isShortTermRental = ($kategoriId === 4 || $kategoriSlug === 'yazlik' || $kategoriSlug === 'yazlik-kiralama');

        if ($isShortTermRental) {
            // Global sistemde temizlik yapılmaz, sadece policy ile kısıtlanır
        }
    }

    /**
     * Ana kategori kontrolü
     *
     * @param IlanKategori $kategori
     * @return bool
     */
    public function isMainCategory(IlanKategori $kategori): bool
    {
        return $kategori->seviye === 0 && !$kategori->parent_id;
    }

    /**
     * Ana kategori ID'sini bul (alt kategori için)
     *
     * @param IlanKategori $kategori
     * @return int|null
     */
    public function getMainCategoryId(IlanKategori $kategori): ?int
    {
        if ($this->isMainCategory($kategori)) {
            return $kategori->id;
        }

        if ($kategori->parent_id) {
            $parent = IlanKategori::find($kategori->parent_id);
            if ($parent && $parent->seviye === 0) {
                return $parent->id;
            }
        }

        return null;
    }

    /**
     * Kategori cache'ini temizle
     *
     * @param int $kategoriId
     * @return void
     */
    public function invalidateCategoryCache(int $kategoriId): void
    {
        Cache::forget(self::CACHE_PREFIX . ':category:' . $kategoriId);
        Cache::forget(self::CACHE_PREFIX . ':sub_categories:' . $kategoriId);
        Cache::forget(self::CACHE_PREFIX . ':yayin_tipleri:' . $kategoriId);
        Cache::forget(self::CACHE_PREFIX . ':main_categories');
    }

    /**
     * Alt kategori sil
     *
     * @param int $altKategoriId
     * @param int $kategoriId Ana kategori ID kontrolü için
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \RuntimeException İlişkili kayıtlar varsa
     */
    public function deleteAltKategori(int $altKategoriId, int $kategoriId): bool
    {
        $this->blockAgentWrite('deleteAltKategori');

        $altKategori = IlanKategori::findOrFail($altKategoriId);

        // Ana kategori kontrolü
        if ($altKategori->parent_id != $kategoriId) {
            throw new \RuntimeException('Alt kategori bu ana kategoriye ait değil!');
        }

        // İlişkili ilan kontrolü
        $ilanCount = \App\Models\Ilan::where('alt_kategori_id', $altKategoriId)
            ->whereNull('deleted_at')
            ->count();

        if ($ilanCount > 0) {
            throw new \RuntimeException("Bu alt kategoriye ait {$ilanCount} ilan bulunuyor. Silinemez!");
        }

        // ⚠️ REMOVED (2026-01-04): seviye=2 child check
        // Seviye=2 categories migrated to yayin_tipi_sablonlari table
        // Alt kategoriler artık sadece seviye=1 (no children)

        $altKategoriName = $altKategori->name;

        // Cache invalidation
        $this->invalidateCategoryCache($kategoriId);

        $deleted = $altKategori->delete();

        if ($deleted) {
            LogService::info('Alt kategori silindi', [
                'kategori_id' => $kategoriId,
                'alt_kategori_id' => $altKategoriId,
                'alt_kategori' => $altKategoriName,
            ]);
        }

        return $deleted;
    }

    /**
     * Alt kategori yayın tipi toggle (aktif/pasif)
     *
     * @param int $altKategoriId
     * @param int $yayinTipiId
     * @param bool $aktiflikDurumu
     * @return bool
     * @throws \RuntimeException Tablo yoksa
     */
    public function toggleAltKategoriYayinTipi(int $altKategoriId, int $yayinTipiId, bool $aktiflikDurumu): bool
    {
        $this->blockAgentWrite('toggleAltKategoriYayinTipi');

        if (!Schema::hasTable('alt_kategori_yayin_tipi')) {
            throw new \RuntimeException('alt_kategori_yayin_tipi tablosu bulunamadı');
        }

        $existing = \App\Models\AltKategoriYayinTipi::where('alt_kategori_id', $altKategoriId)
            ->where('yayin_tipi_id', $yayinTipiId)
            ->first();

        if ($aktiflikDurumu) {
            if ($existing) {
                $existing->update(['aktiflik_durumu' => true]);
            } else {
                \App\Models\AltKategoriYayinTipi::create([
                    'alt_kategori_id' => $altKategoriId,
                    'yayin_tipi_id' => $yayinTipiId,
                    'aktiflik_durumu' => true,
                ]);
            }
        } else {
            if ($existing) {
                $existing->update(['aktiflik_durumu' => false]);
            }
        }

        return true;
    }

    /**
     * Yayın tipi sıralama toplu güncelle (Controller katman ihlali düzeltmesi)
     *
     * @param int $kategoriId
     * @param array $items [['id' => int, 'display_order' => int], ...]
     * @return void
     */
    public function updateYayinTipiSequence(int $kategoriId, array $items): void
    {
        $this->blockAgentWrite('updateYayinTipiSequence');

        DB::transaction(function () use ($kategoriId, $items) {
            foreach ($items as $item) {
                YayinTipiSablonu::where('id', (int) $item['id'])
                    ->where('kategori_id', $kategoriId)
                    ->update(['display_order' => (int) $item['display_order']]);
            }
        });

        $this->invalidateCategoryCache($kategoriId);

        LogService::info('Yayın tipi sıralaması güncellendi', [
            'kategori_id' => $kategoriId,
            'item_count'  => count($items),
        ]);
    }

    /**
     * Cascade toggle: Tüm alt kategorilere yayın tipi durumunu uygula
     *
     * @param IlanKategori $category Üst kategori
     * @param int $yayinTipiId
     * @param bool $aktiflikDurumu
     * @return int Güncellenen alt kategori sayısı
     */
    public function cascadeToggleAltKategoriYayinTipi(
        IlanKategori $category,
        int $yayinTipiId,
        bool $aktiflikDurumu
    ): int {
        $this->blockAgentWrite('cascadeToggleAltKategoriYayinTipi');

        $children = $category->children;

        DB::transaction(function () use ($children, $yayinTipiId, $aktiflikDurumu) {
            foreach ($children as $child) {
                $this->toggleAltKategoriYayinTipi($child->id, $yayinTipiId, $aktiflikDurumu);
            }
        });

        LogService::info('Cascade toggle uygulandı', [
            'parent_kategori_id' => $category->id,
            'yayin_tipi_id'      => $yayinTipiId,
            'aktiflik_durumu'    => $aktiflikDurumu,
            'child_count'        => $children->count(),
        ]);

        return $children->count();
    }

    /**
     * Özellik atamalarını yayın tipi ile eşitle
     *
     * @param int $yayinTipiId
     * @param array $featureIds
     * @return void
     * @throws \RuntimeException
     */
    public function syncFeatures(int $yayinTipiId, array $featureIds): void
    {
        DB::beginTransaction();
        try {
            $propertyType = YayinTipiSablonu::findOrFail($yayinTipiId);

            $validator = app(\App\Services\Category\FeatureAssignmentValidator::class);
            $validation = $validator->validateBatch($featureIds, $propertyType);

            if (!$validation['valid']) {
                $invalidNames = \array_column($validation['invalid_features'], 'feature_name');
                throw new \RuntimeException('Bazı özellikler uygun değil: ' . \implode(', ', $invalidNames));
            }

            $propertyType->syncFeatures($featureIds);

            app(FeatureAssignmentService::class)->invalidateCache();

            DB::commit();

            LogService::info('✅ Features synchronized', [
                'yayin_tipi_id' => $yayinTipiId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            LogService::error('❌ Feature synchronization failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Tüm kategori cache'ini temizle
     *
     * @return void
     */
    public function invalidateAllCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . ':main_categories');
        // Diğer cache'ler pattern-based silinebilir (Redis için)
        // Şimdilik sadece main categories temizleniyor
    }
}

