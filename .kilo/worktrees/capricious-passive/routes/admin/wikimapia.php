<?php

use Illuminate\Support\Facades\Route;

// Wikimapia Site/Apartman Sorgulama Paneli
Route::prefix('/wikimapia-search')->name('wikimapia-search.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'index'])->name('index');
    Route::post('/search', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'search'])->name('search');
    Route::post('/search-places', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'searchPlaces'])->name('search-places');
    Route::post('/nearby', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'nearby'])->name('nearby');
    Route::get('/place/{id}', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'getPlaceDetails'])->name('place-details');
    Route::post('/save-site', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'saveSite'])->name('save-site');
    Route::get('/saved-sites', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'getSavedSites'])->name('saved-sites');

    // ✅ SAB: TurkiyeAPI entegrasyonu route'ları (harita sistemi için)
    Route::get('/location-data', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'getLocationData'])->name('location-data');
    Route::post('/location-from-coordinates', [\App\Http\Controllers\Admin\WikimapiaSearchController::class, 'getLocationFromCoordinates'])->name('location-from-coordinates');
});
