<?php

use App\Http\Controllers\Admin\AddressManagementController;
use App\Http\Controllers\Admin\ArsaCalculationController;
use App\Http\Controllers\Admin\ArsaCalculatorController;
use App\Http\Controllers\Admin\CalendarSyncController;
use App\Http\Controllers\Admin\AI\IlanAIController;
use App\Http\Controllers\Admin\KategoriOzellikApiController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PageAnalyzerController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\IlanSearchController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\TKGMParselController;
use App\Http\Controllers\Api\BulkOperationsController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ConfigOptionController;
use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Api\FieldDependencyController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SmartFieldController;
use App\Http\Controllers\Api\V1\BulkManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes (v1)
|--------------------------------------------------------------------------
|
| Admin panel API endpoints
| Requires authentication and admin middleware
|
*/

Route::prefix('admin')->name('api.admin.')->middleware(['auth', 'admin', 'role:admin', 'sab.compliance'])->group(function () {
    // 🔍 Global Arama (Arveya Style)
    Route::get('/global-search', [\App\Http\Controllers\Api\V1\GlobalSearchController::class, 'search'])->name('global-search');

    // Menu Items API (Lazy Loading için)
    Route::get('/menu-items', [\App\Http\Controllers\Api\Admin\MenuItemsController::class, 'index'])->name('menu-items');
    Route::post('/menu-items/clear-cache', [\App\Http\Controllers\Api\Admin\MenuItemsController::class, 'clearCache'])->name('menu-items.clear-cache');

    // Bulk Operations API
    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::post('/assign-category', [BulkOperationsController::class, 'assignCategory'])->name('assign-category');
        // Context7: Renamed for compliance
        Route::post('/toggle-yayin-durumu', [BulkOperationsController::class, 'toggleYayinDurumu'])->name('toggle-yayin-durumu');
        Route::post('/delete', [BulkOperationsController::class, 'bulkDelete'])->name('delete');
        Route::post('/sirala', [BulkOperationsController::class, 'sirala'])->name('sirala');
    });

    // Bulk Management API (Phase 5)
    Route::prefix('bulk-v2')->name('bulk-v2.')->group(function () {
        Route::post('/update-yayin-tipi', [BulkManagementController::class, 'bulkUpdateYayinTipi'])->name('update-yayin-tipi');
        Route::post('/update-fiyat', [BulkManagementController::class, 'bulkUpdateFiyat'])->name('update-fiyat');
        Route::post('/update-kategori', [BulkManagementController::class, 'bulkUpdateKategori'])->name('update-kategori');
        Route::post('/change-aktiflik', [BulkManagementController::class, 'bulkChangeAktiflikDurumu'])->name('change-aktiflik');
        Route::delete('/delete', [BulkManagementController::class, 'bulkDelete'])->name('delete');
    });

    // Address Management API (Context7 Compliant)
    Route::prefix('address')->name('address.')->group(function () {
        Route::get('/iller', [AddressManagementController::class, 'getIller'])->name('iller');
        Route::get('/ilceler', [AddressManagementController::class, 'getIlceler'])->name('ilceler');
        Route::get('/mahalleler', [AddressManagementController::class, 'getMahalleler'])->name('mahalleler');
        Route::get('/bolgeler', [AddressManagementController::class, 'getBolgeler'])->name('bolgeler');
        Route::post('/update-coordinates', [AddressManagementController::class, 'updateCoordinates'])->name('update-coordinates');
        Route::post('/bulk-sync', [AddressManagementController::class, 'bulkSync'])->name('bulk-sync');
    });

    // Arsa Calculation API
    Route::prefix('api/arsa')->name('arsa.')->group(function () {
        Route::post('/calculate', [ArsaCalculationController::class, 'calculate'])->name('calculate');
        Route::post('/tkgm-query', [ArsaCalculationController::class, 'tkgmQuery'])->name('tkgm-query');
        Route::get('/history', [ArsaCalculatorController::class, 'history'])->name('history');
    });

    // Page Analyzer API
    Route::prefix('page-analyzer')->name('page-analyzer.')->group(function () {
        Route::post('/analyze', [PageAnalyzerController::class, 'analyze'])->name('analyze');
        Route::get('/export/{id?}', [PageAnalyzerController::class, 'export'])->name('export');
        Route::post('/rerun/{id}', [PageAnalyzerController::class, 'rerun'])->name('rerun');
    });

    // Features API
    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/', [FeatureController::class, 'index'])->name('index');
        // ✅ FIX: Slug ve ID route'ları - IlanController her ikisini de handle ediyor
        // Route removed: use frontend-features or centralized governance endpoints
        Route::get('/categories', [FeatureController::class, 'getCategories'])->name('categories');
        Route::get('/stats/{slug}', [\App\Http\Controllers\Admin\FeatureController::class, 'countsBySlug'])->name('stats');
        // ✅ ADDED: Category-based features for wizard (features-dynamic.blade.php)
        Route::get('/category/{kategoriSlug}', [\App\Http\Controllers\Admin\IlanFeatureController::class, 'getFrontendFeaturesByCategory'])->name('by-category');
    });

    // ✅ REFACTORED: Frontend features resolver → IlanFeatureController
    // Frontend features resolver for wizard (FeatureAssignment-based)
    Route::get('/category/{kategoriSlug}/frontend-features', [\App\Http\Controllers\Admin\IlanFeatureController::class, 'getFrontendFeaturesByCategory'])
        ->name('category.frontend-features');

    // ✅ Config Options API
    Route::prefix('config-options')->name('config-options.')->group(function () {
        Route::get('/get', [ConfigOptionController::class, 'get'])->name('get');
        Route::post('/get-multiple', [ConfigOptionController::class, 'getMultiple'])->name('get-multiple');
    });

    // 🧠 Smart Form API (Phase 3: Context-Aware Feature Visibility)
    // Context7 Standard: C7-SMART-FORM-API-2026-01-06
    Route::prefix('smart-form')->name('smart-form.')->group(function () {
        Route::get('/features/{kategoriId}/{yayinTipiId}', [\App\Http\Controllers\Admin\SmartFormController::class, 'getFeaturesByPublicationType'])->name('features');
        Route::get('/summary/{yayinTipiId}', [\App\Http\Controllers\Admin\SmartFormController::class, 'getVisibilitySummary'])->name('summary');
        Route::get('/matrix/{kategoriId}', [\App\Http\Controllers\Admin\SmartFormController::class, 'getMatrix'])->name('matrix');
        Route::post('/update-visibility', [\App\Http\Controllers\Admin\SmartFormController::class, 'updateVisibility'])->name('update-visibility');
    });

    // Kategori Özellik API
    Route::prefix('api/kategori-ozellik')->name('kategori-ozellik.')->group(function () {
        Route::get('/category-data', [KategoriOzellikApiController::class, 'getCategoryData'])->name('category-data');
        Route::get('/features-by-category', [\App\Http\Controllers\Admin\IlanFeatureController::class, 'getFrontendFeaturesByCategory'])->name('features-by-category');
        Route::get('/features-by-publishing-type', [KategoriOzellikApiController::class, 'getFeaturesByPublishingType'])->name('features-by-publishing-type');
        Route::get('/feature-categories', [KategoriOzellikApiController::class, 'getFeatureCategories'])->name('feature-categories');
        Route::post('/update-category-features', [KategoriOzellikApiController::class, 'updateCategoryFeatures'])->name('update-category-features');
        Route::get('/frontend-features', [KategoriOzellikApiController::class, 'getFeaturesForFrontend'])->name('frontend-features');
        Route::get('/ilan-features', [KategoriOzellikApiController::class, 'getIlanFeatures'])->name('ilan-features');
    });

    // Smart Fields API
    Route::prefix('api/smart-fields')->name('smart-fields.')->group(function () {
        Route::get('/smart-fields', [SmartFieldController::class, 'getSmartFields'])->name('get-smart-fields');
        Route::get('/category-matrix', [SmartFieldController::class, 'getCategoryMatrix'])->name('get-category-matrix');
        Route::post('/generate-smart-form', [SmartFieldController::class, 'generateSmartForm'])->name('generate-smart-form');
        Route::post('/analyze-property', [SmartFieldController::class, 'analyzeProperty'])->name('analyze-property');
        Route::get('/ai-suggestions', [SmartFieldController::class, 'getAISuggestions'])->name('get-ai-suggestions');
    });

    // Site/Apartman API
    Route::prefix('api')->name('site.')->group(function () {
        Route::get('/sites/search', [SiteController::class, 'search'])->name('search');
        Route::get('/sites/{id}', [SiteController::class, 'show'])->name('detail');
        Route::post('/sites/create', [SiteController::class, 'store'])->name('create');
    });

    // Site Özellikleri API
    Route::prefix('site-ozellikleri')->name('site-ozellikleri.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SiteOzellikleriController::class, 'index'])->name('index');
        Route::get('/active', [\App\Http\Controllers\Api\SiteOzellikleriController::class, 'active'])->name('active');
    });

    // Photo Management API
    Route::prefix('photos')->name('photos.')->group(function () {
        Route::post('/upload', [PhotoController::class, 'store'])->name('upload');
        Route::get('/{id}', [PhotoController::class, 'show'])->name('show');
        Route::put('/{id}', [PhotoController::class, 'update'])->name('update');
        Route::delete('/{id}', [PhotoController::class, 'destroy'])->name('destroy');
        Route::delete('/bulk-delete', [PhotoController::class, 'bulkAction'])->name('bulk-delete');
    });

    // Calendar Sync API
    Route::prefix('calendars')->name('calendars.')->group(function () {
        Route::get('/{ilan}/syncs', [CalendarSyncController::class, 'getSyncs'])->name('syncs.index');
        Route::post('/{ilan}/syncs', [CalendarSyncController::class, 'createSync'])->name('syncs.store');
        Route::post('/{ilan}/syncs/{sync}', [CalendarSyncController::class, 'updateSync'])->name('syncs.update');
        Route::delete('/{ilan}/syncs/{sync}', [CalendarSyncController::class, 'deleteSync'])->name('syncs.destroy');
        Route::post('/{ilan}/manual-sync', [CalendarSyncController::class, 'manualSync'])->name('manual-sync');
        Route::get('/{ilan}/calendar', [CalendarSyncController::class, 'getCalendar'])->name('calendar');
        Route::post('/{ilan}/block', [CalendarSyncController::class, 'blockDates'])->name('block');
    });

    // TKGM Parsel API
    Route::prefix('api/tkgm-parsel')->name('tkgm-parsel.')->middleware('throttle:20,1')->group(function () {
        Route::post('/query', [TKGMParselController::class, 'query'])->name('query');
        Route::post('/bulk-query', [TKGMParselController::class, 'bulkQuery'])->name('bulk-query');
        Route::get('/history', [TKGMParselController::class, 'history'])->name('history');
        Route::get('/stats', [TKGMParselController::class, 'stats'])->name('stats');
    });

    // Field Dependencies API
    Route::prefix('field-dependencies')->name('field-dependencies.')->group(function () {
        Route::get('/', [FieldDependencyController::class, 'index'])->name('index');
        Route::get('/category/{kategoriId}', [FieldDependencyController::class, 'getByCategory'])->name('by-category');
        Route::post('/upsert', [FieldDependencyController::class, 'upsertDependency'])->name('upsert');
    });

    // ✅ Feature Dependencies API (Context7 Phase L)
    Route::prefix('feature-dependencies')->name('feature-dependencies.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Admin\FeatureDependencyController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\V1\Admin\FeatureDependencyController::class, 'store'])->name('store');
        Route::put('/{featureDependency}', [\App\Http\Controllers\Api\V1\Admin\FeatureDependencyController::class, 'update'])->name('update');
        Route::delete('/{featureDependency}', [\App\Http\Controllers\Api\V1\Admin\FeatureDependencyController::class, 'destroy'])->name('destroy');
        Route::patch('/{featureDependency}/toggle', [\App\Http\Controllers\Api\V1\Admin\FeatureDependencyController::class, 'toggle'])->name('toggle');
        Route::get('/feature/{featureId}', [\App\Http\Controllers\Api\V1\Admin\FeatureDependencyController::class, 'getForFeature'])->name('for-feature');
    });

    // Dynamic Form Fields API
    Route::prefix('fields')->name('fields.')->group(function () {
        Route::get('/by-category/{id}', [CategoryController::class, 'getFieldsByCategory'])->name('by-category');
        Route::get('/render/{id}', [CategoryController::class, 'renderCategoryFields'])->name('render');
    });

    // Smart Categories API
    Route::prefix('smart-categories')->name('smart-categories.')->group(function () {
        Route::get('/default-types', [SearchController::class, 'getDefaultPropertyTypes'])->name('default-types');
        Route::get('/compatible-types', [SearchController::class, 'getCompatiblePropertyTypes'])->name('compatible-types');
    });

    // Nearby Preview API
    Route::get('/nearby/preview', [MapController::class, 'nearbyPreview'])->name('map.nearby.preview');

    // Marketing API - Phase 8.0: Pazarlama ve Sosyal Medya Motoru
    Route::prefix('marketing')->name('marketing.')->group(function () {
        // Dynamic Slogan API
        Route::prefix('slogan')->name('slogan.')->group(function () {
            Route::post('/ilan/{ilanId}/generate', [\App\Http\Controllers\Api\Marketing\SloganController::class, 'generate'])->name('generate');
            Route::post('/ilan/{ilanId}/variations', [\App\Http\Controllers\Api\Marketing\SloganController::class, 'variations'])->name('variations');
        });
    });

    // Reporting API (IX-006)
    Route::prefix('reporting')->name('reporting.')->group(function () {
        Route::get('/metrics', [\App\Http\Controllers\Api\Admin\ReportingController::class, 'getMetrics'])->name('metrics');
    });

    // Categories Children API
    Route::get('/categories/children', function (\Illuminate\Http\Request $request) {
        $parentId = $request->get('parent_id');
        if (! $parentId) {
            return response()->json(['success' => false, 'message' => 'parent_id required'], 400);
        }
        $children = \App\Models\IlanKategori::where('parent_id', $parentId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $children]);
    })->name('categories.children');

    // ✅ NEW: Category Path API (for UPS Template inheritance tree)
    Route::get('/categories/path/{id}', [CategoryController::class, 'getCategoryPath'])->name('categories.path');

    // ✅ NEW: Publication Types API (for UPS Template editor)
    Route::get('/categories/publication-types/{id}', [CategoryController::class, 'getPublicationTypesForUps'])->name('categories.publication-types');

    // 🎯 Phase 8.1: Template Field Visibility (UPS Form akıllı alan görünürlüğü)
    Route::prefix('template')->name('template.')->middleware(['throttle:api'])->group(function () {
        Route::get('/field-visibility/{kategoriId}', [\App\Http\Controllers\Api\V1\Admin\TemplateFieldVisibilityController::class, 'getVisibilityRules'])->name('field-visibility');
        Route::get('/field-visibility/{kategoriId}/{yayinTipiId?}', [\App\Http\Controllers\Api\V1\Admin\TemplateFieldVisibilityController::class, 'getVisibilityByYayinTipi'])->name('field-visibility-yayin-tipi');
        Route::get('/preview/{templateId}', [\App\Http\Controllers\Api\V1\Admin\TemplateFieldVisibilityController::class, 'previewTemplate'])->name('preview');
    });

    // Kişi API
    Route::prefix('api')->name('kisi.')->group(function () {
        Route::get('/kisi/search', function (\Illuminate\Http\Request $request) {
            $searchTerm = $request->get('search', '') ?: $request->get('q', '');
            $limit = $request->get('limit', 10);

            if (empty($searchTerm)) {
                return response()->json(['success' => true, 'data' => []]);
            }

            try {
                $results = \App\Modules\Crm\Services\KisiService::search($searchTerm, $limit);

                return response()->json([
                    'success' => true,
                    'data' => $results,
                    'count' => is_countable($results) ? count($results) : 0,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('search');

        Route::get('/kisi/owners', function (\Illuminate\Http\Request $request) {
            $searchTerm = $request->get('search', '') ?: $request->get('q', '');
            $limit = $request->get('limit', 20);

            try {
                if (empty($searchTerm)) {
                    $results = \App\Models\Kisi::where('aktiflik_durumu', 1) // ✅ SAB: durum → aktiflik_durumu
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get()
                        ->map(function ($kisi) {
                            $kisi->owner_score = \App\Modules\Crm\Services\KisiService::calculateOwnerScore($kisi);

                            return $kisi;
                        })
                        ->sortByDesc('owner_score');
                } else {
                    $results = \App\Modules\Crm\Services\KisiService::getPotentialOwners($searchTerm, $limit);
                }

                return response()->json([
                    'success' => true,
                    'data' => $results,
                    'count' => $results->count(),
                    'search_term' => $searchTerm,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('owners');

        Route::get('/kisi/{id}/owner-history', function (\Illuminate\Http\Request $request, $id) {
            try {
                $history = \App\Modules\Crm\Services\KisiService::getOwnerHistory($id);

                return response()->json([
                    'success' => true,
                    'data' => $history,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('owner-history');

        Route::post('/kisi/create', function (\Illuminate\Http\Request $request) {
            if (! auth()->check()) {
                return \App\Services\Response\ResponseService::unauthorized();
            }

            $validatedData = $request->validate([
                'ad' => 'required|string|max:255',
                'soyad' => 'required|string|max:255',
                'email' => 'nullable|email|unique:kisiler,email',
                'telefon' => 'nullable|string|max:20',
            ]);

            try {
                $kisiService = new \App\Modules\Crm\Services\KisiService;
                $kisi = $kisiService->createKisi($validatedData);

                return \App\Services\Response\ResponseService::success($kisi, 'Kişi oluşturuldu', 201);
            } catch (\Exception $e) {
                return \App\Services\Response\ResponseService::error($e->getMessage(), 500);
            }
        })->name('create');
    });

    // Live Search API
    Route::prefix('api')->name('search.')->group(function () {
        Route::get('/live-search', [IlanSearchController::class, 'liveSearch'])->name('live');
        Route::get('/ilceler', [IlanSearchController::class, 'getIlceler'])->name('ilceler');
        Route::get('/mahalleler', [IlanSearchController::class, 'getMahalleler'])->name('mahalleler');
    });

    // List APIs (Kisiler, Danışmanlar, Talepler, İlanlar)
    Route::prefix('api')->name('list.')->group(function () {
        Route::get('/kisiler', function (\Illuminate\Http\Request $request) {
            $perPage = (int) $request->get('per_page', 20);
            $perPage = max(1, min($perPage, 100));
            $data = \App\Models\Kisi::where('aktiflik_durumu', 1) // ✅ SAB: durum → aktiflik_durumu
                ->select('id', 'ad', 'soyad', 'telefon')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return \App\Services\Response\ResponseService::success($data, 'Kişiler listesi');
        })->name('kisiler');

        Route::get('/danismanlar', function (\Illuminate\Http\Request $request) {
            $perPage = (int) $request->get('per_page', 20);
            $perPage = max(1, min($perPage, 100));
            // Role ID 2 = danisman role
            $data = \App\Models\User::where('role_id', 2)
                ->where('aktiflik_durumu', 1)
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->paginate($perPage);

            return \App\Services\Response\ResponseService::success($data, 'Danışmanlar listesi');
        })->name('danismanlar');

        Route::get('/talepler', function (\Illuminate\Http\Request $request) {
            $perPage = (int) $request->get('per_page', 20);
            $perPage = max(1, min($perPage, 100));
            $data = \App\Models\Talep::where('yayin_durumu', 1)
                ->select('id', 'talep_adi', 'kategori_id')
                ->with('kategori:id,name')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return \App\Services\Response\ResponseService::success($data, 'Talepler listesi');
        })->name('talepler');

        Route::get('/ilanlar', function (\Illuminate\Http\Request $request) {
            $perPage = (int) $request->get('per_page', 20);
            $perPage = max(1, min($perPage, 100));
            $data = \App\Models\Ilan::where('yayin_durumu', 1)
                ->select('id', 'baslik', 'kategori_id', 'fiyat', 'para_birimi')
                ->with('kategori:id,name')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return \App\Services\Response\ResponseService::success($data, 'İlanlar listesi');
        })->name('ilanlar');
    });

    // AI Features
    Route::prefix('ai')->name('ai.')->group(function () {
        // ✅ REFACTORED: IlanController → IlanAIController (2026-01-29)
        Route::post('/ai-title', [IlanAIController::class, 'generateAiTitle'])->name('title');
        Route::post('/ai-description', [IlanAIController::class, 'generateAiDescription'])->name('description');
        Route::post('/quality-check', [\App\Http\Controllers\Admin\IlanAIQualityController::class, 'qualityCheck'])
            ->name('quality-check')->middleware(['throttle:20,1']);
        Route::post('/analyze-image', [\App\Http\Controllers\Admin\AI\LocalVisionController::class, 'analyze'])->name('analyze-image');
    });

    // ❌ DEPRECATED: Smart Calculator API (Removed 2026-01-12)
    // Reason: SmartCalculatorService was stub-only (all methods returned empty/false).
    // SmartCalculatorController also had no implementation.
    // Both service and controller have been deleted.
    // If you need calculator functionality, create a proper implementation.
    /*
    Route::prefix('calculator')->name('calculator.')->group(function () {
        Route::post('/calculate', function (\Illuminate\Http\Request $request) {
            $calculatorService = new \App\Services\SmartCalculatorService;
            $type = $request->input('type');
            $inputs = $request->input('inputs', []);
            try {
                $result = $calculatorService->calculate($type, $inputs);

                return \App\Services\Response\ResponseService::success($result, 'Hesaplama başarıyla tamamlandı');
            } catch (\Exception $e) {
                return \App\Services\Response\ResponseService::error($e->getMessage(), 400);
            }
        })->name('calculate');

        Route::get('/history', function (\Illuminate\Http\Request $request) {
            $calculatorService = new \App\Services\SmartCalculatorService;
            $type = $request->get('type');
            $limit = $request->get('limit', 20);
            $history = $calculatorService->getHistory($type, $limit);

            return \App\Services\Response\ResponseService::success($history);
        })->name('history');

        Route::get('/favorites', function () {
            $calculatorService = new \App\Services\SmartCalculatorService;
            $favorites = $calculatorService->getFavorites();

            return \App\Services\Response\ResponseService::success($favorites);
        })->name('favorites');

        Route::post('/favorites', function (\Illuminate\Http\Request $request) {
            $calculatorService = new \App\Services\SmartCalculatorService;
            $type = $request->input('type');
            $name = $request->input('name');
            $inputs = $request->input('inputs', []);
            $description = $request->input('description');
            $success = $calculatorService->addFavorite($type, $name, $inputs, $description);
            if ($success) {
                return \App\Services\Response\ResponseService::success(null, 'Favori hesaplama eklendi');
            }

            return \App\Services\Response\ResponseService::error('Favori hesaplama eklenemedi', 400);
        })->name('favorites.store');

        Route::delete('/favorites/{id}', function ($id) {
            $calculatorService = new \App\Services\SmartCalculatorService;
            $success = $calculatorService->removeFavorite($id);
            if ($success) {
                return \App\Services\Response\ResponseService::success(null, 'Favori hesaplama silindi');
            }

            return \App\Services\Response\ResponseService::error('Favori hesaplama silinemedi', 400);
        })->name('favorites.destroy');

        Route::get('/tax-rates', function (\Illuminate\Http\Request $request) {
            $calculatorService = new \App\Services\SmartCalculatorService;
            $type = $request->get('type');
            $rates = $calculatorService->getTaxRates($type);

            return \App\Services\Response\ResponseService::success($rates);
        })->name('tax-rates');

        Route::get('/commission-rates', function (\Illuminate\Http\Request $request) {
            $calculatorService = new \App\Services\SmartCalculatorService;
            $type = $request->get('type');
            $rates = $calculatorService->getCommissionRates($type);

            return \App\Services\Response\ResponseService::success($rates);
        })->name('commission-rates');

        Route::get('/types', function () {
            $calculatorService = new \App\Services\SmartCalculatorService;
            $types = $calculatorService->getCalculationTypes();

            return \App\Services\Response\ResponseService::success($types);
        })->name('types');
    });
    */

    // Notifications API
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
        Route::post('/{id}/read', [NotificationController::class, 'markAsReadApi'])->name('read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    });

    // TKGM Parsel Sorgulama (Context7: C7-TKGM-API-2025-12-04)
    Route::any('/properties/tkgm-lookup', function (\Illuminate\Http\Request $request, \App\Services\Integrations\TKGMService $service) {
        try {
            $lat = $request->input('lat');
            $lon = $request->input('lng') ?? $request->input('lon');

            if ($lat && $lon) {
                $result = $service->getParcelByCoordinates((float) $lat, (float) $lon);
                if ($result && ($result['success'] ?? false)) {
                    return response()->json($result);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'TKGM sorgusunda hata oluştu',
                    'error' => $result['error'] ?? null,
                ], 400);
            }

            $parcelNumber = $request->input('parcel_number');
            $districtCode = $request->input('district_code');
            $provinceCode = $request->input('province_code');
            $streetCode = $request->input('street_code');
            $blockNumber = $request->input('block_number');

            if ($parcelNumber && $districtCode && $provinceCode) {
                $result = $service->getParcelByCode($provinceCode, $districtCode, $streetCode, $blockNumber, $parcelNumber);
                if ($result && ($result['success'] ?? false)) {
                    return response()->json($result);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Geçersiz parametreler',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    })->name('properties.tkgm-lookup');

    // AI Monitoring Dashboard - JSON API Endpoints (Context7: C7-AI-MONITOR-API-2025-12-04)
    Route::prefix('ai-monitor')->name('ai-monitor.')->middleware('throttle:60,1')->group(function () {
        Route::get('/mcp', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'apiMcpDurumu'])->name('mcp');
        Route::get('/apis', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'apiApiDurumu'])->name('apis');
        Route::get('/self-healing', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'apiSelfHealing'])->name('self-healing');
        Route::get('/errors', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'apiRecentErrors'])->name('errors');
        Route::get('/code-health', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'apiCodeHealth'])->name('code-health');
        Route::get('/duplicates', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'apiDuplicateFiles'])->name('duplicates');
        Route::get('/conflicts', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'apiConflictingRoutes'])->name('conflicts');
        Route::get('/pages-health', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'apiPagesHealth'])->name('pages-health');
        Route::post('/run-context7-fix', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'runContext7Fix'])->name('run-context7-fix');
        Route::post('/apply-suggestion', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'applySuggestion'])->name('apply-suggestion');
        Route::get('/context7-rules', [\App\Http\Controllers\Admin\SystemMonitorController::class, 'getContext7Rules'])->name('context7-rules');
    });

    // 🎯 Reverse Matching: Potansiyel Alıcı İçin Mesaj Oluşturma
    Route::get('/ilanlar/{ilan}/generate-buyer-message/{talep}', function ($ilanId, $talepId) {
        try {
            $ilan = \App\Models\Ilan::findOrFail($ilanId);
            $talep = \App\Models\Talep::with('kisi')->findOrFail($talepId);

            // Get match data
            $demandMatcher = app(\App\Services\Matching\DemandMatchingEngine::class);
            $matches = $demandMatcher->findPotentialBuyers($ilan, 0); // Get all
            $matchData = $matches->firstWhere('talep.id', $talepId);

            if (!$matchData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Eşleşme bulunamadı'
                ], 404);
            }

            // Generate AI message
            $cortex = app(\App\Services\AI\YalihanCortex::class);
            $message = $cortex->generateBuyerOutreachMessage($ilan, $talep, $matchData);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Buyer message generation failed', [
                'ilan_id' => $ilanId,
                'talep_id' => $talepId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Mesaj oluşturulamadı: ' . $e->getMessage()
            ], 500);
        }
    })->name('ilanlar.generate-buyer-message');

    // 🎯 Cortex Action Center: Daily Actions API
    Route::get('/dashboard/actions', function () {
        try {
            $userId = auth()->id();
            $actionCenter = app(\App\Services\Dashboard\ActionCenterService::class);

            $actions = $actionCenter->getDailyActions($userId);
            $stats = $actionCenter->getStats($userId);

            return response()->json([
                'success' => true,
                'actions' => $actions,
                'stats' => $stats,
                'generated_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Action Center failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Aksiyonlar yüklenemedi: ' . $e->getMessage(),
                'actions' => [],
                'stats' => ['total' => 0],
            ], 500);
        }
    })->name('dashboard.actions');

    // ✅ SAB Phase 17C: Rental Management API
    Route::prefix('rental')->name('rental.')->group(function () {
        Route::get('/ev-kartlari/{id}/ozet', [\App\Http\Controllers\Api\V1\RentalController::class, 'ozet'])->name('ozet');
        Route::post('/gelir', [\App\Http\Controllers\Api\V1\RentalController::class, 'storeIncome'])->name('gelir');
        Route::post('/gider', [\App\Http\Controllers\Api\V1\RentalController::class, 'storeExpense'])->name('gider');
    });

    // 📊 Admin Observability API
    Route::prefix('observability')->name('observability.')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\Admin\ObservabilityController::class, 'stats'])->name('stats');
    });
});
