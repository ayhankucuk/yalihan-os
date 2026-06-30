<?php

use App\Http\Controllers\AI\AdvancedAIController;
use Illuminate\Support\Facades\Route;

/**
 * Context7 Advanced AI Routes
 *
 * 5 ana AI özelliği için API endpoint'leri
 * Context7 kurallarına uygun
 * Rate limiting ve middleware koruması
 *
 * @version 1.0.0
 *
 * @author Context7 System
 */
Route::prefix('ai')->middleware(['throttle:60,1'])->group(function () {

    // Smart Property Matcher AI
    Route::post('/smart-property-match', [AdvancedAIController::class, 'smartPropertyMatch'])
        ->name('ai.smart.property.match');

    // AI Performance Monitor
    Route::get('/performance-dashboard', [AdvancedAIController::class, 'performanceDashboard'])
        ->name('ai.performance.dashboard');

    Route::get('/performance-report', [AdvancedAIController::class, 'performanceReport'])
        ->name('ai.performance.report');

    // Voice Search AI
    Route::post('/voice-search', [AdvancedAIController::class, 'voiceSearch'])
        ->name('ai.voice.search');

    Route::post('/voice-search-test', [AdvancedAIController::class, 'voiceSearchTest'])
        ->name('ai.voice.search.test');

    // Predictive Analytics AI
    Route::get('/predictive-analytics', [AdvancedAIController::class, 'predictiveAnalytics'])
        ->name('ai.predictive.analytics');

    // Advanced Chatbot
    Route::post('/chatbot', [AdvancedAIController::class, 'chatbot'])
        ->name('ai.chatbot');

    Route::get('/chat-history', [AdvancedAIController::class, 'chatHistory'])
        ->name('ai.chat.history');

    Route::delete('/chat-session', [AdvancedAIController::class, 'clearChatSession'])
        ->name('ai.chat.clear');

    // System Health & Statistics
    Route::get('/system-health', [AdvancedAIController::class, 'systemHealth'])
        ->name('ai.system.health');

    Route::get('/features-overview', [AdvancedAIController::class, 'featuresOverview'])
        ->name('ai.features.overview');

    Route::get('/usage-statistics', [AdvancedAIController::class, 'usageStatistics'])
        ->name('ai.usage.statistics');
});

// Public AI endpoints (rate limited, no CSRF)
Route::prefix('ai/public')->middleware(['throttle:30,1'])->group(function () {

    // Public test endpoint (no auth required)
    Route::get('/test', [AdvancedAIController::class, 'test'])
        ->name('ai.public.test');

    // Public chatbot (no auth required)
    Route::post('/chatbot', [AdvancedAIController::class, 'chatbot'])
        ->name('ai.public.chatbot');

    // Public voice search test
    Route::post('/voice-test', [AdvancedAIController::class, 'voiceSearchTest'])
        ->name('ai.public.voice.test');

    // Public features overview
    Route::get('/features', [AdvancedAIController::class, 'featuresOverview'])
        ->name('ai.public.features');

    // Public image analysis
    Route::post('/image-analysis', [AdvancedAIController::class, 'imageAnalysis'])
        ->name('ai.public.image.analysis');

    // Public price optimization
    Route::post('/price-optimization', [AdvancedAIController::class, 'priceOptimization'])
        ->name('ai.public.price.optimization');
});

// Admin AI Management (Web routes for dashboard)
Route::prefix('admin/ai')->middleware(['web', 'auth'])->group(function () {

    // AI Performance Dashboard (Admin)
    Route::get('/dashboard', [AdvancedAIController::class, 'performanceDashboard'])
        ->name('admin.ai.dashboard');

    // AI Performance Report (Admin)
    Route::get('/report', [AdvancedAIController::class, 'performanceReport'])
        ->name('admin.ai.report');

    // AI System Health - REMOVED (use admin.ilanlar.ai.system-check instead)
    // Route::get('/health', [AdvancedAIController::class, 'systemHealth'])
    //     ->name('admin.ai.health');

    // AI Usage Statistics (Admin)
    Route::get('/statistics', [AdvancedAIController::class, 'usageStatistics'])
        ->name('admin.ai.statistics');

    // AI Usage & Billing (Admin)
    Route::get('/usage', [\App\Http\Controllers\Admin\AiUsageController::class, 'index'])
        ->name('admin.ai.usage');
    Route::post('/usage/top-up', [\App\Http\Controllers\Admin\AiUsageController::class, 'topUp'])
        ->name('admin.ai.usage.top-up');

    // AI Features Management (Admin)
    Route::get('/features', [AdvancedAIController::class, 'featuresOverview'])
        ->name('admin.ai.features');
});
