<?php

use Illuminate\Support\Facades\Route;

Route::prefix('adres-yonetimi')->name('adres-yonetimi.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'index'])->name('index');
    Route::get('/ulkeler', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getUlkeler'])->name('ulkeler');
    Route::get('/bolgeler', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getBolgeler'])->name('bolgeler');
    Route::get('/iller', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIller'])->name('iller');
    Route::get('/iller/{ulkeId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIllerByUlke'])->name('iller.by-ulke');
    Route::get('/ilceler', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIlceler'])->name('ilceler');
    Route::get('/ilceler/{ilId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIlcelerByIl'])->name('ilceler.by-il');
    Route::get('/mahalleler', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getMahalleler'])->name('mahalleler');
    Route::get('/mahalleler/{ilceId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getMahallelerByIlce'])->name('mahalleler.by-ilce');

    Route::post('/sync-from-turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'syncFromTurkiyeAPI'])->name('sync-from-turkiyeapi');
    Route::post('/fetch-from-turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'fetchFromTurkiyeAPI'])->name('fetch-from-turkiyeapi');
    Route::get('/ilceler/{ilId}/turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getIlcelerByIlFromTurkiyeAPI'])->name('ilceler.by-il.turkiyeapi');
    Route::get('/all-location-types/{ilceId}/turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'getAllLocationTypesFromTurkiyeAPI'])->name('all-location-types.turkiyeapi');

    Route::get('/api/provinces', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'provinces'])->name('api.provinces');
    Route::get('/api/districts/{provinceApiId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'districts'])->name('api.districts');
    Route::get('/api/neighborhoods/{districtApiId}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'neighborhoods'])->name('api.neighborhoods');
    Route::put('/api/neighborhoods/{id}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'updateNeighborhood'])->name('api.neighborhoods.update');
    Route::post('/api/sync-all', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'syncAll'])->name('api.sync-all');
    Route::post('/api/fetch-from-turkiyeapi', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'fetchFromTurkiyeAPI'])->name('api.fetch-from-turkiyeapi');

    Route::get('/create/{type}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'create'])->name('create');
    Route::get('/{type}/{id}/edit', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'edit'])->name('edit');
    Route::get('/{type}/{id}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'show'])->name('show');
    Route::post('/{type}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'store'])->name('store');
    Route::put('/{type}/{id}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'update'])->name('update');
    Route::delete('/{type}/{id}', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'destroy'])->name('destroy');

    Route::post('/bulk-delete', [\App\Http\Controllers\Admin\AdresYonetimiController::class, 'bulkDelete'])->name('bulk-delete');
});
