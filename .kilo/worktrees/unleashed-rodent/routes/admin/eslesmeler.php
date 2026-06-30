<?php

use App\Http\Controllers\Admin\EslesmeController;
use App\Http\Controllers\Admin\TalepPortfolyoController;
use App\Http\Controllers\Admin\MatchingFeedbackController;
use Illuminate\Support\Facades\Route;

// Eşleştirme Sistemi
Route::prefix('/eslesmeler')->name('eslesmeler.')->group(function () {
    Route::get('/', [EslesmeController::class, 'index'])->name('index');
    Route::get('/create', [EslesmeController::class, 'create'])->name('create');
    Route::post('/', [EslesmeController::class, 'store'])->name('store');
    Route::get('/{eslesme}', [EslesmeController::class, 'show'])->name('show');
    Route::get('/{eslesme}/edit', [EslesmeController::class, 'edit'])->name('edit');
    Route::put('/{eslesme}', [EslesmeController::class, 'update'])->name('update');
    Route::delete('/{eslesme}', [EslesmeController::class, 'destroy'])->name('destroy');

    // Özel eşleştirme işlemleri
    Route::get('/auto-match', [EslesmeController::class, 'autoMatch'])->name('auto-match');
    Route::post('/bulk-create', [EslesmeController::class, 'bulkCreate'])->name('bulk-create');

    // API endpoints for form data
    Route::prefix('/api')->name('api.')->group(function () {
        Route::get('/kisiler', [EslesmeController::class, 'getKisiler'])->name('kisiler');
        Route::get('/danismanlar', [EslesmeController::class, 'getDanismanlar'])->name('danismanlar');
        Route::get('/talepler', [EslesmeController::class, 'getTalepler'])->name('talepler');
        Route::post('/ai/eslesme-onerileri', [EslesmeController::class, 'getAIEslesmeOnerileri'])->name('ai.eslesme-onerileri');
    });
});

// Eşleştirmeler kısa yolu (yeni sisteme yönlendir)
Route::get('/eslesme', function () {
    return redirect()->route('admin.eslesmeler.index');
})->name('eslesme.index');

// Talep-Portföy Eşleştirme Routes
Route::prefix('/talep-portfolyo')->name('talep-portfolyo.')->group(function () {
    Route::get('/', [TalepPortfolyoController::class, 'index'])->name('index');
});

// Matching Feedback System (UI Learning Loop)
Route::prefix('/matching/feedback')->name('matching.feedback.')->group(function () {
    Route::post('/', [MatchingFeedbackController::class, 'store'])->name('store');
    Route::get('/history', [MatchingFeedbackController::class, 'history'])->name('history');
    Route::get('/stats', [MatchingFeedbackController::class, 'stats'])->name('stats');
    Route::post('/{id}/mark-result', [MatchingFeedbackController::class, 'markResult'])->name('mark-result');
});
