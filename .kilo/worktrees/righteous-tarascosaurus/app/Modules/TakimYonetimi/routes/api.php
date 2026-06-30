<?php

use App\Modules\TakimYonetimi\Controllers\API\GorevApiController;
use App\Modules\TakimYonetimi\Controllers\API\ProjeApiController;
use App\Modules\TakimYonetimi\Controllers\API\TakimApiController;
use Illuminate\Support\Facades\Route;

// Takım Yönetimi API Routes
Route::middleware(['api', 'auth:sanctum'])->prefix('api/takim-yonetimi')->group(function () {

    // Görev API
    Route::apiResource('gorevler', GorevApiController::class);
    Route::post('gorevler/{gorev}/atama', [GorevApiController::class, 'atama']);
    Route::post('gorevler/{gorev}/durum', [GorevApiController::class, 'updateStatus']);
    Route::get('gorevler/{gorev}/rapor', [GorevApiController::class, 'rapor']);
    Route::post('gorevler/{gorev}/dosya', [GorevApiController::class, 'addFile']);
    Route::delete('gorevler/{gorev}/dosya/{dosya}', [GorevApiController::class, 'deleteFile']);
    Route::get('gorevler/{gorev}/gecmis', [GorevApiController::class, 'gecmis']);

    // Takım API
    Route::apiResource('takimlar', TakimApiController::class);
    Route::post('takimlar/{takim}/uye', [TakimApiController::class, 'addMember']);
    Route::delete('takimlar/{takim}/uye/{uye}', [TakimApiController::class, 'removeMember']);
    Route::get('takimlar/{takim}/performans', [TakimApiController::class, 'performans']);
    Route::get('takimlar/{takim}/istatistikler', [TakimApiController::class, 'istatistikler']);

    // Proje API
    Route::apiResource('projeler', ProjeApiController::class);
    Route::post('projeler/{proje}/gorev', [ProjeApiController::class, 'addTask']);
    Route::get('projeler/{proje}/rapor', [ProjeApiController::class, 'rapor']);
    Route::get('projeler/{proje}/gorevler', [ProjeApiController::class, 'gorevler']);

    // Dashboard API
    Route::get('dashboard', [GorevApiController::class, 'dashboard']);
    Route::get('raporlar', [GorevApiController::class, 'raporlar']);
    Route::get('istatistikler', [GorevApiController::class, 'istatistikler']);

    // Context7 AI API
    Route::post('ai/gorev-onerisi', [GorevApiController::class, 'aiGorevOnerisi']);
    Route::post('ai/performans-analizi', [GorevApiController::class, 'aiPerformansAnalizi']);
    Route::post('ai/otomatik-atama', [GorevApiController::class, 'aiOtomatikAtama']);
});

// Public API Routes (Webhook'lar için)
// Route::prefix('api/takim-yonetimi')->group(function () {
// });
