<?php

use App\Modules\Finans\Controllers\FinansalIslemController;
use App\Modules\Finans\Controllers\KomisyonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Finans Web Routes
|--------------------------------------------------------------------------
|
| Context7 Standardı: C7-FINANS-WEB-2025-11-25
| AI-Powered Financial Management Web Interface
|
*/

// Health Check
Route::get('/admin/finans/health', function () {
    return response('ok', 200);
})->name('admin.finans.health');

// Financial Transactions (Admin Panel)
Route::prefix('admin/finans/islemler')->name('admin.finans.islemler.')->middleware(['auth', 'web'])->group(function () {
    Route::get('/', [FinansalIslemController::class, 'index'])->name('index');
    Route::get('/create', function () {
        return view('admin.finans.islemler.create');
    })->name('create');
    Route::get('/{id}', [FinansalIslemController::class, 'show'])->name('show');
    Route::get('/{id}/edit', function ($id) {
        return view('admin.finans.islemler.edit', ['id' => $id]);
    })->name('edit');

    // AI Endpoints (Web)
    Route::post('/ai/analyze', [FinansalIslemController::class, 'aiAnalyze'])->name('ai.analyze');
    Route::post('/ai/predict', [FinansalIslemController::class, 'aiPredict'])->name('ai.predict');
    Route::get('/{id}/ai/invoice', [FinansalIslemController::class, 'aiSuggestInvoice'])->name('ai.invoice');
});

// Commissions (Admin Panel)
Route::prefix('admin/finans/komisyonlar')->name('admin.finans.komisyonlar.')->middleware(['auth', 'web'])->group(function () {
    Route::get('/', [KomisyonController::class, 'index'])->name('index');
    Route::get('/create', function () {
        return view('admin.finans.komisyonlar.create');
    })->name('create');
    Route::get('/{id}', [KomisyonController::class, 'show'])->name('show');

    // AI Endpoints (Web)
    Route::post('/ai/suggest-rate', [KomisyonController::class, 'aiSuggestRate'])->name('ai.suggest-rate');
    Route::post('/{id}/ai/optimize', [KomisyonController::class, 'aiOptimize'])->name('ai.optimize');
});
