<?php

use App\Http\Controllers\Admin\AyarlarController;
use Illuminate\Support\Facades\Route;

// Sistem Ayarları
Route::prefix('/ayarlar')->name('ayarlar.')->group(function () {
    Route::get('/', [AyarlarController::class, 'index'])->name('index');
    Route::get('/create', [AyarlarController::class, 'create'])->name('create');
    Route::post('/', [AyarlarController::class, 'store'])->name('store');
    Route::post('/bulk-store', [AyarlarController::class, 'bulkStore'])->name('bulk-store');
    Route::post('/bulk-update', [AyarlarController::class, 'bulkUpdate'])->name('bulk-update');
    Route::post('/clear-caches', [AyarlarController::class, 'clearCaches'])->name('clear-caches');

    // Enterprise Locale & Currency Control
    Route::post('/languages/toggle', [AyarlarController::class, 'toggleLanguage'])->name('languages.toggle');
    Route::post('/languages/set-default', [AyarlarController::class, 'setDefaultLanguage'])->name('languages.set-default');
    Route::post('/currencies/toggle', [AyarlarController::class, 'toggleCurrency'])->name('currencies.toggle');
    Route::post('/currencies/set-default', [AyarlarController::class, 'setDefaultCurrency'])->name('currencies.set-default');
    Route::get('/{ayar}', [AyarlarController::class, 'show'])->name('show');
    Route::get('/{ayar}/edit', [AyarlarController::class, 'edit'])->name('edit');
    Route::put('/{ayar}', [AyarlarController::class, 'update'])->name('update');
    Route::delete('/{ayar}', [AyarlarController::class, 'destroy'])->name('destroy');
});
