<?php

use App\Http\Controllers\Api\V2\IlanController;
use Illuminate\Support\Facades\Route;

/**
 * V2 Ilanlar (Listings) API Routes
 *
 * Context7: All field names are canonical (Context7 compliant)
 * Authentication: Laravel Sanctum tokens required for write operations
 * Versioning: /api/v1/ilanlar
 */

Route::prefix('ilanlar')->group(function () {
    // Public endpoints (list and show)
    Route::get('/', [IlanController::class, 'index'])->name('api.ilanlar.index');
    // Search route must be defined BEFORE {id} to avoid collision
    Route::get('/search', [\App\Http\Controllers\Admin\IlanSearchController::class, 'search'])->name('api.ilanlar.search');
    Route::get('{id}', [IlanController::class, 'show'])->name('api.ilanlar.show');

    // Protected endpoints (auth required for write operations)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [IlanController::class, 'store'])->name('api.ilanlar.store');
        Route::put('{id}', [IlanController::class, 'update'])->name('api.ilanlar.update');
        Route::delete('{id}', [IlanController::class, 'destroy'])->name('api.ilanlar.destroy');

        // Publish/unpublish listing
        Route::patch('{id}/publish', [IlanController::class, 'publish'])->name('api.ilanlar.publish');
        Route::patch('{id}/unpublish', [IlanController::class, 'unpublish'])->name('api.ilanlar.unpublish');
    });
});
