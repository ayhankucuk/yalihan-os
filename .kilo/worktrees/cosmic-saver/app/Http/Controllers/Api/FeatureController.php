<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Services\Ups\UpsFeatureGovernanceService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeatureController extends Controller
{
    /**
     * Get features by category slug or ilan category
     * Context7: Supports applies_to filtering for category-specific features
     * - Handles both string and JSON array storage for applies_to
     * - Includes safe defaults to avoid irrelevant groups (e.g., konut-only groups on arsa)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // ✅ SAB: Get filters
            $categoryId = $request->get('category_id');
            $appliesTo = $request->get('applies_to');
            $yayinTipiId = $request->get('yayin_tipi_id'); // WFC-002: Canonical field
            $categorySlugFilter = $request->get('category');

            Log::info('🔍 FeatureController@index', compact('categoryId', 'appliesTo', 'yayinTipiId'));

            // Helper closure: apply applies_to filter supporting JSON or string
            $applyAppliesToFilter = function ($query, string $column, string $needle) {
                return $query->where(function ($q) use ($column, $needle) {
                    // String storage: exact match or 'all'
                    $q->where($column, $needle)
                        ->orWhere($column, 'all');

                    // JSON storage: ["konut"], ["arsa"], etc. (works if column is JSON or TEXT containing JSON)
                    // MySQL/MariaDB JSON_CONTAINS
                    $q->orWhereRaw("JSON_VALID($column) AND JSON_CONTAINS($column, JSON_QUOTE(?))", [$needle]);
                });
            };

            // ✅ Load categories with filtering via Governance
            // ✅ Include inactive features if requested (for admin/show all features)
            $includeInactive = $request->get('include_inactive', false);
            $service = app(UpsFeatureGovernanceService::class);
            $result = $service->listFeaturesLegacy($appliesTo, $categorySlugFilter, $yayinTipiId, $includeInactive);

            return ResponseService::success([
                'data' => $result,
                'metadata' => [
                    'category_id' => $categoryId,
                    'applies_to' => $appliesTo,
                    'yayin_tipi_id' => $yayinTipiId,
                    'total_categories' => count($result),
                    'total_features' => collect($result)->sum(fn ($cat) => count($cat['features'])),
                ],
            ], 'Özellikler başarıyla getirildi');
        } catch (\Exception $e) {
            Log::error('FeatureController::index hatası', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseService::serverError('Özellikler yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Get features by category slug (applies_to filtresi ile)
     * ✅ FIX: FeaturesService kullanarak applies_to filtresi ile tüm kategorileri döndür
     *
     * @param  string  $categorySlug  - İlan kategori slug'ı (konut, arsa, vb.)
     * @param  Request  $request  - Query params: yayin_tipi
     */
    public function getByCategory(string $categorySlug, Request $request): JsonResponse
    {
        try {
            Log::info('FeatureController::getByCategory başladı', [
                'categorySlug' => $categorySlug,
                'yayin_tipi_id' => $request->get('yayin_tipi_id'), // WFC-002: Canonical field
            ]);

            // ✅ Governance servisi ile applies_to filtresi
            $featuresService = app(UpsFeatureGovernanceService::class);
            $yayinTipiId = $request->get('yayin_tipi_id'); // WFC-002: Canonical field
            // ✅ Include inactive features if requested (for admin/show all features)
            $includeInactive = $request->get('include_inactive', false);

            // ✅ SAB: applies_to = ilan kategori slug'ı (konut, arsa, vb.)
            $categories = $featuresService->listFeaturesLegacy($categorySlug, null, $yayinTipiId, $includeInactive);

            Log::info('FeatureController::getByCategory - Features yüklendi', [
                'categorySlug' => $categorySlug,
                'categoriesCount' => count($categories),
                'totalFeatures' => array_sum(array_map(fn ($c) => count($c['features'] ?? []), $categories)),
            ]);

            return ResponseService::success([
                'data' => $categories, // ✅ FIX: FeaturesService format'ı (kategoriler + feature'lar)
                'features' => $categories, // ✅ Backward compatibility
            ], 'Özellikler başarıyla getirildi');
        } catch (\Exception $e) {
            Log::error('FeatureController::getByCategory hatası', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'categorySlug' => $categorySlug,
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseService::serverError('Özellikler yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Get all feature categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            // ✅ SAB: Sadece veritabanından veri çek
            $query = FeatureCategory::query();

            // ✅ SAB: aktiflik_durumu field kullanımı (Sealed)
            $query->where('aktiflik_durumu', true);

            // ✅ SAB: Sadece mevcut kolonları çek (type kolonu yok)
            $categories = $query->orderBy('display_order') // context7-ignore
                ->orderBy('name') // context7-ignore
                ->get(['id', 'name', 'slug', 'icon']);

            return ResponseService::success([
                'categories' => $categories,
            ], 'Özellik kategorileri başarıyla getirildi');
        } catch (\Exception $e) {
            // ✅ SAB: Hata detaylarını logla
            Log::error('FeatureController::getCategories hatası', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseService::serverError('Failed to load categories', $e);
        }
    }
}
