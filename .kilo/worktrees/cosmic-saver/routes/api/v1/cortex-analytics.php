<?php

use App\Http\Controllers\Api\V1\CortexAnalyticsDashboardController;
use App\Http\Controllers\Api\V1\CortexSmartAPIController;
use Illuminate\Support\Facades\Route;

/**
/* Disable cortex analytics when AI is turned off */
if (! config('ai.enabled')) {
    Route::any('/cortex/analytics/{any}', function () {
        return response()->json(['success' => false, 'message' => 'Cortex Analytics disabled'], 503);
    })->where('any', '.*');
    return;
}

/** Yalıhan Cortex AI: Analytics & Extended Features */

Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('cortex/analytics')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [CortexAnalyticsDashboardController::class, 'getDashboard'])
            ->name('cortex.analytics.dashboard');

        // Performance Metrics
        Route::get('/performance', [CortexAnalyticsDashboardController::class, 'getPerformanceMetrics'])
            ->name('cortex.analytics.performance');

        // Clear Cache
        Route::post('/clear-cache', [CortexAnalyticsDashboardController::class, 'clearCache'])
            ->name('cortex.analytics.clear-cache');
    });

// Golden Visa specific endpoints
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('cortex/golden-visa')
    ->group(function () {
        // Analyze single property for Golden Visa
        Route::post('/analyze/{id}', [CortexSmartAPIController::class, 'analyzeGoldenVisa'])
            ->name('cortex.golden-visa.analyze');

        // Get all eligible properties
        Route::get('/eligible', [CortexSmartAPIController::class, 'getGoldenVisaEligible'])
            ->name('cortex.golden-visa.eligible');
    });

// Spatial Intelligence endpoints
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('cortex/spatial')
    ->group(function () {
        // Single property spatial data
        Route::get('/{id}', [CortexSmartAPIController::class, 'getSpatialIntelligence'])
            ->name('cortex.spatial.single');

        // Batch spatial data
        Route::post('/batch', [CortexSmartAPIController::class, 'getBatchSpatial'])
            ->name('cortex.spatial.batch');
    });
