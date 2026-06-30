<?php

namespace App\Services\Wizard\FieldEngine;

use App\Enums\AktiflikDurumu;
use App\Models\IlanKategori;
use App\Models\KategoriYayinTipiFieldDependency;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FieldResolver — DB'den FieldDefinition[] üretir.
 *
 * Sorumluluk:
 *  - kategori_yayin_tipi_field_dependencies tablosundan aktif field'ları çeker
 *  - Her satırı FieldDefinition DTO'ya çevirir
 *  - Fallback: alt kategori → parent kategori → boş set
 *  - Sonucu cache'ler (1 saat)
 *
 * Kullanım:
 *   $resolver = app(FieldResolver::class);
 *   $fields = $resolver->resolve(kategoriId: 5, yayinTipiId: 2);
 *   // $fields: FieldDefinition[]
 */
class FieldResolver
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'field_engine:schema';

    /**
     * Resolve field definitions for a category + publication type combination.
     *
     * @param int $kategoriId Alt kategori ID (veya ana kategori)
     * @param int $yayinTipiId Yayın tipi şablonu ID
     * @return FieldDefinition[] Sorted by display_order
     */
    public function resolve(int $kategoriId, int $yayinTipiId): array
    {
        $cacheKey = self::CACHE_PREFIX . ":{$kategoriId}:{$yayinTipiId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($kategoriId, $yayinTipiId) {
            return $this->doResolve($kategoriId, $yayinTipiId);
        });
    }

    /**
     * Resolve without cache (for admin/debug).
     */
    public function resolveWithoutCache(int $kategoriId, int $yayinTipiId): array
    {
        return $this->doResolve($kategoriId, $yayinTipiId);
    }

    /**
     * Internal resolve logic.
     */
    private function doResolve(int $kategoriId, int $yayinTipiId): array
    {
        // 1. Resolve slugs
        $kategori = IlanKategori::find($kategoriId);
        if (!$kategori) {
            Log::warning('FieldResolver: Kategori bulunamadı', ['kategori_id' => $kategoriId]);
            return [];
        }

        $yayinTipi = YayinTipiSablonu::find($yayinTipiId);
        if (!$yayinTipi) {
            Log::warning('FieldResolver: YayınTipi bulunamadı', ['yayin_tipi_id' => $yayinTipiId]);
            return [];
        }

        $kategoriSlug = $kategori->slug;
        $yayinTipiSlug = $yayinTipi->slug;

        // 2. Query DB — exact match on kategori_slug + yayin_tipi
        $rows = $this->queryFields($kategoriSlug, $yayinTipiSlug, $yayinTipiId);

        // 3. Fallback: parent category slug
        if ($rows->isEmpty() && $kategori->parent_id) {
            $parent = IlanKategori::find($kategori->parent_id);
            if ($parent) {
                $rows = $this->queryFields($parent->slug, $yayinTipiSlug, $yayinTipiId);
                Log::info('FieldResolver: Fallback to parent', [
                    'child_slug' => $kategoriSlug,
                    'parent_slug' => $parent->slug,
                    'found' => $rows->count(),
                ]);
            }
        }

        // 4. Convert to DTO[]
        $fields = $rows->map(function (KategoriYayinTipiFieldDependency $row) {
            return FieldDefinition::fromDbRow($row->toArray());
        })->toArray();

        // 5. Sort by display_order → name
        usort($fields, function (FieldDefinition $a, FieldDefinition $b) {
            return $a->display_order <=> $b->display_order
                ?: strcmp($a->name, $b->name);
        });

        Log::debug('FieldResolver: Resolved', [
            'kategori_slug' => $kategoriSlug,
            'yayin_tipi_slug' => $yayinTipiSlug,
            'field_count' => count($fields),
        ]);

        return $fields;
    }

    /**
     * Query fields from DB.
     *
     * Match logic:
     *  - Exact: kategori_slug + yayin_tipi slug
     *  - OR: kategori_slug + yayin_tipi_id (numeric)
     *  - OR: kategori_slug + yayin_tipi is NULL/empty (global fields)
     */
    private function queryFields(string $kategoriSlug, string $yayinTipiSlug, int $yayinTipiId): Collection
    {
        return KategoriYayinTipiFieldDependency::where('kategori_slug', $kategoriSlug)
            ->where('aktiflik_durumu', AktiflikDurumu::AKTIF)
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

    /**
     * Get field definitions grouped by category.
     *
     * @return array<string, array{name: string, slug: string, fields: FieldDefinition[]}>
     */
    public function resolveGrouped(int $kategoriId, int $yayinTipiId): array
    {
        $fields = $this->resolve($kategoriId, $yayinTipiId);
        $groups = [];

        foreach ($fields as $field) {
            $cat = $field->category;
            if (!isset($groups[$cat])) {
                $groups[$cat] = [
                    'name' => self::categoryLabel($cat),
                    'slug' => $cat,
                    'fields' => [],
                ];
            }
            $groups[$cat]['fields'][] = $field;
        }

        return array_values($groups);
    }

    /**
     * Get schema contract (full response for API).
     *
     * @return array{fields: array, grouped: array, meta: array}
     */
    public function resolveSchemaContract(int $kategoriId, int $yayinTipiId): array
    {
        $fields = $this->resolve($kategoriId, $yayinTipiId);
        $grouped = $this->resolveGrouped($kategoriId, $yayinTipiId);

        // Convert grouped fields to arrays for JSON
        $groupedArrays = array_map(function ($group) {
            return [
                'name' => $group['name'],
                'slug' => $group['slug'],
                'fields' => array_map(fn (FieldDefinition $f) => $f->toArray(), $group['fields']),
            ];
        }, $grouped);

        $fieldArrays = array_map(fn (FieldDefinition $f) => $f->toArray(), $fields);

        return [
            'fields' => $fieldArrays,
            'grouped' => $groupedArrays,
            'meta' => [
                'total_fields' => count($fields),
                'required_count' => count(array_filter($fields, fn (FieldDefinition $f) => $f->required)),
                'ai_fillable_count' => count(array_filter($fields, fn (FieldDefinition $f) => $f->isAiCapable())),
                'has_dependencies' => count(array_filter($fields, fn (FieldDefinition $f) => $f->hasDependencies())) > 0,
            ],
        ];
    }

    /**
     * Get only required field slugs.
     */
    public function getRequiredSlugs(int $kategoriId, int $yayinTipiId): array
    {
        return array_map(
            fn (FieldDefinition $f) => $f->slug,
            array_filter($this->resolve($kategoriId, $yayinTipiId), fn (FieldDefinition $f) => $f->required)
        );
    }

    /**
     * Get all field slugs (schema whitelist for validation).
     */
    public function getAllSlugs(int $kategoriId, int $yayinTipiId): array
    {
        return array_map(
            fn (FieldDefinition $f) => $f->slug,
            $this->resolve($kategoriId, $yayinTipiId)
        );
    }

    /**
     * Invalidate cache for a specific combination.
     */
    public function invalidateCache(int $kategoriId, int $yayinTipiId): void
    {
        Cache::forget(self::CACHE_PREFIX . ":{$kategoriId}:{$yayinTipiId}");
    }

    /**
     * Category slug → human label mapping.
     */
    private static function categoryLabel(string $slug): string
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
}
