<?php

use Illuminate\Support\Facades\Route;

// AI Automation & Integrations Routes
// Context7: C7-AI-AUTOMATION-INTEGRATION-2025-12-19
Route::prefix('/integrations')->name('integrations.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\IntegrationsController::class, 'index'])->name('index');
    Route::put('/{integration}', [\App\Http\Controllers\Admin\IntegrationsController::class, 'update'])->name('update');
    Route::post('/{integration}/test', [\App\Http\Controllers\Admin\IntegrationsController::class, 'test'])->name('test');

    // n8n Workflow Management
    Route::get('/n8n-workflows', [\App\Http\Controllers\Admin\IntegrationsController::class, 'n8nWorkflows'])->name('n8n-workflows');
});

// Voice Search Settings
Route::prefix('/voice-search')->name('voice-search.')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\Admin\IntegrationsController::class, 'voiceSearchSettings'])->name('settings');
    Route::post('/settings', [\App\Http\Controllers\Admin\IntegrationsController::class, 'updateVoiceSearchSettings'])->name('settings.update');
});

// AI Ayarları
Route::prefix('/ai-settings')->name('ai-settings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AISettingsController::class, 'index'])->name('index');
    Route::put('/', [\App\Http\Controllers\Admin\AISettingsController::class, 'update'])->name('update');
    Route::post('/', [\App\Http\Controllers\Admin\AISettingsController::class, 'update'])->name('update.post'); // POST support for form compatibility
    Route::post('/test-provider', [\App\Http\Controllers\Admin\AISettingsController::class, 'testProvider'])->name('test-provider');
    Route::post('/test-query', [\App\Http\Controllers\Admin\AISettingsController::class, 'testQuery'])->name('test-query');
    Route::post('/test-ollama', [\App\Http\Controllers\Admin\AISettingsController::class, 'testOllamaConnection'])->name('test-ollama');
    Route::get('/analytics', [\App\Http\Controllers\Admin\AISettingsController::class, 'analytics'])->name('analytics'); // Context7: Real-time analytics
    Route::get('/provider-durum', [\App\Http\Controllers\Admin\AISettingsController::class, 'getProviderStatus'])->name('provider-durum');
    Route::get('/statistics', [\App\Http\Controllers\Admin\AISettingsController::class, 'statistics'])->name('statistics');
    Route::post('/proxy-ollama', [\App\Http\Controllers\Admin\AISettingsController::class, 'proxyOllama'])->name('proxy-ollama');

    // API Key ve Ayarlar Güncelleme Route'ları (2025-11-30 eklendi)
    Route::post('/update-api-key', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateApiKey'])->name('update-api-key');
    Route::post('/update-ollama-url', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateOllamaUrl'])->name('update-ollama-url');
    Route::post('/update-provider-model', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateProviderModel'])->name('update-provider-model');
    Route::post('/update-openai-organization', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateOpenAIOrganization'])->name('update-openai-organization');
    Route::post('/update-locale', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateLocale'])->name('update-locale');
    Route::post('/update-currency', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateCurrency'])->name('update-currency');
});

// N2: Outbound Notification Logs
Route::prefix('/outbound-notifications')->name('outbound-notifications.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\OutboundNotificationController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\Admin\OutboundNotificationController::class, 'show'])->name('show');
    Route::post('/{id}/retry', [\App\Http\Controllers\Admin\OutboundNotificationController::class, 'retry'])->name('retry');
    Route::post('/test', [\App\Http\Controllers\Admin\OutboundNotificationController::class, 'testSend'])->name('test');
});
