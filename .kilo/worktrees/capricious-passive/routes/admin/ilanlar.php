<?php

use Illuminate\Support\Facades\Route;

// İlan ek routes
Route::prefix('/ilanlar')->name('ilanlar.')->group(function () {
    // CRUD Routes (Context7 Standard)
    Route::get('/', [\App\Http\Controllers\Admin\IlanCrudController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\IlanCrudController::class, 'create'])->name('create');
    // ✅ FIX-3 (SAB Sprint 2026-04-04): DUPLICATE STORE REMOVED
    // Route::resource('/ilanlar', ...) in admin.php:479 already provides admin.ilanlar.store
    // Having both causes duplicate route registration. Resource is SSOT.
    // Route::post('/', [\App\Http\Controllers\Admin\IlanCrudController::class, 'store'])->name('store');
    Route::get('/{ilan}', [\App\Http\Controllers\Admin\IlanCrudController::class, 'show'])->name('show');
    Route::get('/{ilan}/edit', [\App\Http\Controllers\Admin\IlanCrudController::class, 'edit'])->name('edit');
    Route::put('/{ilan}', [\App\Http\Controllers\Admin\IlanCrudController::class, 'update'])->name('update');
    Route::delete('/{ilan}', [\App\Http\Controllers\Admin\IlanCrudController::class, 'destroy'])->name('destroy');

    // AJAX & API Routes
    // ✅ REFACTORED: Search/Filter (Context7 Standard)
    Route::get('/search', [\App\Http\Controllers\Admin\IlanSearchController::class, 'search'])->name('search');
    Route::get('/filter', [\App\Http\Controllers\Admin\IlanSearchController::class, 'filter'])->name('filter');

    // ✅ REFACTORED: Bulk operations → IlanBulkController
    Route::post('/bulk-action', [\App\Http\Controllers\Admin\IlanBulkController::class, 'bulkAction'])->name('bulk.action');

    // ✅ REFACTORED: Status management → IlanPublishController (Context7: yayin_durumu)
    Route::post('/{ilan}/yayin-durumu-toggle', [\App\Http\Controllers\Admin\IlanPublishController::class, 'toggleYayinDurumu'])->name('yayin.toggle')->middleware(['can:edit-ilanlar', 'throttle:60,1']);
    Route::patch('/{ilan}/yayin-durumu', [\App\Http\Controllers\Admin\IlanPublishController::class, 'updateYayinDurumu'])->name('yayin.update')->middleware(['can:edit-ilanlar', 'throttle:60,1']);

    // Context7: Structured Data Routes (Konut) - Restored for Quality Gate
    Route::prefix('/{id}/structured-data/konut')->name('structured-data.konut.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Admin\KonutStructuredDataController::class, 'store'])->name('store');
        Route::post('/validate', [\App\Http\Controllers\Admin\KonutStructuredDataController::class, 'validateStructuredData'])->name('validate');
        Route::post('/approve', [\App\Http\Controllers\Admin\KonutStructuredDataController::class, 'approve'])->name('approve');
        Route::post('/title', [\App\Http\Controllers\Admin\KonutStructuredDataController::class, 'generateTitle'])->name('title');
    });

    // Context7: Structured Data Routes (Yazlik) - Restored for Quality Gate
    Route::prefix('/{id}/structured-data/yazlik')->name('structured-data.yazlik.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Admin\YazlikStructuredDataController::class, 'store'])->name('store');
        Route::post('/validate', [\App\Http\Controllers\Admin\YazlikStructuredDataController::class, 'validateStructuredData'])->name('validate');
        Route::post('/approve', [\App\Http\Controllers\Admin\YazlikStructuredDataController::class, 'approve'])->name('approve');
        Route::post('/title', [\App\Http\Controllers\Admin\YazlikStructuredDataController::class, 'generateTitle'])->name('title');
    });

    // Context7: Rapor Refresh Route
    Route::post('/{ilan}/rapor/refresh', [\App\Http\Controllers\Admin\IlanRaporController::class, 'refresh'])->name('rapor.refresh');


    // Segment-based workflow routes
    Route::prefix('/segments')->name('segments.')->group(function () {
        // Yeni ilan oluşturma
        Route::get('/create', [\App\Http\Controllers\Admin\IlanSegmentController::class, 'create'])->name('create');
        Route::get('/create/{segment}', [\App\Http\Controllers\Admin\IlanSegmentController::class, 'showCreate'])->name('create.segment');
        Route::post('/create/{segment}', [\App\Http\Controllers\Admin\IlanSegmentController::class, 'storeCreate'])->name('store.create');

        // Mevcut ilan düzenleme
        Route::get('/{ilan}', [\App\Http\Controllers\Admin\IlanSegmentController::class, 'show'])->name('show');
        Route::get('/{ilan}/{segment}', [\App\Http\Controllers\Admin\IlanSegmentController::class, 'showEdit'])->name('show.segment');
        Route::post('/{ilan}/{segment}', [\App\Http\Controllers\Admin\IlanSegmentController::class, 'store'])->name('store');
    });

    // İlan Create API Routes (Context7 Standard)
    Route::prefix('/api')->name('api.')->group(function () {
        // ❌ REMOVED: FeaturesController::getFeaturesByCategory (UPS Phase 4 Decommission)
        // ✅ REPLACEMENT: Use /api/admin/features/by-category (IlanFeatureService)
        Route::get('/categories/publication-types/{categoryId}', [\App\Http\Controllers\Api\CategoryController::class, 'getPublicationTypes'])->name('categories.publication-types');
        Route::get('/categories/{parentId}/subcategories', [\App\Http\Controllers\Api\CategoryController::class, 'getSubcategories'])->name('categories.subcategories');
    });
/*
    // Export Routes - REMOVED (Ghost routes - methods missing in IlanCrudController)
    Route::get('/export/excel', [\App\Http\Controllers\Admin\IlanCrudController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [\App\Http\Controllers\Admin\IlanCrudController::class, 'exportPdf'])->name('export.pdf');
    Route::get('/export', [\App\Http\Controllers\Admin\IlanCrudController::class, 'exportExcel'])->name('export');
*/

    // Legacy Routes
    // ✅ REFACTORED: Publishing operations → IlanPublishController (Context7: yayin_durumu)
    Route::post('/save-draft', [\App\Http\Controllers\Admin\IlanPublishController::class, 'saveDraft'])->name('save-draft')->middleware(['auth', 'throttle:30,1']);
    Route::post('/auto-save', [\App\Http\Controllers\Admin\IlanPublishController::class, 'autoSave'])->name('auto-save')->middleware(['auth', 'throttle:30,1']);
    // ❌ REMOVED: Duplicate yayin-durumu route (already defined on line 386)

    // ✅ REFACTORED: Bulk operations → IlanBulkController
    Route::post('/bulk-update', [\App\Http\Controllers\Admin\IlanBulkController::class, 'bulkUpdate'])->name('bulk-update')->middleware(['can:manage-ilanlar', 'throttle:60,1']);
    Route::post('/bulk-delete', [\App\Http\Controllers\Admin\IlanBulkController::class, 'bulkDelete'])->name('bulk-delete')->middleware(['can:manage-ilanlar', 'throttle:60,1']);
    Route::get('/live-search', [\App\Http\Controllers\Admin\IlanSearchController::class, 'liveSearch'])->name('live-search');
    Route::post('/generate-ai-title', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'generateAiTitle'])->name('generate-ai-title');
    Route::post('/generate-ai-description', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'generateAiDescription'])->name('generate-ai-description');
/*
    Route::post('/convert-price-to-text', [\App\Http\Controllers\Admin\IlanCrudController::class, 'convertPriceToText'])->name('convert-price-to-text');
*/
    Route::post('/{ilan}/ai-copy', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'generateAiCopy'])->name('ai-copy');
    // Phase D: AI-Assisted Publish Control (UPS SSOT + Cortex observer mode)
    Route::post('/{ilan}/publish', [\App\Http\Controllers\Admin\IlanPublishGateController::class, 'publish'])->name('publish')->middleware(['throttle:10,1']);
/*
    Route::get('/{ilan}/price-history', [\App\Http\Controllers\Admin\IlanCrudController::class, 'priceHistoryApi'])->name('price-history');
*/
    // ✅ REFACTORED: Publishing operations → IlanPublishController
    Route::post('/{ilan}/refresh-rate', [\App\Http\Controllers\Admin\IlanPublishController::class, 'refreshRate'])->name('refresh-rate');
    Route::post('/{ilan}/duplicate', [\App\Http\Controllers\Admin\IlanPublishController::class, 'duplicate'])->name('duplicate');

    // ✅ REFACTORED: Photo management → specialized controller
    Route::post('/{ilan}/upload-photos', [\App\Http\Controllers\Admin\PhotoController::class, 'uploadPhotos'])->name('upload-photos');
    Route::delete('/{ilan}/photos/{photo}', [\App\Http\Controllers\Admin\PhotoController::class, 'deletePhoto'])->name('delete-photo');
    // Context7: forbidden pattern fixed - using sequence instead
    Route::post('/{ilan}/update-photo-sequence', [\App\Http\Controllers\Admin\PhotoController::class, 'updatePhotoSequence'])->name('update-photo-sequence');
    Route::get('/ilanlarim', [\App\Http\Controllers\Admin\MyListingsController::class, 'index'])->name('ilanlarim');
});
