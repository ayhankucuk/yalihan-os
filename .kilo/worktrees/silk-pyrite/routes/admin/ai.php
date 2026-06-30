<?php

use Illuminate\Support\Facades\Route;

// AI System Hub Routes (/admin/ai/*)
Route::prefix('/ai')->name('ai.')->group(function () {
    // Debug Routes
    Route::get('/debug/decisions', [\App\Http\Controllers\Admin\AiDebugController::class, 'decisions'])->name('debug.decisions');

    // Logs & Monitoring
    Route::get('/logs', [\App\Http\Controllers\AI\AdvancedAIController::class, 'showLogs'])->name('logs');
    Route::get('/dashboard', [\App\Http\Controllers\Admin\IlanQualityDashboardController::class, 'index'])->name('dashboard');
    // AI ROI Dashboard (Phase S)
    Route::get('/roi-dashboard', [\App\Http\Controllers\Admin\AiObservabilityController::class, 'index'])->name('roi-dashboard');

    Route::get('/monitoring', [\App\Http\Controllers\Admin\CortexMonitoringController::class, 'index'])
        ->name('monitoring')->middleware(['throttle:30,1']);
    Route::get('/monitoring.json', [\App\Http\Controllers\Admin\CortexMonitoringController::class, 'json'])
        ->name('monitoring.json')->middleware(['throttle:30,1']);

    // AI Operations (for İlan module)
    Route::post('/bulk-analyze', [\App\Http\Controllers\Admin\AI\IlanAIController::class, 'bulkAnalyze'])->name('bulk-analyze');
    Route::post('/suggest', [\App\Http\Controllers\Admin\AI\IlanAIController::class, 'suggest'])->name('suggest');
    Route::get('/health', [\App\Http\Controllers\Admin\AI\IlanAIController::class, 'health'])->name('system-check');
    Route::post('/title', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'generateTitle'])
        ->name('title')->middleware(['throttle:20,1']);
    Route::post('/description', [\App\Http\Controllers\Admin\IlanAITitleDescriptionController::class, 'generateDescription'])
        ->name('description')->middleware(['throttle:20,1']);
    Route::post('/quality-check', [\App\Http\Controllers\Admin\IlanAIQualityController::class, 'qualityCheck'])
        ->name('quality-check')->middleware(['throttle:20,1']);

    // Learning & Analytics
    Route::get('/quality-learning', [\App\Http\Controllers\Admin\CortexLearningController::class, 'qualityLearning'])
        ->name('quality-learning')->middleware(['throttle:10,1']);
    Route::get('/quality-learning/global', [\App\Http\Controllers\Admin\CortexLearningController::class, 'globalStats'])
        ->name('quality-learning.global')->middleware(['throttle:10,1']);

    // DEPRECATED (2026-01-11): IlanTemplate system removed
    // Use FeatureTemplateResolver + UPS instead
    // Route::get('/template-advice', [\App\Http\Controllers\Admin\IlanTemplateAdvisorController::class, 'index'])
    //     ->name('template-advice')->middleware(['throttle:10,1']);

    Route::get('/feature-coverage', [\App\Http\Controllers\Admin\CortexFeatureCoverageController::class, 'index'])
        ->name('feature-coverage')->middleware(['throttle:30,1']);
    Route::get('/feature-coverage/global', [\App\Http\Controllers\Admin\CortexFeatureCoverageController::class, 'global'])
        ->name('feature-coverage.global')->middleware(['throttle:30,1']);

    // Phase 13 - Epic 3: Call Intelligence
    Route::post('/calls/{activity}/analyze', [\App\Http\Controllers\Admin\CallAnalysisController::class, 'analyze'])
        ->name('calls.analyze');

    // Property Hub AI (Stage 1 Refactor)
    Route::prefix('/property')->name('property.')->group(function () {
        Route::post('/analyze', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'analyze'])->name('analyze');
        Route::post('/extract-features', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'extractFeatures'])->name('extract-features');
        Route::post('/suggest-template', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'suggestTemplate'])->name('suggest-template');
        Route::post('/generate-template/{templateId}', [\App\Http\Controllers\Admin\AI\PropertyAIController::class, 'generateTemplate'])->name('generate-template');
    });
});
