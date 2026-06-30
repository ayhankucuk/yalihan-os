<?php

/**
 * 🧙 İlan Sihirbazı API Rotaları (v1)
 *
 * ✅ SAB Compliance:
 * - Template sistemi (kategori → features)
 * - 5-aşamalı validasyon
 * - Koordinat mühürleme (lat/lng zorunlu)
 * - POI otomatik yükleme
 *
 * Format: /api/v1/ilan-wizard/*
 *
 * @date 3 Ocak 2026
 * @updated 11 Ocak 2026 - IlanTemplate deprecated, FeatureTemplateResolver kullanılıyor
 * @updated 2026-05-15 - Fix #80: auth:sanctum eklendi, wizard rotaları kimlik doğrulamalı
 */


use App\Http\Controllers\Api\IlanWizardController;
use App\Http\Controllers\Api\V1\WizardContextController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // 🎯 Wizard Context Resolution (Step 2 template + features)
    Route::get('/wizard/context', [WizardContextController::class, 'resolve'])
        ->name('wizard.context');

    // 5-Aşamalı İlan Sihirbazı
    Route::post('/wizard/asama-1', [IlanWizardController::class, 'validateAsama1'])
        ->name('wizard.asama1');

    Route::post('/wizard/asama-2', [IlanWizardController::class, 'validateAsama2'])
        ->name('wizard.asama2');

    Route::post('/wizard/asama-3', [IlanWizardController::class, 'validateAsama3'])
        ->name('wizard.asama3');

    Route::post('/wizard/asama-4', [IlanWizardController::class, 'validateAsama4'])
        ->name('wizard.asama4');

    Route::post('/wizard/asama-5', [IlanWizardController::class, 'validateAsama5'])
        ->name('wizard.asama5');

    Route::post('/wizard/submit', [IlanWizardController::class, 'submitWizard'])
        ->name('wizard.submit');

    // 🤖 AI Assistant: Field suggestions for Step 2
    Route::get('/wizard/ai-suggestions/{id}', [IlanWizardController::class, 'getAiSuggestions'])
        ->name('wizard.ai-suggestions');
});
