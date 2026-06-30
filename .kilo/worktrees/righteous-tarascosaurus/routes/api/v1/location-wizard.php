<?php

use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Location Wizard API Routes
|--------------------------------------------------------------------------
|
| Context7 Compliant Location API for Wizard
|
*/

Route::prefix('locations')->group(function () {
    Route::get('/districts/{cityId}', [LocationController::class, 'getDistrictsByProvince']);
    Route::get('/neighborhoods/{districtId}', [LocationController::class, 'getNeighborhoodsByDistrict']);
    Route::get('/neighborhood/{id}', [LocationController::class, 'getNeighborhoodCoordinates']);
});
