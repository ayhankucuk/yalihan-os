<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/ups')->name('admin.ups.')->middleware(['web', 'auth', 'admin', 'role:admin', 'verified', 'throttle:30,1'])->group(function () {
    Route::get('/governance', fn() => redirect()->route('admin.governance.feature-health'))->name('governance.index');
    Route::post('/governance/generate-proposals', fn() => redirect()->route('admin.governance.feature-health.generate-proposals'))->name('governance.generate-proposals');
    // Route::get('/advanced', [\App\Http\Controllers\Admin\UpsTemplateManagerController::class, 'advanced'])->name('advanced');

    // ❌ DEPRECATED (2026-01-25): UPS Feature Manager - Consolidated into Property Hub
    // ✅ REDIRECT: All /ups/features/* → /property-hub/features/*
    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/dependencies', fn() => redirect()->route('admin.property-hub.features.index'))->name('dependencies');
        Route::get('/', fn() => redirect()->route('admin.property-hub.features.index'))->name('index');
        Route::get('/create', fn() => redirect()->route('admin.property-hub.features.create'))->name('create');
        Route::any('/{feature}', fn($feature) => redirect()->route('admin.property-hub.features.edit', $feature))->name('edit');
    });

    // ✅ UPS Feature Packs (Phase L+1)
    Route::prefix('feature-packs')->name('feature-packs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'store'])->name('store');
        Route::put('/{pack}', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'update'])->name('update');
        // Route::patch('/{pack}/durum', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'toggleDurum'])->name('durum');
        Route::post('/{pack}/features', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'addFeature'])->name('add-feature');
        Route::delete('/{pack}/features/{feature}', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'removeFeature'])->name('remove-feature');
        Route::post('/preview', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'preview'])->name('preview');
        Route::post('/apply', [\App\Http\Controllers\Admin\UpsFeaturePackController::class, 'apply'])->name('apply');
    });

    // UPS Template Manager — SAB PURGE: UpsTemplateManagerController kaldırıldı, TemplateController kullanılıyor
    // ✅ UPS Versioning (Phase N)
    Route::prefix('versions')->name('versions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpsVersionController::class, 'index'])->name('index');
        Route::get('/history', [\App\Http\Controllers\Admin\UpsVersionController::class, 'history'])->name('history');
        Route::post('/{version}/rollback', [\App\Http\Controllers\Admin\UpsVersionController::class, 'rollback'])->name('rollback');
    });

    // ✅ Phase 8.0: Marketing Asset Templates (Template Editor & Preview)
    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'index'])->name('index');
            Route::get('/edit', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'edit'])->name('edit');
            Route::post('/', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'store'])->name('store');
            Route::put('/', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'update'])->name('update');
            Route::delete('/', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'destroy'])->name('destroy');
            Route::post('/preview', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'preview'])->name('preview');
        });

        // ✨ Ilan-specific asset generation
        Route::prefix('assets')->name('assets.')->group(function () {
            Route::post('/ilanlar/{ilan}/generate', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'generateForListing'])->name('generate');
            Route::get('/ilanlar/{ilan}', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'getListingAssets'])->name('listing');
            Route::delete('/ilanlar/{ilan}', [\App\Http\Controllers\Admin\MarketingAssetController::class, 'deleteListingAssets'])->name('delete');
        });
    });

    // ✅ UPS Policies (Phase L+3)
    Route::get('/policies', [\App\Http\Controllers\Admin\UpsPolicyController::class, 'index'])->name('policies');

    // ✅ UPS Packs (Phase L+1 Alias)
    // ✅ UPS Packs (Phase L+1 Alias) & Feature Management
    Route::group(['prefix' => 'packs', 'as' => 'packs.'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\UpsPackController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\UpsPackController::class, 'store'])->name('store');
        Route::get('/{pack}/features', [\App\Http\Controllers\Admin\UpsPackController::class, 'getFeatures'])->name('features.get');
        Route::post('/{pack}/features', [\App\Http\Controllers\Admin\UpsPackController::class, 'updateFeatures'])->name('features.update');
        Route::delete('/{pack}', [\App\Http\Controllers\Admin\UpsPackController::class, 'destroy'])->name('destroy');
    });

    // ✅ UPS Audit Log (Phase N)
    Route::get('/audit-log', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'index'])->name('audit-log');
    Route::get('/audit-log/{auditLog}', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'show'])->name('audit-log.show');
    Route::delete('/audit-log/{auditLog}', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'destroy'])->name('audit-log.destroy');
    Route::post('/audit-log/export', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'export'])->name('audit-log.export');
    Route::post('/audit-log/cleanup', [\App\Http\Controllers\Admin\UPS\AuditLogController::class, 'cleanup'])->name('audit-log.cleanup');

    // ✅ UPS Health Check (Phase S - System Integrity)
    Route::get('/health', [\App\Http\Controllers\Admin\UpsHealthController::class, 'index'])->name('health');
    Route::post('/health/repair', [\App\Http\Controllers\Admin\UpsHealthController::class, 'repair'])->name('health.repair');

    // ✅ UPS Analytics (Dashboard) - Redirected to Property Hub Analytics
    Route::get('/analytics', fn() => redirect()->route('admin.property-hub.analytics'))->name('analytics');
});
