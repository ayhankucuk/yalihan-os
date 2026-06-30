<?php

use Illuminate\Support\Facades\Route;

// Telegram Bot Yönetimi
Route::prefix('/telegram-bot')->name('telegram-bot.')->group(function () {
    Route::get('/', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'index'])->name('index');
    Route::get('/aktiflik-durumu', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'getAktiflikDurumu'])->name('get-aktiflik-durumu');
    Route::post('/set-webhook', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'setWebhook'])->name('set-webhook');
    Route::post('/send-test-message', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'sendTestMessage'])->name('send-test-message');
    Route::get('/webhook-info', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'getWebhookInfo'])->name('webhook-info');
    Route::post('/update-settings', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'updateSettings'])->name('update-settings');
    Route::post('/send-test', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'sendTestMessage'])->name('send-test');
    Route::get('/test', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'testBot'])->name('test');
    Route::post('/generate-pairing-code', [\App\Modules\TakimYonetimi\Controllers\Admin\TelegramBotController::class, 'generatePairingCode'])->name('generate-pairing-code');
});

// Takım Yönetimi ve Görev Dağılımı (Ek Rotalar - Çakışmaları Önlemek İçin)
// NOT: takim-performans ve gorevler/toplu-ata routes/admin.php'de zaten tanımlı.
// Duplicate route isimleri route:cache'i kırıyor — bu blok kaldırıldı.
