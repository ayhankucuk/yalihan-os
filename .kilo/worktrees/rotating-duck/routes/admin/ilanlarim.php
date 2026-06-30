<?php

use Illuminate\Support\Facades\Route;

// İlanlarım (My Listings)
Route::prefix('/ilanlarim')->name('ilanlarim.')->group(function () {
    Route::get('/ai-analysis', [\App\Http\Controllers\Admin\MyListingsController::class, 'aiAnalysis'])->name('ai-analysis');
    Route::get('/', [\App\Http\Controllers\Admin\MyListingsController::class, 'index'])->name('index');
    Route::post('/search', [\App\Http\Controllers\Admin\MyListingsController::class, 'search'])->name('search');
    Route::post('/bulk-action', [\App\Http\Controllers\Admin\MyListingsController::class, 'bulkAction'])->name('bulk.action');
    Route::get('/stats', [\App\Http\Controllers\Admin\MyListingsController::class, 'getStats'])->name('stats');
    Route::get('/export', [\App\Http\Controllers\Admin\MyListingsExportController::class, 'export'])->name('export');
});
