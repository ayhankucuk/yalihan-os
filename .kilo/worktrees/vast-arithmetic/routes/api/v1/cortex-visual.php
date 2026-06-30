<?php

use App\Http\Controllers\Api\V1\CortexVisualController;
use Illuminate\Support\Facades\Route;

/**
 * Yalıhan Cortex AI: Visual Analysis Routes
 *
 * Context7 Standard: C7-VISUAL-ROUTES-2025-12-23
 * Version: 1.0.0
 */

/* Yalıhan Cortex AI: Visual Analysis Routes */
Route::prefix('cortex/visual')
    ->group(function () {
        // Analyze property photos
        Route::post('/{id}/analyze', [CortexVisualController::class, 'analyzePhotos'])
            ->name('cortex.visual.analyze');

        // Batch analyze
        Route::post('/batch-analyze', [CortexVisualController::class, 'batchAnalyze'])
            ->name('cortex.visual.batch-analyze');

        // Automation score stats
        Route::get('/automation-stats', [CortexVisualController::class, 'getAutomationStats'])
            ->name('cortex.visual.automation-stats');
    });
