<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BulkListingController;

/**
 * 📦 Toplu İşlem (Bulk) API Routes — Phase 5
 *
 * Prefix: /api/v1/bulk
 * Middleware: api, throttle:api, auth:sanctum
 *
 * Özellikler:
 * - Excel/JSON toplu içeri aktarım
 * - Otomatik kategori atama (TemplateService)
 * - Toplu alan güncelleme
 * - Progress tracking (Queue)
 *
 * Context7 Compliance: SEALED
 */

Route::middleware(['api', 'throttle:api', 'auth:sanctum'])->group(function () {

    /**
     * 📥 POST /api/v1/bulk/import
     *
     * Excel (XLSX/XLS) veya JSON dosyası yükle
     *
     * Request:
     * {
     *   "file": <binary>,
     *   "danisman_id": 5,
     *   "auto_categorize": true,
     *   "auto_score": true
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "imported_count": 42,
     *     "failed_count": 3,
     *     "job_id": "uuid",
     *     "progress_url": "/api/v1/bulk/progress?job_id=uuid"
     *   }
     * }
     */
    Route::post('/import', [BulkListingController::class, 'importBulk'])
        ->name('bulk.import');

    /**
     * 🔄 POST /api/v1/bulk/update
     *
     * Toplu alan güncellemesi
     *
     * Request:
     * {
     *   "updates": [
     *     { "ilan_id": 1, "fiyat": 500000, "one_cikan": true },
     *     { "ilan_id": 2, "fiyat": 600000, "gosterim_sirasi": 1 }
     *   ]
     * }
     *
     * Yasak alanlar otomatik filtrelenir:
     * - islem_durumu, sira, aktiflik_durumu, lat, lng, yayin_durumu, kisi_id vb.
     *
     * Response:
     * {
     *   "success": true,
     *   "updated_count": 2
     * }
     */
    Route::post('/update', [BulkListingController::class, 'updateBulk'])
        ->name('bulk.update');

    /**
     * 📊 GET /api/v1/bulk/progress?job_id=uuid
     *
     * Toplu işlem ilerleme durumu
     *
     * Response:
     * {
     *   "job_id": "uuid",
     *   "islem_durumu": "processing|completed|failed",
     *   "progress_percent": 75,
     *   "processed": 31,
     *   "total": 42,
     *   "errors": [ ... ]
     * }
     */
    Route::get('/progress', [BulkListingController::class, 'getProgress'])
        ->name('bulk.progress');
});
