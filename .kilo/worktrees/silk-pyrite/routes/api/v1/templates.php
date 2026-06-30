<?php

use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\V1\TemplateController as TemplateResolverController;
use Illuminate\Support\Facades\Route;

/**
 * 🎯 Template Routes - Phase 4 Integration + Template System V2
 *
 * Prefix: /api/v1/templates/
 * Context7 Compliance: %100
 */

// Legacy Template Routes (Phase 4)
Route::controller(TemplateController::class)->prefix('templates')->group(function () {
    // 🎯 Auto-Select: Kategori seçildiğinde optimal template + features
    Route::get('auto-select', 'autoSelect')
        ->name('templates.auto-select')
        ->middleware('throttle:ai');

    // 🔐 Publication Type Sealing: Yayın tipi değişiminde zorunlu alanları mühürle
    Route::post('seal-publication-type', 'sealPublicationType')
        ->name('templates.seal-publication-type')
        ->middleware(['auth:sanctum', 'throttle:ai']);

    // 🧹 Clear Cache (Admin)
    Route::post('clear-cache', 'clearCache')
        ->name('templates.clear-cache')
        ->middleware(['auth:sanctum', 'role:admin|super_admin']);
});

// 🆕 Template System V2 Routes (Template Resolver)
// See: docs/technical/TEMPLATE_SYSTEM_ARCHITECTURE.md
Route::controller(TemplateResolverController::class)->prefix('templates')->group(function () {
    // Resolve template by kategori_id + yayin_tipi
    Route::get('resolve', 'resolve')
        ->name('templates.resolve');

    // Get all templates for a category
    Route::get('category/{kategoriId}', 'getByCategory')
        ->name('templates.category');

    // Get features for a template
    Route::get('{templateId}/features', 'getFeatures')
        ->name('templates.features');
});
