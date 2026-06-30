<?php

use App\Http\Controllers\Api\Frontend\PropertyFeedController;
use App\Http\Controllers\Api\V1\WizardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend API Routes (v1)
|--------------------------------------------------------------------------
|
| Public-facing API endpoints for frontend
|
*/

Route::prefix('frontend')->name('api.frontend.')->group(function () {
    // Property Feed API
    Route::prefix('properties')->name('properties.')->group(function () {
        Route::get('/', [PropertyFeedController::class, 'index'])->name('index');
        Route::get('/featured', [PropertyFeedController::class, 'featured'])->name('featured');
        Route::get('/{propertyId}', [PropertyFeedController::class, 'show'])->name('show');
    });
});

// Phase 6: Wizard API - Smart Features & AI Suggestions
Route::prefix('wizard')->name('api.wizard.')->middleware(['web', 'auth'])->group(function () {
    // Smart API: Features grouped by ui_group
    Route::get('/features', [WizardController::class, 'features'])->name('features');

    // 🔱 CRITICAL #1: Step 2 Field Validation - Category-based required fields
    Route::post('/validate-step-2', [WizardController::class, 'validateStep2'])->name('validate-step-2');

    // AI Suggestion Engine: AI-powered feature recommendations
    Route::post('/suggest', [WizardController::class, 'suggest'])->name('suggest');

    // Phase 5: Visual AI Backend (Simulation)
    Route::post('/analyze-images', [WizardController::class, 'analyzeImages'])->name('visual-analysis');

    // 🎯 Phase 4: AI Template Auto-Select
    Route::get('/template-auto-select', [WizardController::class, 'templateAutoSelect'])->name('template-auto-select');


    // Price to Text Conversion
    Route::post('/price-to-text', [WizardController::class, 'priceToText'])->name('price-to-text');

    // 📊 Phase 7: AI Telemetry - User interaction logging
    Route::post('/telemetry/feature-action', [WizardController::class, 'logFeatureAction'])->name('telemetry.feature-action');

    // 🛰️ SSOT Wizard Context Resolver (Phase 24)
    Route::get('/context', [\App\Http\Controllers\Api\V1\WizardContextController::class, 'resolve'])->name('context');

    // 🏗️ Schema-Driven Field Schema API (Wizard Engine V2)
    Route::get('/field-schema', [\App\Http\Controllers\Api\IlanWizardController::class, 'fieldSchema'])->name('field-schema');

    // 🌳 Category Tree API (Step 1 cascading selection)
    Route::get('/category-tree', [\App\Http\Controllers\Api\IlanWizardController::class, 'categoryTree'])->name('category-tree');
    // 💾 Wizard Draft Engine (Phase 2 - State Management)
    Route::post('/wizard/update-field', [\App\Http\Controllers\Api\IlanWizardController::class, 'updateField'])->name('wizard.update-field');
    Route::get('/wizard/draft/{id}', [\App\Http\Controllers\Api\IlanWizardController::class, 'getDraft'])->name('wizard.get-draft');
});
