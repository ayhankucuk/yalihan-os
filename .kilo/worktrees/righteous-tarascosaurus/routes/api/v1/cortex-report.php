<?php

use App\Http\Controllers\Api\V1\CortexReportController;
use Illuminate\Support\Facades\Route;

/**
/* Disable cortex report when AI is turned off */
if (! config('ai.enabled')) {
    Route::any('/cortex/report/{any}', function () {
        return response()->json(['success' => false, 'message' => 'Cortex Report disabled'], 503);
    })->where('any', '.*');
    return;
}

/** Yalıhan Cortex AI: Report Routes */

// Fix #80 (2026-05-15): auth:sanctum eklendi
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('cortex/report')
    ->group(function () {
        // Generate investment report
        Route::post('/{id}/generate', [CortexReportController::class, 'generateReport'])
            ->name('cortex.report.generate');

        // Download report
        Route::get('/{id}/download', [CortexReportController::class, 'downloadReport'])
            ->name('cortex.report.download');

        // List reports for property
        Route::get('/{id}/list', [CortexReportController::class, 'listReports'])
            ->name('cortex.report.list');

        // Report statistics
        Route::get('/stats', [CortexReportController::class, 'getReportStats'])
            ->name('cortex.report.stats');
    });
