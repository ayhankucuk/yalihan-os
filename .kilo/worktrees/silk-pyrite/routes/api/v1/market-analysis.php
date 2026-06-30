<?php

use App\Http\Controllers\Api\MarketAnalysisController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Market Analysis API Routes (v1)
|--------------------------------------------------------------------------
|
| TKGM Learning Engine powered market analysis endpoints
|
| Routes:
| - POST   /api/v1/market-analysis/predict-price
| - GET    /api/v1/market-analysis/{il_id}/{ilce_id?}
| - GET    /api/v1/market-analysis/hotspots/{il_id}
| - GET    /api/v1/market-analysis/stats
|
*/

Route::prefix('market-analysis')->name('api.market-analysis.')->group(function () {

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 💰 FİYAT TAHMİNİ
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::post('/predict-price', [MarketAnalysisController::class, 'predictPrice'])
        ->name('predict-price');

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 📊 PAZAR ANALİZİ
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::get('/{il_id}/{ilce_id?}', [MarketAnalysisController::class, 'getAnalysis'])
        ->name('analysis')
        ->where(['il_id' => '[0-9]+', 'ilce_id' => '[0-9]+']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 🏆 YATIRIM HOTSPOT'LAR
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::get('/hotspots/{il_id}', [MarketAnalysisController::class, 'getHotspots'])
        ->name('hotspots')
        ->where(['il_id' => '[0-9]+']);

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 📈 İSTATİSTİKLER
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    Route::get('/stats', [MarketAnalysisController::class, 'getStats'])
        ->name('stats');
});
