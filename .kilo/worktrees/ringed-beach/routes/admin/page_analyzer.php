<?php

use Illuminate\Support\Facades\Route;

Route::prefix('/page-analyzer')->name('page-analyzer.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'index'])->name('index');
    Route::get('/dashboard', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'dashboard'])->name('dashboard');
    Route::get('/create', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'destroy'])->name('destroy');

    Route::get('/export/{id?}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'export'])->name('export');
    Route::get('/download', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'download'])->name('download');
    Route::post('/rerun/{id}', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'rerun'])->name('rerun');
    Route::get('/metrics', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'metrics'])->name('metrics');
    Route::get('/health', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'health'])->name('health');
    Route::get('/recommendations', [\App\Http\Controllers\Admin\PageAnalyzerController::class, 'recommendations'])->name('recommendations');
});
