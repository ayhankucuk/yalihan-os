<?php

use App\Http\Controllers\Admin\DanismanController;
use App\Http\Controllers\Admin\DanismanAIController;
use Illuminate\Support\Facades\Route;

// Danışman Yönetimi
Route::prefix('/danisman')->name('danisman.')->group(function () {
    Route::get('/', [DanismanController::class, 'index'])->name('index');
    Route::get('/create', [DanismanController::class, 'create'])->name('create');
    Route::post('/', [DanismanController::class, 'store'])->name('store');
    Route::get('/{danisman}', [DanismanController::class, 'show'])->name('show');
    Route::get('/{danisman}/edit', [DanismanController::class, 'edit'])->name('edit');
    Route::put('/{danisman}', [DanismanController::class, 'update'])->name('update');
    Route::delete('/{danisman}', [DanismanController::class, 'destroy'])->name('destroy');

    // AJAX işlemleri
    Route::get('/search', [DanismanController::class, 'search'])->name('search');
    Route::post('/toggle-durum/{danisman}', [DanismanController::class, 'toggleDurum'])->name('danisman.toggle.durum');
    Route::post('/update-online-durum/{danisman}', [DanismanController::class, 'updateOnlineDurumu'])->name('update-online-durum');
    Route::post('/bulk-action', [DanismanController::class, 'bulkAction'])->name('danisman.bulk.action');

    // Raporlar
    Route::get('/performance-report', [DanismanController::class, 'performanceReport'])->name('performance-report');
});

// DanismanAI Management Routes
Route::prefix('/danisman-ai')->name('danisman-ai.')->group(function () {
    Route::get('/', [DanismanAIController::class, 'index'])->name('index');
    Route::get('/create', [DanismanAIController::class, 'create'])->name('create');
    Route::post('/store', [DanismanAIController::class, 'store'])->name('store');
    Route::get('/{id}', [DanismanAIController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [DanismanAIController::class, 'edit'])->name('edit');
    Route::put('/{id}', [DanismanAIController::class, 'update'])->name('update');
    Route::delete('/{id}', [DanismanAIController::class, 'destroy'])->name('destroy');
    Route::post('/chat', [DanismanAIController::class, 'chat'])->name('chat');
    Route::post('/analyze', [DanismanAIController::class, 'analyze'])->name('analyze');
    Route::post('/suggest', [DanismanAIController::class, 'suggest'])->name('suggest');
    Route::get('/analytics/data', [DanismanAIController::class, 'analytics'])->name('analytics');
    Route::get('/export/{type}', [DanismanAIController::class, 'export'])->name('export');
    Route::get('/prompt-interface', [DanismanAIController::class, 'promptInterface'])->name('prompt-interface');
});

// Danışman Özel Route'ları
Route::prefix('/kisilerim')->name('kisilerim.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\KisiController::class, 'kisilerim'])->name('index');
});
