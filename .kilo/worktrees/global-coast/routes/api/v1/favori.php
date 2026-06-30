<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FavoriController;

/**
 * Context7: İlan Favori API Routes
 * - GET /api/v1/ilanlar/favoriler (liste)
 * - POST /api/v1/ilanlar/{ilan}/favori (toggle)
 * - DELETE /api/v1/ilanlar/{ilan}/favori (çıkar)
 * - GET /api/v1/dashboard/favoriler (widget metriği)
 */

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    // Kişinin favori ilanlarını listele
    Route::get('/ilanlar/favoriler', [FavoriController::class, 'listFavori'])
        ->name('api.favori.list');

    // İlan'ı favoriye ekle/çıkar (toggle)
    Route::post('/ilanlar/{ilan}/favori', [FavoriController::class, 'toggle'])
        ->name('api.favori.toggle');

    // İlan'ı favorilerden çıkar
    Route::delete('/ilanlar/{ilan}/favori', [FavoriController::class, 'cikar'])
        ->name('api.favori.cikar');

    // İlan'ın favori sayısını kontrol et
    Route::get('/ilanlar/{ilan}/favori/sayisi', [FavoriController::class, 'getFavoriSayisi'])
        ->name('api.favori.sayisi');

    // Dashboard widget: En çok favorilenen ilanlar + artış
    Route::get('/dashboard/favoriler', [FavoriController::class, 'dashboardMetrikleri'])
        ->name('api.dashboard.favoriler');
});
