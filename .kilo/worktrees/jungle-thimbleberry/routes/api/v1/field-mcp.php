<?php

/**
 * 🔌 FieldMCP API Rotaları (v1)
 *
 * Hardware Cihazları (Bosch GLM, FLIR ONE) Entegrasyonu
 *
 * ✅ SAB Compliance:
 * - alan_m2 (ASLA area_sqm, meter2)
 * - Sistem Tarafından Onaylı etiketiyle veri kayıt
 * - Otomatik doğrulama ve mühürlü veri
 *
 * Format: /api/v1/field-mcp/*
 *
 * @date 3 Ocak 2026
 */

use App\Http\Controllers\Api\FieldMcpController;
use Illuminate\Support\Facades\Route;

// ✅ SAB Kural #1 — Tüm FieldMCP rotaları auth:sanctum + tenant.context + ai.cost.guard ile korunuyor
// Fix: #48 + #49 — Deploy öncesi kritik auth ve tenant isolation açığı kapatıldı (2026-05-15)
Route::middleware(['auth:sanctum', 'tenant.context', 'ai.cost.guard'])->group(function () {
    // Bosch GLM Lazer Metre
    Route::post('/bosch-glm/measurement', [FieldMcpController::class, 'receiveBoschMeasurement'])
        ->name('field-mcp.bosch-measurement');

    // FLIR ONE Edge Pro Termal Kamera
    Route::post('/flir-one/analysis', [FieldMcpController::class, 'receiveFlirAnalysis'])
        ->name('field-mcp.flir-analysis');

    // Ölçüm Geçmişi
    Route::get('/measurements/{ilanId}', [FieldMcpController::class, 'getMeasurementHistory'])
        ->name('field-mcp.history');

    // Dashboard İstatistikleri (Real-time)
    Route::get('/stats', [FieldMcpController::class, 'getStats'])
        ->name('field-mcp.stats');
});
