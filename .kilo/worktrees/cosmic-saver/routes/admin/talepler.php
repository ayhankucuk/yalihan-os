<?php

use App\Http\Controllers\Admin\TalepController;
use Illuminate\Support\Facades\Route;

// Talepler Yönetimi
Route::prefix('/talepler')->name('talepler.')->group(function () {
    Route::get('/', [TalepController::class, 'index'])->name('index');
    Route::get('/create', [TalepController::class, 'create'])->name('create');
    Route::post('/', [TalepController::class, 'store'])->name('store');
    Route::get('/{talep}', [TalepController::class, 'show'])->name('show');
    Route::get('/{talep}/edit', [TalepController::class, 'edit'])->name('edit');
    Route::put('/{talep}', [TalepController::class, 'update'])->name('update');
    Route::delete('/{talep}', [TalepController::class, 'destroy'])->name('destroy');
    Route::get('/{talep}/eslesen', [TalepController::class, 'eslesen'])->name('eslesen');
    Route::get('/{talep}/matches', [TalepController::class, 'showMatches'])->name('matches'); // 🎯 Eşleşme Kokpiti
    Route::get('/search', [TalepController::class, 'search'])->name('search');
    Route::post('/bulk-action', [TalepController::class, 'bulkAction'])->name('talep.bulk.action');
});

Route::prefix('/taleplerim')->name('taleplerim.')->group(function () {
    Route::get('/', [TalepController::class, 'index'])->name('index');
});
