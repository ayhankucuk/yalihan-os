<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IntelligenceHubController;

/*
|--------------------------------------------------------------------------
| IntelligenceHub API Routes
|--------------------------------------------------------------------------
|
| Context7 Standard: C7-INTELLIGENCE-HUB-API-2026-01-07
|
| Merkezi zeka orkestrasyon servisi için API endpoint'leri
|
*/

Route::prefix('intelligence')->name('intelligence.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/listing-health/{ilanId}', [IntelligenceHubController::class, 'getListingHealth'])
        ->name('listing-health');
    Route::post('/draft-health', [IntelligenceHubController::class, 'getDraftHealth'])
        ->name('draft-health');
});

