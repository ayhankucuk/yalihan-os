<?php

use App\Http\Controllers\Api\CrossModuleIntelligenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cross-Module Intelligence API Routes (v1)
|--------------------------------------------------------------------------
|
| Modüller arası zeka ve entegrasyon endpoint'leri
| Context7: C7-CROSS-MODULE-INTELLIGENCE-2025-12-06
|
*/

Route::prefix('cross-module')->name('api.cross-module.')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/suggest-listings/{kisiId}', [CrossModuleIntelligenceController::class, 'suggestListingsForCustomer'])
        ->name('suggest-listings')
        ->where('kisiId', '[0-9]+');

    Route::post('/calculate-commission/{ilanId}', [CrossModuleIntelligenceController::class, 'calculateCommission'])
        ->name('calculate-commission')
        ->where('ilanId', '[0-9]+');

    Route::get('/prioritize-task/{islemId}', [CrossModuleIntelligenceController::class, 'prioritizeTaskByCommission'])
        ->name('prioritize-task')
        ->where('islemId', '[0-9]+');

    Route::get('/score-customer/{kisiId}', [CrossModuleIntelligenceController::class, 'scoreCustomerByTasks'])
        ->name('score-customer')
        ->where('kisiId', '[0-9]+');

    Route::get('/unified/{kisiId}', [CrossModuleIntelligenceController::class, 'getUnifiedIntelligence'])
        ->name('unified-intelligence')
        ->where('kisiId', '[0-9]+');
});

// Auto-Learning API Routes
Route::prefix('auto-learning')->name('api.auto-learning.')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/detect-patterns', [\App\Http\Controllers\Api\AutoLearningController::class, 'detectSuccessPatterns'])
        ->name('detect-patterns');

    Route::post('/analyze-failures', [\App\Http\Controllers\Api\AutoLearningController::class, 'analyzeFailures'])
        ->name('analyze-failures');

    Route::get('/improvements', [\App\Http\Controllers\Api\AutoLearningController::class, 'getImprovements'])
        ->name('improvements');
});

// Cortex Neural Network API Routes
Route::prefix('neural-network')->name('api.neural-network.')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/record-interaction', [\App\Http\Controllers\Api\CortexNeuralNetworkController::class, 'recordInteraction'])
        ->name('record-interaction');

    Route::get('/suggest-connections/{module}', [\App\Http\Controllers\Api\CortexNeuralNetworkController::class, 'suggestConnections'])
        ->name('suggest-connections')
        ->where('module', '[a-z_]+');

    Route::get('/network-graph', [\App\Http\Controllers\Api\CortexNeuralNetworkController::class, 'getNetworkGraph'])
        ->name('network-graph');
});

// Strategic Decision Engine API Routes
Route::prefix('strategic-decision')->name('api.strategic-decision.')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/analyze', [\App\Http\Controllers\Api\StrategicDecisionController::class, 'analyzeAndDecide'])
        ->name('analyze');
});

// Predictive Analytics API Routes
Route::prefix('predictive')->name('api.predictive.')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/predict-sales', [\App\Http\Controllers\Api\PredictiveAnalyticsController::class, 'predictSales'])
        ->name('predict-sales');

    Route::post('/predict-revenue', [\App\Http\Controllers\Api\PredictiveAnalyticsController::class, 'predictRevenue'])
        ->name('predict-revenue');
});

// Adaptive UI/UX API Routes
Route::prefix('adaptive-ui')->name('api.adaptive-ui.')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/analyze-behavior/{userId}', [\App\Http\Controllers\Api\AdaptiveUIUXController::class, 'analyzeBehavior'])
        ->name('analyze-behavior')
        ->where('userId', '[0-9]+');

    Route::get('/optimizations/{userId}', [\App\Http\Controllers\Api\AdaptiveUIUXController::class, 'getOptimizations'])
        ->name('optimizations')
        ->where('userId', '[0-9]+');
});
