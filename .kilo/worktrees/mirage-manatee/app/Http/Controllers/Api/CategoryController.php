<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    use ValidatesApiRequests;

    public function index(Request $request)
    {
        // ✅ REFACTORED: Using ResponseService
        return ResponseService::success(null, 'Category endpoint - to be implemented');
    }

    public function getSubcategories($parentId)
    {
        try {
            Log::info('Getting subcategories', [
                'parent_id' => $parentId,
            ]);

            $subCategories = \App\Models\IlanKategori::where('parent_id', $parentId)
                ->where('seviye', 1) // ✅ Alt kategoriler seviye=1
                ->where('aktiflik_durumu', true) // ✅ SAB: aktiflik_durumu canonical field
                ->where('seviye', '!=', 2) // ✅ Yayın tipleri (seviye=2) ASLA alt kategori olarak listelenmemeli
                ->orderBy('name') // context7-ignore
                ->get(['id', 'name', 'slug', 'icon']);

            Log::info('Subcategories query result', [
                'parent_id' => $parentId,
                'count' => $subCategories->count(),
                'categories' => $subCategories->pluck('name')->toArray(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'subcategories' => $subCategories->map(function ($cat) {
                        return [
                            'id' => $cat->id,
                            'name' => $cat->name,
                            'slug' => $cat->slug,
                            'icon' => $cat->icon,
                        ];
                    }),
                    'count' => $subCategories->count(),
                ],
                'message' => 'Subcategories loaded successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Subcategories loading error', [
                'parent_id' => $parentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load subcategories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get publication types for a specific category
     *
     * Uses PropertyPublicationPolicy as SINGLE SOURCE OF TRUTH.
     * Category-aware: only returns publication types allowed for the given category.
     */
    public function getPublicationTypes($categoryId)
    {
        try {
            Log::info('Getting publication types', [
                'category_id' => $categoryId,
            ]);

            $category = \App\Models\IlanKategori::find($categoryId);

            if (! $category) {
                // ✅ REFACTORED: Using ResponseService
                return ResponseService::notFound('Category not found');
            }

            // UPS Policy: Single source of truth for allowed publication types
            $policy = app(\App\Services\Ups\PropertyPublicationPolicy::class);
            $yayinTipleri = $policy->getAllowedTypes($categoryId);

            Log::info('Publication types result (policy-driven)', [
                'category_id' => $categoryId,
                'category_slug' => $category->slug,
                'count' => $yayinTipleri->count(),
                'types' => $yayinTipleri->map(fn($t) => $t->ad ?? $t->name ?? '')->toArray(), // context7-ignore
            ]);

            if ($yayinTipleri->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'types' => [], // context7-ignore
                        'yayinTipleri' => [],
                        'count' => 0,
                        'message' => 'Bu kategori için yayın tipi bulunamadı',
                    ]
                ]);
            }

            $mappedTypes = $yayinTipleri->map(function ($type) {
                // ✅ YayinTipiSablonu
                $name = $type->ad;
                return [
                    'id' => $type->id,
                    'name' => $name,
                    'slug' => $type->slug,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'types' => $mappedTypes, // context7-ignore
                    'yayinTipleri' => $mappedTypes,
                    'count' => $yayinTipleri->count(),
                    'message' => 'Yayın tipleri yüklendi',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Publication types loading error', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Yayın tipleri yüklenirken hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug endpoint: Show listing type resolution chain for a category pair.
     * GET /api/v1/categories/listing-types/debug?ana_kategori_id=1&alt_kategori_id=7
     */
    public function debugListingTypes(Request $request)
    {
        if (!app()->isLocal()) {
            return ResponseService::error('Debug endpoint is only available in local environment.', 403);
        }

        $anaKategoriId = (int) $request->query('ana_kategori_id', 0);
        $altKategoriId = $request->query('alt_kategori_id') ? (int) $request->query('alt_kategori_id') : null;

        if (!$anaKategoriId) {
            return ResponseService::error('ana_kategori_id is required.', 422);
        }

        $resolver = app(\App\Services\Wizard\EffectiveListingTypeResolver::class);
        $debug = $resolver->debug($anaKategoriId, $altKategoriId);

        return response()->json([
            'success' => true,
            'data' => $debug,
        ]);
    }

    /**
     * Get category hierarchy path (for breadcrumb/inheritance tree)
     * Endpoint: GET /api/v1/categories/path/{id}
     */
    public function getCategoryPath($id)
    {
        try {
            $category = \App\Models\IlanKategori::find($id);

            if (!$category) {
                return ResponseService::notFound('Kategori bulunamadı');
            }

            $path = [];
            $current = $category;

            // Build path from current to root
            while ($current) {
                array_unshift($path, [
                    'id' => $current->id,
                    'name' => $current->kategori_adi ?? $current->name, // ✅ SAB: kategori_adi
                    'slug' => $current->slug,
                    'seviye' => $current->seviye,
                ]);

                $current = $current->parent_id ? \App\Models\IlanKategori::find($current->parent_id) : null;
            }

            return ResponseService::success([
                'path' => $path,
                'depth' => count($path),
            ], 'Kategori yolu yüklendi');
        } catch (\Exception $e) {
            Log::error('Category path error', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return ResponseService::serverError('Kategori yolu yüklenirken hata oluştu', $e);
        }
    }

    /**
     * Get publication types for UPS template editor
     * Endpoint: GET /api/v1/categories/publication-types/{id}
     */
    public function getPublicationTypesForUps($categoryId)
    {
        try {
            // Context7: Global YayinTipiSablonu used for all categories
            $yayinTipleri = \App\Models\YayinTipiSablonu::where('aktiflik_durumu', true)
                ->orderBy('display_order') // context7-ignore
                ->orderBy('ad') // context7-ignore
                ->get(['id', 'ad as yayin_tipi', 'slug']);

            return ResponseService::success($yayinTipleri, 'Yayın tipleri yüklendi');
        } catch (\Exception $e) {
            Log::error('Publication types for UPS error', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return ResponseService::serverError('Yayın tipleri yüklenirken hata oluştu', $e);
        }
    }

    public function getFieldsByCategory($id)
    {
        return ResponseService::success([
            'category_id' => (int) $id,
            'fields' => [],
        ], 'Kategori alanları');
    }

    public function renderCategoryFields($id)
    {
        return ResponseService::success([
            'category_id' => (int) $id,
            'html' => '',
        ], 'Kategori alanları render edildi');
    }
}
