<?php

use App\Modules\Finans\Controllers\KomisyonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Komisyonlar API Routes
|--------------------------------------------------------------------------
|
| Admin commission management endpoints
|
*/

Route::prefix('admin/komisyonlar')->middleware(['auth:sanctum', 'can:manage-ilanlar'])->group(function () {
    Route::get('/', [KomisyonController::class, 'index'])->name('api.admin.komisyonlar.index');
    Route::get('/{id}', [KomisyonController::class, 'show'])->name('api.admin.komisyonlar.show');
    Route::post('/', [KomisyonController::class, 'store'])->name('api.admin.komisyonlar.store');
    Route::put('/{id}', [KomisyonController::class, 'update'])->name('api.admin.komisyonlar.update');
    Route::delete('/{id}', [KomisyonController::class, 'destroy'])->name('api.admin.komisyonlar.destroy');

    // Status management
    Route::post('/{id}/approve', [KomisyonController::class, 'approve'])->name('api.admin.komisyonlar.approve');
    Route::post('/{id}/pay', [KomisyonController::class, 'pay'])->name('api.admin.komisyonlar.pay');
    Route::post('/{id}/recalculate', [KomisyonController::class, 'recalculate'])->name('api.admin.komisyonlar.recalculate');

    // AI endpoints
    Route::post('/ai/suggest-rate', [KomisyonController::class, 'aiSuggestRate'])->name('api.admin.komisyonlar.ai.suggest-rate');
    Route::post('/{id}/ai/optimize', [KomisyonController::class, 'aiOptimize'])->name('api.admin.komisyonlar.ai.optimize');
    Route::post('/ai/analyze', [KomisyonController::class, 'aiAnalyze'])->name('api.admin.komisyonlar.ai.analyze');
});
