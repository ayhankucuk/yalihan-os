<?php

use App\Http\Controllers\Api\V1\CortexHeatmapController;
use Illuminate\Support\Facades\Route;

/**
/* Disable cortex heatmap when AI is turned off */
if (! config('ai.enabled')) {
    Route::any('/cortex/heatmap/{any}', function () {
        return response()->json(['success' => false, 'message' => 'Cortex Heatmap disabled'], 503);
    })->where('any', '.*');
    return;
}

/** Yalıhan Cortex AI: Heatmap Routes */

// Fix #80 (2026-05-15): auth:sanctum eklendi
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('cortex/heatmap')
    ->group(function () {
        // Generate heatmap
        Route::get('/generate', [CortexHeatmapController::class, 'generateHeatmap'])
            ->name('cortex.heatmap.generate');

        // Get properties in grid cell
        Route::get('/cell/{cellId}', [CortexHeatmapController::class, 'getCellProperties'])
            ->name('cortex.heatmap.cell-properties');

        // Heatmap metadata
        Route::get('/metadata', [CortexHeatmapController::class, 'getHeatmapMetadata'])
            ->name('cortex.heatmap.metadata');
    });
