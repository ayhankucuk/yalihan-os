<?php

namespace App\Services\Category;

use App\Models\IlanKategori;
use App\Models\KategoriYayinTipiFieldDependency;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CategoryTreeService — Kategori ağacı ve field schema SSOT resolver.
 *
 * Sorumluluk:
 *  - Hiyerarşik kategori ağacını önbelleğe alıp sunmak
 *  - Kategori + yayın tipi kombinasyonuna göre field schema çözmek
 *  - Wizard engine'e schema contract sağlamak
 *
 * @package App\Services\Category
 */
class CategoryTreeService
{
    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'cat_tree';

    /**
     * Cache TTL (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Tam kategori ağacını döndürür: Ana → Alt → Yayın Tipleri
     *
     * @return array<int, array{id: int, name: string, slug: string, icon: string, children: array}>
     */
    public function getTree(): array
    {
        return Cache::remember(self::CACHE_PREFIX . ':full_tree', self::CACHE_TTL, function () {
            $anaKategoriler = IlanKategori::where('seviye', 0)
                ->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            return $anaKategoriler->map(function (IlanKategori $ana) {
                $altKategoriler = IlanKategori::where('parent_id', $ana->id)
                    ->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF)
                    ->orderBy('display_order')
                    ->orderBy('id')
                    ->get();

                return [
                    'id' => $ana->id,
                    'name' => $ana->name,
                    'slug' => $ana->slug,
                    'icon' => $ana->icon,
                    'icon_emoji' => $ana->icon_emoji,
                    'children' => $altKategoriler->map(function (IlanKategori $alt) {
                        // Alt kategorinin yayın tiplerini junction üzerinden getir
                        $yayinTipleri = $alt->yayinTipleri()
                            ->wherePivot('aktiflik_durumu', 1)
                            ->orderBy('display_order')
                            ->orderBy('id')
                            ->get();

                        return [
                            'id' => $alt->id,
                            'name' => $alt->name,
                            'slug' => $alt->slug,
                            'icon' => $alt->icon,
                            'icon_emoji' => $alt->icon_emoji,
                            'yayin_tipleri' => $yayinTipleri->map(fn (YayinTipiSablonu $yt) => [
                                'id' => $yt->id,
                                'ad' => $yt->ad,
                                'slug' => $yt->slug,
                            ])->values()->toArray(),
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();
        });
    }

    /**
     * Belirli bir ana kategorinin alt kategorilerini döndürür.
     *
     * @param int $parentId Ana kategori ID
     * @return Collection<IlanKategori>
     */
    public function getSubcategories(int $parentId): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . ":subs:{$parentId}",
            self::CACHE_TTL,
            fn () => IlanKategori::where('parent_id', $parentId)
                ->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * Belirli bir alt kategorinin yayın tiplerini döndürür.
     *
     * @param int $altKategoriId Alt kategori ID
     * @return Collection<YayinTipiSablonu>
     */
    public function getYayinTipleri(int $altKategoriId): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . ":yt:{$altKategoriId}",
            self::CACHE_TTL,
            function () use ($altKategoriId) {
                $kategori = IlanKategori::find($altKategoriId);
                if (!$kategori) {
                    return collect();
                }

                return $kategori->yayinTipleri()
                    ->wherePivot('aktiflik_durumu', 1)
                    ->orderBy('display_order')
                    ->orderBy('id')
                    ->get();
            }
        );
    }

    /**
     * Kategori + yayın tipi kombinasyonu için field schema döndürür.
     *
     * Bu metod wizard Step 2'nin SSOT kaynağıdır.
     * KategoriYayinTipiFieldDependency tablosundan schema-driven field listesi üretir.
     *
     * @param int $kategoriId Alt kategori ID
     * @param int $yayinTipiId Yayın tipi sablonu ID
     * @return array{fields: array, meta: array}
     */
    public function resolveFieldSchema(int $kategoriId, int $yayinTipiId): array
    {
        $cacheKey = self::CACHE_PREFIX . ":schema:{$kategoriId}:{$yayinTipiId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kategoriId, $yayinTipiId) {
            $kategori = IlanKategori::find($kategoriId);
            if (!$kategori) {
                Log::warning('CategoryTreeService: Kategori bulunamadı', ['kategori_id' => $kategoriId]);
                return $this->emptySchema();
            }

            $kategoriSlug = $kategori->slug;

            // Yayın tipi slug'ını çöz
            $yayinTipiSablon = YayinTipiSablonu::find($yayinTipiId);
            $yayinTipiSlug = $yayinTipiSablon?->slug;

            // 1. Doğrudan kategori_slug + yayin_tipi eşleşen field'lar
            $fields = KategoriYayinTipiFieldDependency::where('kategori_slug', $kategoriSlug)
                ->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF)
                ->where(function ($query) use ($yayinTipiSlug, $yayinTipiId) {
                    $query->where('yayin_tipi', $yayinTipiSlug)
                        ->orWhere('yayin_tipi_id', $yayinTipiId)
                        ->orWhereNull('yayin_tipi')  // Global fields (tüm yayın tipleri)
                        ->orWhere('yayin_tipi', '');
                })
                ->orderBy('display_order')
                ->orderBy('field_name')
                ->get();

            // 2. Eğer hiç field yoksa, parent kategori slug'ından dene
            if ($fields->isEmpty() && $kategori->parent_id) {
                $parentKategori = IlanKategori::find($kategori->parent_id);
                if ($parentKategori) {
                    $fields = KategoriYayinTipiFieldDependency::where('kategori_slug', $parentKategori->slug)
                        ->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF)
                        ->where(function ($query) use ($yayinTipiSlug, $yayinTipiId) {
                            $query->where('yayin_tipi', $yayinTipiSlug)
                                ->orWhere('yayin_tipi_id', $yayinTipiId)
                                ->orWhereNull('yayin_tipi')
                                ->orWhere('yayin_tipi', '');
                        })
                        ->orderBy('display_order')
                        ->orderBy('field_name')
                        ->get();
                }
            }

            // 3. Transform to schema contract
            $schemaFields = $fields->map(function (KategoriYayinTipiFieldDependency $field) {
                $options = $field->field_options ?? [];

                return [
                    'id' => $field->id,
                    'slug' => $field->field_slug,
                    'name' => $field->field_name,
                    'type' => $field->field_type ?? 'text',
                    'category' => $field->field_category ?? 'general',
                    'icon' => $field->field_icon ?? '📋',
                    'unit' => $field->field_unit ?? null,
                    'required' => (bool) $field->required,
                    'display_order' => (int) ($field->display_order ?? 999),
                    'options' => $this->normalizeFieldOptions($options, $field->field_type ?? 'text'),
                    'ai_auto_fill' => (bool) $field->ai_auto_fill,
                    'ai_suggestion' => (bool) $field->ai_suggestion,
                    'ai_prompt_key' => $field->ai_prompt_key,
                    'searchable' => (bool) $field->searchable,
                    'show_in_card' => (bool) $field->show_in_card,
                    // Dependency rules (visible_if, required_if from field_options)
                    'visible_if' => $options['visible_if'] ?? null,
                    'required_if' => $options['required_if'] ?? null,
                    'depends_on' => $options['depends_on'] ?? null,
                    'placeholder' => $options['placeholder'] ?? null,
                    'help_text' => $options['help_text'] ?? null,
                    'min' => $options['min'] ?? null,
                    'max' => $options['max'] ?? null,
                    'step' => $options['step'] ?? null,
                ];
            })->values()->toArray();

            // 4. Group by field_category for UI rendering
            $grouped = collect($schemaFields)->groupBy('category')->map(function ($group, $categoryName) {
                return [
                    'name' => $this->getCategoryLabel($categoryName),
                    'slug' => $categoryName,
                    'fields' => $group->values()->toArray(),
                ];
            })->values()->toArray();

            return [
                'fields' => $schemaFields,
                'grouped' => $grouped,
                'meta' => [
                    'total_fields' => count($schemaFields),
                    'required_count' => collect($schemaFields)->where('required', true)->count(),
                    'ai_fillable_count' => collect($schemaFields)->where('ai_auto_fill', true)->count(),
                    'kategori_slug' => $kategoriSlug,
                    'yayin_tipi_slug' => $yayinTipiSlug,
                ],
            ];
        });
    }

    /**
     * Field options'ı normalize et (select/multiselect için)
     */
    private function normalizeFieldOptions(array $options, string $fieldType): ?array
    {
        if (!in_array($fieldType, ['select', 'multiselect'])) {
            return null;
        }

        $items = $options['items'] ?? $options['choices'] ?? $options['values'] ?? null;

        if (empty($items)) {
            return null;
        }

        return collect($items)->map(function ($item) {
            if (is_array($item) && isset($item['value'], $item['label'])) {
                return $item;
            }
            $label = is_string($item) ? $item : (string) $item;
            return [
                'value' => \Illuminate\Support\Str::slug($label),
                'label' => $label,
            ];
        })->values()->toArray();
    }

    /**
     * Field category label mapping
     */
    private function getCategoryLabel(string $slug): string
    {
        return match ($slug) {
            'temel', 'general' => 'Temel Bilgiler',
            'fiziksel' => 'Fiziksel Özellikler',
            'altyapi' => 'Altyapı',
            'finansal' => 'Finansal Bilgiler',
            'konum' => 'Konum Detayları',
            'isyeri' => 'İşyeri Detayları',
            'kiralama' => 'Kiralama Bilgileri',
            'ek_ozellikler' => 'Ek Özellikler',
            default => 'Genel',
        };
    }

    /**
     * Boş schema döndür
     */
    private function emptySchema(): array
    {
        return [
            'fields' => [],
            'grouped' => [],
            'meta' => [
                'total_fields' => 0,
                'required_count' => 0,
                'ai_fillable_count' => 0,
                'kategori_slug' => null,
                'yayin_tipi_slug' => null,
            ],
        ];
    }

    /**
     * Cache invalidation
     */
    public function invalidateCache(?string $kategoriSlug = null): void
    {
        if ($kategoriSlug) {
            Cache::forget(self::CACHE_PREFIX . ":schema:{$kategoriSlug}:*");
        }
        Cache::forget(self::CACHE_PREFIX . ':full_tree');

        Log::info('CategoryTreeService: Cache invalidated', [
            'kategori_slug' => $kategoriSlug,
        ]);
    }
}
