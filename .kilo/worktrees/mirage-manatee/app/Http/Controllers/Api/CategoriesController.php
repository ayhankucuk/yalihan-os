<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\YayinTipiSablonu;
use App\Services\Response\ResponseService;
use App\Support\YayinTipiRules;
use App\Traits\ValidatesApiRequests;
use Illuminate\Support\Facades\Log;
use function array_filter;
use function in_array;
use function count;
use function array_values;

class CategoriesController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Alt kategorileri getir (Ana kategori ID'sine göre)
     * ✅ SAB: seviye=1 ve aktiflik_durumu=true olan kategorileri getirir
     */
    public function getSubcategories($parentId)
    {
        try {
            Log::info('Getting subcategories (Context7)', [
                'parent_id' => $parentId,
            ]);

            $subCategories = \App\Models\IlanKategori::where('parent_id', $parentId)
                ->where('seviye', 1) // ✅ Alt kategoriler seviye=1
                ->where('aktiflik_durumu', true) // ✅ SAB: aktiflik_durumu canonical field
                ->where('seviye', '!=', 2) // ✅ Yayın tipleri (seviye=2) ASLA alt kategori olarak listelenmemeli
                ->orderBy('name') // context7-ignore
                ->get(['id', 'name', 'slug', 'icon']);

            Log::info('Subcategories result (Context7)', [
                'parent_id' => $parentId,
                'count' => $subCategories->count(),
                'categories' => $subCategories->pluck('name')->toArray(),
            ]);

            // ✅ SAB: Clean response format, English-only fields
            $mappedCategories = $subCategories->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'icon' => $cat->icon,
                ];
            });

            return ResponseService::success([
                'subcategories' => $mappedCategories,
                'count' => $subCategories->count(),
            ], 'Subcategories loaded successfully');
        } catch (\Exception $e) {
            Log::error('Subcategories loading error', [
                'parent_id' => $parentId,
                'error' => $e->getMessage(),
            ]);

            return ResponseService::serverError('Failed to load subcategories', $e);
        }
    }

    /**
     * Kategori yayın tipleri
     *
     * ✅ FIX (2026-06-24): alt_kategori_yayin_tipi pivot → yayin_tipleri tablosu
     *    kullanılacak. Policy servisi yayin_tipi_sablonlari + seviye=2 arıyor
     *    ancak DB'de bunlar yok; gerçek veri yayin_tipleri + pivot'ta.
     * ✅ Fallback: pivot boşsa UPS Policy'ye düşer.
     */
    public function getPublicationTypes($categoryId)
    {
        try {
            Log::info('Getting publication types', [
                'category_id' => $categoryId,
            ]);

            $category = \App\Models\IlanKategori::find($categoryId);

            if (! $category) {
                return ResponseService::notFound('Category not found');
            }

            // ✅ FIX: alt_kategori_yayin_tipi pivot → yayin_tipleri join
            // yayin_tipleri tablosu gerçek SSOT (4 kayıt: Satılık, Kiralık, Kat Karşılığı, Devren)
            $yayinTipleri = \Illuminate\Support\Facades\DB::table('alt_kategori_yayin_tipi as pivot')
                ->join('yayin_tipleri as yt', 'yt.id', '=', 'pivot.yayin_tipi_id')
                ->where('pivot.alt_kategori_id', $categoryId)
                ->where('pivot.aktiflik_durumu', 1)
                ->where('yt.aktiflik_durumu', 1)
                ->orderBy('pivot.display_order') // context7-ignore
                ->get(['yt.id', 'yt.name', 'yt.slug']);

            // Fallback: pivot boşsa UPS Policy'ye düş
            if ($yayinTipleri->isEmpty()) {
                Log::info('Pivot boş, UPS Policy fallback', [
                    'category_id' => $categoryId,
                    'category_slug' => $category->slug,
                ]);

                /** @var \App\Services\Ups\PropertyPublicationPolicy $policy */
                $policy = app(\App\Services\Ups\PropertyPublicationPolicy::class);
                $policyTypes = $policy->getAllowedTypes($categoryId);

                if ($policyTypes->isEmpty()) {
                    return ResponseService::success([
                        'types' => [], // context7-ignore
                        'yayinTipleri' => [],
                        'count' => 0,
                    ], 'Bu kategori için yayın tipi bulunamadı');
                }

                $mapped = $policyTypes->map(function ($type) {
                    $name = $type->ad ?? $type->name ?? '';
                    $slug = $type->slug ?? YayinTipiRules::canonicalizeSlug($name);
                    return [
                        'id'   => $type->id,
                        'name' => $name,
                        'slug' => $slug,
                    ];
                });

                return ResponseService::success([
                    'types'        => $mapped, // context7-ignore
                    'yayinTipleri' => $mapped,
                    'count'        => $mapped->count(),
                ], 'Yayın tipleri yüklendi (policy fallback)');
            }

            Log::info('Publication types yüklendi (pivot)', [
                'category_id' => $categoryId,
                'category_slug' => $category->slug,
                'count' => $yayinTipleri->count(),
            ]);

            $mapped = $yayinTipleri->map(function ($type) {
                return [
                    'id'   => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                ];
            });

            return ResponseService::success([
                'types'        => $mapped, // context7-ignore
                'yayinTipleri' => $mapped,
                'count'        => $mapped->count(),
            ], 'Yayın tipleri başarıyla yüklendi');

        } catch (\Exception $e) {
            Log::error('Publication types loading error', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseService::serverError('Failed to load publication types', $e);
        }
    }


    /**
     * Kategori + Yayın Tipi'ne göre dinamik alanları getir
     * ✅ SAB: Type-based fields for smart form organizer
     *
     * Query: /api/categories/fields/1/5 (categoryId=1, publicationTypeId=5)
     */
    public function getFields($categoryId, $publicationTypeId = null)
    {
        try {
            Log::info('Getting fields by category and publication type', [
                'category_id' => $categoryId,
                'publication_type_id' => $publicationTypeId,
            ]);

            $category = \App\Models\IlanKategori::find($categoryId);

            if (!$category) {
                return ResponseService::notFound('Category not found');
            }

            // Get features/fields for this category
            $fields = [];

            // Polimorfik features: bu kategori için hangi özellikler gerekli?
            $features = \App\Models\Feature::where('kategori_id', $categoryId)
                ->where('aktiflik_durumu', true)
                ->orderBy('display_order') // context7-ignore
                ->orderBy('name') // context7-ignore
                ->get(['id', 'name', 'field_type', 'required', 'display_order', 'description']);

            $fields = $features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'field_type' => $feature->field_type, // text, select, number, boolean, etc.
                    'required' => $feature->required ?? false,
                    'description' => $feature->description ?? '',
                ];
            })->toArray();

            // If publication type specified, filter further
            if ($publicationTypeId) {
                $publicationType = \App\Models\YayinTipiSablonu::find($publicationTypeId);
                if ($publicationType && isset($publicationType->required_fields)) {
                    $requiredFieldIds = json_decode($publicationType->required_fields, true) ?? [];
                    $fields = array_filter($fields, function ($field) use ($requiredFieldIds) {
                        return in_array($field['id'], $requiredFieldIds);
                    });
                }
            }

            Log::info('Fields loaded successfully', [
                'category_id' => $categoryId,
                'field_count' => count($fields),
            ]);

            return ResponseService::success([
                'fields' => array_values($fields),
                'count' => count($fields),
            ], 'Alanlar başarıyla yüklendi');
        } catch (\Exception $e) {
            Log::error('Fields loading error', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return ResponseService::serverError('Alanlar yüklenemedi', $e);
        }
    }
}
