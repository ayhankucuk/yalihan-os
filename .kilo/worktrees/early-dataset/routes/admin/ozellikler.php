<?php

use Illuminate\Support\Facades\Route;

    Route::prefix('/ozellikler')->name('ozellikler.')->group(function () {
        // ✅ Main redirect: /admin/ozellikler → /admin/features-management
        Route::get('/', function () {
            return redirect()->route('admin.features-management.index');
        })->name('index');

        // ✅ Legacy feature UI redirect
        Route::get('/features', function () {
            return redirect()->route('admin.features-management.index');
        })->name('features.index');

        // ✅ Kategoriler - Legacy UI (AI semantic labeling için korunuyor)
        // ✅ SAB: Feature categories hala kullanılıyor (UPS'te semantic grouping için)
        Route::prefix('/kategoriler')->name('kategoriler.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'destroy'])->name('destroy');
            Route::get('/kategorisiz-ozellikler', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'kategorisizOzellikler'])->name('kategorisiz');
            Route::get('/{id}/ozellikler', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'ozellikler'])->name('ozellikler');
            Route::post('/{kategori}/toggle-durum', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'toggleDurum'])->name('toggle-durum');
            Route::post('/sirala', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'reorder'])->name('sirala');
            Route::post('/check-slug', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'checkSlug'])->name('slug.check');
            Route::post('/{id}/quick-update', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'quickUpdate'])->name('quick-update');
            Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'duplicate'])->name('duplicate');
            Route::post('/bulk-toggle-durum', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'bulkToggleDurum'])->name('bulk-toggle-durum');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'bulkDelete'])->name('bulk-delete');
            Route::get('/stats', [\App\Http\Controllers\Admin\OzellikKategoriController::class, 'stats'])->name('stats');

            // ✅ SAB AI Integration for Categories
/*
            Route::post('/ai-analysis', [\App\Http\Controllers\Admin\FeatureController::class, 'analyzeCategories'])->name('ai-analysis');
            Route::post('/train-categories', [\App\Http\Controllers\Admin\FeatureController::class, 'trainCategories'])->name('train-categories');
*/
        });

        // ✅ Features - Standard CRUD (Required by category feature list)
        Route::prefix('/features')->name('features.')->group(function () {
            Route::get('/create', [\App\Http\Controllers\Admin\OzellikController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\OzellikController::class, 'store'])->name('store');
            Route::get('/{feature}/edit', [\App\Http\Controllers\Admin\OzellikController::class, 'edit'])->name('edit');
            Route::put('/{feature}', [\App\Http\Controllers\Admin\OzellikController::class, 'update'])->name('update');
            Route::delete('/{feature}', [\App\Http\Controllers\Admin\OzellikController::class, 'destroy'])->name('destroy');
        });

        // ✅ SAB AI Feature Routes
        // ⚠️ DEPRECATED: suggestFeatures, smartSearch, categorizeFeatures moved to API (AdminAIController)
        // Use /api/v1/suggest-features instead
        Route::prefix('/context7')->name('context7.')->group(function () {
            // Route::post('/suggest', [\App\Http\Controllers\Admin\FeatureController::class, 'suggestFeatures'])->name('suggest');
            // Route::post('/search', [\App\Http\Controllers\Admin\FeatureController::class, 'smartSearch'])->name('search');
            // Route::post('/categorize', [\App\Http\Controllers\Admin\FeatureController::class, 'categorizeFeatures'])->name('categorize');
/*
            Route::get('/training-durum', [\App\Http\Controllers\Admin\FeatureController::class, 'getTrainingStatus'])->name('training-durum');
            Route::post('/train/behavior', [\App\Http\Controllers\Admin\FeatureController::class, 'trainUserBehavior'])->name('train.behavior');
            Route::post('/train/market', [\App\Http\Controllers\Admin\FeatureController::class, 'trainMarketTrends'])->name('train.market');
*/
        });

        // ✅ Özellikler - Legacy bulk actions (OzellikController)
        Route::prefix('/ozellik')->name('ozellik.')->group(function () {
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\OzellikController::class, 'bulkAction'])->name('bulk.action');
        });
    });
