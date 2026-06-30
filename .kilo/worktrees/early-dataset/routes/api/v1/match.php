<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MatchController;

/**
 * 🎯 Real-time Feature Matching API Routes — Phase 5
 *
 * Prefix: /api/v1/match
 * Middleware: auth:sanctum, throttle:api
 *
 * Özellikler:
 * - Yeni ilan oluşturulduğunda müşteri taleplerini eşle
 * - Yeni talep oluşturulduğunda uygun ilanları bul
 * - Real-time matching score (0-100)
 * - Matching feedback logging
 *
 * Context7 Compliance: SEALED
 * Fix #79 (2026-05-15): auth:sanctum eklendi, testing bypass kaldırıldı.
 */

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    /**
     * 🔍 POST /api/v1/match/find-for-listing
     * 
     * Yeni İlan İçin Talep Eşleştir
     * 
     * Yeni bir ilan oluşturulur oluşturulmaz,
     * bunu aktif olarak arayan müşterileri bul
     * 
     * Request:
     * {
     *   "ilan_id": 42,
     *   "limit": 10
     * }
     * 
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "ilan_id": 42,
     *     "total_matches_found": 7,
     *     "matches": [
     *       {
     *         "talep_id": 101,
     *         "talep_sahibi_id": 5,
     *         "talep_sahibi_adi": "Ahmet Yılmaz",
     *         "match_score": 92.5,
     *         "match_score_badge": "✅ Mükemmel Uyum",
     *         "score_breakdown": {
     *           "feature_score": 95,
     *           "price_score": 90,
     *           "location_score": 85,
     *           "temporal_score": 95
     *         }
     *       },
     *       ...
     *     ]
     *   }
     * }
     * 
     * Ağırlıklar:
     * - Feature Uyum: %40 (kategori, oda, alan)
     * - Fiyat Aralığı: %30 (müşteri bütçesi)
     * - Lokasyon Yakınlığı: %20 (il/ilçe/mahalle)
     * - Zamansal Uyum: %10 (talep güncelliği)
     */
    Route::post('/find-for-listing', [MatchController::class, 'findForListing'])
        ->name('match.find-for-listing');

    /**
     * 🔍 POST /api/v1/match/find-for-demand
     * 
     * Müşteri Talebi İçin İlan Eşleştir
     * 
     * Müşteri talep oluşturur oluşturulmaz,
     * uygun ilanları bul
     * 
     * Request:
     * {
     *   "talep_id": 101,
     *   "limit": 15
     * }
     * 
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "talep_id": 101,
     *     "total_matches_found": 12,
     *     "matches": [
     *       {
     *         "ilan_id": 42,
     *         "ilan_baslik": "Bodrum Yalıkavak'ta Deniz Manzaralı Arsa",
     *         "fiyat": 500000,
     *         "lokasyon": "Muğla/Bodrum/Yalıkavak",
     *         "match_score": 88.3,
     *         "match_score_badge": "👍 Çok İyi Uyum",
     *         "danisman_id": 3
     *       },
     *       ...
     *     ]
     *   }
     * }
     * 
     * Bloke Edilen İlanlar Filtrelenir:
     * - Müşteri tarafından daha önce "bloke" işlemi yapılan ilanlar gösterilmez
     */
    Route::post('/find-for-demand', [MatchController::class, 'findForDemand'])
        ->name('match.find-for-demand');

    /**
     * 🚫 POST /api/v1/match/refuse-match (Phase 6)
     * 
     * Cortex Feedback Loop: Reddedilen Eşleşmeleri Log Et
     * 
     * Danışman bir eşleşmeyi reddettiğinde, sistem bunu log eder
     * ve Cortex AI bundan öğrenerek gelecekte benzer kombinasyonları
     * öneremeyecektir.
     * 
     * Request:
     * {
     *   "matching_feedback_id": 123,
     *   "reason": "kategori_uygun_degil|fiyat_uygun_degil|lokasyon_uygun_degil|diger",
     *   "danisman_id": 5
     * }
     * 
     * Response:
     * {
     *   "success": true,
     *   "message": "Eşleşme başarıyla reddedildi. Cortex AI bundan öğreniyor...",
     *   "data": {
     *     "feedback_id": 123,
     *     "status": "rejected",
     *     "learning_recorded": true
     *   }
     * }
     */
    Route::post('/refuse-match', [MatchController::class, 'refuseMatch'])
        ->name('match.refuse');

    /**
     * 📊 GET /api/v1/match/cortex-learnings (Phase 6)
     * 
     * Cortex AI Learning Report
     * 
     * Sistem artık hangi kombinasyonları öneremediğini,
     * hangi kategorilerin en çok reddedildiğini raporla
     * 
     * Query Parameters:
     *   - days: 30 (default) — Kaç gündür
     * 
     * Response:
     * {
     *   "refusal_reasons": [
     *     { "reason": "kategori_uygun_degil", "count": 15 },
     *     { "reason": "fiyat_uygun_degil", "count": 8 }
     *   ],
     *   "top_refused_categories": [
     *     { "kategori_id": 5, "refusal_count": 12 }
     *   ],
     *   "learning_summary": "Cortex AI bu bilgilerden öğrenerek..."
     * }
     */
    Route::get('/cortex-learnings', [MatchController::class, 'getCortexLearnings'])
        ->name('match.cortex-learnings');
});
