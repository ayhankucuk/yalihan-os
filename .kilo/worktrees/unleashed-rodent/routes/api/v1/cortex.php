<?php

use App\Http\Controllers\Api\V1\CortexScoreController;
use App\Http\Controllers\Api\V1\CortexSmartAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Yalıhan Cortex AI: Smart API Gateway Routes
|--------------------------------------------------------------------------
|
| Context7 Standard: C7-CORTEX-API-2025-12-23
| Version: 1.0.0
|
| All routes use:
| ✅ auth:sanctum (Fix #79: 2026-05-15 — testing bypass kaldırıldı)
| ✅ sab.compliance middleware (Context7 headers)
| ✅ throttle:api (Rate limiting)
|
*/

Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('cortex')
    ->group(function () {
        // İlan + ROI Full Details
        Route::get('/ilan/{id}/full-details', [CortexSmartAPIController::class, 'getFullDetails'])
            ->name('cortex.ilan.full-details');

        // ROI Hesapla ve Kaydet
        Route::post('/ilan/{id}/calculate-roi', [CortexSmartAPIController::class, 'calculateROI'])
            ->name('cortex.ilan.calculate-roi');

        // Toplu ROI Hesaplama
        
        Route::post('/ilan/batch-calculate-roi', [CortexSmartAPIController::class, 'batchCalculateROI'])
            ->name('cortex.ilan.batch-calculate-roi');

        // Arsa İlanları + ROI Filtering
        Route::get('/arsa', [CortexSmartAPIController::class, 'getArsaWithROI'])
            ->name('cortex.arsa.with-roi');

        // Turizm İlanları + ROI Filtering
        Route::get('/turizm', [CortexSmartAPIController::class, 'getTurizmWithROI'])
            ->name('cortex.turizm.with-roi');

        // Cortex Score: holistic listing quality analysis
        Route::post('/analyze-quality', [CortexScoreController::class, 'analyze'])
            ->name('cortex.score.analyze-quality');
        // 🧠 AI İlan Başlık Optimizasyonu (Cortex v1.2)
        Route::post('/ai/optimize-title', [CortexSmartAPIController::class, 'optimizeTitle']);
    });
