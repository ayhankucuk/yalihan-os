<?php

use App\Http\Controllers\Admin\SiteApartmanController;
use App\Http\Controllers\Admin\YazlikKiralamaController;
use App\Http\Controllers\Admin\TakvimController;
use Illuminate\Support\Facades\Route;

// Site/Apartman Management Routes
Route::prefix('/site-apartman')->name('site-apartman.')->group(function () {
    Route::get('/', [SiteApartmanController::class, 'index'])->name('index');
    Route::get('/create', [SiteApartmanController::class, 'create'])->name('create');
    Route::post('/', [SiteApartmanController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [SiteApartmanController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SiteApartmanController::class, 'update'])->name('update');
    Route::delete('/{id}', [SiteApartmanController::class, 'destroy'])->name('destroy');

    Route::get('/{id}/ilceler', [SiteApartmanController::class, 'getIlceler'])->name('ilceler');
    Route::get('/{id}/mahalleler', [SiteApartmanController::class, 'getMahalleler'])->name('mahalleler');
    Route::get('/search', [SiteApartmanController::class, 'search'])->name('search');
});

// Yazlik Kiralama Management Routes
Route::prefix('/yazlik-kiralama')->name('yazlik-kiralama.')->group(function () {
    // Bookings Management
    Route::get('/bookings/{id?}', [YazlikKiralamaController::class, 'bookings'])->name('bookings');

    // Takvim - Calendar View
    Route::prefix('/takvim')->name('takvim.')->group(function () {
        Route::get('/', [TakvimController::class, 'index'])->name('index');
        Route::get('/sezonlar', [TakvimController::class, 'sezonlar'])->name('sezonlar');
        Route::post('/sezon/store', [TakvimController::class, 'storeSezon'])->name('sezon.store');
        Route::put('/sezon/{id}', [TakvimController::class, 'updateSezon'])->name('sezon.update');
        Route::delete('/sezon/{id}', [TakvimController::class, 'destroySezon'])->name('sezon.destroy');
    });

    // Resource routes
    Route::get('/', [YazlikKiralamaController::class, 'index'])->name('index');
    Route::get('/create', [YazlikKiralamaController::class, 'create'])->name('create');
    Route::post('/store', [YazlikKiralamaController::class, 'store'])->name('store');
    Route::get('/{id}', [YazlikKiralamaController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [YazlikKiralamaController::class, 'edit'])->name('edit');
    Route::put('/{id}', [YazlikKiralamaController::class, 'update'])->name('update');
    Route::delete('/{id}', [YazlikKiralamaController::class, 'destroy'])->name('destroy');
});
