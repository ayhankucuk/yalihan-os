<?php

use App\Modules\Finans\Controllers\FinansalIslemController;
use App\Modules\Finans\Controllers\KomisyonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Finans API Routes
|--------------------------------------------------------------------------
|
| Context7 Standardı: C7-FINANS-API-2025-11-25
| AI-Powered Financial Management API
|
*/

// Health Check
Route::get('/finans/health', function () {
    return response()->json(['success' => true, 'module' => 'finans', 'ai_status' => true]);
})->name('api.finans.health');

// Financial Transactions (Finansal İşlemler)
Route::prefix('finans/islemler')->name('api.finans.islemler.')->middleware(['auth:sanctum'])->group(function () {
    // CRUD
    Route::get('/', [FinansalIslemController::class, 'index'])->name('index');
    Route::get('/{id}', [FinansalIslemController::class, 'show'])->name('show');
    Route::post('/', [FinansalIslemController::class, 'store'])->name('store');
    Route::put('/{id}', [FinansalIslemController::class, 'update'])->name('update');
    Route::delete('/{id}', [FinansalIslemController::class, 'destroy'])->name('destroy');

    // Status Management
    Route::post('/{id}/approve', [FinansalIslemController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [FinansalIslemController::class, 'reject'])->name('reject');
    Route::post('/{id}/complete', [FinansalIslemController::class, 'complete'])->name('complete');

    // 🤖 AI-Powered Endpoints
    Route::post('/ai/analyze', [FinansalIslemController::class, 'aiAnalyze'])->name('ai.analyze');
    Route::post('/ai/predict', [FinansalIslemController::class, 'aiPredict'])->name('ai.predict');
    Route::get('/{id}/ai/invoice', [FinansalIslemController::class, 'aiSuggestInvoice'])->name('ai.invoice');
    Route::post('/ai/risk', [FinansalIslemController::class, 'aiAnalyzeRisk'])->name('ai.risk');
    Route::post('/ai/summary', [FinansalIslemController::class, 'aiGenerateSummary'])->name('ai.summary');
});

// Commissions (Komisyonlar)
Route::prefix('finans/komisyonlar')->name('api.finans.komisyonlar.')->middleware(['auth:sanctum'])->group(function () {
    // CRUD
    Route::get('/', [KomisyonController::class, 'index'])->name('index');
    Route::get('/{id}', [KomisyonController::class, 'show'])->name('show');
    Route::post('/', [KomisyonController::class, 'store'])->name('store');
    Route::put('/{id}', [KomisyonController::class, 'update'])->name('update');
    Route::delete('/{id}', [KomisyonController::class, 'destroy'])->name('destroy');

    // Status Management
    Route::post('/{id}/approve', [KomisyonController::class, 'approve'])->name('approve');
    Route::post('/{id}/pay', [KomisyonController::class, 'pay'])->name('pay');
    Route::post('/{id}/recalculate', [KomisyonController::class, 'recalculate'])->name('recalculate');

    // 🤖 AI-Powered Endpoints
    Route::post('/ai/suggest-rate', [KomisyonController::class, 'aiSuggestRate'])->name('ai.suggest-rate');
    Route::post('/{id}/ai/optimize', [KomisyonController::class, 'aiOptimize'])->name('ai.optimize');
    Route::post('/ai/analyze', [KomisyonController::class, 'aiAnalyze'])->name('ai.analyze');
});
