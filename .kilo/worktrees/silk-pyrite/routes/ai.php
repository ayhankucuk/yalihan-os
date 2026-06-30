<?php

use App\Http\Controllers\Api\AIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AI API Routes
|--------------------------------------------------------------------------
|
| Emlak Pro AI özellikleri için API rotaları
| Tüm rotalar auth:sanctum middleware'i ile korunmaktadır
|
*/

// AI routes (normal registration)

// AI Chatbot
Route::post('/chat', [AIController::class, 'chat'])
    ->name('ai.chat')
    ->middleware(['throttle:30,1']); // Dakikada 30 istek

// Fiyat Tahmini
Route::post('/predict-price', [AIController::class, 'predictPrice'])
    ->name('ai.predict-price')
    ->middleware(['throttle:10,1']); // Dakikada 10 istek

// İlan Açıklaması Oluşturma
Route::post('/generate-description', [AIController::class, 'generateDescription'])
    ->name('ai.generate-description')
    ->middleware(['throttle:20,1']); // Dakikada 20 istek

// Talep Analizi ve Eşleştirme
Route::post('/analyze-request', [AIController::class, 'analyzeRequest'])
    ->name('ai.analyze-request')
    ->middleware(['throttle:15,1']); // Dakikada 15 istek

// AI Sağlayıcı Bağlantı Durumu (Admin)
Route::get('/saglayanlar-durumu', function () {
    $aiService = app(\App\Services\AIService::class);

    return response()->json([
        'success' => true,
        'provider' => config('ai.default_provider'),
        'available_providers' => [
            'openai' => ! empty(config('ai.openai.api_key')),
            'claude' => ! empty(config('ai.claude.api_key')),
            'google' => ! empty(config('ai.google.api_key')),
            'deepseek' => ! empty(config('ai.deepseek.api_key')),
        ],
        'onbellek_durumu' => config('ai.cache.enabled', false),
        'timestamp' => now()->toISOString(),
    ]);
})->name('ai.saglayanlar-durumu')->middleware(['auth:sanctum', 'role:admin']);

// AI Kullanım İstatistikleri (Admin)
Route::get('/stats', function () {
    // Bu endpoint gelecekte AI kullanım istatistiklerini döndürebilir
    return response()->json([
        'success' => true,
        'message' => 'İstatistikler henüz mevcut değil.',
        'data' => [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'average_response_time' => 0,
        ],
    ]);
})->name('ai.stats')->middleware(['auth:sanctum', 'role:admin']);
