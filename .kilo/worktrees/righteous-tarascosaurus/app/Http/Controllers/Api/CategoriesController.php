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
     * ✅ SAB: seviye=1 ve status=true olan kategorileri getirir
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
     * Kategori yayın tipleri - UPS PropertyPublicationPolicy (Yalıhan Unified Property Schema)
     *
     * ✅ UPS: PropertyPublicationPolicy service kullanır (single source of truth)
     * ✅ UPS: Legacy alt_kategori_yayin_tipi pivot tablosu runtime'dan kaldırıldı
     * ✅ UPS: Arsa family için strict policy enforcement
     * ✅ Phase 6.8.1: Yazlık sub-categories için categoryId direkt kullan
     */
    public function getPublicationTypes($categoryId)
    {
        try {
            Log::info('Getting publication types (UPS Policy)', [
                'category_id' => $categoryId,
            ]);

            $category = \App\Models\IlanKategori::find($categoryId);

            if (! $category) {
                return ResponseService::notFound('Category not found');
            }

            // ✅ UPS: PropertyPublicationPolicy service kullan
            /** @var \App\Services\Ups\PropertyPublicationPolicy $policy */
            $policy = app(\App\Services\Ups\PropertyPublicationPolicy::class);

            // Get allowed publication types from UPS policy (direkt categoryId kullan)
            $yayinTipleri = $policy->getAllowedTypes($categoryId);

            Log::info('Publication types result (UPS Policy)', [
                'category_id' => $categoryId,
                'category_slug' => $category->slug,
                'policy_enforced' => $policy->hasExplicitPolicy($categoryId),
                'count' => $yayinTipleri->count(),
                'model_type' => $yayinTipleri->first() ? get_class($yayinTipleri->first()) : 'none',
            ]);

            if ($yayinTipleri->isEmpty()) {
                return ResponseService::success([
                    'types' => [], // context7-ignore
                    'count' => 0,
                ], 'No publication types found for this category');
            }

            return ResponseService::success([
                'types' => $yayinTipleri->map(function ($type) { // context7-ignore
                    // UPS Phase 2: Polymorphic support - handle both IlanKategori and YayinTipiSablonu
                    // YayinTipiSablonu has 'ad', IlanKategori has 'name'
                    $name = $type->ad ?? $type->name ?? '';

                    return [
                        'id' => $type->id,
                        'name' => $name,
                        'slug' => YayinTipiRules::canonicalizeSlug($name),
                    ];
                }),
                'count' => $yayinTipleri->count(),
            ], 'Publication types loaded successfully');
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
