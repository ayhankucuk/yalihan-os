<?php

use App\Http\Controllers\Admin\IntelligenceDashboardController;
use App\Http\Controllers\Admin\MarketIntelligenceController;
use Illuminate\Support\Facades\Route;

// Intelligence Dashboard Routes (Context7: Strategic Intelligence)
Route::prefix('/intelligence')->name('intelligence.')->group(function () {
    Route::get('/opportunity-board', [IntelligenceDashboardController::class, 'opportunityBoard'])
        ->name('opportunity-board');
    Route::get('/opportunities', [IntelligenceDashboardController::class, 'apiOpportunities'])
        ->name('opportunities');
    Route::get('/action-score/{kisiId}', [IntelligenceDashboardController::class, 'apiActionScore'])
        ->name('action-score');
});

// Pazar İstihbaratı (Market Intelligence) Routes
Route::prefix('/market-intelligence')->name('market-intelligence.')->group(function () {
    Route::get('/dashboard', [MarketIntelligenceController::class, 'dashboard'])->name('dashboard');
    Route::get('/settings', [MarketIntelligenceController::class, 'settings'])->name('settings');
    Route::get('/compare/{ilan?}', [MarketIntelligenceController::class, 'compare'])->name('compare');
    Route::get('/trends', [MarketIntelligenceController::class, 'trends'])->name('trends');
});

// Pazar İstihbaratı API Routes (n8n bot ve AJAX için)
Route::prefix('/api/market-intelligence')->name('api.market-intelligence.')->group(function () {
    // n8n bot için: Aktif bölgeleri getir
    Route::get('/active-regions', [MarketIntelligenceController::class, 'getActiveRegions'])->name('active-regions');

    // Bölge ayarları yönetimi
    Route::post('/settings', [MarketIntelligenceController::class, 'saveSettings'])->name('settings.save');
    Route::delete('/settings/{id}', [MarketIntelligenceController::class, 'deleteSetting'])->name('settings.delete');
    Route::patch('/settings/{id}/toggle', [MarketIntelligenceController::class, 'toggleSetting'])->name('settings.toggle');
});

// n8n Bot Sync Endpoint
Route::prefix('/api/admin/market-intelligence')->name('api.market-intelligence.sync.')->group(function () {
    Route::post('/compare-price', [MarketIntelligenceController::class, 'comparePrice'])->name('compare-price');
    Route::post('/analyze-trends', [MarketIntelligenceController::class, 'analyzeTrends'])->name('analyze-trends');
    Route::post('/sync', [MarketIntelligenceController::class, 'sync'])->name('sync');
});
