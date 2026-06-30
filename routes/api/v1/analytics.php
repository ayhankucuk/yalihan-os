<?php

use App\Http\Controllers\Analytics\AnalyticsController;
use App\Http\Controllers\Analytics\ReportsController;
use App\Http\Controllers\Analytics\DashboardController;
use Illuminate\Support\Facades\Route;

/**
 * Phase 6: Analytics Dashboard & Reporting API Routes
 * Base: /api/v1
 * Context7 Compliance: All endpoints use canonical fields
 */

Route::middleware(['auth:sanctum'])->prefix('analytics')->group(function () {
    // ========== ANALYTICS METRICS ENDPOINTS ==========
    Route::controller(AnalyticsController::class)->group(function () {
        // Get all metrics for a property
        Route::get('/metrics/{ilanId}', 'getMetrics');
        
        // Get engagement metrics
        Route::get('/engagement/{ilanId}', 'getEngagementMetrics');
        
        // Get market competitiveness
        Route::get('/market-competitiveness/{ilanId}', 'getMarketCompetitiveness');
        
        // Get ROI potential
        Route::get('/roi/{ilanId}', 'getROIPotential');
        
        // Dashboard endpoints
        Route::get('/dashboard/summary', 'getDashboardSummary');
        Route::get('/dashboard/widgets', 'getWidgetData');
    });

    // ========== REPORTS ENDPOINTS ==========
    Route::controller(ReportsController::class)->prefix('reports')->group(function () {
        // Create new report
        Route::post('/', 'create');
        
        // Get specific report (rapor_durumu: hazirlanıyor|tamamlandı|gonderildi|hata)
        Route::get('/{raporId}', 'show');
        
        // Generate property report
        Route::get('/property/{ilanId}', 'generatePropertyReport');
        
        // Generate market trend report
        Route::post('/market-trend', 'generateMarketTrendReport');
        
        // Generate competitor report
        Route::get('/competitor/{ilanId}', 'generateCompetitorReport');
        
        // Mark report as sent
        Route::put('/{raporId}/send', 'markAsSent');
    });

    // ========== DASHBOARD FILTERS ENDPOINTS ==========
    Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
        // Get filters (analiz_durumu: aktif|sonlandırıldı|kilitli|arsiv)
        Route::get('/filters', 'getFilters');
        
        // Create filter
        Route::post('/filters', 'createFilter');
        
        // Get default filter (varsayilan_mi=true)
        Route::get('/filters/default', 'getDefaultFilter');
        
        // Archive filter
        Route::put('/filters/{filtreId}/archive', 'archiveFilter');
        
        // Lock filter
        Route::put('/filters/{filtreId}/lock', 'lockFilter');
        
        // Apply filter to listings
        Route::get('/apply-filter/{filtreId}', 'applyFilter');
    });
});

