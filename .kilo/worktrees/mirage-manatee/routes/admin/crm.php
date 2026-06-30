<?php

use App\Http\Controllers\Admin\CRMController;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->name('crm.')->group(function () {
    // CRM Dashboard — Canonical entrypoint
    Route::get('/', [CRMController::class, 'index'])->name('dashboard');

    // CRM Dashboard Features (Consolidated from CRMDashboardController)
    Route::get('/pipeline', [CRMController::class, 'pipeline'])->name('pipeline');
    Route::get('/lead-sources', [CRMController::class, 'leadSourceAnalytics'])->name('lead-sources');
    Route::post('/recalculate-scores', [CRMController::class, 'recalculateScores'])->name('recalculate-scores');

    // CRM AJAX Actions (Pipeline & Segment mutations via KisiScoringService)
    Route::post('/kisi/{kisi}/update-pipeline', [CRMController::class, 'updatePipelineStage'])->name('update-pipeline');
    Route::post('/kisi/{kisi}/update-segment', [CRMController::class, 'updateSegment'])->name('update-segment');

    // CRM Pipeline (Kanban) Routes - Phase 6
    Route::prefix('pipeline')->name('pipeline.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'index'])->name('index');
        Route::post('/{kisi}/update-stage', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'updateStage'])->name('update-stage');
        Route::post('/{kisi}/quick-note', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'quickNote'])->name('quick-note');
        Route::get('/statistics', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'statistics'])->name('statistics');
        Route::get('/{kisi}/details', [\App\Http\Controllers\Admin\CRM\PipelineController::class, 'getPersonDetails'])->name('person-details');
    });

    // CRM Activity Timeline Routes - Phase 6
    Route::prefix('people')->name('people.')->group(function () {
        Route::get('/{kisi}/activities', [\App\Http\Controllers\Admin\CRM\ActivityController::class, 'getActivities'])->name('activities');
        Route::post('/{kisi}/activities', [\App\Http\Controllers\Admin\CRM\ActivityController::class, 'storeActivity'])->name('activities.store');
        Route::delete('/{kisi}/activities/{activity}', [\App\Http\Controllers\Admin\CRM\ActivityController::class, 'deleteActivity'])->name('activities.delete');
        Route::get('/{kisi}/activities/stats', [\App\Http\Controllers\Admin\CRM\ActivityController::class, 'getActivityStats'])->name('activities.stats');
    });

    // T3-B: CRM Customer route aliases → canonical Kişi routes
    // Resolves admin.crm.customers.* references in CRM views
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.kisiler.index'))->name('index');
        Route::get('/create', fn () => redirect()->route('admin.kisiler.create'))->name('create');
        Route::get('/{kisiId}', fn ($kisiId) => redirect()->route('admin.kisiler.show', ['kisiId' => $kisiId]))->whereNumber('kisiId')->name('show');
        Route::get('/{kisiId}/edit', fn ($kisiId) => redirect()->route('admin.kisiler.edit', ['kisiId' => $kisiId]))->whereNumber('kisiId')->name('edit');
    });
});

// Legacy Satislar redirect to canonical CRM dashboard
Route::get('/satislar/create', function () {
    return redirect()->route('admin.crm.dashboard');
})->name('satislar.create');

