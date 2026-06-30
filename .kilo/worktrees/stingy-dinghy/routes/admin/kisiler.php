<?php

use App\Http\Controllers\Admin\KisiController;
use Illuminate\Support\Facades\Route;

// Primary Kişiler (Primary: admin.kisiler.show)
Route::prefix('/kisiler')->name('kisiler.')->group(function () {
    Route::get('/', [KisiController::class, 'index'])->name('index');
    Route::get('/create', [KisiController::class, 'create'])->name('create');
    Route::get('/create-context7', function () {
        return view('admin.kisiler.create-context7');
    })->name('create-context7');
    Route::post('/', [KisiController::class, 'store'])->name('store');
    Route::get('/search', [KisiController::class, 'search'])->name('search');
    Route::post('/check-duplicate', [KisiController::class, 'checkDuplicate'])->name('check-duplicate');
    Route::post('/bulk-action', [KisiController::class, 'bulkAction'])->name('kisi.bulk.action');
    Route::post('/ai-analyze', [KisiController::class, 'aiAnalyze'])->name('ai-analyze');
    Route::get('/takip', [KisiController::class, 'takip'])->name('takip');
    Route::get('/{kisiId}', [KisiController::class, 'show'])->whereNumber('kisiId')->name('show');
    Route::get('/{kisiId}/edit', [KisiController::class, 'edit'])->whereNumber('kisiId')->name('edit');
    Route::put('/{kisiId}', [KisiController::class, 'update'])->whereNumber('kisiId')->name('update');
    Route::delete('/{kisiId}', [KisiController::class, 'destroy'])->whereNumber('kisiId')->name('destroy');
});

// Kişilerim Route
Route::prefix('kisilerim')->name('kisilerim.')->group(function () {
    Route::get('/', [KisiController::class, 'kisilerim'])->name('index');
});

// Legacy aliases (admin.kisiler.* legacy redirects/actions)
Route::post('/kisiler-legacy-store', [KisiController::class, 'store'])->name('admin.kisiler.store.legacy');
Route::put('/kisiler-legacy-update/{kisi}', [KisiController::class, 'update'])->whereNumber('kisi')->name('admin.kisiler.update.legacy');
Route::delete('/kisiler-legacy-destroy/{kisi}', [KisiController::class, 'destroy'])->whereNumber('kisi')->name('admin.kisiler.destroy.legacy');
