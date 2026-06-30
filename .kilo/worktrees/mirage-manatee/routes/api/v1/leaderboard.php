<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LeaderboardController;

/**
 * 🏆 Leaderboard API Routes — Phase 6
 * 
 * Danışman Performance Sıralaması
 * 
 * Prefix: /api/v1/leaderboard
 * Middleware: api, throttle:api
 * 
 * Context7 Compliance: SEALED
 */

Route::middleware(['api', 'throttle:api'])->group(function () {
    
    /**
     * 🏆 GET /api/v1/leaderboard/danismanlar
     * 
     * Tüm danışmanları performance score ortalamalarına göre sırala
     * 
     * Query Parameters:
     *   - limit: 50 (default)
     *   - period: all|month|week (default: all)
     * 
     * Response:
     * {
     *   "leaderboard": [
     *     {
     *       "rank": 1,
     *       "danisman_id": 5,
     *       "ad_soyad": "Ahmet Yılmaz",
     *       "average_score": 92.5,
     *       "score_badge": "⭐ Teknik Lider",
     *       "total_ilanlar": 42,
     *       "bosch_count": 35,
     *       "flir_count": 28,
     *       "bosch_usage_percent": 83.33,
     *       "flir_usage_percent": 66.67,
     *       "badge_details": {
     *         "badges": ["⭐ Teknik Lider", "🔧 Bosch Uzmanı", "🌡️ FLIR Sertifikalı"],
     *         "bosch_expert": true,
     *         "flir_certified": true,
     *         "fully_equipped": true
     *       }
     *     }
     *   ]
     * }
     */
    Route::get('/danismanlar', [LeaderboardController::class, 'leaderboard'])
        ->name('leaderboard.danismanlar');

    /**
     * 🎯 GET /api/v1/leaderboard/danismanlar/{id}/rank
     * 
     * Belirli bir danışmanın sırasını ve detaylarını getir
     * 
     * Response:
     * {
     *   "danisman_id": 5,
     *   "ad_soyad": "Ahmet Yılmaz",
     *   "average_score": 92.5,
     *   "rank": 1,
     *   "score_badge": "⭐ Teknik Lider"
     * }
     */
    Route::get('/danismanlar/{id}/rank', [LeaderboardController::class, 'getDanismanRank'])
        ->name('leaderboard.danismanlar.rank');
});
